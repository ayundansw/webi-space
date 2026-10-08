# Recon Menyeluruh — Fase 8: Mode Ganda (Dual-Mode Account)

Laporan ini murni observasi kode/skema NYATA saat ini (dicek langsung lewat
Read/Grep/Glob). **Tidak ada kode yang diubah.** Fase 8 adalah fitur paling
berisiko soal keamanan akses di seluruh aplikasi — setiap klaim di bawah
dikonfirmasi baca-langsung dari file, bukan diasumsikan dari nama fitur.

Rujukan desain utama: `docs/v_2.0/RANCANGAN_FINAL_WEBI-SPACE_v2.md` §2.2
("Sistem Mode Ganda — DESAIN FINAL", disetujui Aye) dan §5.3/§5.6 (panel
admin + slot dashboard). Tidak ada dokumen `RECON_fase8*`/`fase_8*` lain
yang sudah ada sebelum laporan ini (dicek eksplisit, nol hasil).

---

## RINGKASAN EKSEKUTIF (baca ini dulu)

**Tidak ada Gate/Policy terpusat sama sekali saat ini.** Setiap pengecekan
akses adalah salah satu dari tiga pola berbeda yang tidak saling terhubung:
(a) middleware `role:` generik (all-or-nothing per grup route), (b) SATU
middleware bespoke untuk SATU relasi spesifik (`EnsureProjectMembership`),
atau (c) `abort_if`/`abort_unless` yang membandingkan `->role` langsung,
di-copy-paste di 16 file berbeda (20 titik). Ini persis kondisi yang
diperingatkan dokumen rancangan §2.2.D: *"seluruh pengecekan akses HARUS
lewat SATU titik pengecekan terpusat... supaya tidak ada halaman yang lupa
dicek dan jadi celah keamanan."*

**Temuan paling konsekuensial untuk desain Gate:** middleware route-level
TIDAK CUKUP untuk kasus "akses baca-saja Eksplorasi" — `UnitEvaluation`
(quiz submit) di-**nested di dalam route yang SAMA** dengan `UnitShow`
(baca materi), begitu juga `CheckpointShow::submit()` dan
`Resources\Index::submit()` berada di route yang sama dengan tampilan
baca-nya. Artinya larangan "boleh baca, tidak boleh submit" TIDAK BISA
diimplementasikan cuma dengan middleware route — perlu guard tambahan DI
DALAM method action itu sendiri (submitQuiz, submitFreeText, markAsRead,
retry, submit), dengan sumber kebenaran yang sama dengan Gate terpusat.

**Skema `users` dan sistem notifikasi 100% belum tersentuh** — `role` cuma
3 nilai tetap, tidak ada kolom/tabel apa pun yang mengarah ke "mode aktif"
atau "akses tambahan". `hasDualModeCapability()` (`User.php`) memang
hardcode `return false;`, dikonfirmasi bukan bug — itu memang placeholder
sengaja sejak Fase 2.

---

## 1. Inventaris Menyeluruh Titik Pengecekan Akses

### 1a. Middleware `role:` di `routes/web.php` (grup/route, bukan per-baris)

