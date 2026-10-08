# Recon — Pola Admin Panel Existing (Sebelum Editor Blok Konten)

Laporan ini murni observasi kode NYATA saat ini (Read/Grep langsung).
**Tidak ada kode yang diubah.**

---

## 1. Struktur `Admin/Users/` (Index, Create, Edit)

**Routing** (`routes/web.php:109-113`) — satu grup middleware per prefix,
pola yang konsisten dipakai di SELURUH app (Forum, Eksekusi, dst):
```php
Route::middleware(['auth', 'role:admin'])->prefix('admin/users')->name('admin.users.')->group(function () {
    Route::get('/', UsersIndex::class)->name('index');
    Route::get('/create', UsersCreate::class)->name('create');
    Route::get('/{user}/edit', UsersEdit::class)->name('edit');
});
```
Route-model binding otomatis dipakai untuk Edit (`{user}` → `User $user` di
`mount()`), bukan manual `User::findOrFail()`.

**Layout** — SEMUA 3 component pakai `#[Layout('components.layouts.app')]`
(atribut PHP, bukan `@extends` di blade) — **tidak ada layout admin
khusus**, sama persis `layouts.app` yang dipakai member/eksekusi. Tiap
component juga pakai `#[Title('...')]` untuk `<title>` tab browser.

**Struktur class** — full-page Livewire component (bukan komponen embed),
pola:
- `Index`: `render()` cuma query + kirim ke view, tidak ada state/action.
- `Create`: public properties per field form (`$name`, `$email`, `$role`)
  + method `save()` yang `$this->validate([...])` inline (array rules di
  dalam method, bukan Form Request/Rule class terpisah), lalu
  `Model::create()`, lalu `session()->flash(...)` untuk pesan sukses, lalu
  `$this->redirect(url, navigate: false)` (full reload, bukan
  `navigate: true` — konsisten dengan pola `newConversation()` WEBI yang
  juga sengaja `navigate: false` untuk state benar-benar bersih).
- `Edit`: `mount(User $user)` isi public properties dari model existing,
  `save()` validasi + `update()` + flash TANPA redirect (tetap di halaman
  yang sama, form ke-refresh dengan data baru). Ada method aksi tambahan
  terpisah (`resetPassword()`) yang juga cuma flash tanpa redirect.

**Guard self-lockout** (relevan kalau Editor Blok Konten nanti juga py
aturan serupa) — `Edit::save()` cek eksplisit `$this->user->id ===
Auth::id()` sebelum izinkan nonaktifkan diri sendiri / ganti role sendiri,
pakai `$this->addError()` manual (bukan exception) lalu `return` awal.

**Form (Blade)** — pola konsisten di Create & Edit:
```blade
<form wire:submit="save" class="space-y-4 rounded-xl border border-muted/25 p-6">
    <div>
        <label for="name" class="mb-1 block text-sm text-ink">Nama</label>
        <input wire:model="name" class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    ...
    <button type="submit" wire:loading.attr="disabled" class="w-full rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60">Simpan</button>
</form>
```
`wire:model` polos (bukan `.live`/`.blur` — validasi cuma jalan saat
submit), `wire:loading.attr="disabled"` di tombol submit untuk cegah
double-submit, `max-w-lg` sebagai lebar form standar (bukan lebar penuh).

**Flash message sukses/gagal** — DUA pola berbeda dipakai:
- Sukses: `session()->flash('status', '...')`, ditampilkan `@if
  (session('status'))` dengan card hijau (`bg-green-50 text-green-700`).
- Info sensitif sekali-lihat (generated password): `session()->flash('generated_password', ...)`
  + `session()->flash('generated_password_user', ...)`, ditampilkan
  card aksen (`border-accent/50 bg-accent-soft/40`) dengan
  `data-testid="generated-password"` eksplisit (dipakai test).
