# Recon — Shell (Navbar/Sidebar) Sebelum Fase 2 Langkah 5

Murni observasi kode saat ini. **Tidak ada kode yang diubah.**

**Temuan pembuka penting:** `docs/v_2.0/RANCANGAN_FINAL_WEBI-SPACE_v2.md` sekarang ADA di repo (sebelumnya, di sesi-sesi Langkah 1-4, file ini tidak ditemukan dan selalu ditandai sebagai gap). Dokumen ini adalah *living document*, MODUL 1 (Fondasi Lintas-Portal) berisi spesifikasi navbar+popup menu yang eksplisit dan detail (§1.5). Recon ini memakainya sebagai acuan silang di poin 7.

---

## 1. Struktur file shell lengkap

### `resources/views/components/shell/navbar.blade.php` (35 baris, isi penuh)

```blade
<header class="sticky top-0 z-10 border-b border-muted/25 bg-white">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-y-3 px-6 py-4">
        <div class="flex items-center gap-3">
            @auth
                <button
                    type="button"
                    @click="sidebarCollapsed = !sidebarCollapsed; mobileSidebarOpen = !mobileSidebarOpen"
                    :aria-expanded="(!sidebarCollapsed || mobileSidebarOpen).toString()"
                    aria-label="Buka/tutup sidebar"
                    class="rounded-lg border border-muted/40 px-2 py-1.5 text-ink hover:border-ink"
                >
                    <span aria-hidden="true">&#9776;</span>
                </button>
            @endauth

            <a href="{{ url('/dashboard') }}" wire:navigate class="font-display text-lg font-bold text-ink">WEBI-SPACE</a>
        </div>

        @auth
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                <a href="{{ url('/profile') }}" wire:navigate class="text-muted hover:text-accent">{{ auth()->user()->name }}</a>

                <livewire:notifications.bell />

                <form method="POST" action="{{ url('/logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-muted/40 px-3 py-1.5 text-ink hover:border-ink">
                        Keluar
                    </button>
                </form>
            </div>
        @endauth
    </div>
</header>
```

**Catatan penting:** logo masih teks polos (`<a>...WEBI-SPACE</a>` dengan `font-display`), **BUKAN** `<x-brand.wordmark />` yang sudah dibangun di Langkah 2 — belum pernah dipasang ke sini (sesuai batasan Langkah 2 saat itu, "belum perlu dipasang"). Tidak ada avatar akun (gambar/ikon) di navbar — cuma nama sebagai teks link ke `/profile`.

### `resources/views/components/shell/sidebar.blade.php` (120 baris, isi penuh)

