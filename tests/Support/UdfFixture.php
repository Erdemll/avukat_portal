<?php

namespace Tests\Support;

use App\Models\CaseFile;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class UdfFixture
{
    /** @param array<string, string> $extraEntries */
    public static function archive(string $fixture = 'simple-content.xml', array $extraEntries = []): string
    {
        return self::archiveFromXml(self::xml($fixture), $extraEntries);
    }

    /** @param array<string, string> $extraEntries */
    public static function archiveFromXml(string $xml, array $extraEntries = []): string
    {
        return self::archiveEntries(['content.xml' => $xml, ...$extraEntries]);
    }

    /** @param array<string, string> $entries */
    public static function archiveEntries(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'udf-fixture-');
        if ($path === false) {
            throw new RuntimeException('Geçici UDF fixture dosyası oluşturulamadı.');
        }

        $archive = new ZipArchive;
        if ($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('UDF fixture arşivi oluşturulamadı.');
        }

        foreach ($entries as $name => $contents) {
            $archive->addFromString($name, $contents);
        }
        $archive->close();

        $contents = file_get_contents($path);
        unlink($path);

        if (! is_string($contents)) {
            throw new RuntimeException('UDF fixture arşivi okunamadı.');
        }

        return $contents;
    }

    /** @param array<string, string> $extraEntries */
    public static function uploaded(string $fixture = 'simple-content.xml', array $extraEntries = []): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('anonim-belge.udf', self::archive($fixture, $extraEntries));
    }

    /** @param array<string, string> $extraEntries */
    public static function document(CaseFile $caseFile, User $uploader, string $fixture = 'simple-content.xml', array $extraEntries = []): Document
    {
        $contents = self::archive($fixture, $extraEntries);
        $storedName = Str::uuid().'.udf';
        $path = 'case-files/'.$caseFile->id.'/'.$storedName;
        Storage::disk('legal_private')->put($path, $contents);

        $document = Document::factory()->create([
            'event_id' => null,
            'case_file_id' => $caseFile,
            'uploaded_by' => $uploader,
            'original_name' => 'anonim-belge.udf',
            'stored_name' => $storedName,
            'disk' => 'legal_private',
            'path' => $path,
            'mime_type' => 'application/zip',
            'extension' => 'udf',
            'size' => strlen($contents),
            'title' => 'Anonim UDF Belgesi',
        ]);

        DocumentVersion::factory()->create([
            'document_id' => $document,
            'version_no' => 1,
            'original_name' => $document->original_name,
            'stored_name' => $document->stored_name,
            'disk' => $document->disk,
            'path' => $document->path,
            'mime_type' => $document->mime_type,
            'extension' => 'udf',
            'size' => $document->size,
            'sha256' => hash('sha256', $contents),
            'uploaded_by' => $uploader,
        ]);

        return $document->refresh();
    }

    public static function xml(string $fixture): string
    {
        $contents = file_get_contents(base_path('tests/Fixtures/Udf/'.$fixture));

        if (! is_string($contents)) {
            throw new RuntimeException("{$fixture} UDF XML fixture dosyası okunamadı.");
        }

        return $contents;
    }
}
