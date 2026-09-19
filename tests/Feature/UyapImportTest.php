<?php

use App\Models\CaseFile;
use App\Models\CaseType;
use Illuminate\Http\UploadedFile;

function uyapCsv(string $reference = 'UYAP-2026-100'): UploadedFile
{
    $contents = implode("\n", [
        'uyap_referans;dosya_basligi;dosya_turu;oncelik;acilis_tarihi;avukat_eposta;yargi_turu;mahkeme;esas_yili;esas_no',
        "{$reference};İşçilik alacağı;is-hukuku;high;2026-09-01;avukat@example.com;dava;İstanbul 3. İş Mahkemesi;2026;123",
    ]);

    return UploadedFile::fake()->createWithContent('uyap.csv', $contents);
}

it('previews and atomically imports supported uyap csv records', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $lawyer->forceFill(['email' => 'avukat@example.com'])->save();
    CaseType::factory()->create(['name' => 'İş Hukuku', 'slug' => 'is-hukuku', 'is_active' => true]);

    $this->actingAs($manager)->post(route('uyap-import.store'), ['file' => uyapCsv(), 'mode' => 'preview'])
        ->assertOk()->assertSee('1 geçerli, 0 hatalı kayıt')->assertSee('İşçilik alacağı');

    $this->actingAs($manager)->post(route('uyap-import.store'), ['file' => uyapCsv(), 'mode' => 'import'])
        ->assertRedirect(route('uyap-import.create'));

    $caseFile = CaseFile::query()->where('external_reference', 'UYAP-2026-100')->firstOrFail();
    expect($caseFile->import_source)->toBe('uyap')
        ->and($caseFile->activeLawyers()->whereKey($lawyer->id)->exists())->toBeTrue()
        ->and($caseFile->proceedings()->firstOrFail()->principal_number)->toBe('123');
});

it('rejects duplicate imports and non manager access', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $lawyer->forceFill(['email' => 'avukat@example.com'])->save();
    CaseType::factory()->create(['slug' => 'is-hukuku', 'is_active' => true]);

    $this->actingAs($manager)->post(route('uyap-import.store'), ['file' => uyapCsv(), 'mode' => 'import']);
    $this->actingAs($manager)->post(route('uyap-import.store'), ['file' => uyapCsv(), 'mode' => 'import'])
        ->assertOk()->assertSee('referansı daha önce kullanılmış');
    expect(CaseFile::query()->where('external_reference', 'UYAP-2026-100')->count())->toBe(1);

    $this->actingAs($lawyer)->get(route('uyap-import.create'))->assertForbidden();
});
