<?php

namespace PBO\PbomlParser\Style;

class PrintManager
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getPrintStyles(string $type): string
    {
        return match ($type) {
            'container' => 'print:block print:w-full',
            'heading' => 'print:break-after-avoid print:text-black',
            'table' => 'print:break-inside-avoid print:table print:text-sm',
            'link' => 'print:text-black print:no-underline',
            'footnote' => 'print:text-xs print:text-black',
            'aside' => 'print:border print:border-gray-300 print:p-4',
            default => ''
        };
    }

    public function getPageBreakRules(string $type): string
    {
        return match ($type) {
            'section' => 'print:break-before-auto print:break-after-auto',
            'heading' => 'print:break-after-avoid',
            'table' => 'print:break-inside-avoid-page',
            default => ''
        };
    }
}
