<?php

namespace PBO\PbomlParser\Parser\Validation\PBOML\Core;

use PBO\PbomlParser\Parser\Validation\Base\BaseValidator;
use PBO\PbomlParser\Parser\Validation\Base\ValidatorInterface;
use PBO\PbomlParser\Parser\Validation\PBOML\Traits\ValidatesLocalization;

class RootStructureValidator extends BaseValidator implements ValidatorInterface
{
    use ValidatesLocalization;

    protected bool $strictMode = false;


    public function setStrictMode(bool $strict): self
    {
        $this->strictMode = $strict;

        return $this;
    }

    public function isStrictMode(): bool
    {
        return $this->strictMode;
    }

    protected const SUPPORTED_VERSIONS = ['1.0.0'];

    protected const REQUIRED_ROOT_FIELDS = ['pboml', 'slices'];

    public function validate(array $data): bool
    {
        $this->clearErrors();

        if (! $this->validateRequiredFields($data)) {
            return false;
        }

        if (! $this->validatePBOMLVersion($data['pboml'])) {
            return false;
        }

        if (! $this->validateSlicesArray($data['slices'])) {
            return false;
        }

        if (isset($data['document'])) {
            if (! $this->validateDocumentSection($data['document'])) {
                return false;
            }
        }

        if (isset($data['annotations'])) {
            if (! $this->validateAnnotationsArray($data['annotations'])) {
                return false;
            }
        }

        return true;
    }

    protected function validateRequiredFields(array $data): bool
    {
        $missing = [];
        foreach (self::REQUIRED_ROOT_FIELDS as $field) {
            if (! isset($data[$field])) {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            $this->addError('Missing required root fields', [
                'missing_fields' => $missing,
                'required_fields' => self::REQUIRED_ROOT_FIELDS,
            ]);

            return false;
        }

        return true;
    }

    protected function validatePBOMLVersion(array $pbomlData): bool
    {
        if (! isset($pbomlData['version'])) {
            $this->addError('Missing PBOML version');

            return false;
        }

        if (! in_array($pbomlData['version'], self::SUPPORTED_VERSIONS)) {
            $this->addError('Unsupported PBOML version', [
                'version' => $pbomlData['version'],
                'supported_versions' => self::SUPPORTED_VERSIONS,
            ]);

            return false;
        }

        return true;
    }

    protected function validateSlicesArray(array $slices): bool
    {
        if (empty($slices)) {
            $this->addError('Document must contain at least one slice');

            return false;
        }

        if (! is_array($slices)) {
            $this->addError('Slices must be an array');

            return false;
        }

        foreach ($slices as $index => $slice) {
            if (! is_array($slice)) {
                $this->addError("Invalid slice format at index {$index}");

                return false;
            }

            if (! isset($slice['type'])) {
                $this->addError("Missing slice type at index {$index}");

                return false;
            }
        }

        return true;
    }

    protected function validateDocumentSection(array $document): bool
    {
        if ($this->isStrictMode() && ! isset($document['id'])) {
            $this->addError('Document ID is required in strict mode');

            return false;
        }

        $localizedFields = ['title', 'type'];
        foreach ($localizedFields as $field) {
            if (isset($document[$field])) {
                if (! $this->validateLocalizedField($document[$field], "document.{$field}")) {
                    return false;
                }
            } elseif ($this->isStrictMode()) {
                $this->addError("Missing required field '{$field}' in strict mode");

                return false;
            }
        }

        if (isset($document['release_date'])) {
            if (! $this->validateReleaseDate($document['release_date'])) {
                return false;
            }
        }

        if (isset($document['copyright'])) {
            if (! $this->validateLocalizedField($document['copyright'], 'document.copyright')) {
                return false;
            }
        }

        return true;
    }

    protected function validateReleaseDate(string $date): bool
    {
        if (! strtotime($date)) {
            $this->addError('Invalid release date format', [
                'date' => $date,
                'expected_format' => 'ISO8601',
            ]);

            return false;
        }

        return true;
    }

    protected function validateAnnotationsArray(array $annotations): bool
    {
        if (! is_array($annotations)) {
            $this->addError('Annotations must be an array');

            return false;
        }

        foreach ($annotations as $index => $annotation) {
            if (! is_array($annotation)) {
                $this->addError("Invalid annotation format at index {$index}");

                return false;
            }

            if (! isset($annotation['id'])) {
                $this->addError("Missing annotation ID at index {$index}");

                return false;
            }

            if (! isset($annotation['content_type'])) {
                $this->addError("Missing content type at index {$index}");

                return false;
            }

            if (! isset($annotation['content'])) {
                $this->addError("Missing content at index {$index}");

                return false;
            }

            if (! $this->validateLocalizedContent($annotation['content'], "annotation {$index}")) {
                return false;
            }
        }

        return true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    public function clearErrors(): void
    {
        $this->errors = [];
    }

    public function getErrorMessages(): array
    {
        return array_map(function ($error) {
            return $error['message'];
        }, $this->errors);
    }

    public function getErrorContexts(): array
    {
        return array_map(function ($error) {
            return $error['context'] ?? [];
        }, $this->errors);
    }
}