```blade
@php
    $navItems = config('navigation.'.auth()->user()->role, []);

    $icons = [
        'Dashboard' => '<path d="M4 11 12 4l8 7" /><path d="M6 10v9h12v-9" />',
        'Manajemen Akun' => '<circle cx="9" cy="9" r="3" /><path d="M3.5 20a5.5 5.5 0 0 1 11 0" /><circle cx="17" cy="9" r="2.25" /><path d="M15.5 14.5A4.5 4.5 0 0 1 20 19" />',
        'Project Ideas' => '<path d="M12 3a6 6 0 0 0-3 11.2c.6.4 1 .9 1 1.8h4c0-.9.4-1.4 1-1.8A6 6 0 0 0 12 3Z" /><path d="M9.5 18.5h5" /><path d="M10.25 21h3.5" />',
        'Proyek' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" />',
        'Log WEBI' => '<path d="M7 3h7l4 4v14H7Z" /><path d="M14 3v4h4" /><path d="M9.5 12h5" /><path d="M9.5 16h5" />',
        'Antrian Review Praktik' => '<path d="M9 4h6v3H9Z" /><path d="M7 5H6a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-1" /><path d="M9 13.5l2 2 4-4" />',
        'Peta Kurikulum' => '<path d="M9 4 4 6v14l5-2 6 2 5-2V4l-5 2-6-2Z" /><path d="M9 4v14" /><path d="M15 6v14" />',
        'Referensi' => '<path d="M4 5a2 2 0 0 1 2-2h6v18H6a2 2 0 0 1-2-2V5Z" /><path d="M20 5a2 2 0 0 0-2-2h-6v18h6a2 2 0 0 1 2 2V5Z" />',
        'Forum' => '<path d="M4 5h13v9H8l-4 4V5Z" />',
        'WEBI' => '<path d="m12 3 1.7 5.3L19 10l-5.3 1.7L12 17l-1.7-5.3L5 10l5.3-1.7Z" />',
        'Praktik' => '<path d="m9 8-4 4 4 4" /><path d="m15 8 4 4-4 4" />',
        'Kalender Personal' => '<rect x="3.5" y="5" width="17" height="15" rx="2" /><path d="M3.5 10h17" /><path d="M8 3v4" /><path d="M16 3v4" />',
        'Forum General' => '<path d="M3 5h12v8H8l-3 3V5Z" /><path d="M11 9h8v6l-2.5-2H11V9Z" />',
    ];

    $isActiveItem = function (array $item) {
        if (! $item['enabled'] || ! $item['route']) {
            return false;
        }
        if (request()->routeIs($item['route'])) {
            return true;
        }
        if (preg_match('/^(.+)\.(index|show|create|edit|board|approve)$/', $item['route'], $matches)) {
            if (request()->routeIs($matches[1].'.*')) {
                return true;
            }
        }
        foreach ($item['active_when'] ?? [] as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }
        return false;
    };
@endphp

<div
    x-show="mobileSidebarOpen"
    x-cloak
    x-transition:enter="transition-opacity duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="mobileSidebarOpen = false"
    class="absolute inset-0 z-20 bg-ink/40 lg:hidden"
></div>

<aside
    x-cloak
    class="absolute inset-y-0 left-0 z-30 w-64 overflow-hidden border-r border-muted/25 bg-white px-3 py-6 transition-all duration-200 ease-in-out lg:static lg:inset-auto lg:z-auto lg:translate-x-0"
    :class="{
        'translate-x-0': mobileSidebarOpen,
        '-translate-x-full': !mobileSidebarOpen,
        'lg:w-16': sidebarCollapsed,
        'lg:w-56': !sidebarCollapsed,
    }"
>
    <nav class="flex flex-col gap-1 text-sm">
        @foreach ($navItems as $item)
            @php $active = $isActiveItem($item); @endphp

            @if ($item['enabled'])
                <a href="{{ route($item['route']) }}" wire:navigate @click="mobileSidebarOpen = false" title="{{ $item['label'] }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-accent-soft {{ $active ? 'bg-accent-soft text-ink border-l-2 border-accent' : 'text-ink border-l-2 border-transparent' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 shrink-0">
                        {!! $icons[$item['label']] ?? '' !!}
                    </svg>
                    <span class="whitespace-nowrap opacity-100 transition-opacity duration-150" :class="sidebarCollapsed ? 'lg:opacity-0' : 'lg:opacity-100'">{{ $item['label'] }}</span>
                </a>
            @else
                <span title="{{ $item['label'] }} (segera hadir)" aria-disabled="true"
                    class="flex cursor-not-allowed items-center gap-3 rounded-lg border-l-2 border-transparent px-3 py-2 text-muted opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 shrink-0">
                        {!! $icons[$item['label']] ?? '' !!}
                    </svg>
                    <span class="whitespace-nowrap opacity-100 transition-opacity duration-150" :class="sidebarCollapsed ? 'lg:opacity-0' : 'lg:opacity-100'">{{ $item['label'] }}</span>
                </span>
            @endif
        @endforeach
    </nav>
</aside>
```

