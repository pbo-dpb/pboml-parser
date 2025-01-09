<?php

namespace PBO\PbomlParser\Parser\Processors;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\BaseSliceProcessor;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\KeyValueSliceValidator;

class KeyValueSliceProcessor extends BaseSliceProcessor
{
    private KeyValueSliceValidator $validator;

    public function process(array $slice): array
    {
        $this->validator = new KeyValueSliceValidator;
        if (! $this->validator->validate($slice)) {
            throw new ValidationException(
                'Invalid key-value slice: '.implode(', ', $this->validator->getErrorMessages()),
                $this->validator->getErrors()
            );
        }

        return [
            'type' => 'kvlist',
            'prototype' => $this->processPrototype($slice['prototype']),
            'content' => $this->processKVContent($slice['content']),
            'label' => $slice['label'] ?? null,
            'display_label' => $slice['display_label'] ?? true,
            'presentation' => $slice['presentation'] ?? null,
            'sources' => $slice['sources'] ?? [],
            'notes' => $slice['notes'] ?? [],
        ];
    }

    protected function processPrototype(array $prototype): array
    {
        return [
            'key' => [
                'type' => $prototype['key']['type'] ?? 'markdown',
                'label' => $this->validator->processLocalizedField($prototype['key']['label'], 'labael'),
            ],
            'value' => [
                'type' => $prototype['value']['type'] ?? 'markdown',
                'label' => $this->validator->processLocalizedField($prototype['value']['label'], 'labael'),
            ],
        ];
    }

    protected function processKVContent(array $content): array
    {
        return array_map(function ($pair) {
            return [
                'key' => [
                    'content' => $this->validator->processLocalizedField($pair['key']['content'], 'content'),
                ],
                'value' => [
                    'content' => $this->validator->processLocalizedField($pair['value']['content'], 'content'),
                ],
            ];
        }, $content);
    }
}
