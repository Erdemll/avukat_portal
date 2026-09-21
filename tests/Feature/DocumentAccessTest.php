<?php

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

function documentFile(string $name = 'sozlesme.pdf'): UploadedFile
{
    return UploadedFile::fake()->create($name, 20, 'application/pdf');
}

it('allows authorized users to upload private documents and persists safe metadata', function () {
    Storage::fake('legal_private');
    $employee = userWithRole('employee');
    $event = legalEvent($employee, userWithRole('lawyer'));

    $this->actingAs($employee)->post(route('events.documents.store', $event), ['documents' => [documentFile()]])->assertRedirect();

    $document = Document::query()->firstOrFail();
    expect($document->original_name)->toBe('sozlesme.pdf');
    expect($document->stored_name)->not->toBe($document->original_name);
    Storage::disk('legal_private')->assertExists($document->path);
});

it('forbids document uploads to inaccessible events', function () {
    Storage::fake('legal_private');
    $otherEvent = legalEvent(userWithRole('employee'), userWithRole('lawyer'));
    $employee = userWithRole('employee');

    $this->actingAs($employee)->post(route('events.documents.store', $otherEvent), ['documents' => [documentFile()]])->assertForbidden();
});

it('allows an assigned lawyer and manager to upload documents', function () {
    Storage::fake('legal_private');
    $lawyer = userWithRole('lawyer');
    $event = legalEvent(userWithRole('employee'), $lawyer);
    $this->actingAs($lawyer)->post(route('events.documents.store', $event), ['documents' => [documentFile()]])->assertRedirect();
    $this->actingAs(userWithRole('manager'))->post(route('events.documents.store', $event), ['documents' => [documentFile('fatura.pdf')]])->assertRedirect();
    expect(Document::query()->count())->toBe(2);
});

it('protects document downloads from guests and IDOR attempts', function () {
    Storage::fake('legal_private');
    $owner = userWithRole('employee');
    $event = legalEvent($owner, userWithRole('lawyer'));
    $this->actingAs($owner)->post(route('events.documents.store', $event), ['documents' => [documentFile()]]);
    $document = Document::query()->firstOrFail();
    Auth::logout();
    $this->get(route('documents.download', $document))->assertRedirect(route('login'));
    $this->actingAs(userWithRole('employee'))->get(route('documents.download', $document))->assertForbidden();
    $this->actingAs($owner)->get(route('documents.download', $document))->assertOk();
});

it('rejects disallowed extensions and files over fifty megabytes', function () {
    Storage::fake('legal_private');
    $employee = userWithRole('employee');
    $event = legalEvent($employee, userWithRole('lawyer'));
    $this->actingAs($employee)->post(route('events.documents.store', $event), ['documents' => [UploadedFile::fake()->create('zararli.exe', 20, 'application/octet-stream')]])->assertSessionHasErrors('documents.0');
    $this->actingAs($employee)->post(route('events.documents.store', $event), ['documents' => [UploadedFile::fake()->create('buyuk.pdf', 51201, 'application/pdf')]])->assertSessionHasErrors('documents.0');
});

it('lets managers and assigned lawyers soft delete documents without deleting the private file', function () {
    Storage::fake('legal_private');
    $employee = userWithRole('employee');
    $lawyer = userWithRole('lawyer');
    $event = legalEvent($employee, $lawyer);
    $this->actingAs($employee)->post(route('events.documents.store', $event), ['documents' => [documentFile()]]);
    $document = Document::query()->firstOrFail();
    $this->actingAs($employee)->delete(route('documents.destroy', $document))->assertForbidden();
    $this->actingAs(userWithRole('lawyer'))->delete(route('documents.destroy', $document))->assertForbidden();
    $this->actingAs($lawyer)->delete(route('documents.destroy', $document))->assertRedirect();
    $this->assertSoftDeleted('documents', ['id' => $document->id]);
    Storage::disk('legal_private')->assertExists($document->path);
});

it('lets an assigned case lawyer delete a case file document', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $caseFile = legalCaseFile($manager, [$lawyer]);
    $document = Document::factory()->create([
        'event_id' => null,
        'case_file_id' => $caseFile,
        'uploaded_by' => $manager,
    ]);

    $this->actingAs(userWithRole('lawyer'))->delete(route('documents.destroy', $document))->assertForbidden();
    $this->actingAs($lawyer)->delete(route('documents.destroy', $document))
        ->assertRedirect(route('case-files.show', $caseFile));

    $this->assertSoftDeleted('documents', ['id' => $document->id]);
});
