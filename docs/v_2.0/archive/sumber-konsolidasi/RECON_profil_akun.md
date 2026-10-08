# Recon — Profil dan Akun v1.0

Laporan ini murni observasi kode NYATA saat ini (dicek langsung, bukan
diasumsikan). Dibuat sebagai bekal sub-modul Profil 2.2.3. **Tidak ada kode
yang diubah.**

## A. Halaman/komponen profil yang sudah ada

**1. Halaman profil (tempat user lihat/edit data dirinya sendiri): TIDAK
ADA.** Dicek lewat grep "profile"/"Profile"/"Profil" di seluruh `app/` dan
`routes/` — nol hasil. Tidak ada route, class Livewire, atau view apa pun
untuk ini. Satu-satunya tempat user melihat/menyunting data user LAIN
adalah halaman admin (`/admin/users/{user}/edit`), dan itu cuma bisa
diakses admin, bukan halaman "profil diri sendiri".

**2. Edit nama/email/password (self-service oleh user sendiri): TIDAK
ADA.** Yang ada sekarang HANYA versi admin-mengelola-user-lain:
- `App\Livewire\Admin\Users\Edit` (`resources/views/livewire/admin/users/edit.blade.php`,
  route `/admin/users/{user}/edit`, admin-only) — admin bisa ubah `name`,
  `email`, `role`, `membership_status` milik user LAIN, dan reset password
  user itu (`resetPassword()`, generate password acak baru, bukan user  
  memilih sendiri).
- Tidak ada satu pun tempat di kode di mana seorang user mengubah datanya
  SENDIRI (baik anggota eksplorasi/eksekusi maupun admin).

**3. Logout: sudah ada dan berfungsi.** Form POST sederhana di navbar
(`resources/views/components/shell/navbar.blade.php`, `<form method="POST"
action="{{ url('/logout') }}">` + tombol submit "Keluar"), diproses oleh
`App\Http\Controllers\Auth\LogoutController` (invokable single-action
controller) — memanggil `Auth::logout()`, `session()->invalidate()`,
`session()->regenerateToken()`, lalu redirect ke `/login`. Route:
`Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout')`
(`routes/web.php`). Tidak perlu dibangun ulang untuk Profil — ini sudah
selesai dan cukup untuk profil.

## B. Kondisi field User di kode

**4. Ketiga field yang ditanya SUDAH ADA di migrasi DAN model** — bukan
cuma di dokumen. Dicek langsung di
`database/migrations/0001_01_01_000000_create_users_table.php`:
```php
$table->string('avatar_url')->nullable();
$table->json('interest_field')->nullable();
$table->enum('membership_status', ['active', 'inactive']);
```
Ketiganya juga sudah masuk `#[Fillable([...])]` di `app/Models/User.php`.
**Tidak ada migrasi baru yang dibutuhkan untuk field-field ini** — sudah
lengkap sejak fondasi database (2.0 v1.0).

**5. `interest_field`: tipe kolom `json` di DB, di-cast `'array'` di model**
(`protected function casts(): array { return ['interest_field' => 'array']; }`,
`User.php:27-32`). Disimpan sebagai array PHP biasa (sesuai enum yang
disebut di konteks: frontend/backend/ui_ux/analyst/pm/fullstack), otomatis
di-encode/decode JSON oleh Eloquent lewat cast ini — tidak perlu logic
encode/decode manual saat form Profil nanti menulis ke field ini.

**Temuan penting:** `interest_field` **SUDAH DIBACA** oleh WEBI untuk
personalisasi — `App\Services\Webi\PersonalizationContextBuilder.php:31`:
```php
$interestField = $user->interest_field ? implode(', ', $user->interest_field) : '(belum diisi)';
```
(diteruskan ke system prompt lewat blok `[USER_CONTEXT]`, dites di
`tests/Feature/Webi/PersonalizationTest.php`). Fallback string
`'(belum diisi)'` itu sendiri jadi bukti tidak langsung bahwa field ini
memang SELALU kosong sampai sekarang — karena poin berikutnya:

**Tidak ada satu pun tempat di kode yang MENULIS ke `interest_field`.**
Digrep di seluruh `app/` — hanya muncul di `Fillable` (deklarasi), model
cast, dan tiga file WEBI yang MEMBACANYA. Tidak ada form, tidak ada
seeder, tidak ada command yang pernah mengisinya. Ini persis gap yang
perlu ditutup Profil: field-nya sudah siap dan sudah dipakai konsumennya
(WEBI), tinggal kanal untuk mengisinya yang belum ada.

