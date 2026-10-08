# WEBI-SPACE v2.0 — Semua yang Terjadi di Versi 2.0

**Sifat dokumen ini:** narasi koheren PER FASE tentang apa yang dirancang dan apa yang benar-benar dibangun di v2.0 — bukan tempel-isi mentah dari tiap dokumen sumber. Untuk info struktural timeless, lihat `docs/WEBI-SPACE.md`. Untuk histori v1.0, lihat `docs/WEBI-v1.0.md`. Untuk log kerja mentah per-batch (paling detail, ground truth implementasi), `CLAUDE.md` tetap jadi rujukan utama dan TIDAK diubah oleh konsolidasi ini.

Dikonsolidasikan dari 21 file sumber (lihat "Sumber Dokumen Ini" di akhir) — file rancangan dipindah ke `docs/v_2.0/archive/sumber-konsolidasi/`; `CLAUDE.md` dibaca sebagai sumber narasi tapi TETAP di lokasi asalnya, tidak disentuh sama sekali.

---

## 1. Ringkasan Eksekutif v2.0

v2.0 dipicu evaluasi usability testing v1.0/v2.0-awal: poin minus paling menonjol adalah **"desain visual terlalu generik AI / terlihat vibe-coding"**. Seluruh keputusan visual di v2.0 bertujuan membalik itu jadi autentik dan khas WEBI-SPACE — filosofi **RETRO-TECH**, "Profesional = Playful" (lihat `docs/WEBI-SPACE.md` §3 untuk detail design system lengkap).

Cakupan v2.0 jauh lebih besar dari sekadar re-skin: 4 modul baru (Konten Dinamis, Praktik, Manajemen Proyek lanjutan, Mode Ganda) ditambahkan di atas fondasi v1.0, disusun jadi **10 Fase kerja** (`ROADMAP_EKSEKUSI_Final_v2.md`), dikerjakan berurutan dari fondasi visual sampai fitur paling berisiko (Mode Ganda) di akhir. Fase 1-8 **selesai total** per 2026-07-13 (status ini dikonfirmasi langsung dari `CLAUDE.md`, bukan diasumsikan). Fase 9 (Landing Page) dan Fase 10 (QA Menyeluruh) **belum dikerjakan**.

**Prinsip urutan kerja** (dari roadmap): fondasi visual sekali di depan (bukan dipoles belakangan), kebutuhan antar-fitur menentukan urutan (Kelola Kurikulum sebelum Praktik karena Praktik reuse editornya), fitur paling berisiko (Mode Ganda) dikerjakan mendekati akhir, checkpoint kecil per-sub-langkah bukan batch raksasa, backup database wajib sebelum fase yang menyentuh skema baru.

---

## 2. Rancangan & Keputusan Desain

Ringkasan APA YANG DIRENCANAKAN sebelum implementasi — untuk APA YANG SUNGGUHAN DIBANGUN (termasuk penyimpangan dari rencana ini kalau ada), lihat §3.

### 2.1 Modul 1 — Fondasi Lintas-Portal

Shell terpadu: navbar fixed + **popup menu** (pengganti sidebar, ala menu aplikasi Google — kotak-kotak menu muncul saat ikon navbar diklik) + breadcrumb di semua halaman + navigasi lintas portal untuk user akses ganda. Struktur navbar final: kiri logo, kanan (urut) Badge Status Mode → bell notifikasi → ikon grid popup menu → avatar/akun. Badge Status Mode disiapkan sebagai slot struktural sejak Fase 2 tapi baru AKTIF di Fase 8 (Mode Ganda). Halaman Login didesain ulang kreatif (elemen bergerak halus, maskot WEBI menyapa) tapi form tetap sederhana.

**Palet warna dan design system** lengkap ada di `docs/WEBI-SPACE.md` §3 (sudah digabung dari `design-tokens.md`+`design-tokens-v2.md`+`design-brief-v2.md`, tidak diulang di sini). Identitas grafis: maskot WEBI ("Boxy Blocky") dan avatar (Fox 5-tingkat Eksplorasi, 5-hewan-bebas Eksekusi), gaya Pixel Art/8-bit.

### 2.2 Modul 2 — Akun, Login, Mode Ganda

**Login** — redesign kreatif per §2.1. **Mode Ganda** (Modul 2 §2.2) — mekanisme paling kompleks di seluruh v2.0, satu akun (satu role asal, tidak pernah berubah) bisa dapat akses TAMBAHAN, asimetris disengaja:

- **Origin Eksekusi → Mode Eksplorasi**: BEBAS tanpa persetujuan, TAPI READ-ONLY (baca Peta Kurikulum/materi/Referensi, tidak bisa submit evaluasi, tidak dapat poin, tidak masuk leaderboard, sistem lock/unlock DILEWATI karena tidak ada progres jadi syarat). UI wajib menampilkan keterangan jelas saat mode ini aktif.
- **Origin Eksplorasi → Mode Eksekusi**: BUTUH PENGAJUAN + PERSETUJUAN ADMIN, lalu FUNGSIONAL PENUH (bisa di-assign task/proyek nyata). Begitu admin approve, link portal Eksekusi muncul di Profil. Dua riwayat data (Eksplorasi dan Eksekusi) berjalan PARALEL dan PERMANEN, tidak terhapus sekalipun sedang aktif di mode lain. Admin bisa mencabut akses kapan saja.
- **Mekanisme umum**: status mode aktif disimpan PERMANEN (bukan sesi), switching lewat menu Akun navbar. **Admin TIDAK ikut sistem Mode Ganda ini sama sekali.** Prinsip wajib: SATU titik pengecekan terpusat (Gate/middleware canonical), bukan tersebar.

Avatar Eksplorasi: 5 tingkat, threshold poin 50/100/300/500/1000+ (draft — dihitung ulang final di Fase 6). Avatar Eksekusi: 5 hewan pilihan bebas TANPA unlock (Eksekusi tidak punya sistem poin/level).

**Dua item `[BELUM DIPUTUSKAN]` di dokumen rancangan §2.4** — ditutup eksplisit di §5 dokumen ini.

### 2.3 Modul 3 — Eksplorasi

**Dashboard** — layout terkunci sesuai urutan kartu final, leaderboard sekarang DITAMPILKAN ke anggota (pembalikan keputusan v1.0), entry point WEBI kontekstual. **Peta Kurikulum** — visualisasi ulang bergaya roadmap.sh, mekanisme lock/unlock TIDAK berubah dari v1.0. **Halaman Materi + WEBI** — redesign total jadi split-screen (3 kolom: Daftar Isi Modul | Materi | WEBI), menggantikan slide-over lama. **Forum** — desain final, nol migrasi (module_id/unit_id sudah nullable), general topic dibuka. **Praktik** — struktur sudah final (Fase 2.1/rancangan), visual browsing ditunda. **Profil** — tambahan konteks Eksplorasi (galeri avatar, heatmap, dst — detail teknis di §3 batch Fase 3).

**Konten Dinamis** (Rancangan Arsitektur Konten Dinamis v2, `docs/v_2.0/archive/sumber-konsolidasi/`): materi tidak lagi teks polos, disusun dari blok terstruktur. Tabel `content_blocks` POLYMORPHIC sejak awal (`blockable_type`/`blockable_id`) — dipakai bersama Materi (`blockable_type='unit'`) DAN track map Praktik (`blockable_type='challenge_step'`), satu implementasi tabel+renderer. 8 tipe blok dirancang (Heading H1-H6, Teks, Gambar-link-eksternal, Callout, Kode, Video, List, Custom HTML) — **final jadi 9 tipe** setelah recon menemukan 28 kemunculan tabel markdown di 67 unit lama (Tabel dapat tipe blok sendiri), dan Heading dipersempit ke level 1-3 (bukan 1-6, materi unit tidak butuh sedalam itu). Custom HTML sebagai escape hatch, **diketatkan jadi WAJIB disanitasi saat render** (penyimpangan dari rancangan asli yang bilang "apa adanya" — keputusan keamanan tambahan). Migrasi 67 unit lama dikerjakan MANUAL (bukan auto-convert), sekaligus menutup 2 utang teknis v1.0 (upload Intermezo Modul 6, UI evaluasi campuran Unit 5.8).

**Kuis** — flow terpisah, tanpa timer/lock, skor TERBAIK dari seluruh percobaan dipakai untuk poin (mengganti mekanisme v1.0 "attempt pertama penuh, retry nol" — walau recon menemukan retry SUDAH tanpa batas sejak v1.0, yang benar-benar baru cuma "skor terbaik dipakai"). Data submission kuis lama dari masa usability testing v1.0 diperlakukan sebagai arsip riwayat, TIDAK dihitung ulang pakai logic baru.

**Praktik** (Rancangan Modul Praktik v2): challenge berjenjang low/mid/high, dipilih bebas TANPA gating dari progres Materi. Track map (langkah panduan) pakai sistem blok yang sama Konten Dinamis. Submission link/text/file, direview PIC atau `execution_member` yang di-assign PIC per-submission (BUKAN role "mentor" baru — `assigned_reviewer_id` nullable, RBAC ketat: reviewer cuma akses submission yang ditugaskan, prinsip sama seperti pelajaran 2.9 Kontrol Akses Attachment v1.0). Submit ulang tanpa batas dengan label versi, poin diminishing return (submission pertama penuh, berikutnya lebih kecil — bukan nol, karena tiap submit ulang adalah usaha nyata berdasar feedback).

