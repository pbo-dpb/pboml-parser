<?php

namespace PBO\PbomlParser\Exceptions;

use Exception;
use Throwable;

class PBOMLParseException extends Exception
{
    protected ?array $validationErrors = null;

    protected ?string $documentId = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?array $validationErrors = null,
        ?string $documentId = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->validationErrors = $validationErrors;
        $this->documentId = $documentId;
    }

    public static function invalidStructure(array $errors, ?string $documentId = null): self
    {
        return new static(
            'Invalid PBOML document structure: '.implode('; ', $errors),
            1,
            null,
            $errors,
            $documentId
        );
    }

    public static function invalidYaml(string $message, ?string $documentId = null): self
    {
        return new static(
            "Invalid YAML syntax: {$message}",
            2,
            null,
            null,
            $documentId
        );
    }

    public static function unsupportedVersion(string $version, ?string $documentId = null): self
    {
        return new static(
            "Unsupported PBOML version: {$version}",
            3,
            null,
            null,
            $documentId
        );
    }

    public static function invalidSliceType(string $type, ?string $documentId = null): self
    {
        return new static(
            "Invalid slice type: {$type}",
            4,
            null,
            null,
            $documentId
        );
    }

    public static function missingRequiredFields(array $fields, ?string $documentId = null): self
    {
        return new static(
            'Missing required fields: '.implode(', ', $fields),
            5,
            null,
            $fields,
            $documentId
        );
    }

    public static function invalidContentFormat(string $message, ?string $documentId = null): self
    {
        return new static(
            "Invalid content format: {$message}",
            6,
            null,
            null,
            $documentId
        );
    }

    public function getValidationErrors(): ?array
    {
        return $this->validationErrors;
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function hasValidationErrors(): bool
    {
        return ! empty($this->validationErrors);
    }

    public function getFormattedMessage(): string
    {
        $message = $this->getMessage();

        if ($this->documentId) {
            $message .= " (Document ID: {$this->documentId})";
        }

        if ($this->hasValidationErrors()) {
            $message .= "\nValidation Errors:\n- ".implode("\n- ", $this->validationErrors);
        }

        return $message;
    }

    public function __toString(): string
    {
        return $this->getFormattedMessage();
    }
}
