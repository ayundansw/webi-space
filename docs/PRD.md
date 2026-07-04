# PRD (Product Requirements Document) WEBI-SPACE

**Tahapan:** 1.6 dari Fase 1: Inisiasi dan Setup
**Status:** Final
**Disusun oleh:** Celo (partner dan coach Ayunda, PIC Divisi Web Development RIT)
**Sifat dokumen:** Acuan tunggal selama development. Seluruh keputusan fitur, alur pengguna, aturan bisnis, dan batasan sistem yang sudah diambil di tahap 1.2 sampai 1.5 dikonsolidasikan di sini supaya tidak ada keputusan yang berubah-ubah di tengah jalan tanpa dasar.

---

## Dokumen Rujukan

Dokumen-dokumen berikut menjadi sumber seluruh isi PRD ini. Tidak ada konten yang dikarang di luar cakupan dokumen-dokumen ini, kecuali yang secara eksplisit ditandai sebagai keputusan baru di PRD.

1. Fiksasi Fitur dan Cakupan Sistem (tahap 1.2)
2. Blueprint Konten Kurikulum Eksplorasi WEBI-SPACE, Bagian 1 dan 2 (tahap 1.3)
3. Perancangan Struktur Sistem Eksekusi WEBI-SPACE (tahap 1.4)
4. Spesifikasi Fungsional WEBI (tahap 1.5)
5. Laporan Hasil Riset Anggota Divisi Web Development RIT (tahap 1.1)

---

## Cara Membaca Dokumen Ini

Dokumen ini terdiri dari tujuh bagian utama. Bagian 1 dan 2 memberi konteks. Bagian 3 adalah inti teknis: daftar lengkap requirement per modul yang siap jadi acuan development. Bagian 4 menggambarkan bagaimana user mengalami sistem dari awal sampai akhir. Bagian 5 berisi aturan bisnis yang harus ditegakkan sistem. Bagian 6 mendaftarkan apa yang secara sadar tidak dibangun. Bagian 7 menyajikan skenario konkret per role untuk acuan testing.

Di akhir dokumen ada Lampiran A yang mengumpulkan seluruh parameter configurable (threshold, batas, angka default) supaya developer tidak perlu memburunya di seluruh dokumen.

---

## DAFTAR ISI

1. Ringkasan Produk
2. User Roles dan Hak Akses
3. Konsolidasi Fitur dan Requirement per Modul
4. User Flow per Modul
5. Business Rules
6. Batasan Sistem
7. Skenario Penggunaan (Use Case) per Role
8. Lampiran A: Parameter Configurable

---

## 1. RINGKASAN PRODUK

### 1.1 Apa Itu WEBI-SPACE

WEBI-SPACE adalah web app ekosistem internal Divisi Web Development RIT (Republic of Information Technology), sebuah UKM kampus berbasis teknologi. Platform ini terdiri dari tiga modul utama:

**Modul Eksplorasi (LMS)** untuk anggota yang sedang belajar fundamental web development melalui kurikulum terstruktur 10 modul dan 67 unit, dilengkapi sistem gamifikasi.

**Modul Eksekusi (Manajemen Proyek)** untuk anggota yang sudah siap mengerjakan proyek tim nyata, dilengkapi alur kerja dari ide sampai proyek selesai.

**Modul WEBI (AI Companion)** berupa teman belajar berbasis AI yang terintegrasi di LMS, khusus membantu anggota eksplorasi memahami materi kurikulum lewat chat teks maupun suara.

### 1.2 Untuk Siapa

Pengguna utama adalah 12 anggota Divisi Web Development RIT yang terbagi dalam dua segmen: 9 anggota eksplorasi (belajar fundamental) dan 3 anggota eksekusi (mengerjakan proyek). PIC (Ayunda) berperan sebagai admin tunggal.

### 1.3 Karakteristik User (dari Riset 1.1)

Tiga pola utama yang memengaruhi desain sistem:

**Krisis arah, bukan krisis minat.** 6 dari 12 anggota menyebut "bingung mulai dari mana" sebagai kendala utama. Mereka tidak kekurangan keinginan belajar, tapi kekurangan jalur yang jelas. Implikasi: kurikulum bertahap dan navigasi yang intuitif adalah kebutuhan kritis.

**Beban eksternal nyata dan bervariasi.** 6 dari 12 anggota menghadapi benturan waktu signifikan (kerja, organisasi lain, tanggung jawab keluarga). Implikasi: unit belajar harus bisa dicicil dalam waktu singkat (~15 menit), dan sistem tidak boleh menambah tekanan lewat nada atau mekanisme yang menghakimi.

**Dominan pemula dan rentan minder.** 4 anggota mengaku kesulitan memahami materi secara mandiri, beberapa mudah minder dan takut membebani. Minat tertinggi ada di UI/UX (50%) dan Frontend (41,7%), sementara Backend hanya 25%. Implikasi: seluruh nada sistem, WEBI, dan evaluasi harus suportif, tidak kompetitif antaranggota, dan evaluasi bersifat bukti keterlibatan.

### 1.4 Konteks Operasional

Periode Juli sampai Agustus bersifat remote-only (KKN/semester break), sehingga akses digital asinkron menjadi satu-satunya cara anggota berinteraksi dengan sistem. Kolaborasi tatap muka baru bisa dilanjutkan di akhir September. Seluruh mekanisme sistem harus bisa berjalan tanpa asumsi bahwa anggota berkumpul secara fisik.

---

## 2. USER ROLES DAN HAK AKSES

Sistem menerapkan tiga role. Satu user hanya bisa memiliki satu role pada satu waktu.

### 2.1 Anggota Eksplorasi (`exploration_member`)

**Bisa mengakses:**
- Seluruh modul Eksplorasi: kurikulum, unit, evaluasi, progress tracker, dashboard personal, gamifikasi, log aktivitas dan apresiasi, learning resource repository, reminder personal custom.
- Antarmuka chat WEBI (teks dan suara).
- Forum diskusi Eksplorasi (posting dan membaca).
- Notifikasi in-app terkait Eksplorasi dan WEBI.
- Profil pribadi (lihat dan edit data dasar, minat bidang).

**Tidak bisa mengakses:**
- Modul Eksekusi (Project Ideas, proyek, task, Kanban).
- Admin panel, dashboard monitoring, leaderboard.
- Log percakapan WEBI milik user lain.
- Data progres anggota lain.

**Aksi yang bisa dilakukan:**
- Membuka dan mengerjakan unit kurikulum secara berurutan.
- Mengirim jawaban evaluasi (kuis, esai, praktik).
- Bertanya ke WEBI lewat chat teks atau suara.
- Membaca kembali riwayat percakapan WEBI miliknya sendiri.
- Membuat thread dan membalas di forum diskusi Eksplorasi.
- Mengatur jadwal reminder personal.

### 2.2 Anggota Eksekusi (`execution_member`)

**Bisa mengakses:**
- Modul Eksekusi: Project Ideas, daftar proyek yang dia terlibat, task yang di-assign ke dia, Kanban board, progress update, komentar, attachment, activity log, timeline/milestone.
- Notifikasi in-app terkait Eksekusi.
- Profil pribadi (lihat dan edit).

**Tidak bisa mengakses:**
- Modul Eksplorasi (kurikulum, unit, evaluasi, gamifikasi).
- Antarmuka chat WEBI.
- Forum diskusi Eksplorasi.
- Admin panel, dashboard monitoring.
- Proyek yang dia tidak terlibat sebagai anggota tim (kecuali daftar Project Ideas yang visible untuk semua anggota eksekusi).

**Aksi yang bisa dilakukan:**
- Menginput ide proyek baru di Project Ideas.
- Melihat task yang di-assign ke dia dan mengubah statusnya (todo, in_progress, in_review).
- Menambahkan komentar dan attachment di task.
- Mengirim progress update per task.
- Membuat task baru di proyek yang dia terlibat (jika diberi wewenang oleh admin).

**Aksi yang tidak bisa dilakukan:**
- Mengubah status task ke `done` (hanya admin yang bisa, lewat review).
- Mengubah status proyek.
- Approve/reject ide di Project Ideas.
- Menambahkan/mengeluarkan anggota dari proyek.

### 2.3 Admin / PIC (`admin`)

**Bisa mengakses:**
- Seluruh fitur dari kedua modul (Eksplorasi dan Eksekusi), termasuk seluruh data di dalamnya.
- Admin panel terpusat: dashboard monitoring Eksplorasi (progres semua anggota, leaderboard/ranking), dashboard monitoring Eksekusi (ringkasan proyek, alert panel, feed progress update, ringkasan aktivitas anggota).
- Log percakapan WEBI seluruh anggota eksplorasi (untuk monitoring pola pertanyaan dan kesulitan umum, bukan sebagai pengguna chat WEBI).
- Forum diskusi Eksplorasi (membaca dan membalas).
- Seluruh konfigurasi parameter sistem yang bersifat configurable.

**Aksi yang bisa dilakukan di Eksplorasi:**
- Melihat progres, poin, dan level setiap anggota eksplorasi.
- Melihat leaderboard/ranking anggota (khusus admin, tidak ditampilkan ke anggota).
- Melihat log percakapan WEBI dan guardrail flag untuk monitoring.

**Aksi yang bisa dilakukan di Eksekusi:**
- Menginput ide proyek baru.
- Approve/reject ide proyek (wajib isi rejection_reason saat reject).
- Membuat proyek baru langsung tanpa lewat Project Ideas.
- Setup proyek: deskripsi, tipe, anggota tim, milestone, tanggal mulai/target selesai.
- Membuat task, assign ke anggota, set deadline dan prioritas.
- Review task (memindahkan status ke `done` atau mengembalikan ke `in_progress` dengan feedback).
- Re-assign task, ubah deadline, ubah prioritas.
- Pause proyek (ubah ke `on_hold`) dan resume kembali ke `active`.
- Menandai proyek sebagai `completed` (syarat: semua task `done`).
- Meng-archive proyek yang sudah completed.
- Mengambil alih task (assign ke diri sendiri).

