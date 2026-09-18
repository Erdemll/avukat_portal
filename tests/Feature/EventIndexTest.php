<?php

use App\Models\Event;
use App\Models\EventType;

it('searches an employees own events without exposing another employees matching event', function () {
    $employee = userWithRole('employee');
    $lawyer = userWithRole('lawyer');
    $own = legalEvent($employee, $lawyer);
    $own->update(['title' => 'Özel arama kaydı']);
    $other = legalEvent(userWithRole('employee'), $lawyer);
    $other->update(['title' => 'Özel arama kaydı']);

    $this->actingAs($employee)->get(route('events.index', ['search' => 'Özel arama']))
        ->assertSee($own->event_no)
        ->assertDontSee($other->event_no);
});

it('searches creator and assigned lawyer names for managers', function () {
    $manager = userWithRole('manager');
    $creator = userWithRole('employee');
    $lawyer = userWithRole('lawyer');
    $event = legalEvent($creator, $lawyer);
    $creator->update(['name' => 'Aranan Oluşturan']);
    $lawyer->update(['name' => 'Aranan Avukat']);

    $this->actingAs($manager)->get(route('events.index', ['search' => 'Aranan Oluşturan']))->assertSee($event->event_no);
    $this->actingAs($manager)->get(route('events.index', ['search' => 'Aranan Avukat']))->assertSee($event->event_no);
});

it('applies event filters after the visibility scope', function () {
    $employee = userWithRole('employee');
    $lawyer = userWithRole('lawyer');
    $type = EventType::factory()->create();
    $matching = Event::factory()->create(['event_type_id' => $type, 'created_by' => $employee, 'assigned_lawyer_id' => $lawyer, 'system_status' => 'waiting', 'priority' => 'high']);
    $other = Event::factory()->create(['event_type_id' => $type, 'created_by' => userWithRole('employee'), 'assigned_lawyer_id' => $lawyer, 'system_status' => 'waiting', 'priority' => 'high']);

    $this->actingAs($employee)->get(route('events.index', ['event_type' => $type->id, 'status' => 'waiting', 'priority' => 'high']))
        ->assertSee($matching->event_no)
        ->assertDontSee($other->event_no);
});

it('rejects invalid filter and sort values', function () {
    $manager = userWithRole('manager');

    $this->actingAs($manager)->get(route('events.index', ['status' => 'invalid', 'sort' => 'title', 'direction' => 'sideways']))
        ->assertSessionHasErrors(['status', 'sort', 'direction']);
});

it('rejects an inactive or non lawyer assignee filter', function () {
    $manager = userWithRole('manager');
    $inactiveLawyer = userWithRole('lawyer', false);
    $employee = userWithRole('employee');

    $this->actingAs($manager)->get(route('events.index', ['assigned_lawyer' => $inactiveLawyer->id]))->assertSessionHasErrors('assigned_lawyer');
    $this->actingAs($manager)->get(route('events.index', ['assigned_lawyer' => $employee->id]))->assertSessionHasErrors('assigned_lawyer');
});

it('rejects a date range whose end precedes its start', function () {
    $this->actingAs(userWithRole('manager'))->get(route('events.index', [
        'date_from' => '2026-09-10',
        'date_to' => '2026-09-09',
    ]))->assertSessionHasErrors('date_to');
});

it('paginates events by twenty while retaining the search query', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    Event::factory()->count(21)->create(['created_by' => userWithRole('employee'), 'assigned_lawyer_id' => $lawyer, 'title' => 'Sayfalama kaydı']);

    $this->actingAs($manager)->get(route('events.index', ['search' => 'Sayfalama']))
        ->assertSee('search=Sayfalama&amp;page=2', false);
});

it('shows reassignment only to managers and status controls only to editors', function () {
    $employee = userWithRole('employee');
    $lawyer = userWithRole('lawyer');
    $event = legalEvent($employee, $lawyer);

    $this->actingAs($employee)->get(route('events.show', $event))->assertDontSee('Avukat Değiştir')->assertDontSee('Durum Değiştir');
    $this->actingAs($lawyer)->get(route('events.show', $event))->assertSee('Durum Değiştir')->assertDontSee('Avukat Değiştir');
    $this->actingAs(userWithRole('manager'))->get(route('events.show', $event))->assertSee('Durum Değiştir')->assertSee('Avukat Değiştir');
});
