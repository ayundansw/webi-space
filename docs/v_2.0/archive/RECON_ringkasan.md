# Ringkasan Arsip Recon — WEBI-SPACE v2.0

Indeks pointer, BUKAN gabungan isi. Enam belas recon di bawah ini sudah
menjalankan fungsinya (jadi input untuk tugas implementasi yang disebut),
sekarang jadi arsip riwayat keputusan. Detail lengkap tetap ada di
masing-masing file, tidak diduplikasi di sini.

**Update 2026-07-13 (task Konsolidasi Dokumentasi 3-Dokumen-Terpadu):**
keenam belas file di bawah dipindah SATU LEVEL LEBIH DALAM, dari
`docs/v_2.0/archive/` ke `docs/v_2.0/archive/sumber-konsolidasi/`, bersama
20 file sumber lain (rancangan v2.0 + dokumen v1.0 + catatan deployment) —
folder itu sekarang jadi arsip terpadu untuk SEMUA sumber yang sudah
terkonsolidasi ke `docs/WEBI-SPACE.md`/`docs/WEBI-v1.0.md`/`docs/WEBI-v2.0.md`.
Path di tabel bawah sudah diperbarui mengikuti lokasi baru ini.

**Gelombang 1** (awalnya diarsipkan lewat `docs/v_2.0/RENCANA_PEMBERSIHAN.md`, 2026-07-09):

| File | Recon untuk tugas | Status tugas target |
|---|---|---|
| `sumber-konsolidasi/RECON_v1_untuk_v2.md` | 2.2.1 (Fondasi Struktural) | Selesai |
| `sumber-konsolidasi/RECON_kuis.md` | 2.2.3 sub-modul 3 (Kuis v2.0, skor terbaik boolean) | Selesai |
| `sumber-konsolidasi/RECON_peta_kurikulum.md` | 2.2.3 sub-modul 2 (Peta Kurikulum ala roadmap.sh) | Selesai |
| `sumber-konsolidasi/RECON_profil_akun.md` | 2.2.3 sub-modul 1 (Profil dan Avatar) | Selesai |
| `sumber-konsolidasi/RECON_webi.md` | 2.2.3 sub-modul 4a/4b (WEBI riwayat percakapan + kontekstual) | Selesai |
| `sumber-konsolidasi/RECON_dashboard.md` | 2.2.3 sub-modul 5 (Dashboard tiga portal, batch 1 visual + batch 2 konversi Eksekusi) | Selesai |

**Gelombang 2** (awalnya diarsipkan lewat task "Arsipkan 10 Kandidat Recon Gelombang Kedua", 2026-07-13 — hasil recon inventaris dokumentasi yang menemukan pola sama seperti Gelombang 1: task target sudah 100% tuntas, belum pernah dipindah):

| File | Recon untuk tugas | Status tugas target |
|---|---|---|
| `sumber-konsolidasi/RECON_shell_navbar_sidebar.md` | Fase 2 Langkah 5 (navbar + popup menu, badge status mode, bell notifikasi, account-menu) | Selesai |
| `sumber-konsolidasi/RECON_pola_admin_panel.md` | Editor Blok Konten (`ContentEditor.php`, admin Kelola Kurikulum) | Selesai |
| `sumber-konsolidasi/RECON_evaluasi_kuis.md` | Panel Admin Kelola Evaluasi (Fase 4 Batch 3) | Selesai |
| `sumber-konsolidasi/RECON_referensi.md` | Submission member Referensi + label sumber (Fase 3, kolom `created_by`/`description`) | Selesai |
| `sumber-konsolidasi/RECON_forum.md` | Thread general/opsional-modul Forum Eksplorasi (Fase 3) + landasan Forum Proyek/General Eksekusi (Fase 7 Batch 4 & task Forum General) | Selesai |
| `sumber-konsolidasi/RECON_sistem_poin.md` | Penyatuan sistem poin saat Modul Praktik dibangun (`PointService::award()` dipakai ulang oleh Praktik 3) | Selesai |
| `sumber-konsolidasi/RECON_fase3_menyeluruh.md` | Fase 3 — restrukturisasi Materi + WEBI (split-screen, Editor Blok Konten, dst) | Selesai |
| `sumber-konsolidasi/RECON_fase5_praktik.md` | Fase 5 — Modul Praktik (Praktik 1-3: challenge, track map, submission, review) | Selesai |
| `sumber-konsolidasi/RECON_fase7_manajemen_proyek.md` | Fase 7 — Modul Manajemen Proyek (7 batch: Kanban+slide-over, Subtask, Kalender, Gantt, Roadmap, Forum Proyek) | Selesai |
| `sumber-konsolidasi/RECON_fase8_mode_ganda.md` | Fase 8 — Sistem Mode Ganda (7 batch: Gate, akses baca, alur pengajuan, panel admin, UI switching, sweep regresi) | Selesai |

**Catatan:** `RECON_konten_dinamis.md` dan `RECON_project_member_peran.md`
SENGAJA TIDAK ikut diarsipkan — `RECON_konten_dinamis.md` masih dikutip aktif
untuk migrasi 67 unit ke `content_blocks` yang belum selesai (2.2.4), dan
`RECON_project_member_peran.md` masih dikutip berulang sebagai rasional
keputusan berjalan ("Kontribusi Per-Proyek Eksekusi TETAP TANPA kolom
peran", terakhir dikutip Fase 3 Batch 7b) — bukan recon yang "selesai
tugasnya" seperti 16 di atas. Keduanya tetap di `docs/v_2.0/` (bukan folder
arsip ini). Lihat `docs/v_2.0/RENCANA_PEMBERSIHAN.md` untuk penjelasan
lengkap klasifikasi Gelombang 1.
