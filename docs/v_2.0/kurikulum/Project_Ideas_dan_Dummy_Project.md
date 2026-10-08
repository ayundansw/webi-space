# Draft: Project Ideas + Proyek Dummy — Portal Eksekusi (Bagian 12-13)

Status: DRAFT untuk direview Aye. Belum dieksekusi ke Claude Code.

---

## BAGIAN A — Project Ideas (minimal 3, aku kasih 4)

Semua diusulkan atas nama akun Admin (`ayundanasywaher@gmail.com`) sebagai `proposed_by`, status `approved` (siap dipromosikan jadi proyek nyata kapan pun tim siap) — bukan `draft`, karena ini bukan sekadar ide mentah tapi proposal yang sudah dipikirkan matang. Kalau kamu mau statusnya tetap `draft` dulu, tinggal bilang.

### 1. Sistem Deteksi dan Prioritas Otomatis Kerusakan Fasilitas Kampus
**Purpose:** Kerusakan fasilitas kampus (AC, proyektor, lampu, WiFi per titik akses) biasanya dilaporkan lewat grup WhatsApp atau form kertas yang gampang tenggelam dan tidak ada yang bertanggung jawab menindaklanjuti. Aplikasi ini menjawab kebutuhan pelaporan yang terlacak dan transparan.
**Description:** Mahasiswa/staf melaporkan kerusakan lewat foto + lokasi. Sistem menghitung skor prioritas otomatis berdasar kombinasi: frekuensi laporan serupa di titik yang sama, kategori ruangan (lab/kelas praktikum diberi bobot lebih tinggi dari ruang non-akademik), dan lama waktu sejak laporan pertama masuk tanpa ditindaklanjuti. Ada dashboard publik yang menunjukkan status tiap laporan (masuk/diproses/selesai) supaya pelapor tidak perlu bertanya-tanya lagi progresnya. Niche-nya ada di algoritma skor prioritas yang disesuaikan konteks kampus, bukan sekadar sistem tiket generik.

### 2. Time Bank: Marketplace Barter Skill Antar Mahasiswa
**Purpose:** Banyak mahasiswa punya skill yang bisa saling membantu (les privat, desain, edit foto/video, proofreading) tapi tidak semua bisa membayar jasa itu dengan uang. Sistem barter berbasis waktu menjawab kesenjangan ini tanpa perlu transaksi uang sama sekali.
**Description:** Setiap anggota punya "saldo jam" yang didapat dari memberi jasa ke orang lain (1 jam mengajar = 1 jam kredit), lalu kredit itu dipakai untuk menukar jasa dari anggota lain, terlepas dari jenis skillnya (1 jam desain = 1 jam bantuan matematika, nilainya setara karena diukur dari waktu bukan skill yang "lebih mahal"). Niche-nya ada di model ekonomi barter waktu (time banking), sesuatu yang jarang diimplementasikan dengan baik di skala kampus Indonesia, beda dari marketplace jasa berbasis uang pada umumnya.

### 3. Pencocokan Kelompok Belajar Berdasar Gaya Belajar dan Jadwal Kosong
**Purpose:** Aplikasi pencari teman belajar yang sudah ada biasanya cuma mencocokkan berdasar mata kuliah yang sama, hasilnya kelompok belajar sering tidak cocok karena gaya belajar dan waktu luang anggotanya berbeda-beda.
**Description:** Anggota mengisi profil gaya belajar (visual/auditori/kinestetik) dan jadwal kelas mingguan. Sistem mencari irisan waktu kosong antar anggota DAN kecocokan gaya belajar sebelum membentuk kelompok otomatis, bukan sekadar mencocokkan mata kuliah. Niche-nya ada di algoritma pencocokan dua dimensi (waktu + gaya belajar) sekaligus, lebih canggih dari sekadar filter mata kuliah.

### 4. Bank Sampah Digital dengan Jaringan Tukar Poin ke UMKM Sekitar Kampus
**Purpose:** Program bank sampah sering berhenti di tahap pencatatan setoran sampah tanpa insentif nyata yang dirasakan langsung, sehingga partisipasinya cepat menurun.
**Description:** Setoran sampah dicatat lewat aplikasi dan dikonversi jadi poin, tapi bedanya dari bank sampah digital pada umumnya, poin ini bisa ditukar langsung ke warung, laundry, atau fotokopi sekitar kampus yang jadi mitra jaringan (bukan cuma ditukar uang tunai kembali di titik bank sampah). Niche-nya ada di integrasi ekonomi hyperlocal antara program lingkungan dan UMKM sekitar kampus, nilai sosial dan lingkungannya tinggi.

