<?php

namespace PBO\PbomlParser\Layout\Concerns;

trait HasContainer
{
    protected function getContainerClasses(string $type): string
    {
        $classes = ['flex flex-col gap-4 print:mt-4'];

        switch ($type) {
            case 'table':
                $classes[] = 'break-inside-avoid-page';
                break;
            case 'markdown':
                $classes[] = 'prose dark:prose-invert';
                break;
            case 'aside':
                $classes[] = 'bg-gradient-to-tr from-sky-50 to-sky-100 dark:from-transparent dark:to-sky-900 rounded-tr-3xl p-4';
                break;
        }

        return implode(' ', $classes);
    }

    protected function getContainerAttributes(string $type): array
    {
        $attributes = [];

        if (isset($this->config['id'])) {
            $attributes['id'] = $this->config['id'];
        }

        switch ($type) {
            case 'aside':
                $attributes['role'] = 'complementary';
                break;
            case 'footnotes':
                $attributes['role'] = 'note';
                break;
        }

        return $attributes;
    }
}
