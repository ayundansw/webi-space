# Recon — Dashboard Tiga Portal v1.0

Laporan ini murni observasi kode NYATA saat ini. Dibuat sebagai bekal
redesign dashboard ketiga portal (2.2.3, dikerjakan terakhir). **Tidak ada
kode yang diubah.**

## A. Dashboard Admin

**1. Path lengkap:**
- Class: `app/Livewire/Admin/Dashboard.php`
- View: `resources/views/livewire/admin/dashboard.blade.php`
- Route: `/admin/dashboard` (`routes/web.php`, `middleware(['auth', 'role:admin'])`, nama route `admin.dashboard`)

**2. Elemen yang ditampilkan (urut dari atas):**
| Elemen | Sumber data |
|---|---|
| `<x-greeting />` (2.2.2a) | Komponen bersama, baca `auth()->user()` |
| Tabel "Progres Anggota" (nama, unit sedang dikerjakan, level, progres%) | `explorationLeaderboard()` — semua `exploration_member`, `ProgressService::ensureProgress()` + `overallProgressPercentage()` |
| "Leaderboard" (ranking penuh, SEMUA anggota, bukan Top 5) | Method **sama persis** (`explorationLeaderboard()`), cuma ditampilkan beda (nomor urut + poin) |
| Kartu "Log Percakapan WEBI" (jumlah pesan, anggota, guardrail flag, pesan terakhir) | `webiSummary()` — `Message::count()`, `GuardrailFlag::count()`, `Message::max('created_at')` |
| "Ringkasan Proyek Aktif" (kartu per proyek: progres, anggota aktif, hari tersisa, milestone) | `projectSummary()` per `Project` berstatus `active`/`on_hold` |
| "Alert Panel" (OVERDUE, MILESTONE AT RISK, PROJECT IDLE, STALLED, INACTIVE MEMBER, DUE SOON) | `alertPanel()` → `App\Services\Execution\AlertService` (modul Eksekusi 2.4, tidak disentuh) |
| "Feed Progress Update Terbaru" (15 terbaru) | `recentProgressUpdates()` — `ProgressUpdate` lintas semua proyek aktif |
| Tabel "Ringkasan Aktivitas Anggota" (todo/in_progress/in_review/done/update terakhir/overdue per anggota eksekusi) | `memberSummaries()` — per `execution_member` |

**3. Dua tabel yang disebut di konteks:**
- **"Progres Anggota"**: kolom Anggota, Sedang Dikerjakan (judul unit atau "Belum mulai"), Level (`level_name`, font mono), Progres (bar + persentase). Data per baris dari `explorationLeaderboard()`.
- **"Ringkasan Aktivitas Anggota"**: kolom Anggota, Todo, In Progress, In Review, Done (masing-masing COUNT task per status), Update Terakhir (`diffForHumans`), Task Overdue (merah kalau > 0). Data dari `memberSummaries()`, murni Eksekusi.
- **Leaderboard admin ADA di section yang sama** dengan tabel Progres Anggota (bersebelahan, `lg:col-span-3` vs `lg:col-span-2` dalam satu grid) — bukan section terpisah, dan sumber data-nya SAMA PERSIS dengan tabel Progres Anggota (satu pemanggilan `explorationLeaderboard()`, dipakai dua kali untuk dua presentasi berbeda).

**4. Query berat / N+1 risk — ADA, level "per-baris", bukan klasik N+1 model
relation:**
- `explorationLeaderboard()`: untuk SETIAP `exploration_member`, memanggil
  `ensureProgress()` (query `firstOrCreate`) DAN `overallProgressPercentage()`
  (query `count`) secara terpisah — 2 query tambahan per anggota eksplorasi.
- `memberSummaries()`: untuk SETIAP `execution_member`, query `Task` +
  query `ProgressUpdate::max()` terpisah — 2 query tambahan per anggota eksekusi.
- `projectSummary()`: untuk SETIAP proyek aktif/on_hold, query `Task`
  terpisah (untuk hitung anggota aktif).
