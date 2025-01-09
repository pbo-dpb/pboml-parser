<?php

namespace PBO\PbomlParser\Generator;

use Normalizer;
use PBO\PbomlParser\Exceptions\EncodingException;

trait EncodingHandler
{
    protected const COMMON_FRENCH_ENCODINGS = [
        'UTF-8',
        'ISO-8859-1',
        'ISO-8859-15',
        'Windows-1252',
        'macintosh',
    ];

    protected const FRENCH_SPECIAL_CHARS = [
        'é',
        'è',
        'ê',
        'ë',
        'à',
        'â',
        'î',
        'ï',
        'ô',
        'ö',
        'ù',
        'û',
        'ü',
        'ÿ',
        'ç',
        'œ',
        'É',
        'È',
        'Ê',
        'Ë',
        'À',
        'Â',
        'Î',
        'Ï',
        'Ô',
        'Ö',
        'Ù',
        'Û',
        'Ü',
        'Ÿ',
        'Ç',
        'Œ',
    ];

    protected function ensureUtf8s(string $str, bool $strict = false): string
    {
        $str = $this->fixDoubleEncoding($str);
        if (mb_check_encoding($str, 'UTF-8')) {
            $normalized = Normalizer::normalize($str, Normalizer::FORM_C);
            if ($strict && ! $this->validateFrenchCharacters($normalized)) {
                throw new EncodingException('String contains invalid or corrupted French characters');
            }

            return $normalized;
        }

        $detectedEncoding = mb_detect_encoding($str, static::COMMON_FRENCH_ENCODINGS, true);

        if (! $detectedEncoding) {
            foreach (static::COMMON_FRENCH_ENCODINGS as $encoding) {
                $converted = @mb_convert_encoding($str, 'UTF-8', $encoding);
                if ($this->validateFrenchCharacters($converted)) {
                    $str = $converted;
                    $detectedEncoding = $encoding;
                    break;
                }
            }

            if (! $detectedEncoding) {
                throw new EncodingException(
                    'Unable to detect proper encoding for French characters'
                );
            }
        }

        $converted = mb_convert_encoding($str, 'UTF-8', $detectedEncoding);

        $normalized = Normalizer::normalize($converted, Normalizer::FORM_C);

        if ($strict && ! $this->validateFrenchCharacters($normalized)) {
            throw new EncodingException(
                "Failed to properly convert French characters from {$detectedEncoding}"
            );
        }

        return $normalized;
    }

    protected function fixDoubleEncoding(string $str): string
    {
        $previousStr = '';
        while ($str !== $previousStr && mb_check_encoding($str, 'UTF-8') && mb_check_encoding(mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8'), 'UTF-8')) {
            $previousStr = $str;
            $str = mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
        }

        return Normalizer::normalize($str, Normalizer::FORM_C);
    }

    protected function validateFrenchCharacters(string $str): bool
    {
        if (Normalizer::normalize($str, Normalizer::FORM_D) === false) {
            return false;
        }

        foreach (static::FRENCH_SPECIAL_CHARS as $char) {
            if (strpos($str, $char) !== false) {
                $normalized = Normalizer::normalize($char, Normalizer::FORM_C);
                if ($normalized === false) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function safeHtmlEncode(string $str): string
    {
        $str = $this->handleFrenchText($str);

        return htmlspecialchars(
            $str,
            ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
            'UTF-8',
            false
        );
    }

    protected function generateMetaTags(): string
    {
        return <<<'HTML'
        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta http-equiv="Content-Language" content="fr">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        HTML;
    }

    protected function handleFrenchText(string $str): string
    {
        if (!mb_check_encoding($str, 'UTF-8')) {
            foreach (['UTF-8', 'ISO-8859-1', 'ISO-8859-15', 'Windows-1252'] as $encoding) {
                $converted = mb_convert_encoding($str, 'UTF-8', $encoding);
                if ($this->validateFrenchCharacters($converted)) {
                    $str = $converted;
                    break;
                }
            }
        }

        $str = Normalizer::normalize($str, Normalizer::FORM_C);

        return $str;
    }

}
