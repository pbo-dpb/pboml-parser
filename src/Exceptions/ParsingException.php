<?php

namespace PBO\PbomlParser\Exceptions;

use Throwable;

class ParsingException extends PBOMLException
{
    public function __construct(
        string $message,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            'PBOML-PARSE-001',
            'error',
            $context,
            $previous
        );
    }
}
