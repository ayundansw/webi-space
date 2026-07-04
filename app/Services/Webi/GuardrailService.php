<?php

namespace App\Services\Webi;

use App\Models\GuardrailFlag;
use App\Models\Message;
use Illuminate\Support\Collection;

/**
 * Layer 2 backend guardrail validation (docs/spesifikasi-webi.md 5.2), the
 * "pertahanan lapis kedua" behind the system-prompt instructions (Layer 1,
 * SystemPromptBuilder). Logs to GuardrailFlag for admin monitoring (5.3),
 * matching the three flag_type values in the schema:
 * - output_validation: WEBI's own reply text is compared against evaluation_bank
 *   answer keys. This is the one that actually blocks/retries a reply.
 * - eval_detection: the user's incoming message resembles an evaluation
 *   question (paraphrase-tolerant). Logging only — Layer 1 is what makes WEBI
 *   actually refuse; this just records that it happened, for admin monitoring.
 * - domain_rejection: WEBI's reply matches one of the four fixed domain-refusal
 *   templates from docs/spesifikasi-webi.md 1.4.
 *
 * Similarity here is PHP's built-in similar_text() percentage, not literal
 * cosine similarity over embeddings — the doc says "cosine similarity ATAU
 * exact match" and this stack has no vector/embedding infrastructure (per
 * docs/tech-stack.md, that decision was deferred to 1.9 and never made). This
 * is flagged as a pragmatic substitute in the task 2.5 report, not a literal
 * implementation of the doc's wording.
 */
class GuardrailService
{
    /**
     * Cap on reply length for the verbatim-substring leak check below — found
     * live (2026-07-04, while testing the recommendation-card feature) that
     * without this cap, a long, clearly-explanatory reply that happens to
     * mention its own unit's title/subject (which is often ALSO that unit's
     * quiz answer, e.g. "Software Development") gets false-flagged as
     * "leaking the answer" — triggering an unnecessary retry, which costs a
     * full extra Gemini round-trip and doubles real timeout risk for a
     * response that was completely fine. A genuine leaked answer tends to be
     * a short reply that's mostly/only the answer itself, not a small phrase
     * buried in several sentences of explanation.
     */
    private const MAX_LENGTH_FOR_SUBSTRING_LEAK_CHECK = 150;

    private const DOMAIN_REJECTION_TEMPLATES = [
        'out_of_domain' => 'Aku cuma bisa bantu soal materi web development dan cara pakai WEBI-SPACE ya. Ada hal lain seputar itu yang mau kamu tanyakan?',
        'personal_sensitive' => 'Aku tidak bisa bantu soal itu, tapi kalau kamu butuh bicara dengan seseorang, coba hubungi PIC atau orang yang kamu percaya ya.',
        'code_generation' => 'Aku di sini untuk bantu kamu paham materi kurikulum ini. Untuk proyek di luar kurikulum, coba eksplorasi sendiri dulu pakai konsep yang sudah kamu pelajari ya.',
        'harmful_content' => 'Aku tidak bisa bantu soal itu.',
    ];

    public function similarity(string $a, string $b): float
    {
        $a = mb_strtolower(trim($a));
        $b = mb_strtolower(trim($b));

        if ($a === '' || $b === '') {
            return 0.0;
        }

        similar_text($a, $b, $percent);

        return $percent / 100;
    }

    /**
     * @param  Collection<int, array{unit_id: string, kunci_jawaban: mixed}>  $evaluationBank
     * @return array{unit_id: string, similarity: float}|null
     */
    public function checkOutputAgainstAnswers(string $replyText, Collection $evaluationBank): ?array
    {
        $threshold = config('webi.answer_similarity_threshold');
        $best = null;

        foreach ($evaluationBank as $item) {
            $answer = $item['kunci_jawaban'];
            $candidates = is_array($answer) ? $answer : [$answer];

            foreach ($candidates as $candidate) {
                if (empty($candidate) || ! is_string($candidate)) {
                    continue;
                }

                $score = $this->similarity($replyText, $candidate);

                // similar_text()'s percentage is diluted by length differences
                // (found while testing the retry-fallback fix, 2026-07-04): a
                // short-but-distinctive answer quoted verbatim inside a longer
                // sentence can score well under threshold even though it
                // plainly leaks the answer. Multi-word candidates (2+ words,
                // 6+ chars) also match on verbatim substring containment to
                // close that hole. Restricted to multi-word candidates only —
                // single common short words (e.g. "Benar"/"Salah" from
                // true/false quizzes) would false-positive constantly if
                // matched this way, since they appear in ordinary sentences
                // unrelated to leaking anything. ALSO capped to short replies
                // only (see MAX_LENGTH_FOR_SUBSTRING_LEAK_CHECK) — a long,
                // clearly-explanatory reply that merely mentions a unit's own
                // title/subject (often the same text as that unit's answer
                // key) is not a leak.
                $isLeakedVerbatim = mb_strlen($candidate) >= 6
                    && str_contains($candidate, ' ')
                    && mb_strlen($replyText) <= self::MAX_LENGTH_FOR_SUBSTRING_LEAK_CHECK
                    && str_contains(mb_strtolower($replyText), mb_strtolower($candidate));

                if (($score >= $threshold || $isLeakedVerbatim) && ($best === null || $score > $best['similarity'])) {
                    $best = ['unit_id' => $item['unit_id'], 'similarity' => max($score, $isLeakedVerbatim ? 1.0 : $score)];
                }
            }
        }

        return $best;
    }

    /**
     * @param  Collection<int, array{unit_id: string, soal: string}>  $evaluationBank
     * @return array{unit_id: string, soal: string, similarity: float}|null
     */
    public function checkEvalDetectionInput(string $userText, Collection $evaluationBank): ?array
    {
        $threshold = config('webi.question_similarity_threshold', 0.6);
        $best = null;

        foreach ($evaluationBank as $item) {
            $score = $this->similarity($userText, $item['soal']);

            if ($score >= $threshold && ($best === null || $score > $best['similarity'])) {
                $best = ['unit_id' => $item['unit_id'], 'soal' => $item['soal'], 'similarity' => $score];
            }
        }

        return $best;
    }

    public function checkDomainRejection(string $replyText): ?string
    {
        foreach (self::DOMAIN_REJECTION_TEMPLATES as $category => $template) {
            if ($this->similarity($replyText, $template) >= 0.5) {
                return $category;
            }
        }

        return null;
    }

    public function logFlag(Message $message, string $flagType, ?string $unitId, array $details): GuardrailFlag
    {
        return GuardrailFlag::create([
            'message_id' => $message->id,
            'flag_type' => $flagType,
            'unit_id' => $unitId,
            'details' => $details,
        ]);
    }

    /**
     * Fallback used when even the one retry (docs/spesifikasi-webi.md 5.2)
     * still leaks the answer key — the user must never receive a reply that
     * failed Layer 2 validation twice, so this replaces it outright rather
     * than sending it "best effort". Confirmed with the user (2026-07-04)
     * as a required fix, not an open question anymore.
     */
    public function genericRefusalMessage(): string
    {
        return 'Aku tidak bisa kasih jawaban itu langsung ya, tapi aku bisa bantu kamu pahami konsepnya kalau kamu mau. Coba kerjakan dulu berdasarkan pemahamanmu sendiri, atau tanya dengan cara lain kalau ada bagian yang masih bikin bingung.';
    }
}