- Gagal validasi: standar Livewire `@error('field')` per-input, TIDAK ada
  flash-level pesan gagal umum (selalu di-scope ke field spesifik).

**List/Index table** — `overflow-x-auto` wrapper (bukan `overflow-hidden`,
pola yang sudah dikoreksi eksplisit di 2.7 untuk semua tabel di app),
`border border-muted/25` + header `bg-accent-soft/20 text-muted`, badge
status pill (`rounded-full` + warna kondisional), aksi per-baris di kolom
kanan sebagai teks-link `underline`, bukan tombol/icon.

---

## 2. `Admin/Dashboard.php` — pola card ringkasan

**Tidak ada 1 komponen `<x-card>`/`<x-stat-card>` generik yang dipakai admin
dashboard** — `stat-card.blade.php` (component Blade) memang ada di
`resources/views/components/`, tapi dashboard admin ini TIDAK memakainya;
tiap seksi (`memberSummaries`, `explorationLeaderboard`, `webiSummary`,
`alertPanel`, `projectSummary`) markup-nya bespoke langsung di
`livewire/admin/dashboard.blade.php`, dibungkus method privat per-seksi di
`Dashboard.php` yang mengembalikan array/Collection siap-render.

**Pola yang REUSABLE untuk "Status Kurikulum" card kecil nanti:**
`webiSummary()` (baris 73-86) adalah pola paling ringan & paling mirip
kebutuhan itu — method privat kecil, query count/agregat sederhana
(`User::count()`, `Message::count()`, `GuardrailFlag::count()`,
`Message::max('created_at')`), dikembalikan sebagai array asosiatif polos,
lalu di-render di blade sebagai satu card kecil dengan angka-angka + link
"lihat detail" ke halaman terpisah (`admin.webi.index`). Kalau "Status
Kurikulum" cuma butuh angka ringkas (mis. jumlah modul, jumlah unit yang
sudah/belum punya `content_blocks`), pola `webiSummary()` PERSIS pas
dicontek — tinggal tambah method privat baru + panggil di `render()`, tidak
perlu bikin komponen card baru.

**Konvensi visual card** (dari blade, tidak dibaca detail di sini tapi
sudah dikonfirmasi sepanjang sesi-sesi sebelumnya): `card-pixel-accent-*`
+ `border-dashed` + `opacity-70` + badge "Segera Hadir" untuk slot yang
belum ada datanya — pola yang sama persis dipakai di placeholder "Antrian
Review Praktik"/"Daftar Praktik" (Area 3.4 `RECON_fase5_praktik.md`).

---

## 3. Pengelompokan Route Admin

Semua route admin (`role:admin` only) mengikuti pola prefix+name-group yang
identik satu sama lain:
```php
Route::middleware(['auth', 'role:admin'])->prefix('admin/users')->name('admin.users.')->group(function () { ... });
Route::middleware(['auth', 'role:admin'])->prefix('admin/webi')->name('admin.webi.')->group(function () { ... });
```
Plus SATU route admin yang berdiri sendiri (tidak dalam grup, karena cuma 1
route): `admin.dashboard` di `/admin/dashboard`.

**Untuk "Kelola Kurikulum" baru, pola yang konsisten adalah:**
```php
Route::middleware(['auth', 'role:admin'])->prefix('admin/curriculum')->name('admin.curriculum.')->group(function () {
    Route::get('/', ...)->name('index');
    // dst — index/create/edit per Module & Unit, editor blok per Unit/ChallengeStep
});
```
(Nama prefix/route-name ini SARAN mengikuti pola, bukan keputusan final —
perlu dikonfirmasi saat implementasi beneran, konsisten dengan cara
`admin.users`/`admin.webi` sudah dinamai.)

---

## 4. Pola "Reorder" (naik/turun urutan) — HANYA SATU yang pernah dibangun

