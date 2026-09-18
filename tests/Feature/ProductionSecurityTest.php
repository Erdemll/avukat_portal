<?php

use App\AuditAction;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('forbids an employee from another employees event, documents, uploads and updates', function () {
    Storage::fake('legal_private');
    $owner = userWithRole('employee');
    $event = legalEvent($owner, userWithRole('lawyer'));
    $this->actingAs($owner)->post(route('events.documents.store', $event), ['documents' => [UploadedFile::fake()->create('kanıt.pdf', 10, 'application/pdf')]]);
    $document = Document::query()->firstOrFail();
    $attacker = userWithRole('employee');

    $this->actingAs($attacker)->get(route('events.show', $event))->assertForbidden();
    $this->actingAs($attacker)->get(route('documents.download', $document))->assertForbidden();
    $this->actingAs($attacker)->post(route('events.documents.store', $event), ['documents' => [UploadedFile::fake()->create('kanıt.pdf', 10, 'application/pdf')]])->assertForbidden();
    $this->actingAs($attacker)->post(route('events.updates.store', $event), ['description' => 'Yetkisiz işlem'])->assertForbidden();

    expect(AuditLog::query()->where('action', AuditAction::DocumentDownloaded)->count())->toBe(0);
});

it('forbids a lawyer from another lawyers event, documents and mutations', function () {
    Storage::fake('legal_private');
    $owner = userWithRole('employee');
    $lawyer = userWithRole('lawyer');
    $event = legalEvent($owner, $lawyer);
    $this->actingAs($owner)->post(route('events.documents.store', $event), ['documents' => [UploadedFile::fake()->create('kanıt.pdf', 10, 'application/pdf')]]);
    $document = Document::query()->firstOrFail();
    $attacker = userWithRole('lawyer');

    $this->actingAs($attacker)->get(route('events.show', $event))->assertForbidden();
    $this->actingAs($attacker)->get(route('documents.download', $document))->assertForbidden();
    $this->actingAs($attacker)->patch(route('events.status.update', $event), ['status' => 'closed'])->assertForbidden();
    $this->actingAs($attacker)->patch(route('events.priority.update', $event), ['priority' => 'urgent'])->assertForbidden();
    $this->actingAs($attacker)->post(route('events.updates.store', $event), ['description' => 'Yetkisiz işlem'])->assertForbidden();
});

it('forbids non managers from every manager administration boundary', function (string $role) {
    $actor = userWithRole($role);
    $target = userWithRole('employee');
    $event = legalEvent(userWithRole('employee'), userWithRole('lawyer'));

    $this->actingAs($actor)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($actor)->get(route('admin.event-types.index'))->assertForbidden();
    $this->actingAs($actor)->get(route('audit-logs.index'))->assertForbidden();
    $this->actingAs($actor)->post(route('admin.users.activate', $target))->assertForbidden();
    $this->actingAs($actor)->post(route('admin.users.send-password-reset', $target))->assertForbidden();
    $this->actingAs($actor)->patch(route('events.lawyer.update', $event), ['assigned_lawyer_id' => userWithRole('lawyer')->id])->assertForbidden();
})->with(['employee', 'lawyer']);

it('does not mutate state through GET requests', function () {
    $manager = userWithRole('manager');
    $event = legalEvent(userWithRole('employee'), userWithRole('lawyer'));

    $this->actingAs($manager)->get(route('events.status.update', $event))->assertMethodNotAllowed();
    $this->actingAs($manager)->get(route('events.priority.update', $event))->assertMethodNotAllowed();
    $this->actingAs($manager)->get('/admin/users/'.userWithRole('employee')->id.'/deactivate')->assertMethodNotAllowed();
    expect($event->fresh()->system_status->value)->toBe('open');
});

