<?php

use App\Services\Udf\UdfArchiveService;
use App\Services\Udf\UdfException;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UdfFixture;
use Tests\TestCase;

uses(TestCase::class);

it('reads a valid UDF and preserves unknown entries in an edited copy', function () {
    Storage::fake('legal_private');
    $original = UdfFixture::archive('simple-content.xml', ['metadata/custom.bin' => 'preserve-me']);
    Storage::disk('legal_private')->put('source.udf', $original);
    $service = app(UdfArchiveService::class);

    $archive = $service->read('legal_private', 'source.udf');
    $edited = $service->buildEditedCopy('legal_private', 'source.udf', str_replace('Sayın', 'Değerli', $archive['content_xml']));
    Storage::disk('legal_private')->put('edited.udf', $edited);
    $editedArchive = $service->read('legal_private', 'edited.udf');

    expect($archive['entries'])->toContain('content.xml', 'metadata/custom.bin');
    expect($editedArchive['entries'])->toContain('metadata/custom.bin');
    expect($editedArchive['content_xml'])->toContain('Değerli Mahkeme');
    expect(Storage::disk('legal_private')->get('source.udf'))->toBe($original);
});

it('reads and replaces a single content XML nested in a zipped folder', function () {
    Storage::fake('legal_private');
    $original = UdfFixture::archiveEntries([
        'belge/content.xml' => UdfFixture::xml('simple-content.xml'),
        'belge/metadata/custom.bin' => 'preserve-me',
    ]);
    Storage::disk('legal_private')->put('nested.udf', $original);
    $service = app(UdfArchiveService::class);

    $archive = $service->read('legal_private', 'nested.udf');
    $edited = $service->buildEditedCopy(
        'legal_private',
        'nested.udf',
        str_replace('Sayın', 'Değerli', $archive['content_xml']),
    );
    Storage::disk('legal_private')->put('nested-edited.udf', $edited);
    $editedArchive = $service->read('legal_private', 'nested-edited.udf');

    expect($archive['content_entry'])->toBe('belge/content.xml');
    expect($editedArchive['content_entry'])->toBe('belge/content.xml');
    expect($editedArchive['content_xml'])->toContain('Değerli Mahkeme');
    expect($editedArchive['entries'])->toContain('belge/metadata/custom.bin');
    expect($editedArchive['entries'])->not->toContain('content.xml');
    expect(Storage::disk('legal_private')->get('nested.udf'))->toBe($original);
});

it('rejects invalid ZIP files and archives without content XML', function () {
    Storage::fake('legal_private');
    Storage::disk('legal_private')->put('fake.udf', 'not-a-zip');
    Storage::disk('legal_private')->put('missing.udf', UdfFixture::archiveEntries(['metadata.txt' => 'test']));
    $service = app(UdfArchiveService::class);

    expect(fn () => $service->read('legal_private', 'fake.udf'))
        ->toThrow(UdfException::class, 'Geçersiz UDF dosyası');
    expect(fn () => $service->read('legal_private', 'missing.udf'))
        ->toThrow(UdfException::class, 'content.xml bulunamadı');
});

it('rejects archives containing more than one content XML', function () {
    Storage::fake('legal_private');
    Storage::disk('legal_private')->put('ambiguous.udf', UdfFixture::archiveEntries([
        'content.xml' => UdfFixture::xml('simple-content.xml'),
        'belge/content.xml' => UdfFixture::xml('formatted-content.xml'),
    ]));

    expect(fn () => app(UdfArchiveService::class)->read('legal_private', 'ambiguous.udf'))
        ->toThrow(UdfException::class, 'birden fazla content.xml');
});

it('rejects ZIP Slip paths', function () {
    Storage::fake('legal_private');
    Storage::disk('legal_private')->put('unsafe.udf', UdfFixture::archive('simple-content.xml', ['../outside.txt' => 'blocked']));

    expect(fn () => app(UdfArchiveService::class)->read('legal_private', 'unsafe.udf'))
        ->toThrow(UdfException::class, 'güvenli olmayan');
});

it('enforces entry count and uncompressed size limits', function () {
    Storage::fake('legal_private');
    Storage::disk('legal_private')->put('many.udf', UdfFixture::archive('simple-content.xml', ['one.txt' => '1']));
    config(['udf.max_entries' => 1]);

    expect(fn () => app(UdfArchiveService::class)->read('legal_private', 'many.udf'))
        ->toThrow(UdfException::class, 'fazla dosya');

    config(['udf.max_entries' => 100, 'udf.max_uncompressed_size' => 10]);
    expect(fn () => app(UdfArchiveService::class)->read('legal_private', 'many.udf'))
        ->toThrow(UdfException::class, 'açılmış boyutu');
});
