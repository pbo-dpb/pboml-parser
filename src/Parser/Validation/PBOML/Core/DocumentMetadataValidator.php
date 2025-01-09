<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Core;

use PBO\PbomlParser\Parser\Validation\Base\BaseValidator;
use PBO\PbomlParser\Parser\Validation\Base\ValidatorInterface;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesLocalization;

class DocumentMetadataValidator extends BaseValidator implements ValidatorInterface
{
    use ValidatesLocalization;

    protected bool $strictMode = false;


    public function setStrictMode(bool $strict): self
    {
        $this->strictMode = $strict;

        return $this;
    }

    public function isStrictMode(): bool
    {
        return $this->strictMode;
    }

    protected const STRICT_REQUIRED_FIELDS = ['id', 'title', 'type', 'release_date'];

    protected const OPTIONAL_FIELDS = ['copyright', 'form'];

    public function validate(array $data): bool
    {
        $this->clearErrors();

        if ($this->isStrictMode() && ! $this->validateStrictRequiredFields($data)) {
            return false;
        }

        if (isset($data['id']) && ! $this->validateDocumentId($data['id'])) {
            return false;
        }

        $localizedFields = ['title', 'type', 'copyright'];
        foreach ($localizedFields as $field) {
            if (isset($data[$field]) && ! $this->validateLocalizedField($data[$field], $field)) {
                return false;
            }
        }

        if (isset($data['release_date']) && ! $this->validateReleaseDate($data['release_date'])) {
            return false;
        }

        if (isset($data['form']) && ! $this->validateForm($data['form'])) {
            return false;
        }

        if (! $this->validateNoUnknownFields($data)) {
            return false;
        }

        return true;
    }

    protected function validateStrictRequiredFields(array $data): bool
    {
        $missing = [];
        foreach (self::STRICT_REQUIRED_FIELDS as $field) {
            if (! isset($data[$field])) {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            $this->addError('Missing required metadata fields in strict mode', [
                'missing_fields' => $missing,
                'required_fields' => self::STRICT_REQUIRED_FIELDS,
            ]);

            return false;
        }

        return true;
    }

    protected function validateDocumentId(string $id): bool
    {
        if (! preg_match('/^[A-Z0-9-]+$/', $id)) {
            $this->addError('Invalid document ID format', [
                'id' => $id,
                'expected_format' => 'uppercase alphanumeric with hyphens',
            ]);

            return false;
        }

        return true;
    }

    protected function validateReleaseDate(string $date): bool
    {
        if (! strtotime($date)) {
            $this->addError('Invalid release date format', [
                'date' => $date,
                'expected_format' => 'ISO8601',
            ]);

            return false;
        }

        if ($this->isStrictMode() && strtotime($date) > time()) {
            $this->addError('Release date cannot be in the future in strict mode', [
                'date' => $date,
            ]);

            return false;
        }

        return true;
    }

    protected function validateForm(string $form): bool
    {
        if (! preg_match('/^T-[A-Z]{3}-\d+\.\d+\.\d+$/', $form)) {
            $this->addError('Invalid form format', [
                'form' => $form,
                'expected_format' => 'T-XXX-Y.Y.Y',
            ]);

            return false;
        }

        return true;
    }

    protected function validateNoUnknownFields(array $data): bool
    {
        $allowedFields = array_merge(
            self::STRICT_REQUIRED_FIELDS,
            self::OPTIONAL_FIELDS
        );

        $unknownFields = array_diff(array_keys($data), $allowedFields);

        if (! empty($unknownFields)) {
            $this->addError('Unknown metadata fields found', [
                'unknown_fields' => $unknownFields,
                'allowed_fields' => $allowedFields,
            ]);

            return false;
        }

        return true;
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
}