**Catatan:** Admin tidak berinteraksi dengan WEBI sebagai learner. Admin mengakses WEBI hanya lewat log monitoring, bukan lewat antarmuka chat.

---

## 3. KONSOLIDASI FITUR DAN REQUIREMENT PER MODUL

### 3.0 Autentikasi, Profil, dan Fitur Lintas Modul

Fitur di bagian ini dipakai oleh seluruh modul dan seluruh role. Ditempatkan di sini karena bukan milik satu modul tertentu.

#### 3.0.1 Autentikasi dan Akun

- Sistem login dan registrasi per anggota.
- Pembedaan role saat registrasi atau assignment: `exploration_member`, `execution_member`, `admin`.
- Satu user hanya bisa memiliki satu role pada satu waktu.
- Akses ke fitur dan tampilan ditentukan berdasarkan role (Role-Based Access Control).

#### 3.0.2 Profil Anggota

- Data dasar: nama, email, foto profil (opsional).
- Minat bidang: Frontend, Backend, UI/UX, Analis, PM, Fullstack. Diisi saat registrasi, bisa diperbarui kapan saja.
- Status keanggotaan.
- Untuk anggota eksplorasi: tampilan jumlah poin yang sudah dikumpulkan.

#### 3.0.3 Admin Panel Terpusat

Admin panel adalah satu dashboard terpadu yang memperlihatkan data Eksplorasi dan Eksekusi dalam satu tempat. Bukan dua dashboard terpisah.

Komponen sisi Eksplorasi:
- Progres setiap anggota eksplorasi (modul/unit yang sedang dikerjakan, persentase penyelesaian).
- Leaderboard/ranking anggota berdasarkan poin (khusus tampil di sisi admin, tidak pernah ditampilkan ke anggota eksplorasi).
- Akses ke log percakapan WEBI dan guardrail flag.

Komponen sisi Eksekusi (detail lengkap di 3.2.9):
- Ringkasan proyek aktif.
- Alert panel (task/proyek yang butuh perhatian).
- Feed progress update terbaru.
- Ringkasan aktivitas anggota.

#### 3.0.4 Responsive Design

Tampilan menyesuaikan otomatis ke berbagai ukuran layar (desktop, tablet, mobile). Tidak ada aplikasi native mobile terpisah. Ini keputusan desain tunggal yang berlaku untuk seluruh halaman di seluruh modul.

#### 3.0.5 Sistem Notifikasi Terpadu

Seluruh notifikasi di WEBI-SPACE adalah satu sistem terpadu, bukan sistem terpisah per modul. Komponen sistem notifikasi:

- Notifikasi bersifat in-app (muncul di dalam platform, bukan push notification eksternal).
- Setiap notifikasi punya: recipient, konteks (project/task/unit terkait), judul, pesan, status baca (is_read), dan timestamp.
- Sinkronisasi lintas device: notifikasi konsisten muncul di device manapun anggota mengakses sistem.
- Trigger notifikasi berasal dari modul yang berbeda-beda (detail trigger per modul ada di 3.1.8 dan 3.2.10), tapi semuanya masuk ke satu inbox notifikasi per user.

---

### 3.1 Modul Eksplorasi (LMS)

#### 3.1.1 Struktur Kurikulum Bertingkat dan Navigasi

Kurikulum terdiri dari 10 modul dan 67 unit, disusun secara berurutan dari fundamental sampai output akhir (portofolio). Anggota mengerjakan unit secara berurutan sesuai prasyarat.

**Hierarki konten:**
- Modul: unit organisasi terbesar, masing-masing punya tema utama.
- Unit: satuan belajar terkecil, dirancang bisa diselesaikan dalam ~15 menit. Setiap unit punya metadata (estimasi waktu, poin, tipe evaluasi, prasyarat), konten materi, dan evaluasi.
- Checkpoint: muncul di akhir setiap modul, berupa Checklist Akhir Modul dan Intermezo. Checkpoint menandai bahwa satu modul benar-benar tuntas.

**Navigasi:**
- Kurikulum divisualisasikan sebagai peta jalan (terinspirasi roadmap.sh). Anggota melihat seluruh perjalanan belajar sebagai jalur terstruktur dari awal sampai akhir.
- Setiap modul menjadi satu node besar, bisa diperluas menampilkan unit-unit di dalamnya.
- Modul/unit yang sudah selesai ditandai berbeda (misalnya dicentang atau warna terang), yang belum dibuka tampak lebih redup atau terkunci.
- Enam level (Pengenal sampai Lulusan Eksplorasi) ditandai sebagai area atau warna berbeda di peta.

**Pendahuluan kurikulum:**
- Saat pertama kali mengakses kurikulum, anggota melihat halaman pendahuluan yang menjelaskan: kurikulum dibuat untuk pemula total, tidak perlu cepat, ada WEBI sebagai teman belajar, dan mekanisme gamifikasi. Halaman ini muncul sekali, sebelum Modul 1.

#### 3.1.2 Sistem Evaluasi per Unit

Setiap unit diakhiri dengan evaluasi. Tipe evaluasi bervariasi menyesuaikan materi:

**Kuis pilihan ganda/benar-salah.** User memilih jawaban dari opsi yang disediakan. Sistem melakukan auto-grading berdasarkan kunci jawaban. Poin diberikan otomatis saat jawaban disubmit.

**Kuis mencocokkan.** User mencocokkan item di kolom kiri dengan item di kolom kanan. Auto-grading oleh sistem.

**Kuis mengurutkan.** User menyusun item dalam urutan yang benar. Auto-grading oleh sistem.

**Esai singkat (via input teks).** User menulis jawaban dalam kolom teks. Poin diberikan otomatis saat disubmit (auto-approve), karena poin merepresentasikan progres keterlibatan, bukan nilai benar/salah jawaban. [Keputusan baru di PRD]

**Praktik/setup (via input teks).** User menjalankan instruksi praktik dan menuliskan hasilnya. Poin diberikan otomatis saat disubmit (auto-approve), dengan alasan yang sama. [Keputusan baru di PRD]

**Catatan nada:** Seluruh evaluasi disusun dengan nada aman dan mendukung. Tidak ada penilaian benar-salah yang menghakimi. Evaluasi esai dan praktik dinilai sebagai bukti keterlibatan, bukan diberi skor kompetitif. Ini selaras dengan temuan riset bahwa sebagian anggota mudah minder.

**Unit tanpa evaluasi:** Beberapa unit tertentu (misalnya unit rangkuman referensi) tidak memiliki evaluasi. Poin untuk unit seperti ini diberikan saat anggota menandai unit sebagai selesai dibaca.

#### 3.1.3 Progress Tracker dan Dashboard Personal

**Progress tracker** mencatat status penyelesaian di tiga level:
- Per unit: selesai atau belum.
- Per modul: persentase unit yang sudah selesai di modul tersebut.
- Keseluruhan: persentase penyelesaian seluruh kurikulum.

**Dashboard personal** menampilkan:
- Ringkasan progres keseluruhan.
- Unit yang sedang dikerjakan (posisi terakhir).
- Target berikutnya (unit/checkpoint/level selanjutnya).
- Level saat ini dan total poin.

#### 3.1.4 Sistem Gamifikasi

Tiga komponen utama:

**Poin per unit.** Unit materi konsep memberi 10 poin. Unit praktik/setup memberi 15 poin. Poin diberikan saat anggota menyelesaikan evaluasi unit, bukan saat membuka halaman (ini business rule, lihat Bagian 5). Checkpoint (Checklist Akhir Modul + Intermezo) memberi 25 poin bonus.

**Level.** Naik berdasarkan total poin terkumpul. Enam level:
- Level 1, Pengenal (Modul 1-2): memahami peta besar dunia software dan cara website bekerja.
- Level 2, Penyiap (Modul 3): memahami dan menyiapkan peralatan kerja developer.
- Level 3, Kolaborator (Modul 4-5): menguasai version control dan kolaborasi dengan Git dan GitHub.
- Level 4, Perakit (Modul 6-7): membangun tampilan dan memahami sisi backend.
- Level 5, Praktisi (Modul 8-9): memahami kerja tim, metodologi, dan cara merilis karya ke internet.
- Level 6, Lulusan Eksplorasi (Modul 10): menuntaskan portofolio pribadi yang sudah live.

**Visualisasi gamifikasi:**
- Bilah progres keseluruhan kurikulum.
- Indikator level saat ini dan poin terkumpul.
- Penanda posisi anggota di peta kurikulum.
- Perayaan visual saat checkpoint selesai (misalnya "area baru terbuka" di peta).

Prinsip: seluruh visualisasi gamifikasi harus terasa suportif dan memotivasi, tidak menciptakan tekanan atau perbandingan antaranggota. Leaderboard/ranking tidak ditampilkan ke anggota, hanya ke admin (lihat 3.0.3).

#### 3.1.5 Log Aktivitas dan Apresiasi Progres

Feed yang menampilkan pesan apresiasi setiap kali anggota menyelesaikan unit atau checkpoint. Contoh: "Selamat! Kamu mendapatkan 10 poin karena menuntaskan Unit 1.1. Terus jaga semangatmu!"

Nada pesan selalu suportif, tidak membandingkan antaranggota. Fitur ini menggantikan konsep halaman portofolio terpisah; fokusnya motivasi berkelanjutan lewat pengakuan progres kecil.

#### 3.1.6 Learning Resource Repository

Kumpulan link dan referensi pendukung per modul. Sumber-sumber ini tercantum di blueprint kurikulum di akhir setiap modul (roadmap.sh, MDN Web Docs, git-scm.com, Pro Git Book, GitHub Docs, W3Schools, Atlassian Agile Coach, MySQL Tutorial, GitHub Pages Documentation, dan lainnya sesuai modul terkait).

Anggota bisa mengakses sumber ini kapan saja, tidak harus menunggu sampai di modul terkait.