**Sistem Poin Terpadu**: Materi+Kuis+Praktik masuk SATU akumulasi poin/level yang sama, dipakai leaderboard+dashboard+personalisasi WEBI. Threshold dihitung ulang setelah semua sumber poin terbangun (Fase 6).

### 2.4 Modul 4 — Eksekusi

(Rancangan Modul Manajemen Proyek v2, scope TERBESAR di v2.0, direkomendasikan dipecah banyak batch — tiga pilar):

**Manajemen Waktu**: **Kalender** dua kategori — Kegiatan (`#1C1515`, otomatis dari deadline Task/Milestone, TIDAK disimpan dobel) dan Acara (`#05D9E7`, manual, tabel baru `calendar_events`) — dua tingkat tampilan (tab per-proyek vs Kalender Personal agregat semua proyek + Acara umum). **Roadmap**: timeline milestone level tinggi. **Gantt**: bar per task + dependency (blocked-by/blocks, tabel baru `task_dependencies`, validasi cegah circular), molor = indikator visual saja, TIDAK auto-reschedule.

**Manajemen Tugas**: **Kanban** tetap bersih (cuma task besar, bukan subtask). **Subtask**: nested di panel detail task induk, data pakai tabel Task sama dengan `parent_task_id` self-referencing (BUKAN tabel terpisah), mini-list bukan mini-Kanban. **Detail task**: panel geser dari samping (slide-over), bukan halaman terpisah — task dibuka-tutup berulang dalam satu sesi kerja.

**Manajemen Sumber Daya**: **Forum** dua kategori (General lintas-proyek, Proyek per-thread) — dibedakan `project_id` nullable di tabel forum thread yang SUDAH ADA, bukan tabel baru terpisah. Akses cuma `execution_member`+`admin`, `exploration_member` dikecualikan total (RBAC timbal-balik dengan Forum Eksplorasi).

**Profil/Avatar Eksekusi**: mekanisme TERPISAH dari Eksplorasi — TANPA sistem poin/level, langsung pilih bebas dari galeri, tanpa syarat apa pun (keputusan sadar efisiensi, bukan kekurangan desain).

### 2.5 Modul 5 — Admin

Dashboard redesign (efisiensi scanning info). Project Ideas detail penuh via popup. Kanban drag-drop (upgrade dari tombol v1.0). **Antrian Review Praktik** — halaman baru. **Kelola Kurikulum** — "pusat perhatian" konsep OrderHero-style. **Kelola Praktik**. **Kelola Mode Ganda** — Antrian Permintaan (accept/reject) + Cabut Akses dari halaman Manajemen Akun, fitur SAMA SEKALI BARU tanpa padanan v1.0.

### 2.6 Batasan Sistem v2.0 (Disepakati Sadar TIDAK Dibangun)

WEBI di Portal Eksekusi (dicoret total). Auto-reschedule cascading Gantt. Kuis real-time serentak (Kahoot-style). Starter code Praktik. Role RBAC baru "mentor". Editor konten gaya Notion (form terstruktur saja). Upload file gambar native untuk materi (link eksternal saja). Environment staging terpisah (dikerjakan langsung di production yang sama — v1.0 belum rilis resmi). Sistem poin/level di Eksekusi.

**Ditunda, bukan dibatalkan**: Landing Page (Fase 9, akhir), Dokumen Maintenance, kriteria keberhasilan usability testing v2.0.

---

## 3. Histori Implementasi — Narasi per Fase

Sumber utama bagian ini: `CLAUDE.md` (ground truth hasil implementasi nyata, dibaca untuk menyusun narasi ini, TIDAK diubah). Untuk detail lengkap tiap batch kerja (test coverage, file yang disentuh, keputusan mikro), tetap rujuk `CLAUDE.md` langsung.

### Fase 1 — Persiapan
Verifikasi murni: kondisi `app.css` bersih, aset visual final (maskot, avatar) siap, tidak ada pekerjaan menggantung. Tidak ada prompt implementasi.

