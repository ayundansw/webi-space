# Recon Menyeluruh — Semua Area Fase 3

Laporan ini murni observasi kode & skema NYATA saat ini (dicek langsung dari file, bukan dari ingatan/dokumen lama). **Tidak ada kode/skema yang diubah.**

---

## 1. Dashboard Eksplorasi

**File:** `App\Livewire\Eksplorasi\Dashboard` + `resources/views/livewire/eksplorasi/dashboard.blade.php`.

**Urutan section sekarang (top to bottom):**
1. Ornamen SVG dekoratif (circuit echo, `text-accent/20`, pojok kanan-atas card pembungkus).
2. `<x-greeting />`.
3. 3 `<x-stat-card elevated>` dalam grid: Level Saat Ini, Total Poin, Progres Keseluruhan (%).
4. Card Leaderboard (Top 5 + baris "Kamu peringkat N" kalau di luar top 5, pesan apresiasi/motivasi acak).
5. Card "Sedang Dikerjakan" (hero/warm-accent, unit berikutnya + tombol "Lanjut Belajar", atau pesan "semua selesai" + link Peta Kurikulum).
6. Card "Tanya WEBI" (ikon + deskripsi + tombol "Mulai Chat").
7. "Log Aktivitas" — list feed dari `ProgressService::feedFor()` (unit selesai + checkpoint selesai, digabung & diurutkan terbaru, lihat poin 9 soal sumber data).

**Status token v2:** SUDAH PENUH pakai token final — `bg-surface`, `rounded-2xl`/`rounded-xl`, `shadow-warm-xs`/`shadow-warm-md`/`shadow-warm-lg`, `border-warm/30`, `bg-warm-soft`, transisi hover (`hover:-translate-y-0.5`). Ini halaman PALING MATANG secara visual di seluruh aplikasi — dipakai sebagai *template* rujukan sejak awal batch token v2. **Yang BELUM dipakai di sini:** wordmark/maskot/avatar (belum relevan untuk dashboard), `--radius-control`/`--radius-card` eksplisit (masih `rounded-xl`/`rounded-lg`/`rounded-2xl` langsung, walau NILAI-nya kebetulan sama), `--color-heading`/`--color-body`/`--color-caption` (masih `text-ink`/`text-muted` langsung).

---

## 2. Peta Kurikulum

**File:** `App\Livewire\Eksplorasi\PetaKurikulum` + `resources/views/livewire/eksplorasi/peta-kurikulum.blade.php`.

**Struktur data:** `Module::orderBy('order_number')->with('units')`, tiap modul dipetakan ke `['module', 'status', 'percentage', 'units']`, tiap unit ke `['unit', 'locked', 'completed', 'in_progress']` — SEMUA dibaca langsung dari `ProgressService` + `UserUnitProgress`, tidak ada query tambahan di level view.

**Struktur visual sekarang: LIST VERTIKAL bergaya "jalur" (bukan roadmap.sh horizontal-grid style).** Satu kolom (`max-w-xl`), tiap modul = node bulat besar (h-14 w-14) + garis vertikal penghubung ke modul berikutnya, collapsible (Alpine `x-data="{open: ...}"`, auto-terbuka kalau status modul `active`). Di dalam tiap modul yang terbuka: unit-as-node yang lebih kecil (h-8 w-8) dengan pola identik (garis vertikal penghubung, badge "Kamu di sini" untuk in-progress). Checkpoint muncul sebagai baris terpisah di akhir tiap modul.

**PENTING untuk Fase 3:** ini SUDAH signature element "jalur kurikulum" sesuai `docs/design-tokens.md` §4 (node selesai=`bg-ink`+centang, aktif=`bg-accent`+ring, terkunci=`bg-white border-muted`+gembok — cocok 1:1 dengan spek warna di dokumen itu). **TAPI orientasinya VERTIKAL, bukan horizontal** — recon sebelumnya (`RECON_status_awal_fase_final.md` poin D9) sudah mencatat ini sebagai "DITERIMA (keputusan user)" — adaptasi mobile-friendly yang disengaja, BUKAN bug. Kalau Fase 3 memang minta gaya "roadmap.sh" (biasanya horizontal/zigzag), ini perlu keputusan eksplisit ulang: pertahankan vertikal (device-friendly, sudah teruji) atau redesign ke horizontal (risiko regresi mobile yang sudah pernah diputuskan sengaja dihindari).