#### 3.1.7 Forum Diskusi Eksplorasi

Forum diskusi diorganisasi per modul dan per unit, supaya pertanyaan dan jawaban kontekstual terhadap materi yang sedang dibahas. [Keputusan baru di PRD]

**Siapa yang bisa mengakses:** Hanya user dengan role `exploration_member` dan `admin`. Anggota eksekusi tidak bisa mengakses forum Eksplorasi. [Keputusan baru di PRD]

**Fungsionalitas:**
- Anggota bisa membuat thread baru di forum unit/modul tertentu.
- Anggota bisa membalas thread yang sudah ada.
- Anggota bisa memilih bertanya ke sesama anggota (peer) atau langsung ke PIC.
- Admin bisa membaca dan membalas semua thread.

#### 3.1.8 Notifikasi Eksplorasi

Trigger notifikasi yang berasal dari modul Eksplorasi (masuk ke sistem notifikasi terpadu di 3.0.5):
- Checkpoint tercapai.
- Naik level.
- Pengingat unit baru tersedia (setelah menyelesaikan unit sebelumnya).
- Pengingat tugas/evaluasi belum selesai.
- Balasan di thread forum yang diikuti anggota.

#### 3.1.9 Reminder Personal Custom (Pelengkap)

Fitur pelengkap (bukan wajib, prioritas lebih rendah dari fitur wajib). Anggota bisa mengatur sendiri jadwal pengingat belajar sesuai ritme masing-masing. Pengingat muncul sebagai notifikasi in-app pada waktu yang ditentukan anggota.

---

### 3.2 Modul Eksekusi (Manajemen Proyek)

#### 3.2.1 Project Ideas

Menu khusus tempat anggota eksekusi dan admin menginput ide proyek.

**Data input per ide:** judul ide, deskripsi singkat, tujuan/relevansi (kenapa ide ini layak dikerjakan), nama pengusul (otomatis dari akun login).

**Status ide:** `draft` (baru diinput), `approved` (dipromosikan jadi proyek), `rejected` (ditolak, wajib ada rejection_reason).

**Tampilan:** daftar ide yang bisa difilter berdasarkan status. Visible untuk semua anggota eksekusi dan admin.

**Validasi:** tidak ada validasi berat di tahap ini. Tujuannya menurunkan barrier supaya siapapun bisa mengusulkan kapan saja.

#### 3.2.2 Manajemen Proyek

**Pembuatan proyek melalui dua jalur:**
- Jalur utama: admin approve ide di Project Ideas, sistem otomatis membuat entitas Project baru dengan data yang di-copy dari ide (judul, deskripsi, tujuan, pengusul). Field `promoted_to_project_id` di ProjectIdea terisi otomatis.
- Jalur alternatif: admin bisa membuat proyek baru secara langsung tanpa lewat Project Ideas (untuk proyek dari luar: permintaan organisasi, kompetisi, proyek lanjutan).

**Data proyek:**
- Judul dan deskripsi detail.
- Tujuan proyek yang terukur.
- Tipe proyek: `internal` (reguler divisi) atau `competition` (lomba/hackathon). Proyek kompetisi punya karakteristik berbeda: deadline fixed, timeline pendek dan intens.
- Anggota tim (dipilih dari daftar user dengan role `execution_member`).
- Tanggal mulai dan target selesai.

**Status proyek dan transisi:**
- `planning`: proyek baru dibuat, milestone sedang didefinisikan, belum ada task.
- `active`: task sudah dibuat dan sedang dikerjakan. Transisi otomatis dari `planning` saat task pertama dibuat.
- `on_hold`: proyek sengaja di-pause oleh admin. Bisa kembali ke `active`.
- `completed`: semua task selesai, proyek ditandai selesai oleh admin. Syarat: semua task berstatus `done`.
- `archived`: proyek disimpan sebagai arsip, bersifat read-only. Data tetap bisa dilihat tapi tidak bisa diedit.

Transisi yang diizinkan: `planning` > `active` (otomatis), `active` <> `on_hold` (manual admin), `active` > `completed` (manual admin), `completed` > `archived` (manual admin).

#### 3.2.3 Milestone

Penanda tahapan penting proyek. Setiap proyek dipecah jadi beberapa milestone.

**Data per milestone:** judul, deskripsi (opsional), target tanggal selesai, urutan (sort_order).

Progres milestone dihitung secara derived: persentase task berstatus `done` dari total task yang terhubung ke milestone tersebut. Tidak disimpan sebagai field statis karena nilainya harus selalu akurat real-time.

#### 3.2.4 Manajemen Task

Proyek dipecah jadi task-task konkret. Setiap task wajib terhubung ke satu milestone.

**Data per task:** judul (singkat, deskriptif), deskripsi (detail apa yang harus dikerjakan), assignee (satu atau lebih anggota via TaskAssignment), deadline, prioritas (`low`/`medium`/`high`), status.

**Status task dan transisi:**
- `todo`: task baru dibuat, belum dikerjakan.
- `in_progress`: anggota sedang mengerjakan.
- `in_review`: anggota selesai mengerjakan, menunggu review admin.
- `done`: admin sudah review dan menyetujui.

Transisi: `todo` > `in_progress` > `in_review` > `done`. Bisa mundur dari `in_review` ke `in_progress` jika admin meminta revisi. Perpindahan status bisa dilakukan lewat drag-and-drop di Kanban board atau lewat tombol ubah status di detail task.

**Catatan:** saat task pertama dibuat di sebuah proyek, status proyek otomatis berubah dari `planning` ke `active`.

#### 3.2.5 Kanban Board View

Visualisasi task dalam bentuk papan empat kolom: Todo, In Progress, In Review, Done.

Kanban bukan entitas data terpisah. Kanban adalah representasi visual (view layer) yang membaca field `status` pada entitas Task dan menampilkannya per kolom. Task berpindah kolom saat statusnya berubah.

Interaksi: anggota dan admin bisa drag-and-drop kartu task antar kolom (sesuai transisi yang diizinkan per role).

#### 3.2.6 Progress Update dan Pelaporan

Mekanisme pelaporan kerja formal yang terpisah dari komentar.

**Data per progress update:** isi update (teks: apa yang sudah dikerjakan, kendala yang dihadapi), attachment opsional (link atau file pendukung), timestamp otomatis.

**Kenapa dipisah dari komentar:** komentar adalah ruang diskusi bebas (tanya jawab, klarifikasi, catatan). Progress update adalah laporan kerja yang menjadi bahan monitoring admin di dashboard. Kalau dicampur, dashboard monitoring jadi noisy karena admin harus menyortir mana laporan dan mana diskusi.

Progress update bersifat append-only: setiap update baru adalah entry baru, tidak bisa diedit. Ini menjaga integritas riwayat pelaporan.

#### 3.2.7 Komentar dan Attachment per Task

**Komentar:** komunikasi terdokumentasi langsung di task terkait. Kronologis, bisa dari anggota maupun admin. Komentar bisa diedit (berbeda dari progress update).

**Attachment:** mendukung tiga bentuk (keputusan tahap 1.2, Fiksasi Fitur):
- File upload (gambar, PDF, dokumen) dengan metadata: nama file, URL/path, MIME type, ukuran.
- Link eksternal (URL) dengan label.
- Input teks biasa (catatan bebas) dengan label, tanpa file atau URL.

Attachment bisa ditambah kapan saja selama task belum berstatus `done`.

#### 3.2.8 Activity Log

Riwayat seluruh perubahan status, aksi, dan event di modul Eksekusi. Bersifat append-only dan immutable (tidak ada update atau delete). Ini log audit.

Setiap entry mencatat: proyek terkait, task terkait (jika relevan), user yang melakukan aksi, tipe aksi, deskripsi human-readable, metadata terstruktur (untuk keperluan query), dan timestamp.

Daftar lengkap tipe aksi yang dicatat: ide dibuat, ide di-approve, ide di-reject, proyek dibuat, status proyek berubah, anggota ditambahkan/dikeluarkan dari proyek, milestone dibuat, task dibuat, status task berubah, task di-assign, task di-re-assign, deadline task diubah, prioritas task diubah, progress update ditambahkan, komentar ditambahkan, attachment ditambahkan.

#### 3.2.9 Dashboard Monitoring Admin (Eksekusi)

Bagian ini adalah section Eksekusi dari admin panel terpusat (3.0.3). Empat komponen tampilan:

**Komponen A: Ringkasan Proyek Aktif.** Untuk setiap proyek `active` atau `on_hold`: judul, tipe (`internal`/`competition`), status, progres keseluruhan (persentase task `done`), progres per milestone, jumlah anggota aktif, tanggal target selesai dan sisa hari.

**Komponen B: Alert Panel.** Daftar task/proyek yang men-trigger sinyal peringatan otomatis, diurutkan dari yang paling kritis. Enam jenis sinyal:

- OVERDUE (severity tinggi): deadline task sudah lewat dan status bukan `done`. Label merah.
- DUE SOON (severity sedang): deadline task dalam 3 hari ke depan dan status bukan `done`. Label kuning. Threshold configurable.
- STALLED (severity sedang-tinggi): task berstatus `in_progress` dan tidak ada progress update maupun perubahan status selama 7 hari terakhir. Label oranye. Threshold configurable.
- INACTIVE MEMBER (severity tinggi): anggota punya task `todo`/`in_progress` tapi tidak ada aktivitas apapun selama 14 hari. Threshold configurable.
- MILESTONE AT RISK (severity tinggi): tanggal hari ini sudah melewati target_date milestone tapi masih ada task belum `done`. Label merah di timeline.
- PROJECT IDLE (severity tinggi): proyek `active` tapi tidak ada activity log entry selama 14 hari. Threshold configurable.

**Komponen C: Feed Progress Update Terbaru.** Timeline kronologis progress update dari semua anggota di semua proyek aktif. Tiap entry: nama anggota, judul task, isi update ringkas, timestamp. Klik untuk masuk ke detail task.

