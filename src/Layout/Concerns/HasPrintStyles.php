<?php

namespace PBO\PbomlParser\Style\Concerns;

trait HasPrintStyles
{
    protected function getPrintStyles(string $type): string
    {
        $classes = [];

        $classes[] = match ($type) {
            'container' => 'print:block print:w-full',
            'table' => 'print:break-inside-avoid print:table print:text-sm',
            'text' => 'print:text-black print:text-base',
            'heading' => 'print:break-after-avoid print:text-black print:font-bold',
            'link' => 'print:text-black print:no-underline',
            'aside' => 'print:border print:border-gray-300 print:p-4',
            default => ''
        };

        $classes[] = $this->getPageBreakClasses($type);

        return implode(' ', array_filter($classes));
    }

    protected function getPageBreakClasses(string $type): string
    {
        return match ($type) {
            'section' => 'print:break-inside-avoid-page',
            'heading' => 'print:break-after-avoid',
            'table' => 'print:break-inside-avoid',
            default => ''
        };
    }
}
