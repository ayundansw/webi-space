# Rancangan Modul Manajemen Proyek — WEBI-SPACE v2.0

**Bagian dari:** 2.1.3 Definisi Konsep Baru
**Status:** Final, siap masuk 2.1.2 (Fiksasi Fitur v2.0)

---

## 1. Filosofi

Modul Eksekusi v1.0 cuma cukup buat kebutuhan dasar (Kanban, task, komentar). v2.0 dirombak jadi sistem manajemen proyek penuh, terdiri dari tiga pilar: Manajemen Waktu (Kalender, Roadmap, Gantt), Manajemen Tugas (Milestone, Kanban, status), Manajemen Sumber Daya (subtask, forum, informasi). Ini scope terbesar di seluruh v2.0, dijadwalkan sebagai unit kerja tersendiri di tahap implementasi, terpisah dari modul lain.

---

## 2. Struktur Halaman

### Halaman Utama Proyek: navigasi tab, bukan satu halaman panjang

Tab: **Kanban** (default), **Roadmap**, **Gantt**, **Kalender**, **Forum Proyek**, **Anggota**. Pindah tab tanpa reload halaman penuh.

### Detail Task: panel geser dari samping (slide-over), bukan halaman baru

Klik card task di Kanban, detail muncul sebagai panel geser dari kanan, bukan pindah halaman. Semua interaksi task (lihat, update status, komentar, subtask) terjadi di panel ini. Alasan: task dibuka-tutup berulang kali dalam satu sesi kerja, panel geser jauh lebih cepat dibanding pindah halaman penuh tiap kali.

### Dashboard Admin: tetap terpisah dari halaman proyek

Dashboard admin (ringkasan seluruh proyek, sudah ada dari 2.4/2.6) tetap jadi titik masuk pertama, dari situ klik satu proyek untuk masuk ke halaman utama proyek (dengan tab di atas).

---

## 3. Manajemen Waktu

### Kalender

Menampilkan dua kategori, warna mengikuti palet RIT (`docs/design-tokens.md`):

| Kategori | Warna | Isi |
|---|---|---|
| **Kegiatan** | `#1C1515` (gelap) | Otomatis muncul dari deadline task dan milestone yang sudah ada, tidak disimpan dobel, cuma ditarik dari data yang sama dipakai Kanban/Roadmap |
| **Acara** | `#05D9E7` (aksen cyan) | Diinput manual oleh anggota/admin (kompetisi, meeting, kumpulan rutin, hal organisasi di luar progress kerja) |

Acara tersimpan di tabel baru `calendar_events` (judul, tanggal, deskripsi, proyek terkait/opsional kalau sifatnya umum). Kegiatan tidak punya tabel sendiri, dihitung on-the-fly dari Task dan Milestone, sama pola dengan Log Aktivitas Eksplorasi di v1.0.

**Kalender dibuat terpadu, dua tingkat tampilan dari sumber data yang sama:**
- **Tab Kalender di halaman proyek**: menampilkan Kegiatan dan Acara khusus proyek itu saja (filter `project_id`).
- **Kalender Personal** (halaman tersendiri, bisa diakses lewat menu utama Eksekusi, bukan di dalam tab satu proyek): menggabungkan Kegiatan dan Acara dari SEMUA proyek yang diikuti user yang login, plus Acara yang sifatnya umum/tidak terikat proyek tertentu (`project_id` null). Ini yang dimaksud "terpadu secara menyeluruh", relevan untuk anggota yang ikut lebih dari satu proyek sekaligus, supaya tidak perlu buka kalender tiap proyek satu-satu.

Query dua tampilan ini sama-sama menarik dari `calendar_events` plus Task/Milestone, beda cuma di filter cakupannya, tidak ada duplikasi data.

### Roadmap

Timeline milestone level tinggi (bukan per-task), gambaran besar fase proyek dari awal sampai selesai. Data dari tabel Milestone yang sudah ada.

### Gantt Chart

Bar per task sesuai tanggal mulai-selesai, mendukung **dependency** (task bisa ditandai blocked-by/blocks task lain). Kalau task yang jadi dependency molor, sistem kasih **indikator visual** (bukan auto-reschedule) di task yang bergantung padanya. Butuh tabel baru `task_dependencies` (pivot: `task_id`, `depends_on_task_id`), dengan validasi mencegah circular dependency (A depends on B, B depends on A).

---

## 4. Manajemen Tugas

### Kanban

Board utama tetap bersih, cuma menampilkan task besar (bukan subtask). Kolom status seperti yang sudah ada dari v1.0.

### Milestone

Tetap seperti v1.0, jadi acuan Roadmap.

### Subtask

Task mini dengan assignee dan status sendiri, **nested di dalam panel detail task induk**, tidak muncul di board Kanban utama. Secara data, subtask pakai tabel Task yang sama dengan `parent_task_id` (self-referencing, nullable), bukan tabel terpisah. Di dalam panel detail task induk, subtask ditampilkan sebagai mini-list dengan status masing-masing, bukan mini-Kanban penuh (supaya tidak berlebihan untuk unit sekecil subtask).

---

## 5. Manajemen Sumber Daya

### Forum, dua kategori

- **Forum General**: satu forum umum, tidak terikat proyek tertentu, buat info lomba, pengumuman, diskusi lintas proyek.
- **Forum Proyek**: satu thread khusus per proyek, muncul sebagai tab di halaman utama proyek, isinya diskusi kerja proyek itu.

**Akses:** kedua kategori forum ini cuma bisa diakses `execution_member` dan `admin`. `exploration_member` dikecualikan sepenuhnya dari dua-duanya, sama seperti forum Eksplorasi yang dikecualikan dari akses `execution_member` di v1.0 (pola RBAC timbal balik yang konsisten).

Secara data, dua kategori ini dibedakan lewat `project_id` yang nullable di tabel forum thread yang sudah ada, bukan bikin tabel forum baru terpisah. Forum General beda dari Forum Eksplorasi (itu sudah ada sejak v1.0, khusus konteks belajar), ini forum baru khusus konteks Eksekusi/organisasi.

### Form Hasil Penugasan + Feedback

Sudah ada sejak v1.0 lewat Progress Update (laporan formal terpisah dari komentar). Dipertahankan, tidak berubah strukturnya.

### Informasi

Menu info kompetisi/pengumuman relevan tim eksekusi, bisa dipetakan ke Forum General di atas atau jadi section tersendiri di dashboard proyek, diputuskan detailnya pas tahap implementasi.

---

## 6. Catatan Skala Pekerjaan

Modul ini menyentuh hampir semua tabel Eksekusi yang sudah ada (Task, Milestone, ForumThread) plus dua tabel baru (`calendar_events`, `task_dependencies`) dan satu perubahan skema (`parent_task_id` di Task). Direkomendasikan dipecah jadi beberapa batch kerja terpisah saat implementasi nanti (minimal: fondasi data dan Kanban dulu, baru Gantt+dependency, baru Kalender+Roadmap, baru Forum dan subtask), bukan dikerjakan sekaligus dalam satu perintah besar, mengikuti pola checkpoint yang sudah terbukti jalan sepanjang v1.0.
