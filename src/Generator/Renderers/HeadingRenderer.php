<?php

namespace PBO\PbomlParser\Generator\Renderers;

use PBO\PbomlParser\Traits\WithAccessibility;
use PBO\PbomlParser\Traits\WithLayout;
use PBO\PbomlParser\Traits\WithStyles;
use PBO\PbomlParser\Generator\Utils\AttributeBuilder;
use PBO\PbomlParser\Generator\Utils\ClassBuilder;
use PBO\PbomlParser\Generator\EncodingHandler;

class HeadingRenderer implements SliceRenderer
{
    use WithAccessibility, WithLayout, WithStyles, EncodingHandler;

    protected array $validLevels = [0, 1, 2, 3];

    protected array $headingMap = [
        0 => 'h2',
        1 => 'h3',
        2 => 'h4',
        3 => 'h5',
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
        $level = $this->validateLevel($slice['level'] ?? 0);
        $content = $this->getLocalizedContent($slice['content']);
        $id = $this->generateHeadingId($content);
        $tag = $this->headingMap[$level];

        $classes = ClassBuilder::make()
            ->add([
                'font-thin',
                'break-after-avoid',
                'text-balance',
                'text-2xl',
            ])
            ->addIf('text-xl', $level === 1)
            ->addIf('text-lg', $level === 2)
            ->addIf('text-base', $level === 3)
            ->dark([
                'text-gray-100',
            ])
            ->print([
                'break-after-avoid',
                'text-black',
            ])
            ->build();

        $attributes = AttributeBuilder::make()
            ->add('id', $this->safeHtmlEncode($id))
            ->add('class', $classes)
            ->build();

        $headingContent = $this->generateHeadingContent($content, $slice);

        $heading = sprintf(
            '<%s %s>%s</%s>',
            $tag,
            $attributes,
            $headingContent,
            $tag
        );

        if ($slice['wrap_section'] ?? true) {
            return $this->withLayout($heading, 'heading', [
                'id' => "section-{$id}",
                'class' => 'flex flex-col gap-4 print:mt-4 @container/slice',
            ]);
        }

        return $heading;
    }

    protected function validateLevel(int $level): int
    {
        return in_array($level, $this->validLevels, true) ? $level : 0;
    }

    protected function getLocalizedContent(array $content): string
    {
        $localizedContent = $content[$this->locale] ?? $content['en'] ?? '';
        return $this->ensureUtf8s($localizedContent);
    }

    protected function generateHeadingId(string $content): string
    {
        $id = strtolower($content);
        $id = preg_replace('/[^a-z0-9]+/', '-', $id);
        $id = trim($id, '-');

        return 'heading-' . $id;
    }

    protected function generateHeadingContent(string $content, array $slice): string
    {
        $parts = explode(' – ', $content);

        return collect($parts)
            ->map(function ($part, $index) {
                $spanClass = ClassBuilder::make()
                    ->add('[&:not(:last-child)]:after:content-["–"]')
                    ->add('after:mx-1')
                    ->add('after:text-gray-500')
                    ->addIf('first:font-normal', $index === 0)
                    ->build();

                $encodedPart = $this->safeHtmlEncode($part);

                return sprintf(
                    '<span class="%s">%s</span>',
                    $spanClass,
                    $encodedPart
                );
            })
            ->implode('');
    }
}
