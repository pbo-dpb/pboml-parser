<?php

namespace PBO\PbomlParser\Parser;

interface SliceProcessorInterface
{
    public function process(array $slice): array;
}
