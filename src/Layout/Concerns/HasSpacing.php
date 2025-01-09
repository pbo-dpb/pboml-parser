<?php

namespace PBO\PbomlParser\Layout\Concerns;

trait HasSpacing
{
    protected function getSpacingClasses(string $type, array $options = []): string
    {
        $classes = [];

        if ($margin = $options['margin'] ?? null) {
            $classes[] = $this->getMarginClasses($margin);
        }

        if ($padding = $options['padding'] ?? null) {
            $classes[] = $this->getPaddingClasses($padding);
        }

        if ($gap = $options['gap'] ?? null) {
            $classes[] = $this->getGapClasses($gap);
        }

        if (empty($options)) {
            $classes[] = $this->getDefaultSpacing($type);
        }

        return implode(' ', array_filter($classes));
    }

    protected function getMarginClasses(array|string $margin): string
    {
        if (is_string($margin)) {
            return "m-{$margin}";
        }

        return collect($margin)
            ->map(function (string $value, string $direction): string {
                return "m{$direction}-{$value}";
            })
            ->implode(' ');
    }

    protected function getPaddingClasses(array|string $padding): string
    {
        if (is_string($padding)) {
            return "p-{$padding}";
        }

        return collect($padding)
            ->map(function (string $value, string $direction): string {
                return "p{$direction}-{$value}";
            })
            ->implode(' ');
    }

    protected function getGapClasses(array|string $gap): string
    {
        if (is_string($gap)) {
            return "gap-{$gap}";
        }

        return collect($gap)
            ->map(function (string $value, string $direction): string {
                return "gap-{$direction}-{$value}";
            })
            ->implode(' ');
    }

    protected function getDefaultSpacing(string $type): string
    {
        return match ($type) {
            'section' => 'gap-4 print:mt-4',
            'table' => 'gap-4',
            'heading' => 'gap-1',
            'footnotes' => 'gap-4',
            default => 'gap-2'
        };
    }
}