---

## 3. Halaman Materi + WEBI Kontekstual (PALING BERISIKO — lihat highlight di akhir)

**File:** `App\Livewire\Eksplorasi\UnitShow` + `resources/views/livewire/eksplorasi/unit-show.blade.php`, plus `App\Livewire\Eksplorasi\Webi\Chat` (dipakai ulang, bukan komponen terpisah).

**Konten materi: MASIH FORMAT LAMA, BUKAN `<x-content-blocks>`.** Isi unit dirender murni `@foreach (explode("\n\n", $unit->content) as $paragraph) <p>{{ $paragraph }}</p> @endforeach` — `Unit.content` masih kolom `text` polos (paragraf dipisah newline-ganda), bukan JSON blocks. **Ini mengonfirmasi ULANG (tidak ada drift) temuan `RECON_konten_dinamis.md`**: fondasi `<x-content-blocks>` (9 tipe blok, `ContentBlock` model, `HtmlSanitizer`) sudah lengkap dari 2.2.4a tapi **BELUM ADA satu pun unit produksi yang memakainya** — migrasi 67 unit dari `content` teks polos ke `content_blocks` JSON adalah pekerjaan besar TERPISAH yang masih terutang penuh, bukan sesuatu yang otomatis "sudah beres" karena fondasinya ada.

**Slide-over WEBI sekarang:**
- State: Alpine LOKAL `x-data="{ webiPanelOpen: false }"` di root div halaman (bukan Livewire property, murni client-side show/hide).
- Trigger: tombol melayang (`fixed bottom-6 right-6`, bulat, ikon bintang) yang SELALU terlihat (floating action button), bukan bagian dari toolbar/header.
- Panel: `fixed inset-y-0 right-0` (slide dari kanan, `translate-x-full` ↔ `translate-x-0`), lebar `w-full sm:w-md` (jadi FULL-SCREEN overlay di mobile, 448px di desktop), backdrop gelap terpisah yang menutup panel kalau diklik.
- Isi panel: reuse PENUH `<livewire:eksplorasi.webi.chat :context-unit="$unit" :key="'webi-contextual-'.$unit->id" />` — sama persis komponen dengan halaman chat penuh, cuma dikasih prop `contextUnit` tambahan (lihat poin 4). **Tidak ada komponen/desain chat kedua** — kalau Fase 3 redesign jadi 3-kolom, WEBI-nya sendiri (isi pesan, form kirim, riwayat) TIDAK perlu dibangun ulang, cuma WADAH/POSISI-nya yang berubah dari slide-over jadi kolom ketiga permanen.

**Daftar Isi Modul: TIDAK ADA SAMA SEKALI, perlu dibangun dari nol.** Halaman `unit-show.blade.php` sekarang cuma py `<a href="...">&larr; Kembali ke Peta Kurikulum</a>` di atas — tidak ada sidebar/daftar unit lain dalam modul yang sama, tidak ada navigasi unit-sebelumnya/unit-berikutnya. Struktur "3 kolom" yang diminta Fase 3 (kemungkinan: daftar-isi | materi | WEBI) berarti kolom kiri itu 100% pekerjaan baru — data-nya SUDAH ADA (`$unit->module->units` via relasi yang sudah ada, plus flag locked/completed/in_progress yang sudah dihitung `ProgressService`), tapi presentasinya belum pernah dibangun di halaman ini.

---

## 4. WEBI Chat (halaman penuh)

**File:** `App\Livewire\Eksplorasi\Webi\Chat` (dipakai bersama untuk halaman penuh maupun slide-over) + `resources/views/livewire/eksplorasi/webi/chat.blade.php`.

