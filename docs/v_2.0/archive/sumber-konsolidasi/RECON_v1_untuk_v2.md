# Reconnaissance Kode v1.0 untuk Persiapan v2.0

Laporan ini murni observasi kondisi kode NYATA saat ini (bukan yang seharusnya menurut
dokumen). Dibuat sebagai persiapan Fase 2.2 (implementasi v2.0), sebelum shell/UI baru
dibangun.

## A. Struktur Shell dan Layout

**A1. Layout Blade bersama:**
Hanya ADA 2 file layout, dan **ketiganya portal (Admin, Eksplorasi, Eksekusi) memakai
SATU layout yang sama**:
- `resources/views/components/layouts/app.blade.php` — shell utama untuk semua halaman
  yang sudah login, dipakai oleh ketiga role.
- `resources/views/components/layouts/guest.blade.php` — dipakai hanya untuk halaman
  login.
- Tidak ada folder `resources/views/layouts/*.blade.php` sama sekali.
- Tidak ada layout terpisah per portal.

**Mekanisme deklarasi layout:** hampir semua komponen Livewire halaman penuh memakai
atribut PHP `#[Layout('components.layouts.app')]`, contoh:
- `app/Livewire/Admin/Dashboard.php:27`
- `app/Livewire/Eksplorasi/Webi/Chat.php:17`
- `app/Livewire/Eksekusi/Projects/Index.php:11`
- `app/Livewire/Eksplorasi/Dashboard.php:11`
- `app/Livewire/Admin/Users/Index.php:10`

26 dari 28 class Livewire memakai atribut ini. 2 sisanya adalah komponen anak (bukan
halaman ter-route), sengaja tanpa layout:
- `app/Livewire/Notifications/Bell.php` — di-mount lewat `<livewire:notifications.bell />`
  di dalam layout itu sendiri.
- `app/Livewire/Eksplorasi/UnitEvaluation.php` — di-mount lewat
  `<livewire:eksplorasi.unit-evaluation :unit="$unit" />` di
  `resources/views/livewire/eksplorasi/unit-show.blade.php:22`.

Satu view Blade biasa (bukan Livewire) juga ada:
`resources/views/eksekusi/dashboard.blade.php` memakai `<x-layouts.app title="...">`
langsung — tampak seperti kode legacy/tidak terpakai (kemungkinan nama file tumpang
tindih dengan dashboard admin terpadu dari 2.6). Tidak ada `@extends` di mana pun
(grep nol hasil).

**A2. Navbar/sidebar reusable component:**
**Tidak ada sidebar sama sekali** di aplikasi ini — hanya satu `<header>` navigasi
horizontal yang ditulis langsung inline di dalam `app.blade.php` (baris 12-49). Markup
nav TIDAK diekstrak jadi komponen Blade/Livewire terpisah — link nav dan blok
`@if` role ditulis langsung di file layout. Satu-satunya bagian area navbar yang SUDAH
jadi komponen reusable adalah notification bell
(`<livewire:notifications.bell />` di `app.blade.php:38`).

**A3. Diferensiasi menu per role — kode aktual** (`app.blade.php:20-36`):
```blade
@if (auth()->user()->role === 'admin')
    <a href="{{ url('/admin/dashboard') }}">Dashboard</a>
    <a href="{{ url('/admin/users') }}">Manajemen Akun</a>
    <a href="{{ url('/eksekusi/ideas') }}">Project Ideas</a>
    <a href="{{ url('/eksekusi/projects') }}">Proyek</a>
    <a href="{{ url('/admin/webi') }}">Log WEBI</a>
@elseif (auth()->user()->role === 'exploration_member')
    <a href="{{ url('/eksplorasi/dashboard') }}">Dashboard</a>
    <a href="{{ url('/eksplorasi/kurikulum') }}">Peta Kurikulum</a>
    <a href="{{ url('/eksplorasi/resources') }}">Referensi</a>
    <a href="{{ url('/eksplorasi/forum') }}">Forum</a>
    <a href="{{ url('/eksplorasi/webi') }}">WEBI</a>
@elseif (auth()->user()->role === 'execution_member')
    <a href="{{ url('/eksekusi/dashboard') }}">Dashboard</a>
    <a href="{{ url('/eksekusi/ideas') }}">Project Ideas</a>
    <a href="{{ url('/eksekusi/projects') }}">Proyek</a>
@endif
```
Satu rantai `@if/@elseif` inline berdasarkan `auth()->user()->role` (string:
`admin`/`exploration_member`/`execution_member`) — bukan partial terpisah, bukan array
menu konfigurasi.

