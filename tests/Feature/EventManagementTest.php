<?php

test('managers can change event status and priority', function () {
    $event = legalEvent(userWithRole('employee'), userWithRole('lawyer'));
    $manager = userWithRole('manager');
    $this->actingAs($manager)->patch(route('events.status.update', $event), ['status' => 'closed'])->assertRedirect();
    expect($event->fresh()->closed_at)->not->toBeNull();
    $this->actingAs($manager)->patch(route('events.priority.update', $event), ['priority' => 'urgent'])->assertRedirect();
    expect($event->fresh()->priority->value)->toBe('urgent');
});
