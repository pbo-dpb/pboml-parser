<?php

namespace PBO\PbomlParser\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class PBOMLException extends Exception
{
    protected array $context = [];

    protected string $errorCode;

    protected string $severity;

    public function __construct(
        string $message = '',
        string $errorCode = '',
        string $severity = 'error',
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->severity = $severity;
        $this->context = $context;
        $this->logError();
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    protected function logError(): void
    {
        $logData = [
            'error_code' => $this->errorCode,
            'severity' => $this->severity,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ];

        match ($this->severity) {
            'emergency' => Log::emergency($this->message, $logData),
            'alert' => Log::alert($this->message, $logData),
            'critical' => Log::critical($this->message, $logData),
            'error' => Log::error($this->message, $logData),
            'warning' => Log::warning($this->message, $logData),
            default => Log::info($this->message, $logData),
        };
    }
}
