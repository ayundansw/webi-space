# Recon Menyeluruh — Fase 7: Manajemen Proyek (Sebelum Rancang Implementasi)

Laporan ini murni observasi kode/skema NYATA saat ini (dicek langsung lewat
Read/Grep file + query DB). **Tidak ada kode yang diubah.**

**Catatan nama dokumen:** prompt task ini merujuk
`Perancangan_Struktur_Sistem_Eksekusi_WEBI-SPACE.md` §3.5-3.8 — file dengan
nama persis itu **tidak ditemukan** di `docs/` maupun `docs/v_2.0/` (termasuk
`docs/v_2.0/archive/`). Dokumen yang paling relevan dan satu-satunya yang
membahas Fase 7 secara spesifik adalah `docs/v_2.0/Rancangan_Modul_Manajemen_Proyek_v2.md`
(status "Final, siap masuk 2.1.2") — dipakai sebagai rujukan utama laporan
ini. Dokumen itu tidak memakai penomoran §3.5-3.8 (strukturnya §1-§6
berbasis topik, bukan skema tabel per-nomor) — kalau ada dokumen lain yang
dimaksud, mohon ditunjukkan sebelum implementasi dimulai, supaya tidak ada
keputusan rancangan yang terlewat.

---

## RINGKASAN EKSEKUTIF (baca ini dulu)

**Temuan paling konsekuensial: Detail Task SEKARANG halaman penuh
(`/eksekusi/tasks/{task}`), BUKAN slide-over.** Dokumen rancangan eksplisit
minta slide-over — ini pekerjaan KONVERSI arsitektur (bukan cuma re-skin),
dan harus jadi bagian Batch 1, bukan disisipkan belakangan.

**Halaman Utama Proyek belum punya konsep tab sama sekali.** `Projects\Show`
(metadata + Milestone + Anggota) dan `Projects\Board` (Kanban) adalah DUA
ROUTE/KOMPONEN TERPISAH yang saling link lewat tombol biasa, bukan satu
komponen dengan tab. Rombak jadi "satu halaman, tab Alpine" adalah
perubahan struktural, bukan penambahan tab baru ke struktur yang sudah ada.

**Gap skema paling besar (3, semua dikonfirmasi BELUM ADA):**
1. `tasks.parent_task_id` (subtask) — tidak ada sama sekali.
2. `task_dependencies` (Gantt dependency) — tidak ada sama sekali.
3. `calendar_events` — tidak ada sama sekali, dan **`forum_threads.project_id`
   juga tidak ada** (constraint yang dibutuhkan Forum Proyek/Forum General).

**Risiko tersembunyi paling penting untuk Batch Subtask: `parent_task_id`
akan MENDIAMKAN merusak semua query Task yang sudah ada.** Minimal 12 titik
query `Task::where(...)`/`->tasks()` di 5 file (`AlertService`,
`ProjectService`, `Eksekusi\Dashboard`, `Projects\Board`, `Admin\Dashboard`)
TIDAK PERNAH memfilter berdasar tipe task — begitu subtask ditambahkan lewat
kolom yang sama, SEMUA angka summary (dashboard admin, dashboard member,
alert overdue/stalled, Kanban board) akan diam-diam ikut menghitung subtask
kecuali diaudit satu-satu dan diberi `whereNull('parent_task_id')` secara
eksplisit. Ini bukan risiko teoretis — pola akses datanya memang begitu luas.

**RBAC "member proyek" sudah konsisten tapi DIDUPLIKASI 3x, belum
diekstrak.** Pola yang sama (`$user->role !== 'admin' && ! $project->members()->where('user_id', $user->id)->exists()`)
di-copy-paste identik di `Projects\Show::mount()`, `Projects\Board::mount()`,
dan `Tasks\Show::mount()` (via `$task->project`). Kalau tab-tab baru (Gantt,
Kalender, Forum Proyek, Anggota) dibangun sebagai komponen terpisah dengan
pola yang sama, ini akan terduplikasi lagi 4-5x lebih banyak — argumen kuat
untuk konsolidasi jadi SATU komponen ber-tab (bukan banyak route terpisah)
di Batch 1, supaya check ini cuma hidup di satu `mount()`.