**Riwayat percakapan: SUDAH ADA, bukan cuma direncanakan.** Dropdown "Riwayat (N)" di toolbar (pola `x-data="{open:false}"` + `@click.outside`, identik `notifications/bell.blade.php`), menampilkan `pastConversationsFor()` — tiap item: tanggal mulai (`started_at`), preview 60 karakter pesan pertama user, jumlah pesan. Klik → navigasi ke `/eksplorasi/webi/{conversation}` (route sudah ada, parameter `conversation` optional: `eksplorasi/webi/{conversation?}`), yang me-mount komponen dengan `isHistoryView = true` (mode read-only-ish, proactive greeting di-skip, tapi `sendMessage()` tetap berfungsi sama — user BISA lanjut chat di percakapan lama, "bisa dilanjutkan" terkonfirmasi bukan cuma dibaca).

**Data `Conversation`/`Message`: siap pakai, tidak perlu perubahan skema.** `Conversation` (`started_at`=CREATED_AT, `last_message_at`=UPDATED_AT, relasi `messages()` hasMany) dan `Message` (`sender`, `content`, `unit_context`, `voice_mode`) sudah punya semua field yang dibutuhkan untuk list riwayat + preview + resume. Tidak ada gap data untuk fitur ini.

**Elemen lain di halaman:** toggle "Mode suara" (checkbox, cuma muncul kalau `voiceSupported` via Alpine `webiVoice()`), notice transparansi monitoring (persistent, tidak bisa ditutup — baris 57-60 ke atas, belum dibaca penuh tapi dikonfirmasi keberadaannya).

---

## 5. Referensi — ringkas, rujuk `RECON_referensi.md` (masih akurat, TIDAK ADA drift)

Tidak ada perubahan kode di area ini sejak recon itu dibuat. Ringkasan: tabel `learning_resources` cuma 4 kolom data (`module_id` NOT NULL, `title`, `url`, `source_name` — yang mana **`source_name` secara de facto sudah dipakai sebagai deskripsi panjang**, bukan label singkat, di data produksi `CurriculumSeeder`). **HANYA bisa dibuat lewat seeder** — nol UI tulis (admin maupun member), `Resources\Index` murni `render()`. Migrasi baru WAJIB untuk fitur "submit member" (`created_by` nullable + kemungkinan `description` terpisah) — lihat bagian migrasi di bawah untuk detail + flag eksplisit.

---

## 6. Forum — ringkas, rujuk `RECON_forum.md` (masih akurat, TIDAK ADA drift)

Tidak ada perubahan kode di area ini sejak recon itu dibuat. Ringkasan: skema `forum_threads.module_id`/`unit_id` **SUDAH nullable sejak v1.0** — thread "general" TIDAK BUTUH migrasi sama sekali, murni ubah logic aplikasi (hapus pengecekan manual wajib-pilih-salah-satu di `Forum\Create::save()` baris 36-40 + sesuaikan microcopy form). 1 test (`test_thread_without_module_or_unit_is_rejected`) perlu diganti (bukan dihapus) jadi test yang membuktikan sebaliknya.

---

## 7. Dashboard Eksekusi

**File:** `App\Livewire\Eksekusi\Dashboard` + `resources/views/livewire/eksekusi/dashboard.blade.php`.

**Urutan section:** `<x-greeting />` → 3 `<x-stat-card>` (Todo/In Progress/In Review, **TANPA `elevated`** — beda dari dashboard Eksplorasi yang pakai elevated di semua statnya, jadi secara visual dashboard Eksekusi masih pakai style DEFAULT/flat token v2, belum di-upgrade ke elevated seperti Eksplorasi) → 2 tombol link (Project Ideas, Proyek Saya) → "Proyek yang Kamu Ikuti" (list card per-proyek: judul, tipe, jumlah task, milestone terdekat, progress bar, badge status) → "Peringatan" (list alert, severity 1-3, warna **MASIH Tailwind default mentah** `border-red-300 bg-red-50`/`border-orange-300 bg-orange-50`/`border-amber-200 bg-amber-50` — BELUM migrasi ke `--color-danger`/`--color-warning` sama sekali).

