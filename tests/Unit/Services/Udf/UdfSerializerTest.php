<?php

use App\Services\Udf\UdfParser;
use App\Services\Udf\UdfSerializer;
use Tests\Support\UdfFixture;
use Tests\TestCase;

uses(TestCase::class);

it('round trips Turkish characters and supported text formatting', function () {
    $parser = app(UdfParser::class);
    $serializer = app(UdfSerializer::class);
    $source = UdfFixture::xml('formatted-content.xml');
    $parsed = $parser->parse($source);
    $parsed['content']['content'][0]['content'][] = ['type' => 'text', 'text' => ' çğıİöşü'];

    $xml = $serializer->serialize($parsed['content'], $source);
    $roundTrip = $parser->parse($xml);

    expect($xml)->toContain('<properties/>', '<styles>');
    expect(json_encode($roundTrip['content'], JSON_UNESCAPED_UNICODE))->toContain('çğıİöşü');
    expect($roundTrip['content']['content'][0]['content'][0]['marks'])->toContain(['type' => 'bold']);
});

it('round trips table structure and cell text', function () {
    $parser = app(UdfParser::class);
    $source = UdfFixture::xml('table-content.xml');
    $content = $parser->parse($source)['content'];

    $roundTrip = $parser->parse(app(UdfSerializer::class)->serialize($content, $source));

    expect($roundTrip['content']['content'][0]['type'])->toBe('table');
    expect($roundTrip['content']['content'][0]['content'][0]['content'])->toHaveCount(2);
    expect(json_encode($roundTrip['content'], JSON_UNESCAPED_UNICODE))->toContain('Başlık', 'Değer');
});
