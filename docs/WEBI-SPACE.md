# WEBI-SPACE — Informasi Teknis & Non-Teknis Proyek

**Sifat dokumen ini:** referensi STRUKTURAL yang paling sedikit berubah dari waktu ke waktu — gambaran proyek, tech stack, design system, pointer arsitektur database, dan catatan deployment. Ini BUKAN dokumen riwayat pengerjaan — untuk histori "apa yang dibangun kapan dan kenapa", lihat `docs/WEBI-v1.0.md` (rilis pertama) dan `docs/WEBI-v2.0.md` (redesign + fitur lanjutan, per fase). Untuk instruksi kerja harian Claude Code, lihat `CLAUDE.md` (tidak disentuh oleh konsolidasi ini, tetap berdiri sendiri).

Dokumen ini adalah hasil konsolidasi dari 8 file sumber (lihat "Sumber Dokumen Ini" di bagian akhir) — isinya dipertahankan penuh, terutama checklist deployment dan catatan troubleshooting yang bersifat actionable/runbook, sengaja TIDAK diringkas lossy.

---

## 1. Gambaran Umum Proyek

**WEBI-SPACE** adalah web app internal untuk Divisi Web Development sebuah organisasi kampus (RIT). Penggunanya adalah anggota divisi — mahasiswa yang belajar web development, mayoritas pemula total dan sebagian mudah minder (temuan riset awal proyek). Tiga modul utama, masing-masing dengan role akses sendiri:

- **Eksplorasi** — LMS (Learning Management System) internal: kurikulum terstruktur 10 modul, evaluasi (kuis/esai/praktik), sistem poin & level gamifikasi, forum diskusi, referensi belajar.
- **Eksekusi** — manajemen proyek: dari usulan ide, proyek nyata, task/Kanban, sampai monitoring progres oleh admin (PIC).
- **WEBI** — AI companion kontekstual: membantu anggota Eksplorasi belajar lewat chat (teks/suara), dengan guardrail supaya tidak membocorkan kunci jawaban evaluasi.

Role: `exploration_member`, `execution_member`, `admin`. Sejak v2.0, ada mekanisme **Mode Ganda** yang memungkinkan anggota memiliki akses tambahan ke portal lain (lihat `docs/WEBI-v2.0.md` Fase 8 untuk detail lengkap) — tapi role ASAL tetap satu-satunya identitas dasar yang tidak pernah berubah.

Aplikasi dikerjakan bertahap: v1.0 (rilis pertama, fondasi ketiga modul) lalu v2.0 (redesign visual besar-besaran + modul baru: Konten Dinamis, Manajemen Proyek lanjutan, Praktik, Mode Ganda). Riwayat lengkap keduanya ada di dua dokumen terpisah yang disebut di atas.

---

## 2. Tech Stack

### 2.1 Framework dan Bahasa

**Backend dan Frontend: Laravel (PHP), dengan Livewire + Alpine.js untuk interaktivitas.**

Laravel menangani seluruh logic backend (autentikasi, RBAC, logic Eksplorasi dan Eksekusi). Untuk bagian yang butuh interaktivitas (progress tracker real-time, update status task, dst), dipakai Livewire — komponen reaktif tanpa perlu membangun API terpisah dan framework JS penuh seperti React/Vue. Alpine.js dipakai untuk interaksi kecil di sisi browser (dropdown, modal, toggle) yang tidak butuh round-trip ke server.

**Alasan pemilihan:**
- Jalan native di hosting rumahweb (PHP shared hosting), tidak perlu setup tambahan apa pun untuk deploy.
- Sesuai persis dengan environment lokal (Laragon, MySQL, phpMyAdmin).
- Claude Code sangat familiar dengan Laravel, minim risiko hasil kode yang aneh atau tidak stabil.
- Livewire menghindari kompleksitas mengelola dua codebase terpisah (backend API + frontend SPA), cocok untuk solo dev.

**Catatan drag-and-drop:** kalau fitur Kanban board terasa kaku dengan Livewire murni, opsinya tambah library kecil seperti SortableJS di sisi Alpine — tidak perlu ganti stack. (Pada praktiknya, Kanban board v2.0 dibangun dengan HTML5 native drag-and-drop, bukan library tambahan — lihat `docs/WEBI-v2.0.md`.)

### 2.2 Database

**MySQL.** Alasan: bawaan default Laragon dan rumahweb, tidak perlu instalasi tambahan. Notasi tipe data generik di skema ERD langsung bisa dipetakan ke tipe MySQL tanpa penyesuaian besar. Skema lengkap ada di `docs/arsitektur-database.md` — lihat Bagian 4 di bawah untuk pointer detailnya.

### 2.3 Integrasi API WEBI

**Model AI: Google Gemini API.**

Alasan: free tier Gemini cukup untuk skala pemakaian WEBI-SPACE (~12 anggota, bukan aplikasi publik besar), jadi bisa validasi dulu apakah konsep WEBI efektif tanpa keluar biaya di awal. Arsitektur WEBI Service (layer terpisah untuk context assembly dan pemanggilan API) membuat provider ini bisa diganti ke Anthropic/OpenAI nanti kalau kualitas/kebutuhan berubah, tanpa rombak struktur sistem — cukup ganti bagian pemanggilan API-nya saja.

**Mekanisme teknis:** pemanggilan API dilakukan dari backend Laravel (HTTP Client/Guzzle bawaan Laravel), bukan langsung dari browser — supaya API key tidak pernah terekspos ke publik lewat kode sisi client.

**STT/TTS: Web Speech API bawaan browser (gratis).** Cukup untuk versi awal, kualitas standar tapi fungsional. Bisa upgrade ke layanan berbayar (Google Cloud Speech, dsb) nanti kalau kualitas suara jadi masalah nyata di pemakaian riil.

