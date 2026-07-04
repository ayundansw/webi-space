<?php

namespace Tests\Feature\Webi;

use App\Services\Webi\GuardrailService;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Unit-level coverage for the verbatim-substring detection added on top of
 * similar_text() (2026-07-04 follow-up) — proves it catches a short-but-
 * distinctive multi-word answer embedded in a longer reply, and proves it
 * deliberately does NOT trigger on short single-word answers like "Benar"/
 * "Salah" that would otherwise false-positive on ordinary Indonesian sentences.
 */
class GuardrailServiceTest extends TestCase
{
    private function bank(mixed $answer): Collection
    {
        return collect([
            ['unit_id' => 'unit-1', 'kunci_jawaban' => $answer],
        ]);
    }

    public function test_multi_word_answer_embedded_in_a_longer_reply_is_caught(): void
    {
        $service = new GuardrailService;

        $result = $service->checkOutputAgainstAnswers(
            'Ini termasuk Mobile App kok, karena dipasang di ponsel.',
            $this->bank('Mobile App'),
        );

        $this->assertNotNull($result);
        $this->assertSame('unit-1', $result['unit_id']);
    }

    public function test_short_single_word_answer_does_not_false_positive_on_ordinary_sentences(): void
    {
        $service = new GuardrailService;

        // "salah" appears here in its ordinary Indonesian sense ("not wrong to
        // ask"), completely unrelated to leaking a benar/salah quiz answer.
        $result = $service->checkOutputAgainstAnswers(
            'Itu bukan hal yang salah untuk ditanyakan, coba pikirkan konsepnya dulu ya.',
            $this->bank('Salah'),
        );

        $this->assertNull($result);
    }

    public function test_exact_short_answer_is_still_caught_via_similarity(): void
    {
        $service = new GuardrailService;

        $result = $service->checkOutputAgainstAnswers('Salah', $this->bank('Salah'));

        $this->assertNotNull($result);
    }

    /**
     * Found live (2026-07-04) while testing the recommendation-card feature:
     * a long, clearly-explanatory reply recommending the unit "Apa Itu
     * Software Development?" naturally mentions "Software Development" —
     * which is ALSO that unit's own quiz answer key. The multi-word substring
     * check above was false-flagging this as a leak, triggering an
     * unnecessary retry (and doubling real timeout risk) for a response that
     * never actually gave away an answer. Capped the substring check to short
     * replies only — a genuine leak reads as "here's the answer", not several
     * sentences of unrelated explanation that happens to reference the topic.
     */
    public function test_long_explanatory_reply_mentioning_the_answer_as_a_topic_is_not_flagged(): void
    {
        $service = new GuardrailService;

        $longReply = 'Halo! Selamat datang di WEBI-SPACE. Keren banget kamu sudah membulatkan niat '.
            'buat mulai belajar web development. Wajar banget kok kalau di awal merasa bingung harus '.
            'melangkah dari mana dulu karena banyak banget istilah baru. Saran aku, kamu bisa mulai '.
            'dari unit pertama yang ada di modul kamu saat ini, yaitu "Apa Itu Software Development?". '.
            'Di sana, kita bakal bahas santai tentang apa itu software development itu sendiri.';

        $result = $service->checkOutputAgainstAnswers($longReply, $this->bank('Software Development'));

        $this->assertNull($result);
    }
}
