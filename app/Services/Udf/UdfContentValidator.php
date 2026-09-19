<?php

namespace App\Services\Udf;

class UdfContentValidator
{
    private int $nodeCount = 0;

    private int $textLength = 0;

    /** @param array<string, mixed> $document */
    public function validate(array $document): void
    {
        $this->nodeCount = 0;
        $this->textLength = 0;
        $this->validateNode($document, 0, null);

        if (($document['type'] ?? null) !== 'doc') {
            throw new UdfException('Editör içeriğinin kök düğümü doc olmalıdır.', 'invalid_editor_content');
        }
    }

    /** @param array<string, mixed> $node */
    private function validateNode(array $node, int $depth, ?string $parent): void
    {
        $this->nodeCount++;

        if ($this->nodeCount > (int) config('udf.max_document_nodes')
            || $depth > (int) config('udf.max_document_depth')) {
            throw new UdfException('Editör içeriği izin verilen karmaşıklığı aşıyor.', 'editor_content_too_complex');
        }

        $type = $node['type'] ?? null;
        if (! is_string($type) || ! in_array($type, $this->allowedTypes(), true)) {
            throw new UdfException('Editör içeriğinde desteklenmeyen bir düğüm bulundu.', 'unsupported_editor_node');
        }

        $allowedKeys = $type === 'text' ? ['type', 'text', 'marks'] : ['type', 'attrs', 'content'];
        if (array_diff(array_keys($node), $allowedKeys) !== []) {
            throw new UdfException('Editör içeriğinde izin verilmeyen alanlar bulundu.', 'unexpected_editor_key');
        }

        $this->validateParent($type, $parent);
        $this->validateAttributes($type, $node['attrs'] ?? []);

        if ($type === 'text') {
            $this->validateTextNode($node);

            return;
        }

        $content = $node['content'] ?? [];
        if (! is_array($content) || ! array_is_list($content)) {
            throw new UdfException('Editör düğüm içeriği liste biçiminde olmalıdır.', 'invalid_editor_content');
        }

        foreach ($content as $child) {
            if (! is_array($child)) {
                throw new UdfException('Editör içeriğinde geçersiz bir alt düğüm bulundu.', 'invalid_editor_content');
            }

            $this->validateNode($child, $depth + 1, $type);
        }

        if (in_array($type, ['doc', 'bulletList', 'orderedList', 'listItem', 'table', 'tableRow', 'tableCell'], true) && $content === []) {
            throw new UdfException('Editör içeriğinde boş bırakılamayan bir yapı bulundu.', 'invalid_editor_content');
        }
    }

    /** @param array<string, mixed> $node */
    private function validateTextNode(array $node): void
    {
        if (! isset($node['text']) || ! is_string($node['text']) || $node['text'] === '') {
            throw new UdfException('Metin düğümü boş olamaz.', 'invalid_editor_text');
        }

        $this->textLength += mb_strlen($node['text']);
        if ($this->textLength > (int) config('udf.max_text_length')) {
            throw new UdfException('Editör metni izin verilen uzunluğu aşıyor.', 'editor_text_too_long');
        }

        $marks = $node['marks'] ?? [];
        if (! is_array($marks) || ! array_is_list($marks)) {
            throw new UdfException('Metin biçimleri geçersiz.', 'invalid_editor_marks');
        }

        foreach ($marks as $mark) {
            if (! is_array($mark) || ! isset($mark['type']) || ! in_array($mark['type'], ['bold', 'italic', 'underline', 'fontSize'], true)) {
                throw new UdfException('Desteklenmeyen metin biçimi kullanıldı.', 'unsupported_editor_mark');
            }

            $allowedMarkKeys = $mark['type'] === 'fontSize' ? ['type', 'attrs'] : ['type'];
            if (array_diff(array_keys($mark), $allowedMarkKeys) !== []) {
                throw new UdfException('Metin biçiminde izin verilmeyen alanlar bulundu.', 'unexpected_editor_key');
            }

            if ($mark['type'] === 'fontSize') {
                $size = $mark['attrs']['size'] ?? null;
                if (! is_numeric($size) || (float) $size < 8 || (float) $size > 72) {
                    throw new UdfException('Yazı boyutu 8 ile 72 punto arasında olmalıdır.', 'invalid_font_size');
                }
            }
        }
    }

    private function validateAttributes(string $type, mixed $attributes): void
    {
        if (! is_array($attributes)) {
            throw new UdfException('Editör düğüm özellikleri geçersiz.', 'invalid_editor_attributes');
        }

        $allowed = match ($type) {
            'paragraph' => ['textAlign', 'indent'],
            'tableCell' => ['colspan'],
            default => [],
        };

        if (array_diff(array_keys($attributes), $allowed) !== []) {
            throw new UdfException('Editör düğümünde izin verilmeyen özellikler bulundu.', 'unexpected_editor_attribute');
        }

        if ($type === 'paragraph') {
            $alignment = $attributes['textAlign'] ?? 'left';
            $indent = $attributes['indent'] ?? 0;

            if (! in_array($alignment, ['left', 'center', 'right', 'justify'], true)
                || ! is_int($indent)
                || $indent < 0
                || $indent > 8) {
                throw new UdfException('Paragraf hizalama veya girinti değeri geçersiz.', 'invalid_paragraph_attributes');
            }
        }

        if ($type === 'tableCell') {
            $colspan = $attributes['colspan'] ?? 1;
            if (! is_int($colspan) || $colspan < 1 || $colspan > 20) {
                throw new UdfException('Tablo hücresi sütun aralığı geçersiz.', 'invalid_table_attributes');
            }
        }
    }

    private function validateParent(string $type, ?string $parent): void
    {
        $allowedParents = [
            'doc' => [null],
            'paragraph' => ['doc', 'listItem', 'tableCell'],
            'text' => ['paragraph'],
            'hardBreak' => ['paragraph'],
            'horizontalRule' => ['doc'],
            'bulletList' => ['doc', 'listItem'],
            'orderedList' => ['doc', 'listItem'],
            'listItem' => ['bulletList', 'orderedList'],
            'table' => ['doc', 'tableCell'],
            'tableRow' => ['table'],
            'tableCell' => ['tableRow'],
        ];

        if (! in_array($parent, $allowedParents[$type], true)) {
            throw new UdfException('Editör düğümleri geçersiz bir sırada gönderildi.', 'invalid_editor_hierarchy');
        }
    }

    /** @return list<string> */
    private function allowedTypes(): array
    {
        return ['doc', 'paragraph', 'text', 'hardBreak', 'horizontalRule', 'bulletList', 'orderedList', 'listItem', 'table', 'tableRow', 'tableCell'];
    }
}
