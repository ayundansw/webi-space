# Recon — Peta Kurikulum v1.0

Laporan ini murni observasi kode NYATA saat ini. Dibuat sebagai bekal
redesign Peta Kurikulum ke gaya roadmap.sh (2.2.3). **Tidak ada kode yang
diubah.**

## A. Struktur komponen sekarang

**1. Path lengkap:**
- Class Livewire: `app/Livewire/Eksplorasi/PetaKurikulum.php`
- View: `resources/views/livewire/eksplorasi/peta-kurikulum.blade.php`
- Route: `/eksplorasi/kurikulum` (`routes/web.php`, di dalam group
  `middleware(['auth', 'role:exploration_member'])->prefix('eksplorasi')`,
  nama route `eksplorasi.kurikulum`).

**2. Query/struktur data** (`PetaKurikulum::render()`, baris 17-44):
```php
$unitProgressByUnitId = UserUnitProgress::where('user_id', $user->id)->get()->keyBy('unit_id');

$modules = Module::orderBy('order_number')
    ->with(['units' => fn ($query) => $query->orderBy('order_number')])
    ->get()
    ->map(fn (Module $module) => [
        'module' => $module,
        'status' => $progress->moduleStatus($module, $user),
        'percentage' => $progress->moduleProgressPercentage($module, $user),
        'units' => $module->units->map(fn ($unit) => [
            'unit' => $unit,
            'locked' => $progress->unitLocked($unit, $user),
            'completed' => $unitProgressByUnitId->get($unit->id)?->status === 'completed',
        ]),
    ]);
```
Semua modul diambil sekaligus (eager-load `units`), semua `UserUnitProgress`
milik user di-index sekali di awal (`keyBy('unit_id')`) supaya tidak N+1
per unit. Struktur akhir yang dikirim ke view: array modul, tiap modul
punya `status` (completed/active/locked), `percentage`, dan array `units`
(tiap unit punya `locked`/`completed` boolean). **Struktur data ini sudah
persis bentuk yang dibutuhkan diagram roadmap.sh** — tidak perlu
di-reshape, cuma perlu dirender beda.

**3. Alpine dipakai untuk:** murni accordion expand/collapse PER MODUL.
Tiap modul punya `x-data="{ open: ... }"` sendiri (default `true` kalau
modul itu `active`, `false` kalau tidak), tombol node dan judul modul
sama-sama `@click="open = !open"`, isi modul (daftar unit + checkpoint)
di-`x-show="open" x-transition`. Tidak ada Alpine lain di luar ini (tidak
ada drag, tidak ada zoom/pan, tidak ada state lain).

## B. Mekanisme lock/unlock (yang TIDAK boleh berubah)

**4. Logic ada di `App\Services\Exploration\ProgressService`** (bukan di
component/view) — tiga method:
- `moduleStatus(Module $module, User $user): string` — return
  `'locked'`/`'completed'`/`'active'`.
- `moduleCompleted(Module $module, User $user): bool`.
- `unitLocked(Unit $unit, User $user): bool`.

**5. Aturan unlock, persis dari kode:**
- **Modul**: `moduleStatus()` — modul N `locked` kalau modul (N-1) belum
  `moduleCompleted()`. Modul `completed` kalau `moduleCompleted()` true.
  Selain itu `active`. `moduleCompleted()` sendiri: SEMUA unit di modul
  itu harus `status='completed'` di `UserUnitProgress`
  (`allUnitsCompleted()`) DAN (modul itu tidak punya checkpoint ATAU
  checkpoint-nya sudah ada baris `CheckpointCompletion`).
- **Unit**: `unitLocked()` — unit terkunci kalau (a) modul induknya
  `locked` (rantai ke atas), ATAU (b) unit itu punya
  `prerequisite_unit_id` dan unit prasyarat itu belum `completed`. Modul
  PERTAMA tidak punya modul sebelumnya jadi otomatis tidak locked oleh
  aturan (a); unit PERTAMA tiap modul biasanya tidak punya
  `prerequisite_unit_id` jadi juga tidak kena aturan (b) — keduanya
  murni bergantung urutan modul dan `prerequisite_unit_id` per-unit yang
  sudah di-seed `CurriculumSeeder`, bukan dihitung "urutan array" secara
  implisit.
- Tidak ada aturan berbasis LEVEL untuk lock/unlock unit/modul —
  level (`current_level`/`total_points`) sistem TERPISAH (dipakai
  leaderboard/WEBI personalization), tidak pernah dicek di
  `unitLocked()`/`moduleStatus()`.

**6. Selesai vs sedang dikerjakan vs terkunci — dibedakan begini:**
- Data: `UserUnitProgress.status` (`not_started`/`in_progress`/`completed`)
  per unit menentukan selesai-atau-belum; `unitLocked()` (dihitung, bukan
  kolom tersimpan) menentukan terkunci-atau-tidak.
