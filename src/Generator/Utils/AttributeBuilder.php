<?php

namespace PBO\PbomlParser\Generator\Utils;

class AttributeBuilder
{
    protected array $attributes = [];

    public static function make(array $attributes = []): self
    {
        return (new static)->addMany($attributes);
    }

    public function add(string $name, string|array $value): self
    {
        if (isset($this->attributes[$name]) && is_array($value)) {
            if (in_array($name, ['class', 'style'])) {
                $value = array_merge((array) $this->attributes[$name], $value);
            }
        }
        
        $this->attributes[$name] = $value;

        return $this;
    }

    public function addMany(array $attributes): self
    {
        foreach ($attributes as $name => $value) {
            $this->add($name, $value);
        }

        return $this;
    }

    public function addIf(string $name, string|array|null $value, bool|callable $condition): self
    {
        if ($value !== null) {
            if (is_callable($condition)) {
                $shouldAdd = $condition();
            } else {
                $shouldAdd = $condition;
            }

            if ($shouldAdd) {
                return $this->add($name, $value);
            }
        }

        return $this;
    }

    public function data(string $name, mixed $value): self
    {
        return $this->add("data-{$name}", $this->formatDataValue($value));
    }

    public function aria(string $name, string|bool $value): self
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        return $this->add("aria-{$name}", $value);
    }

    public function build(): string
    {
        return collect($this->attributes)
            ->map(function ($value, $name): string {
                if (is_bool($value)) {
                    return $value ? $name : '';
                }

                if (is_array($value)) {
                    $value = $this->formatArrayValue($name, $value);
                }

                return sprintf('%s="%s"', $name, htmlspecialchars($value));
            })
            ->filter()
            ->implode(' ');
    }

    protected function formatArrayValue(string $name, array $value): string
    {
        return match ($name) {
            'class' => implode(' ', $value),
            'style' => collect($value)
                ->map(function (string $value, string $key): string {
                    return "{$key}: {$value};";
                })
                ->implode(' '),
            default => json_encode($value)
        };
    }

    protected function formatDataValue(mixed $value): string
    {
        return match (true) {
            is_array($value) => json_encode($value),
            is_bool($value) => $value ? 'true' : 'false',
            default => (string) $value
        };
    }
}
