<?php

namespace App\Services\Webi;

use App\Models\Unit;
use App\Models\UnitEvaluation;
use Illuminate\Support\Collection;

/**
 * docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md 5.1 [EVALUATION_BANK]: "Soal evaluasi dari unit yang
 * sedang dikerjakan user + unit yang berdekatan". The doc doesn't define
 * "berdekatan" precisely — interpreted here as: the current unit itself, plus
 * the previous and next unit by order_number within the same module. Flagged
 * as an interpretation in the task 2.5 report, not treated as silently final.
 */
class EvaluationBankBuilder
{
    /**
     * @return Collection<int, array{unit_id: string, unit_title: string, tipe_evaluasi: string, soal: string, kunci_jawaban: mixed}>
     */
    public function build(?Unit $currentUnit): Collection
    {
        if (! $currentUnit) {
            return collect();
        }

        $relevantUnitIds = Unit::where('module_id', $currentUnit->module_id)
            ->whereBetween('order_number', [$currentUnit->order_number - 1, $currentUnit->order_number + 1])
            ->pluck('id', 'title');

        return UnitEvaluation::whereIn('unit_id', $relevantUnitIds->values())
            ->with('unit')
            ->get()
            ->map(fn (UnitEvaluation $evaluation) => [
                'unit_id' => $evaluation->unit_id,
                'unit_title' => $evaluation->unit->title,
                'tipe_evaluasi' => $evaluation->question_type,
                'soal' => $evaluation->question_text,
                'kunci_jawaban' => $evaluation->correct_answer,
            ])
            ->values();
    }

    /**
     * SECURITY: this method must NEVER include `correct_answer` in the
     * prompt text sent to Gemini — WEBI only needs to know a question
     * EXISTS (to detect paraphrasing), never the key itself.
     * `correct_answer` stays available on the `$bank` Collection for
     * GuardrailService's SERVER-SIDE comparison against Gemini's
     * input/output after the fact — a completely separate path from what
     * actually gets sent TO Gemini.
     */
    public function toPromptText(Collection $bank): string
    {
        if ($bank->isEmpty()) {
            return '';
        }

        $lines = $bank->map(
            fn (array $item) => "- unit_id: {$item['unit_id']} | tipe: {$item['tipe_evaluasi']} | soal: {$item['soal']}"
        )->implode("\n");

        return "[EVALUATION_BANK]\n{$lines}";
    }
}
