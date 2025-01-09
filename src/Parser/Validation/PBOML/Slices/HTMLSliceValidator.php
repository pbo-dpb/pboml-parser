<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Slices;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\Validation\Base\BaseValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\Contracts\SliceValidatorInterface;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesLocalization;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesPresentation;

class HTMLSliceValidator extends BaseValidator implements SliceValidatorInterface
{
    use ValidatesLocalization, ValidatesPresentation;

    protected bool $strictMode = false;

    protected const ALLOWED_EXTERNAL_DOMAINS = [
        'cdnjs.cloudflare.com',
    ];

    public function getSliceType(): string
    {
        return 'html';
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

        if (! $this->validateHTMLContent($slice['content'])) {
            return false;
        }

        if (isset($slice['css']) && ! $this->validateCSS($slice['css'])) {
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
                $this->addError("Missing required field: {$field}", [
                    'field' => $field,
                    'slice' => $slice,
                ]);

                return false;
            }
        }

        return true;
    }

    protected function validateHTMLContent(array $content): bool
    {
        foreach (['en', 'fr'] as $lang) {
            $html = $content[$lang];

            if (preg_match('/<script\b[^>]*>/', $html)) {
                $this->addError("HTML contains script tags in {$lang} content", [
                    'language' => $lang,
                ]);

                return false;
            }

            if (preg_match('/\bon\w+="/', $html)) {
                $this->addError("HTML contains event handlers in {$lang} content", [
                    'language' => $lang,
                ]);

                return false;
            }

            if (! $this->validateExternalResources($html, $lang)) {
                return false;
            }

            if (! $this->validateImageSources($html, $lang)) {
                return false;
            }
        }

        return true;
    }

    protected function validateExternalResources(string $html, string $lang): bool
    {
        preg_match_all('/\bsrc="([^"]+)"/', $html, $matches);
        foreach ($matches[1] as $src) {
            if ($this->isExternalUrl($src) && ! $this->isAllowedDomain($src)) {
                $this->addError("HTML contains disallowed external resource in {$lang}", [
                    'language' => $lang,
                    'src' => $src,
                    'allowed_domains' => self::ALLOWED_EXTERNAL_DOMAINS,
                ]);

                return false;
            }
        }

        return true;
    }

    protected function validateImageSources(string $html, string $lang): bool
    {
        preg_match_all('/<img[^>]+src="([^"]+)"/', $html, $matches);
        foreach ($matches[1] as $src) {
            if (! preg_match('#^/api/placeholder/\d+/\d+$#', $src) && $this->isExternalUrl($src)) {
                $this->addError("HTML contains non-placeholder image in {$lang}", [
                    'language' => $lang,
                    'src' => $src,
                ]);

                return false;
            }
        }

        return true;
    }

    protected function validateCSS(string $css): bool
    {
        $dangerous = [
            '@import',
            'expression',
            'javascript:',
            'behavior',
            '-moz-binding',
        ];

        foreach ($dangerous as $pattern) {
            if (stripos($css, $pattern) !== false) {
                $this->addError('CSS contains potentially harmful content', [
                    'pattern' => $pattern,
                    'css' => $css,
                ]);

                return false;
            }
        }

        return true;
    }

    protected function isExternalUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    protected function isAllowedDomain(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($host, self::ALLOWED_EXTERNAL_DOMAINS);
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
