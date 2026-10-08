# Recon — Fase 5: Modul Praktik (Sebelum Rancang Skema)

Laporan ini murni observasi kode/data NYATA saat ini (dicek langsung lewat
Read/Grep file + query DB dev lokal). **Tidak ada kode/skema yang diubah.**

---

## RINGKASAN EKSEKUTIF (baca ini dulu)

**Premis di prompt task ini soal `content_blocks` sudah TIDAK BERLAKU LAGI —
generalisasi ke polymorphic yang diminta SUDAH DIKERJAKAN.** Catatan revisi
lama ("field ini perlu digeneralisasi jadi polymorphic... perlu dibawa balik
ke dokumen Rancangan Arsitektur Konten Dinamis") sudah dieksekusi PENUH di
suatu batch sebelumnya (kemungkinan besar 2.2.4a, migrasi bertanggal
2026-07-09) — table, model, dokumen rancangan, DAN dokumen spec turunan
semuanya sudah konsisten memakai `blockable_type`/`blockable_id`. **Migrasi
skema untuk ini TIDAK PERLU dikerjakan lagi — sudah selesai.**

Yang **BELUM** dikerjakan (dan jadi pekerjaan nyata Fase 5): `content_blocks`
baru berupa fondasi kosong (0 baris data, cuma diuji dengan data dummy),
`CurriculumContextBuilder` (WEBI) masih 100% baca dari kolom lama
`units.content`, dan migrasi 67 unit ke blok belum dimulai sama sekali.

**Urutan kerja disarankan** (detail alasan di tiap bagian di bawah):
1. **PointService dulu** — murni refactor/pemindahan kode yang sudah ada &
   teruji (`ProgressService::awardPoints()`), risiko rendah, tidak
   tergantung apa pun dari Praktik.
2. **Challenge + Submission (skema baru)** — bisa paralel/setelah #1, tidak
   overlap dengan `content_blocks` sama sekali di level tabel.
3. **Track map Challenge pakai `content_blocks` yang SUDAH ADA** (relasi
   `blockable` ke model `Challenge`/`ChallengeStep` baru) — TIDAK perlu
   migrasi ulang `content_blocks`, tinggal pakai. Ini terpisah total dari
   pekerjaan "migrasi 67 unit Materi ke blok" yang statusnya independen dan
   belum jadi prasyarat Praktik.
4. **RBAC `assigned_reviewer_id`** — contek pola `AttachmentDownloadController`
   (2.9) persis, sudah ada & masih hidup untuk dicontek.
5. **Notifikasi reviewer** — butuh migrasi ADDITIF ke `notifications`
   (`context_type` dan `type` enum keduanya perlu nilai baru, lihat Area 4).

---

## Area 1 — Kondisi `content_blocks` Sekarang

### 1.1 Skema: SUDAH polymorphic, bukan `unit_id` langsung

`database/migrations/2026_07_09_000001_create_content_blocks_table.php`:
```php
Schema::create('content_blocks', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuidMorphs('blockable');   // -> blockable_type + blockable_id
    $table->enum('type', ['heading', 'text', 'image', 'callout', 'code', 'video', 'list', 'table', 'custom_html']);
    $table->json('content');
    $table->integer('order');
    $table->timestamps();

    $table->index(['blockable_type', 'blockable_id', 'order'], 'content_blocks_blockable_order_index');
});
```
`app/Models/ContentBlock.php` — `MorphTo` penuh (`blockable()`), fillable
`['blockable_type', 'blockable_id', 'type', 'content', 'order']`. Docblock
model eksplisit menyebut alasan polymorphic: "so the same table/renderer
serves Unit materi now and ChallengeStep (Praktik track map, 2.2.6) later."

`app/Models/Unit.php` sudah punya relasi baliknya:
```php
public function contentBlocks(): MorphMany
{
    return $this->morphMany(ContentBlock::class, 'blockable')->orderBy('order');
}
```

Morph map SUDAH terdaftar di `app/Providers/AppServiceProvider.php`:
```php
Relation::morphMap([
    ...
    'unit' => Unit::class,
]);
```
Dites eksplisit (`ContentBlockTest::test_content_blocks_are_polymorphic_and_use_the_registered_morph_map`)
— `blockable_type` tersimpan sebagai `'unit'` (alias pendek dari morph map),
BUKAN `App\Models\Unit` (fully-qualified class name).

**Catatan presisi kecil:** `docs/v_2.0/Rancangan_Arsitektur_Konten_Dinamis_v2.md`
baris 47 menulis contoh `blockable_type = 'Unit'` (huruf besar, tanpa alias) —
implementasi sungguhan pakai `'unit'` (huruf kecil, lewat morph map).
Dokumen vs kode beda penulisan contoh, bukan bug, tapi kalau dibaca literal
saat merancang `Challenge`/`ChallengeStep` bisa menyesatkan penamaan alias
morph map barunya nanti (mis. harus `'challenge_step'`, bukan
`'ChallengeStep'`).

### 1.2 Jumlah baris data SEKARANG: **NOL**

```
php artisan tinker --execute="echo App\Models\ContentBlock::count();"
=> 0
```
Dikonfirmasi juga oleh `docs/v_2.0/content-blocks-spec.md` baris 9: **"belum
ada satu pun unit produksi yang memakai ini."** Satu-satunya data yang pernah
ada adalah dummy di `ContentBlockTest.php` (dalam transaksi test,
`RefreshDatabase`, tidak pernah masuk DB dev/production sungguhan) dan
kemungkinan sample manual dari batch fondasi — keduanya tidak tersisa di DB
sekarang.

**Implikasi risiko migrasi:** karena tabel ini genuinely KOSONG (bukan "sudah
terisi 67 unit dari Batch 4" seperti dugaan di prompt task), **tidak ada
migrasi data existing yang perlu dipikirkan sama sekali untuk kebutuhan
Praktik.** Skema sudah generik/final, tinggal dipakai. Risiko migrasi
`content_blocks` untuk Praktik = **NOL** (tidak ada yang perlu dimigrasi,
tabelnya sudah siap pakai untuk `blockable` jenis baru).

### 1.3 Renderer (`<x-content-blocks>` + 9 komponen tipe): sudah generik, siap pakai

`resources/views/components/content-blocks.blade.php` — `@props(['blocks'])`,
terima koleksi `ContentBlock` APA PUN pemiliknya (`blockable`), loop +
`@switch($block->type)` ke 9 partial per tipe
(`components/content-block/{heading,text,image,callout,code,video,list,table,custom-html}.blade.php`).
**Tidak ada satu baris pun kode yang hardcode asumsi `unit_id`** — komponen
ini murni terima `$blocks` (koleksi Eloquent), tidak pernah menyentuh
`blockable_type`/`blockable_id` secara langsung, cuma baca `$block->type`
dan `$block->content`. Siap dipakai apa adanya untuk render track map
Challenge, TANPA perubahan sama sekali — cukup panggil
`<x-content-blocks :blocks="$challengeStep->contentBlocks" />` begitu model
`ChallengeStep` (atau serupa) dibuat dengan relasi `morphMany` yang sama
polanya seperti `Unit::contentBlocks()`.

Sanitasi (XSS) sudah lengkap & teruji: `SafeMarkdown::toHtml()` (markdown
fields) + `HtmlSanitizer::sanitize()` (`custom_html`), 13 test lolos di
`ContentBlockTest.php` termasuk kasus XSS eksplisit (script tag, event
handler, `javascript:` scheme, iframe asing).

### 1.4 `CurriculumContextBuilder` (WEBI): **BELUM diupdate**, masih baca `units.content` langsung

Ini kontradiksi premis prompt task ("sesuai Batch 4 kemarin" — implikasinya
sudah update). Faktanya, `app/Services/Webi/CurriculumContextBuilder.php`:
```php
public function build(?Unit $currentUnit, string $userQuestion): string
{
    ...
    $blocks[] = "Unit yang sedang dikerjakan user (...):\n".$this->stripDirectives($currentUnit->content);
    ...
}

private function relatedUnits(?Unit $currentUnit, string $userQuestion)
{
    ...
    $q->orWhere('title', 'like', "%{$keyword}%")
        ->orWhere('content', 'like', "%{$keyword}%");   // <- kolom lama
    ...
}

private function stripDirectives(string $content): string
{
    return trim(preg_replace('/\[SAJIKAN:.*?\]/su', '', $content));
}
```
**Nol referensi** ke `contentBlocks()`/`ContentBlock` di file ini (dikonfirmasi
grep). Method `relatedUnits()` masih query `WHERE content LIKE` ke kolom
teks polos, dan `stripDirectives()` masih meregex `[SAJIKAN: ...]`.

**Dampak untuk Praktik:** karena WEBI tidak baca `content_blocks` sama
sekali sekarang, membangun track map Challenge lewat `content_blocks` **TIDAK
BERISIKO merusak WEBI** — WEBI tidak pernah melihat/query tabel ini untuk
entitas apa pun, jadi menambah `blockable_type` baru (`challenge_step` atau
serupa) di tabel yang sama tidak menyentuh jalur WEBI sedikit pun. Update
`CurriculumContextBuilder` untuk baca `content_blocks` (baik untuk Unit
maupun nanti Challenge) tetap PR terpisah yang belum dikerjakan, tapi itu
bukan blocker untuk mulai Praktik.

---

## Area 2 — Sistem Poin Sekarang

*(Ringkasan; laporan detail lengkap dengan kutipan baris sudah ada di
`docs/v_2.0/RECON_sistem_poin.md`, dibuat sebelumnya untuk keperluan yang
sama — dicek ulang sekilas, masih akurat/berlaku, tidak ada perubahan sejak
ditulis.)*

**Satu-satunya method yang pernah menulis `UserExplorationProgress.total_points`:**
`ProgressService::awardPoints()` (`app/Services/Exploration/ProgressService.php:185-206`).
Dipanggil dari 2 tempat, keduanya di class yang sama:
- `completeUnit()` → `awardPoints($user, $unit->point_value)` — idempoten
  (`if (! $alreadyCompleted)`), dipanggil dari 3 titik di
  `UnitEvaluation.php` (`submitQuiz`, `submitFreeText`, `markAsRead`).
- `completeCheckpoint()` → `awardPoints($user, 25)` — poin flat, hardcode.

**Update turunan SELALU sekaligus** dalam satu method: `total_points`
bertambah + `current_level`/`level_name` dihitung ulang (`resolveLevel()`)
+ notifikasi `level_up` kalau naik level. Kolom `total_points` disimpan
langsung (bukan dihitung ulang via `SUM()` tiap query) — dibaca apa adanya
oleh semua konsumen.

**`FoxAvatarService::tierFor()`** (`app/Services/Exploration/FoxAvatarService.php:18-21`):
```php
public function tierFor(User $user): int
{
    return $this->tierForPoints($this->progressService->ensureProgress($user)->total_points);
}
```
Baca `total_points` lewat `ProgressService::ensureProgress()` — **sama
persis** kolom yang ditulis `awardPoints()`. Selama `PointService` baru
(kalau dibangun) tetap menulis ke kolom `total_points` yang sama (baik
lewat `ProgressService` yang dipertahankan sebagai pemanggil, atau
dipindah langsung), Fox avatar otomatis ikut benar tanpa perlu disentuh.

**Leaderboard** (`ProgressService::memberLeaderboard()`) — baca
`$this->ensureProgress($member)->total_points` per anggota, kolom yang sama
juga. Sama seperti Fox avatar, aman selama kolom sumbernya tidak diganti.

**Kesimpulan Area 2 (selaras `RECON_sistem_poin.md`):** sistem SUDAH
tersentralisasi di 1 method, bukan tersebar. Kalau `PointService` dibangun,
cara termurah & paling aman adalah memindah `awardPoints()`+`resolveLevel()`
apa adanya ke class baru itu, lalu ubah 2 titik pemanggil
(`completeUnit()`, `completeCheckpoint()`) untuk panggil pintu baru itu.
Titik BARU yang perlu ditambahkan untuk Praktik: submission disetujui
admin → panggil pintu yang sama dengan `points_reward` (poin penuh) atau
nilai diminishing-return (submission ke-2 dst) — aturan besaran
pengurangannya sendiri belum ada di kode mana pun (dikonfirmasi grep,
sesuai dokumen rancangan §5 yang juga bilang "ditentukan saat implementasi").

---

## Area 3 — Struktur Existing yang Relevan

### 3.1 Pola `Module`/`Unit` (acuan untuk `Challenge`)

`Module` (`app/Models/Module.php`): `HasUuids`, fillable sederhana
(`order_number`, `title`, `description`, `level_number`), relasi `units()`
(hasMany), `checkpoint()` (hasOne), `learningResources()`, `forumThreads()`.

`Unit` (`app/Models/Unit.php`): `HasUuids`, fillable lebih kaya
(`module_id`, `order_number`, `title`, `content`, `estimated_minutes`,
`unit_type` enum, `point_value`, `evaluation_type` enum,
`prerequisite_unit_id` self-referencing nullable). Relasi: `module()`
(belongsTo), `prerequisiteUnit()`/`dependentUnits()` (self), `evaluations()`
(hasMany), `userProgress()`, `evaluationSubmissions()`, `forumThreads()`,
`messages()` (morphTo via `unit_context`), `contentBlocks()` (morphMany,
poin 1.3 di atas).

**Pola yang konsisten untuk `Challenge`:** `HasUuids`, enum `level`
(`low`/`mid`/`high`) dan `status` (`draft`/`published`) mengikuti gaya enum
`Unit.unit_type`/`Unit.evaluation_type` (bukan tabel lookup terpisah — semua
enum kurikulum di app ini pakai native MySQL enum di migration, bukan
tabel `statuses`/`levels` terpisah). `ChallengeStep` (track map) sebaiknya
punya `order_number`/`sort_order` mengikuti pola `Unit.order_number` /
`Milestone.sort_order` (Eksekusi) untuk urutan langkah.

### 3.2 Pola RBAC — dicontek dari 2.9 (Attachment Access Control)

**Masih hidup, siap dicontek**: `app/Http/Controllers/Eksekusi/AttachmentDownloadController.php`.
Pola intinya:
```php
abort_if(
    $user->role !== 'admin' && ! $project->members()->where('user_id', $user->id)->exists(),
    403,
);
```
Prinsip: cek kepemilikan/keterkaitan LANGSUNG di controller/component
(bukan Policy class terpisah — app ini tidak pakai Laravel Policy sama
sekali, dikonfirmasi tidak ada folder `app/Policies`), 403 (bukan 404) saat
ditolak, admin selalu bypass. Middleware role-level (`role:execution_member,admin`
dkk, alias terdaftar di `bootstrap/app.php:18` → `EnsureUserHasRole::class`)
cuma menyaring ROLE (siapa boleh masuk rute), keterkaitan spesifik-baris
(siapa boleh lihat BARIS ini) selalu dicek manual di dalam
controller/`mount()`, konsisten di semua tempat (`Tasks\Show::mount()`,
`AttachmentDownloadController`, dst).

**Untuk `assigned_reviewer_id` Submission:** pola yang PERSIS sama berlaku —
`abort_if($user->role !== 'admin' && $submission->assigned_reviewer_id !== $user->id, 403)`
di halaman/action review, TIDAK memberi akses umum ke semua submission
Praktik siapa pun untuk `execution_member` biasa (persis permintaan
eksplisit dokumen rancangan §5, "jangan sampai terulang celah aksesnya").

### 3.3 Sistem Notifikasi — dua Notifier paralel, pola reusable

Ada **DUA** class `Notifier` (bukan satu class dipakai lintas modul):
`App\Services\Exploration\Notifier` dan `App\Services\Execution\Notifier`
(tidak dibaca isi Execution\Notifier di recon ini, tapi namanya konsisten
paralel). Keduanya tulis ke tabel `notifications` yang SAMA, morph map yang
SAMA. Pola `Exploration\Notifier::send()`:
```php
public function send(User $recipient, string $type, string $title, string $message, Checkpoint|Unit|Module|ForumThread|null $context = null): Notification
```
Method tunggal, terima union type context (bukan generic `Model $context`),
resolve `context_type` via `match(true)`. Untuk Praktik, kemungkinan besar
perlu tambahan union type `Submission` (atau `Challenge`) ke salah satu
Notifier yang sudah ada (atau Notifier baru serupa untuk domain Praktik) —
pola pemanggilannya sendiri sudah reusable, tinggal ikuti bentuk yang sama.

**Bell/dropdown notifikasi** (`resources/views/livewire/notifications/bell.blade.php`)
generik total — baca `$notification->linkUrl()`/`title`/`message`/`is_read`
tanpa peduli `type`/`context_type` apa isinya. **Tidak perlu diubah sama
sekali** untuk notifikasi Praktik baru — begitu `type`/`context_type` baru
ditambahkan (lewat migrasi additif, lihat Area 4) dan `Notification::linkUrl()`
(model, tidak dibaca detail di recon ini tapi pola sudah dikonfirmasi
eksis dari kerja 2.6b) diberi entry match baru untuk context type Submission,
notifikasinya otomatis muncul benar di bell tanpa sentuh `bell.blade.php`.

### 3.4 Slot Dashboard — kondisi kode PERSIS sekarang

**Dashboard Eksekusi** (`resources/views/livewire/eksekusi/dashboard.blade.php`) —
DUA placeholder card sudah ada:
- Baris 2 kolom 3, "Praktik Menunggu Direview" (stat card kosong,
  `Segera Hadir`, baris 91-101).
- Baris 4 kolom 3, "Antrian Review Praktik" (card kosong,
  `Segera Hadir`, baris 201-210, teks "Submission Praktik yang ditugaskan
  admin ke kamu untuk direview akan tampil di sini, dengan link langsung").

Keduanya pakai pola `card-pixel-accent-*` + `border-dashed` + `opacity-70` +
badge "Segera Hadir" — style placeholder yang konsisten di seluruh app.
**Isi murni statis**, tidak ada query/data apa pun di baliknya sekarang —
begitu Fase 5 jalan, dua card ini perlu diganti isi aslinya (query
`Submission::where('assigned_reviewer_id', $user->id)->where('status', 'pending')`
kira-kira), bukan dibangun dari nol strukturnya.

**Dashboard Eksplorasi** (`resources/views/livewire/eksplorasi/dashboard.blade.php`) —
SATU placeholder, "Daftar Praktik" (baris ~192-201, dashed, `Segera Hadir`,
"Challenge praktik akan muncul di sini begitu modul Praktik dibuka"). Sama
pola visualnya.

### 3.5 `config/navigation.php` — slot dikonfirmasi, TAPI `execution_member` TIDAK PUNYA slot

- `admin`: `['label' => 'Antrian Review Praktik', 'route' => null, 'enabled' => false]` — **ADA**, siap diaktifkan.
- `exploration_member`: `['label' => 'Praktik', 'route' => null, 'enabled' => false]` — **ADA**, siap diaktifkan.
- `execution_member`: **TIDAK ADA slot apa pun untuk Praktik/Antrian Review** — array nav untuk role ini cuma berisi Dashboard, Project Ideas, Proyek, Kalender Personal (disabled), Forum General (disabled). **Ini gap nyata, bukan asumsi**: dokumen §4.5 (rujukan sebelumnya di percakapan ini) eksplisit bilang reviewer akses "sebagai bagian dari portal Eksekusi langsung... kartu di dashboard sebagai entry point tambahan" — dashboard SUDAH punya card-nya (Area 3.4), tapi kalau mau juga ada entry point lewat popup menu navigasi (konsisten pola nav item lain), **item baru perlu DITAMBAHKAN ke `config/navigation.php['execution_member']`**, bukan sekadar `enabled: true`-kan yang sudah ada — slot-nya belum pernah dibuat sama sekali untuk role ini.

### 3.6 RBAC role di route level (konfirmasi tambahan)

`bootstrap/app.php:18` mendaftarkan alias `role` → `EnsureUserHasRole::class`,
dipakai luas di `routes/web.php` (`role:exploration_member`,
`role:execution_member,admin`, dst — pola grup middleware per prefix rute
yang sudah konsisten dipakai di seluruh app, termasuk rute Forum, Eksekusi,
attachment download). Rute baru untuk Praktik (daftar challenge, submit,
antrian review) akan mengikuti pola grouping yang identik.

---

## Area 4 — Migrasi yang Dibutuhkan (Ringkasan)

**Tabel baru yang PASTI dibutuhkan** (nama final disarankan untuk hindari
tabrakan, lihat di bawah):

1. **`challenges`** — `title`, `description`, `level` (enum low/mid/high),
   `points_reward` (integer), `status` (enum draft/published), timestamps.
   Tidak ada tabrakan nama (dikonfirmasi grep migrations, tidak ada tabel
   `challenges` sekarang).
2. **Track map** — TIDAK BUTUH tabel data konten baru (pakai
   `content_blocks` yang sudah ada, `blockable_type` baru). **TAPI tetap
   butuh satu tabel kecil untuk representasi "langkah"** (`challenge_steps`
   kemungkinan besar — `challenge_id`, `title`/label langkah,
   `order_number`) sebagai `blockable` yang MEMILIKI banyak `content_blocks`
   — dokumen rancangan menyebut "beberapa langkah berurutan" tapi tidak
   detail apakah 1 langkah = 1 blockable dengan banyak blok, atau seluruh
   track map 1 challenge = 1 blockable dengan banyak blok euntuk mewakili
   semua langkah sekaligus. **Perlu keputusan desain eksplisit sebelum
   implementasi** (di luar scope baca-saja recon ini untuk diputuskan).
3. **Submission** — **JANGAN pakai nama tabel `submissions` polos**, sudah
   ada `evaluation_submissions` (tabel BEDA, untuk kuis/esai Materi) yang
   mirip secara konsep tapi domain berbeda total — nama mirip berisiko
   membingungkan saat baca kode/query nanti. Disarankan
   `challenge_submissions` atau `practice_submissions` (konsisten prefix
   dengan `challenges`). Field sesuai dokumen: `challenge_id`, `user_id`,
   `submission_type` (enum link/text/file), `content`, `status` (enum
   pending/disetujui/perlu_revisi — atau versi Inggris konsisten enum lain
   di app, cek konvensi bahasa enum existing sebelum putuskan), `feedback`,
   `attempt_number`, `points_awarded` (nullable), `assigned_reviewer_id`
   (nullable, FK → users, null-on-delete mengikuti pola FK opsional lain).

**Migrasi ADDITIF ke tabel yang SUDAH ADA (bukan tabel baru, tapi tetap
migrasi skema, perlu backup+konfirmasi sama seperti tabel baru):**

4. **`notifications.context_type` enum** — perlu tambahan nilai (mis.
   `'challenge_submission'`) untuk notifikasi terkait Praktik bisa
   di-morph dengan benar. Enum sekarang: `project, task, unit, checkpoint,
   module, forum_thread, none` — TIDAK ADA varian untuk domain Praktik.
5. **`notifications.type` enum** — perlu tambahan nilai minimal untuk:
   "submission ditugaskan ke reviewer", "submission direview/feedback
   masuk" (mungkin dipecah disetujui vs perlu_revisi, ikuti pola
   `task_status_to_review`/`task_revision_needed` yang sudah ada di Eksekusi
   untuk semangat serupa). Enum sekarang berisi 21 nilai, tidak ada satu
   pun terkait Praktik.
6. **Morph map baru** (`AppServiceProvider`) — alias pendek untuk
   `blockable_type` Challenge/ChallengeStep (poin 1.1 tadi, hindari pola
   penamaan literal `'ChallengeStep'` yang dokumen contohkan, ikuti
   konvensi `'unit'` yang sudah dipakai — kemungkinan `'challenge_step'`).

**Tidak ada tabrakan nama kolom/tabel lain yang ditemukan** dari 4 tabel
baru/perubahan di atas terhadap skema existing (dikonfirmasi grep nama
kandidat terhadap seluruh `database/migrations/`).

---

## Highlight Eksplisit (sesuai diminta)

**Apakah migrasi `content_blocks` ke polymorphic AMAN sekarang?**
Pertanyaannya sendiri sudah tidak relevan lagi — **migrasi itu sudah
selesai dikerjakan di masa lalu, bukan pekerjaan yang masih menunggu.**
Kalau pertanyaannya digeser jadi "amankah MENAMBAH `blockable_type` baru
(Challenge/ChallengeStep) ke tabel yang sudah polymorphic ini" — jawabannya
**YA, sepenuhnya aman**: tabel kosong (0 baris), tidak ada data existing
yang berisiko rusak/perlu backfill, skemanya sendiri sudah generik dari
awal (tidak perlu ALTER TABLE apa pun, cukup INSERT baris baru dengan
`blockable_type` baru begitu model Challenge/ChallengeStep sudah ada).

**Rekomendasi urutan kerja paling aman** (detail sudah di Ringkasan
Eksekutif di atas): **PointService dulu** (refactor kode yang sudah teruji,
risiko rendah, tidak bergantung Praktik) **→ baru Challenge/Submission**
(skema baru, independen dari `content_blocks`/PointService secara teknis,
tapi butuh PointService/pintu poin terpusat sudah ada supaya submission
disetujui punya satu tempat jelas untuk memanggil pemberian poin, alih-alih
menulis langsung ke `ProgressService::awardPoints()` yang sifatnya
"sementara" kalau memang niatnya segera dipindah).