**Grep menyeluruh** (`moveOrderItem|moveUp|moveDown|sort_order|reorder`) di
`app/` menemukan **cuma satu implementasi UI reorder sungguhan**:
`App\Livewire\Eksplorasi\UnitEvaluation::moveOrderItem()`
(`app/Livewire/Eksplorasi/UnitEvaluation.php:97-108`):
```php
public function moveOrderItem(string $questionId, int $index, string $direction): void
{
    $items = $this->quizAnswers[$questionId] ?? [];
    $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

    if ($targetIndex < 0 || $targetIndex >= count($items)) {
        return;
    }

    [$items[$index], $items[$targetIndex]] = [$items[$targetIndex], $items[$index]];
    $this->quizAnswers[$questionId] = $items;
}
```
Ini pola **swap-dengan-tetangga** (bukan drag-and-drop — dokumen 2.7 sudah
catat drag-and-drop sengaja dihindari karena harus testable tanpa browser
sungguhan di lingkungan kerja ini). Dipanggil dari tombol naik/turun di
blade, per-item, dengan disable kondisional di ujung list (index pertama
tidak bisa naik, index terakhir tidak bisa turun).

**PENTING:** ini murni state IN-MEMORY (array `$quizAnswers` di
component, dipakai user biasa menyusun URUTAN JAWABAN, bukan menyimpan
urutan permanen ke kolom `order`/`sort_order` di DB). **Tidak ada satu pun
contoh existing untuk "reorder lalu PERSIST ke kolom DB via klik
naik/turun"** — kasus terdekat, `Milestone.sort_order`, HANYA pernah diisi
otomatis (`max('sort_order') + 1` di `ProjectService::addMilestone()`) saat
create, tidak pernah ada UI admin untuk mengubah urutannya lagi setelah
dibuat (dikonfirmasi grep — nol pemanggilan `update(['sort_order' => ...])`
di app manapun).

**Implikasi untuk Editor Blok Konten:** pola `moveOrderItem()` (swap
in-memory, lalu SEMUA blok di-save ulang sekaligus lewat satu action
"Simpan") adalah acuan paling dekat & paling teruji-polanya untuk dicontek
langsung. Kalau desainnya "tiap klik naik/turun langsung persist ke DB satu
per satu", itu POLA BARU yang belum pernah ada presedennya di app ini —
perlu keputusan desain eksplisit, bukan cuma "ikuti yang sudah ada".

---

## 5. Field `content` (JSON) per Tipe Blok — dari 9 komponen render

Dicek satu-satu langsung dari `resources/views/components/content-block/*.blade.php`
supaya form admin nanti PERSIS sinkron field-nya dengan yang dibaca
renderer (tidak menebak dari dokumen spec):

| Tipe | Field JSON yang dibaca | Catatan |
|---|---|---|
| `heading` | `level` (int, di-clamp 1-3 di blade sendiri via `max(1, min(3, ...))`), `text` | `level` BOLEH invalid/di luar 1-3 di data, blade yang membetulkan tampilannya sendiri — tapi form admin sebaiknya tetap validasi 1-3 supaya tidak mengandalkan clamp defensif ini |
| `text` | `markdown` | Lewat `SafeMarkdown::toHtml()` — form cukup textarea markdown polos |
| `callout` | `variant` (harus salah satu `info`/`tip`/`warning`, fallback ke `info` kalau invalid/kosong), `title` (opsional), `body` (markdown) | Form WAJIB `<select>` 3 opsi tetap untuk `variant`, bukan text bebas |
| `code` | `language` (opsional, tampil sebagai label kecil kalau diisi), `code` | `language` murni label tampilan, TIDAK dipakai untuk syntax highlighting sungguhan (tidak ada library highlight di render ini) |
| `image` | `url`, `alt` (opsional), `caption` (opsional) | Form perlu 3 field terpisah; TIDAK ada upload file — `url` diasumsikan link eksternal/sudah di-hosting, konsisten dengan tidak adanya infrastruktur upload gambar di app ini |
| `video` | `url`, `caption` (opsional) | Provider (YouTube/Vimeo) di-DETEKSI OTOMATIS dari `url` via regex di blade sendiri — form CUKUP satu field url polos, TIDAK butuh field "provider" terpisah. URL yang tidak dikenali otomatis jatuh ke tautan biasa (bukan error) |
| `list` | `style` (`unordered`/`ordered`, fallback `unordered`), `items` (array of string, tiap item lewat markdown) | Form butuh input dinamis tambah/hapus item (array), + toggle ordered/unordered |
| `table` | `headers` (array of string, opsional — `<thead>` di-skip total kalau kosong), `rows` (array of array of string) | Form butuh builder tabel dinamis (tambah/hapus baris & kolom) — paling kompleks dari 9 tipe |
| `custom_html` | `html` (raw string, di-SANITIZE lewat `HtmlSanitizer::sanitize()` SETIAP render, bukan saat disimpan) | Form textarea HTML polos; sanitasi sudah defense-in-depth di sisi render, form TIDAK perlu sanitasi tambahan sendiri (sudah pasti aman apa pun yang tersimpan) |

