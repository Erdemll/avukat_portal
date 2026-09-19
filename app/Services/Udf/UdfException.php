<?php

namespace App\Services\Udf;

use RuntimeException;
use Throwable;

class UdfException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $reason = 'udf_error',
        public readonly int $status = 422,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function conflict(): self
    {
        return new self(
            'Bu belge siz düzenlerken başka bir kullanıcı tarafından güncellendi. Lütfen son sürümü açıp değişikliklerinizi yeniden kontrol edin.',
            'version_conflict',
            409,
        );
    }
}
