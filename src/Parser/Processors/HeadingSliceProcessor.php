<?php

namespace PBO\PbomlParser\Parser\Processors;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\BaseSliceProcessor;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\HeadingSliceValidator;

class HeadingSliceProcessor extends BaseSliceProcessor
{
    private HeadingSliceValidator $validator;

    public function process(array $slice): array
    {
        $this->validator = new HeadingSliceValidator;
        if (! $this->validator->validate($slice)) {
            throw new ValidationException(
                'Invalid heading slice: '.implode(', ', $this->validator->getErrorMessages()),
                $this->validator->getErrors()
            );
        }

        return [
            'type' => 'heading',
            'content' => $this->validator->processLocalizedField($slice['content'], 'content'),
            'level' => (int) $slice['level'],
            'id' => $slice['id'] ?? null,
        ];
    }
}
