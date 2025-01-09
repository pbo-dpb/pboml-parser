<?php

namespace PBO\PbomlParser\Parser\Processors;

use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Generator\MarkdownConverter;
use PBO\PbomlParser\Parser\Validation\PBOML\Core\AnnotationValidator;

class AnnotationProcessor
{
    protected MarkdownConverter $markdown;

    protected AnnotationValidator $validator;

    public function __construct(MarkdownConverter $markdown)
    {
        $this->markdown = $markdown;
        $this->validator = new AnnotationValidator;
    }

    public function setValidator(AnnotationValidator $validator): void
    {
        $this->validator = $validator;
    }

    public function process(array $annotation): array
    {
        if (! $this->validator->validate([$annotation])) {
            throw new ValidationException(
                'Invalid annotation: '.implode(', ', $this->validator->getErrorMessages()),
                $this->validator->getErrors()
            );
        }

        return [
            'id' => $annotation['id'],
            'content_type' => $annotation['content_type'],
            'content' => $this->processContent($annotation['content'], $annotation['content_type']),
        ];
    }

    public function processAnnotations(array $annotations): array
    {
        if (! $this->validator->validate($annotations)) {
            throw new ValidationException(
                'Invalid annotations: '.implode(', ', $this->validator->getErrorMessages()),
                $this->validator->getErrors()
            );
        }

        return array_map([$this, 'process'], $annotations);
    }

    protected function processContent(array $content, string $contentType): array
    {
        $processed = [];

        foreach ($content as $lang => $text) {
            $processed[$lang] = match ($contentType) {
                'markdown' => $this->markdown->convert($text),
                'bibtex' => $text,
            };
        }

        return $processed;
    }
}
