<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Slices;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\Validation\Base\BaseValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\Contracts\SliceValidatorInterface;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesLocalization;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesPresentation;

class KeyValueSliceValidator extends BaseValidator implements SliceValidatorInterface
{
    use ValidatesLocalization, ValidatesPresentation;

    protected bool $strictMode = false;

    protected const VALID_TYPES = ['markdown'];

    public function getSliceType(): string
    {
        return 'kvlist';
    }

    public function validate(array $slice): bool
    {
        $this->clearErrors();

        if (! $this->validateRequiredFields($slice)) {
            return false;
        }

        if (! $this->validatePrototype($slice['prototype'])) {
            return false;
        }

        if (! $this->validateContent($slice['content'], $slice['prototype'])) {
            return false;
        }

        if (! $this->validatePresentation($slice, 'presentation')) {
            return false;
        }

        return true;
    }

    protected function validateRequiredFields(array $slice): bool
    {
        $requiredFields = ['type', 'prototype', 'content'];
        foreach ($requiredFields as $field) {
            if (! isset($slice[$field])) {
                $this->addError("Missing required field: {$field}", [
                    'field' => $field,
                    'slice' => $slice,
                ]);

                return false;
            }
        }

        return true;
    }

    protected function validatePrototype(array $prototype): bool
    {
        if (! isset($prototype['key']) || ! isset($prototype['value'])) {
            $this->addError('Prototype must contain both key and value definitions');

            return false;
        }

        foreach (['key', 'value'] as $field) {
            if (! $this->validatePrototypeField($prototype[$field], $field)) {
                return false;
            }
        }

        return true;
    }

    protected function validatePrototypeField(array $field, string $fieldName): bool
    {
        if (! isset($field['type']) || ! in_array($field['type'], self::VALID_TYPES)) {
            $this->addError("Invalid type in prototype {$fieldName}", [
                'field' => $fieldName,
                'type' => $field['type'] ?? null,
                'valid_types' => self::VALID_TYPES,
            ]);

            return false;
        }

        if (! isset($field['label']) || ! $this->validateLocalizedContent($field['label'], "prototype.{$fieldName}.label")) {
            return false;
        }

        return true;
    }

    protected function validateContent(array $content, array $prototype): bool
    {
        foreach ($content as $index => $item) {
            if (! isset($item['key']) || ! isset($item['value'])) {
                $this->addError('Missing key or value in content item', [
                    'index' => $index,
                ]);

                return false;
            }

            if (! $this->validateContentItem($item['key'], $prototype['key'], "content.{$index}.key")) {
                return false;
            }

            if (! $this->validateContentItem($item['value'], $prototype['value'], "content.{$index}.value")) {
                return false;
            }
        }

        return true;
    }

    protected function validateContentItem(array $item, array $prototype, string $path): bool
    {
        if (! isset($item['content'])) {
            $this->addError("Missing content in {$path}");

            return false;
        }

        return $this->validateLocalizedContent($item['content'], $path);
    }

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

    public function processLocalizedField(array $content, string $field): array
    {
        if (! $this->validateLocalizedContent($content, $field)) {
            throw new ValidationException(
                "Invalid localized content for field: {$field}",
                ['field' => $field, 'content' => $content]
            );
        }

        return [
            'en' => $content['en'],
            'fr' => $content['fr'],
        ];
    }
}