**Komponen D: Ringkasan Aktivitas Anggota.** Per anggota eksekusi: jumlah task by status, tanggal progress update terakhir, jumlah task yang deadline-nya sudah lewat.

**Enam tindakan intervensi admin:**

- Re-assign task: pindahkan task dari satu anggota ke anggota lain. Assignment lama dicabut, anggota baru dan lama menerima notifikasi.
- Ubah deadline: ubah deadline task (untuk proyek `competition`, yang diubah deadline task internal, bukan deadline proyek).
- Tambah komentar intervensi: arahan, feedback, atau teguran langsung di task.
- Ubah prioritas task: menaikkan/menurunkan prioritas.
- Pause proyek (on_hold): semua notifikasi deadline dan alert flag untuk proyek ini di-suppress selama on_hold. Saat di-resume, deadline yang sudah lewat perlu di-update manual.
- Ambil alih task: admin assign task ke diri sendiri.

#### 3.2.10 Notifikasi Eksekusi

Trigger notifikasi yang berasal dari modul Eksekusi (masuk ke sistem notifikasi terpadu di 3.0.5):

Untuk anggota eksekusi:
- Task baru di-assign.
- Task dikembalikan ke `in_progress` (revisi, oleh admin).
- Komentar baru dari admin di task yang di-assign.
- Komentar baru dari siapapun di task yang di-assign (kecuali komentar sendiri).
- Status ide di-approve atau di-reject.
- Ditambahkan ke proyek baru.
- Status proyek berubah.

Untuk admin:
- Task berpindah ke `in_review`.
- Progress update baru dari anggota.
- Ide baru diinput di Project Ideas.
- Flag OVERDUE, STALLED, atau INACTIVE MEMBER pertama kali muncul.

---

### 3.3 Modul WEBI (AI Companion)

WEBI terintegrasi di dalam modul Eksplorasi sebagai antarmuka chat. Dari perspektif user, WEBI adalah bagian dari pengalaman belajar di Eksplorasi, bukan modul terpisah. Namun secara teknis, WEBI memiliki pipeline, dataset, dan entitas data sendiri.

#### 3.3.1 Antarmuka Chat

Chat WEBI tersedia di dalam halaman LMS, bisa diakses kapan saja oleh anggota eksplorasi yang sudah login. Antarmuka menampilkan riwayat percakapan dan input teks/suara.

Hak akses: hanya `exploration_member`. Admin mengakses log percakapan lewat dashboard monitoring, bukan lewat antarmuka chat.

#### 3.3.2 Lingkup Pengetahuan

WEBI menjawab pertanyaan dalam tiga tier, diurutkan berdasarkan prioritas:

**Tier 1 (prioritas tertinggi): Konten kurikulum.** Seluruh materi Modul 1 sampai Modul 10 dari blueprint. Saat menjawab pertanyaan yang tercakup di kurikulum, WEBI merujuk pada konten kurikulum, bukan mengarang penjelasan dari pengetahuan umum model AI.

**Tier 2: Konteks ekosistem webdev terkait.** Pertanyaan web development yang masih dalam domain kurikulum tapi belum dibahas eksplisit di unit tertentu. WEBI menjawab ringkas dan mengarahkan ke unit yang relevan. Contoh: user di Modul 2 bertanya "apa itu JavaScript?" (belum sampai Modul 7), WEBI memberi penjelasan ringkas dan menambahkan "Penjelasan lengkapnya ada di Modul 7 nanti."

**Tier 3: Informasi sistem WEBI-SPACE.** Pertanyaan soal navigasi LMS, cara kerja poin dan level, cara melihat progres, cara kerja evaluasi, cara pakai fitur chat WEBI sendiri.

**Domain yang ditolak (dengan template respons penolakan):**
- Pertanyaan di luar domain webdev dan WEBI-SPACE: "Aku cuma bisa bantu soal materi web development dan cara pakai WEBI-SPACE ya. Ada hal lain seputar itu yang mau kamu tanyakan?"
- Permintaan pribadi-sensitif (curhat, masalah keuangan, hubungan): "Aku tidak bisa bantu soal itu, tapi kalau kamu butuh bicara dengan seseorang, coba hubungi PIC atau orang yang kamu percaya ya."
- Permintaan generate kode di luar konteks kurikulum: "Aku di sini untuk bantu kamu paham materi kurikulum ini. Untuk proyek di luar kurikulum, coba eksplorasi sendiri dulu pakai konsep yang sudah kamu pelajari ya."
- Permintaan konten berbahaya/tidak pantas/ilegal: "Aku tidak bisa bantu soal itu."

#### 3.3.3 Perlindungan Integritas Evaluasi

Area paling kritis. Tanpa mekanisme ini, tracker progres kehilangan makna karena poin tidak mencerminkan pemahaman riil. Prinsip utama: WEBI membantu user memahami konsep, bukan menggantikan proses evaluasi.

**Strategi per tipe evaluasi:**

Kuis pilihan ganda/benar-salah (risiko tinggi): WEBI menolak memberi jawaban langsung. Menjelaskan ulang konsep yang diuji supaya user bisa menjawab sendiri. Contoh respons: "Aku tidak bisa kasih jawaban kuisnya langsung, tapi aku bisa bantu kamu pahami konsepnya. [penjelasan]. Coba jawab lagi berdasarkan ini ya."

Kuis mencocokkan (risiko sedang-tinggi): WEBI menolak memberikan pasangan yang benar. Menjelaskan fungsi masing-masing item secara terpisah supaya user mencocokkan sendiri.

Kuis mengurutkan (risiko sedang): WEBI menolak memberikan urutan langsung. Menjelaskan logika di balik urutan supaya user menyusun sendiri.

Esai singkat (risiko sedang): WEBI boleh menjelaskan konsep terkait topik esai. Tidak boleh menyusunkan paragraf utuh atau kerangka jawaban yang tinggal dicopy.

Praktik/setup (risiko rendah-sedang): WEBI boleh menjelaskan cara kerja perintah dan membantu troubleshoot error. Tidak boleh memberikan output yang seharusnya dihasilkan user sendiri.

**Mekanisme deteksi:**

Daftar soal evaluasi per unit di-inject ke konteks WEBI sebagai data referensi (`[EVALUATION_BANK]`) dalam format terstruktur, supaya model AI bisa melakukan matching antara input user dan soal yang ada.

Deteksi berbasis matching semantik, bukan hanya exact string match, karena user bisa memparafrase soal.

Saat confidence rendah (tidak yakin apakah pertanyaan merujuk soal evaluasi atau pertanyaan belajar biasa), WEBI tetap menjawab konsepnya tapi menambahkan pengingat ringan: "Kalau ini terkait evaluasi unit, coba kerjakan sendiri dulu ya. Aku di sini untuk bantu kamu paham, bukan untuk menggantikan proses belajarmu."

Validasi output di backend sebagai pertahanan lapis kedua (detail di 3.3.10).

#### 3.3.4 Persona dan Nada Bicara

WEBI adalah teman belajar yang sabar dan suportif. Prinsip nada yang wajib ditegakkan:

- Bahasa Indonesia kasual tapi jelas. Tidak terlalu formal, tidak terlalu slang.
- Validasi sebelum penjelasan. Saat user menunjukkan kebingungan: "Wajar kok kalau bagian ini membingungkan" sebelum masuk ke penjelasan ulang.
- Tidak pernah membandingkan antaranggota. WEBI tidak punya akses ke data anggota lain.
- Frasa yang dilarang keras: "harusnya kamu sudah tahu", "ini kan gampang", "masa belum paham", atau variasi bernada serupa.
- Mendorong tanpa memaksa. "Mau lanjut ke unit berikutnya?" bukan "Kamu belum lanjut."

#### 3.3.5 Personalisasi

WEBI mempersonalisasi respons berdasarkan tiga jenis data:

**a. Data progres user (read-only dari LMS tracker).** Data yang dibaca: completed_units, current_unit, current_level (1-6 + nama level), total_points, completed_checkpoints, unit_completion_timestamps, unit_open_count_without_completion.

Efek personalisasi: WEBI menyesuaikan kedalaman jawaban berdasarkan posisi user di kurikulum. User di Modul 2 yang bertanya soal website dijawab dari nol dengan analogi sehari-hari. User di Modul 6 bisa dirujuk balik ke konsep yang sudah dipelajari.

**b. Riwayat percakapan (read-write, database WEBI sendiri).** Setiap pesan disimpan dengan: conversation_id, sender (user/webi), content, timestamp, unit_context, voice_mode. Sesi baru dimulai setelah jeda 30 menit dari pesan terakhir (threshold configurable). Riwayat yang di-inject ke konteks model AI per request: N pesan terakhir dari sesi aktif (default 20 pesan, configurable).

Efek personalisasi: kalau user bertanya topik yang sama di sesi berbeda, WEBI menyesuaikan penjelasan (sudut pandang/analogi berbeda). Kalau bertanya topik sama lebih dari 3 kali lintas sesi, ini sinyal stuck yang menjadi trigger sapaan proaktif.

**c. Minat bidang (read-only dari profil user).** Frontend, Backend, UI/UX, Analis, PM, Fullstack.

Efek personalisasi: WEBI menyesuaikan framing dan contoh. User minat UI/UX yang belajar HTML mendapat penekanan aspek visual. User minat Backend mendapat penekanan aspek struktur data. Ini pengayaan, materi inti tetap sama.

**Gamifikasi bisa ditanyakan:** WEBI bisa menjawab pertanyaan soal progres gamifikasi (level saat ini, poin yang dibutuhkan untuk naik level, deskripsi level berikutnya).

#### 3.3.6 Mode Interaksi Reaktif (Chat)

Mode utama. User membuka chat WEBI, mengetik atau berbicara, WEBI merespons sesuai lingkup pengetahuan dan personalisasi. Respons bersifat sinkron tanpa delay artifisial.

