<?php

namespace PBO\PbomlParser\Generator;

use Exception;
use JsonException;
use PBO\PbomlParser\Exceptions\EncodingException;
use PBO\PbomlParser\Exceptions\RenderingException;
use PBO\PbomlParser\Generator\Renderers\BitmapRenderer;
use PBO\PbomlParser\Generator\Renderers\ChartRenderer;
use PBO\PbomlParser\Generator\Renderers\HeadingRenderer;
use PBO\PbomlParser\Generator\Renderers\KeyValueRenderer;
use PBO\PbomlParser\Generator\Renderers\MarkdownRenderer;
use PBO\PbomlParser\Generator\Renderers\SVGRenderer;
use PBO\PbomlParser\Generator\Renderers\TableRenderer;
use PBO\PbomlParser\Generator\Renderers\HTMLRenderer;
use PBO\PbomlParser\Generator\Renderers\AnnotationRenderer;
use PBO\PbomlParser\Generator\Utils\AttributeBuilder;
use PBO\PbomlParser\Generator\Utils\ClassBuilder;
use PBO\PbomlParser\SEO\AccessibilityManager;
use PBO\PbomlParser\SEO\SEOManager;

class HTMLGenerator
{
    use EncodingHandler;

    protected MarkdownConverter $markdown;

    protected SEOManager $seo;

    protected AccessibilityManager $accessibility;

    protected array $renderers = [];

    protected array $config = [];

    public function __construct()
    {
        $this->markdown = new MarkdownConverter();
        $this->seo = new SEOManager;
        $this->accessibility = new AccessibilityManager;
    }

    public function generate(array $document): array
    {
        try {
            $output = [];
            foreach (['en', 'fr'] as $locale) {
                $this->registerDefaultRenderers($locale);
                $mainContent = $this->renderDocument($document, $locale);
                $mainContent = $this->accessibility->enhance($mainContent);
                $output[$locale] = $this->wrapWithLayout($mainContent, $document, $locale);
            }

            return $output;
        } catch (EncodingException $e) {
            throw new RenderingException(
                'HTML generation failed due to encoding error: ' . $e->getMessage(),
                ['document' => $document['document'] ?? []],
                $e
            );
        } catch (Exception $e) {
            throw new RenderingException(
                'HTML generation failed: ' . $e->getMessage(),
                ['document' => $document['document'] ?? []],
                $e
            );
        }
    }


    protected function renderHeader(array $document, string $locale): string
    {
        $metadata = $document['document'] ?? [];
        $containerClasses = ClassBuilder::make()->add(['flex', 'flex-col', 'gap-1'])->build();
        $typeClasses = ClassBuilder::make()->add('text-xl')->build();
        $titleClasses = ClassBuilder::make()->add(['font-thin', 'text-4xl', 'text-balance'])->build();
        $dateClasses = ClassBuilder::make()->add(['text-sm', 'text-gray-800'])->dark('text-gray-200')->build();

        $html = sprintf('<header class="%s">', $containerClasses);

        if (isset($metadata['type'][$locale])) {
            try {
                $html .= sprintf('<div class="%s">%s</div>', $typeClasses, $metadata['type'][$locale]);
            } catch (EncodingException $e) {
                throw new RenderingException('Failed to encode document type: ' . $e->getMessage(), ['locale' => $locale], $e);
            }
        }

        if (isset($metadata['title'][$locale])) {
            try {
                $title = $metadata['title'][$locale];
                $html .= sprintf('<h1 class="%s">%s</h1>', $titleClasses, $title);
            } catch (EncodingException $e) {
                throw new RenderingException('Failed to encode document title: ' . $e->getMessage(), ['locale' => $locale], $e);
            }
        }

        if (isset($metadata['release_date'])) {
            $formattedDate = date('F j, Y', strtotime($metadata['release_date']));
            $html .= sprintf('<div class="%s">%s</div>', $dateClasses, ($formattedDate));
        }

        $html .= '</header>';

        return $html;
    }

