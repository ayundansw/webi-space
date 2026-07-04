# DEPLOYMENT CHECKLIST — WEBI-SPACE

Checklist manual untuk deploy pertama kali ke production (rumahweb shared hosting,
domain **rit-base.online**, deploy via fitur Git Version Control cPanel — lihat
`docs/tech-stack.md` §4). Ikuti berurutan dari atas ke bawah, jangan lompat bagian.

Setiap eksekusi command di server dilakukan manual olehmu — dokumen ini cuma
panduan urutannya.

> **Sumber checklist ini:** `docs/Catatan_Troubleshooting_Deployment_WEBI-SPACE.md`
> (log insiden nyata dari deploy pertama project ini di tahap 1.10, akun cPanel
> `rits8313`) — semua langkah di bawah yang menyebut error/gejala spesifik
> mengacu langsung ke dokumen itu, bukan dugaan.

---

## 0. Sebelum push — sudah beres di sisi kode (task 2.8)

- [x] `.env.example` mencantumkan semua variabel yang benar-benar dipakai
      (termasuk `GEMINI_API_KEY`, `GEMINI_MODEL`, `GEMINI_THINKING_LEVEL`, dan
      `DB_*` untuk MySQL — sebelumnya hilang/di-comment ke sqlite).
- [x] `npm run build` sukses tanpa error/warning dari kondisi bersih.
- [x] Scheduler (`routes/console.php`) sudah benar: satu-satunya scheduled
      command adalah `execution:check-alerts` (`->daily()`). WEBI tidak
      punya scheduled command untuk sapaan proaktif — itu memang by design
      real-time (dihitung saat halaman chat dibuka lewat
      `ProactiveService::checkAndDeliver()`), bukan gap.
- [x] Seluruh test suite hijau (lihat Batch 5 di laporan).
- [x] Fitur Attachment (Eksekusi) sudah tidak bergantung pada `storage:link`/
      symlink sama sekali — server rumahweb mematikan `symlink()` sepenuhnya.
- [x] **(Task 2.9, menggantikan pendekatan disk publik di atas)** Attachment
      file sekarang ditulis ke disk PRIVAT (`storage/app/private`, disk
      `local` bawaan Laravel), bukan lagi disk publik apa pun — file cuma
      bisa diakses lewat route `/attachments/{attachment}/download` yang
      wajib login DAN cek keanggotaan proyek (sebelumnya siapa pun dengan
      URL bisa akses langsung, celah keamanan yang ditemukan saat menganalisis
      solusi symlink). Tidak ada folder publik baru yang perlu disiapkan di
      server untuk fitur ini.

**Temuan penting yang memengaruhi urutan di bawah:**
- Aplikasi ini **tidak memakai job queue sama sekali** (`QUEUE_CONNECTION=database`
  di `.env` cuma warisan default Laravel, tidak ada satu pun `ShouldQueue`/`Job`
  di kodenya) — **tidak perlu** menjalankan `php artisan queue:work` atau setup
  supervisor apa pun di server.
- `/public/build` (hasil `npm run build`) dan `/vendor` (hasil `composer install`)
  sama-sama di-gitignore — keduanya TIDAK ikut ter-pull dari git, harus
  dibuat/diisi terpisah di server (lihat langkah 4 dan 5).
- Tidak diketahui apakah server rumahweb ini punya Node.js/npm terpasang —
  mengingat `proc_open` saja sudah dimatikan (memengaruhi Composer scripts),
  kemungkinan besar tidak ada Node juga. **Rekomendasi: build asset di lokal
  (sudah dilakukan barusan), lalu upload folder `public/build` manual lewat
  File Manager**, bukan mengandalkan `npm run build` jalan di server. Cek dulu
  ketersediaan Node lewat Terminal cPanel sebelum memutuskan.

---

## 1. Push kode ke GitHub

- [ ] Pastikan tidak ada file sensitif ikut ter-commit (`.env`, dsb — sudah
      di-gitignore, tapi cek ulang `git status` sebelum push).
- [ ] Push branch yang sudah final ke GitHub.

## 2. Tarik kode ke server

