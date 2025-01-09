<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Traits;

use PBO\PbomlParser\Parser\Validation\Rules\PresentationRule;

trait ValidatesPresentation
{
    protected function validatePresentation(array $data, string $field): bool
    {
        if (isset($data[$field])) {
            $rule = new PresentationRule;
            if (! $rule->passes($field, $data[$field])) {
                $this->addError($rule->message(), [
                    'attribute' => $field,
                    'value' => $data[$field],
                ]);

                return false;
            }
        }

        return true;
    }
}