- Untuk ukuran tim yang disebut di project ("tim kecil") ini bukan
  masalah performa mendesak, tapi kalau tim membesar, pola "satu query per
  baris" ini pertama yang perlu dioptimasi (mis. eager-load atau agregasi
  SQL langsung).

## B. Dashboard Eksplorasi

**5. Path lengkap:**
- Class: `app/Livewire/Eksplorasi/Dashboard.php`
- View: `resources/views/livewire/eksplorasi/dashboard.blade.php`
- Route: `/eksplorasi/dashboard` (`middleware(['auth', 'role:exploration_member'])`, nama route `eksplorasi.dashboard`)

**6. Elemen yang ditampilkan:**
| Elemen | Sumber data |
|---|---|
| `<x-greeting />` | Sama seperti admin |
| 3 kartu statistik (Level Saat Ini, Total Poin, Progres Keseluruhan + bar) | `$userProgress` (`ProgressService::ensureProgress()`), `$overallPercentage` |
| Leaderboard Top 5 + baris "Kamu peringkat N" (2.2.2c) | `ProgressService::memberLeaderboard($user)` — **method BERBEDA** dari `explorationLeaderboard()` admin, sengaja hanya balikin Top 5 + posisi diri (batasan privasi, lihat memory task 2.2.2c) |
| "Sedang Dikerjakan" (unit berikutnya + tombol lanjut, atau pesan "semua selesai") | `ProgressService::nextUnitFor($user)` |
| "Log Aktivitas" (feed unit/checkpoint selesai) | `ProgressService::feedFor($user)` |

**Tidak ada** elemen lain di luar yang disebutkan di atas (tidak ada
statistik Eksekusi, tidak ada apa pun soal proyek — dashboard ini murni
Eksplorasi, sesuai batasan modul).

**7. `overallProgressPercentage()`** (`ProgressService.php:253-264`):
`COUNT(UserUnitProgress WHERE user_id=... AND status=completed) / COUNT(seluruh Unit) * 100`, dibulatkan. Sederhana, satu query pembilang + satu query penyebut (bukan per-baris karena cuma dipanggil sekali untuk user yang login, beda dari versi admin yang memanggilnya per-anggota).

**8. Entry point WEBI di dashboard: TIDAK ADA sama sekali.** Digrep
`webi`/`Webi` di `livewire/eksplorasi/dashboard.blade.php` — nol hasil.
WEBI diakses HANYA dari: (a) menu sidebar "WEBI" (halaman chat penuh), atau
(b) panel kontekstual di halaman unit (4b-2, tombol "Tanya WEBI"). Dashboard
Eksplorasi TIDAK py hyperlink/ringkasan/entry point apa pun ke WEBI — beda
dari dashboard Admin yang justru punya kartu ringkasan WEBI lengkap
(jumlah pesan, flag, link ke log). Ini asimetri yang mungkin relevan untuk
redesign: member sendiri tidak diingatkan WEBI ada di dashboard-nya.

## C. Dashboard Eksekusi

**9. Konfirmasi: `resources/views/eksekusi/dashboard.blade.php` adalah
dashboard `execution_member`, BUKAN Livewire.** Route (`routes/web.php:70-72`):
```php
Route::get('/eksekusi/dashboard', function () {
    return view('eksekusi.dashboard');
})->middleware(['auth', 'role:execution_member'])->name('eksekusi.dashboard');
```
Closure biasa yang langsung `return view(...)` — tidak ada class Livewire,
tidak ada component, tidak ada `render()` dengan logic apa pun. View-nya
sendiri (`<x-layouts.app title="Dashboard Eksekusi">...`) juga tidak
menerima variabel data apa pun dari controller/closure (tidak ada array
kedua di `view(...)`).

