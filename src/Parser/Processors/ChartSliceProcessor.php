<?php

namespace PBO\PbomlParser\Parser\Processors;

use Exception;
use PBO\PbomlParser\Exceptions\SliceProcessingException;
use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\BaseSliceProcessor;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\ChartSliceValidator;

class ChartSliceProcessor extends BaseSliceProcessor
{
    private ChartSliceValidator $validator;

    public function process(array $slice): array
    {
        $this->validator = new ChartSliceValidator;
        if (! $this->validator->validate($slice)) {
            throw new ValidationException(
                'Invalid chart slice: '.implode(', ', $this->validator->getErrorMessages()),
                $this->validator->getErrors()
            );
        }

        try {
            if (isset($slice['datatable'])) {
                return $this->processDataTableChart($slice);
            } else {
                return $this->processArrayTableChart($slice);
            }
        } catch (Exception $e) {
            throw new SliceProcessingException(
                'Failed to process chart: '.$e->getMessage(),
                0,
                $e,
                $slice,
                'chart',
                'processing'
            );
        }
    }

    protected function processDataTableChart(array $slice): array
    {
        $variables = $this->processChartVariables($slice['datatable']['variables']);
        $content = $slice['datatable']['content'];

        return [
            'type' => 'chart',
            'datatable' => [
                'variables' => $variables,
                'content' => $content,
            ],
            'label' => $slice['label'] ?? null,
            'display_label' => $slice['display_label'] ?? true,
            'presentation' => $slice['presentation'] ?? null,
            'sources' => $slice['sources'] ?? [],
            'notes' => $slice['notes'] ?? [],
            'referenced_as' => $slice['referenced_as'] ?? null,
        ];
    }

    protected function processChartVariables(array $variables): array
    {
        $processed = [];
        foreach ($variables as $key => $variable) {
            $processed[$key] = [
                'label' => $this->validator->processLocalizedField($variable['label'], 'label'),
            ];

            if (isset($variable['type'])) {
                $processed[$key]['type'] = $variable['type'];
            }

            if (isset($variable['chart_type'])) {
                $processed[$key]['chart_type'] = $variable['chart_type'];
            }

            if (isset($variable['emphasis'])) {
                $processed[$key]['emphasis'] = $variable['emphasis'];
            }

            if (isset($variable['is_descriptive'])) {
                $processed[$key]['is_descriptive'] = $variable['is_descriptive'];
            }

            if (isset($variable['skip_chart'])) {
                $processed[$key]['skip_chart'] = $variable['skip_chart'];
            }

            if (isset($variable['display_label'])) {
                $processed[$key]['display_label'] = $variable['display_label'];
            }

            if (isset($variable['tension'])) {
                $processed[$key]['tension'] = $variable['tension'];
            }

            if (isset($variable['is_time'])) {
                $processed[$key]['is_time'] = $variable['is_time'];
            }

            if (isset($variable['unit'])) {
                $processed[$key]['unit'] = $this->validator->processLocalizedField($variable['unit'], 'unit');
            }

            if (isset($variable['group'])) {
                $processed[$key]['group'] = $this->validator->processLocalizedField($variable['group'], 'group');
            }
        }

        return $processed;
    }

    protected function processArrayTableChart(array $slice): array
    {
        return [
            'type' => 'chart',
            'arraytable' => [
                'chart_type' => $slice['arraytable']['chart_type'],
                'axes' => $slice['arraytable']['axes'] ?? [],
                'arraytable' => $slice['arraytable']['arraytable'],
                'strings' => $this->processLocalizedStrings($slice['arraytable']['strings'] ?? []),
            ],
            'label' => $slice['label'] ?? null,
            'display_label' => $slice['display_label'] ?? true,
            'presentation' => $slice['presentation'] ?? null,
            'sources' => $slice['sources'] ?? [],
            'notes' => $slice['notes'] ?? [],
            'referenced_as' => $slice['referenced_as'] ?? null,
        ];
    }

    protected function processLocalizedStrings(array $strings): array
    {
        return [
            'en' => $strings['en'] ?? [],
            'fr' => $strings['fr'] ?? [],
        ];
    }
}