**Catatan operasional (v2.0):** thinking level Gemini di-set `minimal` (bukan default) setelah ditemukan timeout ~20 detik saat model masih "thinking" pada level lebih tinggi — lihat `CLAUDE.md` untuk detail perbandingan latency kalau perlu mengubah nilai ini lagi.

### 2.4 Tools Pendukung Development

- **Version control:** Git, repository **public** di GitHub (bukan private — awalnya direncanakan privat, tapi diubah saat troubleshooting deployment 1.10 karena SSH key untuk clone repo privat gagal berkali-kali; lihat Bagian 5.2 di bawah). Risiko keamanan dianggap kecil untuk proyek internal ini karena `.env` tidak pernah ikut ter-push.
- **Deployment:** fitur Git Version Control di cPanel rumahweb (deploy lewat pull dari repo, di-clone langsung ke `public_html/webi-space` karena repo public tidak butuh autentikasi), dengan upload manual via File Manager sebagai fallback. Domain: `rit-base.online`.
- **Local development:** Laragon — web server, PHP, MySQL, dan phpMyAdmin dalam satu paket.

**Catatan penting soal kredensial:** API key WAJIB disimpan di file `.env`, jangan pernah di-commit ke GitHub. Repo ini **public**, jadi risiko kebocoran kredensial jauh lebih besar dan permanen (riwayat git tetap menyimpannya walau commit berikutnya menghapusnya) — jangan pernah taruh key/kredensial apa pun langsung di kode.

**Catatan skalabilitas:** kalau WEBI-SPACE melebihi kapasitas shared hosting Unlimited M (traffic tinggi, aplikasi lambat), upgrade ke paket hosting rumahweb yang lebih tinggi tetap tersedia tanpa perlu pindah provider/migrasi besar — stack (PHP/MySQL) didukung di semua tingkatan paket mereka.

---

## 3. Design System

Design system WEBI-SPACE adalah **satu sistem token yang berkembang bertahap**, bukan dua sistem yang bersaing: **v1** adalah fondasi yang ditetapkan di awal proyek (tahap 1.8), **v2** adalah evolusi "Hangat, Playful, Profesional" yang menambah (bukan mengganti) v1, dipicu evaluasi usability testing v1.0 yang bilang tampilan terasa **"terlalu generik AI / vibe-coding"**. Sampai saat ini (2026-07-13), v2 sudah diterapkan di sebagian besar halaman aplikasi (jauh lebih luas dari status "baru 1 halaman" yang tercatat di draf awal `design-tokens-v2.md` — sepanjang pengerjaan v2.0, kelas seperti `shadow-warm-xs`, `rounded-card`, `bg-danger-soft`, `text-success` dipakai konsisten di Kanban, Forum, seluruh panel Admin, dan lainnya), tapi v1 TETAP valid sebagai fondasi filosofi dan masih dipakai apa adanya di halaman yang belum sempat di-reskin eksplisit.

### 3.1 Filosofi Dasar (v1 — fondasi, baca dulu sebelum apa pun)

WEBI-SPACE dipakai anggota yang mayoritas pemula total dan sebagian mudah minder. Karena itu, arah visualnya **terang dan hangat, bukan gelap dan dingin**, walau warna brand RIT condong ke tema "tech". Latar dominan putih/pucat, bukan hitam. Hitam dan cyan dipakai sebagai aksen yang dijaga ketat, bukan disebar rata ke seluruh halaman.

**Larangan eksplisit, jangan generate salah satu pola ini:**
- Background nyaris hitam dengan satu warna aksen neon menyala di mana-mana (dark mode SaaS generic).
- Layout broadsheet/koran dengan garis tipis di mana-mana dan sudut kotak tajam tanpa radius sama sekali.
- Card dashboard generic dengan gradient dan shadow berlebihan.
- Font default seperti Inter atau Poppins untuk heading.

### 3.2 Diagnosis yang Memicu v2 — kenapa tampilan v1 terasa flat

Design token v1 benar filosofinya (terang, hangat, melindungi pemula) tapi dieksekusi terlalu konservatif:
- Semua kartu putih, border abu tipis seragam, nyaris tanpa kedalaman.
- Aksen cyan "dijaga sangat ketat" sampai layar terasa kosong warna.
- Shadow "minim" jadi semua elemen menempel datar, tidak ada hierarki.
- Tidak ada elemen dekoratif/ilustratif yang memberi karakter.

Hasilnya: bersih tapi hambar, terasa wireframe. v2 memperbaiki ini TANPA jatuh ke ekstrem lain (ramai, kekanakan, tidak kredibel) — tiga kata jangkar: **Profesional** (struktur rapi, fondasi, jangan dikorbankan) + **Hangat** (warna tidak dingin/klinis, ruang napas, sudut lembut) + **Playful** (detail kecil menyenangkan — micro-interaction, ilustrasi ringan — ada di DETAIL, bukan struktur). Aturan emas: kalau ragu, profesional dulu, baru tambah kehangatan lewat warna/ruang, baru keceriaan lewat detail kecil.

### 3.3 Warna

**Inti (dipertahankan dari v1, TIDAK berubah nilainya):**

