<?php

namespace PBO\PbomlParser\Generator\Renderers;

interface SliceRenderer
{
    public function render(array $slice): string;
}