#### 3.3.7 Mode Interaksi Proaktif (Sapaan WEBI)

WEBI mengirim pesan duluan berdasarkan trigger tertentu. Pesan muncul di antarmuka chat saat user membuka WEBI-SPACE, bukan sebagai push notification eksternal.

**Trigger 1: Pertama kali login.** User pertama kali membuka WEBI setelah registrasi. Pesan: "Halo! Aku WEBI, teman belajarmu di sini. Kamu bisa tanya aku soal materi kurikulum, cara pakai sistem ini, atau hal apapun seputar web development yang ada di kurikulum. Kalau bingung mulai dari mana, tanya aja." Prioritas tertinggi, selalu muncul terlepas dari kondisi apapun.

**Trigger 2: Stagnasi progres.** User tidak menyelesaikan unit baru selama 5 hari (threshold configurable). Pesan ringan tanpa tekanan: "Hei, terakhir kamu di Unit [X.Y]. Kalau ada yang bikin bingung, tanya aja. Kalau belum sempat, santai, lanjut kapan kamu siap."

**Trigger 3: Stuck di satu unit.** User membuka unit yang sama lebih dari 3 kali tanpa menyelesaikan evaluasi, ATAU bertanya topik yang sama di WEBI lebih dari 3 kali lintas sesi. Pesan spesifik: "Kayaknya Unit [X.Y] tentang [topik] agak tricky ya? Mau aku coba jelaskan dari sudut yang berbeda?"

**Trigger 4: Setelah naik level.** Perubahan level terdeteksi dari LMS tracker. Ucapan selamat dan preview level baru. Prioritas tinggi, selalu muncul terlepas dari cooldown.

**Trigger 5: Setelah menyelesaikan checkpoint modul.** User menyelesaikan Intermezo akhir modul. Apresiasi dan arahan ke modul berikutnya. Prioritas tinggi, selalu muncul.

**Pembatasan frekuensi (anti-spam):**
- Maksimal 1 sapaan proaktif per hari per user.
- Kalau user tidak merespons dalam 24 jam, cooldown 3 hari sebelum sapaan berikutnya.
- Kalau tidak merespons 3 kali berturut-turut, WEBI berhenti mengirim sapaan tipe nudge (Trigger 2 dan 3) sampai user kembali aktif.
- Trigger 4 dan 5 (apresiasi pencapaian) tetap dikirim terlepas dari cooldown.

**Penyimpanan status per user:** last_proactive_message_date, unanswered_proactive_count, last_trigger_type, onboarding_sent (boolean).

#### 3.3.8 Rekomendasi Materi Lanjutan

WEBI bisa menambahkan rekomendasi kontekstual setelah menjawab pertanyaan konsep. Tiga jenis rekomendasi:
- Arahan ke unit selanjutnya.
- Arahan ke unit di modul lain yang relevan.
- Sumber belajar tambahan dari kurikulum.

Frekuensi: maksimal 1 rekomendasi per 3 pesan. Harus kontekstual (ada unit/sumber relevan yang belum diakses user), bukan generik.

#### 3.3.9 Riwayat Percakapan Tersimpan per User

Seluruh percakapan user dengan WEBI tersimpan dan bisa diakses kembali lewat antarmuka chat. User bisa scroll dan membaca percakapan sebelumnya. WEBI menggunakan riwayat ini untuk mendeteksi topik yang sering ditanyakan, menghindari pengulangan jawaban, dan mendeteksi pola stuck.

#### 3.3.10 Interaksi Suara (STT/TTS)

Mode suara adalah lapisan konversi di atas dan di bawah pipeline teks yang sama. Bukan jalur terpisah. Semua guardrail berlaku identik di mode suara.

**Alur teknis:** User tekan tombol mikrofon > input suara direkam > STT konversi ke teks > transkrip ditampilkan ke user untuk verifikasi > teks diproses oleh pipeline WEBI yang sama > respons teks dihasilkan > TTS konversi ke suara > audio diputar + teks respons tetap ditampilkan di chat.

**Penyesuaian respons saat VOICE_MODE=true:**
- Respons lebih ringkas (maksimal 3-4 kalimat per poin).
- Blok kode tidak dibacakan, hanya disebutkan namanya dan user diarahkan ke teks di chat.
- Transkrip STT ditampilkan supaya user bisa verifikasi.

**Ketersediaan:** mode suara opsional, user yang memilih. Tombol mikrofon tersedia di samping input teks. Bisa berganti mode kapan saja di tengah percakapan. Provider STT/TTS ditentukan di tahap 1.9.

#### 3.3.11 Integrasi API dan Manajemen Dataset

**Integrasi API:** WEBI menggunakan API model AI eksternal (provider ditentukan di tahap 1.9). Kriteria pemilihan: dukungan bahasa Indonesia yang akurat, latency rendah, biaya terjangkau untuk ~12 user, API yang bisa diintegrasikan ke tech stack yang dipilih.

**Sumber dataset utama:** Blueprint Kurikulum Eksplorasi. Data yang diekstrak:
- `curriculum_content`: konten materi per unit, di-index per module_id dan unit_id. Direktif penyajian (`[SAJIKAN: ...]`) di-strip karena itu instruksi frontend.
- `evaluation_bank`: soal evaluasi per unit dalam format terstruktur (tipe, soal, kunci jawaban). Kunci jawaban hanya untuk deteksi, tidak pernah di-expose ke user.
- `supplementary_resources`: sumber belajar tambahan per modul.
- `gamification_rules`: definisi level, threshold poin, aturan poin.
- `system_info`: informasi navigasi dan cara kerja fitur WEBI-SPACE (diisi setelah UI/UX tahap 1.8 selesai).

**Skema update dataset:**
- Prinsip: kurikulum adalah single source of truth, dataset WEBI adalah turunannya.
- Update terjadi saat kurikulum direvisi di Fase 3 (iterasi). Dataset WEBI harus diperbarui sebelum versi kurikulum baru dipublikasikan ke anggota.
- Yang trigger update: admin (PIC), bukan otomatis.
- Proses: kurikulum direvisi > admin ekspor ulang ke format dataset WEBI > validasi (cek unit yang dirujuk masih ada, cek soal evaluasi masih sinkron) > handle unit yang dihapus (redirect current_unit user) > deploy dataset baru > archive dataset lama.
- Penanganan gap transisi: WEBI menjawab berdasarkan konten terbaru. Kalau user merujuk konten lama yang sudah berubah, WEBI menjelaskan bahwa materi sudah diperbarui.

Format penyimpanan aktual (JSON, database terstruktur, atau vector store) ditentukan di tahap 1.9.

#### 3.3.12 Guardrail Teknis

**Layer 1: System prompt.**

Bagian statis (tidak berubah antar user/request): instruksi persona, instruksi domain (3 tier + template penolakan), instruksi perlindungan evaluasi, instruksi rekomendasi (maks 1 per 3 pesan), instruksi mode suara. Detail lengkap sudah didefinisikan di spesifikasi WEBI (1.5).

Bagian dinamis (di-inject per request): USER_CONTEXT (id, nama, level, poin, current_unit, completed_units, interest_field, voice_mode), CONVERSATION_HISTORY (N pesan terakhir dari sesi aktif), RELEVANT_CURRICULUM_CONTENT (konten unit terkait, di-retrieve berdasarkan similarity), EVALUATION_BANK (soal evaluasi dari unit terkait).

**Layer 2: Validasi output di backend.**

Cek jawaban evaluasi: respons WEBI dibandingkan dengan kunci jawaban kuis. Similarity di atas threshold (default 0.85, configurable) > respons di-flag, tidak dikirim, request di-retry dengan instruksi tambahan.

Cek domain: respons yang membahas topik di luar domain > di-block. Safety net untuk kasus system prompt gagal ditegakkan.

**Rate limiting:** maksimal 50 pesan per hari per user (configurable). Mencegah abuse dan mengontrol biaya API.

**Logging untuk monitoring admin:**
- Semua pesan (input user dan respons WEBI) tersimpan di database.
- GuardrailFlag: mencatat flag perlindungan evaluasi yang ter-trigger (unit mana, soal mana, berapa kali), flag penolakan domain, dan flag validasi output backend.
- Admin mengakses log ini dari dashboard monitoring untuk: melihat pola pertanyaan umum (perbaikan kurikulum), mendeteksi guardrail yang sering gagal (perbaikan system prompt), dan melihat keaktifan bertanya per anggota.

---

## 4. USER FLOW PER MODUL

### 4.1 User Flow Eksplorasi

**Titik masuk: pertama kali login.**
Anggota baru login > melihat halaman Pendahuluan Kurikulum (penjelasan cara kerja kurikulum, gamifikasi, WEBI) > membuka peta kurikulum > melihat Modul 1 sebagai titik awal > WEBI mengirim sapaan pertama (Trigger 1: "Halo! Aku WEBI...").

**Alur mengerjakan unit.**
Dari peta kurikulum atau dashboard, anggota memilih unit yang tersedia (unit berikutnya yang belum selesai) > membaca konten materi > sampai di evaluasi unit > mengerjakan evaluasi sesuai tipenya (kuis / esai / praktik) > mengirim jawaban > poin otomatis diberikan > unit ditandai selesai > pesan apresiasi muncul di log aktivitas ("Selamat! Kamu mendapatkan X poin...") > unit berikutnya terbuka.

**Interaksi dengan WEBI saat belajar.**
Di titik manapun selama mengerjakan unit, anggota bisa membuka chat WEBI > bertanya soal materi yang sedang dipelajari (teks atau suara) > WEBI merespons sesuai posisi anggota di kurikulum > sesekali WEBI menambahkan rekomendasi materi lanjutan.

**Saat stuck.**
Anggota membuka unit yang sama berulang kali tanpa menyelesaikan evaluasi > atau bertanya topik yang sama berkali-kali ke WEBI > WEBI mengirim sapaan proaktif Trigger 3 ("Kayaknya Unit X.Y agak tricky ya?...") > anggota bisa melanjutkan chat untuk mendapat penjelasan dari sudut berbeda.