| Variable (v2 CSS) | Hex | Peran | Kapan Dipakai |
|---|---|---|---|
| `--color-ink` | `#1C1515` | Teks utama / elemen gelap | Teks judul dan body utama. Boleh jadi background elemen KECIL kontras tinggi (mis. node "selesai" jalur kurikulum). **Jangan** background halaman/section luas. |
| `--color-muted` | `#979393` | Teks sekunder, border, non-aktif | Teks pendukung, placeholder, border card, elemen locked/disabled. |
| `--color-accent` | `#05D9E7` | Aksen utama (cyan) | CTA/tombol primer, progres aktif, elemen signature (jalur kurikulum). Di v1: maks 1 elemen mencolok/layar. Di v2: boleh lebih berani (lihat §3.4). |
| `--color-accent-soft` | `#D1F8FF` | Section alternatif | Background section yang perlu dibedakan (card terpilih, banner info ringan). Sesekali, bukan dominan. |
| `--color-white` | `#FFFFFF` | Background dominan | Background utama hampir semua halaman (v1). Di v2, sebagian peran ini diambil alih `--color-surface` — lihat di bawah. |

**Tambahan v2 — kehangatan & kedalaman:**

| Variable | Hex | Peran & alasan |
|---|---|---|
| `--color-warm` | `#FF7F50` (coral) | Aksen hangat pendukung utama. Coral+cyan/teal adalah pasangan warna yang terbukti harmonis (saling melengkapi di roda warna, sama-sama cerah, beda hue jauh jadi tidak bentrok). Badge/highlight positif/ilustrasi/CTA sekunder — TIDAK untuk teks body panjang. |
| `--color-warm-soft` | `#FFE8DD` | Tint lembut `--color-warm`, pola identik `accent`/`accent-soft` — background section/badge hangat. |
| `--color-surface` | `#FAF8F6` | Tingkat 1: background HALAMAN (bukan kartu) — off-white sangat halus dengan sedikit kehangatan (bukan abu netral). Memberi kedalaman: halaman tidak lagi putih polos sama seperti kartu di atasnya. |
| `--color-surface-alt` | `#F3F0EC` | Tingkat 2: lebih dalam dari `--color-surface` — elemen sunken/nested DALAM kartu (baris berselang, area kode/kutipan). |

**Warna semantik (v2, desaturasi ringan biar nyatu, jangan norak):**

| Variable | Hex | Peran |
|---|---|---|
| `--color-success` | `#2FA872` | Teks/ikon sukses |
| `--color-success-soft` | `#E4F5EC` | Background badge/section sukses |
| `--color-warning` | `#E8A23C` | Teks/ikon warning |
| `--color-warning-soft` | `#FCF0DC` | Background badge/section warning |
| `--color-danger` | `#E1594B` | Teks/ikon error |
| `--color-danger-soft` | `#FBEAE7` | Background badge/section error |

Sengaja dijaga jarak hue dari `--color-warm` (coral `#FF7F50` vs danger `#E1594B`) supaya "aksen hangat playful" tidak pernah tertukar makna dengan "ada masalah/error" di layar yang sama.

**Aturan proporsi per portal (v1, tetap berlaku di v2):**
- **Eksplorasi** (anggota pemula, mudah minder): proporsi putih/surface paling tinggi, cyan cuma di elemen progres/CTA, kesan harus terasa aman dan tidak menghakimi. Di v2: paling hangat, paling lapang, playful paling terasa (ilustrasi, warna hangat, microcopy ramah). Prioritas: tidak intimidating.
- **Eksekusi**: user lebih terbiasa tool kerja, boleh sedikit lebih padat informasi, lebih banyak elemen `#979393` untuk struktur, tapi tetap latar putih/surface dominan. Di v2: profesional-hangat, sedikit lebih padat, playful lebih halus (tetap ada, tidak dihilangkan).
- **Admin**: paling fungsional/padat di v2, tapi TETAP pakai bahasa visual yang sama (bukan tema beda) — hangat lewat warna & ruang, playful minimal tapi ada.

**Satu bahasa visual, tiga tingkat intensitas — bukan tiga desain berbeda.**

### 3.4 Kedalaman & Elevasi (perbaikan terbesar v2 dari "flat")

Shadow dinamai `shadow-warm-*` (BUKAN menimpa `shadow-sm`/`shadow-md`/`shadow-lg` bawaan Tailwind) — supaya halaman yang sudah memakai shadow bawaan (unit-show, chat WEBI, ideas index, notifications bell, welcome) tidak ikut berubah tampilannya. Warna shadow pakai rgb dari `--color-ink` (`28 21 21`), bukan abu netral — inilah yang membuat shadow terasa "hangat".

| Variable | Value | Pemakaian |
|---|---|---|
| `--shadow-warm-xs` | `0 1px 2px 0 rgb(28 21 21 / 0.05), 0 1px 1px 0 rgb(28 21 21 / 0.03)` | Kartu biasa saat diam (resting state) — halus, bukan nol. |
| `--shadow-warm-md` | `0 6px 16px -4px rgb(28 21 21 / 0.10), 0 3px 6px -2px rgb(28 21 21 / 0.06)` | Kartu saat hover, atau kartu utama/CTA saat diam. |
| `--shadow-warm-lg` | `0 16px 32px -8px rgb(28 21 21 / 0.16), 0 6px 12px -4px rgb(28 21 21 / 0.08)` | Elemen benar-benar mengambang (modal, dropdown, panel slide-over). |

Kartu punya hierarki (kartu utama/CTA boleh beda elevasi/warna dari kartu sekunder — tidak semua kartu diperlakukan sama). Border tetap halus tapi dikombinasi surface bertingkat + shadow halus, bukan jadi satu-satunya pemisah.

### 3.5 Tipografi

| Font | Peran | Pemakaian |
|---|---|---|
| **Sora** (weight 600-700) | Display/Heading | Semua judul halaman, judul card besar, angka besar (level, poin di dashboard utama). |
| **Plus Jakarta Sans** (weight 400-500) | Body | Semua teks paragraf, label form, deskripsi, isi konten kurikulum. |
| **JetBrains Mono** (weight 400-500) | Utility/Data | Angka statistik kecil (poin, level, checkpoint ID), kode/snippet, label teknis, timestamp. |

