<?php

namespace PBO\PbomlParser\Style\Concerns;

trait HasDarkMode
{
    protected function getDarkModeStyles(string $type): string
    {
        return match ($type) {
            'text' => 'dark:text-gray-100',
            'background' => 'dark:bg-gray-900',
            'border' => 'dark:border-gray-700',
            'table' => 'dark:border-gray-700 dark:bg-gray-800',
            'aside' => 'dark:from-transparent dark:to-sky-900',
            'link' => 'dark:text-blue-400 dark:hover:text-blue-200',
            default => ''
        };
    }
}
