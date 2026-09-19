<?php

namespace App\Services\Udf;

use DOMDocument;
use DOMElement;
use DOMNode;

class UdfSerializer
{
    private string $textPool = '';

    private int $listId = 0;

    public function __construct(private UdfContentValidator $validator) {}

    /** @param array<string, mixed> $content */
    public function serialize(array $content, string $originalXml): string
    {
        $this->validator->validate($content);
        $document = $this->loadXml($originalXml);
        $root = $document->documentElement;

        if (! $root instanceof DOMElement || $root->localName !== 'template') {
            throw new UdfException('UDF XML kök yapısı serialize edilemiyor.', 'serialization_failed');
        }

        $contentElement = $this->firstDirectChild($root, 'content');
        $elementsElement = $this->firstDirectChild($root, 'elements');

        if ($contentElement === null || $elementsElement === null) {
            throw new UdfException('UDF XML hedef bölümleri bulunamadı.', 'serialization_failed');
        }

        $this->textPool = '';
        $this->listId = 0;
        $this->removeChildren($elementsElement);

        foreach ($content['content'] as $index => $node) {
            $this->serializeBlock($document, $elementsElement, $node);

            if ($index < count($content['content']) - 1) {
                $this->textPool .= "\n";
            }
        }

        $this->removeChildren($contentElement);
        $contentElement->appendChild($document->createCDATASection($this->textPool));
        $document->encoding = 'UTF-8';
        $document->formatOutput = false;
        $xml = $document->saveXML();

        if (! is_string($xml)) {
            throw new UdfException('UDF XML çıktısı oluşturulamadı.', 'serialization_failed');
        }

        return $xml;
    }

    /** @param array<string, mixed> $node */
    private function serializeBlock(DOMDocument $document, DOMElement $parent, array $node): void
    {
        match ($node['type']) {
            'paragraph' => $parent->appendChild($this->paragraphElement($document, $node)),
            'bulletList', 'orderedList' => $this->appendList($document, $parent, $node),
            'table' => $parent->appendChild($this->tableElement($document, $node)),
            'horizontalRule' => $parent->appendChild($this->pageBreakElement($document)),
            default => throw new UdfException('Desteklenmeyen editör düğümü serialize edilemedi.', 'serialization_failed'),
        };
    }