Load via Google Fonts:
```html
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
```

**Aturan:** jangan pakai Sora untuk body text panjang (cuma heading pendek). Jangan pakai Plus Jakarta Sans untuk angka statistik (pakai JetBrains Mono — ini yang bikin identitas "developer" kerasa otentik). Ketiga font TETAP di v2, tidak ada font baru — yang berubah cuma disiplin ukuran saat dipakai (judul lebih besar, kontras lebih jelas dengan body, hierarki lebih berani — dilakukan di level Blade/utility class, bukan token baru).

### 3.6 Radius & Spacing

Border radius sedang: **~8px** untuk button/input (`rounded-lg` Tailwind v4 default), **~12px** untuk card (`rounded-xl` Tailwind v4 default) — dicek eksplisit saat v2 disusun, sudah persis cocok dengan aturan v1, TIDAK ada token radius baru yang ditambahkan. Border tipis (1px), warna `--color-muted` opacity rendah, hindari border tebal/mencolok kecuali elemen aktif/selected. Spacing pakai skala Tailwind default (lapang secara konvensi) — kedalaman didapat dari warna/shadow (v2), bukan dari mengubah angka spacing.

### 3.7 Signature Element: Jalur Kurikulum

Halaman Peta Kurikulum (modul Eksplorasi) memvisualisasikan 10 modul sebagai **jalur node yang saling terhubung**, terinspirasi konsep circuit/papan sirkuit — bukan list vertikal biasa.

**Tiga state node, wajib beda visual:**
- **Selesai:** node bulat solid `#1C1515`, ikon centang warna `#05D9E7` di tengah.
- **Aktif (sedang dikerjakan):** node bulat solid `#05D9E7`, ring/border tebal `#D1F8FF`, nomor modul di tengah warna `#1C1515`.
- **Terkunci:** node bulat kosong (background putih/surface), border `#979393`, ikon gembok `#979393`.

Garis penghubung antar node: `#05D9E7` dari modul pertama sampai modul aktif terakhir, `#979393` (atau putus-putus) untuk sisa jalur belum tercapai — garis berhenti nyala PERSIS di titik progres user (indikator progres sekaligus dekorasi). Elemen jalur node PENUH ini KHUSUS Peta Kurikulum saja (biar tetap terasa signature, bukan pola generic) — tapi elemen "garis progres menyala sampai titik tertentu" boleh dipakai ulang di tempat lain yang relevan (progress bar checkpoint). Gaya sirkuit ini juga jadi inspirasi elemen dekoratif ringan di v2 (garis sirkuit tipis sebagai ornamen di tempat lain).

**Catatan implementasi (v2.0, dicatat retroaktif):** halaman Peta Kurikulum pada akhirnya tetap dibangun VERTIKAL (bukan horizontal seperti disebut literal di atas) — ini keputusan sadar Aye yang diterima sebagai adaptasi mobile-friendly yang tepat, bukan penyimpangan yang perlu diperbaiki. Lihat `docs/WEBI-v2.0.md` untuk detail.

### 3.8 Layout dan Komponen (umum)

- **Shadow:** v1 minim (cuma modal/dropdown); v2 bertingkat (lihat §3.4).
- **Ikon:** set ikon outline yang konsisten (bukan campur berbagai gaya), pakai satu library dan konsisten di seluruh aplikasi.
- **Micro-interaction (v2, pola bukan token CSS):** `transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md` — dipakai konsisten di kartu manapun yang ingin terasa "hidup" saat di-hover.
- **State kosong yang ramah (v2):** ilustrasi ringan + kalimat suportif, bukan area kosong polos.
- **Ilustrasi/spot art ringan (v2):** elemen dekoratif kecil (bukan stok foto), gaya konsisten geometris/line-art ringan.

### 3.9 Nada Penulisan UI (microcopy)

- **Eksplorasi:** nada suportif, tidak menghakimi. Bukan "Kamu gagal kuis", tapi "Coba lagi, kamu hampir sampai" atau sejenisnya — konsisten dengan nada konten kurikulum, UI melanjutkan nada yang sama. Diperkuat di v2 lewat keceriaan di state kosong, sukses, level-up, apresiasi leaderboard.
- **Eksekusi dan Admin:** boleh lebih langsung/fungsional, penggunanya sudah lebih terbiasa dengan tool kerja.
- Hindari bahasa teknis tanpa penjelasan di sisi Eksplorasi (jangan langsung pakai istilah "commit", "deploy" tanpa konteks untuk user yang levelnya belum sampai situ).

### 3.10 Alur Kerja Perubahan Desain (v2)

1. Eksplorasi visual di Claude Design dulu (palet final hex, contoh kartu/dashboard, ilustrasi, shadow) — Aye MELIHAT dan memilih, karena "hangat playful profesional" cuma bisa divalidasi dengan mata, bukan dari deskripsi teks.
2. Kunci jadi design token konkret (nilai hex, shadow, radius, spacing scale).
3. Buktikan di SATU halaman dulu sebelum menyebar (dashboard Eksplorasi jadi halaman pertama v2).
4. Terapkan bertahap ke seluruh aplikasi, halaman per halaman, TIDAK sekaligus.

### 3.11 Referensi Cepat untuk Generate UI Baru

Sebelum generate komponen baru: (1) Apakah ini masuk kategori Eksplorasi (proporsi putih/hangat tinggi, nada suportif) atau Eksekusi/Admin (boleh lebih padat)? (2) Apakah elemen ini butuh warna aksen cyan/warm? Kalau ya, pastikan belum ada elemen aksen lain yang lebih dominan di layar yang sama. (3) Apakah ini halaman Peta Kurikulum? Kalau ya, pakai signature element jalur node, jangan versi sederhana list biasa. (4) Implementasi CSS lewat `resources/css/app.css` (`@theme`, Tailwind v4 theme variable) — bukan hex hardcode tersebar di Blade.

