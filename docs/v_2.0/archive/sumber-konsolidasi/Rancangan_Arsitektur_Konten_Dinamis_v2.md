# Rancangan Arsitektur Konten Dinamis — WEBI-SPACE v2.0

**Bagian dari:** 2.1.3 Definisi Konsep Baru
**Status:** Final, siap masuk 2.1.2 (Fiksasi Fitur v2.0)

---

## 1. Filosofi

Materi kurikulum disusun dari blok-blok konten kecil yang tersimpan sebagai data terstruktur, bukan teks polos. Menambah atau mengedit materi baru berarti menyusun/edit blok lewat admin panel, bukan menulis kode. Satu renderer generik membaca urutan blok dan menampilkannya sesuai tipe masing-masing, konsisten secara visual di semua materi tanpa perlu desain ulang tiap kali.

Untuk kasus yang tidak tercakup tipe blok manapun, tersedia jalur Custom HTML sebagai escape hatch, terpisah dari jalur utama.

---

## 2. Tipe Blok

| Tipe | Field | Catatan |
|---|---|---|
| **Heading** | `level` (1-6), `text` | H1-H6 sesuai kebutuhan judul/subjudul/topik/subtopik |
| **Teks** | `text` | Mendukung format inline terbatas: **bold**, *italic*, [link](url). Bukan markdown penuh, cukup tiga ini, konsisten dengan cara WEBI merender teks. |
| **Gambar** | `url`, `alt`, `caption` (opsional) | `url` eksternal (Cloudinary/imgur/dst), tidak ada upload file ke server kita |
| **Callout** | `variant` (info/tip/peringatan), `text` | Mendukung format inline sama seperti blok Teks |
| **Kode** | `language`, `code` | Syntax highlighting sesuai `language` |
| **Video** | `url`, `provider` (YouTube/Vimeo), `caption` (opsional) | Embed, bukan upload file video |
| **List** | `style` (bullet/numbered), `items` (array teks) | Tiap item mendukung format inline sama seperti blok Teks |
| **Custom HTML** | `html` | Escape hatch, dijelaskan di bagian 5 |

## 3. Skema Data

Satu tabel baru, `content_blocks`, **dirancang polymorphic sejak awal** (bukan terikat langsung ke `unit_id`), supaya bisa dipakai ulang oleh entitas lain yang butuh konten tersusun dari blok, khususnya track map Praktik (lihat Rancangan Modul Praktik v2):

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | uuid | PK |
| `blockable_type` | string | Nama model pemilik blok ini, contoh `Unit` atau `ChallengeStep` |
| `blockable_id` | uuid | ID record pemilik, sesuai `blockable_type` |
| `order` | integer | Urutan tampil, ditentukan admin |
| `type` | enum | `heading`, `paragraph`, `image`, `callout`, `code`, `video`, `list`, `custom_html` |
| `data` | json | Isi field sesuai tipe (lihat tabel bagian 2) |
| `created_at`, `updated_at` | timestamp | |

Ini keputusan final, bukan lagi catatan revisi terpisah. Satu implementasi tabel dan satu renderer (bagian 7) dipakai bersama oleh Materi dan Praktik, konsisten dengan prinsip DRY yang jadi salah satu tuntutan utama catatan penilaian v1.0.

Field `content` yang sekarang ada di tabel `units` (teks polos dari 2.3) tidak dihapus langsung, dipertahankan sebagai arsip/fallback sampai migrasi manual (bagian 6) selesai untuk seluruh 67 unit.

Untuk Unit, `blockable_type = 'Unit'`. Untuk track map Praktik, `blockable_type = 'ChallengeStep'`. Tidak ada tabel `content_blocks` terpisah untuk tiap entitas, satu tabel melayani keduanya.

## 4. Mekanisme Penyusunan (Admin Panel)

Form terstruktur, bukan editor gaya Notion:

1. Admin buka halaman edit Unit, lihat daftar blok yang sudah ada berurutan dari atas ke bawah.
2. Tombol "Tambah Blok", muncul pilihan tipe (dropdown), pilih satu, muncul form sesuai tipe itu.
3. Isi form, simpan, blok masuk ke daftar di posisi terakhir.
4. Urutan blok diatur lewat tombol naik/turun per blok (bukan drag-drop, lebih murah dibangun, cukup untuk kebutuhan susun-ulang materi yang tidak sering berubah drastis).
5. Tiap blok bisa diedit ulang atau dihapus langsung dari daftar itu.
6. Preview langsung tersedia, menampilkan hasil render blok-blok itu persis seperti yang akan dilihat anggota, sebelum admin publish perubahan.

## 5. Custom HTML (Escape Hatch)

Satu tipe blok khusus (`custom_html`) yang bisa disisipkan di antara blok lain kapan pun dibutuhkan visualisasi yang tidak tercakup tipe manapun di atas. Isinya HTML/CSS/JS mentah, dirender apa adanya.

**Catatan keamanan:** cuma admin (kamu) yang punya akses menulis ini. Risikonya setara dengan akses yang sudah kamu punya lewat Claude Code untuk mengubah kode aplikasi, bukan celah baru. Aturan mutlak: jangan pernah menempel script dari sumber yang tidak dipercaya ke blok ini, karena kode itu akan dieksekusi di browser SETIAP anggota yang membuka unit itu, bukan cuma browser admin.

## 6. Migrasi 67 Unit Lama

Dikerjakan manual, bukan auto-convert, sesuai keputusanmu untuk hasil maksimal. Prosesnya per unit: baca ulang konten teks polos yang ada sekarang, susun ulang jadi kombinasi blok yang paling pas (heading buat struktur, callout buat catatan penting, kode buat contoh, dst), kerjakan bareng Claude Code untuk efisiensi.

Ini pekerjaan konten besar, dijadwalkan sebagai satu unit kerja tersendiri di 2.1.2/tahap implementasi, terpisah dari pembangunan sistem blok itu sendiri (sistem dan isi adalah dua pekerjaan berbeda, sama seperti pemisahan 2.2 dan 2.3 di v1.0).

## 7. Renderer

Satu komponen (Blade/Livewire) menerima daftar blok terurut milik satu Unit, loop, dan menampilkan partial view sesuai `type` tiap blok. Styling tiap partial mengikuti `docs/design-tokens.md`, dibangun sekali per tipe, dipakai ulang di semua unit. Ini yang membuat seluruh materi otomatis konsisten begitu desain tiap tipe blok sudah final, tanpa perlu sentuh materi satu-satu lagi ke depannya.