- Visual modul: 3 state (`completed`/`active`/`locked`) tercermin di warna
  node (lihat bagian C). "Sedang dikerjakan" secara SPESIFIK per-unit
  (bukan modul) tidak divisualisasikan beda dari "belum dikerjakan tapi
  terbuka" — keduanya sama-sama tampil sebagai tombol "Buka" polos di
  daftar unit dalam modul. Hanya 2 state visual per-unit yang benar-benar
  dibedakan: terkunci (teks abu "🔒 Terkunci") vs terbuka-tapi-belum-selesai
  ("Buka", tombol solid ink) vs sudah-selesai ("Selesai · Lihat lagi",
  teks underline). Jadi sebenarnya 3 state visual per-unit, tapi tidak ada
  penanda visual "ini yang sedang aktif dikerjakan sekarang" secara
  eksplisit di level unit (beda dari modul yang punya ring accent untuk
  `active`).

## C. Elemen visual signature

**7. Circuit path SUDAH diterapkan, bukan cuma di dokumen** — dan cocok
persis dengan spek `docs/design-tokens.md` bagian 4, untuk level MODUL:
| Aturan design-tokens.md | Kode (`peta-kurikulum.blade.php:22-27`) |
|---|---|
| Selesai: node solid `#1C1515`, centang `#05D9E7` | `bg-ink text-accent` + `&#10003;` — cocok |
| Aktif: node solid `#05D9E7`, ring `#D1F8FF`, nomor `#1C1515` | `bg-accent text-ink ring-4 ring-accent-soft` — cocok |
| Terkunci: node kosong putih, border `#979393`, gembok `#979393` | `bg-white text-muted border-2 border-muted` + `&#128274;` — cocok |
| Garis: `#05D9E7` sampai progres, `#979393`/putus-putus sisanya | `bg-accent` (completed/active) vs `bg-muted/40` (locked) — cocok (pakai solid muted, bukan dashed, tapi dokumen sendiri mengizinkan "atau putus-putus" sebagai alternatif) |

**PENTING — signature element ini HANYA di level MODUL, bukan unit.** Unit
tidak dirender sebagai node dalam jalur bergaris sama sekali — unit cuma
daftar baris polos (card kecil) yang muncul saat modul di-expand
(accordion), tanpa garis penghubung antar unit.

**8. Struktur tampilan sekarang: vertikal, satu kolom, linear, accordion.**
Bukan grid, bukan horizontal. Satu jalur vertikal lurus dari modul 1 ke
modul terakhir (garis penghubung `w-0.5` vertikal antar node modul).
**Konteks penting dari task 2.7 (2026-07-04, tercatat di CLAUDE.md):**
keputusan TETAP VERTIKAL ini sebelumnya SUDAH PERNAH dikonfirmasi user
sebagai "diterima, bukan bug" — dicatat eksplisit sebagai penyesuaian
mobile-friendly yang disengaja, BUKAN redesign horizontal. Task 2.2.3
sekarang secara eksplisit meminta "diagram hierarki jalur" gaya
roadmap.sh — kalau ini berarti perlu jalur bercabang/horizontal
sungguhan, ini akan membalik keputusan 2.7 tadi. Ini bukan blocker, tapi
perlu disadari sebagai keputusan yang sedang ditinjau ulang, bukan celah
yang terlewat.

**Kurikulum sendiri linear, tidak bercabang** — dicek `unitLocked()`/
`prerequisite_unit_id`: tiap unit paling banyak punya SATU prasyarat,
tidak ada struktur "unit A membuka DUA unit sekaligus" atau "unit bisa
dicapai dari dua jalur berbeda" di data manapun yang saya temukan. Jadi
"hierarki jalur roadmap.sh" di sini kemungkinan besar akan tetap secara
LOGIS linear (satu urutan pasti), redesign-nya murni soal tampilan
(bagaimana urutan linear itu divisualisasikan lebih menarik/mirip
roadmap.sh), bukan soal membangun percabangan baru di data.

## D. Interaktivitas & navigasi

**9. Masuk ke halaman unit:** link `<a href="{{ url('/eksplorasi/unit/'.$unit->id) }}">`
langsung (bukan `wire:navigate` — belum dipasang di sini, beda dari shell
2.2.1 yang sudah pakai `wire:navigate` untuk link sidebar/navbar). Unit
yang terkunci TIDAK dirender sebagai link sama sekali (cuma `<span>`
teks "Terkunci"), jadi tidak ada cara mengklik unit terkunci dari halaman
ini (proteksi ganda — backend `UnitShow` juga menolak lewat halaman
"belum bisa dibuka" kalau diakses langsung via URL, dites eksplisit di
`CurriculumNavigationTest`). Checkpoint sama polanya:
`/eksplorasi/checkpoint/{id}`.

**10. Progress indicator yang sudah ada:**
- Bar persentase per-modul (`$entry['percentage']`, dari
  `moduleProgressPercentage()`) — cuma tampil kalau modul TIDAK locked.
- Tidak ada progress bar GLOBAL (seluruh kurikulum) di halaman ini
  sendiri — itu ada di halaman lain (`Eksplorasi\Dashboard`,
  `overallProgressPercentage()`), bukan di Peta Kurikulum.
