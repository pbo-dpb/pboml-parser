<?php

namespace PBO\PbomlParser\Generator\Renderers;

use PBO\PbomlParser\Generator\MarkdownConverter;
use PBO\PbomlParser\Traits\WithAccessibility;
use PBO\PbomlParser\Traits\WithLayout;
use PBO\PbomlParser\Traits\WithStyles;
use PBO\PbomlParser\Generator\Utils\ClassBuilder;
use PBO\PbomlParser\Exceptions\EncodingException;
use PBO\PbomlParser\Exceptions\RenderingException;
use PBO\PbomlParser\Generator\EncodingHandler;
use PBO\PbomlParser\Generator\Utils\ReferenceProcessor;

class KeyValueRenderer implements SliceRenderer
{
    use WithAccessibility, WithLayout, WithStyles, EncodingHandler;

    protected MarkdownConverter $markdown;
    protected ReferenceProcessor $referenceProcessor;
    protected string $locale;

    public function __construct(string $locale = null)
    {
        if ($locale !== null) {
            $this->locale = $locale;
        }
        $this->markdown = new MarkdownConverter;
        $this->referenceProcessor = new ReferenceProcessor($locale);
    }

    public function render(array $slice): string
    {
        try {
            $prototype = $slice['prototype'] ?? [];
            $content = $slice['content'] ?? [];

            $containerHtml = $this->renderContainer($slice);

            if (isset($slice['label'][$this->locale])) {
                $label = $this->handleFrenchText($slice['label'][$this->locale]);
                $containerHtml .= $this->renderTitle($label);
            }

            $containerHtml .= $this->renderDefinitionList($content, $prototype);

            if (!empty($slice['sources'])) {
                $containerHtml .= $this->renderSources($slice['sources']);
            }

            $containerHtml .= '</dl>';

            return $this->withLayout($this->referenceProcessor->process($containerHtml), 'kvlist', [
                'id' => $slice['id'] ?? null,
                'class' => $this->getSectionClasses($slice),
            ]);
        } catch (EncodingException $e) {
            throw new RenderingException(
                'Failed to encode key-value content: ' . $e->getMessage(),
                ['slice_id' => $slice['id'] ?? null],
                $e
            );
        }
    }

    protected function renderContainer(array $slice): string
    {
        $classes = ClassBuilder::make()
            ->add([
                'flex',
                'flex-col',
                'gap-2',
                'break-inside-avoid',
            ])
            ->build();

        return sprintf('<dl class="%s">', $classes);
    }

    protected function renderTitle(string $title): string
    {
        $classes = ClassBuilder::make()
            ->add([
                'font-thin',
                'break-after-avoid',
                'text-balance',
                'text-2xl',
            ])
            ->dark('text-gray-100')
            ->print([
                'break-after-avoid',
                'text-black',
            ])
            ->build();

        return sprintf(
            '<h2 class="%s">%s</h2>',
            $classes,
            htmlspecialchars($title)
        );
    }

    protected function renderDefinitionList(array $pairs, array $prototype): string
    {
        $html = '';
        $totalPairs = count($pairs);

        foreach ($pairs as $index => $pair) {
            $html .= $this->renderPair($pair, $prototype);

            if ($index < $totalPairs - 1) {
                $html .= $this->addSeparator();
            }
        }

        return $html;
    }

    protected function renderPair(array $pair, array $prototype): string
    {
        try {
            $keyContent = $pair['key']['content'][$this->locale] ?? '';
            $keyContent = $this->handleFrenchText($keyContent);

            if (is_array($pair['value']['content'][$this->locale] ?? null)) {
                $valueContent = implode('<br>', array_map(fn($item) => $this->handleFrenchText($item), $pair['value']['content'][$this->locale]));
            } else {
                $valueContent = is_array($pair['value']['content'])
                    ? $this->handleFrenchText($pair['value']['content'][$this->locale] ?? '')
                    : $this->handleFrenchText($pair['value']['content'] ?? '');
            }

            $containerClasses = ClassBuilder::make()
                ->add([
                    'flex',
                    'flex-col',
                    'grid-cols-3',
                    'gap-.5',
                    'border-l-2',
                    'border-gray-200',
                    'pl-2',
                ])
                ->dark('border-gray-700')
                ->build();

            return sprintf(
                '<div class="%s">%s%s</div>',
                $containerClasses,
                $this->renderKey($keyContent, $prototype['key'] ?? []),
                $this->renderValue($valueContent, $prototype['value'] ?? [])
            );
        } catch (EncodingException $e) {
            throw new RenderingException(
                'Failed to encode key-value pair: ' . $e->getMessage(),
                ['key' => $keyContent ?? null],
                $e
            );
        }
    }