- [ ] Lewat fitur **Git Version Control** di cPanel: clone repo ini **langsung
      ke `public_html/webi-space`** (bukan ke `repositories/webi-space` lalu
      copy manual) — repo ini public, clone tidak butuh autentikasi SSH sama
      sekali, jadi langkah copy dua tahap tidak diperlukan.
- [ ] Fallback kalau fitur Git bermasalah: upload manual lewat File Manager
      (zip lokal, extract di server).

## 3. Persiapan PHP dan Composer di server

- [ ] **Cek versi PHP CLI yang aktif di Terminal cPanel:** `php -v`. Project
      ini butuh **PHP ^8.3** (`composer.json`). Server rumahweb kadang default
      ke versi lebih lama (pernah kejadian 8.2) — kalau versi CLI tidak cocok,
      cek menu cPanel > MultiPHP Manager dan pastikan versi PHP untuk domain
      ini di-set ke 8.3, dan CLI ikut memakainya (bukan cuma versi PHP-FPM
      untuk web request).
- [ ] **Composer TIDAK preinstalled di server rumahweb** — install manual
      sekali per server (lewat Terminal cPanel):
      ```
      cd /tmp
      curl -sS https://getcomposer.org/installer | php
      mkdir -p ~/bin
      mv composer.phar ~/bin/composer
      chmod +x ~/bin/composer
      echo "export PATH=$HOME/bin:$PATH" >> ~/.bash_profile
      source ~/.bash_profile
      ```
      Verifikasi: `composer --version`.

## 4. Dependencies PHP (Composer install)

- [ ] Dari folder project (`cd ~/public_html/webi-space`):
      ```
      composer install --no-dev --optimize-autoloader --no-scripts
      ```
      — **wajib pakai `--no-scripts`**: `proc_open` dimatikan di server ini
      (pengaturan keamanan umum shared hosting), kalau tidak pakai flag ini,
      Composer akan gagal di tengah proses dengan pesan
      `The Process class relies on proc_open, which is not available on your
      PHP installation.` saat mencoba menjalankan `package:discover` otomatis.
- [ ] Jalankan discovery paket secara manual (pengganti langkah otomatis yang
      di-skip di atas):
      ```
      php artisan package:discover --ansi
      ```

## 5. Asset frontend (Vite/Tailwind)

Pilih SATU dari dua opsi berikut, tergantung ketersediaan Node di server (cek
lewat Terminal cPanel: `node -v` dan `npm -v`):

- [ ] **Opsi A (direkomendasikan, lebih aman):** build sudah dilakukan di lokal
      (folder `public/build` hasil `npm run build`). Upload folder `public/build`
      ini manual lewat File Manager cPanel ke lokasi yang sama di server (folder
      ini di-gitignore, tidak ikut ter-pull lewat Git Version Control).
- [ ] **Opsi B (kalau Node tersedia di server):**
      ```
      npm install
      npm run build
      ```

## 5b. Verifikasi SSL Domain

- [ ] Cek status SSL certificate untuk `rit-base.online` lewat cPanel (menu
      **SSL/TLS Status**, atau **AutoSSL**). Domain ini sebelumnya menunjukkan
      tanda "?" di kolom **Force HTTPS Redirect** (beda dari `rit-base.org`
      dan `dojobaraya.rit-base.org` yang sudah "On") — indikasi SSL
      kemungkinan belum terpasang untuk domain ini.
- [ ] Kalau SSL belum aktif, jalankan **AutoSSL** dulu atau pasang certificate
      lewat menu itu sebelum lanjut ke langkah berikutnya.
- [ ] Catat hasilnya (aktif atau belum) — dipakai langsung untuk mengisi
      `APP_URL` di section 6 di bawah.

## 6. File `.env`

- [ ] Copy `.env.example` jadi `.env` di server, isi SEMUA variabel yang
      tercantum (jangan lewatkan satu pun — cek ulang ke `.env.example` yang
      sudah lengkap dari task 2.8, termasuk blok `GEMINI_*`).
- [ ] **`APP_ENV=production`** dan **`APP_DEBUG=false`** — WAJIB, jangan sampai
      lupa. Kalau `APP_DEBUG=true` di production, error apa pun akan menampilkan
      stack trace lengkap + detail konfigurasi ke publik.
