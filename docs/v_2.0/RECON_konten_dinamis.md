# Recon — Konten Materi v1.0 (Persiapan Modul Konten Dinamis 2.2.4)

Laporan ini murni observasi kode & data NYATA saat ini. **Tidak ada kode/skema
yang diubah.** Konteks target (blok konten, `content_blocks`) dibaca dari
`docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Arsitektur_Konten_Dinamis_v2.md` hanya untuk pembanding —
bagian ini TIDAK dikerjakan di sini.

## A. Struktur konten unit sekarang

**1. Kolom `units.content`** — tipe `text` (bukan JSON/markdown khusus),
lihat `database/migrations/2026_07_02_235327_create_units_table.php:19`.
Isinya teks polos berbahasa Indonesia dengan **markdown terbatas inline**
(`**bold**`) dan paragraf dipisah baris kosong ganda (`\n\n`). Contoh nyata,
Unit 1.1 "Apa Itu Software Development?" (`database/seeders/CurriculumSeeder.php:69-87`):

```
Mari mulai dari yang paling dasar. **Software** adalah kumpulan instruksi...

Lalu apa itu **software development**? ...

[SAJIKAN: tabel perbandingan — tiga istilah: Coding, Programming, Software Development]

- **Coding** adalah kegiatan menulis kode...
- **Programming** sedikit lebih luas dari coding...

[SAJIKAN: callout — Poin penting: Jadi kalau nanti kamu mendengar orang berkata...]

Kenapa disebut "development"...
```

Unit 1.2 (`CurriculumSeeder.php:129-147`) contoh lain: paragraf naratif +
`[SAJIKAN: kartu — satu kartu per peran, berisi nama peran, satu kalimat inti,
dan ikon]` diikuti daftar `**Nama Peran.** deskripsi...` per baris.

**2. Direktif khusus yang ditemukan — semuanya varian `[SAJIKAN: <jenis> — <deskripsi>]`:**
Hanya SATU keluarga direktif, `[SAJIKAN: ...]`, dengan 6 variasi "jenis" yang
dipakai (dihitung dari seluruh seeder, 190 kemunculan total):

| Jenis `[SAJIKAN: ...]` | Jumlah | Contoh |
|---|---|---|
| `callout` | 71 | `[SAJIKAN: callout — Poin penting: ...]` |
| `kartu` | 34 | `[SAJIKAN: kartu — satu kartu per peran...]` |
| `tabel perbandingan` | 28 | `[SAJIKAN: tabel perbandingan — Library vs Framework...]` |
| `diagram alur` | 27 | `[SAJIKAN: diagram alur — tujuh tahap SDLC berurutan...]` |
| `blok kode` | 21 | lihat poin 6 di bawah — kadang MULTI-BARIS |
| `infografis` | 9 | `[SAJIKAN: infografis — anatomi sebuah URL...]` |

Tidak ada direktif lain selain keluarga `[SAJIKAN: ...]` ini (sudah digrep
`[REKOMENDASI`, `[CALLOUT`, `[KODE`, dst secara terpisah — nol hasil di luar
`[SAJIKAN`, dan `[REKOMENDASI_UNIT:...]`/`[REKOMENDASI_MODUL:...]` yang memang
ada di sistem itu murni output RUNTIME dari WEBI, bukan bagian dari
`units.content`).

**Temuan penting (bukan cuma dokumentasi arsitektur — ini kondisi hidup
sekarang):** `docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md` baris 274 eksplisit bilang direktif
`[SAJIKAN: ...]` "instruksi frontend, bukan konten yang dibaca user". Itu
DIPATUHI untuk sisi WEBI — `CurriculumContextBuilder::stripDirectives()`
(`app/Services/Webi/CurriculumContextBuilder.php:130-133`) men-strip semua
`[SAJIKAN:...]` sebelum masuk prompt Gemini, dan dites eksplisit
(`tests/Feature/Webi/PersonalizationTest.php:83-109`,
`test_current_unit_content_is_injected_with_sajikan_directives_stripped`).