---

## 4. Arsitektur Database (Ringkas — Pointer ke Referensi Lengkap)

Skema database lengkap (35 tabel, 4 kelompok entitas: Lintas Modul, Eksekusi, Eksplorasi, WEBI) **TETAP berdiri sendiri** di `docs/arsitektur-database.md` — dokumen itu terlalu dalam secara teknis (definisi field per tabel, 3 diagram ERD Mermaid, diagram arsitektur aplikasi, alur data WEBI multi-sumber) untuk dilebur ke sini tanpa membuat dokumen ini kehilangan fokus sebagai overview. Keputusan ini diambil eksplisit saat konsolidasi dokumentasi ini — lihat laporan task "Konsolidasi Dokumentasi: 3 Dokumen Terpadu" untuk rasional lengkapnya.

**Ringkasan singkat untuk orientasi cepat:**
- **35 entitas total** (naik dari 27 di v1.0 — 8 tabel baru + 5 tabel lama dapat kolom tambahan sepanjang v2.0).
- **Lintas Modul (3):** User (role + Mode Ganda), Notification (sistem notifikasi terpadu lintas modul), DualModeRequest.
- **Eksekusi (12):** ProjectIdea, Project, ProjectMember, Milestone, Task (+subtask via `parent_task_id`), TaskAssignment, ProgressUpdate, Comment, Attachment, ActivityLog, CalendarEvent, TaskDependency.
- **Eksplorasi (16):** Module, Unit, UnitEvaluation, UserUnitProgress, EvaluationSubmission, Checkpoint, CheckpointCompletion, ForumThread (+portal Eksplorasi/Eksekusi), ForumReply, LearningResource, UserExplorationProgress, ContentBlock (polimorfik), Challenge, ChallengeStep, ChallengeSubmission, ChallengeAttachment.
- **WEBI (4):** Conversation, Message, ProactiveLog, GuardrailFlag.

Untuk field per tabel, tipe data, constraint, dan diagram ERD lengkap: **buka `docs/arsitektur-database.md` langsung.**

---

## 5. Deployment

### 5.1 Ringkasan Arsitektur Deployment

Deploy ke **rumahweb** (shared hosting), domain **rit-base.online**, akun cPanel `rits8313`, lewat fitur **Git Version Control** cPanel. Keputusan kunci:
- Repo GitHub **public** (bukan private) — lihat §5.2 Masalah 1 untuk kenapa.
- Git Version Control cPanel di-clone **langsung ke `public_html/webi-space`**, bukan ke `repositories/webi-space` lalu copy manual — valid karena repo public tidak butuh autentikasi.
- Document Root domain `rit-base.online` diarahkan ke `public_html/webi-space/public` lewat cPanel > Domains > Manage.

### 5.2 Catatan Troubleshooting Deployment (Log Insiden Nyata, dari Deploy Pertama Kali)

Dicatat supaya tidak perlu debug ulang dari nol kalau kejadian serupa muncul lagi (deploy project baru, pindah versi PHP, dst).

**Masalah 1 — SSH key gagal untuk repo private**

Gejala: `Permission denied (publickey)` saat clone via SSH, walau key sudah dibuat dan Deploy Key sudah didaftarkan di GitHub. Sudah dicoba: generate key custom name, generate ulang dengan nama `id_rsa`, daftarkan ke GitHub Deploy Keys — tetap gagal. Akar masalah tidak ditemukan secara pasti (kemungkinan soal permission file key atau passphrase), tidak bisa dipastikan tanpa akses langsung debug server. **Keputusan:** berhenti debug SSH, pindah ke repo public. Kalau suatu saat butuh private repo lagi, kemungkinan perlu bantuan tim support rumahweb langsung, bukan trial-error mandiri.

**Masalah 2 — Composer belum terinstall di server**

Gejala: `bash: composer: command not found`. Penyebab: Composer tidak otomatis tersedia di server cPanel (beda dari lokal, Laragon sudah bundle Composer). Solusi (command persis yang berhasil):
```bash
cd /tmp
curl -sS https://getcomposer.org/installer | php
mkdir ~/bin
mv composer.phar ~/bin/composer
chmod +x ~/bin/composer
echo "export PATH=$HOME/bin:$PATH" >> ~/.bash_profile
source ~/.bash_profile
```
Verifikasi: `composer --version`.

**Masalah 3 — Versi PHP server tidak cocok dengan requirement Laravel**

Gejala: `Root composer.json requires php ^8.3 but your php version (8.2.31) does not satisfy that requirement.` Penyebab: Laragon lokal pakai PHP 8.3 (default saat `composer create-project laravel/laravel .`, otomatis dapat Laravel 13 yang minta PHP 8.3), sementara PHP default server awalnya 8.2. Solusi: server rumahweb ternyata punya PHP 8.3 tersedia, tinggal dipastikan versi CLI terminal juga ikut 8.3 (`php -v`). Tidak perlu downgrade Laravel. **Catatan untuk ke depan:** kalau bikin project baru lagi, cek dulu versi PHP default server sebelum `composer create-project` di lokal, supaya tidak mismatch dari awal.

**Masalah 4 — `proc_open` dimatikan di server**

