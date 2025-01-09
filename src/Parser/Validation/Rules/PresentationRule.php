<?php

namespace PBO\PbomlParser\Parser\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

class PresentationRule implements Rule
{
    protected $allowedValues = ['', 'figure', 'aside'];

    public function passes($attribute, $value)
    {
        return in_array($value, $this->allowedValues);
    }

    public function message()
    {
        return 'The :attribute must be one of the following values: '.implode(', ', $this->allowedValues);
    }
}
