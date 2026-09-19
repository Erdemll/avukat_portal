<?php

namespace App\Services\Udf;

use DOMDocument;
use DOMElement;
use DOMNode;

class UdfParser
{
    private string $textPool = '';

    /** @var list<string> */
    private array $unsupportedNodes = [];

    private bool $hasUnsupportedStructure = false;

    /**
     * @return array{content: array<string, mixed>, compatibility: string, unsupported_nodes: list<string>, format_id: ?string}
     */
    public function parse(string $xml): array
    {
        $document = $this->loadXml($xml);
        $root = $document->documentElement;

        if (! $root instanceof DOMElement || $root->localName !== 'template') {
            throw new UdfException('UDF XML kök yapısı desteklenmiyor.', 'unsupported_xml_root');
        }

        $contentElement = $this->firstDirectChild($root, 'content');
        $elementsElement = $this->firstDirectChild($root, 'elements');

        if ($contentElement === null || $elementsElement === null) {
            throw new UdfException('UDF XML içinde content veya elements bölümü bulunamadı.', 'missing_xml_section');
        }

        $this->textPool = $contentElement->textContent;
        $this->unsupportedNodes = [];
        $this->hasUnsupportedStructure = false;

        foreach ($this->elementChildren($root) as $child) {
            if (! in_array($child->localName, ['content', 'properties', 'elements', 'styles', 'data'], true)) {
                $this->unsupportedNodes[] = 'template/'.$child->localName;
            }
        }

        $content = [];
        $children = $this->elementChildren($elementsElement);

        for ($index = 0; $index < count($children); $index++) {
            $child = $children[$index];

            if ($child->localName === 'paragraph' && $this->listType($child) !== null) {
                [$list, $index] = $this->parseList($children, $index);
                $content[] = $list;

                continue;
            }

            $node = $this->parseBlock($child);
            if ($node !== null) {
                $content[] = $node;
            }
        }

        if ($content === []) {
            $content[] = ['type' => 'paragraph', 'attrs' => ['textAlign' => 'left', 'indent' => 0], 'content' => []];
        }

        $unsupportedNodes = array_values(array_unique($this->unsupportedNodes));
        $compatibility = match (true) {
            $this->hasUnsupportedStructure => 'unsupported',
            $unsupportedNodes !== [] => 'partial',
            default => 'full',
        };

        return [
            'content' => ['type' => 'doc', 'content' => $content],
            'compatibility' => $compatibility,
            'unsupported_nodes' => $unsupportedNodes,
            'format_id' => $root->getAttribute('format_id') ?: null,
        ];
    }

