<?php

namespace PBO\PbomlParser\Generator\Renderers;

use PBO\PbomlParser\Traits\WithAccessibility;
use PBO\PbomlParser\Traits\WithLayout;
use PBO\PbomlParser\Traits\WithStyles;
use PBO\PbomlParser\Generator\Utils\AttributeBuilder;
use PBO\PbomlParser\Generator\Utils\ClassBuilder;
use PBO\PbomlParser\Generator\Utils\ReferenceProcessor;
use PBO\PbomlParser\Generator\MarkdownConverter;

class TableRenderer implements SliceRenderer
{
    use WithAccessibility, WithLayout, WithStyles;

    protected string $locale;
    protected ReferenceProcessor $referenceProcessor;
    protected MarkdownConverter $markdownConverter;

    public function __construct(?string $locale = null)
    {
        if ($locale !== null) {
            $this->locale = $locale;
        }
        $this->referenceProcessor = new ReferenceProcessor($locale);
        $this->markdownConverter  = new MarkdownConverter($locale);
    }

    public function render(array $slice): string
    {
        $variables = $slice['variables'] ?? [];
        $content   = $slice['content'] ?? [];

        $html  = $this->renderContainer($slice);

        $html .= $this->renderHeading($slice);

        $html .= $this->renderTableWideUnit($slice);

        $html .= $this->renderPivotTableWithGroups($variables, $content);

        if (! empty($slice['alts'])) {
            $html .= $this->renderAltsAsTextVersion($slice['alts']);
        }

        if (! empty($slice['sources'])) {
            $html .= $this->renderSources($slice['sources']);
        }
        if (! empty($slice['notes'])) {
            $html .= $this->renderNotes($slice['notes']);
        }

        $html .= '</section>';

        return $this->referenceProcessor->process($html);
    }

    protected function renderContainer(array $slice): string
    {
        $classes = ClassBuilder::make()
            ->add([
                'flex',
                'flex-col',
                'gap-4',
                'print:mt-4',
                '@container/slice',
                'break-inside-avoid-page'
            ])
            ->build();

        $attributes = AttributeBuilder::make()
            ->add('class', $classes)
            ->addIf('id', $slice['id'] ?? null, isset($slice['id']))
            ->build();

        return sprintf('<section %s>', $attributes);
    }

    protected function renderHeading(array $slice): string
    {
        $reference = $slice['referenced_as'][$this->locale] ?? null;
        $label     = $slice['label'][$this->locale] ?? null;

        if (empty($reference) && empty($label)) {
            return '';
        }

        $parts = [];
        if (! empty($reference)) {
            $parts[] = htmlspecialchars($reference, ENT_QUOTES);
        }
        if (! empty($label)) {
            $parts[] = htmlspecialchars($label, ENT_QUOTES);
        }

        $spans = implode(' - ', array_map(fn($part) => sprintf(
            '<span class="first:font-normal">%s</span>',
            $part
        ), $parts));

        return sprintf(
            '<h2 class="font-thin break-after-avoid text-balance text-2xl">%s</h2>',
            $spans
        );
    }

    protected function renderTableWideUnit(array $slice): string
    {
        if (empty($slice['variables'])) {
            return '';
        }

        $units = [];

        foreach ($slice['variables'] as $variable) {
            if (!empty($variable['unit'])) {
                $unit = $variable['unit'][$this->locale] ?? null;
                if (!empty($unit) && !in_array($unit, $units)) {
                    $units[] = htmlspecialchars($unit, ENT_QUOTES);
                }
            }
        }

        if (empty($units)) {
            return '';
        }

        return sprintf(
            '<div aria-hidden="true" class="font-thin text-gray-800 dark:text-gray-200 border-l-2 border-gray-200 dark:border-gray-700 pl-2">%s</div>',
            implode(', ', $units)
        );
    }



