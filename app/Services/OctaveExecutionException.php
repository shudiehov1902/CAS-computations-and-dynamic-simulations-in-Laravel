<?php

namespace App\Services;

use RuntimeException;

class OctaveExecutionException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $httpStatus = 500,
        private readonly string $output = '',
    ) {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function output(): string
    {
        return $this->output;
    }
}
