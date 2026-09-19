<?php

use App\AuditAction;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\Udf\UdfArchiveService;
use App\Services\Udf\UdfParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UdfFixture;

function updatedUdfContent(string $text = 'Güncellenmiş çğıİöşü metni'): array
{
    return [
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'attrs' => ['textAlign' => 'justify', 'indent' => 1],
            'content' => [[
                'type' => 'text',
                'text' => $text,
                'marks' => [['type' => 'bold'], ['type' => 'fontSize', 'attrs' => ['size' => 14]]],
            ]],
        ]],
    ];
}

it('redirects guests away from UDF view and edit routes', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $document = UdfFixture::document(legalCaseFile($manager), $manager);

    $this->get(route('documents.udf.show', $document))->assertRedirect(route('login'));
    $this->get(route('documents.udf.edit', $document))->assertRedirect(route('login'));
});

it('gives managers read-only UDF access', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $document = UdfFixture::document(legalCaseFile($manager), $manager);

    $this->actingAs($manager)->get(route('documents.udf.show', $document))
        ->assertOk()
        ->assertSee('Sayın Mahkeme')
        ->assertSee(route('documents.udf.download', $document), false)
        ->assertDontSee(route('documents.udf.edit', $document), false);
    $this->actingAs($manager)->get(route('documents.udf.edit', $document))->assertForbidden();
    $this->actingAs($manager)->putJson(route('documents.udf.update', $document), [
        'document_version' => 1,
        'content' => updatedUdfContent(),
    ])->assertForbidden();
    $this->actingAs($manager)->post(route('document-versions.store', $document), [
        'file' => UdfFixture::uploaded(),
    ])->assertForbidden();
    expect(DocumentVersion::query()->count())->toBe(1);
});

it('allows an assigned lawyer to view and edit UDF documents', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $document = UdfFixture::document(legalCaseFile($manager, [$lawyer]), $lawyer);

    $this->actingAs($lawyer)->get(route('documents.udf.show', $document))
        ->assertOk()
        ->assertSee(route('documents.udf.edit', $document), false);
    $this->actingAs($lawyer)->get(route('documents.udf.edit', $document))
        ->assertOk()
        ->assertSee('data-udf-toolbar', false)
        ->assertSee('Yeni Sürüm Olarak Kaydet');

    expect(AuditLog::query()->where('action', AuditAction::UdfViewed)->exists())->toBeTrue();
    expect(AuditLog::query()->where('action', AuditAction::UdfEditStarted)->exists())->toBeTrue();
});

it('prevents lawyers and employees from accessing UDF documents outside their case scope', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $assignedLawyer = userWithRole('lawyer');
    $otherLawyer = userWithRole('lawyer');
    $employee = userWithRole('employee');
    $document = UdfFixture::document(legalCaseFile($manager, [$assignedLawyer]), $assignedLawyer);

    $this->actingAs($otherLawyer)->get(route('documents.udf.show', $document))->assertForbidden();
    $this->actingAs($otherLawyer)->get(route('documents.udf.edit', $document))->assertForbidden();
    $this->actingAs($otherLawyer)->putJson(route('documents.udf.update', $document), [
        'document_version' => 1,
        'content' => updatedUdfContent(),
    ])->assertForbidden();
    $this->actingAs($employee)->get(route('documents.udf.show', $document))->assertForbidden();
    expect(DocumentVersion::query()->count())->toBe(1);
});

it('rejects fake UDF uploads and accepts root or nested content XML files', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);

    $this->actingAs($lawyer)->post(route('case-files.documents.store', $caseFile), [
        'documents' => [UploadedFile::fake()->createWithContent('sahte.udf', 'not-a-zip')],
    ])->assertInvalid(['documents.0' => 'Geçersiz UDF dosyası']);

    $this->actingAs($lawyer)->post(route('case-files.documents.store', $caseFile), [
        'documents' => [UploadedFile::fake()->createWithContent('anonim-belge.udf.zip', UdfFixture::archive())],
    ])->assertInvalid([
        'documents.0' => 'Dosya uzantısı desteklenmiyor. UDF yüklemek için dosya adının yalnız .udf ile bittiğini ve .udf.zip olmadığını kontrol edin.',
    ]);

    $this->actingAs($lawyer)->post(route('case-files.documents.store', $caseFile), [
        'documents' => [UdfFixture::uploaded()],
        'title' => 'Geçerli UDF',
    ])->assertRedirect();

    $nestedUdf = UploadedFile::fake()->createWithContent(
        'klasorlu-belge.udf',
        UdfFixture::archiveEntries(['belge/content.xml' => UdfFixture::xml('simple-content.xml')]),
    );
    $this->actingAs($lawyer)->post(route('case-files.documents.store', $caseFile), [
        'documents' => [$nestedUdf],
        'title' => 'Klasörlü Geçerli UDF',
    ])->assertRedirect();

    $document = Document::query()->where('title', 'Geçerli UDF')->firstOrFail();
    expect($document->extension)->toBe('udf');
    expect($document->currentVersion->version_no)->toBe(1);
    expect(Document::query()->where('title', 'Klasörlü Geçerli UDF')->exists())->toBeTrue();

    $this->actingAs($lawyer)->post(route('document-versions.store', $document), [
        'file' => UploadedFile::fake()->createWithContent('yeni-surum.udf.zip', UdfFixture::archive()),
    ])->assertInvalid([
        'file' => 'Dosya uzantısı desteklenmiyor. UDF yüklemek için dosya adının yalnız .udf ile bittiğini ve .udf.zip olmadığını kontrol edin.',
    ]);
    expect($document->versions()->count())->toBe(1);
});

