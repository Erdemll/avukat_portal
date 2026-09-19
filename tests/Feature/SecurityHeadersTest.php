<?php

use Illuminate\Support\Facades\Vite;

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
            ->toContain("script-src 'self' 'unsafe-inline' 'unsafe-eval';")
            ->toContain("connect-src 'self';");
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
            ->toContain("script-src 'self' 'unsafe-inline' 'unsafe-eval' http://127.0.0.1:5173;")
            ->toContain("style-src 'self' 'unsafe-inline' http://127.0.0.1:5173;")
            ->toContain("font-src 'self' data: http://127.0.0.1:5173;")
            ->toContain("connect-src 'self' http://127.0.0.1:5173 ws://127.0.0.1:5173;");
    } finally {
        unlink($hotFile);
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
