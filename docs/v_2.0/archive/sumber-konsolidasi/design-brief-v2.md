# Brief Sistem Desain WEBI-SPACE — Arah "Hangat, Playful, Profesional"

**Tujuan dokumen:** menerjemahkan arah visual baru ("profesional tapi hangat dan playful") jadi keputusan konkret yang bisa (1) dieksplorasi visual di Claude Design, lalu (2) dieksekusi ke seluruh aplikasi lewat Claude Code. Ini pengembangan dari `docs/design-tokens.md` yang sudah ada, BUKAN membuang filosofinya.

---

## 0. Diagnosis: kenapa tampilan sekarang terasa flat

Design token sekarang benar filosofinya (terang, hangat, melindungi pemula) tapi dieksekusi terlalu konservatif:
- Semua kartu putih, border abu tipis seragam, nyaris tanpa kedalaman.
- Aksen cyan "dijaga sangat ketat" sampai layar terasa kosong warna.
- Shadow "minim" jadi semua elemen menempel datar, tidak ada hierarki.
- Tidak ada elemen dekoratif/ilustratif yang memberi karakter.

Hasilnya: bersih tapi hambar, terasa wireframe. Arah baru memperbaiki ini TANPA jatuh ke ekstrem lain (ramai, kekanakan, tidak kredibel).

## 1. Prinsip keseimbangan (jangkar semua keputusan)

Tiga kata, dan cara menyeimbangkannya:
- **Profesional** = struktur rapi, tipografi tegas, konsisten, tidak norak. Ini fondasi, jangan dikorbankan.
- **Hangat** = warna tidak dingin/klinis, ada aksen warm pendukung, microcopy ramah, banyak ruang napas, sudut lembut.
- **Playful** = detail kecil yang menyenangkan (micro-interaction, ilustrasi ringan, aksen warna ceria, state kosong yang ramah) — playful ada di DETAIL, bukan di struktur. Struktur tetap profesional; keceriaan muncul di sentuhan.

**Aturan emas:** kalau ragu, profesional dulu sebagai fondasi, lalu tambahkan kehangatan lewat warna/ruang, dan keceriaan lewat detail kecil. Jangan pernah playful mengorbankan keterbacaan atau kredibilitas.

## 2. Palet diperkaya (dari 4 jadi sistem yang cukup)

Palet lama terlalu sedikit untuk "hangat playful". Pertahankan yang ada, TAMBAH lapisan pendukung. (Nilai hex final ditentukan saat eksplorasi visual — ini panduan peran, bukan angka mati.)

**Inti (dipertahankan dari token lama):**
- Ink `#1C1515` — teks utama, elemen gelap kecil.
- Muted `#979393` — teks sekunder, border, disabled.
- Accent cyan `#05D9E7` — CTA, progres, signature. TAPI boleh lebih berani dari sebelumnya (tidak lagi "maks 1 per layar" yang kaku — lihat §3).
- Accent-soft `#D1F8FF` — section alternatif, highlight lembut.
- White `#FFFFFF` — background dominan.

**Tambahan yang perlu dieksplorasi (INI yang bikin hangat + playful):**
- **1-2 warna hangat pendukung** (mis. peach/coral lembut, atau kuning hangat) untuk aksen keceriaan — dipakai di ilustrasi, badge, highlight positif, state sukses yang ramah. Ini kunci "hangat" yang sekarang hilang.
- **Surface bertingkat**: bukan cuma putih polos. Perlu 1-2 tingkat off-white/abu sangat muda (mis. `#FAFAFA`, `#F5F5F7`) untuk membedakan background halaman vs kartu vs section — ini yang memberi KEDALAMAN tanpa shadow berat.
- **Warna semantik** (sukses/warning/error) didefinisikan proper, desaturasi ringan biar nyatu, jangan norak.

Eksplorasi vs Eksekusi/Admin tetap beda proporsi (Eksplorasi lebih lapang & hangat, Eksekusi/Admin boleh lebih padat) — pertahankan aturan ini dari token lama.

## 3. Kedalaman & elevasi (perbaikan terbesar dari "flat")