**TAPI stripping ini TIDAK PERNAH ada di halaman unit member sendiri.**
`resources/views/livewire/eksplorasi/unit-show.blade.php:15-19` me-render
`$unit->content` APA ADANYA:
```blade
@foreach (explode("\n\n", $unit->content) as $paragraph)
    <p>{{ $paragraph }}</p>
@endforeach
```
Tidak ada `stripDirectives()`/`preg_replace` apa pun di jalur ini. Artinya
**anggota eksplorasi SAAT INI benar-benar melihat teks mentah seperti
`[SAJIKAN: kartu — satu kartu per peran, berisi nama peran, satu kalimat
inti, dan ikon]` sebagai paragraf biasa di 190 titik** tersebar di seluruh 67
unit — bukan risiko migrasi, ini gap yang SUDAH LIVE. Tidak ada test yang
menjaga ini (`tests/Feature/Exploration/CurriculumNavigationTest.php` cuma
`assertSee($unit->title)`/cek status lock, tidak pernah assert isi paragraf
konten atau ketiadaan `[SAJIKAN`). Layak diperbaiki independen dari migrasi
blok penuh kalau mau (strip sementara di `unit-show` sampai migrasi blok
selesai) — TAPI ini di luar scope recon ini untuk diputuskan/dikerjakan.

**3. Rendering sekarang:** `resources/views/livewire/eksplorasi/unit-show.blade.php`
(Livewire, `App\Livewire\Eksplorasi\UnitShow`) — TIDAK ada markdown parser
sama sekali untuk konten unit (beda dari chat WEBI yang sudah pakai
CommonMark lewat `MessageRenderer::toSafeHtml()` sejak 2.5). `**bold**` di
`units.content` dirender APA ADANYA sebagai teks `**Software**` literal
dengan tanda bintang terlihat — bukan bold sungguhan. Ini juga gap live,
bukan cuma soal migrasi ke blok.

**4. Jumlah unit & modul — dikonfirmasi dari seeder (bukan dari roadmap saja):**
`grep -c 'Unit::create('` → **67**. `grep -c 'Module::create('` → **10**.
Juga dikonfirmasi: `Checkpoint::create(` → 9 (bukan 10 — satu modul tidak
punya checkpoint tersendiri, tidak diselidiki lebih jauh karena di luar
scope recon ini), `LearningResource::create(` → 26.

## B. Ragam isi konten

**5. Distribusi jenis konten** (dari tabel di poin 2, plus pengamatan
tambahan): naratif/paragraf biasa mendominasi (semua 67 unit), lalu callout
(71x, paling sering), kartu/card grid (34x), tabel (28x), diagram alur (27x),
blok kode (21x), infografis (9x). List/enumerasi manual (`- item` atau
`**Judul.** teks`) muncul luas (134 baris cocok pola `- ` atau `1. `),
biasanya SEBAGAI teks naratif biasa, bukan ditandai direktif terpisah.

**6. Konten kompleks/tidak biasa yang berpotensi sulit dimigrasi ke 8 tipe
blok yang dirancang:**
- **`tabel perbandingan` (28x) — TIDAK ADA tipe blok "Tabel" di rancangan
  8 tipe** (Heading/Teks/Gambar/Callout/Kode/Video/List/Custom HTML). Kalau
  mau tetap tabel sungguhan, harus lewat `custom_html`, atau diterjemahkan
  jadi kombinasi List/Teks (kehilangan bentuk tabel). Contoh sungguhan tabel
  markdown literal juga ditemukan di konten (`CurriculumSeeder.php:733-738`,
  bukan cuma direktif SAJIKAN tapi tabel markdown asli `| Perintah | Fungsi |`)
  — jadi ada DUA bentuk tabel: direktif `[SAJIKAN: tabel...]` (deskriptif,
  belum berupa data tabel) dan tabel markdown literal (sudah berbentuk baris
  |kolom|kolom|). Keduanya butuh keputusan pemetaan berbeda.
