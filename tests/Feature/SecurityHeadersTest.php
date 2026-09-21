<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Tests\Support\UdfFixture;

it('ignores IPv6 Vite origins that browsers reject in CSP host sources', function () {
    $hotFile = tempnam(sys_get_temp_dir(), 'vite-hot-');
    file_put_contents($hotFile, "http://[::1]:5173\n");
    Vite::useHotFile($hotFile);
    app()->detectEnvironment(fn (): string => 'local');

    try {
        $policy = $this->get(route('login'))
            ->assertOk()
            ->headers->get('Content-Security-Policy');

        expect($policy)
            ->not->toContain('[::1]')
            ->toMatch("/script-src 'nonce-[A-Za-z0-9]+' 'strict-dynamic' 'self';/")
            ->toContain("connect-src 'self' wss://ws.tepenetguvenlik.com;");
    } finally {
        unlink($hotFile);
    }
});

it('allows the IPv4 Vite development server in the local CSP', function () {
    $hotFile = tempnam(sys_get_temp_dir(), 'vite-hot-');
    file_put_contents($hotFile, "http://127.0.0.1:5173\n");
    Vite::useHotFile($hotFile);
    app()->detectEnvironment(fn (): string => 'local');

    try {
        $policy = $this->get(route('login'))
            ->assertOk()
            ->headers->get('Content-Security-Policy');

        expect($policy)
            ->toMatch("/script-src 'nonce-[A-Za-z0-9]+' 'strict-dynamic' 'self' http:\/\/127\.0\.0\.1:5173;/")
            ->toMatch("/style-src 'self' 'nonce-[A-Za-z0-9]+' http:\/\/127\.0\.0\.1:5173;/")
            ->toContain("font-src 'self' data: http://127.0.0.1:5173;")
            ->toContain("connect-src 'self' wss://ws.tepenetguvenlik.com http://127.0.0.1:5173 ws://127.0.0.1:5173;");
    } finally {
        unlink($hotFile);
    }
});

it('uses a fresh nonce and blocks inline script handlers and evaluation', function () {
    $first = $this->get(route('login'))->assertOk();
    $second = $this->get(route('login'))->assertOk();

    $firstPolicy = $first->headers->get('Content-Security-Policy');
    $secondPolicy = $second->headers->get('Content-Security-Policy');

    preg_match("/script-src 'nonce-([A-Za-z0-9]+)' 'strict-dynamic' 'self';/", $firstPolicy, $firstNonce);
    preg_match("/script-src 'nonce-([A-Za-z0-9]+)' 'strict-dynamic' 'self';/", $secondPolicy, $secondNonce);

    expect($firstNonce[1] ?? null)->not->toBeNull()->not->toBe($secondNonce[1] ?? null);
    expect($firstPolicy)
        ->toContain("default-src 'none';")
        ->toContain("script-src-attr 'none';")
        ->toContain("style-src-attr 'none';")
        ->toContain("frame-src 'none';")
        ->toContain("base-uri 'none';")
        ->not->toContain("'unsafe-inline'")
        ->not->toContain("'unsafe-eval'");
    $first->assertSee('<meta property="csp-nonce" nonce="'.$firstNonce[1].'">', false)
        ->assertSee('<script nonce="'.$firstNonce[1].'">', false)
        ->assertDontSee('onclick=', false)
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('nonces the administrator form script', function () {
    $response = $this->actingAs(userWithRole('manager'))
        ->get(route('admin.users.create'))
        ->assertOk();

    preg_match("/script-src 'nonce-([A-Za-z0-9]+)'/", $response->headers->get('Content-Security-Policy'), $matches);

    $response->assertSee('<script nonce="'.$matches[1].'">', false);
});

it('permits UDF formatting styles only on UDF document pages', function () {
    Storage::fake('legal_private');
    $manager = userWithRole('manager');
    $lawyer = userWithRole('lawyer');
    $document = UdfFixture::document(legalCaseFile($manager, [$lawyer]), $lawyer);

    $this->actingAs($lawyer);

    foreach (['documents.udf.show', 'documents.udf.edit'] as $routeName) {
        $response = $this->get(route($routeName, $document))->assertOk();

        expect($response->headers->get('Content-Security-Policy'))
            ->toContain("style-src-attr 'unsafe-inline';")
            ->toContain("script-src-attr 'none';")
            ->not->toContain("'unsafe-eval'");
    }
});

it('does not allow the Vite development server outside the local environment', function () {
    $hotFile = tempnam(sys_get_temp_dir(), 'vite-hot-');
    file_put_contents($hotFile, 'http://127.0.0.1:5173');
    Vite::useHotFile($hotFile);
    app()->detectEnvironment(fn (): string => 'production');

    try {
        $policy = $this->get(route('login'))
            ->assertOk()
            ->headers->get('Content-Security-Policy');

        expect($policy)->not->toContain('127.0.0.1:5173');
    } finally {
        unlink($hotFile);
    }
});
