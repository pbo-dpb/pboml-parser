<?php

namespace PBO\PbomlParser\Generator\Renderers;

use PBO\PbomlParser\Traits\WithAccessibility;
use PBO\PbomlParser\Traits\WithLayout;
use PBO\PbomlParser\Traits\WithStyles;
use PBO\PbomlParser\Generator\Utils\AttributeBuilder;
use PBO\PbomlParser\Generator\Utils\ClassBuilder;

class ChartRenderer implements SliceRenderer
{
    use WithAccessibility, WithLayout, WithStyles;

    protected string $locale;
    protected int $width = 800;
    protected int $height = 400;
    protected int $padding = 50;

    public function __construct(?string $locale = null)
    {
        if ($locale !== null) {
            $this->locale = $locale;
        }
    }

    public function render(array $slice): string
    {
        $data = $this->processChartData($slice);
        $svg = $this->generateSVG($data, $slice);

        $content = $this->renderChartContainer($svg);

        if (! empty($slice['sources'])) {
            $content .= $this->renderSources($slice['sources']);
        }

        if (! empty($slice['notes'])) {
            $content .= $this->renderNotes($slice['notes']);
        }

        if (($slice['presentation'] ?? '') === 'figure') {
            return $this->renderAsFigure($content, $slice);
        }

        return $this->withLayout($content, 'chart', [
            'id' => $slice['id'] ?? null,
            'class' => $this->getSectionClasses($slice),
        ]);
    }

    protected function generateSVG(array $data, array $slice): string
    {
        $chartArea = [
            'x' => $this->padding,
            'y' => $this->padding,
            'width' => $this->width - (2 * $this->padding),
            'height' => $this->height - (2 * $this->padding)
        ];

        // Find data ranges
        $xValues = array_keys($data['datasets'][0]['data'] ?? []);
        $yValues = array_reduce($data['datasets'], function ($carry, $dataset) {
            return array_merge($carry, array_filter($dataset['data'], 'is_numeric'));
        }, []);

        $minY = min($yValues) ?? 0;
        $maxY = max($yValues) ?? 100;
        $padding = ($maxY - $minY) * 0.1;
        $minY = max(0, $minY - $padding);
        $maxY = $maxY + $padding;

        $svg = sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d">', $this->width, $this->height);

        if (isset($slice['label'][$this->locale])) {
            $svg .= sprintf('<title>%s</title>', htmlspecialchars($slice['label'][$this->locale]));
        }

        $svg .= $this->drawAxes($chartArea, $minY, $maxY, $data['labels'] ?? []);

        foreach ($data['datasets'] as $dataset) {
            $svg .= $this->drawDataset($dataset, $chartArea, $minY, $maxY);
        }

        $svg .= $this->drawLegend($data['datasets'], $chartArea);
        $svg .= '</svg>';

        return $svg;
    }

    protected function drawAxes(array $area, float $minY, float $maxY, array $labels): string
    {
        $svg = '';

        // Y-axis line
        $svg .= sprintf(
            '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="currentColor" stroke-width="1"/>',
            $area['x'],
            $area['y'],
            $area['x'],
            $area['y'] + $area['height']
        );

        // X-axis line
        $svg .= sprintf(
            '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="currentColor" stroke-width="1"/>',
            $area['x'],
            $area['y'] + $area['height'],
            $area['x'] + $area['width'],
            $area['y'] + $area['height']
        );

        // Y-axis ticks and labels
        $numTicks = 5;
        $step = ($maxY - $minY) / ($numTicks - 1);
        for ($i = 0; $i < $numTicks; $i++) {
            $value = $maxY - ($i * $step);
            $y = $this->mapY($value, $minY, $maxY, $area);

            // Tick mark
            $svg .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="currentColor" stroke-width="1"/>',
                $area['x'] - 5,
                $y,
                $area['x'],
                $y
            );

            // Label
            $svg .= sprintf(
                '<text x="%d" y="%d" text-anchor="end" dominant-baseline="middle" font-size="12" fill="currentColor">%s</text>',
                $area['x'] - 10,
                $y,
                number_format($value, is_int($value) ? 0 : 1)
            );
        }

        // X-axis labels
        $numLabels = count($labels);
        if ($numLabels > 0) {
            if ($numLabels === 1) {
                // For single label, center it
                $x = $area['x'] + ($area['width'] / 2);
                $svg .= sprintf(
                    '<text x="%d" y="%d" text-anchor="middle" font-size="12" fill="currentColor">%s</text>',
                    $x,
                    $area['y'] + $area['height'] + 20,
                    htmlspecialchars($labels[0])
                );
            } else {
                // For multiple labels
                $step = $area['width'] / ($numLabels - 1);
                foreach ($labels as $i => $label) {
                    $x = $area['x'] + ($i * $step);
                    $svg .= sprintf(
                        '<text x="%d" y="%d" text-anchor="middle" font-size="12" fill="currentColor">%s</text>',
                        $x,
                        $area['y'] + $area['height'] + 20,
                        htmlspecialchars($label)
                    );
                }
            }
        }

