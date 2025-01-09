<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Slices\Factory;

use PBO\PbomlParser\Parser\Validation\PBOML\Slices\BitmapSliceValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\ChartSliceValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\Contracts\SliceValidatorInterface;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\HeadingSliceValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\HTMLSliceValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\KeyValueSliceValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\MarkdownSliceValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\SVGSliceValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\TableSliceValidator;

class SliceValidatorFactory
{
    public function make(string $sliceType): SliceValidatorInterface
    {
        return match ($sliceType) {
            'markdown' => new MarkdownSliceValidator,
            'table' => new TableSliceValidator,
            'svg' => new SVGSliceValidator,
            'chart' => new ChartSliceValidator,
            'kvlist' => new KeyValueSliceValidator,
            'bitmap' => new BitmapSliceValidator,
            'html' => new HTMLSliceValidator,
            'heading' => new HeadingSliceValidator,
            default => throw new \InvalidArgumentException("Unsupported slice type: {$sliceType}"),
        };
    }
}
