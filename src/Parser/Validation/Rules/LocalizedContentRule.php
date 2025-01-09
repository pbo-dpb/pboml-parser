<?php

namespace PBO\PbomlParser\Parser\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

class LocalizedContentRule implements Rule
{
    public function passes($attribute, $value)
    {
        return isset($value['en']) && isset($value['fr']) &&
            is_string($value['en']) && is_string($value['fr']) &&
            ! empty(trim($value['en'])) && ! empty(trim($value['fr']));
    }

    public function message()
    {
        return 'The :attribute must contain non-empty localized content for both English (en) and French (fr).';
    }
}
