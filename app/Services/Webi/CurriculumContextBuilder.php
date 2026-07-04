<?php

namespace App\Services\Webi;

use App\Models\Module;
use App\Models\Unit;

/**
 * Builds the [RELEVANT_CURRICULUM_CONTENT] block (docs/spesifikasi-webi.md 5.1):
 * "Konten unit yang sedang dikerjakan user + konten unit yang relevan dengan
 * pertanyaan user, di-retrieve berdasarkan similarity search."
 *
 * This stack has no vector/embedding store (docs/tech-stack.md 3 defers that
 * decision to 1.9 and never resolves it), so "similarity search" here is a
 * plain keyword-overlap lookup against unit title/content — a pragmatic
 * stand-in flagged in the task 2.5 report, not a literal semantic search.
 *
 * [SAJIKAN: ...] directives are stripped per docs/spesifikasi-webi.md 4.1:
 * they're frontend rendering markers, not content WEBI (or the user) should see.
 *
 * Real unit/module IDs are included alongside titles (added 2026-07-04 for the
 * recommendation-card feature) so RecommendationParser has grounded IDs to
 * validate against — the model is instructed elsewhere (SystemPromptBuilder)
 * to only ever reference IDs that actually appear here, never invent one. This
 * also means a recommendation is only possible for units already present in
 * this context (current unit, keyword-matched related units, next unit/module)
 * — not any of the other ~60 units in the curriculum. Accepted scope limit,
 * not a bug: injecting the full catalog with IDs on every request would bloat
 * the prompt considerably and work against the thinking_level latency tuning.
 */
class CurriculumContextBuilder
{
    private const MAX_RELATED_UNITS = 2;

    public function build(?Unit $currentUnit, string $userQuestion): string
    {
        $blocks = [];

        if ($currentUnit) {
            $blocks[] = "Unit yang sedang dikerjakan user (unit_id: {$currentUnit->id}, judul: {$currentUnit->title}):\n".$this->stripDirectives($currentUnit->content);
        }

        foreach ($this->relatedUnits($currentUnit, $userQuestion) as $unit) {
            $blocks[] = "Unit relevan (unit_id: {$unit->id}, judul: {$unit->title}):\n".$this->stripDirectives($unit->content);
        }

        $navigation = $this->navigationReferences($currentUnit);
        if ($navigation) {
            $blocks[] = $navigation;
        }

        if (empty($blocks)) {
            return '';
        }

        return "[RELEVANT_CURRICULUM_CONTENT]\n".implode("\n\n", $blocks);
    }

    /**
     * Grounds "unit/modul berikutnya" recommendations with real IDs — without
     * this, the model has no way to know the next unit/module's ID even when
     * it wants to point the user forward (a very common recommendation per
     * docs/spesifikasi-webi.md 2.2).
     */
    private function navigationReferences(?Unit $currentUnit): string
    {
        if (! $currentUnit) {
            return '';
        }

        $lines = ["ID navigasi yang tersedia untuk direkomendasikan:"];
        $lines[] = "- modul_id saat ini: {$currentUnit->module_id} ({$currentUnit->module->title})";

        $nextUnit = Unit::where('module_id', $currentUnit->module_id)
            ->where('order_number', $currentUnit->order_number + 1)
            ->first();

        if ($nextUnit) {
            $lines[] = "- unit_id berikutnya di modul yang sama: {$nextUnit->id} ({$nextUnit->title})";
        }

        $nextModule = Module::where('order_number', $currentUnit->module->order_number + 1)->first();

        if ($nextModule) {
            $lines[] = "- modul_id berikutnya: {$nextModule->id} ({$nextModule->title})";
        }

        return implode("\n", $lines);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Unit>
     */
    private function relatedUnits(?Unit $currentUnit, string $userQuestion)
    {
        $keywords = $this->significantKeywords($userQuestion);

        if (empty($keywords)) {
            return collect();
        }

        $query = Unit::query();

        if ($currentUnit) {
            $query->where('id', '!=', $currentUnit->id);
        }

        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('title', 'like', "%{$keyword}%")
                    ->orWhere('content', 'like', "%{$keyword}%");
            }
        });

        return $query->limit(self::MAX_RELATED_UNITS)->get();
    }

    /**
     * @return array<int, string>
     */
    private function significantKeywords(string $text): array
    {
        $stopwords = ['yang', 'dengan', 'untuk', 'dari', 'dan', 'atau', 'apa', 'apakah', 'bagaimana', 'kenapa', 'itu', 'ini', 'aku', 'kamu', 'saya', 'tolong', 'coba', 'gimana'];

        $words = preg_split('/[^\p{L}0-9]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter(array_unique($words), fn ($word) => mb_strlen($word) > 3 && ! in_array($word, $stopwords, true)));
    }

    private function stripDirectives(string $content): string
    {
        return trim(preg_replace('/\[SAJIKAN:.*?\]/su', '', $content));
    }
}
