<?php

namespace PBO\PbomlParser\Generator\Renderers;

use PBO\PbomlParser\Traits\WithAccessibility;
use PBO\PbomlParser\Traits\WithLayout;
use PBO\PbomlParser\Traits\WithStyles;
use PBO\PbomlParser\Generator\Utils\ClassBuilder;

class SVGRenderer implements SliceRenderer
{
    use WithAccessibility, WithLayout, WithStyles;

    protected string $locale;

    public function __construct(?string $locale = null)
    {
        if ($locale !== null) {
            $this->locale = $locale;
        }
    }

    public function render(array $slice): string
    {
        $content = $slice['content'][$this->locale] ?? '';

        $content = $this->sanitizeSVG($content);
        $content = $this->validateViewBox($content);

        $wrappedContent = $this->wrapSVGContent($content, $slice);

        if (! empty($slice['alts'])) {
            $wrappedContent .= $this->renderAltText($slice['alts']);
        }

        if (! empty($slice['sources'])) {
            $wrappedContent .= $this->renderSources($slice['sources']);
        }

        if (($slice['presentation'] ?? '') === 'figure') {
            return $this->renderAsFigure($wrappedContent, $slice);
        }

        return $this->withLayout($wrappedContent, 'svg', [
            'id' => $slice['id'] ?? null,
            'class' => $this->getSectionClasses($slice),
        ]);
    }

    protected function renderSources(array $sources): string
    {
        $containerClasses = ClassBuilder::make()
            ->add([
                'pboml-bitmap-sources',
                'flex',
                'flex-col',
                'gap-1',
                'border-l-2',
                'border-gray-200',
                'pl-2',
                'mt-4',
                'w-full',
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
                htmlspecialchars($source[$this->locale] ?? '')
            );
        }
        $html .= '</div></dl>';

        return $html;
    }

    protected function sanitizeSVG(string $svg): string
    {
        $svg = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $svg);

        $svg = preg_replace('/\bon\w+="[^"]*"/i', '', $svg);

        $svg = preg_replace('/\bxlink:href="(?!#)[^"]*"/i', '', $svg);

        if (! str_contains($svg, 'xmlns="http://www.w3.org/2000/svg"')) {
            $svg = preg_replace('/<svg\b/', '<svg xmlns="http://www.w3.org/2000/svg"', $svg);
        }

        return $svg;
    }

    protected function wrapSVGContent(string $svg, array $slice): string
    {
        $containerClasses = ClassBuilder::make()
            ->add([
                'svg-container',
                'w-full',
                'relative',
                'flex',
                'justify-center',
                'items-center',
            ])
            ->print([
                'print:block',
                'print:w-full',
                'print:max-w-full',
            ])
            ->build();

        $svgClasses = ClassBuilder::make()
            ->add([
                'w-full',
                'h-auto',
                'max-w-full',
            ])
            ->dark([
                'dark:filter',
                'dark:brightness-90',
            ])
            ->print([
                'print:max-w-full',
            ])
            ->build();

        $svg = preg_replace(
            '/<svg\b([^>]*?)(?:\sclass="([^"]*)")?([^>]*?)>/',
            sprintf('<svg$1 class="%s$2"$3>', $svgClasses),
            $svg
        );

        return sprintf(
            '<div class="%s">%s</div>',
            $containerClasses,
            $svg
        );
    }

    protected function renderAsFigure(string $content, array $slice): string
    {
        $figureClasses = ClassBuilder::make()
            ->add([
                'flex',
                'flex-col',
                'gap-4',
                'items-center',
                'break-inside-avoid-page',
            ])
            ->build();

        $html = sprintf('<figure class="%s">', $figureClasses);

        $html .= $content;

        if (isset($slice['referenced_as'][$this->locale])) {
            $html .= $this->renderCaption($slice['referenced_as'][$this->locale]);
        }

        if (! empty($slice['sources'])) {
            $html .= $this->renderSources($slice['sources']);
        }

        if (! empty($slice['notes'])) {
            $html .= $this->renderNotes($slice['notes']);
        }

        if (! empty($slice['alts'])) {
            $html .= $this->renderAltText($slice['alts']);
        }

        $html .= '</figure>';

        return $this->withLayout($html, 'svg-figure', [
            'id' => $slice['id'] ?? null,
            'class' => $this->getSectionClasses($slice),
        ]);
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
                'w-full',
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

    protected function renderAltText(array $alts): string
    {
        $containerClasses = ClassBuilder::make()
            ->add([
                'sr-only',
            ])
            ->build();

        $html = sprintf('<div class="%s">', $containerClasses);
        foreach ($alts as $alt) {
            $html .= sprintf(
                '<p>%s</p>',
                htmlspecialchars($alt[$this->locale])
            );
        }
        $html .= '</div>';

        return $html;
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

    protected function validateViewBox(string $svg): string
    {
        if (! preg_match('/\bviewBox="[^"]*"/', $svg)) {
            preg_match('/\bwidth="([^"]*)"/', $svg, $width);
            preg_match('/\bheight="([^"]*)"/', $svg, $height);

            if (! empty($width[1]) && ! empty($height[1])) {
                $viewBox = sprintf('viewBox="0 0 %s %s"', $width[1], $height[1]);
                $svg = preg_replace('/<svg\b/', '<svg ' . $viewBox, $svg);
            }
        }

        return $svg;
    }
}
