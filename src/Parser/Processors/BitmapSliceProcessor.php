<?php

namespace PBO\PbomlParser\Parser\Processors;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\BaseSliceProcessor;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\BitmapSliceValidator;

class BitmapSliceProcessor extends BaseSliceProcessor
{
    private BitmapSliceValidator $validator;

    public function process(array $slice): array
    {
        $this->validator = new BitmapSliceValidator;
        if (! $this->validator->validate($slice)) {
            throw new ValidationException(
                'Invalid bitmap slice: '.implode(', ', $this->validator->getErrorMessages()),
                $this->validator->getErrors()
            );
        }

        return [
            'type' => 'bitmap',
            'content' => $this->validator->processLocalizedField($slice['content'], 'content'),
            'thumbnails' => $slice['thumbnails'],
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