Ini akar masalah flat. Perkaya:
- **Sistem shadow bertingkat**: bukan "minim" seragam, tapi berjenjang — kartu biasa shadow sangat halus (bukan nol), elemen mengambang (dropdown, modal, kartu penting) shadow lebih terasa. Shadow lembut & warm (bukan abu keras), memberi kesan hangat.
- **Kartu punya hierarki**: kartu utama/CTA boleh beda elevasi/warna dari kartu sekunder. Tidak semua kartu diperlakukan sama seperti sekarang.
- **Border tetap halus** tapi dikombinasi dengan surface bertingkat + shadow halus, bukan border sebagai satu-satunya pemisah.

## 4. Tipografi (dipertahankan, dipertegas)

Sora (display), Plus Jakarta Sans (body), JetBrains Mono (data/angka) — SUDAH BAGUS dan khas, pertahankan. Yang diperbaiki:
- **Hierarki ukuran lebih berani**: judul besar benar-benar besar, kontras jelas dengan body. Sekarang semua terasa selevel.
- **Angka statistik** (poin, level) pakai JetBrains Mono besar sebagai elemen visual yang menyenangkan, bukan angka polos.

## 5. Detail playful (di sini keceriaan hidup)

Ini yang sekarang NOL, dan paling bikin beda:
- **Micro-interaction**: hover halus pada kartu/tombol (angkat sedikit, shadow tumbuh), transisi lembut, tombol terasa "hidup" saat diklik. Sudah ada sebagian (sidebar), perluas konsisten.
- **State kosong yang ramah**: alih-alih area kosong, kasih ilustrasi ringan + kalimat suportif (mis. dashboard tanpa aktivitas, chat kosong, belum ada proyek).
- **Ilustrasi/spot art ringan**: elemen dekoratif kecil (bukan stok foto) yang memberi karakter — mis. di header dashboard, empty state, onboarding. Gaya konsisten, geometris/line-art ringan biar tetap profesional.
- **Aksen keceriaan terukur**: badge, chip, highlight positif pakai warna hangat/accent dengan senang hati — TAPI tetap satu bahasa, tidak acak.
- **Signature circuit/jalur** (Peta Kurikulum) dari token lama — pertahankan sebagai identitas, dan gaya visualnya bisa jadi inspirasi elemen dekoratif ringan di tempat lain (garis sirkuit tipis sebagai ornamen, bukan meniru node penuh).

## 6. Konsistensi lintas portal

- **Eksplorasi** (pemula, mudah minder): paling hangat, paling lapang, playful paling terasa (ilustrasi, warna hangat, microcopy ramah). Prioritas: tidak intimidating.
- **Eksekusi** (anggota kerja proyek): profesional-hangat, sedikit lebih padat, playful lebih halus (tetap ada, tidak dihilangkan).
- **Admin**: paling fungsional/padat, tapi TETAP pakai bahasa visual yang sama (bukan tema beda) — hangat lewat warna & ruang, playful minimal tapi ada.

Satu bahasa visual, tiga tingkat intensitas. Bukan tiga desain berbeda.

## 7. Microcopy (dipertahankan & diperkuat)

Nada suportif Eksplorasi dari token lama sudah benar — pertahankan. Perkuat dengan keceriaan di state kosong, sukses, level-up, apresiasi leaderboard (sudah ada, lanjutkan).

---

## 8. Cara pakai dokumen ini (alur kerja)

1. **Eksplorasi visual di Claude Design**: pakai brief ini untuk mengeksplorasi tampilan konkret — palet final (hex), contoh kartu, contoh dashboard, ilustrasi, shadow. Aye MELIHAT dan memilih. Hasilkan beberapa arah, pilih yang paling terasa "hangat playful profesional".
2. **Kunci jadi design token baru**: setelah arah dipilih, terjemahkan jadi `docs/design-tokens-v2.md` (nilai konkret: hex, shadow, radius, spacing scale, dst).
3. **Buktikan di SATU halaman** (dashboard Eksplorasi) lewat Claude Code sebelum menyebar.
4. **Terapkan bertahap** ke seluruh aplikasi, halaman per halaman, setelah template terbukti.

**Catatan penting:** dokumen ini adalah ARAH, bukan nilai final. Keputusan visual konkret (hex pasti, ukuran shadow, bentuk ilustrasi) ditentukan saat eksplorasi visual di mana Aye bisa MELIHAT hasilnya — karena "hangat playful profesional" hanya bisa divalidasi dengan mata, bukan dari deskripsi teks.
