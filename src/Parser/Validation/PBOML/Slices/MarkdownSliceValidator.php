<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Slices;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\Validation\Base\BaseValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\Contracts\SliceValidatorInterface;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesLocalization;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesPresentation;

class MarkdownSliceValidator extends BaseValidator implements SliceValidatorInterface
{
    use ValidatesLocalization, ValidatesPresentation;

    protected bool $strictMode = false;

    protected const DANGEROUS_PATTERNS = [
        '/<script\b[^>]*>.*?<\/script>/is',
        '/on\w+\s*=\s*["\'][^"\']*["\']/',
        '/<iframe\b[^>]*>.*?<\/iframe>/is',
        '/javascript:/i',
        '/data:/i',
        '/vbscript:/i',
    ];

    public function getSliceType(): string
    {
        return 'markdown';
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

        if (! $this->validateContentSecurity($slice['content'])) {
            return false;
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
                $this->addError("The '{$field}' field is required for markdown slices.", [
                    'field' => $field,
                    'slice' => $slice,
                ]);

                return false;
            }
        }

        return true;
    }

    protected function validateContentSecurity(array $content): bool
    {
        foreach (['en', 'fr'] as $lang) {
            $text = $content[$lang];

            foreach (self::DANGEROUS_PATTERNS as $pattern) {
                if (preg_match($pattern, $text)) {
                    $this->addError("Potentially dangerous content detected in {$lang} content", [
                        'language' => $lang,
                        'pattern' => $pattern,
                        'content' => $text,
                    ]);

                    return false;
                }
            }
        }

        return true;
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

    protected function addError(string $message, array $context = []): void
    {
        $this->errors[] = [
            'message' => $message,
            'context' => $context,
        ];
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
