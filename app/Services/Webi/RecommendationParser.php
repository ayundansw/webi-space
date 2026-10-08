<?php

namespace App\Services\Webi;

use App\Models\Module;
use App\Models\Unit;

/**
 * Extracts the "[REKOMENDASI_UNIT:<id>]" / "[REKOMENDASI_MODUL:<id>]" tag
 * SystemPromptBuilder asks the model to emit at the end of a reply.
 *
 * The tag is ALWAYS stripped from returned text, valid or not — never
 * shown raw to the user. The referenced unit/module is only returned once
 * confirmed to exist in the DB; a hallucinated ID yields no card, never a
 * broken link.
 *
 * NOT stripped at write time — the raw tag stays in `Message.content` so
 * this parser can re-derive/re-validate it every display. Every display
 * surface MUST run content through this parser, never render
 * `Message.content` directly.
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
