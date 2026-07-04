<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configurable Parameters
    |--------------------------------------------------------------------------
    |
    | Defaults from docs/PRD.md "Lampiran A: Parameter WEBI" and
    | docs/spesifikasi-webi.md, all explicitly marked configurable there.
    |
    */

    // Bagian 2.1: jeda dari pesan terakhir sebelum sesi percakapan baru dimulai.
    'session_timeout_minutes' => 30,

    // Bagian 2.1: jumlah pesan terakhir dari sesi aktif yang di-inject ke konteks.
    'context_window_messages' => 20,

    // Bagian 5.2: threshold rate limiting pesan per user per hari.
    'daily_message_limit' => 50,

    // Bagian 5.2: threshold similarity untuk validasi output backend (layer 2).
    'answer_similarity_threshold' => 0.85,

    // Bukan dari dokumen — dipakai untuk GuardrailFlag "eval_detection" (input
    // user vs soal evaluasi), yang secara logika perlu ambang lebih longgar
    // dari validasi output di atas karena parafrase pertanyaan wajar lebih
    // rendah overlap teksnya dibanding jawaban yang bocor verbatim. Ini
    // keputusan implementasi, ditandai untuk konfirmasi di laporan 2.5.
    'question_similarity_threshold' => 0.6,

    // Bagian 2.2: frekuensi maksimal rekomendasi materi lanjutan disisipkan.
    'recommendation_every_n_messages' => 3,

    // Bagian 3.2, Trigger 2: hari tanpa unit baru selesai sebelum sapaan stagnasi.
    'stagnation_days' => 5,

    // Bagian 3.2, Trigger 3: jumlah buka unit tanpa selesai evaluasi, atau
    // jumlah pertanyaan topik sama lintas sesi, sebelum sapaan "stuck".
    'stuck_open_count_threshold' => 3,
    'stuck_topic_repeat_threshold' => 3,

    // Bagian 3.2, anti-spam: cooldown setelah sapaan tidak direspons.
    'proactive_cooldown_days' => 3,

    // Bagian 3.2, anti-spam: batas sapaan nudge tidak direspons berturut-turut
    // sebelum WEBI berhenti kirim sapaan tipe nudge (Trigger 2 dan 3).
    'proactive_unanswered_limit' => 3,

];