---

## BAGIAN B — Proyek Dummy (Manajemen Proyek Lengkap)

**Kontributor HANYA 2 akun:** `ayundanasywaa@gmail.com` (Ayunda, sebagai Eksekusi) dan `eksekusiuser@example.com` (Eksekusi User). `created_by` = akun Ayunda Eksekusi.

Idenya aku pilih yang aman dan masuk akal sebagai demo, sekaligus punya nilai nyata: **redesain alur onboarding anggota baru RIT Webdev** — relevan karena ini benar-benar masalah yang muncul di hasil riset gform kamu (anggota baru bingung mulai dari mana). Judul diberi suffix `[Demo]` konsisten dengan konvensi yang sudah ada di kode (`SeedEksekusiDemo.php`), supaya jelas ini data demo bukan proyek produksi sungguhan.

### Project: "Redesain Alur Onboarding Anggota Baru RIT Webdev [Demo]"
`project_type: internal, status: active, start_date: 2026-07-01, target_end_date: 2026-08-15`
**Description:** Merancang ulang alur penyambutan dan orientasi anggota baru Divisi Web Development supaya tidak lagi bingung harus mulai dari mana begitu bergabung.
**Objective:** Mengurangi waktu adaptasi anggota baru dan meningkatkan kejelasan langkah awal yang perlu diambil setelah bergabung ke divisi.

**Milestone 1: Riset dan Rancangan Alur Onboarding** (`target_date: 2026-07-25, sort_order: 1`)
| Task | Status | Prioritas | Deadline | Assignee | Ketergantungan |
|---|---|---|---|---|---|
| Audit alur onboarding member baru saat ini | done | medium | 2026-07-18 | Ayunda (Eksekusi) | - |
| Wawancara singkat 3 anggota baru soal pengalaman onboarding | in_progress | high | 2026-07-22 | Eksekusi User | - |
| ⤳ Subtask: Susun daftar pertanyaan wawancara | done | - | 2026-07-19 | Eksekusi User | - |
| ⤳ Subtask: Rangkum hasil wawancara jadi insight | todo | - | 2026-07-23 | Eksekusi User | - |
| Rancang alur onboarding baru (diagram alur) | todo | high | 2026-07-25 | Ayunda (Eksekusi) | Depends on: "Wawancara singkat 3 anggota baru" |

**Milestone 2: Implementasi dan Uji Coba Alur Baru** (`target_date: 2026-08-15, sort_order: 2`)
| Task | Status | Prioritas | Deadline | Assignee | Ketergantungan |
|---|---|---|---|---|---|
| Implementasi perubahan halaman welcome/checklist onboarding | todo | medium | 2026-08-05 | Ayunda (Eksekusi) | - |
| Uji coba alur baru dengan 2 anggota baru simulasi | todo | medium | 2026-08-10 | Eksekusi User | Depends on: "Implementasi perubahan halaman welcome/checklist" |
| Dokumentasikan alur onboarding final ke panduan member baru | todo | low | 2026-08-15 | Ayunda (Eksekusi) | - |

Total: 2 Milestone, 6 Task induk + 2 Subtask (8 baris `tasks`), 2 `task_dependencies`, 2 `project_members`. Variasi status (done/in_progress/todo) dan prioritas sengaja dibuat beragam supaya Kanban, Gantt, dan Calendar semuanya punya data untuk ditampilkan, bukan kosong/seragam.

---

## Catatan untuk Aye sebelum ACC
1. Status Project Ideas aku usulkan `approved` (bukan `draft`) — tandanya siap dipromosikan tim kapan saja. Kalau mau tetap `draft` sampai didiskusikan dulu ke anggota, tinggal bilang.
2. `proposed_by` keempat ide di atas aku pakai akun Admin (kamu). Kalau mau atas nama anggota Eksekusi tertentu, sebutkan.
3. Proyek dummy ini TIDAK terhubung ke ide mana pun di atas (`originated_from_idea_id: null`) karena ini murni proyek demo, bukan realisasi salah satu ide Bagian A.
4. Kalau semua di atas sudah oke, aku lanjut susun prompt master untuk Claude Code (seperti pola Praktik kemarin).
