<?php

namespace App\Services\Webi;

use Exception;

/**
 * docs/spesifikasi-webi.md 5.2 / docs/PRD.md 5.10: max messages per user per day.
 */
class RateLimitExceededException extends Exception
{
    public function __construct(public readonly string $userMessage)
    {
        parent::__construct($userMessage);
    }
}
