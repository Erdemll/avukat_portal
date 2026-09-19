<?php

use App\AuditAction;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\DocumentVersion;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

it('uploads private versioned documents to an assigned legal case', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $folder = DocumentFolder::factory()->create(['case_file_id' => $caseFile, 'created_by' => $manager, 'name' => 'Dilekçeler']);

    $this->actingAs($lawyer)->post(route('case-files.documents.store', $caseFile), [
        'documents' => [UploadedFile::fake()->create('dava-dilekcesi.pdf', 20, 'application/pdf')],
        'folder_id' => $folder->id,
        'document_type' => 'petition',
        'title' => 'Dava Dilekçesi',
    ])->assertRedirect();

    $document = Document::query()->firstOrFail();
    $version = $document->versions()->firstOrFail();
    expect($document->case_file_id)->toBe($caseFile->id);
    expect($document->event_id)->toBeNull();
    expect($document->title)->toBe('Dava Dilekçesi');
    expect($version->version_no)->toBe(1);
    expect($version->sha256)->toHaveLength(64);
    Storage::disk('legal_private')->assertExists($version->path);
    expect(AuditLog::query()->where('action', AuditAction::DocumentUploaded)->where('case_file_id', $caseFile->id)->exists())->toBeTrue();
    $this->actingAs($lawyer)->get(route('legal-documents.index'))->assertOk()->assertSee('Dava Dilekçesi');
    $this->actingAs($lawyer)->get(route('case-files.show', $caseFile))->assertOk()->assertSee('Dava Dilekçesi');
});

it('adds immutable ordered versions and serves authorized previews', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $this->actingAs($lawyer)->post(route('case-files.documents.store', $caseFile), [
        'documents' => [UploadedFile::fake()->create('dilekce.pdf', 10, 'application/pdf')],
        'document_type' => 'petition',
    ]);
    $document = Document::query()->firstOrFail();

    $this->actingAs($lawyer)->post(route('document-versions.store', $document), [
        'file' => UploadedFile::fake()->create('dilekce-v2.pdf', 12, 'application/pdf'),
        'change_note' => 'Talepler güncellendi.',
    ])->assertRedirect();

    expect($document->versions()->pluck('version_no')->all())->toBe([1, 2]);
    expect($document->fresh()->currentVersion->version_no)->toBe(2);
    $version = $document->versions()->firstOrFail();
    expect(fn () => $version->update(['change_note' => 'değiştirildi']))->toThrow(LogicException::class);
    $this->actingAs($lawyer)->get(route('document-versions.preview', $document->fresh()->currentVersion))
        ->assertOk()
        ->assertHeader('cache-control', 'no-store, private')
        ->assertHeader('content-security-policy', "sandbox; default-src 'none'; img-src data:");
    $this->actingAs(userWithRole('lawyer'))->get(route('document-versions.download', $document->fresh()->currentVersion))->assertForbidden();
});

it('rejects active svg content previews even for authorized legacy documents', function () {
    $lawyer = userWithRole('lawyer');
    $event = legalEvent(userWithRole('employee'), $lawyer);
    $document = Document::factory()->create([
        'event_id' => $event,
        'uploaded_by' => $lawyer,
        'mime_type' => 'image/svg+xml',
        'extension' => 'svg',
    ]);
    $version = DocumentVersion::factory()->create([
        'document_id' => $document,
        'uploaded_by' => $lawyer,
        'mime_type' => 'image/svg+xml',
        'extension' => 'svg',
    ]);

    $this->actingAs($lawyer)->get(route('document-versions.preview', $version))->assertStatus(415);
});

it('creates immutable v1 records for new event uploads', function () {
    Storage::fake('legal_private');
    $employee = userWithRole('employee');
    $event = legalEvent($employee, userWithRole('lawyer'));

    $this->actingAs($employee)->post(route('events.documents.store', $event), [
        'documents' => [UploadedFile::fake()->create('sozlesme.pdf', 10, 'application/pdf')],
    ])->assertRedirect();

    expect(DocumentVersion::query()->count())->toBe(1);
    expect(DocumentVersion::query()->first()->version_no)->toBe(1);
});

