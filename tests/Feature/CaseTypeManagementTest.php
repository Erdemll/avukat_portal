<?php

use App\AuditAction;
use App\Models\CaseType;

it('only lets managers manage legal case types', function () {
    $this->actingAs(userWithRole('employee'))->get(route('admin.case-types.index'))->assertForbidden();
    $this->actingAs(userWithRole('lawyer'))->get(route('admin.case-types.index'))->assertForbidden();
    $this->actingAs(userWithRole('manager'))->get(route('admin.case-types.index'))->assertOk();
});

it('creates legal case types with a controlled category and stable slug', function () {
    $manager = userWithRole('manager');

    $this->actingAs($manager)->post(route('admin.case-types.store'), [
        'name' => 'İş Davası',
        'category' => 'lawsuit',
        'description' => 'İş hukuku uyuşmazlıkları',
    ])->assertRedirect();

    $caseType = CaseType::query()->where('slug', 'is-davasi')->firstOrFail();
    $this->actingAs($manager)->put(route('admin.case-types.update', $caseType), [
        'name' => 'İş Hukuku Davası',
        'category' => 'lawsuit',
    ])->assertRedirect(route('admin.case-types.index'));

    expect($caseType->fresh()->slug)->toBe('is-davasi');
    $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::CaseTypeCreated->value]);
    $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::CaseTypeUpdated->value]);
});

it('rejects unknown categories and duplicate generated slugs', function () {
    $manager = userWithRole('manager');

    $this->actingAs($manager)->post(route('admin.case-types.store'), [
        'name' => 'Geçersiz Tür',
        'category' => 'unknown',
    ])->assertSessionHasErrors('category');

    CaseType::factory()->create(['name' => 'İş Davası', 'slug' => 'is-davasi']);
    $this->actingAs($manager)->post(route('admin.case-types.store'), [
        'name' => 'İş-Davası',
        'category' => 'lawsuit',
    ])->assertSessionHasErrors('slug');
});

it('activates and deactivates legal case types', function () {
    $manager = userWithRole('manager');
    $caseType = CaseType::factory()->create(['is_active' => true]);

    $this->actingAs($manager)->post(route('admin.case-types.deactivate', $caseType))->assertRedirect();
    expect($caseType->fresh()->is_active)->toBeFalse();

    $this->actingAs($manager)->post(route('admin.case-types.activate', $caseType))->assertRedirect();
    expect($caseType->fresh()->is_active)->toBeTrue();
});

it('filters legal case types by category and status', function () {
    $manager = userWithRole('manager');
    CaseType::factory()->create(['name' => 'Aktif İcra', 'slug' => 'aktif-icra', 'category' => 'enforcement', 'is_active' => true]);
    CaseType::factory()->create(['name' => 'Pasif Dava', 'slug' => 'pasif-dava', 'category' => 'lawsuit', 'is_active' => false]);

    $this->actingAs($manager)->get(route('admin.case-types.index', [
        'category' => 'enforcement',
        'status' => 'active',
    ]))->assertSee('Aktif İcra')->assertDontSee('Pasif Dava');
});