| Prefix/Route | Middleware | Melindungi |
|---|---|---|
| `/admin/dashboard` | `role:admin` | Dashboard admin |
| `/eksplorasi/dashboard` | `role:exploration_member` | Dashboard pribadi Eksplorasi |
| `/eksplorasi/{kurikulum,unit/{unit},checkpoint/{checkpoint},resources,webi/{conversation?},praktik,praktik/{challenge}}` | `role:exploration_member` (satu grup) | Peta Kurikulum, Unit, Checkpoint, Referensi, WEBI, Praktik index+show |
| `/eksplorasi/forum/*` | `role:exploration_member,admin` | Forum Diskusi Eksplorasi (index/create/show) |
| `/eksekusi/dashboard` | `role:execution_member` | Dashboard pribadi Eksekusi (BUKAN `,admin` — beda dari kebanyakan grup Eksekusi lain) |
| `/eksekusi/avatar` | `role:execution_member` | Avatar picker Eksekusi |
| `/eksekusi/kalender` | `role:execution_member` | Kalender Personal (Fase 7 Batch 2b) |
| `/eksekusi/ideas/*` | `role:execution_member,admin` | Project Ideas index/create/approve |
| `/eksekusi/projects/*` (+ nested `/{project}/*` pakai `project.member` juga) | `role:execution_member,admin` (+ `project.member` untuk sub-tab) | Projects index/create, dan SEMUA tab proyek (Kanban/Roadmap/Gantt/Kalender/Forum/Anggota/tasks.create) |
| `/eksekusi/tasks/{task}` (redirect murni ke Kanban) | `role:execution_member,admin` + `project.member` | Link lama Detail Task |
| `/eksekusi/praktik/submissions/{submission}` | `role:execution_member,admin` | Halaman Review Praktik (RBAC halus tambahan di `mount()`, lihat 1c) |
| `/attachments/{attachment}/download` | `role:execution_member,admin` | Download attachment Task |
| `/challenge-submissions/{submission}/download` | `auth` saja (TANPA `role:`) | Controller sendiri yang putuskan (admin/reviewer/owner — bisa dari 3 role manapun) |
| `/challenge-attachments/{attachment}/download` | `auth` saja | Controller sendiri (draft dibatasi admin, published untuk siapa saja login) |
| `/admin/users/*`, `/admin/webi/*`, `/admin/curriculum/*` | `role:admin` | Seluruh panel admin |
| `/notifications`, `/profile`, `/dashboard` (redirect) | `auth` saja | Dipakai SEMUA role, sengaja tidak dibatasi role |
| `/dev/*` (4 route) | `auth` saja, dibungkus `app()->environment('local')` | Preview dev, tidak pernah ada di production |

**Total 12 kombinasi middleware `role:` berbeda** dipakai di seluruh
`routes/web.php`. Semua didukung SATU middleware generik yang sama (lihat
1b) — bukan 12 implementasi berbeda, cuma parameter berbeda.

### 1b. Middleware custom (`app/Http/Middleware/`, 3 file)

1. **`EnsureUserHasRole`** (alias `role`, didaftarkan `bootstrap/app.php:19`)
   — generik, `in_array($request->user()->role, $roles, true)`, `abort(403)`
   kalau gagal. Ini SATU-SATUNYA implementasi di belakang seluruh tabel 1a.
2. **`EnsureProjectMembership`** (alias `project.member`, Fase 7 Batch 1a)
   — resolve `Project` dari `{project}` atau `{task}->project`, lalu
   `$user->role !== 'admin' && !$project->members()->where('user_id', $user->id)->exists()`
   → 403. Konsolidasi dari 4 titik duplikat lama (bukan 3 seperti estimasi
   recon Fase 7 sebelumnya — `Tasks\Create` juga punya snippet identik,
   ketahuan pas dibangun).
3. **`EnsureMembershipIsActive`** — TIDAK terkait role, tapi preseden
   penting: didaftarkan GLOBAL di grup `web` (`bootstrap/app.php`, bukan
   cuma di route ber-`role:`), force-logout kalau `membership_status !==
   'active'`. Pola "middleware global yang re-evaluasi tiap request" ini
   relevan sebagai preseden teknis untuk Fase 8 (lihat bagian penutup).

### 1c. Pengecekan akses MANUAL tersebar (`abort_if`/`abort_unless`/`->role`, di luar 1a/1b)

Hasil grep menyeluruh `app/Livewire` + `app/Http/Controllers` untuk pola
`abort_if(...role...)`, `abort_unless(...role...)`, `->role !==`,
`->role ===`, `user()->role` — **20 titik di 16 file**:

| File : Baris | Pola | Melindungi |
|---|---|---|
| `Livewire/Eksekusi/Tasks/Show.php:88,97,106,123` | `abort_unless(role==='admin')` | `changeDeadline()`/`changePriority()`/`reassign()`/`delete()` — 4 aksi admin-only di panel task |
| `Livewire/Eksekusi/Projects/ProjectHeader.php:44,64` | `abort_unless(role==='admin')` | `changeStatus()`/`addMilestone()` |
| `Livewire/Eksekusi/Projects/Tabs/Anggota.php:34,55` | `abort_unless(role==='admin')` | `addMember()`/`removeMember()` |
| `Livewire/Eksekusi/Projects/Create.php:29` | `abort_unless(role==='admin')` | `mount()` — cuma admin bisa buat proyek langsung |
| `Livewire/Eksekusi/Ideas/Approve.php:35` | `abort_unless(role==='admin')` | `mount()` halaman approve ide |
| `Livewire/Eksekusi/Ideas/Index.php:21` | `abort_unless(role==='admin')` | `reject()` |
| `Livewire/Eksekusi/Projects/Index.php:21` | `if (role !== 'admin')` | Scoping list: admin lihat semua, member cuma proyeknya sendiri (bukan `abort`, tapi query-scoping berbasis role) |
| `Livewire/Eksekusi/Praktik/Review.php:38` | `role!=='admin' && assigned_reviewer_id!==user` | `mount()` — RBAC HALUS (bukan cuma role, tapi assignment spesifik) |
| `Http/Controllers/Eksekusi/AttachmentDownloadController.php:34` | `role!=='admin' && !member` | Download attachment (persis logic `EnsureProjectMembership`, TAPI ditulis ulang manual, bukan reuse middleware — karena controller, bukan route Livewire biasa) |
| `Http/Controllers/Eksplorasi/ChallengeSubmissionDownloadController.php:27-30` | `role!=='admin' && !reviewer && !owner` | Download submission — 3-arah (admin/reviewer/owner), bukan sekadar 1 role |
| `Http/Controllers/Eksplorasi/ChallengeAttachmentDownloadController.php:27` | `draft && role!=='admin'` | Download attachment challenge draft |
| `Livewire/Admin/Webi/Show.php:19` | `abort_unless(role==='exploration_member', 404)` | Halaman log WEBI per-user — 404 kalau target user BUKAN exploration_member (bukan soal SIAPA yang akses, tapi APA yang diakses) |
| `Services/Execution/TaskService.php:191,197` | `actor->role !== 'admin'` | Business rule transisi status task (bukan RBAC halaman, tapi aturan domain) |
| `Services/Execution/TaskService.php:307` | `author->role === 'admin'` | Cabang teks notifikasi (comment_from_admin vs comment_on_my_task) — bukan access control sama sekali |
| `Livewire/Profile/Edit.php:124,132` | `role === 'exploration_member'` / `'execution_member'` | Tampil/sembunyi SECTION di Profil (bukan access denial, murni conditional display) |

**Klasifikasi penting untuk desain Gate**: dari 20 titik ini, **~13 adalah
access-control sungguhan** (abort kalau gagal), sisanya (`TaskService`
business rule, notifikasi wording, `Profile\Edit` conditional display,
`Projects\Index` query-scoping) adalah **logic domain yang KEBETULAN
membaca `->role`**, bukan celah keamanan — TIDAK semua perlu diarahkan ke
Gate terpusat, cuma yang benar-benar "boleh akses atau tidak".

### 1d. Aksi TULIS yang nested di route BACA (tidak kelihatan dari middleware manapun)

Ini bukan celah pengecekan akses yang ADA sekarang (Eksplorasi masih
tertutup total untuk `execution_member` lewat `role:exploration_member`),
tapi jadi TITIK KRITIS begitu Fase 8 membuka akses baca. Route
`eksplorasi.unit.show` merender `<livewire:eksplorasi.unit-evaluation>`
**di dalam blade yang sama** (`unit-show.blade.php:184`) — artinya SATU
route yang sama menyajikan baik konten baca MAUPUN 4 aksi tulis:

| Komponen | Method tulis | Dipanggil dari route |
|---|---|---|
| `UnitEvaluation` | `submitQuiz()`, `submitFreeText()`, `markAsRead()`, `retry()` | `eksplorasi.unit.show` (nested di `UnitShow`) |
| `CheckpointShow` | `submit()` | `eksplorasi.checkpoint.show` |
| `Resources\Index` | `submit()` | `eksplorasi.resources` |
| `Forum\Create` | `save()` | `eksplorasi.forum.create` (sudah tertutup total utk execution_member, tidak relevan Fase 8) |
| `Forum\Show` | `reply()` | `eksplorasi.forum.show` (sama, sudah tertutup) |
| `Praktik\Show` | `submit()` | `eksplorasi.praktik.show` |
| `Webi\Chat` | `sendMessage()`, `newConversation()` | `eksplorasi.webi` — **PENGECUALIAN**: dokumen §2.2.A eksplisit mengizinkan WEBI tetap dipakai di mode baca ("bantuan baca kontekstual"), jadi ini BUKAN yang perlu diblokir |

**Kesimpulan poin 1**: Gate terpusat WAJIB beroperasi di DUA level: (1)
level route/middleware (siapa boleh MEMBUKA halaman ini), dan (2) level
aksi/method di dalam komponen (siapa boleh MENGUBAH data lewat aksi ini)
— middleware saja tidak cukup untuk 5 dari 7 komponen di atas.

---

## 2. Struktur `User` Model Sekarang

