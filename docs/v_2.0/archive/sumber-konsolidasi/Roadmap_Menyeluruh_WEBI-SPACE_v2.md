# Roadmap Menyeluruh — WEBI-SPACE v2.0

**Catatan penomoran:** Fase 2.1, 2.2, 2.3 di dokumen ini adalah tiga fase dari keseluruhan pengerjaan v2.0 (Persiapan, Implementasi, Testing dan Deploy), bukan sub-bagian dari fase lain. Penyesuaian penomoran retroaktif ke dokumen v1.0 (yang pakai prefix 1.x) dikerjakan terpisah, di luar scope dokumen ini.

**Lingkungan kerja:** seluruh implementasi v2.0 dikerjakan langsung di environment production yang sama dengan v1.0, tidak ada staging/branch terpisah. Ini disengaja, karena v1.0 masih berstatus usability testing, belum rilis resmi, belum ada data anggota yang dianggap final/tidak boleh hilang.

---

## Fase 2.1: Persiapan

| Tahap | Isi | Status |
|---|---|---|
| 2.1.1 | Analisis dan Triase Catatan Penilaian | Selesai |
| 2.1.2 | Fiksasi Fitur dan Cakupan v2.0 | Berikutnya |
| 2.1.3 | Definisi Konsep Baru (Konten Dinamis, Manajemen Proyek, Praktik) | Substansi selesai lewat tiga dokumen rancangan, diformalkan jadi satu dokumen resmi setelah 2.1.2 |

**Output:** seluruh keputusan desain dan fitur terkunci. Migrasi ke sesi chat baru terjadi di ujung fase ini.

---

## Fase 2.2: Implementasi

### Prasyarat sebelum mulai (wajib, bukan opsional)

- **Backup database production** sebelum 2.2.1 dimulai. Walau tidak ada data yang dianggap kritis, ini kebiasaan dasar sebelum perubahan skema besar, murah dilakukan, jangan dilewatkan.
- **Aturan regresi eksplisit:** seluruh automated test v1.0 yang ada sekarang (224 ke atas) WAJIB tetap hijau sepanjang pengerjaan v2.0, KECUALI test yang terikat langsung ke mekanisme yang sengaja digantikan (test mekanisme poin Kuis lama, lihat 2.1.1 bagian 2). Test yang sengaja digantikan dihapus/diganti eksplisit dengan alasan tercatat, bukan dihapus diam-diam karena "keburu ganggu".

### Urutan tahap

| Tahap | Isi |
|---|---|
| 2.2.1 | Fondasi Struktural (shell navbar/sidebar/content, responsivitas, design token diterapkan) |
| 2.2.2 | Tier 1 Lintas Portal dan per portal (sapaan dinamis, logo, kontras ikon, leaderboard dibuka, Kanban drag-drop, detail card Project Ideas) |
| 2.2.3 | Tier 2 per portal (dashboard tiap portal, Profil dan Avatar, WEBI kontekstual dan riwayat chat, Peta Kurikulum ala roadmap.sh, mekanisme Kuis baru) |
| 2.2.4 | Modul Konten Dinamis (sistem blok, custom HTML, migrasi manual 67 unit, **plus update `CurriculumContextBuilder` WEBI supaya baca dari `content_blocks` baru, bukan lagi field `units.content` lama** — tanpa ini WEBI diam-diam terus menjawab pakai data materi yang sudah usang) |
| 2.2.5 | Modul Manajemen Proyek (fondasi data dan Kanban, Gantt dan dependency, Kalender dan Roadmap, Forum dan subtask, dipecah lagi jadi batch-batch kecil saat eksekusi) |
| 2.2.6 | Modul Praktik (challenge, track map, submission dan review) |
| 2.2.7 | **Hitung ulang threshold poin per level.** Threshold yang ada sekarang (0-905) dihitung cuma dari Materi dan Checkpoint. Begitu Praktik (2.2.6) mulai menyumbang poin ke akumulasi total yang sama, threshold lama jadi tidak representatif, sama persis alasan kenapa 2.3 v1.0 dulu perlu hitung ulang dari data riil. Dikerjakan setelah 2.2.6 selesai, karena baru di titik itu total poin maksimal bisa dihitung pasti. |
| 2.2.8 | Landing Page |

**Output:** seluruh fitur v2.0 terbangun dan lolos automated test, mengikuti pola checkpoint per batch seperti v1.0.

---

## Fase 2.3: Testing dan Deploy

| Tahap | Isi |
|---|---|
| 2.3.1 | Testing Internal menyeluruh (regresi penuh, audit RBAC ulang karena ada modul dan tabel baru, uji end-to-end lintas modul, verifikasi threshold poin baru dari 2.2.7 masuk akal) |
| 2.3.2 | Deployment perubahan ke Production (mengikuti pola dan pelajaran dari saga deployment v1.0, `DEPLOYMENT_CHECKLIST.md` diperbarui sesuai perubahan v2.0, termasuk langkah migrasi skema baru di database yang sama) |
| 2.3.3 | Dokumen Maintenance (backup data berkala, monitoring cron, panduan keberlangsungan kepemilikan) |
| 2.3.4 | Verifikasi Live dan Kriteria Keberhasilan tahap 1 (penilaian PIC dan developer) |

**Output:** v2.0 live, terverifikasi stabil, siap dibuka ke anggota untuk usability testing tahap 2 (siklus evaluasi berikutnya, di luar scope roadmap ini).

---

## Catatan Fleksibilitas

2.2.4, 2.2.5, 2.2.6 (tiga modul besar) independen satu sama lain secara data, urutan pengerjaan di antara ketiganya bisa fleksibel, asal 2.2.1-2.2.3 sudah kelar semua sebagai fondasi. 2.2.7 (hitung ulang threshold) HARUS setelah 2.2.6 selesai, ini satu-satunya urutan yang kaku di antara tahap-tahap besar tersebut.