        return $svg;
    }

    protected function drawDataset(array $dataset, array $area, float $minY, float $maxY): string
    {
        $data = array_filter($dataset['data'], 'is_numeric');
        if (empty($data)) {
            return '';
        }

        $numPoints = count($data);
        $step = $area['width'] / ($numPoints - 1);
        $points = [];

        foreach ($data as $i => $value) {
            $x = $area['x'] + ($i * $step);
            $y = $this->mapY($value, $minY, $maxY, $area);
            $points[] = "{$x},{$y}";
        }

        $color = $dataset['borderColor'] ?? 'currentColor';
        $svg = '';

        if (($dataset['type'] ?? 'line') === 'line') {
            if ($dataset['fill'] ?? false) {
                $fillPoints = array_merge(
                    $points,
                    [
                        $area['x'] + $area['width'] . ',' . ($area['y'] + $area['height']),
                        $area['x'] . ',' . ($area['y'] + $area['height'])
                    ]
                );
                $svg .= sprintf(
                    '<polygon points="%s" fill="%s" opacity="0.1"/>',
                    implode(' ', $fillPoints),
                    $color
                );
            }

            $svg .= sprintf(
                '<polyline points="%s" fill="none" stroke="%s" stroke-width="%d"/>',
                implode(' ', $points),
                $color,
                $dataset['borderWidth'] ?? 2
            );
        }

        foreach ($points as $point) {
            list($x, $y) = explode(',', $point);
            $svg .= sprintf(
                '<circle cx="%s" cy="%s" r="%d" fill="%s"/>',
                $x,
                $y,
                $dataset['pointRadius'] ?? 4,
                $color
            );
        }

        return $svg;
    }

    protected function drawLegend(array $datasets, array $area): string
    {
        $svg = '';
        $y = $this->padding / 2;
        $x = $area['x'];

        foreach ($datasets as $dataset) {
            $color = $dataset['borderColor'] ?? 'currentColor';

            $svg .= sprintf(
                '<rect x="%d" y="%d" width="12" height="12" fill="%s"/>',
                $x,
                $y - 6,
                $color
            );

            $svg .= sprintf(
                '<text x="%d" y="%d" font-size="12" fill="currentColor">%s</text>',
                $x + 20,
                $y,
                htmlspecialchars($dataset['label'] ?? '')
            );

            $x += 150;
        }

        return $svg;
    }

    protected function mapY(float $value, float $minY, float $maxY, array $area): float
    {
        $ratio = ($value - $minY) / ($maxY - $minY);
        return $area['y'] + $area['height'] - ($ratio * $area['height']);
    }

    protected function renderChartContainer(string $svg): string
    {
        $containerClasses = ClassBuilder::make()
            ->add([
                'relative',
                'w-full',
                'h-auto',
                'chart-container',
            ])
            ->print([
                'print:h-auto',
            ])
            ->build();

        $attributes = AttributeBuilder::make()
            ->add('class', $containerClasses)
            ->build();

        return sprintf('<div %s>%s</div>', $attributes, $svg);
    }

    protected function processChartData(array $slice): array
    {
        if (isset($slice['arraytable'])) {
            return $this->processArrayTable($slice['arraytable']);
        }

        return $this->processDataTable($slice['datatable'] ?? []);
    }

    protected function processArrayTable(array $data): array
    {
        $labels = $data['arraytable'][0] ?? [];
        $datasets = array_slice($data['arraytable'], 1);

        if (isset($data['strings'][$this->locale])) {
            $labels = $this->localizeStrings($labels, $data['strings'][$this->locale]);
            $datasets = $this->localizeDatasets($datasets, $data['strings'][$this->locale]);
        }

        $processedDatasets = [];
        foreach ($datasets as $dataset) {
            $processedDatasets[] = [
                'label' => $dataset[0] ?? '',
                'data' => array_slice($dataset, 1),
                'type' => $data['chart_type'] ?? 'line'
            ];
        }

        foreach ($processedDatasets as &$dataset) {
            $dataset = $this->addDatasetStyling($dataset);
        }

        return [
            'labels' => array_slice($labels, 1),
            'datasets' => $processedDatasets,
        ];
    }

    protected function processDataTable(array $data): array
    {
        $variables = $data['variables'] ?? [];
        $content = $data['content'] ?? [];

        $processedData = [
            'labels' => [],
            'datasets' => [],
        ];

        foreach ($variables as $key => $variable) {
            if ($variable['skip_chart'] ?? false) {
                continue;
            }

            $dataset = [
                'label' => $variable['label'][$this->locale] ?? '',
                'data' => [],
                'type' => $variable['chart_type'] ?? 'line',
                'tension' => $variable['tension'] ?? 0,
                'emphasize' => $variable['emphasize'] ?? false,
            ];

            $dataset = $this->addDatasetStyling($dataset);
            $processedData['datasets'][] = $dataset;

            foreach ($content as $row) {
                if (empty($processedData['labels'])) {
                    $processedData['labels'][] = $this->getRowLabel($row);
                }
                $processedData['datasets'][count($processedData['datasets']) - 1]['data'][] = $row[$key] ?? null;
            }
        }

        return $processedData;
    }

    protected function addDatasetStyling(array $dataset): array
    {
        $colors = [
            'blue' => ['rgb(59, 130, 246)', 'rgba(59, 130, 246, 0.5)'],
            'red' => ['rgb(239, 68, 68)', 'rgba(239, 68, 68, 0.5)'],
            'green' => ['rgb(34, 197, 94)', 'rgba(34, 197, 94, 0.5)'],
            'yellow' => ['rgb(234, 179, 8)', 'rgba(234, 179, 8, 0.5)'],
            'purple' => ['rgb(168, 85, 247)', 'rgba(168, 85, 247, 0.5)'],
        ];

        $colorIndex = array_rand($colors);
        [$solid, $transparent] = $colors[$colorIndex];

        $dataset['borderColor'] = $solid;
        $dataset['backgroundColor'] = $transparent;
        $dataset['fill'] = true;

        if ($dataset['emphasize'] ?? false) {
            $dataset['borderWidth'] = 3;
            $dataset['zIndex'] = 10;
        }

        return $dataset;
    }

    protected function renderSources(array $sources): string
    {
        $containerClasses = ClassBuilder::make()
            ->add([
                'flex',
                'flex-col',
                'gap-1',
                'border-l-2',
                'border-gray-200',
                'pl-2',
                'mt-4',
            ])
            ->dark('border-gray-700')
            ->build();

        $html = sprintf('<dl class="%s">', $containerClasses);
        $html .= '<dt class="text-sm font-semibold">Sources</dt>';

        $contentClasses = ClassBuilder::make()
            ->add([
                'pboml-prose',
                'prose-sm',
                'prose-p:my-0',
            ])
            ->build();

        $html .= sprintf('<div class="%s">', $contentClasses);
        foreach ($sources as $source) {
            $html .= sprintf(
                '<p>%s</p>',
                htmlspecialchars($source[$this->locale])
            );
        }
        $html .= '</div></dl>';

        return $html;
    }

    protected function renderNotes(array $notes): string
    {
        $containerClasses = ClassBuilder::make()
            ->add([
                'flex',
                'flex-col',
                'gap-1',
                'border-l-2',
                'border-gray-200',
                'pl-2',
                'mt-2',
            ])
            ->dark('border-gray-700')
            ->build();

        $html = sprintf('<dl class="%s">', $containerClasses);
        $html .= '<dt class="text-sm font-semibold">Notes</dt>';

        $contentClasses = ClassBuilder::make()
            ->add([
                'pboml-prose',
                'prose-sm',
                'prose-p:my-0',
            ])
            ->build();

        $html .= sprintf('<div class="%s">', $contentClasses);
        foreach ($notes as $note) {
            $html .= sprintf(
                '<p>%s</p>',
                htmlspecialchars($note[$this->locale])
            );
        }
        $html .= '</div></dl>';

        return $html;
    }

    protected function renderAsFigure(string $content, array $slice): string
    {
        $figureClasses = ClassBuilder::make()
            ->add([
                'flex',
                'flex-col',
                'gap-4',
                'break-inside-avoid-page',
            ])
            ->build();

        $html = sprintf('<figure class="%s">', $figureClasses);
        $html .= $content;

        if (isset($slice['referenced_as'][$this->locale])) {
            $html .= $this->renderCaption($slice['referenced_as'][$this->locale]);
        }

        $html .= '</figure>';

        return $html;
    }

    protected function renderCaption(string $caption): string
    {
        $classes = ClassBuilder::make()
            ->add([
                'text-sm',
                'text-center',
                'font-medium',
                'text-gray-900',
                'mt-2',
            ])
            ->dark('text-gray-100')
            ->print('text-black')
            ->build();

        return sprintf(
            '<figcaption class="%s">%s</figcaption>',
            $classes,
            htmlspecialchars($caption)
        );
    }

    protected function getSectionClasses(array $slice): string
    {
        return ClassBuilder::make()
            ->add([
                'flex',
                'flex-col',
                'gap-4',
                'print:mt-4',
                '@container/slice',
                'flex',
            ])
            ->build();
    }

    protected function localizeStrings(array $strings, array $translations): array
    {
        return array_map(function ($string) use ($translations) {
            return $translations[$string] ?? $string;
        }, $strings);
    }

    protected function localizeDatasets(array $datasets, array $translations): array
    {
        return array_map(function ($row) use ($translations) {
            return array_map(function ($value) use ($translations) {
                return $translations[$value] ?? $value;
            }, $row);
        }, $datasets);
    }

    protected function getRowLabel(array $row): string
    {
        foreach ($row as $value) {
            if (is_array($value) && isset($value[$this->locale])) {
                return $value[$this->locale];
            }
        }

        return '';
    }
}