- [ ] `APP_URL` — isi sesuai kondisi ASLI hasil cek langkah 5b, jangan asumsi:
      `https://rit-base.online` HANYA kalau SSL sudah dikonfirmasi aktif,
      `http://rit-base.online` kalau belum. Ini memengaruhi apakah sesi login
      nanti berfungsi normal atau gagal — jangan tebak.
- [ ] `DB_CONNECTION=mysql` + isi `DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD`
      sesuai database MySQL yang dibuat di cPanel.
- [ ] `GEMINI_API_KEY` diisi dari project Google Cloud yang **didedikasikan
      khusus** untuk WEBI-SPACE (bukan project yang dipakai bareng aplikasi
      lain — kuota free tier Gemini per-project, bukan per-key).
- [ ] `GEMINI_MODEL=gemini-3.5-flash`, `GEMINI_THINKING_LEVEL=minimal` (nilai
      final yang sudah diuji, lihat CLAUDE.md kalau mau ubah).
- [ ] **Format penulisan `.env` — persis masalah nyata yang pernah kejadian di
      server ini (Masalah 5, `docs/Catatan_Troubleshooting_Deployment_WEBI-SPACE.md`):**
  - Jangan ada tanda `#` di depan baris `DB_HOST`/`DB_PORT`/`DB_DATABASE`/
    `DB_USERNAME`/`DB_PASSWORD` atau baris aktif lainnya (mudah ke-comment
    tanpa sengaja saat edit manual lewat File Manager/vi). Gejala kalau ini
    kejadian: `SQLSTATE[28000] Access denied for user 'root'@'localhost'
    (using password: NO)` — Laravel diam-diam jatuh balik ke default
    `root`/tanpa password/database `laravel` karena baris-baris di atas
    dianggap komentar, bukan berarti kredensial yang kamu isi salah.
  - Password atau value apa pun yang mengandung karakter spesial (spasi, `#`,
    `$`, `"`, `'`, dst) **wajib dibungkus tanda kutip dua**, contoh:
    `DB_PASSWORD="aEy0JvQZ1#P4QYBk"` — tanpa kutip, karakter spesial (`#`
    khususnya) berisiko dianggap awal komentar dan memotong sisa isinya.
- [ ] Kalau `APP_KEY` masih kosong: `php artisan key:generate --force`
- [ ] **Setelah selesai isi/edit `.env`, jalankan `php artisan config:clear`
      sebelum lanjut ke langkah migrasi** — jaga-jaga kalau ada cache config
      lama tersimpan dari percobaan sebelumnya, supaya migrate benar-benar
      baca `.env` yang baru diisi, bukan cache basi.

## 7. Permission storage

- [ ] Pastikan folder `storage/` dan `bootstrap/cache/` writable oleh user
      web server (biasanya `chmod -R 775` sudah cukup di cPanel, sesuaikan
      kalau ada aturan permission khusus dari hosting).
- [ ] **`php artisan storage:link` TIDAK DIPAKAI LAGI di server ini — jangan
      dijalankan.** Server rumahweb mematikan fungsi `symlink()` sepenuhnya
      lewat `disable_functions` (dikonfirmasi via `ini_get('disable_functions')`),
      jadi command ini akan gagal/tidak berguna. Fitur Attachment di Eksekusi
      (upload file ke task) sekarang menulis ke disk `local` yang PRIVAT
      (`storage/app/private`, bawaan Laravel, di luar `public/` sepenuhnya) —
      file diakses lewat route `/attachments/{attachment}/download` yang cek
      login + keanggotaan proyek dulu (task 2.9), tidak pernah disajikan
      langsung sebagai file statis oleh web server. Tidak ada symlink dan
      tidak ada folder publik baru yang perlu disiapkan untuk fitur ini.
- [ ] Pastikan folder `storage/app/private` writable oleh user web server
      (sudah tercakup oleh permission `storage/` di atas). Laravel otomatis
      membuat subfolder `attachments/` di dalamnya saat upload pertama kali —
      tidak perlu `mkdir` manual.

## 8. Document root domain

- [ ] Di cPanel (Domains > Manage), pastikan document root untuk
      `rit-base.online` menunjuk ke `public_html/webi-space/public`
      (folder **`public/`** di dalam project ini), BUKAN ke root project.
      (`public/.htaccess` bawaan Laravel sudah ada dan tidak perlu diubah —
      asal document root-nya benar.)

