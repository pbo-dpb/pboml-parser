<?php

namespace PBO\PbomlParser\Generator\Renderers;

use PBO\PbomlParser\Generator\Utils\ClassBuilder;
use PBO\PbomlParser\Generator\Utils\ReferenceProcessor;
use PBO\PbomlParser\Generator\MarkdownConverter;

class AnnotationRenderer
{
    protected ReferenceProcessor $referenceProcessor;
    protected MarkdownConverter $markdown;
    protected string $locale;

    public function __construct(string $locale = 'en')
    {
        $this->locale = $locale;
        $this->referenceProcessor = new ReferenceProcessor($locale);
        $this->markdown = new MarkdownConverter($locale);
    }

    public function render(array $annotations): string
    {
        $containerClasses = ClassBuilder::make()
            ->add(['pt-4', 'mt-4', 'border-t', 'border-gray-300'])
            ->build();

        $titleClasses = ClassBuilder::make()
            ->add(['font-thin', 'text-2xl', 'mb-4'])
            ->build();

        $listClasses = ClassBuilder::make()
            ->add(['flex', 'flex-col', 'gap-4', 'print:list-none'])
            ->build();

        $html = sprintf('<aside class="%s" role="note">', $containerClasses);
        $html .= sprintf('<h2 class="%s" id="pb__annotations-label">Notes</h2>', $titleClasses);
        $html .= sprintf('<ol class="%s">', $listClasses);

        foreach ($annotations as $annotation) {
            $html .= $this->renderAnnotation($annotation);
        }

        $html .= '</ol></aside>';

        return $this->referenceProcessor->process($html);
    }

    protected function renderAnnotation(array $annotation): string
    {
        $containerClasses = ClassBuilder::make()
            ->add(['grid', 'grid-cols-12', 'gap-4', 'print:flex', 'print:gap-0', 'print:py-1'])
            ->build();

        $numberClasses = ClassBuilder::make()
            ->add(['col-span-1', 'print:w-1/12', 'flex', 'justify-end', 'print:pr-4', 'gap-2', 'prose', 'dark:prose-invert', 'font-light', 'tracking-wide', 'proportional-nums', 'text-gray-700'])
            ->dark('text-gray-300')
            ->build();

        $contentClasses = ClassBuilder::make()
            ->add(['col-span-11', 'print:w-11/12', 'flex', 'flex-col', 'gap-1'])
            ->build();

        $id = htmlspecialchars($annotation['id'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = $annotation['content'][$this->locale] ?? $annotation['content']['en'] ?? '';

        $html = sprintf('<li class="%s" id="antn_%s">', $containerClasses, $id);

        $html .= sprintf(
            '<div class="%s"><span class="print:hidden sr-only">Note #%s</span><span aria-hidden="true">%s.</span></div>',
            $numberClasses,
            $id,
            $id
        );

        $html .= sprintf('<div class="%s">', $contentClasses);
        $html .= sprintf('<div class="pboml-non-reactive-prose prose-table:my-0 first:prose-p:inline break-inside-avoid" id="antn_%s">', $id);
        $html .= '<div>';

        $html .= $this->processContent($content, $annotation['content_type']);

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</li>';

        return $html;
    }

    protected function processContent(string $content, string $contentType): string
    {
        return match ($contentType) {
            'markdown' => $this->processMarkdownContent($content),
            'bibtex' => $this->formatBibtex($content),
            default => htmlspecialchars($content),
        };
    }

    protected function processMarkdownContent(string $content): string
    {
        if (preg_match('/^\s*\|.*\|.*\n\s*\|[\s:|\\-]+\|/', $content)) {
            $lines = explode("\n", trim($content));
            $alignments = $this->parseTableAlignment($lines[1]);

            $html = $this->markdown->convert($content);

            return $this->applyTableAlignment($html, $alignments);
        }

        return $this->markdown->convert($content);
    }

    protected function parseTableAlignment(string $delimiterRow): array
    {
        $alignments = [];
        $cells = explode('|', trim($delimiterRow, '|'));

        foreach ($cells as $cell) {
            $cell = trim($cell);
            if (preg_match('/^:.*:$/', $cell)) {
                $alignments[] = 'center';
            } elseif (preg_match('/^:/', $cell)) {
                $alignments[] = 'left';
            } elseif (preg_match('/:$/', $cell)) {
                $alignments[] = 'right';
            } else {
                $alignments[] = 'left';
            }
        }

        return $alignments;
    }

    protected function applyTableAlignment(string $html, array $alignments): string
    {
        $columnIndex = 0;

        return preg_replace_callback(
            '/<(td|th)([^>]*)>/',
            function ($matches) use (&$columnIndex, $alignments) {
                if ($matches[1] === 'th' && $columnIndex >= count($alignments)) {
                    $columnIndex = 0;
                }

                $alignment = $alignments[$columnIndex] ?? 'left';
                $columnIndex++;

                return sprintf(
                    '<%s%s align="%s">',
                    $matches[1],
                    $matches[2],
                    $alignment
                );
            },
            $html
        );
    }

    protected function formatBibtex(string $content): string
    {
        $formattedContent = '<div class="csl-bib-body">';
        if (preg_match('/@\w+{([^,]+),/', $content, $matches)) {
            $citationKey = $matches[1];
            $formattedContent .= sprintf(
                '<div data-csl-entry-id="%s" class="csl-entry">',
                htmlspecialchars($citationKey)
            );

            $content = $this->processBibtexFields($content);

            $formattedContent .= $content;
            $formattedContent .= '</div>';
        }
        $formattedContent .= '</div>';

        return $formattedContent;
    }

    protected function processBibtexFields(string $bibtex): string
    {
        preg_match('/author\s*=\s*{([^}]+)}/', $bibtex, $authorMatches);
        $authors = $authorMatches[1] ?? '';
        $authors = str_replace(' and ', ', ', $authors);

        preg_match('/title\s*=\s*{([^}]+)}/', $bibtex, $titleMatches);
        $title = $titleMatches[1] ?? '';

        preg_match('/journal\s*=\s*{([^}]+)}/', $bibtex, $journalMatches);
        $journal = $journalMatches[1] ?? '';

        preg_match('/year\s*=\s*{(\d+)}/', $bibtex, $yearMatches);
        $year = $yearMatches[1] ?? '';

        preg_match('/volume\s*=\s*{([^}]+)}/', $bibtex, $volumeMatches);
        $volume = $volumeMatches[1] ?? '';

        preg_match('/number\s*=\s*{([^}]+)}/', $bibtex, $numberMatches);
        $number = $numberMatches[1] ?? '';

        preg_match('/pages\s*=\s*{([^}]+)}/', $bibtex, $pagesMatches);
        $pages = $pagesMatches[1] ?? '';

        return sprintf(
            '%s (%s). %s. <i>%s</i>, <i>%s</i>(%s), %s.',
            htmlspecialchars($authors),
            htmlspecialchars($year),
            htmlspecialchars($title),
            htmlspecialchars($journal),
            htmlspecialchars($volume),
            htmlspecialchars($number),
            htmlspecialchars($pages)
        );
    }
}