it('ignores manipulated client event ownership and lifecycle values', function () {
    $employee = userWithRole('employee');
    $lawyer = userWithRole('lawyer');

    $this->actingAs($employee)->post(route('events.store'), [
        ...eventPayload($lawyer),
        'created_by' => userWithRole('employee')->id,
        'event_no' => 'ATTACK-1',
        'system_status' => 'closed',
        'closed_at' => now()->toDateTimeString(),
    ])->assertRedirect();

    $event = Event::query()->latest('id')->firstOrFail();
    expect($event->created_by)->toBe($employee->id)
        ->and($event->event_no)->not->toBe('ATTACK-1')
        ->and($event->system_status->value)->toBe('open')
        ->and($event->closed_at)->toBeNull();
});

it('ignores manipulated document storage and ownership values', function () {
    Storage::fake('legal_private');
    $owner = userWithRole('employee');
    $event = legalEvent($owner, userWithRole('lawyer'));

    $this->actingAs($owner)->post(route('events.documents.store', $event), [
        'documents' => [UploadedFile::fake()->create('kanıt.pdf', 10, 'application/pdf')],
        'path' => '../../outside',
        'disk' => 'public',
        'uploaded_by' => userWithRole('employee')->id,
        'event_id' => 999,
    ])->assertRedirect();

    $document = Document::query()->firstOrFail();
    expect($document->disk)->toBe('legal_private')
        ->and($document->event_id)->toBe($event->id)
        ->and($document->uploaded_by)->toBe($owner->id)
        ->and($document->path)->not->toContain('..');
});

it('rejects executable and double extension uploads', function (string $filename, string $mime) {
    Storage::fake('legal_private');
    $employee = userWithRole('employee');
    $event = legalEvent($employee, userWithRole('lawyer'));

    $this->actingAs($employee)->post(route('events.documents.store', $event), [
        'documents' => [UploadedFile::fake()->create($filename, 10, $mime)],
    ])->assertSessionHasErrors('documents.0');

    expect(Document::query()->count())->toBe(0);
})->with([
    'php' => ['shell.php', 'application/x-php'],
    'double extension' => ['document.pdf.php', 'application/pdf'],
    'executable' => ['image.jpg.exe', 'application/octet-stream'],
]);

it('uses private no-store and nosniff headers for document downloads', function () {
    Storage::fake('legal_private');
    $employee = userWithRole('employee');
    $event = legalEvent($employee, userWithRole('lawyer'));
    $this->actingAs($employee)->post(route('events.documents.store', $event), ['documents' => [UploadedFile::fake()->create('kanıt.pdf', 10, 'application/pdf')]]);
    $document = Document::query()->firstOrFail();

    $this->actingAs($employee)->get(route('documents.download', $document))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition');
});

it('does not expose private documents from public style URLs', function () {
    expect($this->get('/storage/private/legal-documents/example.pdf')->getStatusCode())->toBeIn([403, 404]);
    expect($this->get('/legal-documents/example.pdf')->getStatusCode())->toBeIn([403, 404]);
});

it('does not expose registration, backup download, or audit mutation routes', function () {
    $this->get('/register')->assertNotFound();
    $this->get('/backup/download')->assertNotFound();
    $this->delete('/admin/audit-logs/1')->assertMethodNotAllowed();
});

it('throttles repeated password reset requests without revealing account existence', function () {
    foreach (range(1, 3) as $attempt) {
        $this->post(route('password.email'), ['email' => 'unknown@example.test'])
            ->assertRedirect()
            ->assertSessionHas('status', 'Parola sıfırlama bağlantısı gönderildiyse e-posta adresinize ulaşacaktır.');
    }

    $this->post(route('password.email'), ['email' => 'unknown@example.test'])->assertTooManyRequests();
});

it('throttles manager reset resend attempts for the same target', function () {
    $manager = userWithRole('manager');
    $inactiveUser = userWithRole('employee', false);

    foreach (range(1, 5) as $attempt) {
        $this->actingAs($manager)->post(route('admin.users.send-password-reset', $inactiveUser))->assertRedirect();
    }

    $this->actingAs($manager)->post(route('admin.users.send-password-reset', $inactiveUser))->assertTooManyRequests();
});