**Migrasi `0001_01_01_000000_create_users_table.php`** (skema asli,
belum pernah diubah untuk `users` sejak awal):

```php
$table->uuid('id')->primary();
$table->string('name');
$table->string('email')->unique();
$table->string('password_hash');
$table->enum('role', ['exploration_member', 'execution_member', 'admin']);
$table->string('avatar_url')->nullable();
$table->json('interest_field')->nullable();
$table->enum('membership_status', ['active', 'inactive']);
$table->timestamps();
```

**Dikonfirmasi persis**: `role` cuma 3 nilai, TIDAK ADA varian lain
(dicek langsung dari migrasi, bukan diasumsikan dari kode aplikasi).
`membership_status` cuma `active`/`inactive` (dipakai `EnsureMembershipIsActive`,
tidak terkait Mode Ganda).

**Field terkait "mode aktif"/"akses tambahan": NOL, dikonfirmasi.** Model
`User.php` (`app/Models/User.php`) tidak punya kolom/relasi apa pun ke
arah ini — satu-satunya jejak adalah method placeholder:

```php
/**
 * TODO Fase 8: Mode Ganda ... Skema untuk ini (kolom/tabel penyimpan
 * status mode ganda) belum ada sama sekali, jadi ini SENGAJA selalu
 * false sampai Fase 8 dibangun...
 */
public function hasDualModeCapability(): bool
{
    return false;
}
```

Docblock-nya sendiri sudah eksplisit menyatakan ini sengaja kosong. Tidak
ada tabel lain di `database/migrations` yang menyinggung "dual mode",
"mode ganda", atau "cross access" (dicek via grep, nol hasil).

---

## 3. Rute Eksplorasi — Klasifikasi Baca vs Tulis

| Route | Komponen | Klasifikasi | Catatan |
|---|---|---|---|
| `eksplorasi.dashboard` | `Dashboard.php` | **PERLU KEPUTUSAN** | Dashboard PRIBADI (poin/level/progres) — origin Eksekusi yang baca-saja tidak punya `UserExplorationProgress` sama sekali; menampilkan halaman ini apa adanya untuk mereka akan crash/kosong-aneh. Dokumen §2.2.A tidak menyebut dashboard sebagai bagian dari akses baca yang diizinkan (cuma sebut Peta Kurikulum/materi Unit/Referensi) — kemungkinan besar dashboard TIDAK termasuk read-mode, tapi perlu dikonfirmasi eksplisit sebelum implementasi, bukan diasumsikan. |
| `eksplorasi.kurikulum` (Peta Kurikulum) | `PetaKurikulum.php` | **BACA, TAPI BUTUH FORK LOGIC** | Diizinkan eksplisit §2.2.A. Implementasi SEKARANG memanggil `ProgressService::ensureProgress($user)` untuk hitung lock/unlock — dokumen minta lock/unlock DILEWATI utk mode ini (semua modul tampil terbuka). Ini bukan cuma soal RBAC, perlu percabangan logic di komponen. |
| `eksplorasi.unit.show` | `UnitShow.php` + nested `UnitEvaluation` | **BACA (parsial)** | Baca konten Unit diizinkan; `UnitEvaluation`'s 4 method tulis (submitQuiz/submitFreeText/markAsRead/retry) WAJIB tetap tertutup — lihat 1d. |
| `eksplorasi.checkpoint.show` | `CheckpointShow.php` | **BACA (parsial)** | Baca halaman checkpoint boleh (kalau memang perlu ditampilkan di mode baca — checkpoint sendiri tidak eksplisit disebut dokumen, cuma "materi Unit"); `submit()` WAJIB tertutup. |
| `eksplorasi.resources` | `Resources\Index.php` | **BACA (parsial)** | Diizinkan eksplisit ("Referensi"); `submit()` (usul referensi komunitas) WAJIB tertutup — ini "menulis" walau bukan progres akademik. |
| `eksplorasi.webi` | `Webi\Chat.php` | **DIIZINKAN PENUH (termasuk kirim pesan)** | Pengecualian eksplisit dokumen §2.2.A — chat TETAP fungsional penuh (kirim pesan, personalisasi fallback null-safe), yang dilarang cuma progres/poin/evaluasi, BUKAN percakapan WEBI itu sendiri. |
| `eksplorasi.praktik.index`, `.praktik.show` | `Praktik\Index/Show.php` | **TIDAK DISEBUT DOKUMEN — default tertutup, perlu konfirmasi** | §2.2.A cuma sebut "Peta Kurikulum, materi Unit, Referensi" — Praktik tidak disebut sama sekali. `Praktik\Show::submit()` jelas harus tertutup (submission = progres), tapi apakah SEKADAR MELIHAT daftar/detail Challenge termasuk "baca" yang diizinkan atau tidak, BELUM ada keputusan eksplisit di dokumen. Rekomendasi: default tertutup sampai dikonfirmasi (lebih aman dari asumsi terbuka). |
| `eksplorasi.forum.*` | `Forum\Index/Create/Show.php` | **TETAP TERTUTUP TOTAL** | Sudah `role:exploration_member,admin` — tidak disebut sebagai bagian akses baca Fase 8 sama sekali, dan forum adalah interaksi sosial (bukan "referensi"), jadi tidak masuk kategori read-only yang dimaksud dokumen. |