    protected function renderPivotTableWithGroups(array $variables, array $content): string
    {

        $html = '<div class="overflow-x-auto">';
        $html .= '<table class="min-w-full table-fixed border-collapse">';
        $html .= '<tbody>';

        $groupedVars = [];
        foreach ($variables as $varKey => $varConfig) {
            $groupName = $varConfig['group'][$this->locale] ?? '';
            $groupedVars[$groupName][] = [
                'key' => $varKey,
                'config' => $varConfig,
            ];
        }

        $groupLabels = array_keys($groupedVars);
        usort($groupLabels, fn($a, $b) => $a === '' ? -1 : ($b === '' ? 1 : 0));

        foreach ($groupLabels as $groupName) {
            $varsInGroup = $groupedVars[$groupName];
            $rowSpan     = count($varsInGroup);

            $first = array_shift($varsInGroup);
            $html .= $this->renderPivotRow($groupName, $rowSpan, $first['key'], $first['config'], $content);

            foreach ($varsInGroup as $var) {
                $html .= $this->renderPivotRow(null, 1, $var['key'], $var['config'], $content);
            }
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    protected function renderPivotRow(
        ?string $groupName,
        int $rowSpan,
        string $varKey,
        array $varConfig,
        array $content
    ): string {
        $rowHtml = '<tr>';

        if ($groupName !== null) {
            $borderClass = ($groupName === '')
                ? 'border-none'
                : 'border border-gray-300 dark:border-gray-700';

            $rowHtml .= sprintf(
                '<th scope="row" rowspan="%d" class="bg-transparent %s p-1 leading-snug text-balance hyphens-auto pboml-prose">%s</th>',
                $rowSpan,
                $borderClass,
                htmlspecialchars($groupName, ENT_QUOTES)
            );
        }

        $label = $varConfig['label'][$this->locale] ?? '';

        $rowHtml .= sprintf(
            '<th class="border border-gray-300 dark:border-gray-700 p-1 leading-snug text-balance hyphens-auto sticky z-50 -left-2 text-center bg-[rgba(219,234,254,0.8)] dark:bg-[rgba(3,7,18,0.8)] lg:bg-transparent backdrop-blur-sm lg:backdrop-blur-none" scope="row">%s</th>',
            htmlspecialchars($label, ENT_QUOTES)
        );

        foreach ($content as $index => $row) {
            $value = $row[$varKey] ?? null;

            if (is_array($value) && isset($value[$this->locale])) {
                $value = $value[$this->locale];
            }

            if (($varConfig['type'] ?? '') === 'number' && $value !== null) {
                $value = number_format($value, 0, '.', ' ');
            }

            if (($varConfig['type'] ?? '') === 'markdown' && $value !== null) {
                $value = $this->markdownConverter->convert((string)$value);
                $value = trim($value);
            }

            if (empty($value) && $value !== '0') {
                $rowHtml .= '<td class="border border-gray-300 dark:border-gray-700 p-1 text-center bg-gray-100 dark:bg-gray-900"><span class="sr-only">Empty cell</span></td>';
            } else {
                if (($varConfig['type'] ?? '') !== 'markdown') {
                    $value = htmlspecialchars((string)$value, ENT_QUOTES);
                }

                $rowHtml .= sprintf(
                    '<td class="border border-gray-300 dark:border-gray-700 p-1 text-center">%s</td>',
                    $value
                );
            }
        }

        $rowHtml .= '</tr>';
        return $rowHtml;
    }

    protected function renderAltsAsTextVersion(array $alts): string
    {
        if (empty($alts)) {
            return '';
        }

        $containerClasses = ClassBuilder::make()
            ->add([
                'print:hidden',
                'flex',
                'flex-col',
                'gap-2',
                'border-l-2',
                'border-blue-200',
                'dark:border-blue-700',
                'pl-2',
            ])
            ->build();

        $summaryClasses = ClassBuilder::make()
            ->add([
                'cursor-pointer',
                'text-blue-900',
                'hover:text-blue-800',
                'dark:text-blue-100',
                'dark:hover:text-blue-200',
                'text-sm',
                'font-semibold',
                'select-none',
            ])
            ->build();

        $bodyHtml = '';
        foreach ($alts as $alt) {
            $localeContent = $alt[$this->locale] ?? reset($alt);
            $converted     = $this->markdownConverter->convert((string)$localeContent);

            $bodyHtml .= sprintf(
                '<div class="pboml-prose prose-sm prose-p:my-0 prose-table:table-fixed prose-table:w-full">%s</div>',
                $converted
            );
        }

        return sprintf(
            '<details class="%s"><summary class="%s">Text version</summary><div>%s</div></details>',
            $containerClasses,
            $summaryClasses,
            $bodyHtml
        );
    }

    protected function renderSources(array $sources): string
    {
        $containerClasses = ClassBuilder::make()
            ->add([
                'border-l-2',
                'border-gray-200',
                'dark:border-gray-700',
                'flex',
                'flex-col',
                'grid-cols-3',
                'gap-1',
                'pl-2',
            ])
            ->build();

        $html = sprintf('<dl class="%s">', $containerClasses);
        $html .= '<dt class="text-sm font-semibold">Sources</dt>';

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
            ->dark([
                'prose-td:border-gray-700',
                'prose-th:border-gray-700',
            ])
            ->build();

        $html .= sprintf('<div class="%s">', $contentClasses);
        foreach ($sources as $source) {
            $sourceText = $source[$this->locale] ?? reset($source);
            $html .= sprintf('<p>%s</p>', htmlspecialchars($sourceText, ENT_QUOTES));
        }
        $html .= '</div></dl>';

        return $html;
    }

    protected function renderNotes(array $notes): string
    {
        $containerClasses = ClassBuilder::make()
            ->add([
                'border-l-2',
                'border-gray-200',
                'dark:border-gray-700',
                'flex',
                'flex-col',
                'grid-cols-3',
                'gap-1',
                'pl-2',
            ])
            ->build();

        $html = sprintf('<dl class="%s">', $containerClasses);
        $html .= '<dt class="text-sm font-semibold">Note</dt>';

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
            ->dark([
                'prose-td:border-gray-700',
                'prose-th:border-gray-700',
            ])
            ->build();

        $html .= sprintf('<div class="%s">', $contentClasses);
        foreach ($notes as $note) {
            $noteText = $note[$this->locale] ?? reset($note);
            $html .= sprintf('<p>%s</p>', htmlspecialchars($noteText, ENT_QUOTES));
        }
        $html .= '</div></dl>';

        return $html;
    }
}
