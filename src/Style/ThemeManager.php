<?php

namespace PBO\PbomlParser\Style;

class ThemeManager
{
    protected array $config;

    protected string $currentTheme = 'light';

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getThemeStyles(string $type): string
    {
        return $this->currentTheme === 'dark'
            ? $this->getDarkThemeStyles($type)
            : $this->getLightThemeStyles($type);
    }

    public function setTheme(string $theme): self
    {
        $this->currentTheme = $theme;

        return $this;
    }

    protected function getLightThemeStyles(string $type): string
    {
        return match ($type) {
            'text' => 'text-gray-900',
            'background' => 'bg-white',
            'border' => 'border-gray-200',
            'heading' => 'text-gray-900',
            'link' => 'text-blue-600 hover:text-blue-800',
            default => ''
        };
    }

    protected function getDarkThemeStyles(string $type): string
    {
        return match ($type) {
            'text' => 'dark:text-gray-100',
            'background' => 'dark:bg-gray-900',
            'border' => 'dark:border-gray-700',
            'heading' => 'dark:text-gray-100',
            'link' => 'dark:text-blue-400 dark:hover:text-blue-200',
            default => ''
        };
    }
}
