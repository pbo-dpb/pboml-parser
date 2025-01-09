<?php

namespace PBO\PbomlParser\Style\Concerns;

trait HasResponsive
{
    protected function getResponsiveStyles(string $type): string
    {
        $classes = [];

        $classes[] = match ($type) {
            'container' => 'w-full lg:container lg:mx-auto',
            'table' => 'overflow-x-auto lg:overflow-visible',
            'text' => 'text-sm md:text-base lg:text-lg',
            'heading' => 'text-xl md:text-2xl lg:text-3xl',
            default => ''
        };

        if ($this->supportsContainerQueries()) {
            $classes[] = '@container/slice';
        }

        return implode(' ', array_filter($classes));
    }

    protected function getContainerBreakpoint(string $size): string
    {
        return match ($size) {
            'sm' => '@container/slice (min-width: 640px)',
            'md' => '@container/slice (min-width: 768px)',
            'lg' => '@container/slice (min-width: 1024px)',
            'xl' => '@container/slice (min-width: 1280px)',
            default => ''
        };
    }

    protected function supportsContainerQueries(): bool
    {
        return $this->config['container_queries'] ?? true;
    }
}