### Layout wrapper: `resources/views/components/layouts/app.blade.php` (isi penuh)

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'WEBI-SPACE') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body
    class="flex min-h-screen flex-col bg-white text-ink font-sans antialiased"
    x-data="{
        sidebarCollapsed: $persist(false).as('webi_sidebar_collapsed'),
        mobileSidebarOpen: false,
    }"
>
    @persist('shell-navbar')
        <x-shell.navbar />
    @endpersist

    <div class="relative mx-auto flex w-full max-w-7xl flex-1">
        @auth
            <x-shell.sidebar />
        @endauth

        <main class="min-w-0 flex-1 px-6 py-10">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
```

Tidak ada file shell lain (tidak ada `topbar.blade.php`, `footer.blade.php`, dst — cuma dua file ini plus wrapper). `resources/views/components/layouts/guest.blade.php` (dipakai halaman login) TIDAK memuat navbar/sidebar sama sekali — struktur mandiri (centered card), tidak relevan untuk perubahan shell ini.

---

## 2. State Alpine.js

- **`sidebarCollapsed`**: didefinisikan di `x-data` pada `<body>` (`app.blade.php`), dipersist ke localStorage lewat Alpine's `$persist(false).as('webi_sidebar_collapsed')` — **survive reload dan lintas sesi browser** (bukan reset tiap reload). Dibaca oleh: `sidebar.blade.php` (lebar rail desktop `lg:w-16` vs `lg:w-56`, opacity label teks menu), ditulis oleh: tombol toggle di `navbar.blade.php`.
- **`mobileSidebarOpen`**: didefinisikan di `x-data` yang sama, **TIDAK dipersist** (plain Alpine reactive property, `false` tiap load ulang — selalu tertutup saat halaman baru dibuka, sesuai ekspektasi wajar untuk drawer mobile). Dibaca/ditulis oleh: tombol toggle navbar (set keduanya sekaligus, lihat poin 3), backdrop klik-luar di `sidebar.blade.php` (set `false`), tiap `<a>` link di sidebar (`@click="mobileSidebarOpen = false"` — auto-tutup drawer begitu user pilih menu).
- **Alpine sendiri TIDAK di-setup manual** — `resources/js/app.js` isinya cuma komentar kosong (`//`). Alpine + plugin `persist` datang otomatis lewat `@livewireScripts` (Livewire v4 membundel Alpine miliknya sendiri, termasuk plugin `persist`/`intersect`/`collapse` built-in). **Implikasi untuk Langkah 5:** state Alpine baru (mis. `popupMenuOpen`, `accountMenuOpen`) bisa langsung dipakai tanpa setup tambahan apa pun, ikut pola yang sama.
- Tombol toggle navbar sekarang **menyalakan `sidebarCollapsed` DAN `mobileSidebarOpen` sekaligus dalam satu klik** (`sidebarCollapsed = !sidebarCollapsed; mobileSidebarOpen = !mobileSidebarOpen`) — artinya satu tombol dipakai dobel-fungsi (collapse-rail di desktop, buka-drawer di mobile) tanpa dibedakan breakpoint di JS-nya sendiri (perbedaan visual efeknya murni dari CSS `lg:` di `sidebar.blade.php`). Kalau Langkah 5 mengganti jadi popup menu, pola dua-state case ini kemungkinan tidak relevan lagi (popup biasanya cuma butuh satu state open/close, tidak perlu bedakan collapsed/mobile).

---

## 3. Ketergantungan layout ke keberadaan sidebar

