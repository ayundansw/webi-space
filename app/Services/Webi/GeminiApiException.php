<?php

namespace App\Services\Webi;

use Exception;

/**
 * Carries a message already safe to show the user (docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md
 * has no prescribed copy for API failures, so these are written in the same
 * supportive tone as the rest of WEBI's persona, per docs/v_2.0/archive/sumber-konsolidasi/design-tokens.md).
 */
class GeminiApiException extends Exception
{
    public function __construct(public readonly string $userMessage, string $technicalMessage)
    {
        parent::__construct($technicalMessage);
    }
}