**Menyelesaikan checkpoint modul.**
Anggota menyelesaikan seluruh unit di satu modul > mengerjakan Checklist Akhir Modul > mengerjakan Intermezo > mengisi Form Tanggapan Modul > 25 poin bonus diberikan > WEBI mengirim sapaan Trigger 5 ("Modul [N] selesai!...") > modul berikutnya terbuka di peta.

**Naik level.**
Total poin melewati threshold level berikutnya > level otomatis naik > WEBI mengirim sapaan Trigger 4 ("Selamat, kamu sekarang Level [N]!...") > area baru di peta kurikulum berubah tampilan.

**Menyelesaikan seluruh kurikulum.**
Anggota menyelesaikan Modul 10 (portofolio live di GitHub Pages) > mengerjakan Intermezo Modul 10 (refleksi akhir + lampirkan URL portofolio) > mencapai Level 6 (Lulusan Eksplorasi).

### 4.2 User Flow Eksekusi

**Mengusulkan ide proyek.**
Anggota eksekusi membuka menu Project Ideas > mengisi form (judul, deskripsi, tujuan/relevansi) > ide tersimpan dengan status `draft` > activity log mencatat > anggota menunggu review admin.

**Ide di-approve dan bergabung ke proyek.**
Admin approve ide > sistem otomatis buat entitas Project > anggota (pengusul) menerima notifikasi "Ide kamu di-approve" > admin setup proyek (deskripsi detail, tipe, anggota tim, milestone, tanggal) > anggota ditambahkan ke tim > menerima notifikasi "Kamu ditambahkan ke proyek [judul]."

**Mengerjakan task.**
Admin/anggota membuat task di bawah milestone > anggota di-assign > menerima notifikasi "Task baru di-assign ke kamu" > anggota membuka Kanban board > memindahkan task dari `todo` ke `in_progress` > mengerjakan sambil menambahkan komentar/attachment jika perlu > mengirim progress update > setelah selesai, memindahkan ke `in_review`.

**Siklus review.**
Admin menerima notifikasi task masuk `in_review` > admin review > dua kemungkinan: (a) lolos, admin pindahkan ke `done`, atau (b) perlu revisi, admin tambah komentar feedback dan pindahkan kembali ke `in_progress` > anggota menerima notifikasi revisi > memperbaiki > submit ulang ke `in_review`.

**Penyelesaian proyek.**
Semua task `done` > admin review akhir keseluruhan proyek > admin tandai `completed` > proyek bisa di-`archived` untuk referensi riwayat.

### 4.3 User Flow WEBI

User flow WEBI tidak berdiri sendiri; dari perspektif anggota, interaksi WEBI adalah bagian dari pengalaman belajar di Eksplorasi. Bagian ini merinci detail teknis dari titik-titik interaksi WEBI yang sudah disebutkan di user flow 4.1.

**Chat reaktif (user yang mulai).**
Anggota membuka chat WEBI dari halaman LMS > mengetik pertanyaan atau menekan tombol mikrofon > WEBI memproses: inject user_context + conversation_history + relevant_curriculum_content + evaluation_bank ke konteks > model AI generate respons > validasi backend cek jawaban evaluasi dan domain > respons dikirim ke user (teks; ditambah audio jika voice mode) > pesan tersimpan di database.

**Sapaan proaktif (WEBI yang mulai).**
Sistem cek kondisi trigger secara berkala > kalau kondisi terpenuhi dan anti-spam rules terpenuhi > pesan proaktif disiapkan > pesan muncul di chat saat anggota membuka WEBI-SPACE > anggota bisa merespons atau mengabaikan > status sapaan dicatat (responded/not responded) > anti-spam counter diperbarui.

**Pergantian mode teks dan suara.**
Anggota bisa berganti antara mengetik dan berbicara kapan saja di tengah percakapan. Saat switch ke voice mode: flag VOICE_MODE=true di-set > respons WEBI lebih ringkas > blok kode ditampilkan teks saja. Saat switch kembali ke teks: flag VOICE_MODE=false > respons WEBI kembali ke panjang normal.

---

## 5. BUSINESS RULES

Aturan-aturan berikut bersifat tegas dan harus ditegakkan oleh sistem. Ini bukan sekadar fitur, tapi aturan operasional yang kalau dilanggar akan merusak integritas data atau pengalaman user.

### 5.1 Transparansi Monitoring Chat WEBI

[Keputusan baru di PRD]

Anggota eksplorasi harus diberi tahu secara eksplisit bahwa percakapan mereka dengan WEBI bisa diakses admin untuk keperluan monitoring. Tidak boleh ada anggota yang menganggap chat WEBI sepenuhnya privat padahal kenyataannya bisa dibaca admin.

**Kapan pemberitahuan muncul:**
- Saat onboarding pertama kali: di halaman Pendahuluan Kurikulum, tercantum penjelasan bahwa chat WEBI dimonitor admin untuk membantu mendeteksi kesulitan belajar umum.
- Saat pertama kali membuka chat WEBI: notice singkat muncul di atas antarmuka chat (persistent, tidak bisa di-dismiss), misalnya: "Percakapanmu dengan WEBI bisa diakses PIC untuk membantu memahami kesulitan belajar yang umum dialami anggota."

**Prinsip:** Monitoring bertujuan untuk perbaikan kurikulum dan dukungan belajar, bukan pengawasan punitif. Nada pemberitahuan harus mencerminkan ini.

### 5.2 Perlindungan Integritas Evaluasi

WEBI tidak boleh memberikan jawaban langsung untuk soal evaluasi dalam kondisi apapun. Mekanisme perlindungan dua lapis (system prompt + validasi backend) harus aktif di setiap request. Detail strategi per tipe evaluasi ada di 3.3.3.

### 5.3 Otoritas Approve/Reject Project Ideas

Hanya admin yang bisa mengubah status ide dari `draft` ke `approved` atau `rejected`. Anggota eksekusi bisa menginput ide tapi tidak bisa meng-approve ide miliknya sendiri maupun ide orang lain.

### 5.4 Rejection Reason Wajib

Saat admin me-reject ide proyek, field `rejection_reason` wajib diisi. Sistem tidak mengizinkan reject tanpa alasan. Tujuannya supaya pengusul tahu kenapa idenya tidak diangkat dan bisa memperbaiki atau mengusulkan ide lain.

### 5.5 Otoritas Penandaan Proyek Completed

Hanya admin yang bisa menandai proyek sebagai `completed`. Syarat: seluruh task dalam proyek sudah berstatus `done`. Sistem memvalidasi syarat ini sebelum mengizinkan perubahan status.

### 5.6 Admin Bisa Membuat Proyek Langsung

Admin bisa membuat proyek baru tanpa lewat jalur Project Ideas. Ini untuk mengakomodasi proyek dari luar (permintaan organisasi, peluang kompetisi, proyek lanjutan).

### 5.7 Poin Diberikan Saat Evaluasi Selesai, Bukan Saat Membuka Halaman

Untuk kuis: poin diberikan saat jawaban disubmit (auto-grading). Untuk esai dan praktik: poin diberikan saat jawaban disubmit (auto-approve). Untuk unit tanpa evaluasi: poin diberikan saat anggota menandai unit selesai dibaca. Sekedar membuka halaman unit tanpa menyelesaikan evaluasi tidak menghasilkan poin.

### 5.8 Leaderboard Hanya Visible di Sisi Admin

Ranking/leaderboard anggota eksplorasi berdasarkan poin hanya ditampilkan di admin panel. Anggota eksplorasi tidak bisa melihat ranking ini. Tujuannya mencegah tekanan kompetitif antaranggota, selaras dengan temuan riset bahwa sebagian anggota mudah minder.

### 5.9 WEBI Read-Only terhadap Data Progres LMS

WEBI hanya membaca data progres dari LMS tracker, tidak pernah menulis ke data tersebut. Data yang ditulis WEBI terbatas pada entitas miliknya sendiri: Conversation, Message, ProactiveLog, GuardrailFlag.

### 5.10 Rate Limiting Pesan WEBI

Maksimal 50 pesan per hari per user (configurable). Setelah batas tercapai, WEBI menolak menerima pesan baru sampai reset harian. Tujuannya mencegah abuse dan mengontrol biaya API.

### 5.11 Dataset WEBI Hanya Di-update oleh Admin

Update dataset WEBI bukan proses otomatis. Admin yang trigger update setelah kurikulum direvisi. Dataset baru harus divalidasi sebelum di-deploy.

### 5.12 Proyek Archived Bersifat Read-Only

Proyek yang sudah di-archive tetap bisa dilihat datanya (untuk referensi riwayat) tapi tidak bisa diedit. Tidak ada transisi status keluar dari `archived`.

### 5.13 Satu User Satu Role

Satu user hanya bisa memiliki satu role pada satu waktu (`exploration_member`, `execution_member`, atau `admin`). Kalau ada perubahan role (misalnya anggota eksplorasi yang pindah ke eksekusi), data progres dari role sebelumnya tetap tersimpan di database tapi user tidak lagi mengakses fitur role lama.

### 5.14 On Hold Menekan Notifikasi dan Alert

Saat proyek berstatus `on_hold`, seluruh notifikasi deadline dan alert flag (OVERDUE, DUE SOON, STALLED, dsb.) untuk proyek tersebut di-suppress. Saat proyek di-resume ke `active`, deadline task yang sudah lewat selama masa pause perlu di-update manual oleh admin.

### 5.15 ProgressUpdate dan ActivityLog Bersifat Immutable

ProgressUpdate bersifat append-only: setiap update baru adalah entry baru, tidak bisa diedit atau dihapus. ActivityLog bersifat append-only dan immutable: tidak ada update atau delete. Keduanya adalah log audit yang integritasnya harus dijaga.

### 5.16 Forum Diskusi Hanya untuk Eksplorasi

