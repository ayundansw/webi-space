# Recon — Struktur Soal & Kunci Jawaban Evaluasi (Persiapan Panel Admin Kelola Evaluasi)

Laporan ini murni observasi kode & data NYATA saat ini. **Tidak ada kode/skema
yang diubah.**

## 1. Di mana soal & kunci jawaban disimpan

**Bukan kolom di tabel `units`** — soal ada di tabel terpisah **`unit_evaluations`**
(`database/migrations/2026_07_02_235328_create_unit_evaluations_table.php`),
relasi `hasMany` dari `Unit` (`$unit->evaluations()`), model `App\Models\UnitEvaluation`
(catatan: nama class ini SAMA dengan Livewire component `App\Livewire\Eksplorasi\UnitEvaluation`
— dua class berbeda, jangan tertukar).

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | uuid, primary | - |
| `unit_id` | foreignUuid &rarr; `units`, `cascadeOnDelete()` | NOT NULL |
| `question_type` | enum(`multiple_choice`, `matching`, `ordering`, `essay`, `practice`) | NOT NULL — **tanpa prefix `quiz_`** (beda dari `units.evaluation_type` yang pakai prefix `quiz_multiple_choice` dst, lihat poin 4) |
| `question_text` | text | NOT NULL |
| `options` | **json**, nullable | null untuk `essay`/`practice` |
| `correct_answer` | **json**, nullable | null untuk `essay`/`practice` |
| `sort_order` | integer | urutan tampil dalam satu unit |

Model `UnitEvaluation` cast `options` dan `correct_answer` sebagai `array` (`protected function casts()`), jadi di PHP keduanya sudah otomatis jadi array/null, bukan string JSON mentah.

`Unit.evaluation_type` (kolom ENUM di tabel `units`: `quiz_multiple_choice`, `quiz_matching`, `quiz_ordering`, `essay`, `practice`, `none`) menentukan MODE TAMPILAN keseluruhan unit (quiz form vs textarea vs tombol "tandai selesai"), sedangkan `question_type` per baris `unit_evaluations` menentukan cara RENDER & GRADING tiap soal individual. Keduanya biasanya selaras (unit `quiz_matching` isinya soal `question_type=matching`), KECUALI kasus campuran (lihat poin 4).

## 2. Contoh struktur data mentah per tipe (dari `database/seeders/CurriculumSeeder.php`)

**`multiple_choice`** (Unit 1.1, `sort_order` 1):
```php
UnitEvaluation::create([
    'unit_id' => $unit->id,
    'question_type' => 'multiple_choice',
    'question_text' => 'Manakah yang cakupannya paling luas: coding, programming, atau software development?',
    'options' => ['Coding', 'Programming', 'Software Development'],
    'correct_answer' => 'Software Development',
    'sort_order' => 1,
]);
```
`options` = array datar string. `correct_answer` = SATU string, harus sama persis (case-sensitive, `===`) dengan salah satu elemen `options`.

**`matching`** (Unit 1.2):
```php
UnitEvaluation::create([
    'unit_id' => $unit->id,
    'question_type' => 'matching',
    'question_text' => 'Cocokkan lima peran ... dengan deskripsi tugasnya masing-masing.',
    'options' => [
        'pairs' => [
            ['left' => 'Frontend Developer', 'right' => 'Bertanggung jawab atas bagian yang dilihat dan disentuh langsung oleh pengguna'],
            ['left' => 'Backend Developer', 'right' => 'Bertanggung jawab atas bagian yang bekerja di belakang layar dan tidak terlihat pengguna'],
            // ... 3 pasangan lagi
        ],
    ],
    'correct_answer' => [
        'Frontend Developer' => 'Bertanggung jawab atas bagian yang dilihat dan disentuh langsung oleh pengguna',
        'Backend Developer' => 'Bertanggung jawab atas bagian yang bekerja di belakang layar dan tidak terlihat pengguna',
        // ... assoc array, key = label kiri, value = label kanan yang benar
    ],
    'sort_order' => 1,
]);
```
`options` BUKAN array datar — dibungkus satu key `pairs`, isinya array of `['left' => ..., 'right' => ...]`. `correct_answer` BENTUKNYA BEDA dari `options`: assoc array datar `[left_label => right_label]`. Ini nested/asimetris — form admin untuk tipe ini paling rumit di antara semua tipe (lihat poin 6).

