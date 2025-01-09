<?php

namespace PBO\PbomlParser\Exceptions;

use Throwable;

class CacheException extends PBOMLException
{
    public function __construct(
        string $message,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            'PBOML-CACHE-001',
            'error',
            $context,
            $previous
        );
    }
}