Forum diskusi diputuskan di tahap 1.2 (Fiksasi Fitur) sebagai fitur Eksplorasi saja. Forum diskusi Eksplorasi hanya bisa diakses oleh `exploration_member` dan `admin`. Tidak ada forum diskusi untuk modul Eksekusi.

**Koreksi (task 2.6, 2026-07-04):** versi sebelumnya dari dokumen ini (bagian 2.2 dan section ini) sempat menyebut "Forum diskusi Eksekusi" sebagai hak akses `execution_member`, termasuk label section ini yang tadinya "Forum Eksplorasi dan Eksekusi Terpisah". Ini adalah salah tulis saat konsolidasi PRD — forum diskusi tidak pernah diputuskan untuk Eksekusi di tahap 1.2, dan `docs/struktur-eksekusi.md` (dokumen struktur modul Eksekusi) tidak pernah membahasnya sama sekali. Dikoreksi di sini; tidak ada forum Eksekusi yang akan dibangun.

---

## 6. BATASAN SISTEM

Berikut hal-hal yang secara sadar tidak dibangun di WEBI-SPACE. Batasan ini sudah disepakati dan tidak boleh diubah tanpa pembahasan ulang yang eksplisit.

### 6.1 Batasan dari Fiksasi Fitur (1.2)

1. Tidak ada sistem sertifikasi resmi/berbadan hukum.
2. Tidak ada sistem pembayaran/monetisasi.
3. Tidak ada aplikasi native mobile terpisah, cukup web app responsive.
4. Tidak ada dukungan multi-bahasa, fokus Bahasa Indonesia saja.
5. Tidak ada integrasi ke LMS eksternal (Moodle, Google Classroom, dsb.).
6. WEBI tidak melatih model AI sendiri, memanfaatkan API model yang sudah ada.
7. Modul Eksekusi tidak dirancang jadi tool manajemen proyek generik, khusus untuk proyek internal webdev RIT.

### 6.2 Batasan Tambahan yang Disepakati di Tahap Selanjutnya

8. Integrasi link repository GitHub (tautan otomatis ke repo proyek) disisihkan dari cakupan Eksekusi (dari 1.2).
9. Export portofolio ke PDF dihapus dari cakupan Eksplorasi (dari 1.2).
10. Dark/light mode dihapus, sistem cukup satu mode tampilan saja (dari 1.2).
11. Admin tidak berinteraksi dengan WEBI sebagai learner, hanya mengakses log (dari 1.5).

---

## 7. SKENARIO PENGGUNAAN (USE CASE) PER ROLE

Skenario berikut menggambarkan alur konkret bagaimana user memakai sistem. Skenario ini bisa langsung dipakai sebagai acuan testing di Fase 2.

### 7.1 Skenario Anggota Eksplorasi

#### Skenario 7.1a: Onboarding dan Memulai Belajar

**Aktor:** Agitsa (anggota eksplorasi, pemula total, bingung mulai dari mana, minat UI/UX).

**Alur:**
1. Agitsa login ke WEBI-SPACE untuk pertama kali menggunakan akun yang sudah didaftarkan.
2. Sistem menampilkan dashboard personal. Semua indikator masih kosong: Level 1 (Pengenal), 0 poin, 0% progres.
3. Agitsa membuka halaman kurikulum. Halaman Pendahuluan Kurikulum muncul: penjelasan bahwa kurikulum untuk pemula total, bisa dicicil 15 menit per hari, ada WEBI sebagai teman belajar, dan pemberitahuan bahwa chat WEBI dimonitor admin untuk membantu mendeteksi kesulitan belajar umum.
4. Agitsa masuk ke peta kurikulum. Modul 1 menyala sebagai titik awal, modul lain tampak redup.
5. Agitsa membuka Modul 1, Unit 1.1. Konten materi tampil.
6. Di antarmuka chat, WEBI mengirim sapaan pertama: "Halo! Aku WEBI, teman belajarmu di sini..." Notice kecil di atas chat menyebutkan bahwa percakapan bisa diakses PIC.
7. Agitsa membaca materi Unit 1.1, lalu mengerjakan evaluasi (kuis pilihan ganda). Memilih jawaban, submit. Poin 10 otomatis diberikan.
8. Pesan apresiasi muncul di log aktivitas: "Selamat! Kamu mendapatkan 10 poin karena menuntaskan Unit 1.1."
9. Unit 1.2 terbuka di peta.

#### Skenario 7.1b: Stuck dan Berinteraksi dengan WEBI

**Aktor:** Syifa (anggota eksplorasi, sulit mencerna materi sendiri, mudah minder, minat UI/UX).

**Alur:**
1. Syifa sedang di Unit 4.3 (tentang commit di Git). Sudah membaca materi tapi bingung soal perbedaan staging area dan commit.
2. Syifa membuka chat WEBI dan mengetik: "aku bingung, apa bedanya staging sama commit?"
3. WEBI merespons: "Wajar kok kalau bagian ini membingungkan. Coba bayangkan staging area itu kayak keranjang belanja..." (penjelasan dengan analogi sesuai gaya kurikulum, disesuaikan dengan posisi Syifa di Level 3).
4. Syifa masih belum paham, bertanya lagi: "tapi kenapa harus ada staging, kenapa nggak langsung commit aja?"
5. WEBI menjelaskan dari sudut berbeda, tanpa mengulangi analogi yang sama.
6. Syifa paham, kembali ke evaluasi unit, menyelesaikannya. Poin diberikan.
7. Tiga hari kemudian, Syifa belum membuka unit baru. Di hari kelima, WEBI mengirim sapaan proaktif Trigger 2: "Hei, terakhir kamu di Unit 4.3. Kalau ada yang bikin bingung, tanya aja. Kalau belum sempat, santai, lanjut kapan kamu siap."

#### Skenario 7.1c: Menyelesaikan Checkpoint dan Naik Level

**Aktor:** Falaah (anggota eksplorasi, waktu padat tapi konsisten belajar pelan-pelan).

**Alur:**
1. Falaah sudah menyelesaikan seluruh unit di Modul 2. Tinggal Checklist Akhir Modul dan Intermezo.
2. Falaah membuka Checklist Akhir Modul 2: daftar pernyataan pemahaman yang dicentang satu per satu.
3. Falaah mengerjakan Intermezo Modul 2 (kuis pemahaman gabungan Modul 1-2).
4. Falaah mengisi Form Tanggapan Modul 2.
5. Checkpoint selesai. 25 poin bonus diberikan. Total poin Falaah melewati threshold Level 2.
6. WEBI mengirim sapaan Trigger 5: "Modul 2 selesai! Kamu sekarang sudah paham cara website bekerja. Modul berikutnya tentang menyiapkan peralatan kerja developer. Siap lanjut?"
7. WEBI mengirim sapaan Trigger 4: "Selamat, kamu sekarang Level 2 (Penyiap)! Di level ini kamu akan belajar menyiapkan tools development di perangkatmu."
8. Di peta kurikulum, area Modul 3 berubah tampilan, menandakan terbuka.

#### Skenario 7.1d: Menggunakan WEBI Mode Suara

**Aktor:** Dipa (anggota eksplorasi, device terbatas, kadang lebih nyaman bicara daripada mengetik).

**Alur:**
1. Dipa sedang belajar Unit 6.2 (tag-tag HTML dasar) dari ponselnya.
2. Dipa menekan tombol mikrofon di chat WEBI dan bertanya lewat suara: "WEBI, aku bingung, apa bedanya tag div sama span?"
3. STT mengkonversi suara ke teks. Transkrip muncul di chat: "aku bingung apa bedanya tag div sama span." Dipa cek, sudah benar.
4. WEBI memproses (VOICE_MODE=true). Merespons lebih ringkas: "Div itu untuk mengelompokkan elemen dalam blok terpisah, kayak kotak besar. Span itu untuk menandai bagian kecil di dalam teks, kayak stabilo. Coba lihat contoh kodenya di chat ya." Audio respons diputar sekaligus teks muncul di chat.
5. Contoh kode HTML tampil sebagai teks di chat (tidak dibacakan lewat suara).
6. Dipa mengetik pertanyaan lanjutan (switch ke mode teks): "kalau article sama section bedanya apa?" WEBI merespons dalam mode teks biasa (lebih panjang dan detail).

### 7.2 Skenario Anggota Eksekusi

#### Skenario 7.2a: Mengusulkan Ide, Ide Di-Approve, Bergabung ke Proyek

**Aktor:** Azmi (anggota eksekusi, minat fullstack dan PM, termotivasi proyek riil).

**Alur:**
1. Azmi login, masuk ke modul Eksekusi. Membuka menu Project Ideas.
2. Azmi mengisi form: judul "Website Portfolio Divisi Webdev RIT", deskripsi singkat tentang kebutuhan menampilkan profil divisi, tujuan "supaya divisi webdev punya presence online yang profesional."
3. Ide tersimpan dengan status `draft`. Activity log mencatat. Azmi menunggu review.
4. Admin (Ayunda) membuka Project Ideas, melihat ide Azmi. Menilai layak, klik Approve.
5. Sistem otomatis membuat Project baru dari data ide. Azmi menerima notifikasi: "Ide kamu 'Website Portfolio Divisi Webdev RIT' sudah di-approve dan menjadi proyek aktif."
6. Admin setup proyek: menambahkan deskripsi detail, tipe `internal`, menambahkan Azmi dan Ahmad Basir sebagai anggota tim, mendefinisikan 3 milestone ("Setup dan Desain", "Development", "Testing dan Deploy"), set tanggal.
7. Azmi dan Ahmad Basir menerima notifikasi: "Kamu ditambahkan ke proyek 'Website Portfolio Divisi Webdev RIT'."

#### Skenario 7.2b: Mengerjakan Task dari Todo sampai Done (Termasuk Revisi)

**Aktor:** Ahmad Basir (anggota eksekusi, paling konsisten, bisa diandalkan untuk task rutin).

