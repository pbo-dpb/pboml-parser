<?php

namespace PBO\PbomlParser\Parser\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

class VersionRule implements Rule
{
    protected $supportedVersions = ['1.0.0'];

    public function passes($attribute, $value)
    {
        return in_array($value, $this->supportedVersions);
    }

    public function message()
    {
        return 'The :attribute must be one of the supported PBOML versions: '.implode(', ', $this->supportedVersions);
    }
}
