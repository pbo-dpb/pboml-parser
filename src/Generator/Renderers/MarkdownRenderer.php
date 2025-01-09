<?php

namespace PBO\PbomlParser\Generator\Renderers;

use PBO\PbomlParser\Generator\MarkdownConverter;
use PBO\PbomlParser\Traits\WithAccessibility;
use PBO\PbomlParser\Traits\WithLayout;
use PBO\PbomlParser\Traits\WithStyles;
use PBO\PbomlParser\Generator\Utils\AttributeBuilder;
use PBO\PbomlParser\Generator\Utils\ClassBuilder;
use PBO\PbomlParser\Generator\Utils\ReferenceProcessor;

class MarkdownRenderer implements SliceRenderer
{
    use WithAccessibility, WithLayout, WithStyles;
    protected ReferenceProcessor $referenceProcessor;
    protected MarkdownConverter $markdown;
    protected string $locale;

    public function __construct(string $locale = null)
    {
        if ($locale !== null) {
            $this->locale = $locale;
        }
        $this->markdown = new MarkdownConverter($locale);
        $this->referenceProcessor = new ReferenceProcessor($locale);
    }

    public function render(array $slice): string
    {
        try {
            $content = $this->getLocalizedContent($slice['content']);
            $content = $this->normalizeContent($content);

            $html = $this->markdown->convert($content);

            $contentWrapper = $this->wrapContent($html, $slice);

            if ($presentation = $slice['presentation'] ?? null) {
                return $this->handlePresentation($contentWrapper, $presentation, $slice);
            }

            return $this->withLayout($this->referenceProcessor->process($contentWrapper), 'markdown', [
                'id' => $slice['id'] ?? null,
                'class' => $this->getSectionClasses($slice),
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function normalizeContent(string $content): string
    {
        $content = preg_replace('/(?<!\n)\n(?!\n)/', ' ', $content);

        $content = preg_replace('/\R{2,}/', "\n\n", $content);

        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        return trim($content);
    }

    protected function getLocalizedContent(array $content): string
    {
        return $content[$this->locale] ?? $content['en'] ?? '';
    }

    protected function wrapContent(string $html, array $slice): string
    {
        $classes = ClassBuilder::make()
            ->add([
                'pboml-prose',
                'prose',
                'dark:prose-invert',
                'max-w-none',
                'prose-a:font-normal',
                'prose-table:my-0',
                'prose-p:mb-4',
                'prose-p:last:mb-0',
                'leading-normal',
                'break-inside-avoid',
            ])
            ->addIf('prose-p:font-semibold', $slice['bold'] ?? false)
            ->build();

        return sprintf('<div class="%s">%s</div>', $classes, $html);
    }

    protected function handlePresentation(string $content, string $presentation, array $slice): string
    {
        switch ($presentation) {
            case 'aside':
                return $this->renderAside($content);
            case 'figure':
                return $this->renderFigure($content, $slice);
            default:
                return $content;
        }
    }

    protected function renderAside(string $content): string
    {
        $classes = ClassBuilder::make()
            ->add([
                'bg-gradient-to-tr',
                'from-sky-50',
                'to-sky-100',
                'rounded-tr-3xl',
                'p-4',
                'break-inside-avoid-page',
                'pb__aside',
            ])
            ->dark([
                'from-transparent',
                'to-sky-900',
            ])
            ->build();

        return sprintf(
            '<aside class="%s" role="complementary">%s</aside>',
            $classes,
            $content
        );
    }

    protected function renderFigure(string $content, array $slice): string
    {
        $classes = ClassBuilder::make()
            ->add([
                'figure',
                'my-8',
                'break-inside-avoid-page',
            ])
            ->build();

        return sprintf(
            '<figure class="%s">%s%s</figure>',
            $classes,
            $content,
            isset($slice['caption']) ? $this->renderCaption($slice['caption']) : ''
        );
    }

    protected function renderCaption(string $caption): string
    {
        return sprintf(
            '<figcaption class="text-sm text-gray-600 mt-2">%s</figcaption>',
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
            ])
            ->build();
    }
}
