<?php

namespace PBO\PbomlParser\Generator\Renderers;

use PBO\PbomlParser\Traits\WithAccessibility;
use PBO\PbomlParser\Traits\WithLayout;
use PBO\PbomlParser\Traits\WithStyles;
use PBO\PbomlParser\Generator\Utils\AttributeBuilder;
use PBO\PbomlParser\Generator\Utils\ClassBuilder;
use PBO\PbomlParser\Generator\EncodingHandler;
use PBO\PbomlParser\Exceptions\RenderingException;

class HTMLRenderer implements SliceRenderer
{
    use WithAccessibility, WithLayout, WithStyles, EncodingHandler;

    protected string $locale;

    public function __construct(?string $locale = null)
    {
        if ($locale !== null) {
            $this->locale = $locale;
        }
    }

    public function render(array $slice): string
    {
        try {
            $content = $this->getLocalizedContent($slice['content']);
            $content = $this->handleFrenchText($content);
            
            $css = $slice['css'] ?? '';
            $removeDefaultStyles = $slice['remove_default_styles'] ?? false;

            $id = 'html-' . uniqid();

            $hostClasses = ClassBuilder::make()
                ->add([
                    'block',
                    'w-full',
                ])
                ->print([
                    'break-inside-avoid',
                ])
                ->build();

            $hostAttributes = AttributeBuilder::make()
                ->add('id', htmlspecialchars($id))
                ->add('class', $hostClasses)
                ->build();

            $shadowContent = $this->buildShadowContent($content, $css, $removeDefaultStyles);

            $html = sprintf(
                '<div %s>
                    <template shadowrootmode="open">
                        %s
                    </template>
                </div>',
                $hostAttributes,
                $shadowContent
            );
            

            return $this->withLayout($html, 'html', [
                'class' => 'flex flex-col gap-4 print:mt-4 @container/slice',
            ]);

        } catch (\Exception $e) {
            throw new RenderingException(
                'Failed to render HTML slice: ' . $e->getMessage(),
                ['slice_id' => $slice['id'] ?? null],
                $e
            );
        }
    }

    protected function getLocalizedContent(array $content): string
    {
        return $content[$this->locale] ?? $content['en'] ?? '';
    }

    protected function buildShadowContent(string $content, string $css, bool $removeDefaultStyles): string
    {
        try {
            $styles = [];

            if (!$removeDefaultStyles) {
                $styles[] = $this->getDefaultStyles();
            }

            if (!empty($css)) {
                $css = $this->handleFrenchText($css);
                $styles[] = $css;
            }

            $styleTag = !empty($styles)
                ? sprintf('<style>%s</style>', implode("\n", $styles))
                : '';

            return $styleTag . "\n" . $content;

        } catch (\Exception $e) {
            throw new RenderingException(
                'Failed to build shadow content: ' . $e->getMessage(),
                [],
                $e
            );
        }
    }

    protected function getDefaultStyles(): string
    {
        return '
            :host {
                font-family: system-ui, sans-serif;
                line-height: 1.5;
            }
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            p:not(:first-child) {
                margin-top: 1em;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                border: 1px solid #e5e7eb;
                padding: 0.5rem;
                text-align: left;
            }
            @media (prefers-color-scheme: dark) {
                th, td {
                    border-color: #374151;
                }
            }
        ';
    }

    protected function sanitizeHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/\bon\w+\s*=\s*([\'"])?[^"\']*\1/i', '', $html);
        
        return preg_replace_callback(
            '/\b(\w+)\s*=\s*(["\'])(.*?)\2/i',
            function($matches) {
                $attr = $matches[1];
                $quote = $matches[2];
                $value = $this->handleFrenchText($matches[3]);
                return sprintf('%s=%s%s%s', $attr, $quote, $value, $quote);
            },
            $html
        );
    }
}