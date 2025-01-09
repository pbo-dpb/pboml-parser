<?php

namespace PBO\PbomlParser\Generator\Utils;

class ReferenceProcessor
{
    protected string $locale;

    public function __construct(string $locale = 'en')
    {
        $this->locale = $locale;
    }

    public function process(string $content): string
    {
        $parts = preg_split('/\[\^(\d+)\]/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = array_shift($parts);

        while (count($parts) > 0) {
            $id = array_shift($parts);
            $remaining = array_shift($parts);

            $classes = [
                'pb__annotation-anchor',
                'no-underline',
                'print:no-underline',
                'print:text-gray-800',
                'cursor-pointer',
                'text-blue-800',
                'bg-blue-100',
                'hover:bg-blue-200',
                'dark:text-blue-100',
                'dark:bg-blue-900',
                'dark:hover:bg-blue-700',
                "print:before:content-['[']",
                "print:after:content-[']']",
                'rounded',
                'font-mono',
                'px-0.5',
                'mx-0.5'
            ];

            $result .= sprintf(
                '<a href="#annotation-%s" id="ref-%s" class="%s" aria-describedby="antn_%s">%s</a>%s',
                htmlspecialchars($id, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                htmlspecialchars($id, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                implode(' ', $classes),
                htmlspecialchars($id, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                htmlspecialchars($id, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                $remaining
            );
        }
        return $result;
    }
}
