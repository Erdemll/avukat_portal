<?php

use App\Models\Document;
use App\Models\EventUpdate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('forbids employees and unassigned lawyers from creating event updates', function () {
    $lawyer = userWithRole('lawyer');
    $event = legalEvent(userWithRole('employee'), $lawyer);
    $this->actingAs(userWithRole('employee'))->post(route('events.updates.store', $event), ['description' => 'Yetkisiz'])->assertForbidden();
    $this->actingAs(userWithRole('lawyer'))->post(route('events.updates.store', $event), ['description' => 'Yetkisiz'])->assertForbidden();
});

it('allows assigned lawyers and managers to create immutable updates', function () {
    $lawyer = userWithRole('lawyer');
    $event = legalEvent(userWithRole('employee'), $lawyer);
    $this->actingAs($lawyer)->post(route('events.updates.store', $event), ['title' => 'İhtarname', 'description' => 'İhtarname gönderildi.'])->assertRedirect();
    $event->refresh();
    expect($event->current_process)->toBe('İhtarname gönderildi.');
    expect(EventUpdate::query()->count())->toBe(1);
    $this->actingAs(userWithRole('manager'))->post(route('events.updates.store', $event), ['description' => 'Yönetici notu'])->assertRedirect();
    $this->get('/events/'.$event->id.'/updates/'.$event->updates()->first()->id.'/edit')->assertNotFound();
});

it('associates update uploads with both the update and its event', function () {
    Storage::fake('legal_private');
    $lawyer = userWithRole('lawyer');
    $event = legalEvent(userWithRole('employee'), $lawyer);
    $this->actingAs($lawyer)->post(route('events.updates.store', $event), ['description' => 'Dava açıldı.', 'documents' => [UploadedFile::fake()->create('dava.pdf', 20, 'application/pdf')]])->assertRedirect();
    $document = Document::query()->firstOrFail();
    expect($document->event_id)->toBe($event->id);
    expect($document->event_update_id)->not->toBeNull();
    Storage::disk('legal_private')->assertExists($document->path);
});
