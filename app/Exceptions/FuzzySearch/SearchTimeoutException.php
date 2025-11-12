<?php

namespace App\Exceptions\FuzzySearch;

class SearchTimeoutException extends FuzzySearchException
{
    protected float $executionTime;

    public function __construct(string $message = '', float $executionTime = 0.0, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->executionTime = $executionTime;
    }

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }
}
