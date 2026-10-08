# WEBI-SPACE v1.0 — Semua yang Terjadi di Versi 1.0

**Sifat dokumen ini:** narasi koheren tentang apa yang dibangun di rilis pertama WEBI-SPACE, keputusan apa yang diambil, dan kenapa. Untuk info struktural timeless (tech stack, design system, deployment), lihat `docs/WEBI-SPACE.md`. Untuk histori v2.0 (redesign + modul baru), lihat `docs/WEBI-v2.0.md`.

Dikonsolidasikan dari 11 file sumber (lihat "Sumber Dokumen Ini" di akhir) — file aslinya dipindah ke `docs/v_2.0/archive/sumber-konsolidasi/`, tetap ada untuk cross-check detail lengkap.

---

## 1. Ringkasan Eksekutif v1.0

WEBI-SPACE dibangun untuk Divisi Web Development RIT — 12 anggota (9 eksplorasi, 3 eksekusi), PIC tunggal (Ayunda) sebagai admin. Riset awal (tahap 1.1) menemukan tiga pola yang membentuk seluruh keputusan desain sistem: **krisis arah** (6/12 anggota bingung mulai dari mana), **beban eksternal bervariasi** (6/12 punya benturan waktu signifikan), dan **dominan pemula rentan minder** (4 anggota kesulitan belajar mandiri, minat tertinggi UI/UX 50%, Backend cuma 25%). Periode kerja Juli-Agustus bersifat remote-only (KKN/semester break), jadi seluruh sistem harus berfungsi asinkron tanpa asumsi pertemuan fisik.

Tiga modul dibangun: **Eksplorasi** (LMS, 10 modul/67 unit, gamifikasi), **Eksekusi** (manajemen proyek, dari ide sampai selesai), **WEBI** (AI companion kontekstual). v1.0 mencakup fondasi ketiganya plus autentikasi/RBAC dasar dan sistem notifikasi terpadu — v2.0 (lihat `docs/WEBI-v2.0.md`) kemudian melakukan redesign visual besar-besaran dan menambah modul (Konten Dinamis, Manajemen Proyek lanjutan, Praktik, Mode Ganda) di atas fondasi ini.

---

## 2. Business Rules & RBAC

### 2.1 Tiga Role

Satu user satu role pada satu waktu: `exploration_member`, `execution_member`, `admin`.

**`exploration_member`** bisa akses seluruh Eksplorasi (kurikulum, evaluasi, progress tracker, gamifikasi, log aktivitas, learning resources, reminder personal), chat WEBI, forum Eksplorasi, notifikasi terkait, profil pribadi. **Tidak bisa** akses Eksekusi, admin panel, log WEBI anggota lain, data progres anggota lain.

**`execution_member`** bisa akses Project Ideas, proyek yang diikuti, task yang di-assign, Kanban, progress update, komentar, attachment, activity log, notifikasi Eksekusi, profil pribadi. **Tidak bisa** akses Eksplorasi, chat WEBI, forum Eksplorasi, admin panel, proyek yang tidak diikuti. Bisa input ide baru, ubah status task sendiri (todo/in_progress/in_review — TIDAK bisa langsung ke `done`, itu wewenang admin lewat review), tambah komentar/attachment/progress update, buat task baru **kalau diberi wewenang admin** (mekanisme wewenang ini sendiri tidak pernah dijelaskan lebih detail di dokumen manapun — jadi gap yang diwarisi ke v2.0, lihat `docs/WEBI-v2.0.md` Fase 7). Tidak bisa approve/reject ide, ubah status proyek, kelola anggota proyek.

**`admin`** akses penuh ke kedua modul + admin panel terpadu (progres/leaderboard Eksplorasi, ringkasan proyek/alert/feed Eksekusi) + log percakapan WEBI seluruh anggota (untuk monitoring, BUKAN sebagai user chat — admin tidak pernah jadi "murid" WEBI) + forum Eksplorasi (baca-balas) + seluruh parameter configurable sistem. Approve/reject ide (wajib isi alasan kalau reject), buat proyek langsung tanpa lewat ide, setup proyek penuh, buat/assign/review task, pause/resume/selesaikan/arsipkan proyek, ambil alih task siapa saja.

### 2.2 16 Business Rules Kunci

