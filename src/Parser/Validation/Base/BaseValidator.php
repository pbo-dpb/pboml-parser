<?php

namespace PBO\PbomlParser\Parser\Validation\Base;

abstract class BaseValidator implements ValidatorInterface
{
    protected bool $strictMode = false;

    protected array $errors = [];

    public function setStrictMode(bool $strict): self
    {
        $this->strictMode = $strict;

        return $this;
    }

    public function isStrictMode(): bool
    {
        return $this->strictMode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    public function clearErrors(): void
    {
        $this->errors = [];
    }

    public function getErrorMessages(): array
    {
        return array_map(function ($error) {
            return $error['message'];
        }, $this->errors);
    }

    public function getErrorContexts(): array
    {
        return array_map(function ($error) {
            return $error['context'] ?? [];
        }, $this->errors);
    }

    protected function addError(string $message, array $context = []): void
    {
        $this->errors[] = [
            'message' => $message,
            'context' => $context,
        ];
    }

    abstract public function validate(array $data): bool;
}