**Alur:**
1. Admin membuat task "Buat halaman utama" di bawah Milestone 2 (Development), assign ke Ahmad Basir, deadline 2 minggu, prioritas `medium`.
2. Ahmad Basir menerima notifikasi: "Task baru di-assign ke kamu: Buat halaman utama."
3. Ahmad Basir membuka Kanban board. Melihat kartu task di kolom Todo. Drag ke kolom In Progress (status berubah ke `in_progress`).
4. Selama mengerjakan, Ahmad Basir menambahkan komentar: "Pakai layout flexbox untuk section hero, setuju?" Admin membalas di komentar.
5. Ahmad Basir mengirim progress update: "Section hero dan about sudah selesai, tinggal footer. Kendala: belum dapat konten foto tim."
6. Setelah selesai, Ahmad Basir drag kartu ke kolom In Review (status `in_review`). Admin menerima notifikasi.
7. Admin review. Menemukan footer kurang responsive. Admin tambah komentar: "Footer-nya di mobile masih nabrak, coba pakai media query di breakpoint 768px." Admin pindahkan status kembali ke `in_progress`.
8. Ahmad Basir menerima notifikasi revisi. Membuka komentar, melihat feedback. Memperbaiki footer.
9. Ahmad Basir submit ulang ke `in_review`. Admin review lagi, kali ini lolos. Admin pindahkan ke `done`.

#### Skenario 7.2c: Mengirim Progress Update

**Aktor:** Azmi (anggota eksekusi, di-assign task "Setup database schema").

**Alur:**
1. Azmi sedang mengerjakan task "Setup database schema" (status `in_progress`).
2. Azmi membuka detail task, klik "Kirim Progress Update."
3. Mengisi form: "Tabel users dan projects sudah selesai. Tabel tasks masih progress, bingung soal relasi milestone ke task." Melampirkan link ke file SQL di Google Drive.
4. Progress update tersimpan sebagai entry baru (append-only, tidak menimpa update sebelumnya).
5. Admin menerima notifikasi: "Azmi mengirim progress update di task 'Setup database schema'."
6. Di dashboard admin, update ini muncul di Feed Progress Update Terbaru (Komponen C).

### 7.3 Skenario Admin

#### Skenario 7.3a: Memantau Anggota Eksplorasi yang Tidak Aktif

**Aktor:** Ayunda (admin/PIC).

**Alur:**
1. Ayunda membuka admin panel terpusat, section Eksplorasi.
2. Melihat daftar progres semua anggota. Dipa terlihat hanya menyelesaikan 3 unit dalam 2 minggu terakhir, sementara anggota lain rata-rata 6-8 unit.
3. Ayunda cek leaderboard admin-only untuk gambaran cepat. Dipa ada di bawah, tapi Ayunda tahu ini bukan soal kemampuan (dari riset: Dipa terkendala device dan jadwal ngajar madrasah).
4. Ayunda buka log percakapan WEBI milik Dipa. Melihat Dipa beberapa kali bertanya soal instalasi VS Code tapi belum berhasil (kemungkinan masalah device).
5. Ayunda memutuskan menghubungi Dipa secara personal (di luar sistem) untuk menawarkan bantuan akses lab kampus.

#### Skenario 7.3b: Memantau Proyek Eksekusi yang Mandek dan Intervensi

**Aktor:** Ayunda (admin/PIC).

**Alur:**
1. Ayunda membuka admin panel, section Eksekusi. Dashboard menampilkan proyek "Website Portfolio Divisi Webdev RIT" berstatus `active`.
2. Di Alert Panel (Komponen B), terlihat flag STALLED di task "Desain halaman project showcase" yang di-assign ke Azmi (tidak ada aktivitas 8 hari).
3. Ayunda juga melihat flag INACTIVE MEMBER: Riefki tidak ada aktivitas di semua task selama 15 hari.
4. Ayunda klik task Azmi yang STALLED. Menambahkan komentar: "Azmi, ada kendala di task ini? Kalau perlu bantuan desain, kita diskusi."
5. Untuk Riefki, Ayunda tahu dari riset bahwa Riefki sangat sibuk. Ayunda re-assign task Riefki ke Ahmad Basir. Riefki menerima notifikasi bahwa task-nya dipindahkan. Ahmad Basir menerima notifikasi task baru.
6. Satu minggu kemudian, seluruh tim sedang UTS. Ayunda mengubah status proyek ke `on_hold`. Semua notifikasi deadline dan alert di-suppress. Anggota menerima notifikasi status proyek berubah.

#### Skenario 7.3c: Me-review dan Approve/Reject Ide Proyek

**Aktor:** Ayunda (admin/PIC).

**Alur:**
1. Ayunda menerima notifikasi: "Ide baru diinput: Aplikasi Kasir Kantin."
2. Ayunda buka Project Ideas, melihat ide dari Ahmad Basir. Membaca deskripsi dan tujuan.
3. Ayunda menilai ide ini di luar scope divisi webdev (lebih ke mobile app). Klik Reject.
4. Sistem memunculkan field wajib: "Alasan penolakan." Ayunda mengisi: "Ide bagus, tapi ini lebih ke mobile app dan di luar fokus divisi webdev saat ini. Coba usulkan ide yang bisa jadi web app internal divisi."
5. Ahmad Basir menerima notifikasi: "Ide kamu 'Aplikasi Kasir Kantin' di-reject. Alasan: [alasan]."

#### Skenario 7.3d: Memonitor Log Chat WEBI untuk Mendeteksi Kesulitan Umum

**Aktor:** Ayunda (admin/PIC).

**Alur:**
1. Ayunda membuka section monitoring WEBI di admin panel.
2. Melihat ringkasan: 5 anggota aktif bertanya minggu ini, 4 anggota tidak ada interaksi dengan WEBI.
3. Melihat daftar guardrail flag: 3 kali perlindungan evaluasi ter-trigger di Unit 4.2 (kuis tentang perintah Git dasar). Ini menandakan beberapa anggota mencoba menanyakan jawaban kuis ke WEBI.
4. Melihat pola pertanyaan umum: 4 anggota bertanya variasi pertanyaan "apa bedanya staging dan commit" di Modul 4. Ini sinyal bahwa penjelasan di kurikulum mungkin perlu diperkuat.
5. Ayunda membuat catatan untuk memperbaiki penjelasan staging area di Unit 4.3 saat iterasi kurikulum di Fase 3.

---

## LAMPIRAN A: PARAMETER CONFIGURABLE

Seluruh parameter berikut punya nilai default yang bisa diubah oleh admin lewat konfigurasi sistem, tanpa perlu mengubah kode.

### Parameter WEBI (dari Spesifikasi 1.5)

| Parameter | Nilai Default | Keterangan |
|-----------|---------------|------------|
| Stagnasi progres (Trigger 2) | 5 hari | Jumlah hari tanpa menyelesaikan unit baru sebelum sapaan proaktif dikirim |
| Stuck di unit (Trigger 3) | 3 kali | Jumlah kali user membuka unit tanpa menyelesaikan evaluasi sebelum WEBI menawarkan bantuan |
| Stuck di topik (Trigger 3 alternatif) | 3 kali | Jumlah kali user bertanya topik yang sama lintas sesi |
| Cooldown sapaan proaktif | 3 hari | Jeda setelah sapaan yang tidak direspons sebelum sapaan berikutnya dikirim |
| Batas tidak responsif sapaan | 3 kali berturut-turut | Jumlah sapaan tidak direspons sebelum WEBI berhenti mengirim sapaan tipe nudge |
| Maksimal sapaan proaktif per hari | 1 per user | |
| Rate limiting pesan WEBI | 50 pesan per hari per user | |
| Context window riwayat chat | 20 pesan terakhir | Jumlah pesan dari sesi aktif yang di-inject ke konteks model AI |
| Sesi timeout | 30 menit | Jeda dari pesan terakhir sebelum sesi baru dimulai |
| Similarity threshold validasi backend | 0.85 | Threshold cosine similarity untuk mendeteksi jawaban evaluasi di respons WEBI |
| Frekuensi rekomendasi materi | 1 per 3 pesan | Maksimal rekomendasi materi lanjutan yang disisipkan WEBI |

### Parameter Eksekusi (dari Struktur Sistem 1.4)

| Parameter | Nilai Default | Keterangan |
|-----------|---------------|------------|
| Task STALLED | 7 hari | Jumlah hari tanpa aktivitas di task `in_progress` sebelum flag STALLED muncul |
| INACTIVE MEMBER | 14 hari | Jumlah hari tanpa aktivitas di semua task sebelum flag INACTIVE muncul |
| DUE SOON | 3 hari | Jumlah hari sebelum deadline task yang memicu flag DUE SOON |
| PROJECT IDLE | 14 hari | Jumlah hari tanpa activity log entry di proyek `active` sebelum flag PROJECT IDLE muncul |

---

**AKHIR DOKUMEN PRD WEBI-SPACE.**

Dokumen ini mengkonsolidasikan seluruh keputusan dari tahap 1.2 (Fiksasi Fitur), 1.3 (Blueprint Kurikulum), 1.4 (Struktur Sistem Eksekusi), dan 1.5 (Spesifikasi Fungsional WEBI), ditambah empat keputusan baru yang ditetapkan di PRD ini:

1. Mekanisme penilaian evaluasi esai dan praktik: auto-approve, poin langsung diberikan saat submit.
2. Forum diskusi Eksplorasi diorganisasi per modul/unit, hanya untuk `exploration_member` dan `admin` (lihat koreksi di 5.16 — bukan fitur lintas modul).
3. Transparansi monitoring chat WEBI: pemberitahuan eksplisit saat onboarding dan di antarmuka chat.
4. Business rules tambahan yang diidentifikasi dari dokumen rujukan: satu user satu role, on_hold menekan notifikasi, ProgressUpdate dan ActivityLog immutable.

Siap menjadi acuan untuk tahap selanjutnya: arsitektur database (1.7) dan desain UI/UX (1.8).