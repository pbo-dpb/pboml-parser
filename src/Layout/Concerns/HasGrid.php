<?php

namespace PBO\PbomlParser\Layout\Concerns;

trait HasGrid
{
    protected function getGridClasses(array $options = []): string
    {
        $classes = ['grid'];

        if ($cols = $options['cols'] ?? null) {
            $classes[] = match ($cols) {
                1 => 'grid-cols-1',
                2 => 'grid-cols-2',
                3 => 'grid-cols-3',
                4 => 'grid-cols-4',
                12 => 'grid-cols-12',
                default => 'grid-cols-1'
            };
        }

        if ($gap = $options['gap'] ?? null) {
            $classes[] = "gap-{$gap}";
        }

        return implode(' ', $classes);
    }

    protected function getColumnSpan(int $span, int $total = 12): string
    {
        return "col-span-{$span}";
    }
}