    protected function renderAnnotationContent(array $annotation, string $locale): string
    {
        $content = $annotation['content'][$locale] ?? '';

        try {
            return match ($annotation['content_type']) {
                'markdown' => $this->markdown->convert($content),
                'bibtex' => $this->renderBibtexCitation($content),
                default => ($content)
            };
        } catch (EncodingException $e) {
            throw new RenderingException(
                'Failed to encode annotation content: ' . $e->getMessage(),
                [
                    'locale' => $locale,
                    'content_type' => $annotation['content_type'],
                ],
                $e
            );
        }
    }

    protected function renderBibtexCitation(string $bibtex): string
    {
        try {
            return sprintf(
                '<div><pre><code>%s</code></pre></div>',
                ($bibtex)
            );
        } catch (EncodingException $e) {
            throw new RenderingException(
                'Failed to encode bibtex citation: ' . $e->getMessage(),
                [],
                $e
            );
        }
    }

    protected function getDefaultLayout(
        string $content,
        array $metadata,
        array $metaTags,
        array $structuredData,
        string $locale
    ): string {
        try {
            $title = ($metadata['title'][$locale] ?? '');
            $metaTagsHtml = $this->renderMetaTags($metaTags);
            $structuredDataJson = json_encode(
                $structuredData,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
            $content = ($content);

            $languageMeta = match ($locale) {
                'fr' => '<meta http-equiv="Content-Language" content="fr">',
                default => ''
            };

            return <<<HTML
            <!DOCTYPE html>
            <html lang="{($locale)}">
            <head>
                {$this->generateMetaTags()}
                {$languageMeta}
                <title>{($title)}</title>
                {$metaTagsHtml}
                <script type="application/ld+json">
                    {$structuredDataJson}
                </script>
            </head>
            <body>
                {$content}
            </body>
            </html>
            HTML;
        } catch (EncodingException $e) {
            throw new RenderingException(
                'Failed to generate layout: ' . $e->getMessage(),
                ['locale' => $locale],
                $e
            );
        } catch (JsonException $e) {
            throw new RenderingException(
                'Failed to encode structured data: ' . $e->getMessage(),
                [],
                $e
            );
        }
    }

    protected function renderMetaTags(array $metaTags): string
    {
        $html = '';
        foreach ($metaTags as $name => $content) {
            if (empty($content)) {
                continue;
            }

            if ($name === 'title') {
                continue;
            }

            try {
                if (is_array($content)) {
                    if ($name === 'alternate') {
                        foreach ($content as $lang => $url) {
                            $html .= sprintf(
                                '<link rel="alternate" hreflang="%s" href="%s">',
                                ($lang),
                                ($url)
                            );
                        }
                    } else {
                        $content = implode(', ', $content);
                    }
                }

                if (is_string($content)) {
                    if (str_starts_with($name, 'og:')) {
                        $html .= sprintf(
                            '<meta property="%s" content="%s">',
                            ($name),
                            ($content)
                        );
                    } else {
                        $html .= sprintf(
                            '<meta name="%s" content="%s">',
                            ($name),
                            ($content)
                        );
                    }
                }
            } catch (EncodingException $e) {
                throw new RenderingException(
                    'Failed to encode meta tag: ' . $e->getMessage(),
                    ['tag_name' => $name],
                    $e
                );
            }
        }

        return $html;
    }

    protected function renderDocument(array $document, string $locale): string
    {
        $html = $this->renderHeader($document, $locale);

        foreach ($document['slices'] ?? [] as $slice) {
            $sliceContent = $this->renderSlice($slice, $locale);
            $encodedContent = ($sliceContent);
            $html .= $encodedContent;
        }

        if (! empty($document['annotations'])) {
            $html .= $this->renderAnnotations($document['annotations'], $locale);
        }

        $html .= $this->renderFooter($document, $locale);

        return $html;
    }

    protected function renderFooter(array $document, string $locale): string
    {
        $metadata = $document['document'] ?? [];

        $containerClasses = ClassBuilder::make()
            ->add([
                'flex',
                'flex-row',
                'gap-2',
                'text-xs',
                'text-gray-800',
                'justify-center',
                'items-center',
            ])
            ->print('mt-8')
            ->build();

        $separatorClasses = ClassBuilder::make()
            ->add('text-gray-500')
            ->build();

        $html = sprintf('<footer class="%s">', $containerClasses);

        if (isset($metadata['copyright'][$locale])) {
            $html .= sprintf(
                '<div class="">%s</div>',
                htmlspecialchars($metadata['copyright'][$locale])
            );
        }

        $html .= sprintf(
            '<div role="separator" ariahidden="true" class="%s">•</div>',
            $separatorClasses
        );

        if (isset($metadata['id'])) {
            $html .= sprintf(
                '<div class="">%s</div>',
                htmlspecialchars($metadata['id'])
            );
        }

        $html .= '</footer>';

        return $html;
    }

    protected function renderSlice(array $slice, string $locale): string
    {
        $type = $slice['type'] ?? null;

        if (!$type) {
            throw new RenderingException('Missing slice type', ['slice' => $slice]);
        }

        if (!isset($this->renderers[$type])) {
            throw new RenderingException("No renderer registered for slice type: {$type}", ['slice_type' => $type]);
        }

        try {
            $html = $this->renderers[$type]->render($slice, $locale);

            return $this->wrapSlice($html, $slice);
        } catch (Exception $e) {
            throw new RenderingException(
                'Failed to render slice: ' . $e->getMessage(),
                ['slice_type' => $type, 'slice_id' => $slice['id'] ?? null],
                $e
            );
        }
    }


    protected function wrapSlice(string $html, array $slice): string
    {
        $classes = ClassBuilder::make()
            ->add([
                'flex',
                'flex-col',
                'gap-4',
                'print:mt-4',
                '@container/slice',
            ])
            ->addIf('break-inside-avoid-page', $slice['type'] === 'table')
            ->build();

        $attributes = AttributeBuilder::make()
            ->add('class', $classes)
            ->addIf('id', $slice['id'] ?? null, isset($slice['id']))
            ->build();

        return sprintf(
            '<section %s>%s</section>',
            $attributes,
            $html
        );
    }

    protected function renderAnnotations(array $annotations, string $locale): string
    {
        $renderer = new AnnotationRenderer($locale);
        return $renderer->render($annotations);
    }

    protected function wrapWithLayout(string $content, array $document, string $locale): string
    {
        $metadata = $document['metadata'] ?? [];

        $containerClasses = ClassBuilder::make()
            ->add([
                'flex',
                'flex-col',
                'gap-8',
                'print:block',
            ])
            ->build();

        $mainAttributes = AttributeBuilder::make()
            ->add('class', $containerClasses)
            ->add('role', 'main')
            ->build();

        $mainContent = sprintf('<main %s>%s</main>', $mainAttributes, $content);

        $metaTags = $this->seo->generateMetaTags($metadata);
        $structuredData = $this->seo->generateStructuredData($metadata, $locale);

        return $this->getDefaultLayout(
            $mainContent,
            $metadata,
            $metaTags,
            $structuredData,
            $locale
        );
    }

    protected function registerDefaultRenderers(string $locale): void
    {
        $this->renderers = [
            'markdown' => new MarkdownRenderer($locale),
            'heading' => new HeadingRenderer($locale),
            'table' => new TableRenderer($locale),
            'svg' => new SVGRenderer($locale),
            'chart' => new ChartRenderer($locale),
            'kvlist' => new KeyValueRenderer($locale),
            'bitmap' => new BitmapRenderer($locale),
            'html' => new HTMLRenderer($locale),
        ];
    }
}
