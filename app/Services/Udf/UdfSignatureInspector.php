<?php

namespace App\Services\Udf;

class UdfSignatureInspector
{
    /**
     * @param  list<string>  $entries
     */
    public function inspect(array $entries, string $contentXml): string
    {
        foreach ($entries as $entry) {
            $normalized = mb_strtolower(str_replace('\\', '/', $entry));

            if ($normalized === 'sign.sgn'
                || preg_match('/(^|\/)(signature|signatures)(\/|\.|$)/', $normalized) === 1
                || preg_match('/\.(sgn|p7s|p7m)$/', $normalized) === 1) {
                return 'signed_or_signature_detected';
            }
        }

        if (preg_match('/<(?:[A-Za-z0-9_-]+:)?Signature\b/i', $contentXml) === 1) {
            return 'signed_or_signature_detected';
        }

        return 'unsigned';
    }
}