**Status token v2:** SEBAGIAN — struktur card sudah `rounded-xl border-muted/25` (konsisten pola lama, bukan token-v2 elevated), tapi warna severity alert masih hardcode Tailwind lama. Scope AlertService sudah benar (per-member, cuma proyek sendiri, `inactiveMembers()` sengaja dikecualikan karena itu info admin-facing) — TIDAK perlu diubah saat re-skin, murni ganti kelas warna.

---

## 8. Project Ideas

**File:** `App\Livewire\Eksekusi\Ideas\Index` (list + reject), `App\Livewire\Eksekusi\Ideas\Create` (form usul), `App\Livewire\Eksekusi\Ideas\Approve` (belum dibaca detail, tapi rujuk struktur status di bawah).

**Field form Create:** `title`, `description`, `purpose` — SEMUA `required|string` (title dibatasi `max:255`, description/purpose bebas panjang). Tidak ada field lain (tidak ada kategori/tag/lampiran).

**Struktur status: `ProjectIdea.status` (dari gap lama, "idea-approval field gap" — dicek ulang di sini, TIDAK ADA drift):**
- `Index::$statusFilter` default `'draft'` — artinya **halaman list SEKARANG cuma nampilin SATU status per kunjungan** (query `where('status', $this->statusFilter)`), BUKAN sudah dipisah visual "Menunggu Keputusan" vs "Riwayat" dalam satu tampilan. Filter status kemungkinan besar berupa tab/dropdown yang tidak sempat kebaca detail di sini (perlu cek langsung view `index.blade.php` kalau prompt implementasi Fase 3 butuh detail UI filter-nya).
- `reject()` mensyaratkan alasan wajib diisi (`rejectReasons` keyed per idea id), delegasi ke `ProjectIdeaService::reject()`.
- Approve ada di komponen terpisah (`Ideas\Approve`), tidak digabung ke `Index`.

**Catatan:** untuk memastikan struktur "Menunggu Keputusan vs Riwayat" akurat, `index.blade.php` dan `ProjectIdeaService` perlu dibaca detail saat prompt implementasi Fase 3 ditulis — recon ini baru memastikan filter status ADA (single-status-at-a-time), belum memastikan BAGAIMANA UI filter-nya disusun.

---

## 9. Profil (Eksplorasi & Eksekusi)

**File:** `App\Livewire\Profile\Edit` + `resources/views/livewire/profile/edit.blade.php`.

**CRUD dasar: LENGKAP.**
- Nama + `interest_field` (checkbox multi-value) → `saveProfile()`.
- Password → `changePassword()`, WAJIB konfirmasi password lama (`Hash::check`) + `min:8|confirmed` untuk yang baru.
- Email: **read-only, TIDAK BISA diubah lewat sini sama sekali** (keputusan produk lama, disebutkan di docblock — mencegah risiko lockout/typo, cuma admin yang boleh ubah email lewat `Admin\Users\Edit`).
- `role`/`membership_status`: sengaja TIDAK PERNAH diekspos di form ini (guard eksplisit di docblock).

**Data untuk kalender aktivitas/heatmap: KEDUANYA ADA & TERISI, dikonfirmasi langsung (bukan cuma fillable):**
- `UserUnitProgress.completed_at` — diset eksplisit di `ProgressService::completeUnit()` (`$progress->completed_at = $progress->completed_at ?? now();`), SUDAH dipakai aktif untuk Log Aktivitas dashboard (poin 1) dan tie-break leaderboard.
- `CheckpointCompletion.completed_at` — `const CREATED_AT = 'completed_at'`, otomatis terisi Eloquent tiap baris dibuat, SUDAH dipakai sama seperti di atas.

