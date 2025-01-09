<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Slices;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\Validation\Base\BaseValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\Contracts\SliceValidatorInterface;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesLocalization;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesPresentation;

class TableSliceValidator extends BaseValidator implements SliceValidatorInterface
{
    use ValidatesLocalization, ValidatesPresentation;

    protected bool $strictMode = false;

    public function getSliceType(): string
    {
        return 'table';
    }

    public function validate(array $slice): bool
    {
        $this->clearErrors();

        if (! $this->validateRequiredFields($slice)) {
            return false;
        }

        if (! $this->validateVariables($slice['variables'])) {
            return false;
        }

        if (! $this->validateContent($slice['content'], $slice['variables'])) {
            return false;
        }

        if (isset($slice['presentation']) && ! $this->validatePresentation($slice, 'presentation')) {
            return false;
        }

        return true;
    }

    protected function validateRequiredFields(array $slice): bool
    {
        $requiredFields = ['type', 'variables', 'content'];
        foreach ($requiredFields as $field) {
            if (! isset($slice[$field])) {
                $this->addError("The '{$field}' field is required for table slices.");

                return false;
            }
        }

        return true;
    }

    protected function validateVariables(array $variables): bool
    {
        foreach ($variables as $key => $variable) {
            if (! isset($variable['label']) || ! $this->validateLocalizedContent($variable['label'], "variables.{$key}.label")) {
                $this->addError("The 'label' is required and must be localized for variable '{$key}'.");

                return false;
            }

            $validTypes = ['text', 'number', 'markdown'];
            if (isset($variable['type']) && ! in_array($variable['type'], $validTypes, true)) {
                $this->addError("The 'type' for variable '{$key}' is invalid. Expected one of: " . implode(', ', $validTypes) . '.');

                return false;
            }

            if (isset($variable['unit'])) {
                if (! $this->validateLocalizedContent($variable['unit'], "variables.{$key}.unit")) {
                    $this->addError("The 'unit' for variable '{$key}' must be localized.");

                    return false;
                }
            }
        }

        return true;
    }

    protected function validateContent(array $content, array $variables): bool
    {
        if (empty($variables)) {
            $this->addError('Variables array is required and cannot be empty.');

            return false;
        }

        $variableKeys = array_keys($variables);
        foreach ($content as $rowIndex => $row) {
            $missingKeys = array_diff($variableKeys, array_keys($row));
            if (! empty($missingKeys)) {
                $this->addError("Missing variable keys in row {$rowIndex}: " . implode(', ', $missingKeys));

                return false;
            }

            $unexpectedKeys = array_diff(array_keys($row), $variableKeys);
            if (! empty($unexpectedKeys)) {
                $this->addError("Unexpected keys in row {$rowIndex}: " . implode(', ', $unexpectedKeys));

                return false;
            }

            foreach ($row as $key => $value) {
                if (! isset($variables[$key])) {
                    continue;
                }

                $type = $variables[$key]['type'] ?? 'text';
                if ($type === 'number' && ! (is_numeric($value) || is_null($value))) {
                    $this->addError("Invalid value for '{$key}' in row {$rowIndex}. Expected number or null.");

                    return false;
                }

                if ($type === 'text' || $type === 'markdown') {
                    if (is_array($value)) {
                        if (! $this->validateLocalizedContent($value, "content.{$rowIndex}.{$key}")) {
                            $this->addError("Invalid localized content for '{$key}' in row {$rowIndex}.");

                            return false;
                        }
                    } elseif (! is_string($value)) {
                        $this->addError("Invalid value for '{$key}' in row {$rowIndex}. Expected {$type}.");

                        return false;
                    }
                }
            }
        }

        return true;
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
