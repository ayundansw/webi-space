# Rencana Pembersihan Dokumen — WEBI-SPACE

**Status: TAHAP 1 (telusuri & lapor). Belum ada file yang dipindah/dihapus/digabung.
Menunggu approval eksplisit sebelum Tahap 2 dieksekusi.**

**SUPERSEDED (2026-07-13):** dokumen ini snapshot Tahap 1 dari 2026-07-09
(sebelum Fase 3-8 dikerjakan) — klaim faktual di dalamnya (mis. "22 file
kategori A... PERTAHANKAN, tidak disentuh", status "belum mulai dikerjakan
sama sekali" untuk Modul Manajemen Proyek/Praktik) SUDAH TIDAK AKURAT,
sekadar riwayat proses berpikir tahap ini. Rencana Tahap 2 di sini memang
akhirnya dieksekusi (jadi `docs/v_2.0/archive/RECON_ringkasan.md`), tapi
gelombang pembersihan lanjutan jauh lebih besar terjadi di task
"Konsolidasi Dokumentasi: 3 Dokumen Terpadu" (2026-07-13) — lihat
`docs/WEBI-SPACE.md`/`docs/WEBI-v1.0.md`/`docs/WEBI-v2.0.md` untuk kondisi
final. **Catatan teknis:** path di tabel bawah SUDAH ditimpa otomatis
(triase referensi kode task konsolidasi di atas) untuk mengikuti lokasi
file TERKINI (`docs/v_2.0/archive/sumber-konsolidasi/`) — path itu BUKAN
lokasi file pada saat dokumen ini ditulis (2026-07-09), jangan jadikan
acuan historis presisi soal "di mana file ini dulu berada".

Metodologi: inventaris seluruh `.md` di repo (di luar `vendor/`, `node_modules/`, `.git/`),
lalu untuk tiap file di-grep namanya ke seluruh kode (`app/`, `config/`, `database/`,
`resources/`, `routes/`, `tests/`) dan ke dokumen `.md` lain, untuk memastikan
tidak ada referensi aktif yang akan rusak kalau file dipindah/dihapus.

**Total file `.md` ditemukan: 30.**

---

## A. AKTIF-KANONIK (22 file) — PERTAHANKAN, tidak disentuh

| File | Kenapa masih acuan |
|---|---|
| `CLAUDE.md` | Instruksi proyek utama, dibaca tiap sesi. |
| `README.md` | Boilerplate Laravel standar. |
| `DEPLOYMENT_CHECKLIST.md` | Checklist deploy production, masih dipakai untuk 2.3.2 (belum dieksekusi penuh). |
| `docs/v_2.0/archive/sumber-konsolidasi/PRD.md` | Business rules/RBAC — dirujuk di CLAUDE.md, kode (`RouteAccessMatrixTest`, dll), dan hampir semua dokumen v2.0. |
| `docs/arsitektur-database.md` | Skema DB v1.0 — dirujuk CLAUDE.md, migrasi. |
| `docs/v_2.0/archive/sumber-konsolidasi/kurikulum-eksplorasi.md` | Blueprint konten kurikulum — dirujuk `CurriculumSeeder`, `ProactiveService`, config, test. |
| `docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md` | Spek WEBI — dirujuk luas di `app/Services/Webi/*`, test guardrail. |
| `docs/v_2.0/archive/sumber-konsolidasi/struktur-eksekusi.md` | Spek Eksekusi — dirujuk `AlertService`, `TaskService`, dll. |
| `docs/v_2.0/archive/sumber-konsolidasi/tech-stack.md` | Acuan stack & deployment — dirujuk `DEPLOYMENT_CHECKLIST.md`, kode. |
| `docs/v_2.0/archive/sumber-konsolidasi/Catatan_Troubleshooting_Deployment_WEBI-SPACE.md` | Log insiden deploy nyata — dirujuk CLAUDE.md & `DEPLOYMENT_CHECKLIST.md`, masih jadi rujukan kalau masalah serupa muncul lagi di 2.3.2. |
| `docs/v_2.0/archive/sumber-konsolidasi/design-tokens.md` | Token v1 — MASIH dipakai 100% di semua halaman kecuali dashboard Eksplorasi. |
| `docs/v_2.0/archive/sumber-konsolidasi/design-tokens-v2.md` | Token v2 aktif — sudah diimplementasikan nyata di `resources/css/app.css` dan `livewire/eksplorasi/dashboard.blade.php`. Menghapus/mengarsipkan ini akan memutus histori kenapa CSS sekarang begitu. |
| `docs/v_2.0/archive/sumber-konsolidasi/design-brief-v2.md` | Rasional arah desain v2 — dirujuk balik oleh `design-tokens-v2.md`, dan MASIH jadi acuan untuk batch penyebaran berikutnya (Eksekusi, Admin) yang belum dikerjakan. |
| `docs/v_2.0/archive/sumber-konsolidasi/Roadmap_Menyeluruh_WEBI-SPACE_v2.md` | Roadmap fase 2.2.4&ndash;2.3.4 yang BELUM selesai semua — masih peta jalan aktif. |
| `docs/v_2.0/archive/sumber-konsolidasi/2.1.2_Fiksasi_Fitur_dan_Cakupan_v2.md` | "Acuan tunggal fitur v2.0" (kata dokumennya sendiri) — masih jadi rujukan scope untuk 2.2.4&ndash;2.2.8 yang belum semua selesai. |
| `docs/v_2.0/archive/sumber-konsolidasi/2.1.3_Definisi_Konsep_Baru.md` | Indeks ke 3 dokumen Rancangan di bawah, yang semuanya masih aktif. |
| `docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Arsitektur_Konten_Dinamis_v2.md` | Dirujuk LANGSUNG oleh kode (`ContentBlock.php`, `HtmlSanitizer.php`, `content-block/custom-html.blade.php`) dan `content-blocks-spec.md`. Modul Konten Dinamis (2.2.4) baru fondasinya (2.2.4a) yang jadi, migrasi 67 unit + update WEBI belum. |
| `docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Modul_Manajemen_Proyek_v2.md` | Spek untuk 2.2.5 (Modul Manajemen Proyek) — **belum mulai dikerjakan sama sekali**, jangan diarsipkan sebelum dipakai. |
| `docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Modul_Praktik_v2.md` | Spek untuk 2.2.6 (Modul Praktik) — **belum mulai dikerjakan sama sekali**, jangan diarsipkan sebelum dipakai. |
| `docs/v_2.0/archive/sumber-konsolidasi/content-blocks-spec.md` | Dirujuk luas oleh kode aktif (`ContentBlock.php`, `SafeMarkdown.php`, `HtmlSanitizer.php`, view blok konten, `routes/web.php`, test) dan masih fondasi kerja 2.2.4 yang berjalan. |
| `docs/v_2.0/RECON_konten_dinamis.md` | **Kasus khusus** — walau namanya `RECON_*`, isinya BELUM sepenuhnya terkonsumsi: `Unit.php` mengutip §G17-nya untuk migrasi 67 unit yang **belum dikerjakan**, dan `content-blocks-spec.md` mengutip §B.6-nya sebagai justifikasi tipe blok tabel. Mengarsipkan ini sekarang berisiko memutus rujukan aktif untuk sisa pekerjaan 2.2.4. |
| `docs/v_2.0/RECON_sistem_poin.md` | **Kasus khusus** — dibuat sebagai bekal 2.2.6 (penyatuan sistem poin saat Modul Praktik dibangun), yang **belum dikerjakan**. Beda dari recon lain yang task targetnya sudah kelar. |

## B. HASIL RECON — sudah menjalankan fungsinya (6 file)

Keenamnya recon yang tugas targetnya **sudah selesai dikerjakan** di sesi ini (2.2.1 dan seluruh sub-modul 2.2.3). Dirujuk di beberapa tempat, tapi HANYA sebagai catatan historis/rasional keputusan yang sudah diambil, bukan kebutuhan aktif ke depan.

| File | Recon untuk tugas | Status tugas target | Dirujuk di mana |
|---|---|---|---|
| `RECON_v1_untuk_v2.md` | 2.2.1 (Fondasi Struktural) | Selesai | `config/navigation.php` (komentar, cuma sitasi historis "task 2.2.1c report") |
| `RECON_kuis.md` | 2.2.3 sub-modul 3 (Kuis v2.0) | Selesai | `RECON_kuis.md` sendiri tidak dirujuk balik dari manapun aktif |
| `RECON_peta_kurikulum.md` | 2.2.3 sub-modul 2 (Peta Kurikulum) | Selesai | Tidak ada referensi aktif ditemukan |
| `RECON_profil_akun.md` | 2.2.3 sub-modul 1 (Profil) | Selesai | Tidak ada referensi aktif ditemukan |
| `RECON_webi.md` | 2.2.3 sub-modul 4a/4b (WEBI riwayat+kontekstual) | Selesai | `tests/Feature/Webi/ChatTest.php` (komentar, sitasi rasional desain yang sudah final) |
| `RECON_dashboard.md` | 2.2.3 sub-modul 5 (Dashboard, batch 1+2) | Selesai | Tidak ada referensi aktif ditemukan |

**Rekomendasi: PINDAH KE ARSIP** (`docs/v_2.0/archive/`), bukan konsolidasi lossy jadi satu file — keenamnya membahas topik yang BERBEDA (bukan draft-draft dari topik yang sama), jadi menggabungkan isinya akan menghilangkan detail tanpa manfaat nyata. Sebagai gantinya, usulan **konsolidasi ringan**: buat satu file indeks baru `docs/v_2.0/archive/RECON_ringkasan.md` yang cuma berisi pointer (2-3 baris per recon: apa yang direcon, tugas apa yang mengonsumsinya, tanggal beres) — bukan menggabungkan isi lengkapnya. File asli tetap dipindah utuh ke folder arsip yang sama, cuma jadi lebih mudah ditelusuri lewat indeks ini.

Kalau dipindah, 2 komentar kode & 1 dokumen perlu diupdate pathnya (supaya tidak jadi link mati):
- `config/navigation.php` baris 17: `docs/v_2.0/RECON_v1_untuk_v2.md` &rarr; `docs/v_2.0/archive/sumber-konsolidasi/RECON_v1_untuk_v2.md`
- `tests/Feature/Webi/ChatTest.php` baris 184: `docs/v_2.0/RECON_webi.md` &rarr; `docs/v_2.0/archive/sumber-konsolidasi/RECON_webi.md`
- `docs/v_2.0/RECON_kuis.md` baris 258 mengutip `RECON_sistem_poin.md` — TIDAK perlu diupdate karena `RECON_sistem_poin.md` TIDAK dipindah (masih kategori A, lihat di atas). Tapi `RECON_kuis.md` sendiri ikut dipindah ke arsip, jadi sitasi ini tetap valid selama pembacanya paham keduanya sama-sama ada di lokasi baru/lama masing-masing.

## C. PROMPT LAMA / BRIEF INTERIM (1 file)

| File | Alasan |
|---|---|
| `docs/claude-design-prompt-v2.md` | Prompt satu-kali untuk eksplorasi visual di Claude Design (produk terpisah). Tugasnya sudah selesai — hasilnya sudah terkunci jadi `design-tokens-v2.md`. Nol referensi ditemukan di kode maupun dokumen lain. |

**Rekomendasi: PINDAH KE ARSIP**, bukan hapus — biaya menyimpan nyaris nol, dan berguna kalau nanti mau mengulang proses serupa untuk batch Eksekusi/Admin (lihat rencana penyebaran di `design-tokens-v2.md` §8).

## D. TIDAK JELAS / butuh keputusan Aye (1 file)

| File | Kenapa ambigu |
|---|---|
| `docs/v_2.0/2.1.1_Analisis_Triase_Catatan_Penilaian.md` | Isinya triase catatan penilaian v1.0 lama. `2.1.2_Fiksasi_Fitur_dan_Cakupan_v2.md` secara eksplisit bilang dirinya "Hasil kompilasi seluruh keputusan dari 2.1.1 dan tiga dokumen rancangan" — artinya kesimpulan 2.1.1 SUDAH terserap penuh ke 2.1.2. Tidak ada kode/dokumen lain yang merujuknya. Dua opsi: **(1)** treat sama seperti kategori B (recon yang sudah beres tugasnya) &rarr; PINDAH KE ARSIP, karena 2.1.2 sudah jadi acuan tunggal yang menggantikannya; **(2)** pertahankan sebagai bagian dari "trilogi" dokumen fase 2.1 (2.1.1/2.1.2/2.1.3) yang jadi catatan resmi keputusan awal v2.0, serupa PRD v1.0 — kalau nanti perlu telusur ulang KENAPA sebuah fitur di-skip/dipilih, detail triase ada di sini, tidak di 2.1.2 yang cuma ringkasan. |

Rekomendasi saya condong ke opsi (1) PINDAH KE ARSIP (bukan hapus) — tapi ini genuinely keputusan yang menurutku pantas Aye ambil sendiri, karena menyangkut nilai historis, bukan soal teknis.

---

## Ringkasan Tindakan yang Diusulkan (Tahap 2, MENUNGGU APPROVAL)

1. **HAPUS permanen: TIDAK ADA.** Tidak ditemukan file yang benar-benar tidak berharga sebagai riwayat dan tidak dirujuk sama sekali — semua kandidat pembersihan direkomendasikan diarsipkan (dipindah), bukan dihapus, karena biaya menyimpan beberapa file `.md` kecil jauh lebih rendah daripada risiko kehilangan riwayat kalau ternyata masih dibutuhkan.
2. **PINDAH KE ARSIP** (`docs/v_2.0/archive/`, folder baru):
   - `RECON_v1_untuk_v2.md`, `RECON_kuis.md`, `RECON_peta_kurikulum.md`, `RECON_profil_akun.md`, `RECON_webi.md`, `RECON_dashboard.md`
   - `claude-design-prompt-v2.md` (dari `docs/` ke `docs/v_2.0/archive/`)
   - *(menunggu keputusan)* `2.1.1_Analisis_Triase_Catatan_Penilaian.md`
3. **KONSOLIDASI (ringan, bukan lossy merge):** buat `docs/v_2.0/archive/RECON_ringkasan.md` sebagai indeks pointer ke 6 file RECON yang diarsipkan.
4. **UPDATE REFERENSI** (2 file kode) supaya tidak jadi link mati: `config/navigation.php` baris 17, `tests/Feature/Webi/ChatTest.php` baris 184.
5. **PERTAHANKAN di lokasi sekarang:** 22 file kategori A (lihat tabel di atas) — termasuk 2 recon "kasus khusus" (`RECON_konten_dinamis.md`, `RECON_sistem_poin.md`) yang isinya belum selesai terkonsumsi, jangan sampai ikut diarsipkan karena penamaan `RECON_*`-nya menyesatkan.

**Status: MENUNGGU APPROVAL Aye sebelum eksekusi.**
