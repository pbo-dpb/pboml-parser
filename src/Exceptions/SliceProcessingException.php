<?php

namespace PBO\PbomlParser\Exceptions;

use Exception;
use Throwable;

class SliceProcessingException extends Exception
{
    protected ?array $slice = null;

    protected ?string $sliceType = null;

    protected ?string $processingStage = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?array $slice = null,
        ?string $sliceType = null,
        ?string $processingStage = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->slice = $slice;
        $this->sliceType = $sliceType;
        $this->processingStage = $processingStage;
    }

    public static function unsupportedType(string $type, ?array $slice = null): self
    {
        return new static(
            "Unsupported slice type: {$type}",
            1,
            null,
            $slice,
            $type,
            'type-validation'
        );
    }

    public static function missingFields(array $fields, string $type, ?array $slice = null): self
    {
        return new static(
            "Missing required fields for slice type '{$type}': ".implode(', ', $fields),
            2,
            null,
            $slice,
            $type,
            'field-validation'
        );
    }

    public static function invalidFieldContent(string $field, string $type, string $reason, ?array $slice = null): self
    {
        return new static(
            "Invalid content in field '{$field}' for slice type '{$type}': {$reason}",
            3,
            null,
            $slice,
            $type,
            'content-validation'
        );
    }

    public static function processingFailed(string $type, string $stage, string $reason, ?array $slice = null): self
    {
        return new static(
            "Failed to process slice type '{$type}' at stage '{$stage}': {$reason}",
            4,
            null,
            $slice,
            $type,
            $stage
        );
    }

    public static function missingLocalization(string $type, string $locale, ?array $slice = null): self
    {
        return new static(
            "Missing localization for slice type '{$type}' in locale '{$locale}'",
            5,
            null,
            $slice,
            $type,
            'localization'
        );
    }

    public static function invalidResource(string $type, string $resource, ?array $slice = null): self
    {
        return new static(
            "Invalid resource reference '{$resource}' in slice type '{$type}'",
            6,
            null,
            $slice,
            $type,
            'resource-validation'
        );
    }

    public function getSlice(): ?array
    {
        return $this->slice;
    }

    public function getSliceType(): ?string
    {
        return $this->sliceType;
    }

    public function getProcessingStage(): ?string
    {
        return $this->processingStage;
    }

    public function getFormattedMessage(): string
    {
        $message = $this->getMessage();

        if ($this->sliceType) {
            $message .= "\nSlice Type: {$this->sliceType}";
        }

        if ($this->processingStage) {
            $message .= "\nProcessing Stage: {$this->processingStage}";
        }

        if ($this->slice) {
            $message .= "\nSlice Content: ".json_encode($this->slice, JSON_PRETTY_PRINT);
        }

        return $message;
    }

    public function occurredAtStage(string $stage): bool
    {
        return $this->processingStage === $stage;
    }

    public function isForType(string $type): bool
    {
        return $this->sliceType === $type;
    }

    public function __toString(): string
    {
        return $this->getFormattedMessage();
    }
}