- **Tidak ada padding-left/margin statis di konten utama yang mengasumsikan lebar sidebar.** Layout dibangun pakai Flexbox murni (`app.blade.php`: `<div class="relative mx-auto flex w-full max-w-7xl flex-1">` membungkus `<x-shell.sidebar />` (kalau `@auth`) + `<main class="min-w-0 flex-1 ...">`), jadi sidebar mendorong konten secara alami lewat flex-basis-nya sendiri (`w-64`/`lg:w-16`/`lg:w-56` di `<aside>`), BUKAN via offset hardcoded di `<main>`. **Ini kabar baik untuk Langkah 5**: menghapus `<x-shell.sidebar />` dari `app.blade.php` seharusnya membuat `<main>` otomatis melebar mengisi ruang, tanpa perlu cari-cari class `pl-*`/`ml-*` yang perlu dihapus di tempat lain.
- **`position: absolute` bergantung pada `relative` di parent-nya**, bukan pada lebar sidebar itu sendiri: `<aside>` pakai `absolute inset-y-0 left-0` (mobile) beralih ke `lg:static` (desktop) — comment di `app.blade.php` baris 23-29 menjelaskan eksplisit KENAPA parent row itu `relative flex-1` (bukan `fixed` ke viewport): supaya drawer mobile `sidebar.blade.php`'s backdrop (`absolute inset-0`) bisa nempel PERSIS di bawah navbar tanpa hitung tinggi navbar manual di JS. **Kalau sidebar dihapus total, comment blok ini (baris 22-29 app.blade.php) jadi tidak relevan lagi** dan sebaiknya dihapus/ditulis ulang di Langkah 5, bukan cuma kodenya.
- **Digrep di seluruh `resources/views`**: tidak ada halaman/komponen LAIN (di luar navbar/sidebar sendiri) yang mereferensikan lebar sidebar (`w-56`/`w-64`/`w-16`) atau class terkait — 3 hit lain yang muncul (dashboard Eksplorasi, dashboard Admin, ideas index) semuanya kebetulan/tidak terkait (SVG dekoratif, progress bar, lebar textarea). **Tidak ada CSS custom selector** (`.sidebar`, `#sidebar`, dst) di `app.css` — murni utility class Tailwind, jadi tidak ada stylesheet terpisah yang perlu disentuh.

---

## 4. Isi navbar sekarang (urutan persis)

**Kiri → kanan, kondisi `@auth` (user login):**
1. Tombol toggle sidebar (hamburger `&#9776;`, border rounded, SATU tombol untuk desktop-collapse + mobile-drawer sekaligus — lihat poin 2).
2. Logo teks polos `WEBI-SPACE` (`font-display text-lg font-bold`, link ke `/dashboard`) — **bukan** `<x-brand.wordmark />`.
3. *(gap kanan-kiri, `justify-between`)*
4. Nama user (teks, link ke `/profile`, tanpa avatar/foto apa pun).
5. `<livewire:notifications.bell />` — komponen Livewire terpisah (`App\Livewire\Notifications\Bell`), poll `wire:poll` (tidak ada websocket), badge unread + dropdown ringkas.
6. Tombol "Keluar" — **form HTML biasa** (`POST /logout` ke `LogoutController`, invokable controller sederhana: `Auth::logout()` + invalidate session + regenerate token + redirect `/login`), BUKAN Livewire action, BUKAN di dalam submenu apa pun — tombol berdiri sendiri langsung di navbar.

**Kondisi guest (belum login):** navbar cuma tampil logo saja (baris `@auth`/`@endauth` menyembunyikan toggle button dan seluruh blok kanan) — meski demikian, `<header>`-nya sendiri tetap dirender (tidak dibungkus `@auth` di level root), jadi guest tetap melihat navbar kosong berisi logo doang. *(Catatan: halaman login sendiri sebenarnya pakai `guest.blade.php`, bukan `app.blade.php`, jadi kondisi guest-di-app.blade.php ini kemungkinan besar tidak pernah benar-benar terlihat user di alur normal — worth dicek ulang saat Langkah 5 apakah masih relevan dipertahankan.)*

`@persist('shell-navbar')` di `app.blade.php` membungkus navbar — elemen DOM navbar dipertahankan lintas `wire:navigate` (tidak re-render dari nol tiap pindah halaman), beda dari sidebar yang sengaja TIDAK di-`@persist` (ada comment eksplisit menjelaskan kenapa: highlight active-route sidebar butuh re-render server tiap halaman).

---