- **`kartu` (34x) dan `infografis` (9x) — juga TIDAK ADA tipe blok yang
  cocok langsung.** Ini deskripsi visual ("satu kartu per peran, berisi nama
  peran, satu kalimat inti, dan ikon") yang butuh INTERPRETASI manusia untuk
  diterjemahkan ke kombinasi blok (mis. List bertingkat, atau beberapa blok
  Teks/Heading berurutan, atau `custom_html` kalau mau bentuk kartu visual
  sungguhan). Tidak otomatis 1:1.
- **`diagram alur` (27x)** — deskripsi diagram (mis. "tujuh tahap SDLC
  berurutan dengan panah... beri panah melingkar kembali dari Maintenance")
  juga tidak punya tipe blok langsung; realistisnya jadi List bernomor atau
  `custom_html` kalau ingin visual panah sungguhan.
- **`blok kode` (21x) — sebagian MULTI-BARIS di dalam satu direktif**, bukan
  selalu satu baris. Contoh (`CurriculumSeeder.php:742-745`):
  ```
  [SAJIKAN: blok kode — contoh sesi terminal sederhana yang menunjukkan membuat folder lalu masuk ke dalamnya:
  mkdir latihan_pertama
  cd latihan_pertama
  pwd]
  ```
  Kode aktual (`mkdir latihan_pertama` dst) ada DI DALAM tanda kurung siku
  direktif, tercampur dengan deskripsi. Ini paling mudah dipetakan ke blok
  Kode (`language` + `code`) TAPI butuh parsing manual untuk memisahkan
  "instruksi penyajian" dari "kode aktual yang harus disalin ke field `code`"
  — tidak bisa auto-extract naif (mis. `mkdir` dkk perlu dipisah dari kalimat
  "contoh sesi terminal sederhana yang menunjukkan...").
- **Bold inline `**teks**`** sudah cocok 1:1 dengan spesifikasi blok Teks/Callout/List
  di rancangan ("mendukung **bold**, *italic*, [link](url)") — migrasi paling
  mulus dari semua elemen yang ada.

**7. Gambar di konten sekarang: TIDAK ADA SAMA SEKALI.** Digrep seluruh
`units.content` untuk URL/ekstensi gambar (`.png/.jpg/.jpeg/.svg/.gif`,
`http(s)://`) — SEMUA 31 kecocokan yang ditemukan adalah:
- `LearningResource::create(..., 'url' => ...)` — model **terpisah**
  (`resources` Eksplorasi, bukan bagian `units.content`), 26 baris.
- URL contoh DI DALAM teks penjelasan (mis. `git remote add origin
  https://github.com/username/nama-repo.git]` sebagai contoh perintah,
  `<a href="https://google.com">` sebagai contoh syntax HTML) — bukan
  gambar, cuma teks URL sebagai materi ajar.

Nol unit yang benar-benar meng-embed gambar/video. Rancangan blok "Gambar"
(`url` eksternal, TANPA upload file) jadi 100% belum pernah dipakai — bukan
migrasi dari sesuatu yang ada, murni kapabilitas baru. Isu upload Modul 6
yang disebut di CLAUDE.md **BUKAN soal gambar** — itu soal submission tugas
praktik (file HTML/CSS anggota), sama sekali beda dari blok Gambar/Video di
rancangan konten. Lihat poin 8.

## C. Utang teknis yang mau ditutup

**8. Modul 6 (upload submission) dan Unit 5.8 (UI evaluasi campuran) — dicek
kondisi sekarang:**

- **Modul 6, intermezo file upload**: `CurriculumSeeder.php:2504-2508`,
  checkpoint Modul 6 (`seedModule6()`) — teks `intermezo_questions` literal
  minta "Tugas praktik dengan pengumpulan file... klik tombol 'Kumpulkan'".
  **Tidak ada mekanisme upload file di manapun di aplikasi** (dikonfirmasi
  ulang, sudah tercatat di CLAUDE.md sejak 2.3, masih berlaku persis sama
  sekarang — tidak ada perubahan). Field `intermezo_questions` cuma teks
  deskriptif di `Checkpoint`, bukan tempat submission sungguhan. Ini
  **TIDAK terkait `units.content`/blok konten** — file upload adalah
  fitur submission terpisah (kemungkinan entitas baru semacam
  `IntermezoSubmission`), bukan salah satu dari 8 tipe blok yang dirancang.
  Rancangan konten dinamis menyebut ini "sekaligus ditutup" tapi secara
  teknis ini pekerjaan BERBEDA sistem dari migrasi blok — perlu keputusan
  eksplisit apakah benar mau digabung satu batch atau dipisah.
- **Unit 5.8, UI evaluasi campuran (quiz + esai)**: LOGIC-nya sudah benar
  sejak 2.3 (`App\Livewire\Eksplorasi\UnitEvaluation.php:148-153`, esai
  divalidasi wajib diisi tapi tidak ikut grading `$allCorrect`). Yang masih
  utang murni **UI/tampilan** — komentar di kode (baris 148) mengonfirmasi
  ini masih pakai "tampilan quiz biasa + textarea tambahan", bukan UI
  gabungan yang dirancang khusus. Ini juga **bukan bagian `units.content`**
  — soal esai/pilihan-ganda disimpan di tabel TERPISAH `unit_evaluations`
  (`question_type` enum termasuk `essay`), bukan di `content`. Migrasi blok
  konten TIDAK akan otomatis memperbaiki ini — perlu pekerjaan UI terpisah
  di `UnitEvaluation` Livewire component/view, hanya KEBETULAN disebut di
  rancangan yang sama karena sama-sama "utang UI kurikulum".

## D. Skema & relasi

**9. Skema `units` lengkap** (`database/migrations/2026_07_02_235327_create_units_table.php`):

| Kolom | Tipe |
|---|---|
| `id` | uuid, PK |
| `module_id` | uuid, FK → `modules`, cascade delete |
| `order_number` | integer |
| `title` | string |
| `content` | text |
| `estimated_minutes` | integer, default 15 |
| `unit_type` | enum: `concept`, `practice` |
| `point_value` | integer |
| `evaluation_type` | enum: `quiz_multiple_choice`, `quiz_matching`, `quiz_ordering`, `essay`, `practice`, `none` |
| `prerequisite_unit_id` | uuid, FK → `units` (self), nullable, null-on-delete |
| `created_at`/`updated_at` | timestamp |

Relasi (`app/Models/Unit.php`): `module()` (belongsTo Module), `prerequisiteUnit()`/
`dependentUnits()` (self-referencing), `evaluations()` (hasMany UnitEvaluation),
`userProgress()` (hasMany UserUnitProgress), `evaluationSubmissions()` (hasMany
EvaluationSubmission), `forumThreads()`, `messages()` (via `unit_context`, WEBI).

Tabel terkait yang RELEVAN untuk migrasi (skema, bukan isi):
- `unit_evaluations`: `question_type` (`multiple_choice`/`matching`/`ordering`/`essay`/`practice`),
  `question_text`, `options` (json), `correct_answer` (json), `sort_order` —
  **terpisah total dari `units.content`**, tidak terdampak migrasi blok.
- `user_unit_progress`: `status`, `open_count_without_completion`, `completed_at`
  — juga tidak baca `content` sama sekali, murni status.

**10. `content_blocks` — BELUM ADA SAMA SEKALI di skema.** Digrep seluruh
`database/migrations/` untuk nama tabel/kolom `content_block` — nol hasil.
Tidak disiapkan sejak fondasi (beda dari field seperti `interest_field`/
`avatar_url` di modul lain yang memang sudah disiapkan sejak 2.0 tapi belum
dipakai) — ini benar-benar tabel baru yang perlu migrasi dari nol kalau mau
dibangun.

**11. Konsumen `units.content` — SEMUA titik pemakaian, dikonfirmasi lewat
grep `->content` di `app/`:**
- `resources/views/livewire/eksplorasi/unit-show.blade.php` (halaman member,
  poin 3 — TANPA strip direktif).
- `App\Services\Webi\CurriculumContextBuilder::build()` (WEBI, DENGAN strip
  direktif via `stripDirectives()`), dipanggil untuk unit saat ini DAN unit
  "relevan" (`relatedUnits()` — query `LIKE` ke kolom `content` juga,
  bukan cuma `title`, jadi ini KEDUA fungsi WEBI bergantung pada bentuk teks
  polos `content` sekarang, bukan cuma satu).

Tidak ada konsumen lain — tidak dipakai di evaluasi (evaluasi baca
`unit_evaluations`, bukan `content`), tidak ada fitur search terpisah di
aplikasi ini (dikonfirmasi tidak ada halaman/endpoint "cari materi" di
`routes/web.php`).

## E. WEBI CurriculumContextBuilder

**12. Cara baca `units.content` sekarang, persis** (`app/Services/Webi/CurriculumContextBuilder.php`):
- `build(?Unit $currentUnit, string $userQuestion)` (baris 35-57): kalau ada
  `$currentUnit`, ambil `$currentUnit->content` mentah, panggil
  `stripDirectives()`, gabung dengan label `"Unit yang sedang dikerjakan
  user (unit_id: ..., judul: ...):\n"`.
- `relatedUnits()` (baris 94-116): query `Unit::where('content', 'LIKE',
  "%{keyword}%")` (plus `title`) untuk cari unit "relevan" berdasar
  keyword overlap pertanyaan user — **keyword search LANGSUNG ke teks
  polos**, termasuk ke teks direktif `[SAJIKAN: ...]` itu sendiri (belum
  di-strip pada tahap pencarian, baru di-strip setelah unit ditemukan).
  Kalau `content` pindah ke blok terstruktur (json per blok, bukan satu
  kolom teks panjang), query `LIKE` sederhana ini **wajib diubah total** —
  tidak bisa lagi `WHERE content LIKE`, perlu gabungan dari isi `data->text`
  di seluruh blok milik unit itu (query JSON atau baca lewat relasi lalu
  filter di PHP).
- `stripDirectives()` (baris 130-133): `preg_replace('/\[SAJIKAN:.*?\]/su',
  '', $content)` — regex ini SENGAJA `dotall` (`s`) supaya menangkap
  direktif multi-baris (poin B.6, `blok kode` yang membentang beberapa
  baris). Kalau `content` pindah ke blok, method ini (dan seluruh konsep
  "strip direktif") **jadi tidak relevan lagi** — blok terstruktur tidak
  akan pernah punya direktif `[SAJIKAN: ...]` tersisa (sudah otomatis
  terpisah by design per tipe), method ini bisa dihapus, diganti "loop
  blok, render `data.text` per tipe yang relevan untuk teks WEBI" (skip
  tipe non-teks seperti Gambar/Video mentahnya, atau sertakan caption-nya
  saja).

## F. Test terkait

| File | Yang diuji | Terdampak migrasi? |
|---|---|---|
| `tests/Feature/Webi/PersonalizationTest.php` (`test_current_unit_content_is_injected_with_sajikan_directives_stripped`) | Strip direktif SAJIKAN di context WEBI | **YA, langsung** — assert `str_contains($prompt, 'RELEVANT_CURRICULUM_CONTENT') && !str_contains($prompt, '[SAJIKAN:')`. Kalau `content` pindah ke blok (tidak ada literal `[SAJIKAN:` lagi di data blok), assertion kedua otomatis lolos tapi jadi TIDAK BERGUNA (menguji sesuatu yang sudah mustahil terjadi) — perlu ditulis ulang untuk menguji "blok non-teks tidak disertakan mentah"/"urutan blok terjaga", bukan sekadar cek string SAJIKAN. |
| `tests/Feature/Webi/ChatTest.php`, `VoiceModeTest.php`, `ProactiveTest.php`, `GuardrailTest.php` | Membuat fixture `Unit` dengan `content` teks biasa (banyak dipakai sebagai setup, bukan diuji langsung) | Perlu factory/fixture disesuaikan kalau `content` berhenti jadi sumber utama — TAPI rancangan (bagian 3) bilang kolom `content` DIPERTAHANKAN sebagai arsip sampai migrasi manual per unit selesai, jadi fixture test yang isi `content` langsung TETAP valid sampai keputusan lain diambil. |
| `tests/Feature/Exploration/CurriculumNavigationTest.php` | `assertSee($unit->title)`, status lock — TIDAK assert isi `content` atau ketiadaan `[SAJIKAN` | Tidak terdampak migrasi, tapi juga TIDAK melindungi dari gap di A.2 (leak `[SAJIKAN` ke user) sekarang. |
| `tests/Feature/Integration/CrossModuleEndToEndTest.php` | Menyebut `SAJIKAN`/konten unit sebagai bagian smoke-test alur penuh | Kemungkinan kecil terdampak (pemakaian tidak literal cek regex SAJIKAN, cuma memakai unit sungguhan dari seeder) — perlu dicek ulang saat migrasi beneran dikerjakan, di luar scope baca-saja recon ini untuk memastikan detail persis. |

**Tidak ada test khusus untuk `content_blocks`** — wajar, tabelnya belum ada.

## G. Penilaian

**14. Skala & risiko migrasi 67 unit — CAMPURAN, condong manual, TIDAK bisa
auto-convert penuh.** Alasan konkret dari B.6:
- Elemen yang mulus di-auto-convert: paragraf naratif → blok Teks, `**bold**`
  inline → tetap didukung native di blok Teks/Callout/List (tidak perlu
  diterjemahkan). Ini porsi TERBESAR dari volume teks (dominan di semua 67 unit).
- Elemen yang WAJIB keputusan manusia per kemunculan (190 direktif `[SAJIKAN:
  ...]` + tabel markdown literal): `callout` (auto-mapping jelas ke blok
  Callout, tapi variant info/tip/peringatan perlu dipilih manual per kasus
  dari kalimat "Poin penting"/"Tips"/"Catatan"), `kartu`/`infografis`/`diagram
  alur` (TIDAK ADA blok yang cocok langsung — wajib keputusan desain: List,
  beberapa blok Teks, atau `custom_html`), `tabel perbandingan` (tidak ada
  blok Tabel — wajib `custom_html` atau List), `blok kode` multi-baris (perlu
  parsing manual pisah instruksi vs kode aktual).
- **Keputusan rancangan sendiri (bagian 6) sudah eksplisit: migrasi ini
  manual per unit, bukan auto-convert** — recon ini mengonfirmasi keputusan
  itu MEMANG diperlukan (bukan cuma pilihan gaya), karena struktur direktif
  yang ada tidak punya pemetaan 1:1 otomatis ke 8 tipe blok untuk >90 dari
  190 kemunculan (semua kecuali `blok kode` yang paling dekat 1:1).

**15. Titik paling berisiko:**
- **Kehilangan/salah tafsir maksud direktif visual** (`kartu`, `diagram
  alur`, `infografis`) — ini deskripsi INTENSI ("satu kartu per peran"),
  bukan konten final; migrator (manusia + Claude Code) bisa menafsirkan beda
  dari yang dimaksud penulis asli kalau tidak hati-hati, apalagi untuk 70
  kemunculan gabungan ketiga jenis ini.
- **Kode di dalam direktif `blok kode` yang membentang baris** — salah
  potong batas "deskripsi" vs "kode aktual" bisa membuat kode contoh yang
  tampil ke user jadi rusak/tidak bisa disalin dengan benar (mis. Unit yang
  isinya `mkdir latihan_pertama` dkk).
- **Query `relatedUnits()` WEBI berhenti berfungsi diam-diam** kalau
  `CurriculumContextBuilder` tidak diupdate SERENTAK dengan migrasi
  (`WHERE content LIKE` terhadap kolom yang mungkin sudah kosong/tidak
  dipakai lagi untuk unit yang sudah dimigrasi ke blok) — WEBI bisa
  kehilangan hasil "unit relevan" untuk unit yang sudah dimigrasi tanpa
  ada error yang kelihatan (silent degradation, bukan crash).
- **67 unit, migrasi bertahap** — kalau dikerjakan unit-per-unit sambil
  `content` lama tetap ada (sesuai rancangan bagian 3, dipertahankan sebagai
  fallback), perlu kejelasan: unit-show/WEBI baca dari MANA untuk unit yang
  SEBAGIAN sudah dimigrasi, sebagian belum? Rancangan tidak menyebutkan
  strategi "unit mana pakai jalur mana" secara eksplisit — potensi
  ambiguitas kalau tidak diputuskan dulu sebelum mulai (mis. flag
  `has_content_blocks` di `units`, atau cek `content_blocks()->exists()`).

**16. `content_blocks` BUTUH migrasi skema baru** (dikonfirmasi poin 10 —
belum ada sama sekali). Ini **memicu prasyarat backup DB production** sesuai
konvensi proyek (aturan wajib CLAUDE.md: perubahan skema butuh konfirmasi
eksplisit + best practice standar sebelum migrate di server live). Tidak
menyentuh/mengubah kolom `units.content` yang sudah ada (rancangan bagian 3
eksplisit: dipertahankan, tidak dihapus) — jadi migrasi ini ADDITIVE
(tabel baru + mungkin kolom penanda opsional di `units`), bukan destructive,
tapi tetap migrasi skema sungguhan yang perlu prosedur backup yang sama.

**17. Urutan kerja yang disarankan untuk 2.2.4:**
1. **Skema dulu** (`content_blocks` migration + model + relasi polymorphic
   `blockable`) — fondasi tanpa ini semua langkah lain tidak bisa mulai.
   Cadangan DB dulu sebelum migrate ke production nantinya.
2. **Renderer generik** (satu komponen Blade/Livewire per tipe blok,
   sesuai rancangan bagian 7) — dibangun & ditest dengan data DUMMY/manual
   dulu (beberapa blok contoh), BUKAN langsung terhadap 67 unit asli, supaya
   styling per tipe selesai sebelum konten sungguhan masuk.
3. **Update `CurriculumContextBuilder` SERENTAK dengan keputusan strategi
   dual-source** (poin 15) — ini yang paling berisiko kalau telat, karena
   WEBI harus tetap berfungsi benar untuk unit yang SUDAH dan BELUM
   dimigrasi secara bersamaan selama masa transisi 67 unit.
4. **Migrasi konten per unit** (manual, bertahap, per modul atau per
   beberapa unit sekaligus) — pekerjaan konten besar, cocok dipisah jadi
   sub-task tersendiri per modul (mis. "2.2.4a Modul 1-3", dst) daripada
   satu batch raksasa 67 unit sekaligus, supaya tiap sub-batch bisa
   divalidasi visual sebelum lanjut.
5. **Unit-show pindah baca dari `content_blocks`** untuk unit yang sudah
   dimigrasi, fallback ke `content` lama untuk yang belum — baru dilepas
   sepenuhnya setelah closing task ke-67.

Modul 6 (upload) dan Unit 5.8 (UI evaluasi) — per poin 8, keduanya BUKAN
bagian sistem blok konten secara teknis. Rekomendasi: putuskan eksplisit
apakah mau digarap SATU batch bersama migrasi konten (alasan rancangan:
"sekalian menutup utang UI kurikulum") atau dipisah jadi task sendiri-sendiri
mengingat keduanya menyentuh sistem yang sama sekali berbeda (`Checkpoint`/
submission baru untuk Modul 6; `UnitEvaluation` Livewire view untuk 5.8) dari
`content_blocks`/`units.content`.
