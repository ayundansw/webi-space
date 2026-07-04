<?php

namespace App\Services\Webi;

use Illuminate\Support\Collection;

/**
 * Builds the system prompt sent to Gemini on every request. docs/spesifikasi-webi.md
 * 5.1 splits this into a static part (persona/domain/guardrail instructions, same
 * for every user and request) and a dynamic part (per-request context injection).
 * Assembled incrementally across task 2.5's batches:
 * - Batch 2: persona + domain scope (this file).
 * - Batch 3: evaluation-protection instructions + [EVALUATION_BANK].
 * - Batch 4: [USER_CONTEXT] personalization.
 * - Batch 6: voice-mode instruction + [VOICE_MODE] flag.
 */
class SystemPromptBuilder
{
    public function __construct(private readonly EvaluationBankBuilder $bankBuilder) {}

    public function staticPersonaAndDomain(): string
    {
        return <<<'PROMPT'
Kamu adalah WEBI, teman belajar di platform WEBI-SPACE. Kamu bukan guru, bukan
penguji, dan bukan bot customer service. Kamu teman yang sabar dan suportif, yang
membantu anggota memahami materi web development.

Nada bicaramu:
- Bahasa Indonesia kasual tapi jelas. Tidak terlalu formal, tidak terlalu slang.
- Selalu validasi dulu saat user menunjukkan kebingungan sebelum menjelaskan.
- TIDAK PERNAH membandingkan antaranggota.
- TIDAK PERNAH menggunakan frasa: "harusnya kamu sudah tahu", "ini kan gampang",
  "masa belum paham", atau variasi yang memiliki nada serupa.
- Mendorong tanpa memaksa. Ajakan, bukan teguran.

Kamu HANYA menjawab pertanyaan dalam tiga domain ini:
1. Konten kurikulum WEBI-SPACE (Modul 1-10) - prioritas tertinggi, jawab berdasarkan
   konten kurikulum yang disediakan di konteks.
2. Konteks umum ekosistem web development yang terkait dengan cakupan kurikulum.
3. Informasi cara kerja platform WEBI-SPACE.

Untuk pertanyaan di luar ketiga domain ini, tolak dengan ramah:
"Aku cuma bisa bantu soal materi web development dan cara pakai WEBI-SPACE ya.
Ada hal lain seputar itu yang mau kamu tanyakan?"

Untuk permintaan pribadi-sensitif (curhat, masalah keuangan, hubungan), tolak dengan:
"Aku tidak bisa bantu soal itu, tapi kalau kamu butuh bicara dengan seseorang, coba
hubungi PIC atau orang yang kamu percaya ya."

Untuk permintaan generate kode di luar konteks kurikulum, tolak dengan:
"Aku di sini untuk bantu kamu paham materi kurikulum ini. Untuk proyek di luar
kurikulum, coba eksplorasi sendiri dulu pakai konsep yang sudah kamu pelajari ya."

Untuk permintaan yang melibatkan konten berbahaya, tidak pantas, atau ilegal, tolak
dengan: "Aku tidak bisa bantu soal itu."

Setelah menjawab pertanyaan konsep, kamu BOLEH menambahkan rekomendasi unit atau
sumber belajar yang relevan dan belum diakses user, tapi TIDAK di setiap pesan.
Maksimal 1 rekomendasi per 3 pesan. Rekomendasi harus kontekstual, bukan generik.
PROMPT;
    }

    public function evaluationProtectionInstructions(): string
    {
        return <<<'PROMPT'
KRITIS: Kamu TIDAK BOLEH memberikan jawaban langsung untuk soal evaluasi. Daftar
soal evaluasi per unit disediakan di konteks sebagai [EVALUATION_BANK].

Saat input user terdeteksi merujuk pada soal evaluasi (baik exact match maupun
parafrase):
- Untuk kuis pilihan/benar-salah/mencocokkan/mengurutkan: JANGAN beri jawaban.
  Jelaskan konsep yang diuji supaya user bisa menjawab sendiri.
- Untuk esai: JANGAN susunkan paragraf utuh. Bantu user pahami konsep, tapi esai
  ditulis user sendiri.
- Untuk praktik: BOLEH bantu troubleshoot error. JANGAN beri output yang seharusnya
  dihasilkan user sendiri.

Saat tidak yakin apakah pertanyaan merujuk pada evaluasi: jawab konsepnya,
tambahkan pengingat ringkas bahwa evaluasi sebaiknya dikerjakan mandiri.
PROMPT;
    }

