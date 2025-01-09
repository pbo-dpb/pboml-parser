<?php

namespace PBO\PbomlParser\Exceptions;

class ValidationException extends PBOMLException
{
    protected array $validationErrors;

    public function __construct(
        string $message,
        array $validationErrors = [],
        ?\Throwable $previous = null
    ) {
        $this->validationErrors = $validationErrors;
        parent::__construct(
            $message,
            'PBOML-VAL-001',
            'error',
            ['validation_errors' => $validationErrors],
            $previous
        );
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function hasValidationErrors(): bool
    {
        return ! empty($this->validationErrors);
    }

    public function getValidationError(string $key)
    {
        return $this->validationErrors[$key] ?? null;
    }

    public function getErrorMessages(): array
    {
        return array_map(function ($error) {
            return $error['message'] ?? $error;
        }, $this->validationErrors);
    }

    public function getFormattedMessage(): string
    {
        $message = $this->getMessage();
        if ($this->hasValidationErrors()) {
            $message .= "\nValidation Errors:\n";
            foreach ($this->getErrorMessages() as $error) {
                $message .= "- {$error}\n";
            }
        }

        return $message;
    }

    public function __toString(): string
    {
        return $this->getFormattedMessage();
    }
}
