<?php

namespace App\Services\Webi;

use App\Models\Module;
use App\Models\Unit;
use Illuminate\Support\Collection;

/**
 * Builds the [RELEVANT_CURRICULUM_CONTENT] block (spesifikasi-webi.md 5.1):
 * current unit's content plus keyword-matched related units. No vector store
 * exists, so "similarity" is plain keyword overlap, not semantic search.
 * [SAJIKAN: ...] directives are stripped (frontend rendering markers).
 *
 * Real unit/module IDs are included so RecommendationParser can validate
 * against them, scoping recommendations to units already in this context.
 * Reads content_blocks first, falling back to the legacy `content` column
 * for units not yet migrated to the block editor.
 */
class CurriculumContextBuilder
{
    private const MAX_RELATED_UNITS = 2;

    public function build(?Unit $currentUnit, string $userQuestion): string
    {
        $blocks = [];

        if ($currentUnit) {
            $blocks[] = "Unit yang sedang dikerjakan user (unit_id: {$currentUnit->id}, judul: {$currentUnit->title}):\n".$this->unitContextText($currentUnit);
        }

        foreach ($this->relatedUnits($currentUnit, $userQuestion) as $unit) {
            $blocks[] = "Unit relevan (unit_id: {$unit->id}, judul: {$unit->title}):\n".$this->unitContextText($unit);
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
     * docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md 2.2).
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
                // Legacy plain-text column AND content_blocks (JSON, cast to
                // text for LIKE) — a unit migrated to blocks must stay
                // findable by keyword search, not silently drop out of
                // relevance matching just because `content` is now stale/empty.
                $q->orWhere('title', 'like', "%{$keyword}%")
                    ->orWhere('content', 'like', "%{$keyword}%")
                    ->orWhereHas('contentBlocks', function ($blockQuery) use ($keyword) {
                        $blockQuery->whereRaw('CAST(content AS CHAR) LIKE ?', ["%{$keyword}%"]);
                    });
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

    /**
     * content_blocks first (Fase 4 Editor Blok Konten), legacy `content`
     * column as fallback — a unit only ever has one or the other in
     * practice (nothing writes content_blocks for a unit except the editor,
     * and the editor never touches `content`), but checking both keeps this
     * correct even if that assumption ever changes.
     */
    private function unitContextText(Unit $unit): string
    {
        $blocks = $unit->contentBlocks;

        $text = $blocks->isNotEmpty()
            ? $this->extractTextFromBlocks($blocks)
            : $unit->content;

        return $this->stripDirectives($text);
    }

    /**
     * @param  Collection<int, \App\Models\ContentBlock>  $blocks
     */
    private function extractTextFromBlocks(Collection $blocks): string
    {
        $parts = [];

        foreach ($blocks as $block) {
            $text = match ($block->type) {
                'heading' => (string) ($block->content['text'] ?? ''),
                'text' => (string) ($block->content['markdown'] ?? ''),
                'callout' => trim((! empty($block->content['title']) ? $block->content['title'].': ' : '').($block->content['body'] ?? '')),
                'code' => trim((! empty($block->content['language']) ? '['.$block->content['language'].'] ' : '').($block->content['code'] ?? '')),
                'list' => collect($block->content['items'] ?? [])->map(fn ($item) => '- '.$item)->implode("\n"),
                'table' => $this->extractTableText($block->content),
                // custom_html: plain-text extraction only, never the raw
                // markup — this text goes into an LLM prompt, not rendered
                // HTML, but tags add noise without meaning here.
                'custom_html' => trim(strip_tags($block->content['html'] ?? '')),
                // image/video: no text meaningfully describes visual content
                // for WEBI's purposes — skipped, not an oversight.
                default => '',
            };

            if (trim($text) !== '') {
                $parts[] = $text;
            }
        }

        return implode("\n\n", $parts);
    }

    private function extractTableText(array $content): string
    {
        $lines = [];

        if (! empty($content['headers'])) {
            $lines[] = implode(' | ', $content['headers']);
        }

        foreach ($content['rows'] ?? [] as $row) {
            $lines[] = implode(' | ', $row);
        }

        return implode("\n", $lines);
    }

    private function stripDirectives(string $content): string
    {
        return trim(preg_replace('/\[SAJIKAN:.*?\]/su', '', $content));
    }
}