    private function loadXml(string $xml): DOMDocument
    {
        if (preg_match('/<!\s*(DOCTYPE|ENTITY)\b/i', $xml) === 1) {
            throw new UdfException('UDF XML içinde güvenli olmayan DTD veya entity tanımı bulundu.', 'unsafe_xml_declaration');
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->resolveExternals = false;
        $document->substituteEntities = false;

        try {
            if (! $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT | LIBXML_BIGLINES)) {
                throw new UdfException('UDF içindeki content.xml geçerli XML değil.', 'invalid_xml');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $document;
    }

    /** @param list<DOMElement> $siblings @return array{array<string, mixed>, int} */
    private function parseList(array $siblings, int $start): array
    {
        $first = $siblings[$start];
        $type = $this->listType($first);
        $listId = $first->getAttribute('ListId');
        $items = [];
        $index = $start;

        while (isset($siblings[$index])) {
            $paragraph = $siblings[$index];

            if ($paragraph->localName !== 'paragraph'
                || $this->listType($paragraph) !== $type
                || $paragraph->getAttribute('ListId') !== $listId) {
                break;
            }

            if ((int) $paragraph->getAttribute('ListLevel') > 0) {
                $this->unsupportedNodes[] = 'elements/paragraph[ListLevel>0]';
            }

            $items[] = [
                'type' => 'listItem',
                'content' => [$this->parseParagraph($paragraph)],
            ];
            $index++;
        }

        return [[
            'type' => $type,
            'content' => $items,
        ], $index - 1];
    }

    /** @return array<string, mixed>|null */
    private function parseBlock(DOMElement $element): ?array
    {
        return match ($element->localName) {
            'paragraph' => $this->parseParagraph($element),
            'table' => $this->parseTable($element),
            'page-break' => ['type' => 'horizontalRule'],
            default => $this->unsupportedBlock($element),
        };
    }

    /** @return array<string, mixed> */
    private function parseParagraph(DOMElement $paragraph): array
    {
        $content = [];

        foreach ($this->elementChildren($paragraph) as $child) {
            if (in_array($child->localName, ['content', 'space', 'tab'], true)) {
                $text = $this->textForOffsetNode($child);

                if ($text === "\u{200B}") {
                    continue;
                }

                if ($child->localName === 'tab') {
                    $text = "\t";
                }

                $content = [...$content, ...$this->textNodes($text, $child)];

                continue;
            }

            $this->unsupportedNodes[] = 'elements/paragraph/'.$child->localName;
            $this->hasUnsupportedStructure = true;
        }

        $alignment = match ($this->attribute($paragraph, ['Alignment', 'alignment'])) {
            '1' => 'center',
            '2' => 'right',
            '3' => 'justify',
            default => 'left',
        };
        $leftIndent = (float) ($this->attribute($paragraph, ['LeftIndent', 'leftIndent']) ?: 0);

        return [
            'type' => 'paragraph',
            'attrs' => [
                'textAlign' => $alignment,
                'indent' => max(0, min(8, (int) round($leftIndent / 18))),
            ],
            'content' => $content,
        ];
    }

    /** @return array<string, mixed> */
    private function parseTable(DOMElement $table): array
    {
        $rows = [];

        foreach ($this->elementChildren($table) as $row) {
            if ($row->localName !== 'row') {
                $this->unsupportedNodes[] = 'elements/table/'.$row->localName;
                $this->hasUnsupportedStructure = true;

                continue;
            }

            $cells = [];
            foreach ($this->elementChildren($row) as $cell) {
                if ($cell->localName !== 'cell') {
                    $this->unsupportedNodes[] = 'elements/table/row/'.$cell->localName;
                    $this->hasUnsupportedStructure = true;

                    continue;
                }

                $cellContent = [];
                foreach ($this->elementChildren($cell) as $block) {
                    $parsed = $this->parseBlock($block);
                    if ($parsed !== null) {
                        $cellContent[] = $parsed;
                    }
                }

                if ($cellContent === []) {
                    $cellContent[] = ['type' => 'paragraph', 'attrs' => ['textAlign' => 'left', 'indent' => 0], 'content' => []];
                }

                $cells[] = [
                    'type' => 'tableCell',
                    'attrs' => ['colspan' => max(1, min(20, (int) ($cell->getAttribute('colspan') ?: 1)))],
                    'content' => $cellContent,
                ];
            }

            if ($cells !== []) {
                $rows[] = ['type' => 'tableRow', 'content' => $cells];
            }
        }

        if ($rows === []) {
            $this->unsupportedNodes[] = 'elements/table[empty]';
            $this->hasUnsupportedStructure = true;

            return ['type' => 'paragraph', 'attrs' => ['textAlign' => 'left', 'indent' => 0], 'content' => []];
        }

        return ['type' => 'table', 'content' => $rows];
    }

    /** @return array<string, mixed>|null */
    private function unsupportedBlock(DOMElement $element): ?array
    {
        $this->unsupportedNodes[] = 'elements/'.$element->localName;
        $this->hasUnsupportedStructure = true;

        return null;
    }

    private function textForOffsetNode(DOMElement $element): string
    {
        $start = filter_var($element->getAttribute('startOffset'), FILTER_VALIDATE_INT);
        $length = filter_var($element->getAttribute('length'), FILTER_VALIDATE_INT);

        if ($start === false || $length === false || $start < 0 || $length < 0 || $start + $length > mb_strlen($this->textPool)) {
            throw new UdfException('UDF XML metin offsetleri geçersiz.', 'invalid_text_offset');
        }

        return mb_substr($this->textPool, $start, $length);
    }

    /** @return list<array<string, mixed>> */
    private function textNodes(string $text, DOMElement $source): array
    {
        if ($text === '') {
            return [];
        }

        $parts = preg_split('/(\R)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $nodes = [];

        foreach ($parts as $part) {
            if (preg_match('/^\R$/u', $part) === 1) {
                $nodes[] = ['type' => 'hardBreak'];

                continue;
            }

            $node = ['type' => 'text', 'text' => $part];
            $marks = $this->marks($source);
            if ($marks !== []) {
                $node['marks'] = $marks;
            }
            $nodes[] = $node;
        }

        return $nodes;
    }

    /** @return list<array<string, mixed>> */
    private function marks(DOMElement $element): array
    {
        $marks = [];

        foreach (['bold', 'italic', 'underline'] as $mark) {
            if (filter_var($element->getAttribute($mark), FILTER_VALIDATE_BOOLEAN)) {
                $marks[] = ['type' => $mark];
            }
        }

        $size = $element->getAttribute('size');
        if ($size !== '' && is_numeric($size) && (float) $size !== 12.0) {
            $marks[] = ['type' => 'fontSize', 'attrs' => ['size' => (float) $size]];
        }

        return $marks;
    }

    private function listType(DOMElement $paragraph): ?string
    {
        if (filter_var($paragraph->getAttribute('Bulleted'), FILTER_VALIDATE_BOOLEAN)) {
            return 'bulletList';
        }

        if (filter_var($paragraph->getAttribute('Numbered'), FILTER_VALIDATE_BOOLEAN)) {
            return 'orderedList';
        }

        return null;
    }

    private function attribute(DOMElement $element, array $names): string
    {
        foreach ($names as $name) {
            if ($element->hasAttribute($name)) {
                return $element->getAttribute($name);
            }
        }

        return '';
    }

    private function firstDirectChild(DOMElement $parent, string $name): ?DOMElement
    {
        foreach ($this->elementChildren($parent) as $child) {
            if ($child->localName === $name) {
                return $child;
            }
        }

        return null;
    }

    /** @return list<DOMElement> */
    private function elementChildren(DOMNode $parent): array
    {
        $children = [];

        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $children[] = $child;
            }
        }

        return $children;
    }
}