## 5. `config/navigation.php` — cara dikonsumsi

- **Sumber data**: `config('navigation.'.auth()->user()->role, [])` — array asosiatif per role, tiap item `['label', 'route', 'enabled', 'active_when'?]`. Ini **murni data**, tidak ada logic apa pun di file config-nya sendiri (cuma array + docblock).
- **Ikon**: hardcoded di `sidebar.blade.php` sendiri (bukan di `config/navigation.php`), array asosiatif `$icons` di-key PERSIS oleh string `label` (bukan oleh `route` atau id lain) — SVG path mentah (viewBox 24×24, stroke style konsisten dengan `bell.blade.php`). **Konsekuensi untuk Langkah 5**: format ini (map label→SVG path, di-loop dan disuntik via `{!! $icons[$item['label']] ?? '' !!}`) **langsung reusable tanpa tulis ulang** untuk grid popup — cuma bungkusnya (`<a>` dalam `<nav class="flex flex-col">` list vertikal) yang perlu diganti jadi grid (`<a>` dalam `grid grid-cols-*`), isi ikon+label+href-nya tetap sama persis. Satu risiko: SVG-nya monokrom (`stroke="currentColor"`, warna ikut `text-*` parent) — spek §1.5 RANCANGAN_FINAL minta "ikon berwarna (bukan monokrom)" untuk popup grid, jadi ikon-ikon ini kemungkinan perlu direvisi warnanya (atau dibungkus background berwarna per item), bukan sekadar dipindah polos.
- **Logic active-state** (`$isActiveItem` closure): 3 lapis pengecekan — (a) exact `routeIs($item['route'])`, (b) **suffix-widening otomatis** via regex `^(.+)\.(index|show|create|edit|board|approve)$` → kalau match, cek `routeIs($prefix.'.*')` (jadi `eksekusi.projects.index` otomatis ikut nyala untuk `eksekusi.projects.show`/`.create`/dst tanpa perlu didaftar manual), (c) override manual `active_when` (dipakai untuk detail-page yang route name-nya TIDAK berbagi prefix dengan item induknya, mis. `eksekusi.tasks.show` dinyalakan oleh item "Proyek" lewat `active_when`). **Closure ini murni fungsi PHP biasa (bukan terikat sidebar), 100% reusable apa adanya untuk grid popup** — tinggal panggil `$isActiveItem($item)` yang sama di template baru.

---

## 6. Breakpoint & mobile behavior

- **Breakpoint pivot: `lg` (Tailwind default, 1024px).** Di bawah `lg`: sidebar jadi **drawer overlay** — `<aside>` posisi `absolute inset-y-0 left-0`, transform `-translate-x-full` (tersembunyi) / `translate-x-0` (terbuka) dengan transisi `duration-200 ease-in-out`, plus backdrop gelap (`bg-ink/40`) yang fade in/out (`x-transition`) dan bisa diklik untuk menutup. Di atas `lg`: sidebar jadi **rail statis in-flow** (`lg:static lg:inset-auto lg:translate-x-0`, tidak overlay lagi), lebarnya toggle antara `lg:w-16` (collapsed, cuma ikon) dan `lg:w-56` (expanded, ikon+label, label fade in/out via opacity transition).
- **Tidak ada breakpoint tambahan lain** (tidak ada `md:`/`sm:` khusus untuk sidebar) — cuma satu pivot biner di `lg`.
- Navbar sendiri (`navbar.blade.php`) pakai `flex-wrap` + `gap-y-3` supaya baris kanan (nama+bell+keluar) bisa turun ke baris ke-2 di layar sangat sempit tanpa breakpoint eksplisit — sudah ditandai RESOLVED di `CLAUDE.md` (2.7) sebagai perbaikan responsive.
- **Untuk Langkah 5**: popup menu (bukan drawer sidebar) kemungkinan besar tidak butuh logic `lg:` serumit ini sama sekali — popup biasanya berperilaku SAMA di semua breakpoint (klik ikon grid → overlay muncul, klik lagi/klik luar → tutup), jadi ini area yang justru **menyederhanakan** kode, bukan menambah kerumitan. Yang perlu dipastikan supaya bukan regresi: popup di layar sempit tetap harus scrollable/fit (grid ikon yang di desktop 4-5 kolom mungkin perlu jadi 2-3 kolom di mobile), dan area tap ikon grid trigger + isi popup tetap cukup besar untuk jari (accessibility touch-target), bukan cuma disusun rapat demi muat di layar kecil.

