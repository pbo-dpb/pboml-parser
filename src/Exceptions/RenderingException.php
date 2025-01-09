<?php

namespace PBO\PbomlParser\Exceptions;

use Throwable;

class RenderingException extends PBOMLException
{
    public function __construct(
        string $message,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            'PBOML-REND-001',
            'error',
            $context,
            $previous
        );
    }
}