**Urutan batch yang disarankan** (detail alasan tiap area di bawah,
rekomendasi pemecahan lebih lanjut di bagian akhir laporan):
1. **Konversi struktural dulu** (tab + slide-over) — murni re-arsitektur UI
   dari halaman yang SUDAH ada, nol tabel baru, risiko regresi Kanban
   drag-drop paling perlu diawasi ketat di sini.
2. **Subtask** (`parent_task_id`) — audit+scoping 12 query Task yang sudah
   ada HARUS selesai sebelum atau bersamaan dengan penambahan kolomnya,
   bukan pekerjaan susulan.
3. **Gantt + Dependency** (`task_dependencies`) — murni aditif, tidak
   menyentuh data yang sudah ada, bisa independen dari Kalender.
4. **Kalender + Roadmap** (`calendar_events`) — Roadmap murni baca ulang
   Milestone yang sudah ada (tidak butuh tabel baru), Kalender butuh tabel
   baru tapi juga murni aditif.
5. **Forum Proyek + Forum General** — butuh migrasi skema ke tabel yang
   SUDAH ADA (`forum_threads.project_id`), beda kelas risiko dari Kalender/
   Gantt yang keduanya migrasi CREATE TABLE baru murni; naikkan kehati-hatian
   di sini.

---

## 1. Halaman Utama Proyek — Struktur Sekarang

**Bukan satu halaman panjang, TAPI juga bukan tab — dua route/komponen
Livewire terpisah:**

- `App\Livewire\Eksekusi\Projects\Show` (`GET /eksekusi/projects/{project}`,
  route name `eksekusi.projects.show`) — metadata proyek (judul, status,
  deskripsi, tombol ubah status untuk admin), Milestone (list + form tambah,
  admin-only), Anggota Tim (list + form tambah/hapus, admin-only). Satu
  komponen, semua di satu Blade, TIDAK ada Alpine tab state.
- `App\Livewire\Eksekusi\Projects\Board` (`GET /eksekusi/projects/{project}/board`,
  route name `eksekusi.projects.board`) — Kanban, komponen SEPENUHNYA
  terpisah dari `Show`, dihubungkan cuma lewat tombol `<a href>` biasa
  ("Kanban Board" di `show.blade.php:17`, kembali via link "Kembali ke
  Proyek" — dicek: **tidak ada link balik eksplisit di board.blade.php**,
  cuma via breadcrumb/back browser).

**Kanban (`Board.php`) dan mekanisme drag-drop-nya (re-skin Fase 3 Batch 6)
— TIDAK DISENTUH dalam recon ini, cuma dibaca.** Board murni view layer atas
`Task.status` (dikonfirmasi via docblock `Board.php:14`: "Kanban board is a
view layer only ... no separate board entity"), 4 kolom tetap
(`todo`/`in_progress`/`in_review`/`done`), drag native HTML5 + tombol manual
memanggil `TaskService::changeStatus()` yang sama. **Kalau Kanban jadi tab
di dalam komponen ber-tab baru, mekanisme drag-and-drop-nya (atribut
`draggable`, 4 handler `x-on:drag*`, key `dataTransfer`) harus dipindah
APA ADANYA ke partial/include, persis kehati-hatian yang sudah dipraktikkan
Fase 3 Batch 6** — bukan ditulis ulang.

**Implikasi untuk Batch 1:** membuat tab bukan "nambah tab ke struktur yang
ada" — ini RESTRUKTURISASI dari 2 route terpisah jadi 1 komponen (atau 1
komponen induk + child Livewire per tab, pola nested Livewire yang sudah
established di app ini — lihat `<livewire:eksekusi.avatar-picker />` di
Profil). Routing juga perlu diputuskan: tab sebagai query string/Alpine
state (tanpa reload, sesuai "pindah tab tanpa reload halaman penuh" di
dokumen) vs sub-route Livewire per tab (reload penuh tapi state URL
bookmarkable) — dokumen minta yang pertama secara eksplisit.

---

## 2. Detail Task — Slide-over atau Halaman Penuh? (DICEK LANGSUNG)

**HALAMAN PENUH, bukan slide-over.** Konfirmasi konkret:

- Route terpisah: `GET /eksekusi/tasks/{task}` (`eksekusi.tasks.show`,
  `routes/web.php:126-128`), prefix `eksekusi/tasks` — BUKAN nested di
  bawah `eksekusi/projects/{project}/...`.
