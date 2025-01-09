<?php

namespace PBO\PbomlParser\Parser\Processors;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\BaseSliceProcessor;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\SVGSliceValidator;

class SVGSliceProcessor extends BaseSliceProcessor
{
    private SVGSliceValidator $validator;

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

    public function process(array $slice): array
    {
        $this->validator = new SVGSliceValidator;
        if (! $this->validator->validate($slice)) {
            throw new ValidationException(
                'Invalid SVG slice: '.implode(', ', $this->validator->getErrorMessages()),
                $this->validator->getErrors()
            );
        }

        $content = $this->validator->processLocalizedField($slice['content'], 'content');

        // Sanitize SVG content
        foreach (['en', 'fr'] as $lang) {
            $content[$lang] = $this->sanitizeSVG($content[$lang]);
        }

        return [
            'type' => 'svg',
            'content' => $content,
            'label' => isset($slice['label']) ?
                $this->validator->processLocalizedField($slice['label'], 'label') : null,
            'display_label' => $slice['display_label'] ?? true,
            'presentation' => $slice['presentation'] ?? null,
            'sources' => isset($slice['sources']) ?
                array_map(fn ($source) => $this->validator->processLocalizedField($source, 'source'), $slice['sources']) : [],
            'notes' => isset($slice['notes']) ?
                array_map(fn ($note) => $this->validator->processLocalizedField($note, 'note'), $slice['notes']) : [],
            'referenced_as' => isset($slice['referenced_as']) ?
                $this->validator->processLocalizedField($slice['referenced_as'], 'referenced_as') : null,
            'alts' => isset($slice['alts']) ?
                array_map(fn ($alt) => $this->validator->processLocalizedField($alt, 'alt'), $slice['alts']) : [],
        ];
    }

    protected function sanitizeSVG(string $svg): string
    {
        // Remove scripts
        $svg = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $svg);

        // Remove event handlers
        $svg = preg_replace('/\bon\w+="[^"]*"/i', '', $svg);

        // Remove external resources
        $svg = preg_replace('/\bxlink:href="(?!#)[^"]*"/i', '', $svg);

        // Ensure SVG namespace
        if (! str_contains($svg, 'xmlns="http://www.w3.org/2000/svg"')) {
            $svg = preg_replace('/<svg\b/', '<svg xmlns="http://www.w3.org/2000/svg"', $svg);
        }

        return $svg;
    }
}