---

## 4. Rute Eksekusi — Middleware Sekarang (Dasar Perluasan)

Semua rute Eksekusi (tabel lengkap di 1a) gate dengan **role literal**:
`role:execution_member` (dashboard/avatar/kalender — TANPA `,admin`) atau
`role:execution_member,admin` (ideas/projects/tasks/praktik/attachments).
`project.member` (nested) mengecek `ProjectMember` row secara terpisah,
tidak terkait role asal sama sekali — ini akan TETAP BENAR tanpa
perubahan asalkan anggota Eksplorasi yang disetujui benar-benar
di-assign lewat `ProjectMember` seperti anggota Eksekusi asli (sesuai
janji dokumen §2.2.B: "sama seperti anggota Eksekusi asli").

**Titik yang WAJIB diperluas** (persis kutipan dokumen §2.2.D): setiap
kemunculan `role:execution_member` (dengan atau tanpa `,admin`) di tabel
1a — 8 baris route/grup — perlu logic baru: *"role asal Eksekusi ATAU
(origin Eksplorasi DENGAN akses granted DAN mode aktif = eksekusi)"*.
Karena SEMUANYA didukung middleware generik yang sama (`EnsureUserHasRole`),
titik perluasannya SATU tempat secara implementasi (ganti middleware-nya,
bukan edit 8 route satu-satu) — asalkan middleware barunya dipasang
sebagai pengganti `role:execution_member[,admin]` di seluruh 8 titik itu.

---

## 5. Pola Alur Pengajuan → Approval yang Bisa Dicontek

Dua kandidat, dipetakan detail state machine-nya:

### 5a. Project Ideas (`ProjectIdeaService`, paling dekat secara bentuk)

- Skema `project_ideas.status` enum: `draft` → `approved` **atau**
  `rejected` (2 jalur terminal, satu keputusan, tidak ada assignment/delegasi).
- `propose()`: anggota Eksekusi buat idea, status awal `draft`.
- `approve()` (admin-only, `Ideas\Approve::mount()` cek `abort_unless(role==='admin')`
  + `abort_unless($idea->status === 'draft', 404)` — idea yang sudah
  diputuskan tidak bisa diproses ulang): update status jadi `approved`,
  BUAT entitas baru (`Project`), kirim `Notifier::send(..., 'idea_status_changed', ...)`
  ke pengaju.
- `reject()` (`Ideas\Index::reject()`, admin-only): update status
  `rejected` + `rejection_reason` (kolom teks alasan), kirim notifikasi
  serupa.
- **Pola relevan untuk Fase 8**: single-step binary decision (bukan
  multi-tahap), guard `status === 'draft'` mencegah proses ulang idea
  yang sudah diputuskan (analog: `dual_mode_requests.status === 'pending'`
  guard sebelum admin bisa approve/reject).

### 5b. Praktik Submission Review (`Eksekusi\Praktik\Review`, lebih kaya tapi ada elemen tak relevan)

- Skema `challenge_submissions.status`: `pending` → `disetujui` **atau**
  `perlu_revisi`, PLUS `assigned_reviewer_id` (opsional, admin bisa
  delegasikan dulu ke `execution_member` sebelum diputuskan) dan
  `reviewed_at` (timestamp lock — begitu terisi, `abort_if($submission->reviewed_at !== null, 403)`
  mencegah approve/reject dobel, termasuk dari request/tab kedua yang
  masih terbuka).