1. **Transparansi monitoring chat WEBI** — anggota HARUS diberi tahu eksplisit chat-nya bisa diakses admin, muncul di halaman Pendahuluan Kurikulum (onboarding) DAN notice persisten (tidak bisa di-dismiss) di antarmuka chat. Nadanya: monitoring untuk perbaikan kurikulum/dukungan belajar, bukan pengawasan punitif.
2. **Perlindungan integritas evaluasi** — WEBI tidak boleh memberi jawaban langsung evaluasi dalam kondisi apapun, dua lapis pertahanan (system prompt + validasi backend) wajib aktif di setiap request.
3. **Otoritas approve/reject Project Ideas** — hanya admin, tidak bisa approve ide sendiri.
4. **Rejection reason wajib** saat reject ide.
5. **Otoritas penandaan proyek `completed`** — hanya admin, syarat semua task `done`.
6. **Admin bisa buat proyek langsung** tanpa lewat Project Ideas.
7. **Poin diberikan saat evaluasi selesai** (submit), bukan saat membuka halaman unit.
8. **Leaderboard cuma visible di admin** — mencegah tekanan kompetitif antaranggota.
9. **WEBI read-only terhadap data progres LMS** — cuma menulis ke Conversation/Message/ProactiveLog/GuardrailFlag miliknya sendiri.
10. **Rate limiting WEBI**: 50 pesan/hari/user (configurable).
11. **Dataset WEBI cuma di-update admin**, bukan otomatis.
12. **Proyek `archived` read-only**, tidak ada transisi keluar dari situ.
13. **Satu user satu role** — kalau role berubah, data progres role lama tetap tersimpan tapi tidak lagi diakses.
14. **`on_hold` menekan notifikasi/alert** — deadline task yang lewat selama pause perlu di-update manual saat resume.
15. **ProgressUpdate dan ActivityLog immutable** (append-only, log audit).
16. **Forum diskusi hanya untuk Eksplorasi** — `exploration_member` + `admin` saja. **Koreksi (task 2.6, 2026-07-04):** versi lama PRD sempat salah tulis "Forum Eksekusi" sebagai hak akses `execution_member` (termasuk judul section-nya) — dikonfirmasi ini salah tulis konsolidasi, bukan keputusan 1.2 yang nyata; `struktur-eksekusi.md` sendiri tidak pernah membahas forum sama sekali. Dikoreksi, tidak ada forum Eksekusi v1.0. (Forum Proyek/General Eksekusi baru ada di v2.0 Fase 7 — konsep BERBEDA, dibangun kemudian, lihat `docs/WEBI-v2.0.md`.)

### 2.3 Batasan Sistem yang Disepakati

Tidak dibangun secara sadar: sertifikasi resmi, sistem pembayaran, aplikasi native mobile, multi-bahasa (fokus Bahasa Indonesia), integrasi LMS eksternal, model AI sendiri (pakai API pihak ketiga), Eksekusi sebagai tool manajemen proyek generik (khusus internal RIT), integrasi link repo GitHub otomatis, export portofolio ke PDF, dark/light mode, admin sebagai learner WEBI.

---

## 3. Modul Eksplorasi (LMS)

### 3.1 Struktur Kurikulum

**10 modul, 67 unit**, disusun berurutan dari fundamental sampai output akhir (portofolio live). Filosofi pembuka kurikulum: "Kurikulum ini dibuat untuk pemula total... Kamu tidak harus belajar cepat... Kamu tidak belajar sendirian." Setiap unit dirancang selesai dalam ~15 menit.

**Daftar modul** (dan level gamifikasi terkait — lihat §3.4):

| Modul | Judul | Level |
|---|---|---|
| 1 | Dunia Software Development | 1 (Pengenal) |
| 2 | Bagaimana Website Bekerja | 1 (Pengenal) |
| 3 | Peralatan dan Ekosistem Kerja Developer | 2 (Penyiap) |
| 4 | Version Control dengan Git | 3 (Kolaborator) |
| 5 | Kolaborasi dengan GitHub | 3 (Kolaborator) |
| 6 | Dasar-Dasar Frontend | 4 (Perakit) |
| 7 | Mengenal Backend dan Database | 4 (Perakit) |
| 8 | Kolaborasi Tim dan Metodologi Pengembangan Software | 5 (Praktisi) |
| 9 | Deployment, Membuat Website Bisa Diakses Dunia | 5 (Praktisi) |
| 10 | Proyek Akhir, Portofolio Pribadi | 6 (Lulusan Eksplorasi) |

