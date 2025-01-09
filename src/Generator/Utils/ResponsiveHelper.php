<?php

namespace PBO\PbomlParser\Generator\Utils;

class ResponsiveHelper
{
    protected static array $breakpoints = [
        'sm' => 640,
        'md' => 768,
        'lg' => 1024,
        'xl' => 1280,
        '2xl' => 1536,
    ];

    public static function responsive(array $config, string $prefix = ''): string
    {
        return collect($config)
            ->map(function ($value, string $breakpoint) use ($prefix) {
                if ($breakpoint === 'default') {
                    return $prefix ? "{$prefix}-{$value}" : $value;
                }

                $prefixedValue = $prefix ? "{$prefix}-{$value}" : $value;

                return "{$breakpoint}:{$prefixedValue}";
            })
            ->implode(' ');
    }

    public static function container(array $config, string $name = 'slice'): string
    {
        return collect($config)
            ->map(fn ($value, int $size) => "@container/{$name} ({$size}px) {$value}")
            ->implode(' ');
    }

    public static function aspect(int|float $width, int|float $height): string
    {
        return "aspect-[{$width}/{$height}]";
    }

    public static function cols(int|string $columns): string
    {
        if (is_numeric($columns)) {
            return "grid-cols-{$columns}";
        }

        return match ($columns) {
            'auto' => 'grid-cols-auto',
            'full' => 'grid-cols-1',
            '1/2' => 'grid-cols-2',
            '1/3' => 'grid-cols-3',
            '1/4' => 'grid-cols-4',
            default => 'grid-cols-12'
        };
    }

    public static function media(string $breakpoint, string $type = 'min'): string
    {
        $width = static::$breakpoints[$breakpoint] ?? 0;

        return "@media ({$type}-width: {$width}px)";
    }

    public static function setBreakpoints(array $breakpoints): void
    {
        static::$breakpoints = $breakpoints;
    }

    public static function getBreakpoint(string $name): ?int
    {
        return static::$breakpoints[$name] ?? null;
    }

    public static function matches(string $breakpoint): bool
    {
        if ($width = static::getBreakpoint($breakpoint)) {
            return $width <= self::getCurrentWidth();
        }

        return false;
    }

    protected static function getCurrentWidth(): int
    {
        return $_COOKIE['vw'] ?? 1024;
    }
}
