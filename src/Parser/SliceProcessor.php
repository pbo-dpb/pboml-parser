<?php

namespace PBO\PbomlParser\Parser;

use Exception;
use PBO\PbomlParser\Exceptions\ParsingException;
use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\Processors\BitmapSliceProcessor;
use PBO\PbomlParser\Parser\Processors\ChartSliceProcessor;
use PBO\PbomlParser\Parser\Processors\HeadingSliceProcessor;
use PBO\PbomlParser\Parser\Processors\HTMLSliceProcessor;
use PBO\PbomlParser\Parser\Processors\KeyValueSliceProcessor;
use PBO\PbomlParser\Parser\Processors\MarkdownSliceProcessor;
use PBO\PbomlParser\Parser\Processors\SVGSliceProcessor;
use PBO\PbomlParser\Parser\Processors\TableSliceProcessor;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\Factory\SliceValidatorFactory;

class SliceProcessor extends BaseSliceProcessor
{
    protected SliceValidatorFactory $validatorFactory;

    protected array $processors = [];

    protected bool $strictMode = false;

    public function __construct()
    {
        $this->validatorFactory = new SliceValidatorFactory;
        $this->registerDefaultProcessors();
    }

    public function setStrictMode(bool $strict): self
    {
        $this->strictMode = $strict;
        foreach ($this->processors as $processor) {
            $processor->setStrictMode($strict);
        }

        return $this;
    }

    public function process(array $slices): array
    {
        try {
            return array_map(function ($slice) {
                return $this->processSlice($slice);
            }, $slices);
        } catch (Exception $e) {
            throw new ParsingException(
                'Slice processing failed: '.$e->getMessage(),
                ['slice_count' => count($slices)],
                $e
            );
        }
    }

    protected function processSlice(array $slice): array
    {
        $this->validateRequiredFields($slice, ['type']);

        $validator = $this->validatorFactory->make($slice['type']);
        if (! $validator->validate($slice)) {
            throw new ValidationException(
                "Validation failed for slice type '{$slice['type']}'",
                $validator->getErrors()
            );
        }

        if (! isset($this->processors[$slice['type']])) {
            throw new ValidationException(
                "No processor registered for slice type: {$slice['type']}",
                ['available_types' => array_keys($this->processors)]
            );
        }

        return $this->processors[$slice['type']]->process($slice);
    }

    public function registerProcessor(string $type, $processor): void
    {
        $this->processors[$type] = $processor;
    }

    protected function registerDefaultProcessors(): void
    {
        $this->processors = [
            'markdown' => new MarkdownSliceProcessor(),
            'heading' => new HeadingSliceProcessor(),
            'table' => new TableSliceProcessor(),
            'svg' => new SVGSliceProcessor(),
            'chart' => new ChartSliceProcessor(),
            'kvlist' => new KeyValueSliceProcessor(),
            'bitmap' => new BitmapSliceProcessor(),
            'html' => new HTMLSliceProcessor(),
        ];
    }
}
