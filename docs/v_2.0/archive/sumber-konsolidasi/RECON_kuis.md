# Recon — Mekanisme Kuis v1.0

Laporan ini murni observasi kode NYATA saat ini. Dibuat sebagai bekal
penggantian mekanisme poin Kuis ke v2.0 (skor terbaik, retry bebas).
**Tidak ada kode yang diubah.**

## A. Alur kuis sekarang

**1. Path lengkap:**
- Class Livewire: `app/Livewire/Eksplorasi/UnitEvaluation.php`
- View: `resources/views/livewire/eksplorasi/unit-evaluation.blade.php`
- Tidak punya route sendiri — di-mount sebagai child component
  `<livewire:eksplorasi.unit-evaluation :unit="$unit" />` di dalam
  `resources/views/livewire/eksplorasi/unit-show.blade.php` (route induk:
  `/eksplorasi/unit/{unit}` → `App\Livewire\Eksplorasi\UnitShow`).

**2. Tipe evaluasi dan penanganannya** (dicek `unit-evaluation.blade.php`
+ `UnitEvaluation.php`):
- `quiz_multiple_choice`, `quiz_matching`, `quiz_ordering` — form yang sama
  (`wire:submit="submitQuiz"`), question-type per-soal (`radio` untuk
  pilihan ganda, `select` per-pasangan untuk matching, tombol naik/turun
  `moveOrderItem()` untuk ordering — bukan drag-and-drop asli, alasannya
  eksplisit di kode: browser-testable tanpa engine browser sungguhan).
  Satu unit BOLEH campur tipe soal (mis. 2 pilihan-ganda + 1 esai dalam
  satu unit — Unit "Menangani Merge Conflict", dites eksplisit).
- `essay`, `practice` — form `wire:submit="submitFreeText"`, satu
  textarea, auto-approve (`is_correct = null`, tidak pernah dinilai
  benar/salah).
- `none` — tombol tunggal `wire:click="markAsRead"`, tidak ada form sama
  sekali (unit materi baca-saja).
- Tipe lain di luar daftar itu: fallback pesan "Tipe evaluasi ini belum
  didukung di versi ini" (`unit-evaluation.blade.php:187`).

**3. Alur submit → grade → simpan → poin** (`submitQuiz()`,
`UnitEvaluation.php:77-127`):
1. Validasi semua soal terisi (`required` per tipe soal).
2. Hitung `$isFirstAttempt` (belum pernah ada `EvaluationSubmission`
   untuk user+unit ini).
3. Grading: `$allCorrect` — AND logika, semua soal non-esai harus benar
   (esai di-skip dari grading, `isCorrectAnswer()`). Matching di-grade
   `ksort()` kedua sisi dulu (order-independent); ordering strict `===`
   (urutan ADALAH jawabannya).
4. `EvaluationSubmission::create([...])` — **SELALU dibuat**, apa pun
   hasil `$allCorrect`.
5. `$progress->completeUnit(Auth::user(), $this->unit)` — **dipanggil
   TANPA SYARAT**, tidak digate oleh `$allCorrect` sama sekali. Ini
   konsisten dengan keputusan lama yang sudah tercatat
   (`feedback_evaluation_scoring_rules`): kuis kasih poin terlepas dari
   benar/salah, cukup submit.
6. Poin sungguhan masuk lewat `completeUnit()` → `awardPoints()` (lihat
   bagian B untuk detail kritis di sini).

## B. Mekanisme poin kuis v1.0 (yang akan diganti)

**4. Konfirmasi "attempt pertama penuh, retry nol"** — BENAR, tapi HANYA
untuk kolom `EvaluationSubmission.points_awarded`, bukan untuk poin
sungguhan yang masuk `total_points` (lihat temuan kritis di poin 7):
```php
// UnitEvaluation.php:121
'points_awarded' => $isFirstAttempt ? $this->unit->point_value : 0,
```
`$isFirstAttempt` dihitung: `! EvaluationSubmission::where('user_id', ...)->where('unit_id', ...)->exists()` (baris 93-95) — SEBELUM baris insert, jadi baris pertama selalu `true`.

**Asimetri yang saya temukan:** aturan ini HANYA ada di `submitQuiz()`.
`submitFreeText()` (esai/praktik) TIDAK punya `$isFirstAttempt` sama
sekali — `points_awarded` di situ SELALU `$this->unit->point_value`
apa pun jumlah attempt-nya (baris 165). Tidak masalah dalam praktik
karena alasan di poin 7, tapi kalau nanti ada yang membaca kolom
`points_awarded` mentah-mentah untuk hal lain, perbedaan perlakuan quiz
vs esai/praktik ini perlu diketahui.