### Fase 2 — Fondasi Visual & Shell (Modul 1 Penuh)
Design token v2 dikunci konkret (nilai hex, shadow bertingkat, radius — lihat `docs/WEBI-SPACE.md` §3), diterapkan sebagai `@theme` Tailwind v4 di `resources/css/app.css`. Maskot WEBI dan avatar (Fox 5-tingkat + 5 hewan Eksekusi) jadi komponen Blade. Halaman pilih avatar Eksekusi dibangun. Shell dirombak total: sidebar diganti navbar+popup menu (`config/navigation.php` dipertahankan sebagai sumber data, cuma cara tampil yang berubah). Slot Badge Status Mode disiapkan struktural (belum aktif — nanti Fase 8). Breadcrumb dipasang di semua halaman. Halaman Login didesain ulang.

### Fase 3 — Re-skin Eksplorasi & Eksekusi + Fitur Kecil (2026-07-11)
Batch terbesar dari sisi jumlah halaman. Dashboard Eksplorasi, Peta Kurikulum (warna saja, struktur tidak berubah, tambah ringkasan Level+Poin), Halaman Materi+WEBI (redesign TOTAL dari slide-over jadi 3 kolom: Daftar Isi Modul | Materi | WEBI kontekstual — **penyimpangan sadar dari PRD 5.1**: notice transparansi monitoring chat SENGAJA disembunyikan di panel kontekstual ini, cuma tampil di halaman chat penuh, dikonfirmasi eksplisit ke Aye), WEBI Chat halaman penuh (2 kolom, ganti nama+ikon maskot resmi). **Referensi**: migrasi kecil (`created_by`+`description` ditambah, `module_id` dilebarkan nullable untuk referensi "General"), form submission member baru dibangun dari nol. **Forum**: thread general/bebas-modul dibuka, nol migrasi. Dashboard Eksekusi dibangun dari nyaris-kosong (v1.0 cuma 2 tombol) jadi dashboard sungguhan dengan data dummy (slot "Antrian Review Praktik"/"Ringkasan Kalender" disiapkan kosong untuk Fase 5/7). Project Ideas: cuma Judul wajib, halaman dipecah "Menunggu Keputusan"+"Riwayat". Profil: ringkasan poin/level/avatar, galeri avatar, kalender aktivitas heatmap (6 bulan, diperluas dari draft 4 bulan), kontribusi per-proyek. Kanban: warna saja, drag-drop TIDAK disentuh (dibuktikan diff baris-per-baris).

