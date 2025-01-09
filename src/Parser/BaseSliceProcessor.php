<?php

namespace PBO\PbomlParser\Parser;

use PBO\PbomlParser\Exceptions\ValidationException;

abstract class BaseSliceProcessor
{
    protected bool $strictMode = false;

    public function setStrictMode(bool $strict): self
    {
        $this->strictMode = $strict;

        return $this;
    }

    protected function validateRequiredFields(array $slice, array $requiredFields, string $context = ''): void
    {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (! array_key_exists($field, $slice)) {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            $contextMsg = $context ? " in {$context}" : '';
            throw new ValidationException(
                sprintf(
                    'Missing required fields%s: %s',
                    $contextMsg,
                    implode(', ', $missing)
                ),
                ['missing_fields' => $missing, 'slice' => $slice]
            );
        }
    }

    abstract public function process(array $slice): array;
}
