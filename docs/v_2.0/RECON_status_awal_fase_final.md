# Recon — Status Repo Menyeluruh (Sebelum Fase 2, Roadmap Eksekusi Final v2.0)

Laporan ini murni observasi kode & dokumen NYATA saat ini. **Tidak ada
kode/skema/dokumen yang diubah.**

## A. Inventaris Dokumen Status yang Sudah Ada

### A1. Isi `docs/v_2.0/` (aktif, di luar arsip)

| File | Ringkasan 1 baris |
|---|---|
| `2.1.2_Fiksasi_Fitur_dan_Cakupan_v2.md` | Acuan tunggal scope fitur v2.0 (setara PRD untuk v2.0) — masih berlaku untuk sisa 2.2.4-2.2.8. |
| `2.1.3_Definisi_Konsep_Baru.md` | Indeks 3 dokumen Rancangan di bawah + ketergantungan antar-dokumen. |
| `Rancangan_Arsitektur_Konten_Dinamis_v2.md` | Spek sistem blok konten (9 tipe) — rujukan langsung kode `ContentBlock`/`HtmlSanitizer`. |
| `Rancangan_Modul_Manajemen_Proyek_v2.md` | Spek rombak Eksekusi (Kalender, Gantt, Kanban+slide-over, Forum+subtask) — untuk 2.2.5, **belum dikerjakan**. |
| `Rancangan_Modul_Praktik_v2.md` | Spek challenge project berjenjang + track map + review — untuk 2.2.6, **belum dikerjakan**. |
| `Roadmap_Menyeluruh_WEBI-SPACE_v2.md` | Peta jalan lengkap Fase 2.1-2.3, urutan tahap 2.2.1-2.2.8. |
| `content-blocks-spec.md` | Spek teknis konkret 9 tipe blok konten + aturan sanitasi — turunan dari Rancangan Konten Dinamis, sudah diimplementasikan (fondasi 2.2.4a). |
| `RECON_konten_dinamis.md` | Recon Modul Konten Dinamis v1.0 — **masih dirujuk aktif** (`Unit.php` §G17, `content-blocks-spec.md` §B.6) karena migrasi 67 unit belum selesai. |
| `RECON_sistem_poin.md` | Recon sistem poin v1.0 — bekal 2.2.6 (penyatuan poin saat Modul Praktik dibangun), **belum dikerjakan**, jadi masih relevan. |
| `RECON_forum.md` | *(dibuat sesi ini)* Skema Forum: `module_id`/`unit_id` SUDAH nullable di DB, tapi `Forum\Create::save()` memaksa salah satu wajib diisi — jadi thread general/bebas modul TIDAK butuh migrasi, cuma ubah logic app. |
| `RECON_project_member_peran.md` | *(dibuat sesi ini)* Tabel `project_members` TIDAK punya kolom peran/jabatan sama sekali — perlu migrasi kolom baru (nullable, additive, risiko rendah) untuk fitur "peran per proyek" di Profil. |
| `RECON_evaluasi_kuis.md` | *(dibuat sesi ini)* Struktur soal/kunci jawaban di tabel `unit_evaluations` (json `options`/`correct_answer`), beda kompleksitas tiap `question_type` (matching paling rumit/asimetris, ordering punya temuan UX tersembunyi soal urutan awal = urutan benar). |
| `RECON_referensi.md` | *(dibuat sesi ini)* Tabel `learning_resources` tidak punya `created_by` sama sekali, cuma bisa diisi lewat seeder (nol UI) — butuh migrasi + Livewire component BARU dari nol untuk submission member. |
| `RENCANA_PEMBERSIHAN.md` | Rencana pembersihan dokumen (lihat A3). |

### A2. `CLAUDE.md` — ringkasan status/catatan terakhir

