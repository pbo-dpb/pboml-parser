<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Slices;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\Validation\Base\BaseValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\Contracts\SliceValidatorInterface;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesLocalization;

class HeadingSliceValidator extends BaseValidator implements SliceValidatorInterface
{
    use ValidatesLocalization;

    protected bool $strictMode = false;

    protected const VALID_LEVELS = [0, 1, 2, 3];

    protected const DANGEROUS_HTML_TAGS = ['<script', '<iframe', '<object', '<embed'];

    public function getSliceType(): string
    {
        return 'heading';
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

        if (! $this->validateLevel($slice)) {
            return false;
        }

        if (! $this->validateNoUnsafeHtml($slice['content'])) {
            return false;
        }

        if ($this->strictMode) {
            if (! $this->validateStrictMode($slice)) {
                return false;
            }
        }

        return true;
    }

    protected function validateRequiredFields(array $slice): bool
    {
        $requiredFields = ['type', 'content', 'level'];
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

    protected function validateLevel(array $slice): bool
    {
        if (! in_array($slice['level'], self::VALID_LEVELS)) {
            $this->addError('Invalid heading level', [
                'level' => $slice['level'],
                'valid_levels' => self::VALID_LEVELS,
            ]);

            return false;
        }

        return true;
    }

    protected function validateNoUnsafeHtml(array $content): bool
    {
        foreach ($content as $lang => $text) {
            foreach (self::DANGEROUS_HTML_TAGS as $tag) {
                if (stripos($text, $tag) !== false) {
                    $this->addError("Unsafe HTML detected in {$lang} content", [
                        'language' => $lang,
                        'content' => $text,
                        'unsafe_tag' => $tag,
                    ]);

                    return false;
                }
            }
        }

        return true;
    }

    protected function validateStrictMode(array $slice): bool
    {
        foreach (['en', 'fr'] as $lang) {
            if (strlen($slice['content'][$lang]) > 100) {
                $this->addError("Heading content too long in {$lang}", [
                    'language' => $lang,
                    'content' => $slice['content'][$lang],
                    'max_length' => 100,
                ]);

                return false;
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
            $context = isset($error['context']) ? json_encode($error['context']) : '';

            return $error['message'].($context ? " Context: {$context}" : '');
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
