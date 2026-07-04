<?php

namespace App\Services\Webi;

use App\Models\Module;
use App\Models\Unit;

/**
 * Extracts the structured "[REKOMENDASI_UNIT:<id>]" / "[REKOMENDASI_MODUL:<id>]"
 * tag SystemPromptBuilder::recommendationTagInstructions() asks the model to
 * emit at the end of a reply (task 2.5 follow-up, 2026-07-04, recommendation
 * card feature).
 *
 * The tag is ALWAYS stripped from the returned text — whether or not the
 * referenced ID turns out to be valid — it must never appear as raw text to
 * the user under any circumstance, same principle as the VOICE_MODE leak fix.
 * The referenced unit/module is only returned (for building a card) once its
 * existence is confirmed against the database; a hallucinated ID silently
 * yields a null recommendation with no card, never a broken link, per the
 * user's explicit requirement.
 *
 * Deliberately NOT applied at write time (ChatService doesn't strip it before
 * saving `Message.content`) — the raw tag stays in storage so this parser can
 * re-derive the recommendation (and re-validate it, in case the referenced
 * unit/module is deleted later) every time a message is displayed, including
 * when reopening old conversation history. Every display surface (member
 * chat, admin log viewer, TTS plain-text) must run content through this
 * parser — never render `Message.content` directly.
 */
class RecommendationParser
{
    private const PATTERN = '/\[REKOMENDASI_(UNIT|MODUL)\s*:\s*([^\]]+)\]/i';

    /**
     * @return array{text: string, unit: ?Unit, module: ?Module}
     */
    public function parse(string $rawContent): array
    {
        $unit = null;
        $module = null;

        if (preg_match(self::PATTERN, $rawContent, $matches)) {
            $type = strtoupper($matches[1]);
            $id = trim($matches[2]);

            if ($type === 'UNIT') {
                $unit = Unit::find($id);
            } else {
                $module = Module::find($id);
            }
        }

        $cleanText = trim((string) preg_replace(self::PATTERN, '', $rawContent));

        return ['text' => $cleanText, 'unit' => $unit, 'module' => $module];
    }
}