it('does not delete version blobs when a forbidden physical purge is attempted', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $caseFile = legalCaseFile($manager, [userWithRole('lawyer')]);
    $this->actingAs($manager)->post(route('case-files.documents.store', $caseFile), [
        'documents' => [UploadedFile::fake()->create('delil.pdf', 10, 'application/pdf')],
    ]);
    $document = Document::query()->firstOrFail();
    $path = $document->currentVersion->path;

    expect(fn () => $document->forceDelete())->toThrow(QueryException::class);
    Storage::disk('legal_private')->assertExists($path);
});

it('rejects folders and documents outside the users legal case scope', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $otherCase = legalCaseFile($manager, [userWithRole('lawyer')]);
    $otherFolder = DocumentFolder::factory()->create(['case_file_id' => $otherCase, 'created_by' => $manager]);

    $this->actingAs($lawyer)->post(route('case-files.documents.store', $caseFile), [
        'documents' => [UploadedFile::fake()->create('delil.pdf', 10, 'application/pdf')],
        'folder_id' => $otherFolder->id,
    ])->assertSessionHasErrors('folder_id');
    $this->actingAs(userWithRole('employee'))->post(route('case-files.documents.store', $caseFile), [
        'documents' => [UploadedFile::fake()->create('delil.pdf', 10, 'application/pdf')],
    ])->assertForbidden();
    expect(Document::query()->count())->toBe(0);
});

it('removes all stored files when a multi document audit transaction fails', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $caseFile = legalCaseFile($manager, [userWithRole('lawyer')]);
    $this->mock(AuditService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('log')->once()->andThrow(new RuntimeException('audit unavailable'));
    });
    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($manager)->post(route('case-files.documents.store', $caseFile), [
        'documents' => [
            UploadedFile::fake()->create('bir.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->create('iki.pdf', 10, 'application/pdf'),
        ],
    ]))->toThrow(RuntimeException::class);
    expect(Document::query()->count())->toBe(0);
    expect(DocumentVersion::query()->count())->toBe(0);
    expect(Storage::disk('legal_private')->allFiles())->toBe([]);
});

it('backfills legacy event documents once with integrity hashes', function () {
    Storage::fake('legal_private');
    $document = Document::factory()->create(['path' => 'events/1/legacy.pdf']);
    Storage::disk('legal_private')->put($document->path, 'legacy-content');

    $this->artisan('documents:backfill-versions')->assertSuccessful();
    $this->artisan('documents:backfill-versions')->assertSuccessful();

    expect($document->versions()->count())->toBe(1);
    expect($document->versions()->first()->sha256)->toBe(hash('sha256', 'legacy-content'));
});

it('prevents document folder and version writes after a case is closed', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $this->actingAs($lawyer)->post(route('case-files.documents.store', $caseFile), [
        'documents' => [UploadedFile::fake()->create('evrak.pdf', 10, 'application/pdf')],
    ]);
    $document = Document::query()->firstOrFail();
    $caseFile->forceFill(['status' => 'closed', 'closed_at' => today()])->save();

    $this->actingAs($lawyer)->post(route('case-files.documents.store', $caseFile), [
        'documents' => [UploadedFile::fake()->create('yeni.pdf', 10, 'application/pdf')],
    ])->assertSessionHasErrors('documents');
    $this->actingAs($lawyer)->post(route('document-versions.store', $document), [
        'file' => UploadedFile::fake()->create('v2.pdf', 10, 'application/pdf'),
    ])->assertSessionHasErrors('file');
    $this->actingAs($lawyer)->post(route('case-files.document-folders.store', $caseFile), ['name' => 'Yeni Klasör'])->assertSessionHasErrors('name');
});
