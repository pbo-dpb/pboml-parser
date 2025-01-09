<?php

namespace PBO\PbomlParser\Style;

use PBO\PbomlParser\Style\Concerns\HasDarkMode;
use PBO\PbomlParser\Style\Concerns\HasPrintStyles;
use PBO\PbomlParser\Style\Concerns\HasResponsive;

class StyleManager
{
    use HasDarkMode, HasPrintStyles, HasResponsive;

    protected ThemeManager $themeManager;

    protected PrintManager $printManager;

    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->themeManager = new ThemeManager($config['theme'] ?? []);
        $this->printManager = new PrintManager($config['print'] ?? []);
    }

    public function getStyles(string $type, array $options = []): string
    {
        $classes = [];

        $classes[] = $this->getBaseStyles($type);

        $classes[] = $this->themeManager->getThemeStyles($type);

        if ($options['darkMode'] ?? true) {
            $classes[] = $this->getDarkModeStyles($type);
        }

        if ($options['print'] ?? true) {
            $classes[] = $this->getPrintStyles($type);
        }

        if ($options['responsive'] ?? true) {
            $classes[] = $this->getResponsiveStyles($type);
        }

        return implode(' ', array_filter($classes));
    }

    public function apply(string $content, string $type, array $options = []): string
    {
        $classes = $this->getStyles($type, $options);

        return sprintf('<div class="%s">%s</div>', $classes, $content);
    }

    public function theme(): ThemeManager
    {
        return $this->themeManager;
    }

    public function print(): PrintManager
    {
        return $this->printManager;
    }

    protected function getBaseStyles(string $type): string
    {
        return match ($type) {
            'heading' => 'font-thin break-after-avoid text-balance text-2xl',
            'prose' => 'prose dark:prose-invert max-w-none',
            'table' => 'min-w-full w-max lg:w-full table-fixed border-collapse',
            'link' => 'text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200',
            'aside' => 'bg-gradient-to-tr from-sky-50 to-sky-100 dark:from-transparent dark:to-sky-900 rounded-tr-3xl',
            'footnote' => 'text-sm text-gray-600 dark:text-gray-400',
            default => ''
        };
    }
}