**`ordering`** (Unit 1.3):
```php
UnitEvaluation::create([
    'unit_id' => $unit->id,
    'question_type' => 'ordering',
    'question_text' => 'Susun tujuh tahap SDLC dalam urutan yang benar.',
    'options' => ['Perencanaan', 'Analisis Kebutuhan', 'Desain', 'Development', 'Testing', 'Deployment', 'Maintenance'],
    'correct_answer' => ['Perencanaan', 'Analisis Kebutuhan', 'Desain', 'Development', 'Testing', 'Deployment', 'Maintenance'],
    'sort_order' => 1,
]);
```
**Temuan penting:** di SEMUA contoh ordering di seeder, `options` dan `correct_answer` **identik persis** (array yang sama, urutan sama) — lihat poin 6 untuk kenapa ini relevan bagi desain form admin.

**`essay`** (Unit 1.5, unit evaluation_type=`essay`):
```php
UnitEvaluation::create([
    'unit_id' => $unit->id,
    'question_type' => 'essay',
    'question_text' => 'Dari empat tempat kerja yang dijelaskan ..., mana yang paling menarik untukmu saat ini dan kenapa?',
    'sort_order' => 1,
]);
```
Tidak ada `options`/`correct_answer` sama sekali (default null). `practice` (evaluation_type=`practice`) strukturnya IDENTIK dengan `essay` — cuma beda label semantik, sama-sama cuma `question_text` + tanpa kunci jawaban.

## 3. Bagaimana `UnitEvaluation::submitQuiz()`/`showResultFor()` membaca struktur ini

`app/Livewire/Eksplorasi/UnitEvaluation.php`:

- **Grading** (`isCorrectAnswer()`, baris 181-193): untuk `matching`, `ksort()` KEDUA sisi (`$selected` dari user dan `$question->correct_answer`) sebelum dibandingkan `===` — order-independent by design (dites eksplisit di `MatchingAndOrderingEvaluationTest::test_matching_pair_order_does_not_affect_correctness`). Untuk tipe lain (`multiple_choice`, `ordering`): `$selected === $question->correct_answer` langsung, strict & order-SENSITIVE (ordering sengaja begitu, urutan itu sendiri adalah jawabannya).
- **Validasi** (`submitQuiz()`, baris 114-120): rule Laravel per soal — `matching`/`ordering` divalidasi `['required', 'array', 'min:1']`, tipe lain (`multiple_choice`, `essay`) divalidasi `['required', 'string']`.
- **Essay di dalam unit quiz** (baris 151-153): kalau `question_type === 'essay'` (kasus campuran, lihat poin 4), dilewati dari pengecekan `isCorrectAnswer()` — essay TIDAK PERNAH ikut menentukan `$allCorrect`.
- **Render form** (`unit-evaluation.blade.php` baris 110-180): percabangan per `question_type` — `essay` &rarr; textarea, `matching` &rarr; dropdown per pasangan (baca `$question->options['pairs']`, populate `<option>` dari `pluck('right')` gabungan semua pasangan), `ordering` &rarr; list dengan tombol naik/turun (baca `$question->options` sebagai array datar), default (`multiple_choice`) &rarr; radio button dari `$question->options`.
- **Tampilan hasil** (`showResultFor()` baris 235-271, dan blade baris 17-70): untuk setiap soal, bandingkan `$submission->answers[$question->id]` (jawaban tersimpan) terhadap `$question->correct_answer` untuk highlight benar/salah + tampilkan kunci jawaban yang benar kalau salah.

## 4. Satu unit bisa punya BEBERAPA soal

**Ya.** Direpresentasikan sebagai BEBERAPA baris `UnitEvaluation` dengan `unit_id` yang sama, dibedakan `sort_order` (integer, urutan tampil). Contoh: Unit 1.1 punya 3 baris `multiple_choice` (`sort_order` 1, 2, 3).

**Kasus campuran (dicatat eksplisit di docblock `CurriculumSeeder.php` baris 22-29):** Unit dengan judul "Menangani Merge Conflict..." punya `evaluation_type = quiz_multiple_choice` TAPI isinya 3 baris `unit_evaluations`: 2 bertipe `multiple_choice` + 1 bertipe `essay` (`sort_order` terakhir). Form quiz merender ketiganya berurutan (2 radio group + 1 textarea), textarea WAJIB diisi (validasi `required`) tapi TIDAK ikut grading benar/salah. Dites eksplisit di `UnitEvaluationTest::test_mixed_quiz_essay_unit_requires_essay_answer_before_completion` dan `test_mixed_quiz_essay_unit_completes_once_essay_is_filled`.

`practice` juga selalu ditemukan sebagai SATU baris `question_type=practice` per unit (1:1 dengan `evaluation_type=practice`) — tidak pernah dicampur dengan tipe lain di data yang ada sekarang.

