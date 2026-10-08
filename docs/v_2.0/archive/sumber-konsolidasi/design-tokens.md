# Design Token — WEBI-SPACE

**Tujuan dokumen ini:** acuan visual tunggal supaya semua UI yang digenerate Claude Code konsisten dan tidak generic. Baca ini SEBELUM bikin halaman/komponen apa pun. Kalau ada keputusan visual yang tidak tercakup di sini, tanya dulu, jangan asumsi sendiri.

---

## 1. Filosofi Dasar (baca dulu sebelum apa pun)

WEBI-SPACE dipakai anggota yang mayoritas pemula total dan sebagian mudah minder (temuan riset awal proyek ini). Karena itu, arah visualnya **terang dan hangat, bukan gelap dan dingin**, walau warna brand RIT condong ke tema "tech". Latar dominan putih/pucat, bukan hitam. Hitam dan cyan dipakai sebagai aksen yang dijaga ketat, bukan disebar rata ke seluruh halaman.

**Larangan eksplisit, jangan generate salah satu dari pola ini:**
- Background nyaris hitam dengan satu warna aksen neon menyala di mana-mana (dark mode SaaS generic).
- Layout broadsheet/koran dengan garis tipis di mana-mana dan sudut kotak tajam tanpa radius sama sekali.
- Card dashboard generic dengan gradient dan shadow berlebihan.
- Font default seperti Inter atau Poppins untuk heading (sudah dipakai di hampir semua produk AI-generated, tidak beda dari yang lain).

---

## 2. Warna

| Hex | Nama Peran | Kapan Dipakai |
|---|---|---|
| `#1C1515` | Teks utama / elemen gelap | Teks judul dan body utama. Boleh jadi background elemen KECIL yang sengaja mau kontras tinggi (contoh: node "selesai" di jalur kurikulum). **Jangan** dipakai sebagai background halaman/section luas. |
| `#979393` | Teks sekunder, border, elemen non-aktif | Teks pendukung, placeholder, border card, elemen locked/disabled. |
| `#05D9E7` | Aksen utama | CTA/tombol primer, progres aktif, elemen signature (jalur kurikulum), highlight state aktif. **Batasi pemakaian**, maksimal satu elemen aksen mencolok per layar. Jangan dipakai buat background luas atau teks body panjang (kontras buruk buat dibaca lama). |
| `#D1F8FF` | Section alternatif | Background section yang perlu dibedakan dari section lain (contoh: card terpilih, banner info ringan). Dipakai sesekali, bukan dominan. |
| `#FFFFFF` | Background dominan | Background utama hampir semua halaman. |

**Aturan tambahan:**
- Modul Eksplorasi (anggota pemula): pakai proporsi putih paling tinggi, cyan cuma di elemen progres/CTA, kesan harus terasa aman dan tidak menghakimi.
- Modul Eksekusi dan Admin Panel (user lebih terbiasa tool kerja): boleh sedikit lebih padat informasi dan lebih banyak elemen `#979393` untuk struktur (garis tabel, divider), tapi tetap latar putih dominan.
- Warna status (error, warning, success) TIDAK didefinisikan di sini karena tidak ada di palet brand. Pakai warna semantik standar yang wajar (merah untuk error, hijau untuk sukses, kuning/amber untuk warning), tapi tetap desaturasi ringan supaya tidak bentrok sama palet utama.

---

## 3. Tipografi

| Font | Peran | Pemakaian |
|---|---|---|
| **Sora** (weight 600-700) | Display/Heading | Semua judul halaman, judul card besar, angka besar (level, poin di dashboard utama). |
| **Plus Jakarta Sans** (weight 400-500) | Body | Semua teks paragraf, label form, deskripsi, isi konten kurikulum. |
| **JetBrains Mono** (weight 400-500) | Utility/Data | Angka statistik kecil (poin, level, checkpoint ID), kode/snippet, label teknis, timestamp. |

**Load via Google Fonts:**
```html
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
```