**Kenapa Blade biasa, bukan Livewire (dikonfirmasi dari histori proyek,
bukan cuma dugaan):** file ini SENGAJA dibuat sesederhana mungkin sejak
awal (task 2.1 era) — cuma dua tombol link (Project Ideas, Proyek Saya),
tidak pernah ada logic/data dinamis yang butuh Livewire reactivity. Ini
BUKAN keputusan v2.0, murni peninggalan v1.0 yang belum pernah disentuh
karena memang tidak butuh interaktivitas apa pun sampai sekarang.

**10. Yang ditampilkan sekarang:** `<x-greeting />`, satu kalimat
("Mulai dari salah satu menu di bawah."), dan **cuma 2 link tombol**:
"Project Ideas" (`/eksekusi/ideas`) dan "Proyek Saya" (`/eksekusi/projects`).
**Tidak ada** statistik, tidak ada ringkasan proyek, tidak ada alert, tidak
ada apa pun yang menunjukkan DATA — murni halaman navigasi kosong dengan
2 tombol. Ini yang PALING minim dari ketiga dashboard, sangat jauh dari
"dashboard" dalam arti biasa (ringkasan informasi).

**11. Tumpang tindih dengan dashboard admin — TIDAK ADA kebingungan data,
tapi ADA kesenjangan pengalaman:**
- Admin **melihat SEMUA proyek + SEMUA anggota eksekusi** (agregat lintas
  tim) di `/admin/dashboard` — data mentah dari `Project`/`Task`/`ProgressUpdate`
  yang sama.