## B. Navigasi dan Interaktivitas

**B4. wire:navigate:** Nol pemakaian di seluruh `resources/views` (grep tidak
menemukan apa pun). Seluruh navigasi pakai `<a href="{{ url(...) }}">` biasa — full
page reload, termasuk link nav di layout itu sendiri.

**B5. Interaktivitas sidebar:** Tidak ada sidebar sama sekali (lihat A2), jadi tidak
ada yang bisa di-collapse. Alpine (`x-data`/`x-show`/`x-transition`/`x-cloak`) cuma
dipakai di 4 file, TIDAK ADA satu pun di layout/nav:
- `resources/views/livewire/notifications/bell.blade.php` — `x-data="{ open: false }"`,
  `@click.outside`, `x-show`, `x-cloak`, `x-transition` (toggle dropdown).
- `resources/views/livewire/eksplorasi/webi/chat.blade.php` — `x-data="webiVoice()"`
  (voice mode UI).
- `resources/views/livewire/eksekusi/ideas/index.blade.php`
- `resources/views/livewire/eksplorasi/peta-kurikulum.blade.php`

Nav di layout (`app.blade.php:12-49`) sendiri 100% markup statis, nol Alpine — belum
ada collapsible/hover/ikon interaktif.

## C. Design Token

**C6. Token yang SUDAH diterapkan di kode** (`resources/css/app.css:1-16`, blok
`@theme`):
```css
@theme {
    --font-sans: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif, ...;
    --font-display: 'Sora', ui-sans-serif, system-ui, sans-serif;
    --font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;

    --color-ink: #1C1515;
    --color-muted: #979393;
    --color-accent: #05D9E7;
    --color-accent-soft: #D1F8FF;
}
```
Total: 3 variabel font, 4 variabel warna. **Tidak ada** `tailwind.config.js`/`.ts`/`.cjs`
sama sekali (Tailwind v4 CSS-first murni lewat `@theme`, dikonfirmasi lewat
`@tailwindcss/vite: ^4.0.0` di `package.json`).

**Perbandingan dokumen vs kode** (`docs/design-tokens.md`):
- Warna: tabel dokumen (section 2) mendaftar persis 5 nilai yang sama dengan kode
  (`#1C1515`, `#979393`, `#05D9E7`, `#D1F8FF`, plus putih bawaan Tailwind). **Cocok
  penuh** — tidak ada yang di dokumen tapi belum di kode, atau sebaliknya.
- Font: dokumen (section 3) menyebut 3 font yang sama, stack sudah cocok dengan
  `--font-*` di kode. TAPI mekanisme load-nya beda: dokumen menyarankan snippet
  `<link>` Google Fonts literal, implementasi ASLI pakai Bunny Fonts (privacy-friendly)
  lewat plugin `laravel-vite-plugin`, bukan tag `<link>` manual. Jadi dokumen agak usang
  soal *cara* load, walau pilihan fontnya sendiri sudah cocok.
- Dokumen juga mendefinisikan panduan non-token (border radius, shadow, spacing, elemen
  "circuit path" Peta Kurikulum) yang memang bukan CSS custom property, jadi wajar tidak
  ada di `app.css`.

**C7. Loading font — mekanisme aktual:**
`vite.config.js` (baris 1-24) mendaftarkan Bunny Fonts lewat
`laravel-vite-plugin/fonts`:
```js
fonts: [
    bunny('Sora', { weights: [600, 700] }),
    bunny('Plus Jakarta Sans', { weights: [400, 500] }),
    bunny('JetBrains Mono', { weights: [400, 500] }),
],
```
Dipanggil via directive Blade `@fonts` di `<head>`:
- `resources/views/components/layouts/app.blade.php:7`
- `resources/views/components/layouts/guest.blade.php:7`
- `resources/views/welcome.blade.php`