**Tidak ditemukan mismatch** antara field yang dokumen (`content-blocks-spec.md`)
sebut vs field yang benar-benar dibaca blade — 9 komponen ini konsisten
dengan spec yang sudah ada. Poin PENTING buat form admin: beberapa field
punya validasi/normalisasi HANYA di sisi renderer (`heading.level` clamp,
`callout.variant` fallback) — form admin idealnya tetap validasi eksplisit
di sisi input (bukan mengandalkan renderer membetulkan data kotor), supaya
preview akhir dan apa yang admin kira sudah disimpan tidak berbeda.

---

## 6. `config/navigation.php` — konfirmasi ulang

Dicek ulang, isi PERSIS sama dengan temuan recon sebelumnya:
```php
'admin' => [
    ...
    ['label' => 'Antrian Review Praktik', 'route' => null, 'enabled' => false],
],
```
**Tidak ada entry "Kelola Kurikulum" untuk admin** — array admin cuma
berisi Dashboard, Manajemen Akun, Project Ideas, Proyek, Log WEBI, Antrian
Review Praktik (disabled). Entry baru perlu DITAMBAHKAN dari nol (bukan
mengaktifkan yang sudah ada), pola label serupa `['label' => 'Kelola
Kurikulum', 'route' => 'admin.curriculum.index', 'enabled' => true]`.

---

## Ringkasan Cepat untuk Editor Blok Konten

1. Ikuti pola `Admin/Users/` PERSIS: full-page Livewire component per aksi
   (Index/Create/Edit terpisah, bukan satu component besar dengan banyak
   mode), `#[Layout('components.layouts.app')]`, `#[Title(...)]`, validasi
   inline di method aksi, flash session untuk pesan sukses, `max-w-lg`
   (atau lebih lebar kalau memang perlu, tabel builder butuh ruang) untuk
   form.
2. Reorder blok TIDAK punya preseden "persist per-klik" — pola terdekat
   (`moveOrderItem`) murni in-memory + 1 tombol save besar. Rekomendasi:
   ikuti pola itu (susun urutan di memory dulu, satu action "Simpan Urutan"
   nulis semua `order` sekaligus), bukan bikin pola baru real-time-persist.
3. Field JSON per tipe blok sudah dipetakan lengkap di Area 5 — form admin
   tinggal dibangun 1:1 dari tabel itu, tidak perlu re-derive dari
   `content-blocks-spec.md` lagi.
4. `config/navigation.php['admin']` butuh 1 entry baru ditambahkan (bukan
   di-enable-kan) untuk link masuk ke Kelola Kurikulum.
5. Card ringkasan "Status Kurikulum" di dashboard admin: contek
   `Dashboard::webiSummary()` + section blade-nya persis, bukan bikin
   pattern card baru.