Kedua field ini SUDAH jadi sumber data aktif di 2 fitur produksi lain (bukan cuma kolom kosong menunggu dipakai) — heatmap/kalender aktivitas tinggal query `GROUP BY DATE(completed_at)` dari dua tabel ini, tidak perlu migrasi/kolom baru sama sekali.

**Data kontribusi per-proyek: bisa diturunkan dari `TaskAssignment`, DIKONFIRMASI tidak ada kolom `role`.** `TaskAssignment` fillable cuma `['task_id', 'user_id', 'assigned_by']` — TIDAK ADA kolom peran/jabatan. `ProjectMember` juga cuma `['project_id', 'user_id']` — sama, tidak ada kolom peran. Ini konsisten dengan keputusan lama ("role field ditolak") dan `RECON_project_member_peran.md` (dari recon Fase 1) yang sudah mencatat gap ini SEBAGAI GAP TERBUKA, bukan sesuatu yang sudah dibuild — kalau Fase 3 butuh "peran per proyek" di halaman Profil, itu tetap butuh migrasi kolom baru yang BELUM ada (additive, risiko rendah, tapi tetap migrasi skema — wajib flag eksplisit ke Aye sebelum eksekusi, sesuai konvensi proyek).

Kontribusi per-proyek TANPA peran (cuma "ikut di proyek X, mengerjakan N task, task apa saja") sepenuhnya bisa diturunkan dari `TaskAssignment` + `ProjectMember` yang sudah ada, tanpa migrasi apa pun.

---

## 10. Kanban/Proyek (Eksekusi)

**File:** `App\Livewire\Eksekusi\Projects\Board` + `resources/views/livewire/eksekusi/projects/board.blade.php`.

**Drag-and-drop: Native HTML5 Drag and Drop API, TIDAK ADA library JS pihak ketiga.** Dikonfirmasi langsung dari docblock kelasnya sendiri + kode: `draggable="true"` + Alpine `x-on:dragstart`/`x-on:dragover.prevent`/`x-on:drop.prevent`/`x-on:dragend` murni bawaan browser, dikombinasikan Alpine cuma untuk state visual (`dragging` → opacity kartu, `dragOver` → highlight kolom tujuan). Saat drop: `dataTransfer.getData('text/task-id')` + `'text/from-status'` dibaca, lalu `$wire.changeStatus(taskId, newStatus)` dipanggil — method Livewire YANG SAMA dengan tombol status manual di tiap kartu (`Mulai Kerjakan`/`Submit Review`/`Lolos`/`Perlu Revisi`), yang delegasi ke `TaskService::changeStatus()` (validasi/RBAC terpusat di situ, bukan di kartu/kolom).

**Implikasi PENTING untuk re-skin warna:** drag-drop TIDAK bergantung pada class Tailwind/warna sama sekali — semua mekanismenya di atribut `x-on:*`/`draggable`/`dataTransfer`, murni struktural. **Aman diubah warnanya asal:**
- Atribut `draggable="true"`, `x-data="{dragging: false}"` (per-kartu) dan `x-data="{dragOver: false}"` (per-kolom) TIDAK dihapus/direname.
- Handler `x-on:dragstart`/`x-on:dragover.prevent`/`x-on:drop.prevent`/`x-on:dragend` TIDAK dihapus.
- String key `'text/task-id'`/`'text/from-status'` di `dataTransfer` TIDAK diubah (harus match persis antara `dragstart` dan `drop`).
- Tombol status manual (fallback non-drag) TETAP ada — bukan cuma UX redundant, tapi jalur akses yang sama pentingnya (drag-drop kurang accessible untuk keyboard/touch-assistive).

Kartu proyek lain: badge status masih `border-muted/40` polos (belum token semantik), badge `OVERDUE` masih `bg-red-100 text-red-700` (Tailwind mentah, sama pola dengan alert dashboard di poin 7).

---

## Migrasi Referensi (untuk Langkah 5) — detail ulang + FLAG EKSPLISIT