it('creates an immutable UDF version from canonical JSON and keeps archive entries', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $document = UdfFixture::document(
        legalCaseFile($manager, [$lawyer]),
        $lawyer,
        'simple-content.xml',
        ['metadata/custom.bin' => 'preserved'],
    );
    $originalPath = $document->path;
    $original = Storage::disk('legal_private')->get($document->path);

    $this->actingAs($lawyer)->putJson(route('documents.udf.update', $document), [
        'document_version' => 1,
        'content' => updatedUdfContent(),
    ])->assertOk()->assertJsonPath('version', 2);

    $document->refresh();
    $version = $document->currentVersion;
    $archive = app(UdfArchiveService::class)->read($version->disk, $version->path);
    $parsed = app(UdfParser::class)->parse($archive['content_xml']);

    expect($document->versions()->pluck('version_no')->all())->toBe([1, 2]);
    expect(Storage::disk('legal_private')->get($originalPath))->toBe($original);
    expect($archive['entries'])->toContain('metadata/custom.bin');
    expect(json_encode($parsed['content'], JSON_UNESCAPED_UNICODE))->toContain('Güncellenmiş çğıİöşü metni');
    expect(AuditLog::query()->where('action', AuditAction::UdfVersionCreated)->exists())->toBeTrue();

    $audit = AuditLog::query()->where('action', AuditAction::UdfVersionCreated)->firstOrFail();
    expect(json_encode($audit->new_values, JSON_UNESCAPED_UNICODE))
        ->not->toContain('Güncellenmiş', '<template', 'case-files/');
});

it('returns 409 and does not overwrite a newer UDF version', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $document = UdfFixture::document(legalCaseFile($manager, [$lawyer]), $lawyer);

    $this->actingAs($lawyer)->putJson(route('documents.udf.update', $document), [
        'document_version' => 1,
        'content' => updatedUdfContent('İkinci sürüm'),
    ])->assertOk();

    $this->actingAs($lawyer)->putJson(route('documents.udf.update', $document), [
        'document_version' => 1,
        'content' => updatedUdfContent('Eski editör içeriği'),
    ])->assertConflict()->assertJsonPath('message', 'Bu belge siz düzenlerken başka bir kullanıcı tarafından güncellendi. Lütfen son sürümü açıp değişikliklerinizi yeniden kontrol edin.');

    expect($document->versions()->count())->toBe(2);
    expect($document->fresh()->currentVersion->version_no)->toBe(2);
});

it('rejects raw HTML and unsupported editor structures', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $document = UdfFixture::document(legalCaseFile($manager, [$lawyer]), $lawyer);

    $this->actingAs($lawyer)->putJson(route('documents.udf.update', $document), [
        'document_version' => 1,
        'content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'rawHtml',
                'attrs' => ['html' => '<script>alert(1)</script>'],
            ]],
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors('content');

    expect($document->versions()->count())->toBe(1);
});

it('shows signature warnings and disables editing for unsupported structures', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $signed = UdfFixture::document($caseFile, $lawyer, 'simple-content.xml', ['sign.sgn' => 'dummy-signature']);
    $unsupported = UdfFixture::document($caseFile, $lawyer, 'unknown-node-content.xml');
    $signedOriginalPath = $signed->path;
    $signedOriginalContents = Storage::disk('legal_private')->get($signedOriginalPath);

    $this->actingAs($manager)->get(route('documents.udf.show', $signed))
        ->assertOk()
        ->assertSee('Elektronik imza bilgisi algılandı');
    $this->actingAs($lawyer)->get(route('documents.udf.edit', $signed))
        ->assertOk()
        ->assertSee('Elektronik imza uyarısı');
    $this->actingAs($lawyer)->putJson(route('documents.udf.update', $signed), [
        'document_version' => 1,
        'content' => updatedUdfContent('İmzalı belgeden üretilen yeni sürüm'),
    ])->assertOk()->assertJsonPath('version', 2);
    $this->actingAs($lawyer)->get(route('documents.udf.show', $signed))
        ->assertOk()
        ->assertSee('Düzenlenmiş kopya; imza geçerli sayılmaz');

    $signed->refresh();
    $editedArchive = app(UdfArchiveService::class)->read($signed->currentVersion->disk, $signed->currentVersion->path);
    expect(Storage::disk('legal_private')->get($signedOriginalPath))->toBe($signedOriginalContents);
    expect($editedArchive['entries'])->toContain('sign.sgn');

    $this->actingAs($lawyer)->get(route('documents.udf.show', $unsupported))
        ->assertOk()
        ->assertDontSee(route('documents.udf.edit', $unsupported), false);
    $this->actingAs($lawyer)->get(route('documents.udf.edit', $unsupported))
        ->assertUnprocessable()
        ->assertSee('güvenli düzenleme için desteklenmiyor');
    $this->actingAs($lawyer)->putJson(route('documents.udf.update', $unsupported), [
        'document_version' => 1,
        'content' => updatedUdfContent(),
    ])->assertUnprocessable()->assertJsonPath('message', 'Bu UDF yapısı güvenli düzenleme için desteklenmiyor. Belgeyi yalnız görüntüleyebilir ve indirebilirsiniz.');
});

it('downloads the current private UDF with security headers and audit metadata', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $document = UdfFixture::document(legalCaseFile($manager), $manager);

    $this->actingAs($manager)->get(route('documents.udf.download', $document))
        ->assertOk()
        ->assertDownload('anonim-belge.udf')
        ->assertHeader('cache-control', 'no-store, private')
        ->assertHeader('x-content-type-options', 'nosniff');

    $audit = AuditLog::query()->where('action', AuditAction::UdfDownloaded)->firstOrFail();
    expect($audit->new_values)->toMatchArray(['document_id' => $document->id, 'version_no' => 1]);
});
