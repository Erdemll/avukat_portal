<?php

use App\Models\EventType;

test('managers can manage event types while other roles are forbidden', function () {
    $this->actingAs(userWithRole('employee'))->get(route('admin.event-types.index'))->assertForbidden();
    $this->actingAs(userWithRole('lawyer'))->get(route('admin.event-types.index'))->assertForbidden();
    $this->actingAs(userWithRole('manager'))->get(route('admin.event-types.index'))->assertOk();
});

test('managers create event types with stable generated slugs and filters', function () {
    $manager = userWithRole('manager');
    $this->actingAs($manager)->post(route('admin.event-types.store'), ['name' => 'Fikri Mülkiyet Uyuşmazlığı'])->assertRedirect();
    $type = EventType::query()->where('slug', 'fikri-mulkiyet-uyusmazligi')->firstOrFail();
    $this->actingAs($manager)->put(route('admin.event-types.update', $type), ['name' => 'Güncellenen ad'])->assertRedirect();
    expect($type->fresh()->slug)->toBe('fikri-mulkiyet-uyusmazligi');
    $this->actingAs($manager)->get(route('admin.event-types.index', ['search' => 'Güncellenen']))->assertSee('Güncellenen ad');
});
