<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Slices;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\Validation\Base\BaseValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\Contracts\SliceValidatorInterface;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesLocalization;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesPresentation;

class ChartSliceValidator extends BaseValidator implements SliceValidatorInterface
{
    use ValidatesLocalization, ValidatesPresentation;

    protected bool $strictMode = false;

    protected const VALID_VARIABLE_TYPES = ['markdown', 'number'];

    protected const VALID_CHART_TYPES = ['bar', 'line', 'scatter'];

    public function getSliceType(): string
    {
        return 'chart';
    }

    public function validate(array $slice): bool
    {
        $this->clearErrors();

        if (! $this->validateRequiredFields($slice)) {
            return false;
        }

        if (isset($slice['datatable'])) {
            if (! $this->validateDataTable($slice['datatable'])) {
                return false;
            }
        } elseif (isset($slice['arraytable'])) {
            if (! $this->validateArrayTable($slice['arraytable'])) {
                return false;
            }
        } else {
            $this->addError('Chart must contain either datatable or arraytable');

            return false;
        }

        if (! $this->validatePresentation($slice, 'presentation')) {
            return false;
        }

        return true;
    }

    protected function validateRequiredFields(array $slice): bool
    {
        $requiredFields = ['type'];
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

    protected function validateDataTable(array $datatable): bool
    {
        if (! isset($datatable['variables']) || ! isset($datatable['content'])) {
            $this->addError('Datatable must contain variables and content');

            return false;
        }

        if (! $this->validateVariables($datatable['variables'])) {
            return false;
        }

        if (! $this->validateTableContent($datatable['content'], array_keys($datatable['variables']))) {
            return false;
        }

        return true;
    }

    protected function validateVariables(array $variables): bool
    {
        foreach ($variables as $key => $variable) {
            if (! isset($variable['label'])) {
                $this->addError("Missing required label for variable: {$key}");

                return false;
            }

            if (! isset($variable['type'])) {
                $this->addError("Missing required type for variable: {$key}");

                return false;
            }

            if (! $this->validateLocalizedContent($variable['label'], "variables.{$key}.label")) {
                return false;
            }

            if (! in_array($variable['type'], self::VALID_VARIABLE_TYPES)) {
                $this->addError("Invalid variable type for: {$key}", [
                    'type' => $variable['type'],
                    'valid_types' => self::VALID_VARIABLE_TYPES,
                ]);

                return false;
            }

            if (isset($variable['display_label']) && ! is_bool($variable['display_label'])) {
                $this->addError("display_label must be boolean for variable: {$key}");

                return false;
            }

            if (isset($variable['is_descriptive']) && ! is_bool($variable['is_descriptive'])) {
                $this->addError("is_descriptive must be boolean for variable: {$key}");

                return false;
            }

            if (isset($variable['is_time']) && ! is_bool($variable['is_time'])) {
                $this->addError("is_time must be boolean for variable: {$key}");

                return false;
            }

            if (isset($variable['skip_chart']) && ! is_bool($variable['skip_chart'])) {
                $this->addError("skip_chart must be boolean for variable: {$key}");

                return false;
            }

            if (isset($variable['chart_type']) && ! in_array($variable['chart_type'], self::VALID_CHART_TYPES)) {
                $this->addError("Invalid chart type for: {$key}", [
                    'chart_type' => $variable['chart_type'],
                    'valid_types' => self::VALID_CHART_TYPES,
                ]);

                return false;
            }

            if (isset($variable['unit'])) {
                if (! $this->validateLocalizedContent($variable['unit'], "variables.{$key}.unit")) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function validateTableContent(array $content, array $variableKeys): bool
    {
        foreach ($content as $rowIndex => $row) {
            foreach ($variableKeys as $key) {
                if (! array_key_exists($key, $row)) {
                    $this->addError('Missing variable in content row', [
                        'row' => $rowIndex,
                        'missing_key' => $key,
                    ]);

                    return false;
                }
            }
        }

        return true;
    }

    protected function validateArrayTable(array $arraytable): bool
    {
        if (! isset($arraytable['chart_type']) || ! in_array($arraytable['chart_type'], self::VALID_CHART_TYPES)) {
            $this->addError('Invalid or missing chart type in arraytable', [
                'valid_types' => self::VALID_CHART_TYPES,
            ]);

            return false;
        }

        if (! isset($arraytable['arraytable']) || ! is_array($arraytable['arraytable'])) {
            $this->addError('Missing or invalid arraytable data');

            return false;
        }

        if (empty($arraytable['arraytable'])) {
            $this->addError('Arraytable cannot be empty');

            return false;
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
            $context = isset($error['context']) ? json_encode($error['context']) : '';

            return $error['message'].($context ? " Context: {$context}" : '');
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