- `App\Livewire\Eksekusi\Tasks\Show` adalah full-page Livewire component
  (`#[Layout('components.layouts.app')]`, `#[Title('Detail Task')]`) —
  pola yang identik dengan halaman penuh lain (Modules/Units/dst), BUKAN
  pola slide-over yang sudah established di tempat lain (`x-show`/`x-cloak`/
  `x-transition` + backdrop, seperti modal detail Project Ideas atau panel
  WEBI kontekstual di halaman Materi).
- Navigasi ke sana: dari `board.blade.php`, tiap card task adalah
  `<a href="{{ url('/eksekusi/tasks/'.$task->id) }}">` — klik card =
  pindah halaman penuh (dikonfirmasi, bukan asumsi).
- Isi halaman ini SUDAH LENGKAP secara fungsional: ubah status, ubah
  deadline/prioritas (admin), reassign (admin), hapus (admin), komentar,
  attachment (file/link/text, 3 mode), progress update. Semuanya perlu
  dipindah APA ADANYA ke dalam panel slide-over, cuma wadahnya yang
  berubah.

**Implikasi untuk Batch 1:** ini pekerjaan konversi arsitektur UI (full-page
→ slide-over), bukan fitur baru — HARUS direncanakan eksplisit sebagai
bagian Batch 1 (sesuai dugaan dokumen sendiri: "Kalau masih halaman penuh:
ini pekerjaan konversi yang perlu direncanakan di Batch 1" — dan memang
benar, kondisinya masih halaman penuh). Karena Kanban ada di dalam tab yang
sama dengan detail task nantinya, pola yang masuk akal: slide-over sebagai
nested Livewire component yang di-`x-show` dari state Alpine milik komponen
tab Kanban, mirip pola panel WEBI kontekstual (`UnitShow`) yang sudah ada.

---

## 3. Skema Task Sekarang

### 3.1 `Task` (`app/Models/Task.php`, migrasi `2026_07_02_...create_tasks_table.php`)

```php
$table->uuid('id')->primary();
$table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
$table->foreignUuid('milestone_id')->constrained('milestones')->cascadeOnDelete();  // NOT NULL, wajib
$table->string('title');
$table->text('description')->nullable();
$table->enum('status', ['todo', 'in_progress', 'in_review', 'done']);
$table->enum('priority', ['low', 'medium', 'high']);
$table->date('deadline');
$table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
$table->timestamps();
```

**`parent_task_id` DIKONFIRMASI TIDAK ADA** — persis dugaan prompt ini.
Perlu migrasi aditif: `foreignUuid('parent_task_id')->nullable()->constrained('tasks')->cascadeOnDelete()`
(self-referencing). Catatan tambahan: `milestone_id` NOT NULL di skema
sekarang — kalau subtask idealnya tidak wajib punya milestone sendiri
(mengikuti milestone task induk), ini keputusan skema yang perlu
dikonfirmasi eksplisit ke Aye sebelum implementasi (nullable-kan
`milestone_id`, atau subtask tetap wajib punya `milestone_id` yang sama
dengan induknya — dua pendekatan beda dampak validasi).

### 3.2 `TaskAssignment` — SESUAI dokumen, tidak ada drift

```php
$table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
$table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
$table->foreignUuid('assigned_by')->constrained('users')->restrictOnDelete();
$table->timestamp('assigned_at')->nullable();
$table->unique(['task_id', 'user_id']);  // satu user cuma bisa 1x assignment per task
```
Tidak ada kolom peran/role (konsisten dengan `RECON_project_member_peran.md`
yang sudah lama mencatat ini sebagai keputusan, bukan gap).

### 3.3 `ProgressUpdate` — SESUAI dokumen ("dipertahankan, tidak berubah")

```php
$table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
$table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
$table->text('content');
$table->string('attachment_url')->nullable();  // URL string bebas, BUKAN file upload — beda dari Attachment
$table->timestamp('created_at')->nullable();   // const UPDATED_AT = null -- tidak bisa diedit setelah dikirim
```
Terpisah total dari `Comment` (task_id + user_id + content saja, tanpa
attachment) — dua sistem berbeda tujuan (laporan formal vs diskusi santai),
tidak ada overlap/tabrakan yang perlu diwaspadai Fase 7.

### 3.4 `Milestone` — SESUAI dokumen ("tetap seperti v1.0")