- Execution member **tidak melihat data proyek/task/alert apa pun** di
  dashboard-nya sendiri — dia harus PINDAH ke `/eksekusi/projects` untuk
  lihat ringkasan proyeknya sendiri (halaman terpisah, `Eksekusi\Projects\Index`,
  yang justru SUDAH menampilkan ringkasan progres per proyek — bukan
  "tidak ada data sama sekali di aplikasi", tapi "tidak ada di dashboard,
  cuma satu klik lagi").
- Tidak ada RISIKO kebocoran data lintas-anggota (masing-masing halaman
  sudah scoped benar per role/kepemilikan) — murni soal *dashboard eksekusi
  saat ini tidak berfungsi sebagai dashboard sungguhan*, cuma jadi
  halaman "menu tengah" yang hampir kosong.

## D. Komponen bersama

**12. `<x-greeting />` — konsisten dipasang di ketiga dashboard**, dikonfirmasi
baris pertama konten di ketiganya (`admin/dashboard.blade.php:2`,
`eksplorasi/dashboard.blade.php:2`, `eksekusi/dashboard.blade.php:2`). Path
komponen: `resources/views/components/greeting.blade.php` (dari 2.2.2a).

**13. Tidak ada komponen/pola kartu statistik bersama.** Ketiga dashboard
(dan bahkan dalam SATU dashboard admin sendiri) menulis markup kartu
statistik/tabel masing-masing dari nol — pola visual MIRIP (`rounded-xl
border border-muted/25 p-5` untuk kartu statistik, dipakai identik di
admin utk tidak ada, tapi eksplorasi & — cek ulang: admin dashboard
sebenarnya TIDAK punya kartu statistik kotak sederhana seperti eksplorasi,
strukturnya lebih ke tabel+section), tapi TIDAK ADA `<x-stat-card>` atau
komponen serupa yang di-reuse lewat kode. Ini murni kemiripan visual by
convention (class Tailwind yang sama diulang manual), bukan komponen
bersama sungguhan.

**14. Notifikasi/feed aktivitas per dashboard:**
- Admin: "Feed Progress Update Terbaru" (15 item lintas SEMUA proyek
  aktif) + "Alert Panel" (6 jenis alert Eksekusi).
- Eksplorasi: "Log Aktivitas" (`feedFor()`, unit/checkpoint selesai MILIK
  USER SENDIRI saja, bukan lintas-anggota).
- Eksekusi (`eksekusi/dashboard.blade.php`): **tidak ada feed/notifikasi
  apa pun** — kosong sama sekali di dashboard ini.
- **Terpisah dari dashboard**: sistem notifikasi terpadu (bell icon,
  `Notifications\Bell`, 2.6b) muncul di NAVBAR, bukan di dashboard manapun
  — jadi "notifikasi" sungguhan (task assigned, comment, dst) sudah ada
  infrastrukturnya di luar ketiga dashboard ini, tidak perlu dibangun ulang.

## E. Konsistensi & gaya

**15. Ketiga dashboard TIDAK seragam:**
- Admin: paling padat, tabel + grid + banyak section, terasa seperti
  "control panel" (memang untuk role admin, boleh lebih padat per
  `docs/design-tokens.md`).
- Eksplorasi: kartu statistik rapi (3 kolom), leaderboard, cukup lapang,
  sudah terasa seperti dashboard modern setelah 2.2.2a/2.2.2c.
  Konsisten memakai token warna/font dengan benar.
  - Konsisten memakai token yang sama (radius, warna) dengan admin,
    tapi STRUKTUR layoutnya beda total (kartu vs tabel).
- Eksekusi: nyaris kosong, cuma 2 tombol — tidak "berantakan" secara
  visual (karena memang nyaris tidak ada konten untuk berantakan), tapi
  paling JAUH dari kelayakan sebagai dashboard.

**16. Yang PALING butuh redesign: Dashboard Eksekusi**, bukan karena
tampilannya jelek/berantakan, tapi karena SECARA FUNGSIONAL nyaris kosong
— tidak menampilkan satu pun data (padahal datanya SUDAH ada dan sudah
dipakai di halaman lain: ringkasan proyek per-anggota sudah ada logic-nya
di `Eksekusi\Projects\Index`, alert per-proyek sudah ada logic-nya di
`AlertService` yang dipakai admin). Dashboard Admin urutan kedua (bukan
soal kerapian, tapi soal PADAT — dua tabel "Progres Anggota" dan
"Ringkasan Aktivitas" panjang ke bawah, belum ada ringkasan visual
level-atas/kartu KPI sebelum detail tabel).

## F. Test terkait

| File | Menguji DATA (harus tetap hijau) | Sekadar `assertSee` teks |
|---|---|---|
| `tests/Feature/Admin/DashboardTest.php` | `test_admin_leaderboard_ranks_members_by_points_descending_and_member_also_sees_their_own` (urutan ranking benar), `test_exploration_progress_shown_to_admin_matches_the_members_own_dashboard` (konsistensi data lintas dashboard), `test_webi_summary_card_links_to_the_full_log_page`, `test_eksekusi_section_still_shows_project_summary_and_alerts` | `test_unified_dashboard_shows_both_eksplorasi_and_eksekusi_sections` (assertSee judul section) |
| `tests/Feature/Exploration/DashboardTest.php` | `test_dashboard_reflects_points_level_and_feed_after_progress`, `test_top5_member_sees_top5_with_own_row_highlighted_and_appreciation_message`, `test_non_top5_member_sees_top5_plus_own_rank_and_motivation_message`, `test_members_outside_top5_and_self_never_leak_into_the_response` (KEAMANAN, paling penting), `test_leaderboard_tiebreak_ranks_whoever_reached_the_points_first` | `test_dashboard_shows_starting_state_for_new_user`, `test_dashboard_shows_leaderboard_to_member_per_v2_reversal` (sebagian assertSee teks) |
| `tests/Feature/Rbac/DashboardAccessTest.php`, `RouteAccessMatrixTest.php` | Akses per role ke tiap route dashboard (admin/eksplorasi/eksekusi saling tolak) | — |
| `tests/Feature/Integration/FullSystemSmokeTest.php`, `CrossModuleEndToEndTest.php`, `Rbac/CrossModuleDataConsistencyTest.php` | Smoke test lintas modul yang MELEWATI dashboard sebagai bagian alur, bukan menguji dashboard itu sendiri secara spesifik | — |

**Tidak ada test khusus untuk `eksekusi/dashboard.blade.php`** di luar
RBAC (akses route). Masuk akal — karena view itu sendiri tidak py logic/data
untuk diuji, cuma 2 link statis.

## G. Penilaian

**18. Bisa dipakai ulang vs perlu dibangun:**
- **Dipakai ulang penuh, tidak perlu sentuh:** `<x-greeting />`, seluruh
  query/service (`AlertService`, `ProgressService::feedFor/nextUnitFor/
  overallProgressPercentage/memberLeaderboard`, `explorationLeaderboard()`
  di admin) — semua logic data SUDAH BENAR dan SUDAH DITES, redesign
  MURNI lapisan tampilan untuk ketiganya (per prinsip yang sama dengan
  redesign Peta Kurikulum/Chat WEBI sebelumnya di 2.2.3).
- **Perlu ditambahkan (bukan diubah) untuk Dashboard Eksekusi:** hampir
  semua data yang perlu ditampilkan SUDAH ADA logic-nya di tempat lain
  (`Eksekusi\Projects\Index` untuk ringkasan proyek milik anggota,
  `AlertService` untuk alert — tapi `AlertService` sekarang HANYA
  dipanggil dari `Admin\Dashboard`, perlu dicek apakah method-methodnya
  aman dipanggil discope ke SATU user/proyek yang diikuti, bukan cuma
  "semua proyek" seperti sekarang dipakai admin).
- **Kandidat komponen bersama BARU (belum ada, kalau mau dibangun):**
  `<x-stat-card>` sederhana (angka besar + label + opsional bar progres)
  — pola ini sudah 3x diulang manual (2 di eksplorasi, beberapa di admin)
  dengan markup nyaris identik, calon nyata untuk diekstrak jadi komponen
  Blade bersama supaya konsisten otomatis, bukan konsisten by convention.

**19. Titik risiko paling mungkin menyentuh logic (bukan cuma tampilan):**
- **Dashboard Eksekusi** — kalau redesign menambah data proyek/alert ke
  situ, ini BUKAN murni tampilan lagi, perlu memanggil `AlertService` (atau
  service serupa) dengan SCOPE BARU (proyek milik anggota yang login, bukan
  semua proyek seperti versi admin) — kemungkinan perlu method baru atau
  parameter tambahan di `AlertService`/query serupa `Eksekusi\Projects\Index`.
  Ini satu-satunya bagian dari redesign dashboard yang REALISTIS menyentuh
  logic/query baru, bukan cuma reshuffle tampilan dari data yang sudah ada.
- Dashboard Admin & Eksplorasi: redesign murni tampilan, data yang sudah
  di-render() sudah lengkap, tinggal disusun ulang visualnya.

**20. Migrasi: TIDAK PERLU sama sekali** untuk ketiganya — semua data yang
relevan sudah ada di skema yang sudah ada. **Soal menyatukan 2 dashboard
Eksekusi (Blade vs Livewire):** rekomendasi TIDAK perlu dipaksa disatukan
secara arsitektur — tapi `eksekusi/dashboard.blade.php` PERLU dikonversi
jadi Livewire component kalau mau menampilkan data dinamis apa pun
(ringkasan proyek, alert) — Blade biasa tidak punya cara reaktif menampilkan
data tanpa reload penuh, dan ini juga akan membuatnya KONSISTEN secara
arsitektur dengan dua dashboard lain (sama-sama Livewire dengan
`#[Layout]`/`#[Title]`), bukan kombinasi campuran yang sekarang ada murni
karena kebetulan sejarah (tidak pernah butuh Livewire, bukan keputusan
sadar untuk beda). Ini keputusan yang perlu dikonfirmasi eksplisit sebelum
redesign dikerjakan, karena mengubah `eksekusi/dashboard.blade.php` jadi
Livewire component adalah perubahan STRUKTURAL (bukan cuma tampilan),
sedikit di luar pola "redesign visual saja" yang dipakai batch-batch 2.2.3
sebelumnya — worth dikonfirmasi user dulu, bukan diasumsikan.