- **Pola relevan untuk Fase 8**: kalau Fase 8 TIDAK butuh delegasi
  (dokumen §2.2.B bilang "admin meninjau & memutuskan" langsung, tidak
  ada konsep assign-ke-orang-lain untuk approval mode ganda) — cukup
  pola (a) di atas. TAPI pola `reviewed_at`-style lock (kolom timestamp
  "sudah diproses", dicek SEBELUM mutasi apa pun) layak dicontek buat
  cegah race condition approve dobel, terlepas dari ada/tidaknya
  delegasi.

**Rekomendasi**: Fase 8 paling dekat ke pola 5a (single-step,
admin-langsung-putuskan) + kunci `reviewed_at`-style dari 5b. Notifier
sudah punya morph map + `type` enum (25 nilai sekarang, di
`create_notifications_table` + 2 migrasi tambahan) — Fase 8 perlu
migrasi ADDITIF baru untuk `type` (mis. `dual_mode_request_submitted`,
`dual_mode_approved`, `dual_mode_rejected`, `dual_mode_revoked`) dan
kemungkinan `context_type` baru kalau permintaan mode ganda mau dijadikan
context notifikasi yang bisa diklik (pola sama seperti `challenge_submission`
ditambahkan di 2026_07_11).

---

## 6. Leaderboard & Progress — Konfirmasi Tidak Perlu Berubah

**Dikonfirmasi BENAR, dicek langsung dari kode**:

```php
// Admin\Dashboard::explorationLeaderboard()
return User::where('role', 'exploration_member')->orderBy('name')->get()->map(...)
```

Query ini filter murni berdasar kolom `role` (origin, permanen per
dokumen §2.2.C: *"Admin tidak ikut sistem mode ganda... role asal TIDAK
berubah"*). Selama implementasi Fase 8 benar-benar mengikuti prinsip ini
(kolom `role` immutable, akses tambahan disimpan di kolom/tabel LAIN),
query ini otomatis tetap benar tanpa modifikasi — anggota Eksplorasi yang
disetujui masuk Eksekusi tetap `role='exploration_member'` selamanya,
tetap muncul di leaderboard.

**TAPI ditemukan asumsi tersembunyi yang PERLU diperbaiki (bukan di
leaderboard, tapi di titik lain yang bergantung sama prinsip serupa)**:
`PetaKurikulum.php` dan `UnitShow.php` memanggil `ProgressService::ensureProgress($user)`
**tanpa syarat** — method ini (dilihat sebelumnya di kode Eksplorasi)
MEMBUAT baris `UserExplorationProgress` kalau belum ada. Kalau origin
Eksekusi mengunjungi halaman ini di mode baca TANPA guard tambahan,
`ensureProgress()` akan otomatis MEMBUAT progress record untuk mereka —
melanggar langsung janji dokumen §2.2.A *"TIDAK ada `UserExplorationProgress`
yang dibuat/diubah"*. Ini WAJIB diperbaiki (bukan berubah dengan
sendirinya) saat Fase 8 dibangun — bercabang berdasar mode sebelum
memanggil `ensureProgress()`.

---

## 7. Dashboard Admin — Slot yang Perlu Diisi

**Dikonfirmasi TIDAK ADA sama sekali** — `App\Livewire\Admin\Dashboard.php`
dibaca penuh: method `render()` cuma mengembalikan `projects`, `alerts`,
`feed`, `memberSummaries`, `explorationLeaderboard`, `webiSummary`,
`curriculumSummary`. Tidak ada `dualModeQueue`/sejenisnya. Blade-nya juga
dicek (grep "Mode Ganda"/"dual" di `livewire/admin/dashboard.blade.php`)
— nol hasil.