- **Progress Fase 2 tercatat**: 2.0-2.7 dan 2.9 ditandai selesai (`[x]`); **2.8 (Deployment Live) masih `[ ]`** — persiapan kode sudah selesai (`.env.example`, build asset, scheduler, `DEPLOYMENT_CHECKLIST.md`), tapi eksekusi manual di server production ("APP_DEBUG production", test manual `app:create-admin` di terminal asli) secara eksplisit didelegasikan ke user, bukan tugas Claude Code.
- Berisi log gaps/backlog terstruktur per task (2.3 sampai 2.9), sebagian besar berstatus **RESOLVED** dengan detail akar masalah + perbaikan, beberapa masih terbuka sebagai keputusan yang menunggu konfirmasi eksplisit (mis. varian "user" di `context_type` enum, threshold `evaluation_reminder`).
- **Tidak menyebutkan apa pun soal 4 recon baru sesi ini** (Forum, ProjectMember peran, evaluasi kuis, referensi) — konsisten, karena keempatnya READ-ONLY (belum ada implementasi yang perlu dicatat sebagai gap/resolved).
- **Tidak menyebutkan Fase 2 dashboard batch 1/2, pembersihan dokumen, atau design-tokens-v2** secara eksplisit sebagai entri baru — dokumen ini terakhir diupdate di tahap 2.9 (2026-07-04), sedangkan pekerjaan dashboard/design-token-v2/pembersihan dokumen terjadi di sesi-sesi SETELAH itu tanpa CLAUDE.md ikut diperbarui. **Ini kemungkinan celah dokumentasi** yang layak diperhatikan — CLAUDE.md belum mencerminkan pekerjaan v2.0 terbaru (dashboard 3-portal redesign, design-tokens-v2, recon-recon baru).

### A3. `docs/v_2.0/RENCANA_PEMBERSIHAN.md` — konfirmasi eksekusi

**Sudah dieksekusi sesuai rencana, dikonfirmasi langsung dari isi folder saat ini:**
- `docs/v_2.0/archive/` ada dan berisi persis 8 file yang direncanakan dipindah: `2.1.1_Analisis_Triase_Catatan_Penilaian.md`, `RECON_dashboard.md`, `RECON_kuis.md`, `RECON_peta_kurikulum.md`, `RECON_profil_akun.md`, `RECON_v1_untuk_v2.md`, `RECON_webi.md`, `claude-design-prompt-v2.md`.
- `RECON_ringkasan.md` (indeks konsolidasi ringan) ada di folder arsip, sesuai rencana.
- `docs/v_2.0/` (di luar arsip) HANYA berisi file kategori "PERTAHANKAN" dari rencana, ditambah 4 recon baru yang dibuat setelah pembersihan (Forum, ProjectMember, evaluasi kuis, referensi) — konsisten, wajar untuk dokumen baru pasca-pembersihan.
- `config/navigation.php` baris 17 dan (belum dicek ulang literal di sini, tapi tercatat sudah dieksekusi di laporan sebelumnya) `tests/Feature/Webi/ChatTest.php` sudah menunjuk ke path arsip — dikonfirmasi ulang: `config/navigation.php` baris 17 sekarang berbunyi `docs/v_2.0/archive/sumber-konsolidasi/RECON_v1_untuk_v2.md` (lihat poin D9 di bawah untuk isi lengkapnya).

**Kesimpulan: pembersihan dokumen tuntas, tidak ada pekerjaan tersisa dari rencana itu.**

## B. Status Kode Sekarang (Fondasi Visual)

### B4. `resources/css/app.css` — isi lengkap blok `@theme`

```css
@theme {
    --font-sans: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';
    --font-display: 'Sora', ui-sans-serif, system-ui, sans-serif;
    --font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;

    --color-ink: #1C1515;
    --color-muted: #979393;
    --color-accent: #05D9E7;
    --color-accent-soft: #D1F8FF;

    /* Design token v2 (docs/v_2.0/archive/sumber-konsolidasi/design-tokens-v2.md) — additive only ... */
    --color-warm: #FF7F50;
    --color-warm-soft: #FFE8DD;
    --color-surface: #FAF8F6;
    --color-surface-alt: #F3F0EC;

    --color-success: #2FA872;
    --color-success-soft: #E4F5EC;
    --color-warning: #E8A23C;
    --color-warning-soft: #FCF0DC;
    --color-danger: #E1594B;
    --color-danger-soft: #FBEAE7;

    --shadow-warm-xs: 0 1px 2px 0 rgb(28 21 21 / 0.05), 0 1px 1px 0 rgb(28 21 21 / 0.03);
    --shadow-warm-md: 0 6px 16px -4px rgb(28 21 21 / 0.10), 0 3px 6px -2px rgb(28 21 21 / 0.06);
    --shadow-warm-lg: 0 16px 32px -8px rgb(28 21 21 / 0.16), 0 6px 12px -4px rgb(28 21 21 / 0.08);
}
```

