<?php

namespace PBO\PbomlParser\Parser\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

class SliceTypeRule implements Rule
{
    protected $allowedTypes = [
        'markdown',
        'table',
        'svg',
        'chart',
        'kvlist',
        'bitmap',
        'html',
    ];

    public function passes($attribute, $value)
    {
        return in_array($value, $this->allowedTypes);
    }

    public function message()
    {
        return 'The :attribute must be one of the following slice types: '.implode(', ', $this->allowedTypes);
    }
}
