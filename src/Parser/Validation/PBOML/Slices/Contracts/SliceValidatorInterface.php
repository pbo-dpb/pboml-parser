<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Slices\Contracts;

use PBO\PbomlParser\Parser\Validation\Base\ValidatorInterface;

interface SliceValidatorInterface extends ValidatorInterface
{
    public function validate(array $slice): bool;

    public function getSliceType(): string;

    public function setStrictMode(bool $strict): self;

    public function isStrictMode(): bool;

    public function getErrors(): array;

    public function hasErrors(): bool;

    public function clearErrors(): void;

    public function getErrorMessages(): array;

    public function getErrorContexts(): array;
}