- Tidak ada "penanda posisi kamu sekarang di sini" secara eksplisit
  selain modul `active` yang otomatis ter-expand (`open: true` default).

## E. Test terkait

Test yang benar-benar menyentuh Peta Kurikulum/navigasi/unlock:
- `tests/Feature/Exploration/CurriculumNavigationTest.php` — paling
  relevan. Isinya: render halaman kurikulum menampilkan modul sample
  (`test_peta_kurikulum_shows_both_sample_modules`), unit pertama bisa
  diakses, unit kedua terkunci sampai unit pertama selesai, unit modul
  kedua terkunci sampai checkpoint modul pertama selesai, checkpoint
  ditolak sampai semua unit modul selesai, admin tidak bisa akses
  `/eksplorasi/kurikulum` (RBAC).
- `tests/Feature/Rbac/RouteAccessMatrixTest.php` — `/eksplorasi/kurikulum`
  masuk daftar `explorationOnlyRoutes` dan `protectedRoutes` (dicek admin/
  execution_member/guest ditolak).
- `tests/Feature/Console/DatabaseSeederTest.php`,
  `tests/Feature/Notifications/BellTest.php`,
  `tests/Feature/Webi/RecommendationCardTest.php`,
  `tests/Feature/Webi/ChatTest.php` — muncul di grep tapi cuma referensi
  incidental (link ke `/eksplorasi/kurikulum` dari WEBI/notifikasi, atau
  cek jumlah modul dari seeder), bukan yang menguji logic Peta Kurikulum
  itu sendiri.
- `tests/Feature/Exploration/ProgressServiceTest.php` (ditemukan sebelumnya
  di sesi ini, bukan lewat grep di atas) kemungkinan menguji
  `moduleStatus()`/`unitLocked()` langsung di level service — **ini test
  yang PALING WAJIB tetap hijau**, karena redesign 2.2.3 seharusnya sama
  sekali tidak menyentuh `ProgressService`.

## F. Penilaian

**Yang WAJIB dipertahankan (jangan disentuh sama sekali):**
- Seluruh isi `App\Services\Exploration\ProgressService` — `moduleStatus()`,
  `moduleCompleted()`, `unitLocked()`, `allUnitsCompleted()`,
  `moduleProgressPercentage()`. Redesign 2.2.3 murni soal `PetaKurikulum.php`
  (component) dan `peta-kurikulum.blade.php` (view) — TIDAK ADA alasan
  logic untuk menyentuh service ini.
- Struktur data yang di-return `render()` — sudah dalam bentuk yang tepat
  (array modul dengan status/percentage/units, tiap unit dengan
  locked/completed) untuk divisualisasikan sebagai diagram apa pun,
  termasuk gaya roadmap.sh. Redesign kemungkinan besar cuma perlu
  MENAMBAH data kecil (mis. koordinat X/Y kalau mau layout non-linear,
  atau tidak perlu sama sekali kalau tetap representasi linear) — bukan
  mengganti query yang sudah ada.
- Query eager-loading (`with('units')`, `keyBy('unit_id')` sekali di
  awal) — sudah efisien (tidak N+1), pertahankan pola ini di redesign.

**Yang murni lapisan tampilan (bebas diganti tanpa risiko logic):**
- Seluruh isi `peta-kurikulum.blade.php` — markup, warna per-state (walau
  warnanya SUDAH benar sesuai token, jadi kemungkinan tetap dipakai ulang,
  bukan diganti), struktur accordion vs non-accordion, orientasi vertikal
  vs bentuk lain, cara unit divisualisasikan (saat ini polos, roadmap.sh
  minta unit juga jadi node di jalur).
- Penambahan `wire:navigate` ke link unit/checkpoint (belum ada sekarang,
  tapi ini konsisten dengan shell 2.2.1 yang sudah lebih dulu pakai
  `wire:navigate` di tempat lain — aman ditambahkan).

**Risiko redesign menyentuh logic yang tidak boleh berubah: RENDAH.**
Karena:
1. Component (`PetaKurikulum.php`) sudah SANGAT tipis — cuma satu method
   `render()` yang MEMANGGIL (bukan mengimplementasikan ulang) tiga method
   `ProgressService`. Redesign yang mengubah TAMPILAN saja tidak
   menyentuh baris logic ini sama sekali, cuma menyusun ulang data yang
   sudah dihasilkan.
2. Tidak ada logic lock/unlock yang "bocor" ke Blade view — semua
   keputusan locked/completed/active sudah final SAAT sampai ke view
   (`$unitEntry['locked']`, `$entry['status']`, dst adalah boolean/string
   siap pakai, bukan kondisi yang dihitung ulang di Blade).
3. Satu-satunya risiko nyata: kalau redesign roadmap.sh butuh DATA
   TAMBAHAN yang belum ada di struktur `render()` sekarang (mis. posisi
   koordinat kalau mau layout benar-benar non-linear/bercabang secara
   visual meski datanya tetap logis linear) — itu PENAMBAHAN aman
   (menambah key baru ke array yang sudah ada), bukan PERUBAHAN pada
   logic lock/unlock yang sudah benar.