Beda dengan slot Praktik ("Antrian Review Praktik") yang SUDAH disiapkan
sebagai nav item `enabled: false` sejak Fase 3 lalu tinggal diisi —
"Antrian Permintaan Mode Ganda" belum punya jejak APAPUN (bukan nav slot
disabled, bukan card placeholder, bukan query kosong). Perlu dibangun
dari nol: nav item baru (`config/navigation.php['admin']`), card dashboard
baru, query `dual_mode_requests where status='pending'`, dan halaman
panel admin approve/reject-nya sendiri (§5.3 dokumen: "Antrian Permintaan
Mode Eksekusi" + "Cabut Akses" dari halaman Manajemen Akun).

---

## 8. Badge Status Mode & Slot Profil — Kondisi Sekarang

### 8a. `mode-badge.blade.php` (navbar, Fase 2)

```blade
@if (auth()->user()?->hasDualModeCapability())
    <span class="rounded-full border border-accent/40 bg-accent-soft px-2.5 py-1 font-mono text-xs font-semibold text-ink">
        {{-- TODO Fase 8: tampilkan mode aktif (Eksplorasi/Eksekusi) sungguhan --}}
    </span>
@endif
```

Persis seperti diduga — kondisi selalu `false` (lewat method placeholder
di poin 2), badge secara struktural sudah di posisi yang benar di navbar
(urutan: Badge → Bell → Grid → Avatar, sesuai §1.5 dokumen) tapi isinya
kosong total, tinggal comment TODO.

### 8b. Slot "Info Mode Ganda" di Profil (Fase 3 Batch 7b)

`resources/views/livewire/profile/edit.blade.php:470-488` — pola
placeholder IDENTIK slot "Segera Hadir" lain (dashed border, badge
"Segera Hadir", opacity-70), tampil untuk `exploration_member`/`execution_member`
(bukan admin), teks statis: *"Status akses lintas-mode (Eksplorasi ↔
Eksekusi) akan tampil di sini begitu Fase 8 aktif."* — murni informational,
NOL interaktivitas.

**Kesenjangan dengan spesifikasi dokumen** (perlu diperhatikan saat
implementasi, bukan cuma isi ulang placeholder dengan teks status):
dokumen §2.2.B eksplisit minta slot ini jadi **titik aksi**, bukan cuma
info pasif — *"begitu admin approve, link/kartu navigasi ke portal
Eksekusi MUNCUL di halaman Profil... titik pertama dia sadar aksesnya
terbuka."* Untuk anggota Eksplorasi yang BELUM mengajukan, slot ini
kemungkinan juga perlu CTA "Ajukan Akses Eksekusi" (bukan cuma teks
status). Placeholder sekarang tidak punya ruang untuk `wire:click`/tombol
sama sekali — perlu rombak struktur, bukan sekadar ganti isi teks.

### 8c. Menu Akun (navbar dropdown) — TIDAK DICEK EKSPLISIT DI PROMPT, TAPI RELEVAN LANGSUNG

Dicek langsung (`resources/views/components/shell/account-menu.blade.php`)
karena dokumen §2.2.C eksplisit bilang switching mode dilakukan *"lewat
menu Akun (navbar)"* — isi menu ini SEKARANG cuma 2 item: "Profil Saya"
dan "Keluar". **Nol jejak mekanisme switch-mode apa pun** — perlu item
baru ("Ganti Mode" atau serupa) ditambahkan, kondisional terhadap
`hasDualModeCapability()`, kemungkinan besar jadi titik implementasi
paling terlihat user selain badge/slot profil.

---

## Usulan Struktur Gate/Middleware Terpusat

Berdasar semua temuan di atas (terutama 1d — kebutuhan guard di level
AKSI, bukan cuma route):

**1. `User` — dua method baru sebagai SATU-SATUNYA sumber kebenaran**
(dipanggil dari middleware MAUPUN guard aksi di komponen, tidak pernah
dihitung ulang beda tempat):

```php
public function activeMode(): string   // 'eksplorasi' | 'eksekusi' | 'admin'
public function canAccessEksekusi(): bool   // role==='execution_member' OR (granted && activeMode()==='eksekusi')
public function canAccessEksplorasi(): bool // role==='exploration_member' OR role==='execution_member' (baca-saja OTOMATIS free, tanpa syarat granted)
public function isReadOnlyInEksplorasi(): bool // true kalau origin Eksekusi (akses baca-saja, bukan exploration_member asli/granted)
```

**2. Middleware — perluas pola `EnsureUserHasRole` yang SUDAH ada**
(konsisten konvensi existing, bukan pola baru): parameter baru
`role:mode-eksekusi` / `role:mode-eksplorasi-read` yang membaca method di
atas alih-alih `in_array($role, $roles)` literal — ATAU dua middleware
class baru terpisah (`EnsureCanAccessEksekusi`/`EnsureCanAccessEksplorasi`)
kalau logic keduanya cukup beda untuk tidak dipaksakan satu class
parametrized. Menggantikan LITERAL setiap `role:execution_member[,admin]`
di 8 titik (poin 4) dan `role:exploration_member` di titik yang memang
dibuka baca (poin 3) — TIDAK menyentuh `role:admin` murni (Admin tidak
ikut sistem ini, §2.2.C) atau `role:exploration_member,admin` Forum
(tetap tertutup total).

