<?php

namespace PBO\PbomlParser\Exceptions;

use Illuminate\Support\Facades\Date;

class PBOMLErrorHandler
{
    protected array $errorStack = [];

    public function handleException(PBOMLException $exception): void
    {
        $this->errorStack[] = [
            'message' => $exception->getMessage(),
            'code' => $exception->getErrorCode(),
            'severity' => $exception->getSeverity(),
            'context' => $exception->getContext(),
            'timestamp' => Date::now(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];
    }

    public function getErrors(): array
    {
        return $this->errorStack;
    }

    public function getLastError(): ?array
    {
        return empty($this->errorStack) ? null : end($this->errorStack);
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errorStack);
    }

    public function clearErrors(): void
    {
        $this->errorStack = [];
    }

    public function getErrorsByType(string $severity): array
    {
        return array_filter($this->errorStack, function ($error) use ($severity) {
            return $error['severity'] === $severity;
        });
    }

    public function getErrorCount(): int
    {
        return count($this->errorStack);
    }

    public function getErrorSummary(): array
    {
        $summary = [
            'total' => 0,
            'by_severity' => [],
            'by_code' => [],
        ];

        foreach ($this->errorStack as $error) {
            $summary['total']++;

            // Count by severity
            if (! isset($summary['by_severity'][$error['severity']])) {
                $summary['by_severity'][$error['severity']] = 0;
            }
            $summary['by_severity'][$error['severity']]++;

            // Count by error code
            if (! isset($summary['by_code'][$error['code']])) {
                $summary['by_code'][$error['code']] = 0;
            }
            $summary['by_code'][$error['code']]++;
        }

        return $summary;
    }
}