Tiap unit: metadata (estimasi waktu, poin, tipe evaluasi, prasyarat), konten materi (dengan direktif penyajian `[SAJIKAN: jenis visual — deskripsi]` sebagai penanda teknis untuk frontend, bukan bagian materi yang dibaca anggota), evaluasi. Level modul: **checkpoint** (Checklist Akhir Modul + Intermezo + Form Tanggapan Modul) menandai modul benar-benar tuntas.

**Navigasi**: kurikulum divisualisasikan sebagai peta jalan terinspirasi roadmap.sh — tiap modul satu node besar (bisa diperluas ke unit di dalamnya), status visual beda (selesai/terkunci), 6 level ditandai area/warna berbeda. Halaman Pendahuluan Kurikulum tampil sekali di awal, menjelaskan filosofi kurikulum + keberadaan WEBI + gamifikasi + transparansi monitoring (business rule #1).

### 3.2 Sistem Evaluasi

Tipe evaluasi per unit: **kuis pilihan ganda/benar-salah** (auto-grading), **kuis mencocokkan** (auto-grading), **kuis mengurutkan** (auto-grading), **esai singkat** (auto-approve, poin langsung — "poin merepresentasikan progres keterlibatan, bukan nilai benar/salah"), **praktik/setup** (auto-approve, sama alasan). Unit tanpa evaluasi (rangkuman referensi) dapat poin saat ditandai selesai dibaca. Nada evaluasi: aman, tidak menghakimi, esai/praktik dinilai sebagai bukti keterlibatan bukan skor kompetitif.

### 3.3 Progress Tracker & Dashboard

Tracking 3 level: per unit (selesai/belum), per modul (persentase), keseluruhan (persentase). Dashboard personal: ringkasan progres, unit sedang dikerjakan, target berikutnya, level+poin.

### 3.4 Gamifikasi

**Poin**: unit konsep 10 poin, unit praktik 15 poin (diberikan saat evaluasi selesai, bukan saat buka halaman). Checkpoint: 25 poin bonus.

**6 Level**: 1 Pengenal (Modul 1-2, "memahami peta besar dunia software dan cara website bekerja") → 2 Penyiap (Modul 3, "menyiapkan peralatan kerja developer") → 3 Kolaborator (Modul 4-5, "version control dan kolaborasi Git/GitHub") → 4 Perakit (Modul 6-7, "membangun tampilan, memahami backend") → 5 Praktisi (Modul 8-9, "kerja tim, metodologi, merilis karya") → 6 Lulusan Eksplorasi (Modul 10, "portofolio live").

Visualisasi: bilah progres keseluruhan, indikator level+poin, penanda posisi di peta, perayaan visual saat checkpoint ("area baru terbuka"). **Leaderboard TIDAK ditampilkan ke anggota** (business rule #8) — cuma admin. Log aktivitas: feed apresiasi tiap unit/checkpoint selesai ("Selamat! Kamu mendapatkan 10 poin karena menuntaskan Unit 1.1..."), nada suportif, tidak membandingkan antaranggota.

### 3.5 Learning Resource Repository

Kumpulan link/referensi per modul (roadmap.sh, MDN Web Docs, git-scm.com, Pro Git Book, GitHub Docs, W3Schools, Atlassian Agile Coach, MySQL Tutorial, GitHub Pages Documentation, dll — tercantum di akhir tiap modul blueprint). Bisa diakses kapan saja, tidak perlu menunggu modul terkait.

### 3.6 Forum Diskusi Eksplorasi

Diorganisasi per modul/unit (keputusan baru PRD). Hanya `exploration_member` + `admin`. Anggota bisa buat thread, balas thread, pilih bertanya ke sesama anggota (peer) atau langsung ke PIC.

### 3.7 Notifikasi Eksplorasi

Trigger: checkpoint tercapai, naik level, unit baru tersedia (setelah selesai unit sebelumnya), pengingat evaluasi belum selesai, balasan thread forum yang diikuti.

### 3.8 Kondisi Kode v1.0 (baseline sebelum v2.0)

Recon sebelum redesign v2.0 menemukan beberapa hal penting sebagai baseline:

**Mekanisme kuis** — `submitQuiz()` SELALU membuat `EvaluationSubmission` dan memanggil `completeUnit()` TANPA syarat kebenaran (poin diberikan terlepas benar/salah, konsisten aturan "kasih poin terlepas dari benar/salah, cukup submit"). **Retry sudah TANPA batas** sejak v1.0 (tombol "Coba Lagi" tidak membatasi jumlah percobaan). Temuan kritis: `EvaluationSubmission.points_awarded` adalah kolom **archival murni, tidak pernah dibaca** sistem manapun — poin sungguhan masuk `total_points` lewat `completeUnit()` → `awardPoints()` yang selalu pakai `$unit->point_value` tetap, BUKAN dari kolom itu. Proteksi anti-dobel-poin murni dari idempotency `UserUnitProgress.status === 'completed'`. Tidak ada skor numerik tersimpan di mana pun — cuma `is_correct` boolean (semua-benar vs ada-yang-salah).

**Peta Kurikulum** — logic lock/unlock ada di `ProgressService` (`moduleStatus()`, `unitLocked()`), bukan di view. Modul terkunci kalau modul sebelumnya belum `moduleCompleted()` (semua unit completed DAN checkpoint selesai kalau ada). Unit terkunci kalau modul induk terkunci ATAU `prerequisite_unit_id`-nya belum selesai. **Tidak ada aturan berbasis level** — level cuma dipakai leaderboard/WEBI, bukan gate akses. Tampilan: vertikal, satu kolom, accordion per modul, signature "circuit path" (node solid/ring/kosong sesuai state) — HANYA di level modul, unit cuma daftar polos di dalam modul yang di-expand. Struktur data kurikulum sendiri LINEAR (satu prasyarat per unit, tidak bercabang) — jadi "hierarki jalur ala roadmap.sh" murni soal visual, bukan struktur data baru.

**Profil/Akun** — v1.0 **TIDAK PUNYA halaman profil self-service sama sekali**. Field `avatar_url`/`interest_field`/`membership_status` sudah lengkap di skema `users` sejak awal, tapi `interest_field` (walau sudah DIBACA WEBI untuk personalisasi) tidak pernah punya kanal PENULISAN — selalu kosong. `avatar_url` benar-benar dorman (tidak dibaca/ditulis/ditampilkan di mana pun). Registrasi self-service TIDAK ADA — user cuma dibuat lewat `php artisan app:create-admin` (interaktif, selalu admin) atau `Admin\Users\Create` (password digenerate acak, ditampilkan sekali ke admin). Ganti password self-service (dengan konfirmasi password lama) juga belum ada — yang ada cuma `resetPassword()` admin (generate ulang acak untuk user lain).

**WEBI** — chat CUMA bisa diakses sebagai halaman terpisah penuh (`/eksplorasi/webi`), tidak ada widget/panel embed di halaman lain. Conversation SUDAH berbasis sesi (window 30 menit) — satu user sudah otomatis punya banyak baris Conversation, tapi **tidak ada UI browse riwayat percakapan LAMA** dari sisi member (cuma sesi aktif). `Message.unit_context` sudah dipakai tapi merefleksikan "unit terakhir di progres global", bukan "unit yang sedang dilihat di halaman spesifik". `CurriculumContextBuilder` baca `Unit::content` sebagai string plain-text (belum ada sistem blok — itu baru v2.0).

**Dashboard tiga portal** — Admin: paling padat (tabel Progres Anggota + Leaderboard dari method sama `explorationLeaderboard()`, kartu ringkasan WEBI, ringkasan proyek, alert panel Eksekusi, feed progress update, ringkasan aktivitas anggota). Eksplorasi: 3 kartu statistik + leaderboard Top 5 (method BEDA, `memberLeaderboard()`, batasan privasi — cuma Top 5 + posisi diri, bukan seperti admin yang lihat semua) + unit sedang dikerjakan + log aktivitas. **Dashboard Eksekusi PALING minim** — bukan Livewire sama sekali (Blade biasa via closure route, `<x-layouts.app>` langsung), cuma 2 tombol link ("Project Ideas", "Proyek Saya"), nol data ditampilkan. Ini jadi salah satu prioritas terbesar redesign v2.0 (lihat `docs/WEBI-v2.0.md` §4.1).

---

## 4. Modul Eksekusi (Manajemen Proyek)

### 4.1 Konteks Tim

3 anggota eksekusi dengan kapasitas tidak merata: Ahmad Basir (paling konsisten, diandalkan task rutin), Azmi Renalji (termotivasi proyek riil, minat fullstack/PM), Riefki Nugraha (sangat sibuk, lebih cocok proyek kompetisi/hackathon daripada kerja rutin mingguan). Alur dirancang tetap berfungsi walau cuma 1 proyek aktif dengan 1-2 orang.

### 4.2 Alur Kerja (8 Tahap)

1. **Input Ide** — siapa saja (anggota+admin), status `draft`, tanpa validasi berat (barrier rendah).
2. **Seleksi & Promosi** — admin approve (Project baru otomatis dibuat, data di-copy dari ide, `promoted_to_project_id` terisi) atau reject (wajib `rejection_reason`). Jalur alternatif: admin buat proyek langsung tanpa lewat ide.
3. **Setup Proyek** — deskripsi detail, tujuan terukur, `project_type` (`internal`/`competition` — kompetisi py karakteristik beda: deadline fixed, timeline pendek intens), anggota tim, milestone (judul+deskripsi+target tanggal), status `planning`.
4. **Pemecahan jadi Task** — admin atau anggota berwewenang. Task wajib terhubung 1 milestone. Task pertama dibuat → status proyek otomatis `planning` → `active`. Kanban BUKAN entitas terpisah, cuma view layer baca field `status`.
5. **Eksekusi Task** — `todo` → `in_progress` (drag-and-drop atau tombol) → komentar/attachment kapan saja → `in_review` → admin review: lolos (`done`) atau revisi (komentar feedback + balik `in_progress`).
6. **Pelaporan Progres** — ProgressUpdate terpisah dari komentar (laporan kerja formal vs diskusi bebas) — supaya dashboard monitoring tidak noisy. Append-only.
7. **Monitoring & Intervensi Admin** — lihat §4.4.
8. **Penyelesaian** — syarat semua task `done` → admin tandai `completed` → bisa `archived` (read-only).

**Status proyek**: `planning` → `active` (otomatis) ⟷ `on_hold` (manual) → `completed` (manual) → `archived` (manual). **Status task**: `todo` → `in_progress` → `in_review` → `done` (bisa mundur `in_review` → `in_progress` untuk revisi).

### 4.3 Struktur Data Inti

12 entitas: User (referensi), ProjectIdea, Project, ProjectMember, Milestone, Task, TaskAssignment, ProgressUpdate (append-only), Comment (bisa diedit), Attachment (3 bentuk: file upload/link eksternal/teks biasa — keputusan tahap 1.2), ActivityLog (append-only immutable, log audit), Notification (v1.0: field `project_id`/`task_id` terpisah, digeneralisasi jadi `context_type`/`context_id` di arsitektur DB 1.7).

### 4.4 Monitoring Admin — 4 Komponen, 6 Alert, 6 Intervensi

**4 komponen dashboard**: (A) Ringkasan Proyek Aktif — progres keseluruhan+per-milestone, anggota aktif, sisa hari. (B) Alert Panel. (C) Feed Progress Update Terbaru (kronologis lintas proyek). (D) Ringkasan Aktivitas Anggota (task by status, update terakhir, task overdue).

**6 sinyal alert otomatis**: OVERDUE (deadline lewat, severity tinggi), DUE SOON (≤3 hari, threshold configurable, severity sedang), STALLED (`in_progress` tanpa aktivitas 7 hari, threshold configurable, severity sedang-tinggi), INACTIVE MEMBER (tanpa aktivitas 14 hari, threshold configurable, severity tinggi), MILESTONE AT RISK (target lewat + ada task belum done, severity tinggi), PROJECT IDLE (`active` tanpa activity log 14 hari, threshold configurable, severity tinggi).

**6 tindakan intervensi**: re-assign task, ubah deadline, tambah komentar intervensi, ubah prioritas, pause proyek (`on_hold`, notifikasi/alert di-suppress), ambil alih task.

---

## 5. Modul WEBI (AI Companion)

### 5.1 Hak Akses & Persona

Hanya `exploration_member` yang login. Admin akses lewat log monitoring, bukan chat. WEBI = teman belajar, bukan guru/penguji/customer service. Prinsip nada wajib: suportif-sabar, Bahasa Indonesia kasual-jelas, validasi sebelum penjelasan ("Wajar kok kalau bagian ini membingungkan..."), TIDAK PERNAH membandingkan antaranggota, frasa terlarang keras ("harusnya kamu sudah tahu", "ini kan gampang", "masa belum paham"), mendorong tanpa memaksa.

### 5.2 Lingkup Pengetahuan (3 Tier)

**Tier 1** (prioritas tertinggi): konten kurikulum Modul 1-10 — jawab berdasar konten kurikulum, bukan mengarang dari pengetahuan umum model. **Tier 2**: konteks ekosistem webdev terkait tapi belum dibahas eksplisit — jawab ringkas + arahkan ke unit relevan. **Tier 3**: info sistem WEBI-SPACE (navigasi, cara kerja poin/level/evaluasi).

**Domain ditolak** (dengan template respons): di luar webdev/WEBI-SPACE, permintaan pribadi-sensitif (curhat/keuangan/hubungan — diarahkan ke PIC/orang terpercaya), generate kode di luar kurikulum, konten berbahaya/tidak pantas/ilegal.

### 5.3 Perlindungan Integritas Evaluasi (Area Paling Kritis)

Strategi per tipe, dari risiko tinggi ke rendah: **Kuis pilihan/benar-salah** (risiko tinggi) — tolak jawaban langsung, jelaskan ulang konsep. **Kuis mencocokkan** (sedang-tinggi) — jelaskan fungsi tiap item terpisah. **Kuis mengurutkan** (sedang) — jelaskan logika urutan. **Esai** (sedang) — boleh jelaskan konsep, TIDAK boleh susunkan paragraf utuh. **Praktik/setup** (rendah-sedang) — boleh bantu troubleshoot error, TIDAK boleh beri output yang seharusnya dihasilkan user.

**Deteksi**: `[EVALUATION_BANK]` di-inject ke konteks, matching semantik (bukan cuma exact-match, user bisa parafrase), confidence rendah tetap jawab konsep + pengingat ringan. **Validasi backend (Layer 2)**: cek similarity output vs kunci jawaban (threshold 0.85 default), kalau di atas threshold → di-flag, retry dengan instruksi tambahan.

### 5.4 Personalisasi (3 Sumber Data)

**(a) Progres user** (read-only): completed_units, current_unit, current_level+nama, total_points, completed_checkpoints, unit_completion_timestamps, unit_open_count_without_completion. Efek: kedalaman jawaban menyesuaikan posisi user (Modul 2 dijelaskan dari nol, Modul 9 dijelaskan penuh dengan rujukan balik ke konsep sebelumnya).

**(b) Riwayat percakapan** (read-write, milik WEBI sendiri): tiap pesan simpan conversation_id/sender/content/timestamp/unit_context. Sesi baru setelah jeda >30 menit (configurable). Konteks model dapat N pesan terakhir (default 20). Topik sama >3x lintas sesi = sinyal stuck → trigger proaktif.

**(c) Minat bidang** (read-only): framing dan contoh disesuaikan (bukan materi inti berbeda) — minat UI/UX dapat penekanan visual, Backend dapat penekanan struktur data.

**Rekomendasi materi**: maks 1 per 3 pesan, harus kontekstual (bukan generik).

### 5.5 Sapaan Proaktif (5 Trigger)

1. **Pertama login** — prioritas tertinggi, selalu muncul.
2. **Stagnasi** — 5 hari tanpa unit baru (configurable), nada ringan tanpa tekanan.
3. **Stuck** — unit sama dibuka >3x tanpa evaluasi selesai, ATAU topik sama ditanya >3x lintas sesi.
4. **Naik level** — bypass semua cooldown (apresiasi pencapaian).
5. **Checkpoint selesai** — bypass semua cooldown.

**Anti-spam**: maks 1 sapaan/hari, cooldown 3 hari kalau tidak direspons, berhenti kirim nudge (Trigger 2&3) setelah 3x tidak direspons berturut-turut sampai user aktif lagi. Status disimpan: last_proactive_message_date, unanswered_proactive_count, last_trigger_type, onboarding_sent.

### 5.6 Guardrail Teknis — 2 Lapis

**Layer 1 (system prompt)**: bagian statis (persona, domain 3-tier+template tolak, perlindungan evaluasi per-tipe, instruksi rekomendasi maks 1/3 pesan, instruksi mode suara) + bagian dinamis per-request (`[USER_CONTEXT]`, `[CONVERSATION_HISTORY]`, `[RELEVANT_CURRICULUM_CONTENT]`, `[EVALUATION_BANK]`).

**Layer 2 (validasi backend)**: cek similarity jawaban vs kunci (>0.85 → flag+retry), cek domain (safety net kalau Layer 1 gagal), rate limiting 50 pesan/hari/user.

**Logging**: semua pesan tersimpan, `GuardrailFlag` per jenis (eval_detection/domain_rejection/output_validation) untuk monitoring admin (pola pertanyaan umum → perbaikan kurikulum; guardrail sering gagal → perbaikan prompt).

### 5.7 Interaksi Suara (STT/TTS)

Lapisan konversi di atas/bawah pipeline teks yang SAMA — bukan jalur terpisah, guardrail identik. Alur: mic → STT → transkrip ditampilkan untuk verifikasi → pipeline teks biasa → respons → TTS → audio + teks tetap tampil. Penyesuaian VOICE_MODE=true: respons lebih ringkas (maks 3-4 kalimat/poin), blok kode tidak dibacakan (cukup disebut namanya).

### 5.8 Sumber Dataset

Sumber utama: Blueprint Kurikulum (curriculum_content, evaluation_bank, supplementary_resources, gamification_rules, system_info). Prinsip: kurikulum = single source of truth, dataset WEBI = turunannya. Update di-trigger admin (bukan otomatis) saat kurikulum direvisi, lewat proses: revisi → ekspor ulang → validasi → deploy → archive versi lama.

### 5.9 Kondisi Kode v1.0 (baseline sebelum v2.0)

Chat sepenuhnya berfungsi end-to-end: `ChatService::sendMessage()` mengorkestrasi rate-limit check → ambil histori+unit aktif → simpan Message user → guardrail input (logging) → bangun prompt lengkap → panggil Gemini → guardrail output (retry/block) → simpan Message balasan → guardrail domain (logging). Layer service: `GeminiClient` (wrapper HTTP, timeout 20s, retry 2x), `SystemPromptBuilder`, `EvaluationBankBuilder`, `GuardrailService`, `PersonalizationContextBuilder`, `CurriculumContextBuilder`, `MessageRenderer`, `RecommendationParser`, `ProactiveService`. Voice mode: client-side murni via Web Speech API browser (feature-detect, fallback teks kalau tidak didukung), tidak ada layanan STT/TTS eksternal.

**Entry point tunggal**: menu sidebar "WEBI" → halaman chat penuh. Tidak ada widget embed di halaman lain — ini jadi salah satu target utama redesign v2.0 (WEBI kontekstual di halaman unit, lihat `docs/WEBI-v2.0.md` Fase 3).

---

## 6. Lampiran: Detail Teknis Tambahan

### 6.1 Parameter Configurable (Lampiran A PRD)

| Parameter | Default |
|---|---|
| Stagnasi progres (Trigger 2) | 5 hari |
| Stuck unit/topik (Trigger 3) | 3 kali |
| Cooldown sapaan proaktif | 3 hari |
| Batas tidak responsif | 3 kali berturut |
| Maks sapaan proaktif/hari | 1 |
| Rate limiting WEBI | 50 pesan/hari |
| Context window chat | 20 pesan terakhir |
| Sesi timeout | 30 menit |
| Similarity threshold guardrail | 0.85 |
| Frekuensi rekomendasi | 1 per 3 pesan |
| Task STALLED | 7 hari |
| INACTIVE MEMBER | 14 hari |
| DUE SOON | 3 hari |
| PROJECT IDLE | 14 hari |

### 6.2 Struktur Shell v1.0 (sebelum redesign navbar/popup v2.0)

Satu layout (`app.blade.php`) dipakai ketiga role. **Tidak ada sidebar** — satu `<header>` navigasi horizontal inline, markup nav ditulis langsung dengan rantai `@if/@elseif` berdasar role string (bukan partial/config array). Nol `wire:navigate` (full page reload untuk semua nav). Alpine cuma di 4 file (notification bell, WEBI voice, ideas index, peta kurikulum) — nav sendiri 100% statis. Tidak ada breadcrumb, tidak ada dropdown profil (cuma teks nama polos + form logout), tidak ada komponen toast/flash terpusat (duplikat manual di 5 file). Token desain (`@theme` di `app.css`): 3 font-variable, 4 color-variable — cocok penuh dengan `design-tokens.md`, dimuat lewat Bunny Fonts (self-hosted, privacy-friendly) via `laravel-vite-plugin/fonts`, bukan `<link>` Google Fonts manual seperti disarankan dokumen (pilihan font sama, cara load beda). Alpine dibawa bawaan Livewire v4, bukan dependency terpisah.

### 6.3 Skenario Penggunaan (Ringkasan)

PRD mendokumentasikan 10 skenario konkret per role sebagai acuan testing: 4 skenario Eksplorasi (Agitsa — onboarding; Syifa — stuck & WEBI; Falaah — checkpoint & naik level; Dipa — mode suara), 3 skenario Eksekusi (Azmi — usul ide sampai gabung proyek; Ahmad Basir — task todo sampai done termasuk revisi; Azmi — kirim progress update), 3 skenario Admin (memantau anggota tidak aktif; memantau proyek mandek+intervensi; review ide; monitor log WEBI). Detail lengkap tiap skenario ada di `docs/v_2.0/archive/sumber-konsolidasi/PRD.md` §7.

### 6.4 Skema Data Ringkas

27 entitas total di v1.0 (skema lengkap dengan field/tipe/relasi ada di `docs/arsitektur-database.md`, yang sudah diperbarui mencakup gabungan v1.0+v2.0): 2 lintas modul (User, Notification), 10 Eksekusi (ProjectIdea, Project, ProjectMember, Milestone, Task, TaskAssignment, ProgressUpdate, Comment, Attachment, ActivityLog), 11 Eksplorasi (Module, Unit, UnitEvaluation, UserUnitProgress, EvaluationSubmission, Checkpoint, CheckpointCompletion, ForumThread, ForumReply, LearningResource, UserExplorationProgress), 4 WEBI (Conversation, Message, ProactiveLog, GuardrailFlag).

---

## Sumber Dokumen Ini

Isi dokumen ini dikonsolidasikan dari 11 file sumber (dipindah ke `docs/v_2.0/archive/sumber-konsolidasi/`, tetap ada untuk cross-check):

1. `docs/v_2.0/archive/sumber-konsolidasi/PRD.md` — business rules, RBAC, user flow, skenario penggunaan lengkap.
2. `docs/v_2.0/archive/sumber-konsolidasi/kurikulum-eksplorasi.md` — blueprint konten kurikulum 10 modul, 67 unit (isi materi pembelajaran lengkap TIDAK direproduksi di sini — dokumen ini terlalu besar, ~173KB, untuk narasi; struktur/filosofi/daftar modul sudah cukup di §3.1, konten unit demi unit tetap ada utuh di file arsipnya).
3. `docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md` — spesifikasi fungsional WEBI lengkap.
4. `docs/v_2.0/archive/sumber-konsolidasi/struktur-eksekusi.md` — spesifikasi teknis modul Eksekusi.
5. `docs/v_2.0/archive/sumber-konsolidasi/RECON_v1_untuk_v2.md` — kondisi kode shell/layout sebelum v2.0.
6. `docs/v_2.0/archive/sumber-konsolidasi/RECON_kuis.md` — mekanisme poin kuis v1.0.
7. `docs/v_2.0/archive/sumber-konsolidasi/RECON_peta_kurikulum.md` — struktur Peta Kurikulum v1.0.
8. `docs/v_2.0/archive/sumber-konsolidasi/RECON_profil_akun.md` — kondisi Profil/Akun v1.0 (belum ada).
9. `docs/v_2.0/archive/sumber-konsolidasi/RECON_webi.md` — struktur chat WEBI v1.0.
10. `docs/v_2.0/archive/sumber-konsolidasi/RECON_dashboard.md` — kondisi 3 dashboard v1.0.
11. `docs/v_2.0/archive/RECON_ringkasan.md` — indeks pointer 6 recon di atas (file ini SENDIRI TIDAK dipindah, tetap jadi indeks navigasi di `docs/v_2.0/archive/`).