## 5. Test yang menyentuh struktur ini

| File | Assersi yang bergantung ketat pada bentuk struktur |
|---|---|
| `tests/Feature/Exploration/UnitEvaluationTest.php` | Iterasi generik `foreach ($questions as $question) { $component->set('quizAnswers.'.$question->id, $question->correct_answer); }` — bekerja untuk SEMUA tipe (termasuk matching/ordering) karena bentuk `correct_answer` untuk tiap tipe sudah persis bentuk yang dibutuhkan `quizAnswers` untuk soal itu. Kalau form admin nanti menghasilkan `correct_answer` dalam bentuk BEDA dari yang test asumsikan (mis. matching disimpan sebagai array-of-pairs, bukan assoc `left=>right`), test ini PECAH. `test_mixed_quiz_essay_unit_*` bergantung pada urutan `sort_order` (essay = `$questions->last()`). |
| `tests/Feature/Exploration/MatchingAndOrderingEvaluationTest.php` | Bergantung KETAT pada: (a) `correct_answer` matching berbentuk assoc array yang bisa di-iterate `foreach ($question->correct_answer as $left => $right)`; (b) `options` ordering berbentuk array datar bisa diindeks numerik (`$question->options[1]`); (c) **`options` ordering HARUS SUDAH dalam urutan benar saat seed** — `test_ordering_question_correct_sequence_is_graded_correct` submit TANPA menggerakkan apa pun dan mengharapkan benar, karena `UnitEvaluation::mount()` menginisialisasi `quizAnswers` dari `$question->options` apa adanya (baris 50). Kalau form admin nanti mengizinkan admin menyimpan `options` dalam urutan ACAK (bukan urutan benar) sementara `correct_answer` beda, test spesifik ini tidak pecah (dia pakai unit seed yang sudah pasti berurutan benar), TAPI ini mengekspos **temuan UX**, lihat poin 6. |

## 6. Penilaian

**Kompleksitas berbeda jauh antar tipe — TIDAK semuanya bisa pakai satu form generik sederhana:**

- **`multiple_choice`**: PALING SEDERHANA. Form terstruktur: repeater teks bebas untuk `options` (N baris), radio/dropdown pilih SATU dari opsi itu sebagai `correct_answer`. Tidak ada nested structure.
- **`ordering`**: SEDERHANA secara struktur (satu array datar dipakai dua kali — `options` dan `correct_answer`), tapi ada **keputusan desain yang perlu diambil sebelum bangun form**: apakah admin cuma input SATU urutan (dipakai untuk keduanya, sama seperti seed data sekarang — konsekuensinya user yang belum pernah mengubah apa pun bisa "benar" secara kebetulan karena tampilan awal sudah dalam urutan benar), atau admin input `options` TERACAK terpisah dari `correct_answer` (soal jadi benar-benar menguji, tapi butuh 2 input berbeda + UI form admin sedikit lebih rumit: "urutan tampil ke user" vs "urutan yang benar"). **Ini bukan cuma soal form, ini temuan UX di data produksi sekarang** yang layak diangkat sebagai keputusan eksplisit terpisah dari task form admin ini.
- **`matching`**: PALING RUMIT. `options` nested (`{pairs: [{left, right}, ...]}`) TIDAK sebangun dengan bentuk `correct_answer` (assoc array `left => right`) — form admin perlu (a) repeater pasangan kiri-kanan untuk `options.pairs`, DAN (b) otomatis MENURUNKAN `correct_answer` dari pasangan yang sama (bukan input terpisah — most straightforward: `correct_answer` = hasil transformasi `pairs` jadi assoc array saat form disimpan, supaya admin tidak perlu isi data yang sama dua kali dan tidak mungkin salah ketik antara `pairs` dan `correct_answer`).
- **`essay`/`practice`**: PALING SEDERHANA — cuma `question_text`, tidak ada `options`/`correct_answer` sama sekali.
- **Unit dengan BEBERAPA soal + kasus campuran** (poin 4): form admin perlu mendukung banyak baris soal per unit dengan `sort_order`, DAN mengizinkan campur `question_type` berbeda dalam satu unit `quiz_multiple_choice` (essay-di-dalam-quiz) — bukan pembatasan "satu unit = satu question_type", form perlu fleksibel per-baris.
- **Konsistensi enum**: form admin HARUS menulis `question_type` tanpa prefix `quiz_` (`multiple_choice`, bukan `quiz_multiple_choice`) — gampang tertukar dengan `evaluation_type` di tabel `units` yang justru PAKAI prefix. Salah satu risiko nyata kalau form admin dikembangkan tanpa membaca recon ini dulu.