Gejala: saat `composer install` sampai ke tahap `php artisan package:discover` otomatis, muncul error `The Process class relies on proc_open, which is not available on your PHP installation.` Penyebab: pengaturan keamanan umum shared hosting, fungsi PHP `proc_open` (untuk menjalankan subprocess) dimatikan. Solusi: skip script otomatis composer, jalankan manual setelahnya:
```bash
composer install --no-dev --optimize-autoloader --no-scripts
php artisan package:discover --ansi
```
**Catatan untuk ke depan:** command lain yang butuh spawn subprocess kemungkinan besar kena batasan yang sama, perlu dicari cara manual/alternatif tiap kali ketemu.

**Masalah 5 — Access denied database, padahal kredensial sudah diisi**

Gejala: `SQLSTATE[28000] Access denied for user 'root'@'localhost' (using password: NO)`, padahal `.env` sudah diisi kredensial database cPanel yang benar. Penyebab: baris `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` di `.env` masih diawali tanda `#` (dianggap komentar/tidak aktif), Laravel jatuh balik ke default (`root`, tanpa password, database `laravel`). Solusi: hapus tanda `#` di depan baris-baris itu. **Catatan tambahan:** kalau password database mengandung karakter `#`, bungkus nilainya pakai tanda kutip dua di `.env`:
```
DB_PASSWORD="aEy0JvQZ1#P4QYBk"
```
Tanpa tanda kutip, karakter `#` di tengah nilai berisiko dianggap awal komentar dan memotong sisa isinya. Setelah edit, WAJIB jalankan `php artisan config:clear` sebelum `php artisan migrate`, karena Laravel bisa menyimpan cache config lama.

**Urutan command final yang terbukti berhasil (referensi cepat, gabungan Masalah 2-5):**
```bash
# Install composer (sekali saja per server)
cd /tmp
curl -sS https://getcomposer.org/installer | php
mkdir ~/bin
mv composer.phar ~/bin/composer
chmod +x ~/bin/composer
echo "export PATH=$HOME/bin:$PATH" >> ~/.bash_profile
source ~/.bash_profile

# Install dependency project
cd ~/public_html/webi-space
composer install --no-dev --optimize-autoloader --no-scripts
php artisan package:discover --ansi

# Setup environment
cp .env.example .env
php artisan key:generate
# edit .env manual: isi DB_* tanpa tanda #, bungkus password pakai kutip dua kalau ada karakter spesial

php artisan config:clear
php artisan migrate
chmod -R 775 storage bootstrap/cache
```

### 5.3 Checklist Deploy Production (Lengkap, Berurutan)

Checklist manual untuk deploy ke production (rumahweb shared hosting, domain **rit-base.online**, deploy via fitur Git Version Control cPanel). **Ikuti berurutan dari atas ke bawah, jangan lompat bagian.** Setiap eksekusi command di server dilakukan manual — dokumen ini cuma panduan urutannya. Sumber checklist ini adalah log insiden nyata di §5.2 — semua langkah yang menyebut error/gejala spesifik mengacu langsung ke situ, bukan dugaan.

**0. Sebelum push — sudah beres di sisi kode**

- [x] `.env.example` mencantumkan semua variabel yang benar-benar dipakai (termasuk `GEMINI_API_KEY`, `GEMINI_MODEL`, `GEMINI_THINKING_LEVEL`, dan `DB_*` untuk MySQL — sebelumnya hilang/di-comment ke sqlite).
- [x] `npm run build` sukses tanpa error/warning dari kondisi bersih.
- [x] Scheduler (`routes/console.php`) sudah benar: satu-satunya scheduled command adalah `execution:check-alerts` (`->daily()`). WEBI tidak punya scheduled command untuk sapaan proaktif — itu memang by design real-time (dihitung saat halaman chat dibuka lewat `ProactiveService::checkAndDeliver()`), bukan gap.
- [x] Seluruh test suite hijau.
- [x] Fitur Attachment (Eksekusi) sudah tidak bergantung pada `storage:link`/symlink sama sekali — server rumahweb mematikan `symlink()` sepenuhnya.
- [x] Attachment file ditulis ke disk PRIVAT bernama `attachments` (`storage/app/private`) — **BUKAN disk `local` bawaan Laravel**. Sempat salah pakai disk `local` di percobaan pertama dan itu bikin SELURUH upload file lewat Livewire di aplikasi ini gagal di production (Livewire juga pakai disk `local` sebagai disk default-nya sendiri) — sudah diperbaiki dengan disk terpisah, `local` dikembalikan ke kondisi bawaan (`storage/app`), tidak disentuh sama sekali. File attachment cuma bisa diakses lewat route `/attachments/{attachment}/download` yang wajib login DAN cek keanggotaan proyek. Tidak ada folder publik baru yang perlu disiapkan di server untuk fitur ini.

**Temuan penting yang memengaruhi urutan di bawah:**
- Aplikasi ini **tidak memakai job queue sama sekali** (`QUEUE_CONNECTION=database` di `.env` cuma warisan default Laravel, tidak ada satu pun `ShouldQueue`/`Job` di kodenya) — **tidak perlu** `php artisan queue:work` atau setup supervisor apa pun di server.
- `/public/build` (hasil `npm run build`) dan `/vendor` (hasil `composer install`) sama-sama di-gitignore — keduanya TIDAK ikut ter-pull dari git, harus dibuat/diisi terpisah di server (lihat langkah 4 dan 5).
- Tidak diketahui apakah server rumahweb ini punya Node.js/npm terpasang — mengingat `proc_open` saja sudah dimatikan, kemungkinan besar tidak ada Node juga. **Rekomendasi: build asset di lokal, lalu upload folder `public/build` manual lewat File Manager**, bukan mengandalkan `npm run build` jalan di server. Cek dulu ketersediaan Node lewat Terminal cPanel sebelum memutuskan.

