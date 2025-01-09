<?php

namespace PBO\PbomlParser\Layout;

use PBO\PbomlParser\Layout\Concerns\HasContainer;
use PBO\PbomlParser\Layout\Concerns\HasGrid;
use PBO\PbomlParser\Layout\Concerns\HasSpacing;

class LayoutManager
{
    use HasContainer, HasGrid, HasSpacing;

    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getLayoutClasses(string $type, array $options = []): string
    {
        $classes = [];

        if ($options['container'] ?? true) {
            $classes[] = $this->getContainerClasses($type);
        }

        if ($options['grid'] ?? false) {
            $classes[] = $this->getGridClasses($options['grid']);
        }

        $classes[] = $this->getSpacingClasses($type, $options['spacing'] ?? []);

        if ($options['flex'] ?? true) {
            $classes[] = 'flex flex-col';
        }

        return implode(' ', array_filter($classes));
    }

    public function getLayoutAttributes(string $type, array $options = []): array
    {
        $attributes = [];

        if ($options['container'] ?? true) {
            $attributes = array_merge($attributes, $this->getContainerAttributes($type));
        }

        if ($options['responsive'] ?? true) {
            $attributes['class'] = ($attributes['class'] ?? '').' @container/slice';
        }

        return $attributes;
    }

    public function wrap(string $content, string $type, array $options = []): string
    {
        $classes = $this->getLayoutClasses($type, $options);
        $attributes = $this->getLayoutAttributes($type, $options);

        $attributeString = collect($attributes)->map(function ($value, $key) {
            return sprintf('%s="%s"', $key, htmlspecialchars($value));
        })->implode(' ');

        return sprintf(
            '<section class="%s" %s>%s</section>',
            $classes,
            $attributeString,
            $content
        );
    }
}
