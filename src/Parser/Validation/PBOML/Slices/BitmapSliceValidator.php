<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Slices;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\Validation\Base\BaseValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\Contracts\SliceValidatorInterface;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesLocalization;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesPresentation;

class BitmapSliceValidator extends BaseValidator implements SliceValidatorInterface
{
    use ValidatesLocalization, ValidatesPresentation;

    protected bool $strictMode = false;

    protected const REQUIRED_FORMATS = [
        'sm_1x_webp',
        'sm_1x_png',
        'sm_2x_webp',
        'sm_2x_png',
        'md_1x_webp',
        'md_1x_png',
        'md_2x_webp',
        'md_2x_png',
        'lg_1x_webp',
        'lg_1x_png',
        'lg_2x_webp',
        'lg_2x_png',
    ];

    public function getSliceType(): string
    {
        return 'bitmap';
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

        if (! $this->validateThumbnails($slice['thumbnails'])) {
            return false;
        }

        if (! $this->validatePresentation($slice, 'presentation')) {
            return false;
        }

        return true;
    }

    protected function validateRequiredFields(array $slice): bool
    {
        $requiredFields = ['type', 'content', 'thumbnails'];
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

    protected function validateThumbnails(array $thumbnails): bool
    {
        foreach (['en', 'fr'] as $locale) {
            if (! isset($thumbnails[$locale])) {
                $this->addError("Missing thumbnails for locale: {$locale}");

                return false;
            }

            $missingFormats = $this->getMissingFormats($thumbnails[$locale]);
            if (! empty($missingFormats)) {
                $this->addError("Missing required thumbnail formats for {$locale}", [
                    'locale' => $locale,
                    'missing_formats' => $missingFormats,
                ]);

                return false;
            }

            $invalidFormats = array_diff(
                array_keys($thumbnails[$locale]),
                self::REQUIRED_FORMATS
            );
            if (! empty($invalidFormats)) {
                $this->addError('Invalid thumbnail format names', [
                    'invalid_formats' => $invalidFormats,
                ]);

                return false;
            }

            foreach ($thumbnails[$locale] as $format => $url) {
                if (! $this->validateUrl($url)) {
                    $this->addError('Invalid URL format for thumbnail', [
                        'locale' => $locale,
                        'format' => $format,
                        'url' => $url,
                    ]);

                    return false;
                }
            }
        }

        return true;
    }

    protected function getMissingFormats(array $thumbnails): array
    {
        return array_filter(
            self::REQUIRED_FORMATS,
            fn ($format) => ! isset($thumbnails[$format])
        );
    }

    protected function validateUrl(string $url): bool
    {
        if (str_starts_with(strtolower($url), 'javascript:')) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false ||
            preg_match('/^[\w\-\.\/]+\.(jpg|jpeg|png|gif|webp)$/i', $url) === 1;
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