**1. Push kode ke GitHub**
- [ ] Pastikan tidak ada file sensitif ikut ter-commit (`.env`, dsb — sudah di-gitignore, tapi cek ulang `git status` sebelum push).
- [ ] Push branch yang sudah final ke GitHub.

**2. Tarik kode ke server**
- [ ] Lewat fitur **Git Version Control** di cPanel: clone repo ini **langsung ke `public_html/webi-space`** (bukan ke `repositories/webi-space` lalu copy manual) — repo ini public, clone tidak butuh autentikasi SSH sama sekali.
- [ ] Fallback kalau fitur Git bermasalah: upload manual lewat File Manager (zip lokal, extract di server).

**3. Persiapan PHP dan Composer di server**
- [ ] **Cek versi PHP CLI yang aktif di Terminal cPanel:** `php -v`. Project ini butuh **PHP ^8.3**. Server rumahweb kadang default ke versi lebih lama (pernah kejadian 8.2) — kalau versi CLI tidak cocok, cek menu cPanel > MultiPHP Manager dan pastikan versi PHP untuk domain ini di-set ke 8.3, dan CLI ikut memakainya (bukan cuma versi PHP-FPM untuk web request).
- [ ] **Composer TIDAK preinstalled di server rumahweb** — install manual sekali per server (lihat command di §5.2).

**4. Dependencies PHP (Composer install)**
- [ ] Dari folder project (`cd ~/public_html/webi-space`): `composer install --no-dev --optimize-autoloader --no-scripts` — **wajib pakai `--no-scripts`** (lihat Masalah 4, §5.2).
- [ ] Jalankan discovery paket secara manual: `php artisan package:discover --ansi`.

**5. Asset frontend (Vite/Tailwind)**

Pilih SATU, tergantung ketersediaan Node di server (cek `node -v` dan `npm -v` di Terminal cPanel):
- [ ] **Opsi A (direkomendasikan, lebih aman):** build di lokal (folder `public/build`), upload manual lewat File Manager cPanel ke lokasi yang sama di server.
- [ ] **Opsi B (kalau Node tersedia di server):** `npm install` lalu `npm run build`.

**5b. Verifikasi SSL Domain**
- [ ] Cek status SSL certificate untuk `rit-base.online` lewat cPanel (menu **SSL/TLS Status** atau **AutoSSL**). Domain ini sebelumnya menunjukkan tanda "?" di kolom **Force HTTPS Redirect** (beda dari `rit-base.org` dan `dojobaraya.rit-base.org` yang sudah "On") — indikasi SSL kemungkinan belum terpasang untuk domain ini.
- [ ] Kalau SSL belum aktif, jalankan **AutoSSL** dulu atau pasang certificate lewat menu itu sebelum lanjut.
- [ ] Catat hasilnya (aktif atau belum) — dipakai langsung untuk mengisi `APP_URL` di langkah 6.

**6. File `.env`**
- [ ] Copy `.env.example` jadi `.env` di server, isi SEMUA variabel yang tercantum (jangan lewatkan satu pun, termasuk blok `GEMINI_*`).
- [ ] **`APP_ENV=production`** dan **`APP_DEBUG=false`** — WAJIB. Kalau `APP_DEBUG=true` di production, error apa pun akan menampilkan stack trace lengkap + detail konfigurasi ke publik.
- [ ] `APP_URL` — isi sesuai kondisi ASLI hasil cek langkah 5b: `https://rit-base.online` HANYA kalau SSL sudah dikonfirmasi aktif, `http://rit-base.online` kalau belum. Ini memengaruhi apakah sesi login nanti berfungsi normal — jangan tebak.
- [ ] `DB_CONNECTION=mysql` + isi `DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` sesuai database MySQL yang dibuat di cPanel.
- [ ] `GEMINI_API_KEY` diisi dari project Google Cloud yang **didedikasikan khusus** untuk WEBI-SPACE (bukan project yang dipakai bareng aplikasi lain — kuota free tier Gemini per-project, bukan per-key).
- [ ] `GEMINI_MODEL=gemini-3.5-flash`, `GEMINI_THINKING_LEVEL=minimal` (nilai final yang sudah diuji, lihat CLAUDE.md kalau mau ubah).
- [ ] **Format penulisan `.env`** (lihat Masalah 5, §5.2): jangan ada tanda `#` di depan baris `DB_*` atau baris aktif lainnya. Value dengan karakter spesial (spasi, `#`, `$`, `"`, `'`, dst) wajib dibungkus tanda kutip dua.
- [ ] Kalau `APP_KEY` masih kosong: `php artisan key:generate --force`.
- [ ] **Setelah selesai isi/edit `.env`, jalankan `php artisan config:clear` sebelum lanjut ke langkah migrasi.**

**7. Permission storage**
- [ ] Pastikan folder `storage/` dan `bootstrap/cache/` writable oleh user web server (`chmod -R 775` biasanya cukup di cPanel).
- [ ] **`php artisan storage:link` TIDAK DIPAKAI LAGI di server ini — jangan dijalankan.** Server rumahweb mematikan fungsi `symlink()` sepenuhnya. Fitur Attachment Eksekusi menulis ke disk `attachments` (privat, root `storage/app/private`, terdaftar terpisah di `config/filesystems.php`) — bukan disk `local` bawaan Laravel. File diakses lewat route `/attachments/{attachment}/download` (cek login + keanggotaan proyek dulu), tidak pernah disajikan langsung sebagai file statis. Tidak ada symlink dan tidak ada folder publik baru yang perlu disiapkan.
- [ ] Pastikan folder `storage/app/private` writable oleh user web server (sudah tercakup permission `storage/` di atas). Laravel otomatis membuat subfolder `attachments/` saat upload pertama kali — tidak perlu `mkdir` manual.

