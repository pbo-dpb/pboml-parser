<?php

namespace PBO\PbomlParser\Generator\Renderers;

use PBO\PbomlParser\Generator\Utils\AttributeBuilder;
use PBO\PbomlParser\Generator\Utils\ClassBuilder;
use PBO\PbomlParser\Traits\WithAccessibility;
use PBO\PbomlParser\Traits\WithLayout;
use PBO\PbomlParser\Traits\WithStyles;


class BitmapRenderer implements SliceRenderer
{
    use WithAccessibility, WithLayout, WithStyles;

    protected array $breakpoints = [
        'sm' => '(max-width: 640px)',
        'md' => '(max-width: 768px)',
        'lg' => '(min-width: 769px)',
    ];

    protected string $locale;

    public function __construct(?string $locale = null)
    {
        if ($locale !== null) {
            $this->locale = $locale;
        }
    }

    public function render(array $slice): string
    {
        $mainImageUrl = $slice['content'][$this->locale] ?? '';
        $thumbnails = $slice['thumbnails'][$this->locale] ?? [];

        $pictureContent = $this->renderPictureElement($mainImageUrl, $thumbnails, $slice);

        if (! empty($slice['sources'])) {
            $pictureContent .= $this->renderSources($slice['sources']);
        }

        if (! empty($slice['alts'])) {
            $pictureContent .= $this->renderAltTextDescription($slice['alts']);
        }

        if (($slice['presentation'] ?? '') === 'figure') {
            return $this->renderAsFigure($pictureContent, $slice);
        }

        return $this->withLayout($pictureContent, 'bitmap', [
            'id' => $slice['id'] ?? null,
            'class' => ClassBuilder::make()
                ->add([
                    'flex',
                    'flex-col',
                    'gap-4',
                    'print:mt-4',
                    '@container/slice',
                ])
                ->build()
        ]);
    }

    protected function renderPictureElement(string $mainImageUrl, array $thumbnails, array $slice): string
    {
        $containerClasses = ClassBuilder::make()
            ->add([
                'pboml-bitmap-container',
                'w-full',
                'flex',
                'justify-center',
            ])
            ->print([
                'print:block',
                'print:w-full',
            ])
            ->build();

        $label = $slice['label'][$this->locale] ?? '';

        $attributes = AttributeBuilder::make()
            ->add('class', $containerClasses)
            ->addIf('aria-label', htmlspecialchars($label), ! empty($label))
            ->add('role', 'figure')
            ->build();

        $html = sprintf('<picture %s>', $attributes);
        $html .= $this->renderSourceElements($thumbnails);
        $html .= $this->renderMainImage($mainImageUrl, $slice);
        $html .= '</picture>';

        return $html;
    }

    protected function renderSourceElements(array $thumbnails): string
    {
        $html = '';

        foreach ($this->breakpoints as $size => $media) {
            $srcset = $this->buildSrcSet($thumbnails, $size, 'webp');
            if ($srcset) {
                $html .= sprintf(
                    '<source type="image/webp" srcset="%s" media="%s">',
                    htmlspecialchars($srcset),
                    $media
                );
            }

            $srcset = $this->buildSrcSet($thumbnails, $size, 'png');
            if ($srcset) {
                $html .= sprintf(
                    '<source type="image/png" srcset="%s" media="%s">',
                    htmlspecialchars($srcset),
                    $media
                );
            }
        }

        return $html;
    }

    protected function buildSrcSet(array $thumbnails, string $size, string $format): string
    {
        $srcset = [];

        if (isset($thumbnails["{$size}_1x_{$format}"])) {
            $srcset[] = $thumbnails["{$size}_1x_{$format}"] . ' 1x';
        }
        if (isset($thumbnails["{$size}_2x_{$format}"])) {
            $srcset[] = $thumbnails["{$size}_2x_{$format}"] . ' 2x';
        }

        return implode(', ', $srcset);
    }

    protected function renderMainImage(string $url, array $slice): string
    {
        $alt = '';
        $label = '';

        if (! empty($slice['alts'])) {
            $alt = $slice['alts'][0][$this->locale] ?? '';
        }

        if (! empty($slice['label'])) {
            $label = $slice['label'][$this->locale] ?? '';
        }

        $classes = ClassBuilder::make()
            ->add([
                'pboml-bitmap',
                'w-full',
                'h-auto',
                'object-contain',
            ])
            ->print([
                'print:max-w-full',
            ])
            ->build();

        $attributes = AttributeBuilder::make()
            ->add('src', htmlspecialchars($url))
            ->add('alt', htmlspecialchars($alt))
            ->add('class', $classes)
            ->add('loading', 'lazy')
            ->add('fetchpriority', 'low')
            ->add('decoding', 'async')
            ->add('role', 'img')
            ->addIf('aria-label', htmlspecialchars($label), ! empty($label))
            ->build();

        return sprintf('<img %s>', $attributes);
    }

    protected function renderAsFigure(string $content, array $slice): string
    {

        $figureClasses = ClassBuilder::make()
            ->add([
                'pboml-bitmap-figure',
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

        if (! empty($slice['alts'])) {
            $html .= $this->renderAltTextDescription($slice['alts']);
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

        $html = sprintf('<div class="%s">', $containerClasses);

        foreach ($sources as $source) {
            $sourceClasses = ClassBuilder::make()
                ->add([
                    'pboml-bitmap-source',
                    'text-sm',
                    'text-gray-600',
                ])
                ->dark('text-gray-400')
                ->build();

            $html .= sprintf(
                '<div class="%s">%s</div>',
                $sourceClasses,
                htmlspecialchars($source[$this->locale])
            );
        }

        $html .= '</div>';

        return $html;
    }

    protected function renderAltTextDescription(array $alts): string
    {
        if (count($alts) <= 1) {
            return '';
        }

        $containerClasses = ClassBuilder::make()
            ->add([
                'mt-4',
                'w-full',
                'text-sm',
                'text-gray-600',
            ])
            ->dark('text-gray-400')
            ->build();

        $html = sprintf('<div class="%s">', $containerClasses);

        foreach (array_slice($alts, 1) as $alt) {
            $html .= sprintf(
                '<p class="mb-2">%s</p>',
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
}