Jadi font SUDAH ter-load dan berfungsi, cuma lewat mekanisme beda dari yang disebut di
dokumen (self-hosted Bunny Fonts, bukan Google Fonts / `@font-face` manual / paket npm
font). Tidak ada `alpinejs` di `package.json` devDependencies — Alpine dibawa bawaan
oleh Livewire v4 (`livewire/livewire: ^4.3`) lewat `@livewireScripts`
(`app.blade.php:54`, `guest.blade.php:23`). `resources/js/app.js` kosong (cuma
komentar).

## D. Komponen Berfungsi yang Tidak Boleh Rusak

**D8. WEBI entry point:**
- Class: `app/Livewire/Eksplorasi/Webi/Chat.php`
  (`#[Layout('components.layouts.app')]`, `#[Title('Chat WEBI')]`).
- View: `resources/views/livewire/eksplorasi/webi/chat.blade.php`.
- Route: `routes/web.php:61` → `/eksplorasi/webi`, middleware
  `['auth', 'role:exploration_member']`.
- **Cara pasang:** TIDAK di-embed global sebagai widget/tombol mengambang di layout
  bersama. Hanya bisa diakses sebagai halaman penuh sendiri, di-link dari nav HANYA
  untuk role `exploration_member` (`app.blade.php:31`). Admin dan execution_member
  TIDAK punya entry point ke chat WEBI di nav sama sekali (admin cuma punya "Log WEBI"
  yang mengarah ke `App\Livewire\Admin\Webi\Index` — log monitoring read-only, bukan
  chat itu sendiri).

**D9. Sistem notifikasi terpadu:**
- Component: `app/Livewire/Notifications/Bell.php`.
- View: `resources/views/livewire/notifications/bell.blade.php`.
- **Dipasang global** di layout bersama: `app.blade.php:38` →
  `<livewire:notifications.bell />`, di dalam blok `@auth` — jadi tampil untuk SEMUA
  role yang sudah login.
- Halaman daftar penuh: `app/Livewire/Notifications/Index.php` /
  `resources/views/livewire/notifications/index.blade.php`, route `/notifications`
  (`routes/web.php:44-46`).
- Pakai `wire:poll.30s="refresh"` + listener `#[On('notifications-updated')]` — bukan
  websocket sungguhan.

**D10. Komponen global shell lain:**
- **Breadcrumb:** Tidak ada (grep "breadcrumb" nol hasil).
- **Dropdown profil:** Tidak ada — layout cuma tampilkan teks polos
  `<span class="text-muted">{{ auth()->user()->name }}</span>` (`app.blade.php:18`)
  plus form logout biasa. Tidak ada dropdown, tidak ada avatar/menu.
- **Toast/flash message:** Tidak ada komponen bersama/reusable — flash message
  ditulis manual per halaman dengan blok `@if (session('status'))` yang duplikat di
  5 file berbeda:
  - `resources/views/livewire/admin/users/edit.blade.php`
  - `resources/views/livewire/eksekusi/projects/board.blade.php`
  - `resources/views/livewire/eksekusi/projects/show.blade.php`
  - `resources/views/livewire/eksekusi/tasks/show.blade.php`
  - `resources/views/livewire/eksplorasi/peta-kurikulum.blade.php`
  Tidak ada komponen `Toast`/partial flash-message terpusat — ini markup ad hoc yang
  diduplikasi, bukan sistem yang menempel di shell.

## E. Peta Teknis Ringkas

**E11. Versi:**
- `composer.json`: PHP `^8.3`, `laravel/framework: ^13.8`, `livewire/livewire: ^4.3`,
  `laravel/tinker: ^3.0`. Dev: `phpunit/phpunit: ^12.5.12`, `laravel/pint: ^1.27`.
- `package.json` devDependencies: `@tailwindcss/vite: ^4.0.0`, `tailwindcss: ^4.0.0`,
  `vite: ^8.0.0`, `laravel-vite-plugin: ^3.1`. **Tidak ada entry `alpinejs`** — dibawa
  bawaan oleh Livewire 4 (lihat C7). Tidak ada `tailwind.config.js` (Tailwind v4
  CSS-first via `@theme`).