**Aturan:** jangan pakai Sora untuk body text panjang (cuma untuk heading pendek). Jangan pakai Plus Jakarta Sans untuk angka statistik (pakai JetBrains Mono, ini yang bikin identitas "developer" kerasa otentik).

---

## 4. Signature Element: Jalur Kurikulum

Halaman Peta Kurikulum (modul Eksplorasi) memvisualisasikan 10 modul sebagai **jalur node yang saling terhubung**, terinspirasi konsep circuit/papan sirkuit (grounded ke tema ekosistem software development), bukan list vertikal biasa.

**Tiga state node, wajib beda visual:**
- **Selesai:** node bulat solid `#1C1515`, ikon centang warna `#05D9E7` di tengah.
- **Aktif (sedang dikerjakan):** node bulat solid `#05D9E7`, dengan ring/border tebal warna `#D1F8FF`, nomor modul di tengah warna `#1C1515`.
- **Terkunci:** node bulat kosong (background putih/surface), border `#979393`, ikon gembok warna `#979393`.

**Garis penghubung antar node:** warna `#05D9E7` dari modul pertama sampai modul aktif terakhir, warna `#979393` (atau putus-putus) untuk sisa jalur yang belum tercapai. Garis berhenti nyala PERSIS di titik progres user, ini yang bikin elemen ini berfungsi sebagai indikator progres sekaligus dekorasi.

Elemen serupa (garis progres yang "menyala" sampai titik tertentu) bisa dipakai ulang di tempat lain yang relevan (progress bar checkpoint per modul), tapi jalur node penuh ini KHUSUS untuk halaman Peta Kurikulum saja, jangan diulang di halaman lain supaya tetap terasa sebagai elemen signature, bukan pola generic yang dipakai di mana-mana.

---

## 5. Layout dan Komponen

- **Border radius:** sedang, tidak kotak tajam (radius 0) dan tidak bulat berlebihan (pill-shape di semua elemen). Gunakan radius sekitar 8px untuk button/input, 12px untuk card.
- **Border:** tipis (1px), warna `#979393` dengan opacity rendah atau langsung warna pucat turunan abu-abu itu. Hindari border tebal/mencolok kecuali untuk elemen aktif/selected.
- **Shadow:** minim, cuma dipakai untuk elemen yang benar-benar perlu terasa "mengambang" (modal, dropdown). Card biasa cukup pakai border, tidak perlu shadow.
- **Spacing:** lapang, jangan padat. Target audiens pemula butuh visual yang tidak terasa "penuh sesak".
- **Ikon:** gunakan set ikon outline yang konsisten (bukan campur berbagai gaya ikon). Kalau pakai library, pilih satu dan konsisten di seluruh aplikasi.

---

## 6. Nada Penulisan UI (microcopy)

- Halaman Eksplorasi: nada suportif, tidak menghakimi. Contoh: bukan "Kamu gagal kuis", tapi "Coba lagi, kamu hampir sampai" atau sejenisnya. Sudah konsisten dengan nada yang dipakai di konten kurikulum (1.3), UI harus melanjutkan nada yang sama, bukan berubah jadi kaku di level interface.
- Halaman Eksekusi dan Admin: boleh lebih langsung/fungsional, karena penggunanya sudah lebih terbiasa dengan tool kerja.
- Hindari bahasa teknis tanpa penjelasan di sisi Eksplorasi (contoh: jangan langsung pakai istilah "commit", "deploy" tanpa konteks buat user yang levelnya belum sampai situ).

---

## 7. Referensi Cepat untuk Claude Code

Sebelum generate komponen baru, cek: apakah ini masuk kategori Eksplorasi (proporsi putih tinggi, nada suportif) atau Eksekusi/Admin (boleh lebih padat)? Apakah elemen ini butuh warna aksen cyan? Kalau ya, pastikan belum ada elemen aksen cyan lain yang lebih dominan di layar yang sama. Apakah ini halaman Peta Kurikulum? Kalau ya, pakai signature element jalur node, jangan bikin versi sederhana list biasa.