## 9. Migrasi database dan data awal

**Urutan ini final dan sudah diverifikasi di task 2.7** — jangan dibalik:

- [ ] **Untuk deploy PERTAMA KALI ke database yang benar-benar kosong saja:**
      ```
      php artisan migrate:fresh --seed --force
      ```
      Ini akan menjalankan `CurriculumSeeder` SAJA (10 modul, 67 unit asli) —
      tidak ada data sample/dummy, tidak ada user apa pun yang otomatis
      terbuat.
  > ⚠️ **PERINGATAN:** `migrate:fresh` MENGHAPUS SEMUA TABEL. Jangan pernah
  > jalankan ini lagi setelah ada data anggota sungguhan di database — kalau
  > perlu migrasi tambahan di masa depan, pakai `php artisan migrate --force`
  > (tanpa `--fresh`) saja.
- [ ] Buat akun admin pertama secara terpisah, interaktif (tidak ada
      kredensial di-hardcode di kode manapun):
      ```
      php artisan app:create-admin
      ```
      Command ini akan menanyakan nama, email, dan password lewat prompt
      terminal (password tersembunyi saat diketik).
- [ ] Login ke `/login` pakai akun admin yang baru dibuat, konfirmasi bisa
      masuk ke `/admin/dashboard`.

## 10. Cache production — PALING TERAKHIR

**Baru jalankan bagian ini setelah `.env` benar-benar final** (semua value di
atas sudah benar dan tidak akan diubah lagi dalam waktu dekat):

- [ ] ```
      php artisan config:cache
      php artisan route:cache
      php artisan view:cache
      ```

> ⚠️ **Kalau nanti perlu edit `.env` LAGI setelah langkah ini** (ganti API key,
> ubah `APP_DEBUG`, dsb): jalankan `php artisan config:clear` DULU sebelum
> edit, edit filenya, baru `php artisan config:cache` lagi. Kalau langsung
> edit `.env` tanpa `config:clear` dulu, perubahan TIDAK akan kepakai karena
> aplikasi masih baca dari cache config lama.

## 11. Cron job — satu-satunya yang perlu didaftarkan

- [ ] Daftarkan **satu** cron job di cPanel (Cron Jobs), jalan tiap menit:
      ```
      * * * * * cd /path/ke/project && php artisan schedule:run >> /dev/null 2>&1
      ```
      Ini satu-satunya cron yang dibutuhkan — semua command terjadwal
      (`execution:check-alerts`, jalan harian) dipanggil lewat `schedule:run`
      ini, tidak perlu cron terpisah per command.
- [ ] Verifikasi terdaftar benar dengan (lewat Terminal cPanel):
      ```
      php artisan schedule:list
      ```

## 12. Verifikasi akhir

- [ ] Buka `https://rit-base.online/login`, pastikan halaman muncul benar
      (font Sora/Plus Jakarta Sans/JetBrains Mono termuat, styling tidak
      polos/rusak — kalau CSS tidak termuat, cek lagi langkah 5).
- [ ] Login sebagai admin, cek `/admin/dashboard` menampilkan data kosong yang
      wajar (0 anggota, 0 proyek — bukan error).
- [ ] Buat 1 akun `exploration_member` dan 1 `execution_member` lewat
      `/admin/users/create`, coba login masing-masing, konfirmasi RBAC benar
      (tidak bisa akses modul milik role lain).
- [ ] Cek WEBI (`/eksplorasi/webi`) bisa membalas pesan sungguhan (konfirmasi
      `GEMINI_API_KEY` valid dan kuota project tidak habis).
- [ ] Cek upload attachment di sebuah task (Eksekusi): upload file, klik link
      attachment-nya, konfirmasi bisa terunduh (disk privat + route
      `/attachments/{id}/download` jalan benar). Coba juga buka URL download
      itu dari akun anggota proyek LAIN atau saat logout — harus ditolak
      (403/redirect login), bukan malah bisa diakses.
- [ ] Terakhir, pastikan sekali lagi `APP_DEBUG=false` aktif di server —
      caranya paling aman: coba akses URL yang sengaja salah/tidak ada
      (404 harus tampil sebagai halaman generic, bukan stack trace).