**5. Cek "sudah pernah attempt":** field yang dipakai adalah keberadaan
baris `EvaluationSubmission` untuk `[user_id, unit_id]` itu (bukan kolom
counter/flag terpisah) — `EvaluationSubmission::where(...)->exists()`.

**6. Retry: TIDAK dibatasi/dikunci sama sekali** sekarang. User bisa
klik "Coba Lagi" (`retry()`, cuma reset `$mode` ke `'form'`, tidak ada
pengecekan jumlah percobaan) berkali-kali tanpa batas, dites eksplisit
(`test_quiz_retry_after_wrong_answer_does_not_double_award_points`).
Ini artinya **v1.0 SUDAH punya "retry tanpa batas"** — bagian yang benar-benar
baru di v2.0 bukan "buka retry" (sudah terbuka), tapi "skor terbaik
dipakai untuk poin/leaderboard" (lihat temuan kritis poin 7 & bagian G).

**7. TEMUAN PALING PENTING — bagaimana poin kuis benar-benar masuk ke
`total_points`:** LEWAT `completeUnit()` → `awardPoints()`, **BUKAN
lewat membaca `EvaluationSubmission.points_awarded` sama sekali.**
Dikonfirmasi dengan grep `points_awarded` di seluruh `app/` — kolom itu
DITULIS di 2 tempat (`UnitEvaluation.php`) tapi **tidak pernah DIBACA**
oleh `ProgressService` atau kode manapun. `awardPoints()` dipanggil
dengan `$unit->point_value` (nilai TETAP per-unit dari kolom
`units.point_value`), bukan dari `$submission->points_awarded`.
Proteksi anti-dobel-poin datang MURNI dari idempotency check
`completeUnit()` sendiri (`$alreadyCompleted = $progress->exists &&
$progress->status === 'completed'`) berbasis `UserUnitProgress.status`
— sama sekali independen dari isi `EvaluationSubmission`.

**Konsekuensi penting untuk v2.0:** `EvaluationSubmission.points_awarded`
saat ini adalah **kolom archival murni, write-only, tidak pernah dibaca**
oleh sistem manapun (dikonfirmasi juga tidak dipakai `feedFor()` — feed
aktivitas pakai `UserUnitProgress.completed_at`, bukan
`EvaluationSubmission`). Mengubah cara kolom ini dihitung untuk v2.0
**TIDAK berisiko memutus konsumen mana pun**, karena tidak ada konsumen
sama sekali hari ini.

## C. Struktur data submission

**8. Field aktual di migrasi** (`database/migrations/2026_07_02_235329_create_evaluation_submissions_table.php`):
`id` (uuid), `user_id`, `unit_id`, `answers` (json), `is_correct`
(boolean nullable), `points_awarded` (integer), `submitted_at`
(timestamp nullable) — **persis sesuai dokumen arsitektur**, tidak ada
selisih.

**Per-attempt, BUKAN di-overwrite** — dikonfirmasi tidak ada unique
constraint `[user_id, unit_id]` di migrasi (beda dari
`checkpoint_completions` dan `user_unit_progress` yang SAMA-SAMA punya
constraint itu). `EvaluationSubmission::create()` selalu INSERT baris
baru, tidak pernah `update()`/`updateOrCreate()`. Dites eksplisit:
`test_quiz_retry_after_wrong_answer_does_not_double_award_points`
memverifikasi ADA 2 baris submission setelah 2x percobaan
(`assertSame(2, EvaluationSubmission::where(...)->count())`).

**9. Tidak ada kolom `attempt_number`.** Urutan percobaan dibedakan lewat
`id` (UUIDv7, time-sortable ke presisi milidetik) — dipakai eksplisit di
`UnitEvaluation::mount()` (`orderByDesc('id')->first()`, dengan komentar
kode yang menjelaskan kenapa `id` dipilih dibanding `submitted_at`: retry
cepat dalam detik yang sama tetap terurut benar). **Data SUDAH CUKUP
untuk skor terbaik** dari sisi "semua attempt tersimpan" — tapi lihat
temuan penting di bagian G soal APA yang sebenarnya bisa dibandingkan
sebagai "skor".

## D. Feedback jawaban vs kunci

**10. Implementasi sekarang** (`unit-evaluation.blade.php:8-61`, mode
`'result'`): per soal, tampilkan jawaban user vs kunci jawaban
(`$detail['correct_answer']`), dengan styling beda (border accent kalau
benar, border muted kalau salah). Detail per tipe soal:
- Pilihan ganda: teks jawaban user + status "tepat!"/"belum tepat" +
  kunci jawaban kalau salah.
- Matching: tiap pasangan ditampilkan, kanan-kiri, ditandai
  benar/salah per pasangan, teks muted untuk yang salah.
- Ordering: urutan user ditampilkan, kalau salah tampilkan juga urutan
  yang benar di bawahnya.
