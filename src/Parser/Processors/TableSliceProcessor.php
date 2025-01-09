<?php

namespace PBO\PbomlParser\Parser\Processors;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\BaseSliceProcessor;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\TableSliceValidator;

class TableSliceProcessor extends BaseSliceProcessor
{
    protected const VALID_TYPES = ['text', 'number', 'markdown'];

    protected const VALID_PRESENTATIONS = ['', 'figure', 'aside'];

    private TableSliceValidator $validator;

    public function process(array $slice): array
    {
        $this->validator = new TableSliceValidator();
        if (! $this->validator->validate($slice)) {
            throw new ValidationException(
                'Invalid table slice: ' . implode(', ', $this->validator->getErrorMessages()),
                $this->validator->getErrors()
            );
        }

        $variables = $this->processVariables($slice['variables']);
        $content   = $this->validateAndProcessContent($slice['content'], $variables);

        return [
            'type'           => 'table',
            'variables'      => $variables,
            'content'        => $content,
            'label'          => isset($slice['label'])
                ? $this->validator->processLocalizedField($slice['label'], 'label')
                : null,
            'display_label'  => $slice['display_label'] ?? true,
            'presentation'   => $this->validatePresentation($slice['presentation'] ?? ''),
            'sources'        => isset($slice['sources'])
                ? array_map(fn($source) => $this->validator->processLocalizedField($source, 'source'), $slice['sources'])
                : [],
            'notes'          => isset($slice['notes'])
                ? array_map(fn($note) => $this->validator->processLocalizedField($note, 'note'), $slice['notes'])
                : [],
            'referenced_as'  => isset($slice['referenced_as'])
                ? $this->validator->processLocalizedField($slice['referenced_as'], 'referenced_as')
                : null,

            'alts' => isset($slice['alts'])
                ? array_map(fn($alt) => $this->validator->processLocalizedField($alt, 'alt'), $slice['alts'])
                : [],
        ];
    }

    protected function processVariables(array $variables): array
    {
        $processed = [];
        foreach ($variables as $key => $variable) {
            if (! isset($variable['label'])) {
                throw new ValidationException(
                    "Missing label for variable: {$key}",
                    ['variable' => $key]
                );
            }

            if (isset($variable['type']) && ! in_array($variable['type'], self::VALID_TYPES)) {
                throw new ValidationException(
                    "Invalid variable type: {$variable['type']}",
                    ['valid_types' => self::VALID_TYPES]
                );
            }

            $processed[$key] = [
                'label'         => $this->validator->processLocalizedField($variable['label'], "variables.{$key}.label"),
                'type'          => $variable['type'] ?? 'text',
                'chart_type'    => $variable['chart_type'] ?? 'line',
                'is_descriptive' => $variable['is_descriptive'] ?? false,
                'skip_chart'    => $variable['skip_chart'] ?? false,
                'display_label' => $variable['display_label'] ?? true,
                'emphasize'     => $variable['emphasize'] ?? false,
                'is_time'       => $variable['is_time'] ?? false,
                'unit'          => isset($variable['unit'])
                    ? $this->validator->processLocalizedField($variable['unit'], "variables.{$key}.unit")
                    : null,
                'group'         => isset($variable['group'])
                    ? $this->validator->processLocalizedField($variable['group'], "variables.{$key}.group")
                    : null,
                'readonly'      => $variable['readonly'] ?? false,
            ];
        }

        return $processed;
    }

    protected function validateAndProcessContent(array $content, array $variables): array
    {
        foreach ($content as $index => $row) {
            foreach ($variables as $key => $variable) {
                if (! isset($row[$key])) {
                    continue;
                }

                $value = $row[$key];

                if ($variable['type'] === 'number') {
                    if (! is_null($value) && ! is_numeric($value)) {
                        throw new ValidationException(
                            "Row {$index}: Value for '{$key}' must be numeric or null",
                            ['row' => $row, 'field' => $key, 'value' => $value]
                        );
                    }
                }
                elseif ($variable['type'] === 'text' || $variable['type'] === 'markdown') {
                    if (is_array($value)) {
                        if (! isset($value['en']) || ! isset($value['fr'])) {
                            throw new ValidationException(
                                "Row {$index}: Localized content for '{$key}' must have both 'en' and 'fr' translations",
                                ['row' => $row, 'field' => $key, 'value' => $value]
                            );
                        }
                    } elseif (! is_string($value)) {
                        throw new ValidationException(
                            "Row {$index}: Value for '{$key}' must be string or localized content",
                            ['row' => $row, 'field' => $key, 'value' => $value]
                        );
                    }
                }
            }
        }

        return $content;
    }

    protected function validatePresentation(?string $presentation): ?string
    {
        if ($presentation === null) {
            return null;
        }

        if (! in_array($presentation, self::VALID_PRESENTATIONS)) {
            throw new ValidationException(
                "Invalid presentation value: {$presentation}",
                ['valid_presentations' => self::VALID_PRESENTATIONS]
            );
        }

        return $presentation;
    }
}
