<?php

namespace PBO\PbomlParser\Parser;

use Exception;
use PBO\PbomlParser\Exceptions\ParsingException;
use PBO\PbomlParser\Exceptions\ValidationException;
use PBO\PbomlParser\Parser\Validation\PBOML\Core\AnnotationValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Core\DocumentMetadataValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Core\RootStructureValidator;
use PBO\PbomlParser\Parser\Validation\PBOML\Slices\Factory\SliceValidatorFactory;
use Symfony\Component\Yaml\Parser as YamlParser;

class PBOMLParser
{
    protected YamlParser $yaml;

    protected RootStructureValidator $rootValidator;

    protected DocumentMetadataValidator $metadataValidator;

    protected AnnotationValidator $annotationValidator;

    protected SliceValidatorFactory $sliceValidatorFactory;

    protected SliceProcessor $sliceProcessor;

    protected bool $strictMode = false;

    public function __construct()
    {
        $this->yaml = new YamlParser;
        $this->rootValidator = new RootStructureValidator;
        $this->metadataValidator = new DocumentMetadataValidator;
        $this->annotationValidator = new AnnotationValidator;
        $this->sliceValidatorFactory = new SliceValidatorFactory;
        $this->sliceProcessor = new SliceProcessor;
    }

    public function setStrictMode(bool $strict): self
    {
        $this->strictMode = $strict;
        $this->rootValidator->setStrictMode($strict);
        $this->metadataValidator->setStrictMode($strict);
        $this->annotationValidator->setStrictMode($strict);

        return $this;
    }

    public function isStrictMode(): bool
    {
        return $this->strictMode;
    }

    public function parse(string $content): array
    {
        try {
            $document = $this->yaml->parse($content);

            if (! $this->rootValidator->validate($document)) {
                throw new ValidationException(
                    'Invalid PBOML document structure: '.implode(', ', $this->rootValidator->getErrorMessages()),
                    $this->rootValidator->getErrors()
                );
            }

            return $this->processDocument($document);
        } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
            throw new ParsingException(
                'Failed to parse YAML content: '.$e->getMessage(),
                ['line' => $e->getLine(), 'snippet' => $e->getSnippet()],
                $e
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ParsingException(
                'Unexpected error during parsing: '.$e->getMessage(),
                ['trace' => $e->getTraceAsString()],
                $e
            );
        }
    }

    protected function processDocument(array $document): array
    {
        try {
            if (isset($document['document']) && ! $this->metadataValidator->validate($document['document'])) {
                throw new ValidationException(
                    'Invalid document metadata: '.implode(', ', $this->metadataValidator->getErrorMessages()),
                    $this->metadataValidator->getErrors()
                );
            }

            if (isset($document['annotations']) && ! $this->annotationValidator->validate($document['annotations'])) {
                throw new ValidationException(
                    'Invalid annotations: '.implode(', ', $this->annotationValidator->getErrorMessages()),
                    $this->annotationValidator->getErrors()
                );
            }

            foreach ($document['slices'] ?? [] as $index => $slice) {
                $validator = $this->sliceValidatorFactory->make($slice['type']);
                if (! $validator->validate($slice)) {
                    throw new ValidationException(
                        "Invalid slice at index {$index}: ".implode(', ', $validator->getErrorMessages()),
                        $validator->getErrors()
                    );
                }
            }

            return [
                'metadata' => $this->processMetadata($document),
                'slices' => $this->sliceProcessor->process($document['slices'] ?? []),
                'annotations' => $document['annotations'] ?? [],
            ];
        } catch (Exception $e) {
            throw new ParsingException(
                'Document processing failed: '.$e->getMessage(),
                ['document_id' => $document['document']['id'] ?? null],
                $e
            );
        }
    }

    protected function processMetadata(array $document): array
    {
        return [
            'version' => $document['pboml']['version'] ?? '1.0.0',
            'id' => $document['document']['id'] ?? null,
            'release_date' => $document['document']['release_date'] ?? null,
            'title' => $document['document']['title'] ?? [],
            'type' => $document['document']['type'] ?? [],
            'copyright' => $document['document']['copyright'] ?? [],
            'url' => $document['document']['url'] ?? '',
        ];
    }
}