**E12. Struktur folder (2 level):**

`resources/views/`:
```
components/layouts/        → app.blade.php, guest.blade.php
eksekusi/                  → dashboard.blade.php (Blade biasa, non-Livewire, pakai <x-layouts.app>)
eksplorasi/                → (kosong)
livewire/admin/            → dashboard.blade.php, users/, webi/
livewire/auth/             → login.blade.php
livewire/eksekusi/         → ideas/, projects/, tasks/
livewire/eksplorasi/       → checkpoint-show.blade.php, dashboard.blade.php, forum/,
                              peta-kurikulum.blade.php, resources/,
                              unit-evaluation.blade.php, unit-show.blade.php, webi/
livewire/notifications/    → bell.blade.php, index.blade.php
vendor/pagination/         → tailwind.blade.php (override kustom, lihat CLAUDE.md 2.6b)
welcome.blade.php
```

`app/Livewire/` (tidak ada `app/Http/Livewire/` legacy):
```
Admin/          → Dashboard.php, Users/, Webi/
Auth/           → Login.php
Eksekusi/       → Ideas/, Projects/, Tasks/
Eksplorasi/     → CheckpointShow.php, Dashboard.php, Forum/, PetaKurikulum.php,
                   Resources/, UnitEvaluation.php, UnitShow.php, Webi/
Notifications/  → Bell.php, Index.php
```
Konvensi penamaan: folder PascalCase sesuai nama portal (`Admin`, `Eksplorasi`,
`Eksekusi`), sub-namespace per fitur/entitas (`Users`, `Webi`, `Ideas`, `Projects`,
`Tasks`, `Forum`, `Resources`). Nama class bergaya CRUD (`Index`, `Create`, `Edit`,
`Show`, `Approve`, `Board`). Path view Blade mengikuti namespace PHP 1:1 tapi
kebab-case (mis. `App\Livewire\Eksplorasi\UnitShow` → `livewire/eksplorasi/unit-show.blade.php`).

**E13. Test yang ada sekarang (41 file, tidak dijalankan):**
```
tests/TestCase.php
tests/Unit/ExampleTest.php                                    (1)
tests/Feature/ExampleTest.php                                 (1, root)
tests/Feature/Admin/                                          (3: Dashboard, Users/RouteBindingSmoke, Users/UserManagement)
tests/Feature/Auth/                                            (1: Login)
tests/Feature/Console/                                         (2: CreateAdmin, DatabaseSeeder)
tests/Feature/Execution/                                       (6: Alert&Dashboard, AttachmentDownload, ProjectIdea, ProjectManagement, TaskCollaboration, TaskManagement)
tests/Feature/Exploration/                                     (7: CurriculumNavigation, Dashboard, MatchingAndOrderingEvaluation, NotificationTriggers, ProgressService, ResourcesAndForum, UnitEvaluation)
tests/Feature/Integration/                                     (2: CrossModuleEndToEnd, FullSystemSmoke)
tests/Feature/Notifications/                                   (3: Bell, NotificationScopingAndSafety, NotificationsIndex)
tests/Feature/Rbac/                                             (3: CrossModuleDataConsistency, DashboardAccess, RouteAccessMatrix)
tests/Feature/Security/                                         (1: ErrorHandling)
tests/Feature/Webi/                                             (11: AdminMonitoring, Chat, GeminiClient, GuardrailService, Guardrail, MarkdownRendering, MessageRenderer, Personalization, Proactive, RecommendationCard, VoiceMode)
```
Total: 41 file test di 10 subfolder Feature + Unit + root.

## Catatan tambahan (observasi, bukan bagian A-E tapi relevan)

- `resources/views/eksekusi/dashboard.blade.php` tampak seperti file Blade legacy/tidak
  terpakai (bukan Livewire, pakai `<x-layouts.app>` langsung) — namanya tumpang tindih
  dengan dashboard admin terpadu dari task 2.6. Perlu dicek manual apakah masih
  di-route ke mana pun sebelum shell v2.0 dibangun, supaya tidak salah asumsi ini
  dead code.
