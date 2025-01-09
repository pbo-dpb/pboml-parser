<?php

namespace PBO\PbomlParser\Generator\Utils;

class ClassBuilder
{
    protected array $classes = [];

    protected array $conditionals = [];

    public static function make(string|array $classes = []): self
    {
        return (new static)->add($classes);
    }

    public function add(string|array $classes): self
    {
        if (is_string($classes)) {
            $classes = explode(' ', $classes);
        }

        $this->classes = array_merge($this->classes, array_filter($classes));

        return $this;
    }

    public function addIf(string|array $classes, bool|callable $condition): self
    {
        $this->conditionals[] = [
            'classes' => $classes,
            'condition' => $condition,
        ];

        return $this;
    }

    public function dark(string|array $classes): self
    {
        $darkClasses = collect((array) $classes)
            ->map(fn ($class) => "dark:{$class}")
            ->toArray();

        return $this->add($darkClasses);
    }

    public function print(string|array $classes): self
    {
        $printClasses = collect((array) $classes)
            ->map(fn ($class) => "print:{$class}")
            ->toArray();

        return $this->add($printClasses);
    }

    public function responsive(array $breakpoints): self
    {
        foreach ($breakpoints as $breakpoint => $classes) {
            if ($breakpoint === 'default') {
                $this->add($classes);
            } else {
                $this->add(
                    collect((array) $classes)
                        ->map(fn ($class) => "{$breakpoint}:{$class}")
                        ->toArray()
                );
            }
        }

        return $this;
    }

    public function build(): string
    {
        foreach ($this->conditionals as $conditional) {
            if (is_callable($conditional['condition'])) {
                $shouldAdd = $conditional['condition']();
            } else {
                $shouldAdd = $conditional['condition'];
            }

            if ($shouldAdd) {
                $this->add($conditional['classes']);
            }
        }

        return implode(' ', array_unique($this->classes));
    }
}
