<?php

use App\Models\Event;
use Illuminate\Support\Facades\Gate;

it('applies the event policy role matrix', function () {
    $employee = userWithRole('employee');
    $lawyer = userWithRole('lawyer');
    $manager = userWithRole('manager');
    $event = legalEvent($employee, $lawyer);

    expect(Gate::forUser($manager)->allows('view', $event))->toBeTrue();
    expect(Gate::forUser($employee)->allows('create', Event::class))->toBeTrue();
    expect(Gate::forUser($lawyer)->allows('create', Event::class))->toBeFalse();
    expect(Gate::forUser($lawyer)->allows('update', $event))->toBeTrue();
    expect(Gate::forUser($manager)->allows('forceDelete', $event))->toBeFalse();
});
