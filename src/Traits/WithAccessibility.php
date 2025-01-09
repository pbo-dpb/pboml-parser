<?php

namespace PBO\PbomlParser\Traits;

trait WithAccessibility
{
    protected function withAccessibility(string $content, array $options = []): string
    {
        $attributes = $this->getAccessibilityAttributes($options);

        return $this->addAttributes($content, $attributes);
    }

    protected function getAccessibilityAttributes(array $options = []): array
    {
        $attributes = [];

        if ($role = $options['role'] ?? null) {
            $attributes['role'] = $role;
        }

        if ($label = $options['aria-label'] ?? null) {
            $attributes['aria-label'] = $label;
        }

        if ($describedBy = $options['aria-describedby'] ?? null) {
            $attributes['aria-describedby'] = $describedBy;
        }

        if (isset($options['tabindex'])) {
            $attributes['tabindex'] = $options['tabindex'];
        }

        if (isset($options['hidden'])) {
            $attributes['aria-hidden'] = $options['hidden'] ? 'true' : 'false';
        }

        if (isset($options['expanded'])) {
            $attributes['aria-expanded'] = $options['expanded'] ? 'true' : 'false';
        }

        if (isset($options['required'])) {
            $attributes['aria-required'] = $options['required'] ? 'true' : 'false';
        }

        if ($live = $options['live'] ?? null) {
            $attributes['aria-live'] = $live;
            if ($atomic = $options['atomic'] ?? null) {
                $attributes['aria-atomic'] = $atomic ? 'true' : 'false';
            }
        }

        return $attributes;
    }

    protected function srOnly(string $text): string
    {
        return sprintf(
            '<span class="sr-only">%s</span>',
            htmlspecialchars($text)
        );
    }

    protected function addAttributes(string $content, array $attributes): string
    {
        if (empty($attributes)) {
            return $content;
        }

        $attributeString = collect($attributes)
            ->map(fn ($value, $key) => sprintf('%s="%s"', $key, htmlspecialchars($value)))
            ->implode(' ');

        return preg_replace(
            '/^<(\w+)/',
            '<$1 '.$attributeString,
            $content,
            1
        );
    }

    protected function getHeadingLevel(int $baseLevel = 2): int
    {
        return $baseLevel;
    }

    protected function createSkipLink(string $target, string $text): string
    {
        return sprintf(
            '<a href="#%s" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:p-4 focus:bg-white focus:text-blue-800">%s</a>',
            $target,
            htmlspecialchars($text)
        );
    }

    protected function withKeyboardNav(string $content, array $options = []): string
    {
        $attributes = [
            'tabindex' => $options['tabindex'] ?? '0',
            'role' => $options['role'] ?? 'button',
            'onkeydown' => "if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click()}",
        ];

        return $this->addAttributes($content, $attributes);
    }

    protected function withDescription(string $content, string $description, string $id): string
    {
        return sprintf(
            '%s<div id="%s" class="sr-only">%s</div>',
            $this->addAttributes($content, ['aria-describedby' => $id]),
            $id,
            htmlspecialchars($description)
        );
    }
}
