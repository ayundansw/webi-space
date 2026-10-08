# Rancangan Modul Praktik — WEBI-SPACE v2.0

**Bagian dari:** 2.1.3 Definisi Konsep Baru
**Status:** Final, siap masuk 2.1.2 (Fiksasi Fitur v2.0)

---

## 1. Filosofi

Praktik adalah challenge project berjenjang (low/mid/high) yang bisa dipilih bebas oleh anggota eksplorasi, sepenuhnya independen dari progres linear Materi. Anggota baru gabung boleh langsung coba level high kalau berani. Tiap challenge punya track map (langkah terarah) supaya tidak berhenti di "bingung mulai dari mana", sesuai karakteristik anggota yang cenderung tidak jalan tanpa arahan jelas.

---

## 2. Perbedaan dengan Materi

| | Materi | Praktik |
|---|---|---|
| Urutan | Linear, harus berurutan | Bebas, tidak ada gating sama sekali |
| Evaluasi | Kuis/esai per unit, auto atau sederhana | Submission direview manual admin |
| Sifat | Wajib, bagian dari kurikulum inti | Opsional |
| Poin | Masuk akumulasi total | Masuk akumulasi total (sumber sama dengan Materi dan Kuis) |

---

## 3. Struktur Data

### Challenge

| Field | Keterangan |
|---|---|
| `title`, `description` | |
| `level` | `low` / `mid` / `high` |
| `points_reward` | Poin yang didapat kalau submission disetujui |
| `status` | `draft` / `published` |

### Track Map (langkah-langkah panduan)

Satu challenge punya beberapa langkah berurutan, isinya panduan/instruksi, bukan evaluasi tiap langkah. Pemakaian sistem blok konten yang sama dari rancangan Konten Dinamis, supaya panduan ini bisa disusun dengan heading, teks, callout, kode, dst, konsisten secara visual dengan Materi.

**Perubahan yang diperlukan di skema Konten Dinamis:** tabel `content_blocks` sebelumnya dirancang terikat langsung ke `unit_id`. Supaya bisa dipakai ulang di sini, field itu perlu digeneralisasi jadi polymorphic (`blockable_type`, `blockable_id`), bisa menunjuk ke Unit ATAU ke langkah Challenge. Ini catatan revisi yang perlu dibawa balik ke dokumen Rancangan Arsitektur Konten Dinamis sebelum masuk implementasi.

### Submission

| Field | Keterangan |
|---|---|
| `challenge_id`, `user_id` | |
| `submission_type` | `link` (URL repo/demo) / `text` / `file` |
| `content` | Isi sesuai tipe |
| `status` | `pending` / `disetujui` / `perlu_revisi` |
| `feedback` | Catatan admin |
| `attempt_number` | Untuk lacak percobaan ke berapa |
| `points_awarded` | Diisi cuma kalau status disetujui |

---

## 4. Mekanisme

1. Anggota buka daftar Praktik, filter berdasar level, pilih bebas.
2. Buka satu challenge, baca track map (langkah-langkah panduan), kerjakan di luar sistem (misal bikin website statis di device sendiri).
3. Submit hasil (link/text/file) lewat form di halaman challenge itu.
4. Status jadi `pending`, masuk antrian review admin.
5. Admin review, kasih feedback, ubah status jadi `disetujui` (poin langsung ditambahkan ke akumulasi total) atau `perlu_revisi` (feedback dikasih, anggota boleh submit ulang, attempt_number bertambah).
6. Tidak ada batas jumlah percobaan submit ulang.

---

## 5. Keputusan Final Mekanisme Review dan Poin

### Delegasi Review, per-submission

Tidak ada role "mentor" baru. Label `execution_member` yang sudah ada cukup. Mekanisme:
- Anggota eksplorasi submit hasil Praktik, tujuannya ke PIC.
- PIC bisa review dan kasih feedback langsung sendiri, ATAU
- PIC assign submission itu ke satu `execution_member` tertentu untuk mereview, penilaian, dan feedback. Assignment dilakukan manual per-submission oleh PIC, bukan per-challenge atau global.
- Anggota eksekusi yang di-assign dapat notifikasi begitu ditugaskan.
- Hasil feedback yang tampil ke anggota eksplorasi mencantumkan siapa yang mereview (PIC atau nama anggota eksekusi yang ditugaskan).

**Field data:** `assigned_reviewer_id` (nullable) di tabel Submission, null berarti PIC yang review sendiri.

**Aturan RBAC wajib:** anggota eksekusi yang di-assign HANYA boleh lihat dan isi feedback untuk submission yang ditugaskan ke dia, bukan akses umum ke semua submission Praktik siapa pun. Ini prinsip yang sama dengan pelajaran dari 2.9 (Kontrol Akses Attachment) di v1.0, jangan sampai terulang celah aksesnya.

### Submit Ulang dan Poin

Challenge yang sama boleh disubmit ulang tanpa batas jumlah percobaan, termasuk setelah statusnya sudah disetujui. Tiap submission diberi label eksplisit "Submission versi 1", "versi 2", dst beserta waktunya, riwayat lengkap tersimpan.

**Poin memakai skema diminishing return:** submission pertama yang disetujui dapat `points_reward` penuh. Submission kedua dan seterusnya yang disetujui dapat poin lebih kecil dari sebelumnya (bukan nol, karena tiap submission ulang adalah usaha nyata memperbaiki hasil berdasar feedback, bukan sekadar re-attempt tanpa kerja tambahan seperti pada Kuis). Nilai persis pengurangan poin ditentukan saat implementasi, prinsipnya poin menurun tiap versi, tidak pernah naik atau tetap.

### Starter Code

Tidak disediakan. Cukup instruksi di track map yang jelas dan terstruktur soal apa yang perlu dikerjakan dan bentuk output yang diharapkan. Anggota mencari tahu cara teknis sendiri, sesuai tujuan pembelajaran mandiri.