    protected function addSeparator(): string
    {
        return sprintf(
            '<span role="separator" class="%s">•</span>',
            ClassBuilder::make()
                ->add([
                    'pboml-prose',
                    'prose',
                    'prose-sm',
                    'dark:prose-invert',
                    'max-w-none',
                    'prose-a:font-normal',
                    'prose-p:inline',
                    'leading-none',
                    'break-inside-avoid',
                ])
                ->build()
        );
    }

    protected function renderKey(string $content, array $prototype): string
    {
        $classes = ClassBuilder::make()
            ->add([
                'pboml-prose',
                'prose',
                'prose-sm',
                'dark:prose-invert',
                'max-w-none',
                'prose-a:font-normal',
                'prose-p:inline',
                'leading-none',
                'break-inside-avoid',
            ])
            ->addIf('prose-p:font-semibold prose-a:font-semibold', $prototype['emphasize'] ?? false)
            ->build();

        $formattedContent = $this->formatContent($content, $prototype['type'] ?? 'text');

        return sprintf(
            '<dt class="">%s</dt>',
            sprintf('<span class="%s">%s</span>', $classes, $formattedContent)
        );
    }

    protected function renderValue($content, array $prototype): string
    {
        $classes = ClassBuilder::make()
            ->add([
                'pboml-prose',
                'prose',
                'prose-sm',
                'dark:prose-invert',
                'max-w-none',
                'prose-a:font-normal',
                'prose-p:inline',
                'leading-none',
                'break-inside-avoid',
            ])
            ->build();

        $formattedContent = is_array($content)
            ? implode('<br>', array_map(fn($item) => $this->formatContent($item, $prototype['type'] ?? 'text'), $content))
            : $this->formatContent($content, $prototype['type'] ?? 'text');

        return sprintf(
            '<dd class="col-span-2">%s</dd>',
            sprintf('<span class="%s">%s</span>', $classes, $formattedContent)
        );
    }

    protected function formatContent(string $content, string $type): string
    {
        return match ($type) {
            'markdown' => $this->markdown->convert($content),
            'number' => $this->formatNumber($content),
            default => htmlspecialchars($content)
        };
    }

    protected function formatNumber(string $content): string
    {
        if (empty($content)) {
            return '0.00';
        }

        $number = (float) str_replace(',', '', $content);

        return number_format($number, 2, '.', ',');
    }

    protected function renderSources(array $sources): string
    {
        try {
            $containerClasses = ClassBuilder::make()
                ->add([
                    'flex',
                    'flex-col',
                    'grid-cols-3',
                    'gap-1',
                    'border-l-2',
                    'border-gray-200',
                    'pl-2',
                ])
                ->dark('border-gray-700')
                ->build();

            $html = sprintf('<dl class="%s">', $containerClasses);
            $html .= '<dt class="text-sm font-semibold">Sources</dt>';

            $contentClasses = ClassBuilder::make()
                ->add([
                    $contentClasses = ClassBuilder::make()
                        ->add([
                            'pboml-prose',
                            'prose-sm',
                            'prose-p:my-0',
                            'prose-td:border',
                            'prose-td:border-gray-300',
                            'prose-td:p-2',
                            'prose-th:border',
                            'prose-th:border-gray-300',
                            'prose-th:font-semibold',
                            'prose-th:p-2',
                            'prose-th:text-left',
                            'prose-table:table-fixed',
                            'prose-table:w-full',
                        ])
                ])
                ->build();

            $html .= sprintf('<div class="%s">', $contentClasses);
            foreach ($sources as $source) {
                $sourceContent = $this->handleFrenchText($source[$this->locale] ?? '');
                $html .= sprintf(
                    '<p>%s</p>',
                    htmlspecialchars($sourceContent)
                );
            }
            $html .= '</div></dl>';

            return $html;
        } catch (EncodingException $e) {
            throw new RenderingException(
                'Failed to encode sources: ' . $e->getMessage(),
                [],
                $e
            );
        }
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
}