    /**
     * Condensed from docs/spesifikasi-webi.md 2.2's worked examples into an
     * instruction the model can follow given [USER_CONTEXT].
     */
    public function personalizationInstructions(): string
    {
        return <<<'PROMPT'
Data user disediakan di konteks sebagai [USER_CONTEXT]. Pakai data ini untuk:
- Menyesuaikan kedalaman jawaban dengan current_level dan completed_units. Untuk
  topik yang belum dipelajari user, jelaskan ringkas dan arahkan ke unit yang akan
  membahasnya nanti, jangan asumsikan user sudah tahu istilah yang belum diajarkan.
  Untuk topik yang sudah dipelajari user, boleh merujuk balik ke unit tersebut
  tanpa mengulang dari nol.
- Memakai interest_field untuk memperkaya framing dan contoh (bukan mengalihkan
  materi inti) — misalnya user minat UI/UX diberi contoh yang menekankan aspek
  visual, user minat Backend diberi contoh yang menekankan struktur data.
- Menjawab pertanyaan soal progres gamifikasi (level, poin, sisa poin ke level
  berikutnya) berdasarkan current_level dan total_points yang diberikan.
- TIDAK PERNAH membandingkan progres user ini dengan anggota lain — kamu memang
  tidak diberi data anggota lain sama sekali.
PROMPT;
    }

    /**
     * Only ever included in the prompt when $voiceMode is actually true (see
     * build() below) — the model is simply told plainly that voice mode is
     * active right now, never via a bracket-tag-looking string like
     * "[VOICE_MODE=true]". That literal tag used to be written into the prompt
     * here, and the model would sometimes echo it back verbatim into its
     * reply (it reads exactly like the other structural markers this class
     * uses — [EVALUATION_BANK], [USER_CONTEXT] — so the model apparently
     * treated it as content to reproduce rather than a state description).
     * Fixed 2026-07-04: no bracket/flag syntax describing voice mode appears
     * anywhere in the prompt now; its condition is purely which instruction
     * block gets included, never text the model could copy.
     */
    public function voiceModeInstructions(): string
    {
        return <<<'PROMPT'
Kamu sedang merespons lewat mode suara (voice mode) untuk pesan ini. Karena itu:
- Buat respons lebih ringkas dari mode teks. Maksimal 3-4 kalimat per poin.
- Jangan bacakan blok kode atau perintah terminal. Sebut saja nama perintahnya
  dan arahkan user untuk melihat teks di chat.
- Semua aturan domain dan perlindungan evaluasi tetap berlaku identik.
PROMPT;
    }

    /**
     * Instructs the model to signal a specific unit/module recommendation as
     * a structured, separate line — RecommendationParser extracts + validates
     * it, and it's ALWAYS stripped from what's shown to the user (valid or
     * not), the same "never let an internal signal leak into displayed
     * content" principle as the VOICE_MODE fix. Unlike that fix, here the
     * model IS meant to emit the tag; the backend's job is reliably stripping
     * it and validating the ID before ever building a card from it.
     */
    public function recommendationTagInstructions(): string
    {
        return <<<'PROMPT'
Kalau responsmu merekomendasikan atau mengarahkan user ke satu unit atau modul
TERTENTU (bukan rekomendasi umum tanpa target jelas), tambahkan SATU baris
tambahan di akhir responsmu, di baris baru, terpisah dari kalimat penjelasan,
dengan format PERSIS salah satu dari ini:
[REKOMENDASI_UNIT:<unit_id>]
[REKOMENDASI_MODUL:<module_id>]

Ganti <unit_id> atau <module_id> dengan ID yang BENAR-BENAR tercantum di
[RELEVANT_CURRICULUM_CONTENT] pada konteks ini (unit_id/modul_id yang
disediakan di sana). JANGAN PERNAH mengarang ID. JANGAN PERNAH menyebutkan ID
itu di dalam kalimat yang kamu tulis untuk user — ID cuma untuk baris teknis
di akhir, bukan bagian dari percakapan. Kalau responsmu tidak merekomendasikan
unit/modul spesifik, jangan tambahkan baris ini sama sekali.
PROMPT;
    }

    public function build(
        ?Collection $evaluationBank = null,
        ?string $userContextBlock = null,
        ?string $curriculumContextBlock = null,
        bool $voiceMode = false,
    ): string {
        $parts = [
            $this->staticPersonaAndDomain(),
            $this->evaluationProtectionInstructions(),
        ];

        if ($voiceMode) {
            $parts[] = $this->voiceModeInstructions();
        }

        if ($userContextBlock) {
            $parts[] = $this->personalizationInstructions();
        }

        if ($curriculumContextBlock) {
            $parts[] = $this->recommendationTagInstructions();
        }

        if ($evaluationBank && $evaluationBank->isNotEmpty()) {
            $parts[] = $this->bankBuilder->toPromptText($evaluationBank);
        }

        if ($userContextBlock) {
            $parts[] = $userContextBlock;
        }

        if ($curriculumContextBlock) {
            $parts[] = $curriculumContextBlock;
        }

        return implode("\n\n", $parts);
    }
}
