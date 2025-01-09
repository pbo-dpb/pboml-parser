<?php

namespace PBO\PbomlParser\Exceptions;

class EncodingException extends PBOMLException
{
    protected array $context = [];

    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
