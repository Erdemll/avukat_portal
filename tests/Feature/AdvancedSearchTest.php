<?php

it('searches only legal records visible to the authenticated lawyer', function () {
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $otherLawyer = userWithRole('lawyer');
    legalCaseFile($manager, [$lawyer])->update(['title' => 'Benzersiz marka uyuşmazlığı']);
    legalCaseFile($manager, [$otherLawyer])->update(['title' => 'Benzersiz gizli uyuşmazlık']);

    $this->actingAs($lawyer)->get(route('search.index', ['q' => 'Benzersiz']))
        ->assertOk()
        ->assertSee('Benzersiz marka uyuşmazlığı')
        ->assertDontSee('Benzersiz gizli uyuşmazlık');
});

it('rejects employees and invalid search filters', function () {
    $this->actingAs(userWithRole('employee'))->get(route('search.index', ['q' => 'dosya']))->assertForbidden();
    $this->actingAs(userWithRole('manager'))->get(route('search.index', ['q' => 'x']))->assertSessionHasErrors('q');
});