    /** @param array<string, mixed> $node */
    private function paragraphElement(DOMDocument $document, array $node, array $extraAttributes = []): DOMElement
    {
        $paragraph = $document->createElement('paragraph');
        $alignment = match ($node['attrs']['textAlign'] ?? 'left') {
            'center' => '1',
            'right' => '2',
            'justify' => '3',
            default => '0',
        };
        $paragraph->setAttribute('Alignment', $alignment);
        $paragraph->setAttribute('LeftIndent', number_format((int) ($node['attrs']['indent'] ?? 0) * 18, 1, '.', ''));
        $paragraph->setAttribute('RightIndent', '0.0');

        foreach ($extraAttributes as $key => $value) {
            $paragraph->setAttribute($key, (string) $value);
        }

        $inlineContent = $node['content'] ?? [];
        if ($inlineContent === []) {
            $this->appendTextRun($document, $paragraph, "\u{200B}", []);

            return $paragraph;
        }

        foreach ($inlineContent as $inline) {
            if ($inline['type'] === 'hardBreak') {
                $this->appendTextRun($document, $paragraph, "\n", []);

                continue;
            }

            $parts = preg_split('/(\t)/u', $inline['text'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($parts as $part) {
                if ($part === "\t") {
                    $this->appendOffsetElement($document, $paragraph, 'tab', "\t", $inline['marks'] ?? []);
                } else {
                    $this->appendTextRun($document, $paragraph, $part, $inline['marks'] ?? []);
                }
            }
        }

        return $paragraph;
    }

    /** @param array<string, mixed> $list */
    private function appendList(DOMDocument $document, DOMElement $parent, array $list, int $level = 0): void
    {
        $listId = ++$this->listId;

        foreach ($list['content'] as $item) {
            foreach ($item['content'] as $child) {
                if ($child['type'] === 'paragraph') {
                    $attributes = $list['type'] === 'bulletList'
                        ? ['Bulleted' => 'true', 'BulletType' => 'BULLET_TYPE_ELLIPSE']
                        : ['Numbered' => 'true', 'NumberType' => 'NUMBER_TYPE_NUMBER_DOT'];
                    $attributes['ListId'] = (string) $listId;
                    $attributes['ListLevel'] = (string) $level;
                    $parent->appendChild($this->paragraphElement($document, $child, $attributes));
                } elseif (in_array($child['type'], ['bulletList', 'orderedList'], true)) {
                    $this->appendList($document, $parent, $child, $level + 1);
                }
            }
        }
    }

    /** @param array<string, mixed> $node */
    private function tableElement(DOMDocument $document, array $node): DOMElement
    {
        $table = $document->createElement('table');
        $columnCount = count($node['content'][0]['content']);
        $table->setAttribute('tableName', 'Sabit');
        $table->setAttribute('columnCount', (string) $columnCount);
        $table->setAttribute('columnSpans', implode(',', array_fill(0, $columnCount, '120.0')));
        $table->setAttribute('border', 'borderCell');

        foreach ($node['content'] as $rowNode) {
            $row = $document->createElement('row');
            $row->setAttribute('rowType', 'dataRow');

            foreach ($rowNode['content'] as $cellNode) {
                $cell = $document->createElement('cell');
                $cell->setAttribute('colspan', (string) ($cellNode['attrs']['colspan'] ?? 1));
                $cell->setAttribute('align', 'top');
                $cell->setAttribute('border', 'borderCell');
                $cell->setAttribute('borderStyle', 'borderStyle-solid');
                $cell->setAttribute('borderWidth', '1.0');
                $cell->setAttribute('borderColor', '-16777216');
                $cell->setAttribute('borderSpec', '15');

                foreach ($cellNode['content'] as $index => $block) {
                    $this->serializeBlock($document, $cell, $block);
                    if ($index < count($cellNode['content']) - 1) {
                        $this->textPool .= "\n";
                    }
                }

                $row->appendChild($cell);
            }

            $table->appendChild($row);
        }

        return $table;
    }

    private function pageBreakElement(DOMDocument $document): DOMElement
    {
        $pageBreak = $document->createElement('page-break');
        $pageBreak->appendChild($this->paragraphElement($document, [
            'type' => 'paragraph',
            'attrs' => ['textAlign' => 'left', 'indent' => 0],
            'content' => [],
        ]));

        return $pageBreak;
    }

    /** @param list<array<string, mixed>> $marks */
    private function appendTextRun(DOMDocument $document, DOMElement $parent, string $text, array $marks): void
    {
        $this->appendOffsetElement($document, $parent, 'content', $text, $marks);
    }

    /** @param list<array<string, mixed>> $marks */
    private function appendOffsetElement(DOMDocument $document, DOMElement $parent, string $name, string $text, array $marks): void
    {
        $element = $document->createElement($name);
        $element->setAttribute('startOffset', (string) mb_strlen($this->textPool));
        $element->setAttribute('length', (string) mb_strlen($text));
        $element->setAttribute('family', 'Times New Roman');
        $element->setAttribute('size', $this->fontSize($marks));

        foreach (['bold', 'italic', 'underline'] as $markName) {
            if ($this->hasMark($marks, $markName)) {
                $element->setAttribute($markName, 'true');
            }
        }

        $this->textPool .= $text;
        $parent->appendChild($element);
    }

    /** @param list<array<string, mixed>> $marks */
    private function hasMark(array $marks, string $type): bool
    {
        foreach ($marks as $mark) {
            if (($mark['type'] ?? null) === $type) {
                return true;
            }
        }

        return false;
    }

    /** @param list<array<string, mixed>> $marks */
    private function fontSize(array $marks): string
    {
        foreach ($marks as $mark) {
            if (($mark['type'] ?? null) === 'fontSize') {
                return rtrim(rtrim(number_format((float) $mark['attrs']['size'], 2, '.', ''), '0'), '.');
            }
        }

        return '12';
    }

    private function loadXml(string $xml): DOMDocument
    {
        if (preg_match('/<!\s*(DOCTYPE|ENTITY)\b/i', $xml) === 1) {
            throw new UdfException('UDF XML güvenli biçimde serialize edilemiyor.', 'unsafe_xml_declaration');
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->resolveExternals = false;
        $document->substituteEntities = false;

        try {
            if (! $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT | LIBXML_BIGLINES)) {
                throw new UdfException('UDF XML serialize edilmeden önce doğrulanamadı.', 'invalid_xml');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $document;
    }

    private function firstDirectChild(DOMElement $parent, string $name): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $name) {
                return $child;
            }
        }

        return null;
    }

    private function removeChildren(DOMNode $node): void
    {
        while ($node->firstChild !== null) {
            $node->removeChild($node->firstChild);
        }
    }
}