**Kondisi: BERSIH, tidak ada sisa eksperimen yang setengah jadi.** Token v1 (baris 12-15) tidak disentuh sama sekali. Token v2 (`--color-warm*`, `--color-surface*`, semantik, `--shadow-warm-*`) ditambahkan secara ADDITIVE dengan komentar eksplisit menjelaskan cakupannya ("BARU diterapkan ke satu halaman: dashboard Eksplorasi + `<x-stat-card>`"). Ini PERSIS cocok dengan yang dilaporkan `docs/v_2.0/archive/sumber-konsolidasi/design-tokens-v2.md` — tidak ada drift antara dokumen dan kode. `<x-stat-card>` (`resources/views/components/stat-card.blade.php`) mengonfirmasi hal yang sama: prop `elevated`/`accent` opt-in, default lama tidak berubah (dipakai persis apa adanya di KPI row Admin dashboard).

### B5. `resources/views/components/shell/` — struktur sidebar/navbar

Dua file: `navbar.blade.php` (header sticky, tombol toggle sidebar, logo, `<livewire:notifications.bell />`, tombol keluar) dan `sidebar.blade.php` (menu per role dari `config('navigation.'.role)`, ikon inline SVG di-key oleh label, highlight active-state termasuk suffix-widening untuk route CRUD, dukungan collapse desktop + drawer mobile via Alpine `sidebarCollapsed`/`mobileSidebarOpen`).

**Konfirmasi: masih struktur sidebar hasil 2.2.1, TIDAK ada perubahan arsitektur shell** sejak recon terakhir (`RECON_v1_untuk_v2.md`, sekarang di arsip) — cuma isi datanya (jumlah/label menu) yang mungkin bertambah seiring modul baru (lihat D9, ada beberapa slot `enabled: false` untuk fitur v2.0 yang belum dibangun: "Antrian Review Praktik", "Praktik", "Kalender Personal", "Forum General").

### B6. `<x-content-blocks>`, model `ContentBlock`, migrasi terkait

**Semua masih ada, konsisten dengan `RECON_konten_dinamis.md` dan `content-blocks-spec.md`, tidak ada perubahan:**
- Model: `app/Models/ContentBlock.php` — ada.
- Migrasi: `database/migrations/2026_07_09_000001_create_content_blocks_table.php` — ada.
- Komponen render per tipe (9 tipe, sesuai spec): `resources/views/components/content-block/{heading,text,image,callout,code,video,list,table,custom-html}.blade.php` — SEMUA 9 ada.
- Wrapper: `resources/views/components/content-blocks.blade.php` — ada.
- Service pendukung: `app/Services/Content/HtmlSanitizer.php`, `app/Services/Content/SafeMarkdown.php` — ada.

**Tidak ada drift** dari yang dilaporkan `RECON_konten_dinamis.md` — fondasi 2.2.4a utuh, migrasi 67 unit + update `CurriculumContextBuilder` WEBI masih belum dikerjakan (sesuai catatan "belum ada satu pun unit produksi yang memakai ini" di `content-blocks-spec.md`).

## C. Status Test Suite

**`php artisan test`: 277 total, 277 lolos (passed), 0 gagal.** 995 assertion, durasi ~102 detik. Suite hijau bersih, tidak ada test gagal untuk dilaporkan.

## D. Struktur Umum

### D8. `app/Livewire/` dan `resources/views/livewire/` (2 level)

```
app/Livewire/                          resources/views/livewire/
├── Admin/ (Dashboard.php)             ├── admin/
│   ├── Users/                         │   ├── users/
│   └── Webi/                          │   └── webi/
├── Auth/ (Login.php)                  ├── auth/
├── Eksekusi/ (Dashboard.php)          ├── eksekusi/
│   ├── Ideas/                         │   ├── ideas/
│   ├── Projects/                      │   ├── projects/
│   └── Tasks/                         │   └── tasks/
├── Eksplorasi/ (Dashboard.php,         ├── eksplorasi/
│   PetaKurikulum.php, UnitShow.php,
│   UnitEvaluation.php,
│   CheckpointShow.php)
│   ├── Forum/                         │   ├── forum/
│   ├── Resources/                     │   ├── resources/
│   └── Webi/                          │   └── webi/
├── Notifications/ (Bell.php, Index.php) ├── notifications/
└── Profile/ (Edit.php)                └── profile/
```

