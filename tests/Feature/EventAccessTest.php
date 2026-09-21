<?php

use App\Models\Event;
use Illuminate\Support\Facades\Hash;

it('rejects inactive users during login', function () {
    $user = userWithRole('employee', false);
    $user->forceFill(['email' => 'inactive@example.com', 'password' => Hash::make('Password123!')])->save();
    $this->post('/login', ['email' => $user->email, 'password' => 'Password123!'])->assertSessionHasErrors('email');
});

it('shows all events to managers', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    legalEvent(userWithRole('employee'), $lawyer);
    legalEvent(userWithRole('employee'), $lawyer);
    $this->actingAs($manager)->get('/events')->assertSee(Event::query()->first()->event_no);
});

it('limits employees to their own events and hides other event details', function () {
    $employee = userWithRole('employee');
    $other = userWithRole('employee');
    $lawyer = userWithRole('lawyer');
    $own = legalEvent($employee, $lawyer);
    $otherEvent = legalEvent($other, $lawyer);
    $this->actingAs($employee)->get('/events')->assertSee($own->event_no)->assertDontSee($otherEvent->event_no);
    $this->actingAs($employee)->get(route('events.show', $otherEvent))->assertForbidden();
});

it('limits lawyers to assigned events and forbids other event details', function () {
    $lawyer = userWithRole('lawyer');
    $otherLawyer = userWithRole('lawyer');
    $employee = userWithRole('employee');
    $own = legalEvent($employee, $lawyer);
    $other = legalEvent($employee, $otherLawyer);
    $this->actingAs($lawyer)->get('/events')->assertSee($own->event_no)->assertDontSee($other->event_no);
    $this->actingAs($lawyer)->get(route('events.show', $other))->assertForbidden();
});

it('allows employees and managers to create events but forbids lawyers', function () {
    $lawyer = userWithRole('lawyer');
    foreach (['employee', 'manager'] as $roleSlug) {
        $user = userWithRole($roleSlug);
        $this->actingAs($user)->post('/events', eventPayload($lawyer))->assertRedirect();
    }
    $this->actingAs($lawyer)->post('/events', eventPayload($lawyer))->assertForbidden();
});

it('accepts only active lawyers as event assignees', function () {
    $employee = userWithRole('employee');
    $notLawyer = userWithRole('employee');
    $this->actingAs($employee)->post('/events', eventPayload($notLawyer))->assertSessionHasErrors('assigned_lawyer_id');
});

it('lets managers and assigned lawyers delete events', function () {
    $employee = userWithRole('employee');
    $lawyer = userWithRole('lawyer');
    $otherLawyer = userWithRole('lawyer');
    $manager = userWithRole('manager');
    $event = legalEvent($employee, $lawyer);
    $this->actingAs($manager)->get(route('events.show', $event))->assertSee('Olayı Sil');
    $this->actingAs($employee)->delete(route('events.destroy', $event))->assertForbidden();
    $this->actingAs($otherLawyer)->delete(route('events.destroy', $event))->assertForbidden();
    $this->actingAs($lawyer)->delete(route('events.destroy', $event))->assertRedirect();
    $this->assertSoftDeleted('events', ['id' => $event->id]);
});

it('ignores protected event attributes from creation payloads', function () {
    $employee = userWithRole('employee');
    $lawyer = userWithRole('lawyer');
    $this->actingAs($employee)->post('/events', [...eventPayload($lawyer), 'created_by' => 999, 'event_no' => 'EVIL', 'system_status' => 'closed'])->assertRedirect();
    $event = Event::query()->latest('id')->first();
    expect($event->created_by)->toBe($employee->id);
    expect($event->event_no)->not->toBe('EVIL');
    expect($event->system_status->value)->toBe('open');
});