```php
$table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
$table->string('title');
$table->text('description')->nullable();
$table->date('target_date');
$table->integer('sort_order');
```
Tidak ada kolom `start_date` — kalau Roadmap butuh rentang waktu (bukan
cuma titik `target_date`), ini gap kecil yang perlu diputuskan (Roadmap
bisa saja cuma render titik-titik target_date berurutan tanpa rentang,
tergantung desain visual yang diinginkan).

### 3.5 Attachment — dikonfirmasi masih akurat, tidak ada drift dari 2.9

`Attachment` (`file_type` enum file/link/text, disk privat `attachments`,
`AttachmentDownloadController` dengan RBAC identik `Tasks\Show::mount()`)
kondisinya sama persis seperti dipetakan di task 2.9 — tidak ada perubahan
yang perlu dicatat ulang di sini.

### 3.6 `task_dependencies` — DIKONFIRMASI TIDAK ADA sama sekali

Tidak ada migrasi, model, atau referensi apa pun di kode. Perlu dibangun
dari nol persis sesuai dokumen: pivot `task_id`/`depends_on_task_id`,
validasi anti-circular-dependency (perlu ditulis manual, tidak ada mekanisme
generik untuk ini di app sekarang).

---

## 4. Milestone & Progress Derived

**Dikonfirmasi: progres Milestone dihitung on-the-fly, tidak disimpan.**

```php
// app/Models/Milestone.php:37-48
public function progressPercentage(): int
{
    $total = $this->tasks()->count();
    if ($total === 0) return 0;
    $done = $this->tasks()->where('status', 'done')->count();
    return (int) round($done / $total * 100);
}
```
Dipakai di: `resources/views/livewire/eksekusi/projects/show.blade.php:42/49`
(list Milestone), `Admin\Dashboard::projectSummary()` (lewat
`$project->milestones()` lalu blade admin dashboard memanggil
`->progressPercentage()` per milestone). **PERINGATAN yang sama seperti di
Ringkasan Eksekutif**: `$this->tasks()->count()` di sini TIDAK memfilter
`parent_task_id` — begitu subtask ada, `progressPercentage()` akan ikut
menghitung subtask kecuali diberi `whereNull('parent_task_id')` eksplisit.
`Project::progressPercentage()` (`app/Models/Project.php:61-72`) punya pola
identik dan risiko yang sama.

---

## 5. Forum — Kondisi Sekarang

### 5.1 Skema `forum_threads` — `module_id`/`unit_id` nullable DIKONFIRMASI, `project_id` TIDAK ADA

```php
$table->foreignUuid('module_id')->nullable()->constrained('modules')->nullOnDelete();
$table->foreignUuid('unit_id')->nullable()->constrained('units')->nullOnDelete();
$table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
$table->string('title');
$table->text('content');
$table->enum('target', ['peer', 'pic']);
$table->timestamps();
```
**`project_id` BELUM ADA — perlu migrasi aditif** persis seperti diduga
dokumen ("cek apakah kolom itu SUDAH ADA atau perlu ditambah" → perlu
ditambah). Kalau ditambah, jadi `nullable` (Forum General = null, Forum
Proyek = terisi) — pola yang sama persis dengan `module_id`/`unit_id`
sekarang untuk Forum Eksplorasi.