---

## 7. Tempat untuk elemen baru — cross-check ke `RANCANGAN_FINAL_WEBI-SPACE_v2.md` §1.5

Dokumen ini (BARU ditemukan ada di repo, sebelumnya tidak ada di 4 task Langkah 1-4) memberi spesifikasi navbar yang eksplisit:

> **Struktur Navbar (final):**
> - **Kiri:** Logo/wordmark WEBI-SPACE
> - **Kanan (urut):** Badge Status Mode → Menu Notifikasi (bell) → Ikon Navigasi Grid (popup menu) → Avatar/Akun (CTA "Keluar" di dalam sub-menu)
> - **Badge Status Mode:** HANYA tampil untuk user berkapabilitas Mode Ganda (Modul 2 §2.2 — origin Eksekusi dengan akses baca Eksplorasi, atau sebaliknya yang disetujui admin). User mode-tunggal TIDAK melihat badge ini sama sekali.
> - Navbar **sticky/fixed** di semua halaman.

**Delta dari kondisi sekarang ke target ini:**

| Elemen | Sekarang | Target §1.5 |
|---|---|---|
| Logo | Teks polos | `<x-brand.wordmark />` (sudah ada, tinggal pasang) |
| Badge Status Mode | Tidak ada | Slot baru, kondisional (Mode Ganda belum dibangun sama sekali — jadi PRAKTIKNYA tidak akan pernah tampil untuk siapa pun sampai Modul 2 §2.2 diimplementasikan) |
| Notifikasi | `<livewire:notifications.bell />` di kanan, urutan ke-2 dari kiri blok kanan | Tetap dipakai apa adanya, tapi urutannya jadi item ke-1 (paling kiri di blok kanan, sebelum grid navigasi) |
| Navigasi | Sidebar kiri, list vertikal | Ikon grid-dots di navbar kanan → popup grid (data tetap dari `config/navigation.php`) |
| Akun | Nama teks polos + tombol "Keluar" terpisah | Avatar (baru — foto/inisial/ikon, BELUM ada elemen ini sama sekali di kode sekarang) dengan submenu dropdown, "Keluar" pindah ke DALAM submenu itu (bukan tombol berdiri sendiri lagi) |
| Toggle sidebar | Tombol hamburger, dobel-fungsi collapse+drawer | Dihapus, diganti trigger popup (state lebih sederhana, cuma open/close) |