**Dikonfirmasi ulang dari `RECON_referensi.md`, tidak ada drift.** Kolom yang perlu ditambahkan ke `learning_resources` (additive, nullable, aman untuk data existing):
- `created_by` (foreignUuid → `users`, **nullable**) — baris lama otomatis `NULL` = "Dari Admin", tanpa backfill.
- `description` (text, nullable) — opsional tergantung keputusan produk apakah `source_name` lama mau dipisah maknanya atau dibiarkan.
- (Kondisional) kolom status moderasi — HANYA kalau "tayang otomatis tanpa moderasi" ternyata bukan keputusan final.

**FLAG EKSPLISIT (wajib dibaca sebelum eksekusi):** ini MIGRASI SKEMA — sesuai aturan proyek (`CLAUDE.md`: "Jangan ubah struktur skema database yang sudah ada tanpa konfirmasi eksplisit"), migrasi ini harus dikonfirmasi Aye SEBELUM dijalankan, dan **WAJIB backup database dulu** sebelum `php artisan migrate` di production — walau risikonya rendah (murni ADD COLUMN nullable, tidak ada ALTER/DROP data existing), backup tetap wajib sebagai standar proses, bukan opsional karena "risikonya kecil".

---

## Highlight — Area Paling Berisiko/Kompleks untuk Prioritas Hati-Hati

1. **Halaman Materi + WEBI (poin 3) — PALING KOMPLEKS.** Bukan cuma re-skin warna: (a) slide-over → 3-kolom permanen berarti mekanisme buka-tutup (Alpine `webiPanelOpen`, backdrop, translate-x transition) SELURUHNYA dibuang, bukan direstyle — beda kelas pekerjaan dari re-skin biasa; (b) Daftar Isi Modul dibangun dari NOL (bukan reposisi elemen yang sudah ada, beda dari kasus breadcrumb/navbar Fase 2 kemarin); (c) risiko godaan untuk "sekalian" migrasi ke `<x-content-blocks>` di task yang sama — WAJIB dipisah eksplisit di prompt master supaya tidak scope-creep ke pekerjaan migrasi-67-unit yang jauh lebih besar dan belum diminta.
2. **Kanban Board (poin 10) — risiko fungsional tersembunyi kalau re-skin ceroboh.** Drag-and-drop native HTML5 gampang rusak kalau seseorang "membersihkan" atribut yang terlihat tidak relevan secara visual (`draggable`, `x-on:drag*`, `dataTransfer` key names) tanpa sadar itu bagian dari mekanisme, bukan styling. Prompt master WAJIB eksplisit larang menyentuh atribut-atribut itu.
3. **Peta Kurikulum (poin 2) — potensi konflik keputusan lama vs ekspektasi baru.** Orientasi vertikal sudah pernah diputuskan SENGAJA (bukan bug) di sesi sebelumnya; kalau Fase 3 minta "roadmap.sh style" secara harfiah (biasanya horizontal), ini butuh keputusan ulang eksplisit dari Aye, bukan diasumsikan otomatis "ya, redesign ke horizontal" atau otomatis "pertahankan vertikal" — dua-duanya valid tergantung maksud sebenarnya.
4. **Project Ideas (poin 8) — data UI filter status belum lengkap kepetakan.** Recon ini baru mengonfirmasi ADA filter status single-value, belum detail bagaimana UI-nya (tab? dropdown? Sudah ada pemisahan visual "Menunggu Keputusan"/"Riwayat" atau belum?) — kalau prompt master Fase 3 butuh detail ini, `index.blade.php` perlu dibaca ulang saat itu, jangan asumsi dari recon ini saja.

Area yang **RENDAH risiko** (aman untuk re-skin murni tanpa kejutan struktural): Dashboard Eksplorasi (poin 1, sudah paling matang, jadi referensi pola), Dashboard Eksekusi (poin 7, cuma ganti kelas warna alert), Referensi & Forum (poin 5-6, sudah lengkap dipetakan recon lama, tidak ada kejutan), Profil (poin 9, data untuk fitur baru sudah dikonfirmasi tersedia dan terisi).
