<?php

namespace PBO\PbomlParser\Parser\Processors;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\BaseSliceProcessor;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\MarkdownSliceValidator;

class MarkdownSliceProcessor extends BaseSliceProcessor
{
    private MarkdownSliceValidator $validator;

    public function process(array $slice): array
    {
        $this->validator = new MarkdownSliceValidator;
        if (! $this->validator->validate($slice)) {
            throw new ValidationException(
                'Invalid markdown slice: ' . implode(', ', $this->validator->getErrorMessages()),
                $this->validator->getErrors()
            );
        }

        $processed = [
            'type' => 'markdown',
            'content' => $this->validator->processLocalizedField($slice['content'], 'content'),
        ];

        if (isset($slice['label'])) {
            $processed['label'] = $this->validator->processLocalizedField($slice['label'], 'label');
            $processed['display_label'] = $slice['display_label'] ?? true;
        }

        if (isset($slice['presentation'])) {
            $processed['presentation'] = $slice['presentation'];
        }

        foreach (['sources', 'notes', 'alts'] as $field) {
            if (!empty($slice[$field])) {
                $processed[$field] = array_map(
                    fn($item) => $this->validator->processLocalizedField($item, $field),
                    $slice[$field]
                );
            }
        }

        foreach (['annotation_id', 'referenced_as', 'bold'] as $field) {
            if (isset($slice[$field])) {
                $processed[$field] = $slice[$field];
            }
        }

        return $processed;
    }
}