- Esai: tidak ada kunci jawaban (memang tidak dinilai), cuma tampilkan
  balik jawaban user sendiri.

Data ini dibangun di `UnitEvaluation::showResultFor()`
(baris 190-226), dari `$submission->answers` (jawaban tersimpan) + kunci
jawaban unit itu sendiri — bukan dari field baru manapun.

## E. Keterkaitan dengan sistem lain

**11. Rantai lengkap:** `submitQuiz()`/`submitFreeText()`/`markAsRead()`
→ (submit case) buat `EvaluationSubmission` (kalau relevan) → panggil
`ProgressService::completeUnit()` TANPA SYARAT KEBENARAN → di dalam
`completeUnit()`: `UserUnitProgress.status` diset `'completed'`
(idempoten via `firstOrNew`), BARU KALAU baris ini baru pertama kali
`completed` → panggil `awardPoints($user, $unit->point_value)`. Jadi
YA, submit kuis (apa pun hasilnya) menandai unit `completed`, dan
PERUBAHAN STATUS itu sendiri (bukan isi submission) yang memicu
`awardPoints()`.

**12. Dampak berantai kalau skor terbaik mengubah `points_awarded`:**
- **Ke `total_points`/`resolveLevel()`: TIDAK ADA DAMPAK LANGSUNG**, justru
  karena temuan poin 7 — `awardPoints()` tidak pernah membaca
  `EvaluationSubmission.points_awarded`. Kalau v2.0 cuma mengubah NILAI
  yang ditulis ke kolom itu (mis. berdasar skor terbaik), tapi
  `completeUnit()`/`awardPoints()` tetap dipanggil dengan cara yang sama
  seperti sekarang, `total_points` dan level tidak terpengaruh sama
  sekali oleh perubahan itu sendiri.
- **TAPI** kalau v2.0 juga ingin `total_points` benar-benar
  MEREFLEKSIKAN skor terbaik (misal, unit dengan skor rendah dapat poin
  LEBIH SEDIKIT dari `point_value` penuh, bukan selalu penuh seperti
  sekarang) — itu BUKAN sekadar ubah `EvaluationSubmission`, itu berarti
  `awardPoints()` perlu dipanggil dengan angka yang BEDA dari
  `$unit->point_value` tetap, DAN `completeUnit()`'s idempotency
  (sekarang: sekali completed, tidak pernah re-award) perlu berubah jadi
  "boleh re-award kalau skor attempt baru lebih baik dari sebelumnya" —
  ini PERUBAHAN LOGIC NYATA di `ProgressService`, bukan cuma di
  `UnitEvaluation`. **Ini keputusan desain yang menentukan seberapa besar
  perubahannya** (lihat bagian G).
- **Ke WEBI**: `PersonalizationContextBuilder`/`SystemPromptBuilder`
  baca `total_points`/`completed_units` (dari `UserUnitProgress`), tidak
  pernah baca `EvaluationSubmission` langsung — jadi WEBI TIDAK
  terdampak oleh perubahan cara kuis dinilai, selama `total_points` dan
  `UserUnitProgress.status` tetap diisi dengan makna yang sama.

## F. Test terkait

**Test mekanisme LAMA (akan sengaja diganti/dihapus per aturan regresi
Roadmap 2.2, BUKAN dipertahankan apa adanya):**
- `tests/Feature/Exploration/UnitEvaluationTest.php::test_quiz_retry_after_wrong_answer_does_not_double_award_points`
  — inti aturan v1.0: attempt kedua dapat `points_awarded = 0`
  literal di baris submission. Ini test yang PALING LANGSUNG
  mengasumsikan mekanisme v1.0.

**Test yang KEMUNGKINAN BESAR tetap relevan, tapi assersi
`points_awarded`-nya perlu ditinjau ulang** (karena semuanya menegaskan
"attempt pertama = `points_awarded` penuh", asumsi yang valid di v1.0
tapi belum tentu di v2.0 kalau skor terbaik ≠ selalu-penuh):
- `test_quiz_submission_with_all_correct_answers_awards_points_and_marks_correct`
- `test_quiz_submission_with_wrong_answer_still_awards_points_but_marked_incorrect`
- `test_essay_submission_auto_approves_with_null_is_correct`
- `test_practice_submission_auto_approves`
- `test_mixed_quiz_essay_unit_completes_once_essay_is_filled`

**Test yang TIDAK bergantung pada mekanisme poin sama sekali (harus
tetap hijau apa adanya):**
- `test_wrong_quiz_answer_shows_per_question_feedback_and_correct_answer`
- `test_correct_quiz_answer_does_not_offer_retry`
- `test_reopening_unit_after_wrong_quiz_still_allows_retry`
- `test_mixed_quiz_essay_unit_requires_essay_answer_before_completion`
- `test_empty_essay_answer_is_rejected`
- `tests/Feature/Exploration/MatchingAndOrderingEvaluationTest.php` —
  seluruhnya soal UI/grading matching-ordering, bukan poin.

