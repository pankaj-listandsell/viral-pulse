<?php

namespace App\Services\AI\Exceptions;

use RuntimeException;

class AiGenerationException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $retryable = false, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /** Rate limits and overloads clear on their own - worth another attempt. */
    public static function retryable(string $message, ?\Throwable $previous = null): self
    {
        return new self($message, true, $previous);
    }

    /** A bad key, a rejected prompt or malformed output will fail identically forever. */
    public static function permanent(string $message, ?\Throwable $previous = null): self
    {
        return new self($message, false, $previous);
    }
}