**3. Guard level-aksi — helper method kecil dipanggil di AWAL tiap
method tulis yang nested di route baca** (poin 1d, 5 komponen):

```php
abort_if(Auth::user()->isReadOnlyInEksplorasi(), 403);
```

Satu baris, dipanggil eksplisit di `submitQuiz()`/`submitFreeText()`/`markAsRead()`/`retry()`
(`UnitEvaluation`), `submit()` (`CheckpointShow`, `Resources\Index`,
`Praktik\Show`) — TIDAK di `Webi\Chat` (pengecualian dokumen). Auditable
lewat grep sederhana (`isReadOnlyInEksplorasi`) untuk verifikasi tidak
ada yang lupa, mirip cara recon ini sendiri men-grep pola `role`.

**4. `EnsureProjectMembership` TIDAK diubah** — orthogonal, otomatis
tetap benar asalkan admin benar-benar menambah anggota Eksplorasi yang
disetujui ke `ProjectMember` seperti anggota asli.

**5. Skema baru (perlu RECON/konfirmasi terpisah soal nama kolom
persis saat implementasi, bukan diputuskan final di sini)**: minimal
`users.dual_mode_granted` (bool/timestamp) + `users.active_mode` (enum)
untuk arah B, PLUS tabel baru `dual_mode_requests` (id, user_id, status
pending/approved/rejected, reviewed_by, reviewed_at, timestamps) — pola
skema mirip `project_ideas` (5a).

---

## Usulan Pemecahan Batch Implementasi

Mengingat riwayat kerja (Fase 7 dipecah jadi 7 batch kecil untuk fitur
yang JAUH lebih rendah risiko dari ini) dan peringatan eksplisit prompt
task ini ("PALING BERISIKO"), diusulkan **7 batch kecil**, urutan wajib
(tidak bisa ditukar — tiap batch bergantung batch sebelumnya):

1. **Batch A — Skema murni**: `users` (2 kolom baru) + tabel
   `dual_mode_requests`. Backup wajib, konfirmasi eksplisit sebelum
   eksekusi (menyentuh tabel `users` yang paling sensitif di seluruh app).
   NOL logic akses berubah di batch ini.
2. **Batch B — Gate terpusat + swap middleware Eksekusi**: method `User`
   baru + middleware baru, ganti SEMUA `role:execution_member[,admin]`
   (8 titik) ke middleware baru. Regresi WAJIB: seluruh
   `RouteAccessMatrixTest.php` + matrix RBAC Eksekusi lain harus tetap
   100% lolos TANPA modifikasi assertion (pola sama seperti pembuktian
   non-regresi Forum Eksplorasi di Fase 7 Batch 4).
3. **Batch C — Akses baca Eksplorasi (arah A, tanpa approval)**: buka
   `role:exploration_member` jadi bisa dimasuki origin Eksekusi di
   PetaKurikulum/UnitShow/Resources (fork lock-bypass logic + guard
   `isReadOnlyInEksplorasi()` di 5 method tulis, poin 1d) + banner
   keterangan wajib (§2.2.A). WEBI Chat: buka akses TANPA guard tulis
   (pengecualian).
4. **Batch D — Alur pengajuan member (arah B, sisi pengaju)**: UI
   "Ajukan Akses Eksekusi" (kemungkinan di slot Profil, poin 8b),
   `dual_mode_requests` create, notifikasi ke admin.
5. **Batch E — Panel admin approve/reject + cabut akses**: halaman baru
   §5.3, update `dual_mode_requests.status` + `users.dual_mode_granted`,
   notifikasi ke pengaju, nav slot + card dashboard admin (poin 7),
   "Cabut Akses" dari Manajemen Akun.
6. **Batch F — UI switching + badge + slot Profil jadi aktif**: menu
   Akun dapat item "Ganti Mode" (poin 8c), `mode-badge.blade.php` isi
   sungguhan, slot Profil jadi actionable (bukan cuma teks, poin 8b).
7. **Batch G — Sweep regresi penuh + edge case**: revoke akses di
   tengah sesi (pola sama `EnsureMembershipIsActive`, poin 1b — cek
   ulang tiap request, bukan cuma saat login), nasib
   assignment/task/ProjectMember yang tersisa saat akses dicabut
   (pertanyaan terbuka dokumen §2.2.B — "kemungkinan tetap sebagai
   riwayat, tidak otomatis unassign", perlu keputusan eksplisit sebelum
   Batch G, bukan diasumsikan), dan test suite penuh + `npm run build`.
