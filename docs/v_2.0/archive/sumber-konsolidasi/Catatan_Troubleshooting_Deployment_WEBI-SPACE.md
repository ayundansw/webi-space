# Catatan Troubleshooting Deployment WEBI-SPACE ke rumahweb

**Konteks:** Proses setup infrastruktur (1.10), deploy pertama kali project Laravel dari lokal ke hosting rumahweb (`rit-base.online`, akun cPanel `rits8313`). Dicatat supaya tidak perlu debug ulang dari nol kalau kejadian serupa muncul lagi (deploy project baru, pindah versi PHP, dst).

---

## Keputusan arsitektur deployment yang dipakai

- Repo GitHub **public** (bukan private). Awalnya coba private + SSH key, gagal berkali-kali (lihat bagian SSH di bawah), akhirnya pindah ke public karena risiko keamanannya kecil untuk proyek internal ini (`.env` tidak pernah ikut ter-push, isinya cuma kode aplikasi).
- Git Version Control cPanel di-clone **langsung ke `public_html/webi-space`**, bukan ke `repositories/webi-space` lalu copy manual. Ini cuma valid karena repo public (tidak butuh autentikasi), dan menghindari langkah copy dua tahap.
- Document Root domain `rit-base.online` diarahkan ke `public_html/webi-space/public` lewat menu cPanel > Domains > Manage.

---

## Masalah 1: SSH key gagal untuk repo private

**Gejala:** `Permission denied (publickey)` saat clone via SSH, walau key sudah dibuat dan Deploy Key sudah didaftarkan di GitHub.

**Yang sudah dicoba:** generate key custom name, generate ulang dengan nama `id_rsa`, daftarkan ke GitHub Deploy Keys. Tetap gagal.

**Akar masalah:** tidak ditemukan secara pasti, kemungkinan soal permission file key atau passphrase, tidak bisa dipastikan tanpa akses langsung debug server.

**Keputusan:** berhenti debug SSH, pindah ke repo public (lihat keputusan arsitektur di atas). Kalau suatu saat butuh private repo lagi, kemungkinan perlu bantuan tim support rumahweb langsung, bukan trial-error mandiri.

---

## Masalah 2: Composer belum terinstall di server

**Gejala:** `bash: composer: command not found`.

**Penyebab:** Composer tidak otomatis tersedia di server cPanel, beda dari di lokal (Laragon sudah bundle Composer).

**Solusi (command persis yang berhasil):**
```
cd /tmp
curl -sS https://getcomposer.org/installer | php
mkdir ~/bin
mv composer.phar ~/bin/composer
chmod +x ~/bin/composer
echo "export PATH=$HOME/bin:$PATH" >> ~/.bash_profile
source ~/.bash_profile
```
Verifikasi: `composer --version`.

---

## Masalah 3: Versi PHP server tidak cocok dengan requirement Laravel

**Gejala:** `Root composer.json requires php ^8.3 but your php version (8.2.31) does not satisfy that requirement.`

**Penyebab:** Laragon lokal pakai PHP 8.3 (default saat `composer create-project laravel/laravel .` dijalankan, otomatis dapat Laravel 13 yang minta PHP 8.3), sementara PHP default server awalnya 8.2.

**Solusi:** server rumahweb ternyata punya PHP 8.3 tersedia, tinggal dipastikan versi CLI terminal juga ikut 8.3 (dicek lewat `php -v`). Tidak perlu downgrade Laravel.

**Catatan untuk ke depan:** kalau bikin project baru lagi dan mau aman, cek dulu versi PHP default server sebelum `composer create-project` di lokal, supaya tidak mismatch dari awal.

---

## Masalah 4: `proc_open` dimatikan di server

**Gejala:** saat `composer install` sampai ke tahap `php artisan package:discover` otomatis, muncul error `The Process class relies on proc_open, which is not available on your PHP installation.`

**Penyebab:** pengaturan keamanan umum di shared hosting, fungsi PHP `proc_open` (dipakai untuk menjalankan subprocess) dimatikan.

**Solusi:** skip script otomatis composer, jalankan manual setelahnya:
```
composer install --no-dev --optimize-autoloader --no-scripts
php artisan package:discover --ansi
```

**Catatan untuk ke depan:** kalau nanti ada command lain yang butuh spawn subprocess (`--no-scripts` di composer, atau perintah Laravel lain yang manggil proses eksternal), kemungkinan besar bakal kena batasan yang sama, perlu dicari cara manual/alternatif tiap kali ketemu.

---

## Masalah 5: Access denied database, padahal kredensial sudah diisi

**Gejala:** `SQLSTATE[28000] Access denied for user 'root'@'localhost' (using password: NO)`, padahal `.env` sudah diisi kredensial database cPanel yang benar.

**Penyebab:** baris-baris `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` di `.env` masih diawali tanda `#`, artinya dianggap komentar/tidak aktif. Laravel jatuh balik ke default (`root`, tanpa password, database `laravel`).

**Solusi:** hapus tanda `#` di depan baris-baris itu.

**Catatan tambahan:** kalau password database mengandung karakter `#` (seperti kasus ini), bungkus nilainya pakai tanda kutip dua di `.env`, contoh:
```
DB_PASSWORD="aEy0JvQZ1#P4QYBk"
```
Tanpa tanda kutip, karakter `#` di tengah nilai berisiko dianggap awal komentar dan memotong sisa isinya.

Setelah edit, wajib jalankan `php artisan config:clear` sebelum `php artisan migrate`, karena Laravel bisa nyimpen cache config lama.

---

## Urutan command final yang terbukti berhasil (referensi cepat)

```
# Install composer (sekali saja per server)
cd /tmp
curl -sS https://getcomposer.org/installer | php
mkdir ~/bin
mv composer.phar ~/bin/composer
chmod +x ~/bin/composer
echo "export PATH=$HOME/bin:$PATH" >> ~/.bash_profile
source ~/.bash_profile

# Install dependency project
cd ~/public_html/webi-space
composer install --no-dev --optimize-autoloader --no-scripts
php artisan package:discover --ansi

# Setup environment
cp .env.example .env
php artisan key:generate
# edit .env manual: isi DB_* tanpa tanda #, bungkus password pakai kutip dua kalau ada karakter spesial

php artisan config:clear
php artisan migrate
chmod -R 775 storage bootstrap/cache
```