**Test lain yang MENYINGGUNG kuis/poin tapi cuma insidental** (dipakai
sebagai bagian dari smoke test lintas sistem, bukan menguji mekanisme
poin kuis itu sendiri): `tests/Feature/Integration/FullSystemSmokeTest.php`,
`tests/Feature/Integration/CrossModuleEndToEndTest.php`,
`tests/Feature/Webi/ProactiveTest.php`.

## G. Penilaian

**TEMUAN PALING KRITIS UNTUK KEPUTUSAN DESAIN:** saat ini **tidak ada
skor NUMERIK sama sekali** untuk kuis — cuma `is_correct` boolean
(SEMUA soal benar → `true`, ada yang salah → `false`). Tidak ada
"3 dari 5 benar" tersimpan di mana pun. **"Skor terbaik" di 2.1.2 belum
punya definisi teknis yang jelas** dari kode yang ada — perlu diputuskan
dulu SEBELUM implementasi:
- **Opsi sederhana**: "skor terbaik" = kalau SALAH SATU attempt pernah
  `is_correct = true`, hitung sebagai fully-correct (poin penuh)
  selamanya, apa pun urutan attempt. Ini cuma butuh "OR logic" atas
  `is_correct` yang SUDAH ada, tidak perlu skor numerik baru.
- **Opsi penuh**: skor numerik per attempt (mis. persentase soal benar),
  simpan skor tertinggi, poin proporsional ke skor itu. Ini butuh
  MENGHITUNG dan MENYIMPAN skor numerik yang belum pernah ada — migrasi
  kolom baru di `evaluation_submissions` (mis. `score_percentage`) BISA
  diperlukan, tergantung apakah cukup dihitung on-the-fly dari `answers`
  JSON tiap kali dibutuhkan (tidak perlu kolom baru) atau perlu disimpan
  untuk performa/kemudahan query leaderboard historis.

**Seberapa besar perubahannya, tergantung opsi di atas:**
- Kalau Opsi sederhana (OR is_correct): perubahan KECIL. Cukup ubah
  logic di `UnitEvaluation::submitQuiz()` — hapus gating
  `$isFirstAttempt`/retry-nol, ganti jadi cek "apakah user PERNAH benar
  di attempt manapun sebelum ini ATAU attempt ini sendiri benar" untuk
  menentukan apakah unit sudah truly-mastered. `completeUnit()`/
  `awardPoints()` di `ProgressService` **kemungkinan besar TIDAK perlu
  diubah sama sekali** — poin tetap diberikan penuh saat submission
  pertama (participation-based, seperti sekarang), skor terbaik cuma
  memengaruhi TAMPILAN/status "sudah pernah benar" bukan JUMLAH poin.
  **Tidak perlu migrasi.**
- Kalau Opsi penuh (skor numerik proporsional): perubahan LEBIH BESAR.
  `ProgressService::awardPoints()`/`completeUnit()` PERLU diubah supaya
  bisa "re-award selisih" ketika attempt baru lebih baik dari
  sebelumnya (sekarang murni idempoten sekali-award, tidak ada mekanisme
  "tambah/kurangi poin dari unit yang sudah completed"). Ini menyentuh
  titik yang recon poin kemarin (`RECON_sistem_poin.md`) identifikasi
  sebagai satu-satunya penulis `total_points` — **titik paling
  berisiko** kalau opsi ini yang dipilih, karena mengubah asumsi dasar
  "unit completed = sudah final, tidak pernah dihitung ulang" yang
  berlaku di seluruh sistem poin saat ini (termasuk checkpoint).
  Mungkin perlu kolom skor baru di `evaluation_submissions`.

**Titik paling berisiko keseluruhan:** BUKAN soal "cukup data" (data attempt
history sudah lengkap tersimpan, tidak ada yang ditimpa) — risiko
sebenarnya ada di **keputusan desain skor terbaik** itu sendiri (opsi
sederhana vs penuh) dan **apakah `completeUnit()`'s aturan "sekali
completed, tidak pernah dihitung ulang" perlu dilonggarkan**. Ini
keputusan produk, bukan keterbatasan teknis — rekomendasi saya (bukan
keputusan final): opsi sederhana (OR is_correct) sudah cukup memenuhi
bunyi literal "skor terbaik dari seluruh percobaan dipakai" kalau
"skor" didefinisikan biner (pernah-benar vs tidak-pernah-benar), dan itu
JAUH lebih kecil risikonya dibanding membongkar asumsi
idempotency `ProgressService`.
