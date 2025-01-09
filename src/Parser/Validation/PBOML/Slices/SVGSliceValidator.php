<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Slices;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\Validation\Base\BaseValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\Contracts\SliceValidatorInterface;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesLocalization;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesPresentation;

class SVGSliceValidator extends BaseValidator implements SliceValidatorInterface
{
    use ValidatesLocalization, ValidatesPresentation;

    protected bool $strictMode = false;

    protected const ALLOWED_SVG_TAGS = [
        'svg',
        'g',
        'path',
        'rect',
        'circle',
        'ellipse',
        'line',
        'polyline',
        'polygon',
        'text',
        'tspan',
        'defs',
        'clipPath',
        'use',
        'title',
        'desc',
    ];

    public function getSliceType(): string
    {
        return 'svg';
    }

    public function validate(array $slice): bool
    {
        $this->clearErrors();

        if (! $this->validateRequiredFields($slice)) {
            return false;
        }

        if (! $this->validateLocalizedContent($slice['content'], 'content')) {
            return false;
        }

        foreach ($slice['content'] as $lang => $content) {
            if (! $this->validateSVGContent($content, $lang)) {
                return false;
            }
        }

        if (! $this->validatePresentation($slice, 'presentation')) {
            return false;
        }

        return true;
    }

    protected function validateRequiredFields(array $slice): bool
    {
        $requiredFields = ['type', 'content'];
        foreach ($requiredFields as $field) {
            if (! isset($slice[$field])) {
                $this->addError("Missing required field: {$field}", [
                    'field' => $field,
                    'slice' => $slice,
                ]);

                return false;
            }
        }

        return true;
    }

    protected function validateSVGContent(string $svg, string $lang): bool
    {
        if (trim($svg) === '') {
            $this->addError("Empty SVG content in {$lang}", [
                'language' => $lang,
            ]);

            return false;
        }

        if (! preg_match('/<svg[^>]*>.*<\/svg>/s', $svg)) {
            $this->addError("Invalid SVG structure in {$lang} content", [
                'language' => $lang,
            ]);

            return false;
        }

        if (preg_match('/<script\b[^>]*>.*<\/script>/is', $svg)) {
            $this->addError("SVG contains <script> tags in {$lang} content, which are not allowed", [
                'language' => $lang,
            ]);

            return false;
        }

        if (preg_match('/\bon\w+="[^"]*"/i', $svg)) {
            $this->addError("SVG contains event handlers in {$lang} content, which are not allowed", [
                'language' => $lang,
            ]);

            return false;
        }

        if (preg_match('/\bxlink:href="(?!#)[^"]*"/i', $svg)) {
            $this->addError("SVG contains external resources in {$lang} content, which are not allowed", [
                'language' => $lang,
            ]);

            return false;
        }

        if (! preg_match('/<svg[^>]*\b(viewBox|xmlns)="[^"]*"[^>]*>/', $svg)) {
            $this->addError("SVG is missing required attributes (viewBox or xmlns) in {$lang} content", [
                'language' => $lang,
            ]);

            return false;
        }

        return true;
    }

    protected function validateViewBox(string $svg): bool
    {
        return (bool) preg_match('/\bviewBox="[^"]*"/', $svg);
    }

    public function setStrictMode(bool $strict): self
    {
        $this->strictMode = $strict;

        return $this;
    }

    public function isStrictMode(): bool
    {
        return $this->strictMode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    public function clearErrors(): void
    {
        $this->errors = [];
    }

    public function getErrorMessages(): array
    {
        return array_map(function ($error) {
            return $error['message'];
        }, $this->errors);
    }

    public function getErrorContexts(): array
    {
        return array_map(function ($error) {
            return $error['context'] ?? [];
        }, $this->errors);
    }

    public function processLocalizedField(array $content, string $field): array
    {
        if (! $this->validateLocalizedContent($content, $field)) {
            throw new ValidationException(
                "Invalid localized content for field: {$field}",
                ['field' => $field, 'content' => $content]
            );
        }

        return [
            'en' => $content['en'],
            'fr' => $content['fr'],
        ];
    }
}
