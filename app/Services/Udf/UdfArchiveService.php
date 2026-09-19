<?php

namespace App\Services\Udf;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class UdfArchiveService
{
    /**
     * @return array{content_xml: string, content_entry: string, entries: list<string>}
     */
    public function read(string $disk, string $path): array
    {
        $this->assertArchiveSize($disk, $path);

        return $this->withLocalCopy($disk, $path, function (string $localPath): array {
            $archive = $this->open($localPath, ZipArchive::RDONLY);

            try {
                $inspection = $this->inspect($archive);
                $contentXml = $archive->getFromName($inspection['content_entry']);

                if (! is_string($contentXml)) {
                    throw new UdfException('UDF içindeki content.xml okunamadı.', 'content_xml_unreadable');
                }

                return [
                    'content_xml' => $contentXml,
                    'content_entry' => $inspection['content_entry'],
                    'entries' => $inspection['entries'],
                ];
            } finally {
                $archive->close();
            }
        });
    }

    /**
     * @return array{content_xml: string, content_entry: string, entries: list<string>}
     */
    public function readLocal(string $path): array
    {
        if (! is_readable($path) || filesize($path) === false) {
            throw new UdfException('UDF yükleme dosyası okunamadı.', 'storage_read_failed');
        }

        if (filesize($path) > $this->limit('max_archive_size')) {
            throw new UdfException('UDF dosyası izin verilen arşiv boyutunu aşıyor.', 'archive_too_large');
        }

        $archive = $this->open($path, ZipArchive::RDONLY);

        try {
            $inspection = $this->inspect($archive);
            $contentXml = $archive->getFromName($inspection['content_entry']);

            if (! is_string($contentXml)) {
                throw new UdfException('UDF içindeki content.xml okunamadı.', 'content_xml_unreadable');
            }

            return [
                'content_xml' => $contentXml,
                'content_entry' => $inspection['content_entry'],
                'entries' => $inspection['entries'],
            ];
        } finally {
            $archive->close();
        }
    }

    public function buildEditedCopy(string $disk, string $path, string $contentXml): string
    {
        if (strlen($contentXml) > $this->limit('max_xml_size')) {
            throw new UdfException('Oluşturulan UDF XML içeriği izin verilen boyutu aşıyor.', 'xml_too_large');
        }

        $this->assertArchiveSize($disk, $path);

        return $this->withLocalCopy($disk, $path, function (string $localPath) use ($contentXml): string {
            $archive = $this->open($localPath);

            try {
                $inspection = $this->inspect($archive);

                if (! $archive->addFromString($inspection['content_entry'], $contentXml)) {
                    throw new UdfException('Yeni UDF içeriği arşive yazılamadı.', 'archive_write_failed');
                }
            } finally {
                $archive->close();
            }

            $contents = File::get($localPath);

            if (strlen($contents) > $this->limit('max_archive_size')) {
                throw new UdfException('Oluşturulan UDF dosyası izin verilen boyutu aşıyor.', 'archive_too_large');
            }

            return $contents;
        });
    }

    /**
     * @return array{content_entry: string, entries: list<string>}
     */
    private function inspect(ZipArchive $archive): array
    {
        if ($archive->numFiles > $this->limit('max_entries')) {
            throw new UdfException('UDF arşivi izin verilenden fazla dosya içeriyor.', 'too_many_entries');
        }

        $entries = [];
        $contentEntries = [];
        $totalUncompressedSize = 0;

        for ($index = 0; $index < $archive->numFiles; $index++) {
            $stat = $archive->statIndex($index);

            if (! is_array($stat) || ! isset($stat['name'], $stat['size'])) {
                throw new UdfException('UDF arşiv girdisi okunamadı.', 'invalid_entry');
            }

            $name = (string) $stat['name'];
            $this->assertSafeEntryName($name);
            $entries[] = $name;

            $entrySize = (int) $stat['size'];
            if ($entrySize < 0 || $entrySize > $this->limit('max_uncompressed_size') - $totalUncompressedSize) {
                throw new UdfException('UDF arşivinin açılmış boyutu güvenlik limitini aşıyor.', 'uncompressed_size_exceeded');
            }

            $totalUncompressedSize += $entrySize;

            if ($this->isContentXmlEntry($name)) {
                $contentEntries[] = $name;

                if ($entrySize > $this->limit('max_xml_size')) {
                    throw new UdfException('UDF XML içeriği izin verilen boyutu aşıyor.', 'xml_too_large');
                }
            }
        }

        if ($contentEntries === []) {
            throw new UdfException('UDF arşivinde content.xml bulunamadı.', 'content_xml_missing');
        }

        if (count($contentEntries) > 1) {
            throw new UdfException('UDF arşivinde birden fazla content.xml bulundu.', 'multiple_content_xml');
        }

        return [
            'content_entry' => $contentEntries[0],
            'entries' => $entries,
        ];
    }

    private function isContentXmlEntry(string $name): bool
    {
        $normalized = str_replace('\\', '/', $name);

        return ! str_ends_with($normalized, '/')
            && basename($normalized) === 'content.xml';
    }

    private function assertSafeEntryName(string $name): void
    {
        $normalized = str_replace('\\', '/', $name);
        $segments = explode('/', $normalized);

        if ($name === ''
            || str_contains($name, "\0")
            || str_starts_with($normalized, '/')
            || preg_match('/^[A-Za-z]:\//', $normalized) === 1
            || in_array('..', $segments, true)) {
            throw new UdfException('UDF arşivinde güvenli olmayan bir dosya yolu bulundu.', 'unsafe_entry_path');
        }
    }

    private function assertArchiveSize(string $disk, string $path): void
    {
        try {
            $size = Storage::disk($disk)->size($path);
        } catch (Throwable $exception) {
            throw new UdfException('UDF dosyası özel depolama alanından okunamadı.', 'storage_read_failed', previous: $exception);
        }

        if ($size > $this->limit('max_archive_size')) {
            throw new UdfException('UDF dosyası izin verilen arşiv boyutunu aşıyor.', 'archive_too_large');
        }
    }

    private function open(string $path, int $flags = 0): ZipArchive
    {
        $archive = new ZipArchive;

        if ($archive->open($path, $flags) !== true) {
            throw new UdfException('Geçersiz UDF dosyası. Dosya geçerli bir ZIP kapsayıcı değil.', 'invalid_archive');
        }

        return $archive;
    }

    private function withLocalCopy(string $disk, string $path, callable $callback): mixed
    {
        $temporaryDirectory = (string) config('udf.temporary_path');
        File::ensureDirectoryExists($temporaryDirectory, 0750, true);
        $temporaryPath = $temporaryDirectory.'/'.Str::uuid().'.udf';
        $source = null;
        $target = null;

        try {
            $source = Storage::disk($disk)->readStream($path);
            $target = fopen($temporaryPath, 'w+b');

            if (! is_resource($source) || ! is_resource($target) || stream_copy_to_stream($source, $target) === false) {
                throw new UdfException('UDF dosyası güvenli çalışma alanına alınamadı.', 'temporary_copy_failed');
            }

            fflush($target);

            return $callback($temporaryPath);
        } catch (UdfException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new UdfException('UDF dosyası işlenemedi.', 'archive_processing_failed', previous: $exception);
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }

            if (is_resource($target)) {
                fclose($target);
            }

            File::delete($temporaryPath);
        }
    }

    private function limit(string $key): int
    {
        $limit = (int) config('udf.'.$key);

        if ($limit < 1) {
            throw new RuntimeException("UDF {$key} limiti geçersiz yapılandırılmış.");
        }

        return $limit;
    }
}
