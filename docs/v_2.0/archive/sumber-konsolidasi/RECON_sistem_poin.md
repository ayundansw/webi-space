# Recon — Pemetaan Sistem Poin v1.0

Laporan ini murni observasi kode NYATA saat ini (dicek langsung via grep/read
seluruh `app/`, bukan diasumsikan). Dibuat sebagai bekal perencanaan 2.2.6
(penyatuan sistem poin saat modul Praktik dibangun). **Tidak ada kode yang
diubah.**

## A. Titik penambahan poin

Cuma **SATU method** di seluruh codebase yang pernah menulis ke
`UserExplorationProgress.total_points`: `ProgressService::awardPoints()`
(`app/Services/Exploration/ProgressService.php:184-205`). Dikonfirmasi lewat
grep `total_points` di seluruh `app/` — semua kemunculan lain HANYA membaca
nilainya (`PersonalizationContextBuilder.php:21`, `Admin\Dashboard.php:69`,
`SystemPromptBuilder.php:100` cuma komentar dokumentasi), tidak ada yang
menulis.

`awardPoints()` sendiri dipanggil dari **dua tempat**, keduanya di dalam
`ProgressService` yang sama (bukan dipanggil langsung dari Livewire manapun):

1. **`ProgressService::completeUnit()`** (baris 137-157) — memanggil
   `awardPoints($user, $unit->point_value)`. Poin diambil dari kolom
   `Unit.point_value` (`units.point_value`, integer per-unit, sudah diseed
   `CurriculumSeeder`). Dijaga idempoten (`if (! $alreadyCompleted)`) — kalau
   unit sudah pernah `completed`, tidak di-award ulang meski dipanggil lagi
   (kejadian nyata: retry kuis memanggil `completeUnit()` lagi tapi tidak
   dobel poin).
2. **`ProgressService::completeCheckpoint()`** (baris 159-182) — memanggil
   `awardPoints($user, 25)`. Poin checkpoint FLAT 25, hardcode langsung di
   method ini (bukan dari kolom konfigurasi).

**`completeUnit()` sendiri dipanggil dari 3 lokasi, SEMUANYA di
`app/Livewire/Eksplorasi/UnitEvaluation.php`:**
- `submitQuiz()` (baris 124) — untuk `quiz_multiple_choice`,
  `quiz_matching`, `quiz_ordering`.
- `submitFreeText()` (baris 168) — untuk tipe esai/free-text.
- `markAsRead()` (baris 175) — untuk unit tipe materi-baca-saja tanpa
  evaluasi (tandai selesai).

**`completeCheckpoint()` dipanggil dari 1 lokasi**:
`app/Livewire/Eksplorasi/CheckpointShow.php:53`.

**Temuan penting:** deskripsi tugas menyebut sumber poin sekarang sebagai
"Materi (submit evaluasi unit) dan Kuis" seolah dua jalur berbeda — pada
kenyataannya KETIGANYA (kuis, esai, materi-baca-saja) sudah memakai **satu
pintu masuk yang sama persis** (`completeUnit()`), bukan tiga jalur
paralel. Ini lebih terpusat dari yang diasumsikan.

## B. Tingkat sentralisasi sekarang

**Sudah lewat SATU jalur bersama, tidak tersebar.** `ProgressService` adalah
satu-satunya class yang pernah menyentuh `total_points`/`current_level`/
`level_name`. Tidak ada satu pun Livewire component, Controller, atau
service lain yang meng-update field-field ini langsung ke model
(`UserExplorationProgress::update(...)` atau `$progress->total_points +=
...` tidak ditemukan di luar file ini).

Struktur pemanggilan (dari Livewire ke titik akhir):
```
UnitEvaluation::submitQuiz()      \
UnitEvaluation::submitFreeText()   ├─> ProgressService::completeUnit()      ─┐
UnitEvaluation::markAsRead()      /                                          ├─> ProgressService::awardPoints()
CheckpointShow (checkpoint submit) ──> ProgressService::completeCheckpoint() ─┘
```

## C. Konsistensi update turunan

Saat `awardPoints()` jalan, SELALU tiga hal sekaligus, tidak pernah
sebagian (satu method, satu transaksi implisit lewat `$progress->save()`):
1. `total_points` bertambah.
2. `current_level` dan `level_name` dihitung ulang dari total baru (lewat
   `resolveLevel()`, baris 238-250).
3. Kalau level naik dibanding sebelumnya, notifikasi `level_up` terkirim.

Karena HANYA ada satu method yang pernah melakukan ini, tidak ada risiko
"titik yang update sebagian saja" — semua sumber poin (unit maupun
checkpoint) otomatis mendapat treatment turunan yang identik karena
keduanya memang memanggil method yang sama, bukan menduplikasi logic-nya
sendiri-sendiri.

**Perhitungan ulang level** ada di `ProgressService::resolveLevel()`
(baris 238-250, private), dipanggil inline setiap kali `awardPoints()`
jalan — bukan job/command terpisah yang jalan berkala. Threshold level
diambil dari `config('exploration.level_thresholds')`.

**Catatan terkait (bukan bagian sistem poin, tapi relevan untuk 2.2.6):**
`config/exploration.php` sendiri menandai `level_thresholds` masih **DRAFT,
menunggu konfirmasi user** (komentar eksplisit di file itu sejak task 2.3).
Threshold itu dihitung berdasar total poin achievable SEKARANG (995 poin
dari 67 unit + 9 checkpoint saja) — begitu Kuis Tier 2 dan Praktik
menambah sumber poin baru di 2.2.6, angka 995 ini pasti berubah, jadi
threshold ini kemungkinan besar perlu dihitung ulang lagi saat itu,
terlepas dari soal sentralisasi service.

