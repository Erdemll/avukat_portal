<?php

namespace App\Services;

use App\AuditAction;
use App\CaseProceedingType;
use App\EventPriority;
use App\Models\CaseFile;
use App\Models\CaseType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class UyapImportService
{
    private const REQUIRED_HEADERS = [
        'uyap_referans',
        'dosya_basligi',
        'dosya_turu',
        'oncelik',
        'acilis_tarihi',
        'avukat_eposta',
        'yargi_turu',
        'mahkeme',
        'esas_yili',
        'esas_no',
    ];

    public function __construct(
        private CaseFileManagementService $caseFiles,
        private AuditService $audit,
    ) {}

    /** @return array{rows: array<int, array<string, mixed>>, errors: array<int, string>} */
    public function inspect(UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath());
        if ($contents === false || ! mb_check_encoding($contents, 'UTF-8')) {
            return ['rows' => [], 'errors' => ['Dosya UTF-8 kodlamasında ve okunabilir olmalıdır.']];
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ['rows' => [], 'errors' => ['CSV dosyası açılamadı.']];
        }

        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = substr_count((string) $firstLine, ';') >= substr_count((string) $firstLine, ',') ? ';' : ',';
        $header = fgetcsv($handle, 0, $delimiter, '"', '\\');
        if ($header === false) {
            fclose($handle);

            return ['rows' => [], 'errors' => ['CSV başlık satırı okunamadı.']];
        }

        $header = array_map(fn (string $value): string => Str::lower(trim($value, "\xEF\xBB\xBF \t\n\r\0\x0B")), $header);
        if (count($header) !== count(array_unique($header))) {
            fclose($handle);

            return ['rows' => [], 'errors' => ['CSV başlıkları tekrar etmemelidir.']];
        }
        $missing = array_values(array_diff(self::REQUIRED_HEADERS, $header));
        if ($missing !== []) {
            fclose($handle);

            return ['rows' => [], 'errors' => ['Eksik sütunlar: '.implode(', ', $missing).'.']];
        }

        $rows = [];
        $errors = [];
        $seenReferences = [];
        $line = 1;
        while (($values = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $line++;
            if ($line > 501) {
                $errors[] = 'Tek dosyada en fazla 500 kayıt aktarılabilir.';
                break;
            }
            if (count(array_filter($values, fn ($value): bool => trim((string) $value) !== '')) === 0) {
                continue;
            }
            if (count($values) !== count($header)) {
                $errors[] = "Satır {$line}: sütun sayısı başlıkla uyuşmuyor.";

                continue;
            }

            $raw = array_combine($header, array_map(fn ($value): string => trim((string) $value), $values));
            $result = $this->normalizeRow($raw, $line, $seenReferences);
            if (is_string($result)) {
                $errors[] = $result;
            } else {
                $rows[] = $result;
                $seenReferences[] = $result['external_reference'];
            }
        }
        fclose($handle);

        if ($rows === [] && $errors === []) {
            $errors[] = 'CSV dosyasında aktarılabilir kayıt bulunamadı.';
        }

        return compact('rows', 'errors');
    }

    /** @param array<int, array<string, mixed>> $rows */
    public function import(array $rows, User $actor): int
    {
        return DB::transaction(function () use ($rows, $actor): int {
            foreach ($rows as $row) {
                $caseFile = $this->caseFiles->create([
                    'case_type_id' => $row['case_type_id'],
                    'title' => $row['title'],
                    'priority' => $row['priority'],
                    'description' => 'UYAP manuel CSV aktarımı. Referans: '.$row['external_reference'],
                    'opened_at' => $row['opened_at'],
                    'lawyer_ids' => [$row['lawyer_id']],
                    'lead_lawyer_id' => $row['lawyer_id'],
                    'client_party_ids' => [],
                    'proceeding_type' => $row['proceeding_type'],
                    'authority_name' => $row['authority_name'],
                    'principal_year' => $row['principal_year'],
                    'principal_number' => $row['principal_number'],
                    'external_file_number' => $row['external_reference'],
                ], $actor);
                $caseFile->forceFill(['import_source' => 'uyap', 'external_reference' => $row['external_reference']])->save();
                $this->audit->log(AuditAction::UyapCaseImported, $actor, auditable: $caseFile, description: 'UYAP CSV kaydı hukuki dosyaya aktarıldı.', newValues: ['external_reference' => $row['external_reference']], caseFile: $caseFile);
            }

            return count($rows);
        });
    }

    /** @param array<string, string> $raw
     * @param  array<int, string>  $seenReferences
     * @return array<string, mixed>|string
     */
    private function normalizeRow(array $raw, int $line, array $seenReferences): array|string
    {
        foreach (self::REQUIRED_HEADERS as $header) {
            if ($raw[$header] === '') {
                return "Satır {$line}: {$header} alanı boş bırakılamaz.";
            }
        }

        $reference = Str::limit($raw['uyap_referans'], 100, '');
        if (in_array($reference, $seenReferences, true) || CaseFile::query()->where('import_source', 'uyap')->where('external_reference', $reference)->exists()) {
            return "Satır {$line}: {$reference} referansı daha önce kullanılmış.";
        }

        $caseType = CaseType::query()->where('is_active', true)->where(function ($query) use ($raw): void {
            $query->where('slug', $raw['dosya_turu'])->orWhere('name', $raw['dosya_turu']);
        })->first();
        if ($caseType === null) {
            return "Satır {$line}: '{$raw['dosya_turu']}' adında etkin dosya türü bulunamadı.";
        }

        $lawyer = User::query()->where('email', $raw['avukat_eposta'])->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('slug', 'lawyer'))->first();
        if ($lawyer === null) {
            return "Satır {$line}: '{$raw['avukat_eposta']}' adresli etkin avukat bulunamadı.";
        }

        $priority = EventPriority::tryFrom(Str::lower($raw['oncelik']));
        $proceedingType = $this->proceedingType($raw['yargi_turu']);
        try {
            $openedAt = CarbonImmutable::createFromFormat('!Y-m-d', $raw['acilis_tarihi']);
        } catch (Throwable) {
            $openedAt = null;
        }
        if ($priority === null || $proceedingType === null || $openedAt === null || $openedAt->format('Y-m-d') !== $raw['acilis_tarihi']) {
            return "Satır {$line}: öncelik, yargı türü veya açılış tarihi geçersiz.";
        }
        if (! ctype_digit($raw['esas_yili']) || (int) $raw['esas_yili'] < 1900 || (int) $raw['esas_yili'] > ((int) date('Y') + 1)) {
            return "Satır {$line}: esas_yili geçersiz.";
        }

        return [
            'line' => $line,
            'external_reference' => $reference,
            'title' => Str::limit($raw['dosya_basligi'], 255, ''),
            'case_type_id' => $caseType->id,
            'case_type_name' => $caseType->name,
            'priority' => $priority->value,
            'opened_at' => $openedAt->toDateString(),
            'lawyer_id' => $lawyer->id,
            'lawyer_name' => $lawyer->name,
            'proceeding_type' => $proceedingType->value,
            'authority_name' => Str::limit($raw['mahkeme'], 255, ''),
            'principal_year' => (int) $raw['esas_yili'],
            'principal_number' => Str::limit($raw['esas_no'], 100, ''),
        ];
    }

    private function proceedingType(string $value): ?CaseProceedingType
    {
        return match (Str::lower($value)) {
            'dava', 'lawsuit' => CaseProceedingType::Lawsuit,
            'icra', 'enforcement' => CaseProceedingType::Enforcement,
            'arabuluculuk', 'mediation' => CaseProceedingType::Mediation,
            'diger', 'diğer', 'other' => CaseProceedingType::Other,
            default => null,
        };
    }
}
