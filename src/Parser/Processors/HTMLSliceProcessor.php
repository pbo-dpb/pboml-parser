<?php

namespace PBO\PbomlParser\Parser\Processors;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\BaseSliceProcessor;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\HTMLSliceValidator;

class HTMLSliceProcessor extends BaseSliceProcessor
{
    private HTMLSliceValidator $validator;

    public function process(array $slice): array
    {
        $this->validator = new HTMLSliceValidator;
        if (! $this->validator->validate($slice)) {
            throw new ValidationException(
                'Invalid HTML slice: '.implode(', ', $this->validator->getErrorMessages()),
                $this->validator->getErrors()
            );
        }

        return [
            'type' => 'html',
            'content' => $this->validator->processLocalizedField($slice['content'], 'content'),
            'remove_default_styles' => $slice['remove_default_styles'] ?? false,
            'css' => $slice['css'] ?? null,
            'label' => $slice['label'] ?? null,
            'display_label' => $slice['display_label'] ?? true,
            'presentation' => $slice['presentation'] ?? null,
            'sources' => $slice['sources'] ?? [],
            'notes' => $slice['notes'] ?? [],
            'referenced_as' => $slice['referenced_as'] ?? null,
            'alts' => $slice['alts'] ?? [],
        ];
    }
}