**Catatan tambahan yang perlu diputuskan**: kolom `target` (enum
`peer`/`pic`) adalah konsep khusus Forum Eksplorasi ("target diskusi ke
peer atau PIC") — dokumen Fase 7 tidak menyebut konsep serupa untuk Forum
Eksekusi. Perlu diputuskan: Forum Eksekusi tetap mengisi `target` dengan
nilai default/dummy (kalau kolom tetap NOT NULL), atau `target` perlu
di-nullable-kan supaya Forum Eksekusi bisa mengosongkannya sebagai konsep
yang tidak relevan untuknya. Ini keputusan skema, bukan keputusan teknis
murni — flag eksplisit ke Aye.

### 5.2 RBAC Forum Eksplorasi — pola yang bisa dicontek persis

```php
// routes/web.php:98 (grup, bukan per-route individual)
Route::middleware(['auth', 'role:exploration_member,admin'])
    ->prefix('eksplorasi/forum')->name('eksplorasi.forum.')
    ->group(function () { ... });
```
Pola timbal-balik yang diminta dokumen ("`exploration_member` dikecualikan
sepenuhnya ... sama seperti forum Eksplorasi yang dikecualikan dari akses
`execution_member`") tinggal dicontek persis dengan
`role:execution_member,admin` untuk grup route Forum Eksekusi baru — tidak
perlu pola RBAC baru, cuma mirror nama role di middleware.

---

## 6. Kalender — Fondasi Sekarang

**Tidak ada fondasi sama sekali** — dikonfirmasi via pencarian migrasi
(`grep -i calendar` di `database/migrations`): nol hasil. `calendar_events`
benar-benar dari nol.

**Slot placeholder yang SUDAH ada** (keduanya *disabled*, bukan sekadar
belum terlihat):
- `config/navigation.php:47` — `['label' => 'Kalender Personal', 'route' => null, 'enabled' => false]`
  (nav slot execution_member, dipakai `NavPopupTest` sebagai contoh
  "slot disabled belum aktif").
- `resources/views/livewire/eksekusi/dashboard.blade.php:154-163` — card
  "Ringkasan Kalender" di Dashboard Eksekusi, dashed border + badge "Segera
  Hadir" (dibangun Fase 3, placeholder murni, belum terhubung ke apa pun).
- `config/navigation.php:48` — `Forum General` juga sudah disabled slot
  yang sama (relevan untuk Area 5 di atas juga).

Kedua slot ini tinggal di-`enabled: true` + di-route-kan begitu Kalender/
Forum General dibangun — tidak perlu ubah struktur nav, cuma flip flag.

---

## 7. Dashboard Admin — Ketergantungan ke Struktur Proyek

`App\Livewire\Admin\Dashboard.php` membaca struktur proyek di 3 method:

- **`projectSummary()`** (`:108-124`): `Task::where('project_id', ...)`
  (untuk `active_members`, filter status `in_progress`/`in_review`),
  `$project->progressPercentage()`, `$project->milestones()`. **Tidak
  filter `parent_task_id`** (belum ada kolomnya, tapi begitu ada, method
  ini otomatis kena — lihat Ringkasan Eksekutif).
- **`memberSummaries()`** (`:168-185`): `Task::whereHas('assignments', ...)`
  per member, hitung breakdown status + `overdue_count`. Sama, tidak ada
  filter tipe task.
- **`alertPanel()`** (`:126-155`): delegasi penuh ke `AlertService`
  (`overdueTasks()`, `milestonesAtRisk()`, `idleProjects()`,
  `stalledTasks()`, `inactiveMembers()`, `dueSoonTasks()`) — SEMUA method
  ini query Task/Milestone langsung, sama-sama tanpa filter tipe task.

**Kesimpulan Area 7**: Dashboard Admin TIDAK butuh perubahan struktural
untuk Fase 7 (tidak membaca tab/slide-over apa pun, cuma link `<a>` biasa
ke `/eksekusi/projects/{id}`) — risikonya murni di NILAI yang ditampilkan
kalau subtask/dependency ditambahkan tanpa audit query, bukan di halaman
dashboard itu sendiri rusak/error.

---

## 8. RBAC & Middleware Proyek Sekarang

**Dua lapis, konsisten di semua tempat yang dicek:**

1. **Middleware route-level (kasar)**: `role:execution_member,admin` di
   level grup route (`routes/web.php:118` untuk `eksekusi/projects`,
   `:126` untuk `eksekusi/tasks`) — cuma memastikan role benar, TIDAK tahu
   proyek mana yang diakses.
2. **Cek keanggotaan di `mount()` tiap komponen (halus)**: pola identik
   diulang di 3 tempat —
   ```php
   $user = Auth::user();
   if ($user->role !== 'admin' && ! $project->members()->where('user_id', $user->id)->exists()) {
       abort(403);
   }
   ```
   Muncul di `Projects\Show::mount()`, `Projects\Board::mount()`, dan
   `Tasks\Show::mount()` (lewat `$task->project`, bentuk sama persis).
   **Ini DIDUPLIKASI, bukan diekstrak ke trait/service/Form Request.**

**Implikasi untuk Fase 7**: kalau tab-tab baru (Roadmap, Gantt, Kalender,
Forum Proyek, Anggota) tetap dibangun sebagai komponen/route terpisah
mengikuti pola sekarang, snippet di atas akan terduplikasi 4-5x lagi.
Konsolidasi ke satu komponen ber-tab (Batch 1) secara otomatis menyelesaikan
ini — check-nya cuma perlu hidup sekali di `mount()` komponen induk, semua
tab anak (kalau nested Livewire) otomatis terlindungi karena tidak pernah
di-render kalau induknya sudah `abort()`. Kalau desain akhirnya TETAP pakai
route terpisah per tab (bukan satu komponen), rekomendasi minimal: ekstrak
snippet ini ke satu method reusable (trait atau `Gate`/Policy) supaya
perubahan aturan akses di masa depan cuma perlu diubah di satu tempat.

---

## Rekomendasi Pemecahan Batch (lebih detail dari 4 batch besar dokumen)

Dokumen rancangan sendiri sudah minta dipecah jadi "minimal" 4 batch
(Fondasi+Kanban, Gantt+Dependency, Kalender+Roadmap, Forum+Subtask). Berdasar
temuan recon ini — terutama volume perubahan struktural di Area 1-2 dan
risiko cross-cutting subtask di Area 3-4-7 — usulan pemecahan lebih halus:

**Batch 1a — Konversi struktural: Tab Kanban (TANPA tab lain dulu)**
Rombak `Projects\Show` + `Projects\Board` jadi satu komponen ber-tab,
tapi HANYA tab Kanban yang aktif (tab lain placeholder disabled, pola
sama seperti slot nav yang sudah ada). Fokus sempit: pindahkan Kanban
tanpa menyentuh drag-drop, buktikan pola tab+RBAC-terpusat jalan sebelum
tab lain ditambah di atasnya. Ini juga titik paling aman untuk deteksi dini
kalau konversi tab ternyata merusak sesuatu — blast radius kecil.

**Batch 1b — Konversi struktural: Slide-over Task (TANPA subtask dulu)**
Pindahkan `Tasks\Show` jadi slide-over yang dibuka dari tab Kanban Batch
1a, isi fungsional (status/deadline/prioritas/reassign/komentar/attachment/
progress update) dipindah apa adanya. Dipisah dari 1a supaya rollback
lebih presisi kalau salah satu dari dua konversi ini bermasalah.

**Batch 2a — Subtask (skema + audit query existing WAJIB duluan)**
Migrasi `parent_task_id` + AUDIT & PERBAIKI ke-12 titik query Task yang
sudah ada (Area Ringkasan Eksekutif) SEBELUM atau BERSAMAAN dengan
menambah kemampuan bikin subtask — bukan pekerjaan susulan "nanti
dibereskan". Baru setelah itu bangun UI mini-list subtask di dalam
slide-over (dari Batch 1b).

**Batch 2b — Gantt + Dependency**
`task_dependencies` (murni aditif) + validasi anti-circular + render
Gantt. Independen dari Kalender, bisa habis duluan atau ditukar urutan
dengan 3a tanpa saling blocking.

**Batch 3a — Kalender**
`calendar_events` (aditif) + tab Kalender per-proyek + halaman Kalender
Personal terpisah (flip 2 nav slot yang sudah disabled). Query gabungan
Kegiatan (dari Task/Milestone existing — WASPADA filter `parent_task_id`
lagi di sini) + Acara (tabel baru).

**Batch 3b — Roadmap**
Murni baca Milestone yang sudah ada (tidak ada tabel baru) — batch paling
ringan, cocok jadi "batch istirahat" di antara batch berat lainnya.

**Batch 4 — Forum Proyek + Forum General**
Migrasi `forum_threads.project_id` (skema ke tabel EXISTING, bukan tabel
baru — kelas risiko beda, wajib `AskUserQuestion` konfirmasi backup
eksplisit sebelum migrate) + keputusan kolom `target` (Area 5.1) + RBAC
mirror Forum Eksplorasi (Area 5.2, tinggal contek) + flip nav slot "Forum
General" yang sudah disabled.

**Urutan disarankan**: 1a → 1b → 2a → (2b dan 3a/3b bisa ditukar/paralel
kalau ingin) → 4 terakhir (karena satu-satunya yang menyentuh skema tabel
lama, paling aman dikerjakan setelah pola migrasi aditif lain sudah
terbukti jalan mulus di batch-batch sebelumnya).
