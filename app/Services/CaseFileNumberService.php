<?php

namespace App\Services;

use App\Models\CaseNumberSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CaseFileNumberService
{
    public function next(?int $year = null, ?string $prefix = null): string
    {
        $year ??= now()->year;
        $prefix = Str::upper($prefix ?? (string) config('legal.case_number_prefix'));

        if ($year < 2000 || $year > 9999 || preg_match('/^[A-Z0-9]{1,20}$/', $prefix) !== 1) {
            throw new InvalidArgumentException('Geçersiz hukuki dosya numarası yapılandırması.');
        }

        return DB::transaction(function () use ($year, $prefix): string {
            DB::table('case_number_sequences')->insertOrIgnore([
                'prefix' => $prefix,
                'year' => $year,
                'next_number' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = CaseNumberSequence::query()
                ->where('prefix', $prefix)
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();
            $number = $sequence->next_number;
            $sequence->forceFill(['next_number' => $number + 1])->save();

            return sprintf('%s-%d-%06d', $prefix, $year, $number);
        });
    }
}