### Fase 4 — Admin Kelola Kurikulum (2026-07-12)
Nol migrasi (semua tabel sudah ada dari fondasi + Fase 3's `content_blocks`). Kelola Modul & Unit (CRUD+urutan+prasyarat+poin+tipe evaluasi). **Editor Blok Konten**: form terstruktur 9 tipe (bukan drag-drop, tombol naik/turun — lebih murah, cukup untuk kebutuhan susun-ulang yang tidak sering berubah drastis), preview sebelum publish. **Kelola Evaluasi**: form beda per tipe soal, `correct_answer` matching DITURUNKAN OTOMATIS dari pasangan (bukan input terpisah). **Bug data lama ditemukan+diperbaiki (dikonfirmasi Aye)**: SEMUA 3 baris `ordering` dari seeder punya `options === correct_answer` (bisa "benar" tanpa menggeser apa pun) — diperbaiki via form admin sungguhan (`shuffleDisplayOrder()`), representasi indeks memisahkan "urutan kunci" dari "urutan tampil" supaya tidak pernah basi. **Temuan keamanan kritis**: `EvaluationBankBuilder::toPromptText()` ternyata mengirim `correct_answer` LITERAL ke prompt Gemini sejak 2.5 — diperbaiki, kunci jawaban sekarang TIDAK PERNAH dikirim ke AI, cuma dipakai validasi server-side setelah fakta. Migrasi 67 unit ke sistem blok DIMULAI (belum 100% selesai — status persis dicek ulang di Fase 5, ditemukan NOL unit bermigrasi saat itu, ditambal defensif di `CurriculumContextBuilder`).

### Fase 5 — Modul Praktik (Praktik 1-3, 2026-07-12)
Backup database dulu. Skema: `challenges`, `challenge_steps`, `challenge_submissions`, `challenge_attachments`. Track map reuse editor blok Fase 4. Anggota jelajah+submit (link/text/file — upload file BARU aktif di Praktik 2, sebelumnya sengaja ditunda karena infrastruktur upload cuma ada untuk Attachment Task). Admin antrian review + assign per-submission. **Halaman review DIPAKAI BERSAMA admin dan `execution_member`** (bukan dua halaman terpisah) — RBAC: admin selalu lolos (self-review), `execution_member` cuma lolos kalau `assigned_reviewer_id` PERSIS dirinya. **Bug presisi floating-point ditemukan+diperbaiki**: formula diminishing-return `0.7 ** (attempt-1)` sempat salah bulat (48 alih-alih 49) karena representasi biner `0.7` tidak eksak — ditambal epsilon `+1e-9` sebelum `floor()`. Poin HANYA diberikan di titik approve (`PointService::award()`), TIDAK PERNAH di titik submit. **Recon follow-up ditemukan+ditambal saat batch ini**: `CurriculumContextBuilder` masih baca `units.content` lama eksklusif, padahal migrasi Editor Blok Konten sudah mulai — kalau tidak ditambal, unit migrasi pertama akan bocor konteks salah/kosong ke WEBI (dicek live: 0 unit termigrasi saat itu, bukan bug aktif, tapi laten). Diperbaiki: cek `content_blocks` dulu, fallback ke `content` lama.

### Fase 6 — Hitung Ulang Threshold Poin (2026-07-12)
**Temuan & dibersihkan (dikonfirmasi Aye sebelum eksekusi)**: database dev punya 19 baris Modul sampah/duplikat dari sesi debugging sebelumnya — dibersihkan (dicek dulu 0 referensi progres anggota nyata, aman). Total poin maksimal realistis dihitung ULANG dari DB bersih: Materi 770 + Checkpoint 225 + Praktik 25 (estimasi, cuma 1 challenge published saat itu) = **1020**. Threshold 6 level baru: 0/204/306/510/714/918 — proporsional terhadap jumlah modul per level (BUKAN pembagian rata, beda metodologi dari draft lama karena Praktik tidak terikat modul/level tertentu). Command `exploration:recalculate-levels` (idempotent, bisa dijalankan ulang kalau threshold disesuaikan lagi) dieksekusi — dampak ke 26 `UserExplorationProgress` existing: **NOL** (semua akun dev jauh di bawah threshold level 2 manapun).

### Fase 7 — Modul Manajemen Proyek (7 Batch, 2026-07-12 s.d. 2026-07-13)
Batch PALING BESAR. **1a**: halaman proyek jadi tab (Kanban default + placeholder lain), RBAC "member proyek" dikonsolidasi jadi middleware `project.member` (ditemukan 4 titik duplikasi kode RBAC lama, bukan 3 seperti estimasi recon). **1b**: Detail Task jadi panel slide-over (komponen lama dipakai APA ADANYA sebagai nested component, bukan dibangun ulang) — **bug framework ditemukan**: helper `redirect()` melempar TypeError di closure route polos (beda dari Livewire component) karena Livewire membajak resolusi container `'redirect'`, diakali `new RedirectResponse(...)` langsung. **2a**: Subtask (`parent_task_id`, self-referencing) — **audit 18 titik query lama** (bukan 12 seperti estimasi recon), keputusan cakupan subtask DIKONFIRMASI via `AskUserQuestion` (ikut hitungan alert+personal, TAPI dikecualikan dari Kanban/rollup progres/ringkasan admin). **2b**: Kalender (Kegiatan derived on-the-fly vs Acara manual `calendar_events`) — enum `type` Acara SENGAJA MENYIMPANG dari daftar yang diajukan (`meeting`/`deadline`/`personal`/`milestone`) setelah dicek dokumen: `deadline`/`milestone` itu Kegiatan (tidak pernah jadi baris tabel ini), diganti `meeting`/`competition`/`other`. **3a**: Gantt+Task Dependency — **pencegahan siklik di LEVEL APLIKASI** (DFS terhadap graf yang sudah ada), bukan constraint DB, karena siklik tidak bisa dicegah CHECK/FK sederhana; render murni CSS+SVG inline (nol library chart, konsisten preferensi app ini). "Tanggal mulai" task pakai `created_at` (bukan kolom baru) karena `start_date` tidak ada di skema. **3b**: Roadmap — status milestone (selesai/berjalan/belum mulai) dihitung on-the-fly dari task besar (subtask dikecualikan, audit Batch 2a konsisten), orientasi HORIZONTAL (keputusan sendiri, timeline lebih cocok horizontal daripada vertikal ala Peta Kurikulum). **4 (TERAKHIR)**: Forum Proyek — **migrasi ke tabel LAMA `forum_threads`** dikonfirmasi eksplisit ke Aye dulu (kelas risiko beda dari CREATE TABLE baru) — `project_id` nullable ditambah, `target` dilebarkan nullable. Logic backend digeneralisasi ke `App\Services\Forum\ForumService` (namespace BARU, dipakai KEDUA portal — kasus pertama logic yang genuinely lintas-portal). **Bug isolasi ditemukan lewat test sendiri**: Forum Eksplorasi Index awalnya query TANPA filter, sempat bikin thread Forum Proyek ikut muncul+bikin CRASH di sana — ditambal `whereNull('project_id')`. **Forum General Eksekusi TIDAK dibangun di batch ini** (di luar scope literal, project_id null tapi konteks Eksekusi tidak bisa dibedakan dari General Eksplorasi tanpa kolom pembeda — gap ini RESOLVED belakangan, lihat §3 penutup).

### Fase 8 — Sistem Mode Ganda (7 Batch + 2 perbaikan pasca-batch, 2026-07-13)
Fitur paling berisiko keamanan di seluruh aplikasi — recon read-only dulu (`RECON_fase8_mode_ganda.md`) sebelum implementasi apa pun. **Batch 1**: skema murni (`users.dual_mode_status`/`active_mode`, tabel `dual_mode_requests`) — NOL logic Gate, `active_mode` nullable-default-null dipilih (pola sama `Task.parent_task_id`/`ForumThread.project_id`: null=default, terisi=pengecualian). **Batch 2**: Gate terpusat `User::canAccessExecution()`, middleware `EnsureCanAccessMode` (alias `mode:`), **8 route/grup (18 URL) di-swap** dari `role:` ke `mode:` — 2 gap DITEMUKAN saat audit (reviewer Praktik keyed ke role literal, Kontribusi Proyek Profil keyed ke role literal), RESOLVED di Batch 6. **Batch 3**: arah sebaliknya, `canAccessExploration()`/`isReadOnlyExploration()`, 6 rute dibuka baca-saja untuk `execution_member`, 7 titik guard tulis diaudit — **2 bug ditemukan SAAT implementasi** (urutan route wildcard vs literal `/create`, `recordUnitOpened()` yang lolos dari guard awal — kalau tidak ditambal, sekadar MELIHAT unit sudah bikin baris progress untuk user read-only). Sistem lock/unlock DILEWATI TOTAL (bukan sebagian) untuk mode baca — kalau cuma dilewati sebagian, HAMPIR SEMUA unit akan tampak terkunci (progres kosong = prasyarat modul manapun belum terpenuhi), kebalikan dari yang diinginkan. **Batch 4**: alur pengajuan (`DualModeService::submitRequest()`), notifikasi broadcast admin. **Batch 5**: panel admin approve/reject + cabut akses. **Batch 6**: UI switching (badge navbar, dropdown menu akun, `SwitchModeController`) + 2 gap Batch 2 ditutup. **Batch 7 (TERAKHIR)**: sweep regresi penuh — 1 pertanyaan kritis dikonfirmasi eksplisit ke Aye via `AskUserQuestion` (bukan diasumsikan): apakah `exploration_member` approved+`active_mode=execution` kehilangan akses tulis Eksplorasi selagi di mode Eksekusi? **Jawaban: TIDAK — akses ganda SIMULTAN**, `active_mode` cuma menentukan badge/tujuan navigasi default, tidak pernah mengunci portal manapun (ini ternyata SUDAH jadi perilaku kode sejak Batch 3, nol perubahan kode dibutuhkan, cuma dikunci formal lewat test).

**2 perbaikan pasca-Fase-8** (task terpisah, sama hari): (1) User yang di-revoke admin awalnya TIDAK BISA mengajukan ulang (`dual_mode_status='revoked'` jalan buntu) — diperbaiki: `revoke()` sekarang berperilaku seperti `reject()` (kembali ke `none`), riwayat pencabutan tetap permanen sebagai baris `DualModeRequest` baru terpisah. (2) **Forum General Eksekusi** (utang Fase 7) — kolom `forum_threads.portal` (enum `exploration`/`execution`, NOT NULL, backfill 3-langkah) menyelesaikan ambiguitas `project_id IS NULL` yang dulu SELALU berarti Eksplorasi, sekarang bisa juga berarti Forum General Eksekusi lintas-proyek. Halaman baru dibangun, RBAC `mode:execution,admin`.

---

## 4. Fase 9-10 (Belum Dikerjakan)

**Fase 9 — Landing Page.** Dikerjakan paling akhir sesuai kesepakatan dari awal. Detail belum dibahas di rancangan manapun — akan dibahas terpisah saat fase ini tiba waktunya.

**Fase 10 — QA Menyeluruh.** Rencana: jalankan seluruh test, review manual 5-sisi (Fungsi/Data/Visual/Bahasa/Mobile) tiap halaman, cek ulang seluruh sistem akses (khusus area Fase 8), cek performa halaman berat, perbarui catatan status bahwa 2 utang teknis lama (upload materi, evaluasi campuran) sudah tertutup lewat migrasi Fase 4.

---

## 5. Keputusan Terbuka yang Ditutup di Dokumen Ini

`RANCANGAN_FINAL_WEBI-SPACE_v2.md` §2.4 (Sistem Avatar) mencatat dua item `[BELUM DIPUTUSKAN — perlu keputusan Aye]` yang tidak pernah ditutup eksplisit di dokumen aslinya. Diverifikasi langsung dari kode (bukan diasumsikan) untuk menutup keduanya di sini:

**(a) Apakah tingkatan avatar Fox independen dari sistem level, atau disatukan?**

**DITUTUP: independen.** `config/exploration.php` punya dua config key TERPISAH: `level_thresholds` (6 level: 0/204/306/510/714/918, dari Fase 6) dan `fox_avatar_tiers` (5 tingkat: 0/51/101/301/501, TIDAK disentuh Fase 6). Komentar eksplisit di kode: *"INDEPEN dari `level_thresholds`/`level_names` di atas — ini sistem terpisah (RANCANGAN_FINAL Modul 2 §2.4), threshold sendiri, JANGAN disatukan meski keduanya sama-sama dibaca dari total_points"* — dan dikonfirmasi lewat catatan *"Dikonfirmasi Aye (susulan Langkah 4)"*. Jadi ini bukan cuma "belum sempat disatukan", tapi keputusan sadar yang sudah dikonfirmasi Aye: 5 tingkat Fox tetap terpisah dari 6 level tampilan, sama-sama dihitung dari `total_points` yang sama tapi dengan ambang masing-masing.

**(b) Jenis animasi avatar: sprite bergerak multi-frame, atau statis + micro-animation CSS?**

**DITUTUP: statis, tanpa animasi sama sekali** (lebih ringan dari kedua opsi yang diajukan dokumen — bahkan opsi "statis + micro-animation CSS" yang direkomendasikan dokumen pun tidak diterapkan). Dicek `resources/views/components/avatar/fox.blade.php`: nol keyword `animation`/`animate`/`sprite`/`keyframes` di seluruh file, komponen murni presentational membaca `tingkat` (integer) dan merender siluet SVG statis per tingkat, referensi ke gambar PNG statis (`Eksplor_Level 1-5.png`). Konsisten dengan prinsip performa cepat (1.8) yang jadi alasan rekomendasi dokumen sendiri.

---

## 6. Lampiran: Detail Teknis Tambahan

### 6.1 Struktur `content_blocks` — 9 Tipe Final

| Type | Struktur `content` (json) |
|---|---|
| `heading` | `{level: 1-3, text}` — di-clamp saat render kalau di luar rentang |
| `text` | `{markdown}` — disimpan sumber markdown, dirender aman saat tampil |
| `image` | `{url, alt, caption?}` — URL eksternal saja |
| `callout` | `{variant: info/tip/warning, title?, body}` |
| `code` | `{language?, code}` — selalu escape penuh, tidak lewat parser markdown |
| `video` | `{url, caption?}` — provider (YouTube/Vimeo) dideteksi otomatis dari URL saat render, bukan field terpisah yang bisa "bohong" |
| `list` | `{style: ordered/unordered, items[]}` |
| `table` | `{headers[], rows[][]}` — sel TIDAK mendukung markdown, teks polos di-escape |
| `custom_html` | `{html}` — WAJIB disanitasi (allowlist custom via `DOMDocument`, bukan library pihak ketiga — hapus tag berbahaya `script`/`iframe`/dll + semua atribut `on*` + skema URL berbahaya) |

Sanitasi markdown pakai `SafeMarkdown::toHtml()` — pembungkus `Str::markdown()` dengan opsi PERSIS sama seperti `MessageRenderer::toSafeHtml()` WEBI (`html_input: escape`, `allow_unsafe_links: false`) — satu standar keamanan konsisten untuk "teks dari markdown" di seluruh aplikasi, tidak peduli siapa penulisnya.

### 6.2 Poin Diminishing Return Praktik

Formula: `points_reward * (0.7 ** (attempt_number - 1))`, dibulatkan ke bawah dengan epsilon `+1e-9` (jaga-jaga presisi floating-point biner). Attempt 1 = 100%, attempt 2 = 70%, attempt 3 = 49%, dst. Diisi HANYA saat status jadi `disetujui`, tidak pernah di titik submit.

### 6.3 Ringkasan Total Poin Maksimal (Fase 6)

Materi 770 + Checkpoint 225 (25 × 9 modul yang punya checkpoint, Modul 4 tidak punya) + Praktik ~25 (estimasi dasar, akan naik begitu ada Challenge asli lebih banyak) = **1020**. Threshold 6 level: 0 / 204 / 306 / 510 / 714 / 918 — proporsional jumlah modul per level (2/1/2/2/2/1 dari 10 modul), bukan pembagian rata.

### 6.4 Daftar Recon Gelombang 2 (bekal per-fase, ringkasan)

10 recon dibuat sebagai bekal implementasi tiap fase — detail lengkap masing-masing ada di file arsipnya:

- `RECON_shell_navbar_sidebar.md` — bekal Fase 2 (navbar+popup menu).
- `RECON_pola_admin_panel.md` — bekal Fase 4 (Editor Blok Konten, pola admin panel existing).
- `RECON_evaluasi_kuis.md` — bekal Fase 4 (struktur soal/kunci jawaban `unit_evaluations`).
- `RECON_referensi.md` — bekal Fase 3 (skema Referensi, submission member).
- `RECON_forum.md` — bekal Fase 3 (skema Forum, general topic).
- `RECON_sistem_poin.md` — bekal Fase 5 (pemetaan sistem poin v1.0, sebelum disatukan).
- `RECON_fase3_menyeluruh.md` — recon menyeluruh sebelum Fase 3 (Materi+WEBI restrukturisasi).
- `RECON_fase5_praktik.md` — recon sebelum Fase 5 (rancang skema Praktik).
- `RECON_fase7_manajemen_proyek.md` — recon sebelum Fase 7 (audit 18 titik subtask, dll).
- `RECON_fase8_mode_ganda.md` — recon paling berisiko keamanan, sebelum Fase 8.

### 6.5 Roadmap Lama (Superseded)

`Roadmap_Menyeluruh_WEBI-SPACE_v2.md` (penomoran 2.1-2.3, lebih tua) digantikan `ROADMAP_EKSEKUSI_Final_v2.md` (penomoran Fase 1-10, dipakai sebagai basis narasi §3 di atas) — dokumen lama ini dicatat sebagai riwayat perencanaan awal, isinya sudah tercakup dalam bentuk lebih final di roadmap baru.

---

## Sumber Dokumen Ini

Isi dokumen ini dikonsolidasikan dari 21 file sumber:

**Rancangan (dipindah ke `docs/v_2.0/archive/sumber-konsolidasi/`):**
1. `docs/v_2.0/archive/sumber-konsolidasi/RANCANGAN_FINAL_WEBI-SPACE_v2.md` — living document master, 5 modul.
2. `docs/v_2.0/archive/sumber-konsolidasi/ROADMAP_EKSEKUSI_Final_v2.md` — urutan kerja Fase 1-10.
3. `docs/v_2.0/archive/sumber-konsolidasi/Roadmap_Menyeluruh_WEBI-SPACE_v2.md` — roadmap lebih tua, superseded.
4. `docs/v_2.0/archive/sumber-konsolidasi/2.1.2_Fiksasi_Fitur_dan_Cakupan_v2.md` — acuan tunggal fitur v2.0.
5. `docs/v_2.0/archive/sumber-konsolidasi/2.1.3_Definisi_Konsep_Baru.md` — indeks 3 rancangan.
6. `docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Arsitektur_Konten_Dinamis_v2.md`
7. `docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Modul_Manajemen_Proyek_v2.md`
8. `docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Modul_Praktik_v2.md`
9. `docs/v_2.0/archive/sumber-konsolidasi/content-blocks-spec.md` — spek teknis 9 tipe blok.
10. `docs/v_2.0/archive/sumber-konsolidasi/2.1.1_Analisis_Triase_Catatan_Penilaian.md` — triase catatan penilaian v1.0, basis 2.1.2.

**10 Recon gelombang 2 (dipindah ke `docs/v_2.0/archive/sumber-konsolidasi/`):**
11. `RECON_shell_navbar_sidebar.md`
12. `RECON_pola_admin_panel.md`
13. `RECON_evaluasi_kuis.md`
14. `RECON_referensi.md`
15. `RECON_forum.md`
16. `RECON_sistem_poin.md`
17. `RECON_fase3_menyeluruh.md`
18. `RECON_fase5_praktik.md`
19. `RECON_fase7_manajemen_proyek.md`
20. `RECON_fase8_mode_ganda.md`

**Log implementasi (TIDAK dipindah, dibaca sebagai sumber narasi):**
21. `CLAUDE.md` — ground truth seluruh histori implementasi Fase 3-8, section "Known gaps / backlog" dan "Progress Fase" per batch. Tetap di root project, tidak diubah oleh konsolidasi ini.
