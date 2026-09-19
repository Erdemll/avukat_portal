<?php

use App\Services\Udf\UdfException;
use App\Services\Udf\UdfParser;
use Tests\Support\UdfFixture;
use Tests\TestCase;

uses(TestCase::class);

it('parses Turkish text and paragraph alignment into canonical JSON', function () {
    $parsed = app(UdfParser::class)->parse(UdfFixture::xml('simple-content.xml'));

    expect($parsed['compatibility'])->toBe('full');
    expect($parsed['content']['content'][0]['attrs']['textAlign'])->toBe('justify');
    expect($parsed['content']['content'][0]['content'][0]['text'])->toBe('Sayın Mahkeme');
    expect($parsed['content']['content'][1]['content'][0]['text'])->toBe('çğıİöşü');
});

it('parses supported formatting and tables', function () {
    $formatted = app(UdfParser::class)->parse(UdfFixture::xml('formatted-content.xml'));
    $table = app(UdfParser::class)->parse(UdfFixture::xml('table-content.xml'));

    expect($formatted['content']['content'][0]['content'][0]['marks'])
        ->toContain(['type' => 'bold'], ['type' => 'fontSize', 'attrs' => ['size' => 14.0]]);
    expect($formatted['content']['content'][0]['attrs']['indent'])->toBe(1);
    expect($table['content']['content'][0]['type'])->toBe('table');
    expect($table['content']['content'][0]['content'][0]['content'])->toHaveCount(2);
});

it('marks unknown structural nodes as unsupported without exposing raw XML', function () {
    $parsed = app(UdfParser::class)->parse(UdfFixture::xml('unknown-node-content.xml'));

    expect($parsed['compatibility'])->toBe('unsupported');
    expect($parsed['unsupported_nodes'])->toContain('elements/custom-field');
    expect(json_encode($parsed))->not->toContain('<custom-field');
});

it('rejects malformed XML and XXE declarations without reading local files', function () {
    $xxe = <<<'XML'
<?xml version="1.0"?><!DOCTYPE template [<!ENTITY secret SYSTEM "file:///etc/passwd">]><template><content>&secret;</content><elements/></template>
XML;

    expect(fn () => app(UdfParser::class)->parse('<template>'))
        ->toThrow(UdfException::class, 'geçerli XML değil');
    expect(fn () => app(UdfParser::class)->parse($xxe))
        ->toThrow(UdfException::class, 'DTD veya entity');
});
