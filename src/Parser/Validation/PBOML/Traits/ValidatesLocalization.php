<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Traits;

trait ValidatesLocalization
{
    protected array $supportedLanguages = ['en', 'fr'];

    protected function validateLocalizedContent(array $content, string $context): bool
    {
        foreach ($this->supportedLanguages as $lang) {
            if (! isset($content[$lang])) {
                $this->addError(
                    "Missing {$lang} translation in {$context}",
                    [
                        'context' => $context,
                        'language' => $lang,
                        'content' => $content,
                    ]
                );

                return false;
            }
        }

        foreach ($this->supportedLanguages as $lang) {
            $value = $content[$lang];

            if (is_array($value)) {
                $value = implode('', $value);
            }

            if (empty(trim((string) $value))) {
                $this->addError(
                    "Empty content for {$lang} in {$context}",
                    [
                        'context' => $context,
                        'language' => $lang,
                    ]
                );

                return false;
            }
        }

        $extraLanguages = array_diff(array_keys($content), $this->supportedLanguages);
        if (! empty($extraLanguages)) {
            $this->addError(
                "Unsupported languages found in {$context}",
                [
                    'context' => $context,
                    'unsupported_languages' => $extraLanguages,
                ]
            );

            return false;
        }

        return true;
    }

    protected function validateLocalizedArray(array $items, string $context): bool
    {
        foreach ($items as $index => $item) {
            if (! $this->validateLocalizedContent($item, "{$context} at index {$index}")) {
                return false;
            }
        }

        return true;
    }

    protected function validateLocalizedField($field, string $fieldName): bool
    {
        if (! is_array($field)) {
            $this->addError(
                "Field {$fieldName} must be a localized string object",
                [
                    'field' => $fieldName,
                    'value' => $field,
                ]
            );

            return false;
        }

        return $this->validateLocalizedContent($field, $fieldName);
    }

    protected function hasLanguage(array $content, string $language): bool
    {
        return isset($content[$language]) && ! empty(trim((string) $content[$language]));
    }

    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    protected function isLanguageSupported(string $language): bool
    {
        return in_array($language, $this->supportedLanguages);
    }

    protected function addSupportedLanguage(string $language): void
    {
        if (! $this->isLanguageSupported($language)) {
            $this->supportedLanguages[] = $language;
        }
    }
}
