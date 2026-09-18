<?php

test('guests cannot access the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('shows employees only their own dashboard events', function () {
    $employee = userWithRole('employee');
    $lawyer = userWithRole('lawyer');
    $own = legalEvent($employee, $lawyer);
    $other = legalEvent(userWithRole('employee'), $lawyer);

    $this->actingAs($employee)->get(route('dashboard'))->assertSee($own->event_no)->assertDontSee($other->event_no);
});

it('shows lawyers only their assigned events and unprocessed events', function () {
    $lawyer = userWithRole('lawyer');
    $own = legalEvent(userWithRole('employee'), $lawyer);
    $other = legalEvent(userWithRole('employee'), userWithRole('lawyer'));

    $this->actingAs($lawyer)->get(route('dashboard'))->assertSee($own->event_no)->assertDontSee($other->event_no)->assertSee('Henüz İşlem Yapılmamış Olaylar');
});

it('removes updated events from a lawyers unprocessed dashboard list', function () {
    $lawyer = userWithRole('lawyer');
    $event = legalEvent(userWithRole('employee'), $lawyer);
    $update = $event->updates()->make(['description' => 'İşleme alındı']);
    $update->user()->associate($lawyer);
    $update->save();

    $this->actingAs($lawyer)->get(route('dashboard'))
        ->assertViewHas('unprocessedEvents', fn ($events) => $events->doesntContain('id', $event->id));
});

it('shows managers all events on the dashboard', function () {
    $lawyer = userWithRole('lawyer');
    $first = legalEvent(userWithRole('employee'), $lawyer);
    $second = legalEvent(userWithRole('employee'), $lawyer);

    $this->actingAs(userWithRole('manager'))->get(route('dashboard'))->assertSee($first->event_no)->assertSee($second->event_no)->assertSee('Avukat İş Yükü');
});