**Konvensi penamaan KONSISTEN**: struktur folder `app/Livewire/<Modul>/<SubFitur>/<Aksi>.php` mencerminkan 1:1 ke `resources/views/livewire/<modul>/<sub-fitur>/<aksi>.blade.php` (PascalCase di PHP namespace, lowercase-dash di path view) — tidak ada penyimpangan pola ditemukan di seluruh pohon 2 level ini, termasuk penambahan modul BARU sejak recon-recon sebelumnya (`Eksekusi/Dashboard.php` dari batch dashboard, `Profile/Edit.php` dari sub-modul profil).

### D9. `config/navigation.php` — isi lengkap

Sudah ditunjukkan lengkap di poin B5 di atas (49 baris). Ringkasan strukturnya: array asosiatif per role (`admin`, `exploration_member`, `execution_member`), tiap item `['label', 'route', 'enabled', 'active_when'?]`. **Slot placeholder untuk fitur v2.0 yang BELUM dibangun** (`route: null, enabled: false`, dirender sebagai item nonaktif/abu-abu di sidebar): "Antrian Review Praktik" (admin, untuk 2.2.6), "Praktik" (exploration_member, untuk 2.2.6), "Kalender Personal" dan "Forum General" (execution_member, untuk 2.2.5) — slot-slot ini SUDAH DISIAPKAN di data menu, tinggal diisi `route` begitu fitur terkait dibangun di Fase 2.2.5/2.2.6, TIDAK perlu migrasi data menu baru saat itu tiba.

Komentar baris 17 mengonfirmasi path referensi RECON sudah diupdate ke lokasi arsip (`docs/v_2.0/archive/sumber-konsolidasi/RECON_v1_untuk_v2.md`) — pembersihan dokumen di poin A3 terbukti benar-benar dieksekusi, bukan cuma diklaim.

---

## Ringkasan Temuan Kunci untuk Fase 2

1. **Base yang bisa dipakai ulang tanpa recon ulang**: `RECON_konten_dinamis.md` dan `RECON_sistem_poin.md` (masih aktif, belum terkonsumsi penuh) + 4 recon baru sesi ini (Forum, ProjectMember peran, evaluasi kuis, referensi) — kelimanya sudah punya pemetaan skema/kode lengkap, siap jadi input task implementasi kapan saja tanpa perlu dibaca ulang dari nol.
2. **`app.css`/design-tokens-v2 bersih**, tidak ada utang teknis atau eksperimen setengah jadi yang perlu dibereskan sebelum Fase 2 mulai.
3. **Shell (navbar/sidebar) stabil**, sudah punya slot navigasi untuk fitur 2.2.5/2.2.6 yang belum dibangun — tidak perlu restrukturisasi, cukup isi slot yang sudah ada.
4. **Modul Konten Dinamis fondasinya utuh** (2.2.4a), tapi migrasi 67 unit + update WEBI (`CurriculumContextBuilder`) MASIH TERUTANG — ini pekerjaan besar yang belum tersentuh sejak fondasinya dibangun.
5. **Test suite hijau bersih (277/277)** — aman untuk mulai Fase 2 tanpa technical debt test yang mengganjal.
6. **Yang mengejutkan/perlu diperhatikan**: `CLAUDE.md` belum diperbarui untuk mencerminkan pekerjaan v2.0 terbaru (dashboard 3-portal, design-tokens-v2, pembersihan dokumen, 4 recon baru) — dokumen ini terakhir menyebut tahap 2.9. Ini bukan bug, tapi celah dokumentasi yang mungkin ingin diperbarui di titik yang tepat (mis. sebelum Fase 2 mulai, atau sebagai bagian dari 2.3.1 Testing Internal nanti) supaya siapa pun yang membaca `CLAUDE.md` mendapat gambaran akurat status terkini.
