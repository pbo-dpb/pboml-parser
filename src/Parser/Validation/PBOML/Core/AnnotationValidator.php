<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Core;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\Validation\Base\BaseValidator;
use PBO\PbomlParser\Parser\Validation\Base\ValidatorInterface;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesLocalization;

class AnnotationValidator extends BaseValidator implements ValidatorInterface
{
    use ValidatesLocalization;

    public const SUPPORTED_CONTENT_TYPES = ['markdown', 'bibtex'];

    public function validate(array $annotations): bool
    {
        $ids = [];

        foreach ($annotations as $index => $annotation) {
            if (! $this->validateRequiredFields($annotation, $index)) {
                return false;
            }

            if (! $this->validateUniqueId($annotation['id'], $ids, $index)) {
                return false;
            }
            $ids[] = $annotation['id'];

            if (! $this->validateContentType($annotation, $index)) {
                return false;
            }

            if (! $this->validateContent($annotation, $index)) {
                return false;
            }

            if ($this->isStrictMode() && ! $this->validateStrict($annotation, $index)) {
                return false;
            }
        }

        return true;
    }

    protected function validateRequiredFields(array $annotation, int $index): bool
    {
        $required = ['id', 'content_type', 'content'];
        $missing = [];

        foreach ($required as $field) {
            if (! isset($annotation[$field])) {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            $this->addError(
                "Missing required fields in annotation at index {$index}",
                ['missing_fields' => $missing]
            );

            return false;
        }

        return true;
    }

    protected function validateUniqueId(string $id, array $existingIds, int $index): bool
    {
        if (in_array($id, $existingIds)) {
            $this->addError(
                "Duplicate annotation ID '{$id}' at index {$index}",
                ['id' => $id]
            );

            return false;
        }

        return true;
    }

    protected function validateContentType(array $annotation, int $index): bool
    {
        if (! in_array($annotation['content_type'], self::SUPPORTED_CONTENT_TYPES)) {
            $this->addError(
                "Invalid content type '{$annotation['content_type']}' at index {$index}",
                [
                    'content_type' => $annotation['content_type'],
                    'supported_types' => self::SUPPORTED_CONTENT_TYPES,
                ]
            );

            return false;
        }

        return true;
    }

    protected function validateContent(array $annotation, int $index): bool
    {
        if (! $this->validateLocalizedContent($annotation['content'], "annotation {$index}")) {
            return false;
        }

        switch ($annotation['content_type']) {
            case 'bibtex':
                return $this->validateBibtexContent($annotation['content'], $index);
            case 'markdown':
                return $this->validateMarkdownContent($annotation['content'], $index);
            default:
                return true;
        }
    }

    protected function validateBibtexContent(array $content, int $index): bool
    {
        foreach (['en', 'fr'] as $lang) {
            if (! $this->isValidBibtex($content[$lang])) {
                $this->addError(
                    "Invalid BibTeX format in {$lang} content at index {$index}",
                    ['content' => $content[$lang]]
                );

                return false;
            }
        }

        return true;
    }

    protected function validateMarkdownContent(array $content, int $index): bool
    {
        foreach (['en', 'fr'] as $lang) {
            if (empty(trim($content[$lang]))) {
                $this->addError(
                    "Empty markdown content in {$lang} at index {$index}",
                    ['language' => $lang]
                );

                return false;
            }
        }

        return true;
    }

    protected function isValidBibtex(string $content): bool
    {
        return (bool) preg_match('/^@\w+\s*{\s*[\w\-:]+\s*,/m', $content);
    }

    protected function validateStrict(array $annotation, int $index): bool
    {
        return true;
    }

    protected bool $strictMode = false;

    protected array $errors = [];

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
}