**6. `avatar_url`: kolomnya ADA** (`string`, nullable) **tapi BENAR-BENAR
TIDAK DIPAKAI di mana pun** — digrep di seluruh `app/` dan
`resources/views/**/*.blade.php`, muncul HANYA di migrasi dan deklarasi
`Fillable` model. Tidak dibaca, tidak ditulis, tidak ditampilkan di view
manapun. Kolom ini murni dorman, menunggu fitur Avatar (disebut terpisah
dari Profil di 2.1.2 — "Aset visual disiapkan terpisah oleh Aye, logic
unlock oleh Claude Code").

## C. Registrasi dan pengisian data awal

**7. Tidak ada registrasi self-service sama sekali.** Digrep "register" di
`routes/` — nol hasil, tidak ada route pendaftaran akun baru untuk publik.
User HANYA dibuat lewat dua jalur, keduanya oleh pihak lain (bukan
calon-user sendiri):
- **`php artisan app:create-admin`** (`App\Console\Commands\CreateAdmin`)
  — command interaktif, tanya nama/email/password lewat prompt terminal,
  SELALU membuat role `admin`. Dipakai untuk akun admin pertama (dan admin
  tambahan kalau perlu), bukan untuk anggota biasa.
- **`App\Livewire\Admin\Users\Create`** (`/admin/users/create`, admin-only)
  — admin mengisi nama/email/role untuk anggota BARU, password di-generate
  otomatis (`Str::password(12)`, acak 12 karakter) dan ditampilkan SEKALI
  lewat flash session (`generated_password`) untuk dicatat admin lalu
  disampaikan ke anggota — anggota sendiri TIDAK pernah memilih password
  awalnya sendiri.
- `database/seeders/DatabaseSeeder.php` (yang jalan di `migrate:fresh --seed`)
  **TIDAK membuat user apa pun** — cuma seed kurikulum. Ini keputusan sadar
  dari task 2.7, dikonfirmasi lewat komentar eksplisit di file itu.

**8. Field yang diisi saat pembuatan:** Baik `CreateAdmin` maupun
`Admin\Users\Create` HANYA mengisi `name`, `email`, `password_hash`,
`role`, `membership_status` (selalu `'active'`). **`interest_field` dan
`avatar_url` TIDAK PERNAH diisi di titik pembuatan manapun** — keduanya
`nullable`, jadi user baru selalu punya `interest_field = null` dan
`avatar_url = null` sampai (kalau nanti ada) mekanisme lain mengisinya.
Profil adalah kandidat pertama dan satu-satunya rencana untuk mengisi
`interest_field`; Avatar (batch terpisah) untuk `avatar_url`.

## D. Validasi dan pola yang sudah ada

**9. Pola validasi di `Admin\Users\Edit`/`Create`** (keduanya Livewire
`$this->validate([...])` biasa, bukan Form Request terpisah):
```php
'name' => ['required', 'string', 'max:255'],
'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->user->id], // pengecualian ID sendiri saat edit
'role' => ['required', 'in:exploration_member,execution_member,admin'],
'membership_status' => ['required', 'in:active,inactive'],
```
Konsisten dan sederhana — form Profil nanti sebaiknya memakai pola yang
sama persis (rule array biasa di method Livewire, exception unique-email
pakai `$this->user->id` atau `Auth::id()` untuk profil diri sendiri).

**Pola "self-edit guard" yang relevan sebagai preseden** (bukan untuk
dipakai ulang literal, tapi pola pengamanannya relevan):
`Admin\Users\Edit::save()` punya pengecekan eksplisit
`if ($this->user->id === Auth::id())` sebelum izinkan perubahan
role/status — mencegah admin mengunci diri sendiri. Untuk Profil (user
mengedit DIRI SENDIRI, bukan orang lain), pengecekan simetris yang relevan
adalah: profil TIDAK boleh mengizinkan user mengubah `role` atau
`membership_status` miliknya sendiri sama sekali (field itu murni domain
admin) — form Profil sebaiknya cuma expose `name`/`email`/`password`/
`interest_field`, tidak pernah `role`/`membership_status`.

**10. Update password di `Admin\Users\Edit`:** method `resetPassword()`
—generate password ACAK baru (`Str::password(12)`), langsung
`$this->user->update(['password_hash' => bcrypt($password)])`, tampilkan
sekali lewat flash session. **Pola ini TIDAK bisa dipakai ulang langsung
untuk Profil** karena beda kebutuhan fundamental: admin reset password
ORANG LAIN (tidak perlu tahu password lama, cukup wewenang admin), sedang
user ganti password SENDIRI biasanya perlu (a) konfirmasi password lama
(security best-practice, mencegah orang yang kebetulan memegang sesi login
terbuka mengubah password tanpa tahu password asli), dan (b) user memilih
password sendiri (bukan digenerate acak dan ditampilkan). **Ini bagian
yang harus dibangun BARU, bukan reuse** — tidak ada satu pun pola "ganti
password sendiri dengan konfirmasi password lama" di kode saat ini.

## E. Test terkait

Test yang paling relevan (menyentuh User/akun/profil secara langsung):
- `tests/Feature/Auth/LoginTest.php` — flow login, harus tetap hijau
  (Profil tidak boleh menyentuh mekanisme login).
- `tests/Feature/Console/CreateAdminTest.php` — command `app:create-admin`.
- `tests/Feature/Admin/Users/UserManagementTest.php` — CRUD admin atas
  user lain (create, edit role/status, reset password, self-lockout guard,
  efek deaktivasi ke sesi aktif). Pola test di sini (Livewire::actingAs +
  assertHasErrors, cek `Hash::check` untuk password) adalah template yang
  paling relevan untuk ditiru saat menulis test Profil.
- `tests/Feature/Admin/Users/RouteBindingSmokeTest.php` — smoke test rute
  HTTP nyata untuk halaman user admin.
- `tests/Feature/Webi/PersonalizationTest.php` — menguji `interest_field`
  DIBACA dengan benar oleh WEBI; test ini harus tetap hijau setelah Profil
  dibangun (Profil menulis ke field yang sama, tidak boleh mengubah cara
  field ini dibaca).

Tidak ada test yang menyentuh `avatar_url` atau `interest_field` dari sisi
PENULISAN sama sekali saat ini (konsisten dengan temuan B — belum pernah
ada kanal untuk menulisnya).

## F. Penilaian

**Skema database: TIDAK perlu migrasi baru sama sekali.** Ketiga field
yang relevan (`avatar_url`, `interest_field`, `membership_status`) sudah
lengkap di tabel `users` sejak fondasi (2.0). Ini kabar baik — Profil
2.2.3 murni pekerjaan Livewire component + view + validasi, bukan
pekerjaan skema.

**Yang bisa dipakai ulang:**
- Pola validasi Livewire (`$this->validate([...])` sederhana, aturan mirip
  `Admin\Users\Edit`).
- Pola unique-email-kecuali-diri-sendiri (`unique:users,email,'.$id`).
- Logout sudah selesai total, tidak perlu disentuh.
- Konvensi flash-session untuk pesan sukses (`session()->flash('status', ...)`)
  sudah dipakai di `Admin\Users\Edit`, bisa diikuti untuk halaman Profil.

**Yang harus dibangun baru (belum ada preseden sama sekali di kode):**
- Halaman/route/component Profil itu sendiri (dari nol — tidak ada apa pun
  untuk dimulai dari situ).
- Form ganti password SELF-SERVICE dengan konfirmasi password lama —
  pola yang ada sekarang (`resetPassword()` admin) generate-acak-untuk-orang-lain,
  bukan model yang cocok untuk "saya ganti password saya sendiri".
- UI untuk mengisi `interest_field` (checkbox/select multi-value dari enum
  frontend/backend/ui_ux/analyst/pm/fullstack) — field-nya siap di DB dan
  sudah dikonsumsi WEBI, tapi belum pernah ada UI penulisannya sama sekali.
- Guard eksplisit: form Profil TIDAK boleh mengekspos `role`/`membership_status`
  (beda dari form admin yang memang boleh mengubah keduanya) — ini bukan
  soal "pakai ulang tapi dibatasi", tapi soal sengaja TIDAK menyertakan
  field itu sama sekali di form Profil.
- Avatar (`avatar_url`) sengaja DI LUAR scope Profil 2.2.3 per pemisahan
  eksplisit di 2.1.2 — tidak perlu dikerjakan bersamaan.

**Kesimpulan:** pekerjaan Profil 2.2.3 lebih ke arah "membangun halaman
baru dari nol mengikuti pola yang sudah mapan" daripada "menyatukan/memperbaiki
sesuatu yang berantakan" — fondasi (skema, model, cast, WEBI consumer untuk
interest_field, logout) semuanya sudah solid dan tidak perlu disentuh;
yang kurang murni di lapisan presentasi (belum ada halaman) dan satu pola
baru (ganti password sendiri dengan konfirmasi password lama).