## D. Audit trail — bisakah `total_points` direkonstruksi dari nol?

**Ya, bisa — tapi sumber yang benar bukan `EvaluationSubmission.points_awarded`
sendirian, melainkan `UserUnitProgress` (status completed) + `Unit.point_value`.**
Detail:

- **`UserUnitProgress`** (`user_id`, `unit_id`, `status`, `completed_at`,
  unique per `[user_id, unit_id]`) — untuk tiap baris `status = 'completed'`,
  poin yang pernah di-award adalah `Unit.point_value` milik unit itu. Karena
  `completeUnit()` idempoten dan constraint unique mencegah duplikat baris
  per user+unit, `SUM(Unit.point_value)` atas baris completed ini
  merekonstruksi PERSIS bagian "Materi/Kuis" dari `total_points` — tidak
  lebih tidak kurang.
- **`CheckpointCompletion`** (`points_awarded`, default 25, unique per
  `[user_id, checkpoint_id]`) — `SUM(points_awarded)` per user
  merekonstruksi bagian checkpoint dengan tepat, tanpa risiko dobel-hitung
  (constraint unique menjamin satu baris per checkpoint per user).
- **`EvaluationSubmission.points_awarded`** — **BUKAN sumber yang cukup
  sendirian**, dan ini temuan yang perlu digarisbawahi: kolom ini cuma
  terisi untuk unit yang benar-benar melalui `submitQuiz()`/`submitFreeText()`.
  Unit yang diselesaikan lewat `markAsRead()` (materi baca-saja, tanpa
  evaluasi) **TIDAK PERNAH membuat baris `EvaluationSubmission` sama
  sekali** — jadi merekonstruksi murni dari tabel ini akan **undercount**
  untuk unit-unit bertipe itu. `EvaluationSubmission` lebih tepat dianggap
  log/audit-trail "attempt mana yang dinilai benar/salah dan kapan", bukan
  sumber kebenaran untuk total poin.

**Kesimpulan D:** rekonstruksi `total_points` dari nol memang mungkin dan
akurat, TAPI query yang benar adalah
`SUM(Unit.point_value WHERE UserUnitProgress.status='completed') + SUM(CheckpointCompletion.points_awarded)`
per user — bukan menjumlah `EvaluationSubmission.points_awarded`. Catatan
arsitektur yang menyebut ketiga tabel itu "cukup untuk rekonstruksi" sudah
benar secara keseluruhan (union informasinya cukup), tapi kalau nanti ada
yang mengimplementasikan rekonstruksi ini secara literal dengan menjumlah
`EvaluationSubmission.points_awarded` sebagai jalan pintas, hasilnya akan
salah (kurang) untuk anggota yang punya unit tipe baca-saja.

## E. Penilaian untuk 2.2.6

**Pekerjaan penyatuan lebih kecil dari yang biasanya dikhawatirkan pada
kondisi seperti ini** — karena sistem SUDAH tersentralisasi di satu method
(`awardPoints()`), bukan tersebar di banyak tempat yang perlu diaudit satu-
per-satu. Penilaian konkret:

- **Kalau nanti dibuat `PointService` terpusat**, cara paling murah adalah
  me-rename/memindahkan `ProgressService::awardPoints()` (plus
  `resolveLevel()`) ke class baru itu apa adanya — logic-nya sudah benar
  dan sudah teruji (idempotency, level-up notif, dsb), tidak perlu ditulis
  ulang dari nol.
- **Titik yang perlu dialihkan ke pintu terpusat itu** cuma **2 titik**:
  `ProgressService::completeUnit()` dan `ProgressService::completeCheckpoint()`
  — keduanya tinggal memanggil `PointService::award(...)` sebagai ganti
  `$this->awardPoints(...)`. Tidak ada titik lain yang perlu disentuh
  karena memang tidak ada titik lain yang menulis poin.
- **Kuis Tier 2** (skor terbaik, retry tanpa batas — 2.1.2 poin 9): perlu
  logic BARU untuk "ambil skor terbaik dari seluruh attempt" (belum ada
  sama sekali di kode saat ini — `submitQuiz()` sekarang cuma peduli
  `isFirstAttempt` untuk poin, tidak membandingkan skor antar attempt sama
  sekali), tapi setelah skor terbaik itu dihitung, tetap tinggal memanggil
  pintu terpusat yang sama untuk benar-benar menambah poinnya.
- **Praktik (Tier 3)**: submission/review/diminishing-return adalah domain
  baru sepenuhnya (belum ada modelnya), tapi begitu poin sudah dihitung
  (penuh untuk submission pertama, mengecil untuk berikutnya), tetap
  tinggal satu panggilan ke pintu terpusat yang sama.
- **Risiko utama BUKAN di sisi "menyatukan yang tersebar"** (karena memang
  belum tersebar), **melainkan di merancang aturan skor-terbaik Kuis Tier 2
  dan diminishing-return Praktik dengan benar** — pekerjaan desain business
  logic baru, bukan pekerjaan refactor/pembersihan poin yang sudah ada.
- Satu hal administratif yang perlu diingat di 2.2.6 (bukan soal
  sentralisasi poin itu sendiri): `config/exploration.php`'s
  `level_thresholds` akan perlu dihitung ulang begitu total poin
  achievable berubah (lihat bagian C), dan statusnya sendiri masih DRAFT
  belum dikonfirmasi user bahkan untuk angka yang sekarang.