**8. Document root domain**
- [ ] Di cPanel (Domains > Manage), pastikan document root untuk `rit-base.online` menunjuk ke `public_html/webi-space/public` (folder `public/`), BUKAN root project.

**9. Migrasi database dan data awal**

Urutan ini final dan sudah diverifikasi — jangan dibalik:
- [ ] **Untuk deploy PERTAMA KALI ke database yang benar-benar kosong saja:**
  ```bash
  php artisan migrate:fresh --seed --force
  ```
  Ini menjalankan `CurriculumSeeder` SAJA (10 modul, 67 unit asli) — tidak ada data sample/dummy, tidak ada user apa pun yang otomatis terbuat.
  > ⚠️ **PERINGATAN:** `migrate:fresh` MENGHAPUS SEMUA TABEL. Jangan pernah jalankan ini lagi setelah ada data anggota sungguhan di database — kalau perlu migrasi tambahan di masa depan, pakai `php artisan migrate --force` (tanpa `--fresh`) saja.
- [ ] Buat akun admin pertama secara terpisah, interaktif (tidak ada kredensial di-hardcode di kode manapun):
  ```bash
  php artisan app:create-admin
  ```
  Command ini menanyakan nama, email, dan password lewat prompt terminal (password tersembunyi saat diketik).
- [ ] Login ke `/login` pakai akun admin yang baru dibuat, konfirmasi bisa masuk ke `/admin/dashboard`.

**10. Cache production — PALING TERAKHIR**

Baru jalankan setelah `.env` benar-benar final (semua value sudah benar dan tidak akan diubah lagi dalam waktu dekat):
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
> ⚠️ **Kalau nanti perlu edit `.env` LAGI setelah langkah ini** (ganti API key, ubah `APP_DEBUG`, dsb): jalankan `php artisan config:clear` DULU sebelum edit, edit filenya, baru `php artisan config:cache` lagi. Kalau langsung edit `.env` tanpa `config:clear` dulu, perubahan TIDAK akan kepakai karena aplikasi masih baca dari cache config lama.

**11. Cron job — satu-satunya yang perlu didaftarkan**
- [ ] Daftarkan **satu** cron job di cPanel (Cron Jobs), jalan tiap menit:
  ```
  * * * * * cd /path/ke/project && php artisan schedule:run >> /dev/null 2>&1
  ```
  Satu-satunya cron yang dibutuhkan — semua command terjadwal (`execution:check-alerts`, jalan harian) dipanggil lewat `schedule:run` ini, tidak perlu cron terpisah per command.
- [ ] Verifikasi terdaftar benar: `php artisan schedule:list`.

**12. Verifikasi akhir**
- [ ] Buka `https://rit-base.online/login`, pastikan halaman muncul benar (font Sora/Plus Jakarta Sans/JetBrains Mono termuat, styling tidak polos/rusak — kalau CSS tidak termuat, cek lagi langkah 5).
- [ ] Login sebagai admin, cek `/admin/dashboard` menampilkan data kosong yang wajar (0 anggota, 0 proyek — bukan error).
- [ ] Buat 1 akun `exploration_member` dan 1 `execution_member` lewat `/admin/users/create`, coba login masing-masing, konfirmasi RBAC benar (tidak bisa akses modul milik role lain).
- [ ] Cek WEBI (`/eksplorasi/webi`) bisa membalas pesan sungguhan (konfirmasi `GEMINI_API_KEY` valid dan kuota project tidak habis).
- [ ] Cek upload attachment di sebuah task (Eksekusi): upload file, klik link attachment-nya, konfirmasi bisa terunduh. Coba juga buka URL download itu dari akun anggota proyek LAIN atau saat logout — harus ditolak (403/redirect login), bukan malah bisa diakses.
- [ ] **Wajib khusus setelah insiden production 2026-07-04:** upload file attachment di task Eksekusi HARUS berhasil tanpa error "Unable to retrieve the file_size" — error ini pernah muncul di production karena percobaan perbaikan pertama menimpa disk `local` bawaan Laravel (yang juga dipakai Livewire untuk upload sementara di halaman manapun), bukan cuma masalah attachment. Kalau error ini muncul lagi, cek `config/filesystems.php` — disk `local` HARUS tetap `storage_path('app')`, jangan pernah diubah untuk kebutuhan fitur tertentu, selalu buat disk baru dengan nama sendiri.
- [ ] Terakhir, pastikan sekali lagi `APP_DEBUG=false` aktif di server — cara paling aman: coba akses URL yang sengaja salah/tidak ada (404 harus tampil sebagai halaman generic, bukan stack trace).

---

## Sumber Dokumen Ini

Isi dokumen ini dikonsolidasikan dari 8 file sumber. 6 di antaranya sekarang dipindah ke `docs/v_2.0/archive/sumber-konsolidasi/` — file aslinya tetap ada, tidak dihapus, untuk cross-check detail:

1. `README.md` (tidak dipindah — boilerplate Laravel, tetap di root)
2. `docs/v_2.0/archive/sumber-konsolidasi/tech-stack.md`
3. `docs/v_2.0/archive/sumber-konsolidasi/design-tokens.md`
4. `docs/v_2.0/archive/sumber-konsolidasi/design-tokens-v2.md`
5. `docs/v_2.0/archive/sumber-konsolidasi/design-brief-v2.md`
6. `docs/v_2.0/archive/sumber-konsolidasi/Catatan_Troubleshooting_Deployment_WEBI-SPACE.md`
7. `docs/v_2.0/archive/sumber-konsolidasi/DEPLOYMENT_CHECKLIST.md`
8. `docs/arsitektur-database.md` — DIRUJUK, bukan dilebur (tetap berdiri sendiri di lokasi asli, tidak dipindah ke arsip).