**Kendala ruang yang perlu diantisipasi sekarang (supaya navbar tidak dirombak ulang saat Fase 8):**
- Navbar sekarang pakai `flex flex-wrap items-center justify-between` dengan DUA blok (`kiri`, `kanan`) — pola ini sudah cukup fleksibel untuk nambah 1-2 elemen lagi di blok kanan (Badge Status Mode + Ikon Grid) tanpa restrukturisasi flex, TAPI `flex-wrap` berarti kalau blok kanan terlalu penuh di layar sempit, itemnya turun ke baris baru (bukan overflow-scroll horizontal) — perlu dicek visual apakah 4 item (badge+bell+grid+avatar) di kanan masih pas satu baris di layar HP kecil sebelum wrap, atau perlu strategi lain (sembunyikan label teks, cuma ikon, dst).
- **Badge Status Mode kondisional** (cuma render untuk user dual-mode) berarti secara struktural elemen ini sebaiknya di-render via `@if`/komponen terpisah yang gampang no-op (return null/kosong) untuk mayoritas user — supaya kelak Modul 2 §2.2 dibangun, navbar tidak perlu ubah struktur flex-nya lagi, cuma isi kondisinya yang berubah dari "selalu false" jadi "cek kapabilitas user". Direkomendasikan: siapkan slot `@if($user->hasDualModeCapability())` (atau nama method serupa, belum ada di `User` model sekarang) dari awal Langkah 5 walau isinya sementara selalu `false`/method belum ada — supaya urutan visual (kiri-ke-kanan: badge, bell, grid, avatar) sudah benar dari awal dan tidak geser-geser lagi nanti.
- Avatar akun (baru) punya kandidat sumber data yang SUDAH DIBANGUN di Langkah 4: `<x-avatar.fox tingkat="..." />` (untuk `exploration_member`, tingkat dari `FoxAvatarService::tierFor()`) dan `<x-avatar.eksekusi jenis="..." />` (untuk `execution_member`, dari `users.avatar_url` — lihat catatan reuse kolom di Langkah 4). **Admin tidak punya salah satu dari keduanya** (bukan bagian sistem Fox atau Eksekusi) — perlu diputuskan representasi avatar admin di Langkah 5 (inisial nama, ikon generik, atau lainnya), ini BELUM ada jawabannya di recon ini maupun di `RANCANGAN_FINAL_WEBI-SPACE_v2.md` yang saya baca sejauh ini.

---

## Rekomendasi — bagian paling berisiko/rumit saat transisi

1. **Konversi active-state highlight dari list vertikal ke grid popup** — logic-nya (`$isActiveItem`) reusable, TAPI *tampilannya* di grid perlu pola visual baru (di sidebar, active state ditandai `bg-accent-soft` + `border-l-2` sepanjang baris; di grid, "baris kiri" tidak ada, perlu padanan lain — mis. ring/border di sekeliling ikon, atau dot indicator). Bukan sekadar copy-paste style class.
2. **Ikon monokrom → berwarna**: 13 SVG path yang ada semuanya `stroke="currentColor"` (ikut warna teks). Spek minta ikon berwarna di grid popup (ala Google Apps, tiap ikon biasanya beda warna solid). Ini pekerjaan desain kecil per-ikon (pilih warna dari token yang sudah final), bukan cuma perubahan kode.
3. **Efek "jejak" untuk halaman aktif** (§1.5, wajib) — belum ada padanan konkret di kode sekarang selain `bg-accent-soft` statis; kalau dimaknai sebagai animasi/transisi visual (bukan cuma warna statis), ini elemen BARU yang perlu dirancang dari nol, bukan migrasi dari yang sudah ada.
4. **Breadcrumb wajib di semua halaman** (konsekuensi langsung dari hilangnya sidebar, §1.6) — ini di luar scope shell navbar/sidebar itu sendiri tapi SATU PAKET dengan Langkah 5 secara logis (dokumen bilang eksplisit "bukan lagi nice-to-have"). Belum ada satu pun implementasi breadcrumb di kode manapun sekarang — perlu strategi generate (manual per halaman vs otomatis dari struktur route/`config/navigation.php`) diputuskan SEBELUM implementasi Langkah 5 dimulai, supaya tidak jadi kerjaan terpisah yang terlewat.
5. **`@persist('shell-navbar')`** yang sudah ada perlu dicek ulang kompatibilitasnya dengan popup menu ber-state Alpine baru — `@persist` mempertahankan DOM lintas navigasi, jadi state Alpine di dalamnya (kalau popup open/close disimpan sebagai `x-data` LOKAL di navbar, bukan di `<body>` seperti `sidebarCollapsed`/`mobileSidebarOpen` sekarang) kemungkinan ikut ter-persist juga (popup yang sedang terbuka bisa "nempel" terbuka lintas halaman) — perlu keputusan sadar apakah itu perilaku yang diinginkan atau perlu direset manual tiap navigasi.
