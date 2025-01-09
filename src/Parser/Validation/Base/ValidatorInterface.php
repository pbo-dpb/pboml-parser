<?php

namespace PBO\PbomlParser\Parser\Validation\Base;

interface ValidatorInterface
{
    public function validate(array $data): bool;

    public function setStrictMode(bool $strict): self;

    public function isStrictMode(): bool;

    public function getErrors(): array;

    public function hasErrors(): bool;

    public function clearErrors(): void;

    public function getErrorMessages(): array;

    public function getErrorContexts(): array;
}
