# Konten Praktik: Challenge + Track Map — WEBI-SPACE v2.0 (Bagian 11)

Status: FINAL (blueprint disetujui Aye di bagian 10). Dokumen ini sumber teks resmi untuk 15 Challenge beserta Track Map (ChallengeStep) masing-masing. `points_reward`: low = 15, mid = 35, high = 60. `status` = `published` untuk semua.

Prinsip Track Map (dari `Rancangan_Modul_Praktik_v2.md`): instruksi konkret per langkah, TIDAK ada starter code, cukup arahan yang jelas apa yang harus dipikirkan/dikerjakan di tiap tahap.

---

## TIER LOW

### L1. Simulator Antrian Fotokopi Kampus
`level: low, points_reward: 15, status: published`
**Deskripsi:** Bangun simulator antrian untuk kios fotokopi kampus. Pengguna memasukkan jumlah halaman yang mau dicetak dan memilih kecepatan mesin (lambat/sedang/cepat), simulator menghitung estimasi waktu tunggu dan menampilkan animasi sederhana posisi antrian yang bergerak maju.

Track Map:
1. **Rancang Variabel Input** — Tentukan input apa saja (jumlah halaman, kecepatan mesin) dan bagaimana masing-masing memengaruhi waktu tunggu. Tulis rumus kasarnya di atas kertas dulu sebelum ngoding.
2. **Bangun Logika Perhitungan Waktu** — Tulis fungsi yang menghitung estimasi waktu berdasar variabel di atas, uji lewat console dulu sebelum disambungkan ke tampilan.
3. **Bangun Tampilan dan Animasi Antrian** — Tampilkan hasil perhitungan dan animasi sederhana posisi antrian yang bergerak (boleh CSS transition, tidak perlu library animasi berat).
4. **Uji dengan Beberapa Skenario** — Coba kombinasi jumlah halaman dan kecepatan berbeda, termasuk nilai ekstrem (0 halaman, jumlah sangat besar), pastikan hasil masuk akal. *(callout tip: nilai ekstrem sering menyingkap bug yang tidak kelihatan di kasus normal)*
5. **Kirim** — Submission berupa link repository atau kode yang bisa dijalankan.

### L2. Konversi Takaran Masak "Kira-Kira" ke Ukuran Presisi
`level: low, points_reward: 15, status: published`
**Deskripsi:** Bangun tool yang mengonversi takaran masakan rumahan ("segenggam", "secukupnya", "sejumput") ke estimasi gram/ml berdasar tabel referensi bahan umum, lengkap catatan bahwa hasilnya perkiraan.

Track Map:
1. **Riset dan Susun Tabel Referensi** — Kumpulkan minimal 8-10 takaran umum dan estimasi gramnya per jenis bahan (segenggam beras berbeda beratnya dari segenggam kacang, catat perbedaan ini).
2. **Bangun Struktur Data Lookup** — Simpan tabel referensi sebagai objek/array yang mudah dicari berdasar nama bahan dan jenis takaran.
3. **Bangun Form dan Logika Konversi** — Pengguna pilih bahan dan takaran, sistem menampilkan estimasi gram/ml.
4. **Tambahkan Disclaimer yang Jelas** — Tampilkan secara mencolok bahwa ini perkiraan, bukan ukuran presisi laboratorium. *(callout warning: penting secara etis, jangan sampai tool ini terlihat seperti menyampaikan fakta ilmiah pasti)*
5. **Kirim**.

### L3. Generator Julukan Alien Berdasarkan Nama dan Tanggal Lahir
`level: low, points_reward: 15, status: published`
**Deskripsi:** Tool yang menghasilkan "nama alien" unik dari kombinasi nama dan tanggal lahir pengguna, memakai transformasi deterministik (input sama selalu menghasilkan output sama, bukan acak murni).

Track Map:
1. **Rancang Algoritma Deterministik** — Tentukan cara mengubah kombinasi nama+tanggal lahir jadi nilai konsisten (misalnya jumlah kode karakter dimodulo ke panjang daftar kata).
2. **Susun Daftar Kata/Pola Julukan** — Siapkan daftar suku kata atau pola alien yang akan dikombinasikan hasil algoritma di atas.
3. **Implementasikan dan Uji Konsistensi** — Pastikan input yang sama SELALU menghasilkan output yang sama. *(callout tip: ini beda dari sekadar random, uji dengan memasukkan nama dan tanggal yang sama dua kali berturut-turut, hasilnya harus identik)*
4. **Kirim**.

### L4. Pelacak Jadwal Piket Kos dengan Rotasi Otomatis
`level: low, points_reward: 15, status: published`
**Deskripsi:** Tool yang menerima daftar nama penghuni kos dan daftar tugas piket, lalu men-generate jadwal rotasi mingguan otomatis supaya semua orang kebagian semua tugas secara adil.

Track Map:
1. **Rancang Struktur Data Penghuni dan Tugas** — Simpan daftar nama dan daftar tugas sebagai dua kumpulan data terpisah.
2. **Bangun Logika Rotasi** — Tulis fungsi yang menggeser pasangan orang-tugas tiap minggu supaya tidak ada yang dapat tugas sama berturut-turut sebelum semua kebagian merata.
3. **Tampilkan Jadwal Mingguan** — Render jadwal dalam bentuk tabel per minggu.
4. **Uji dengan Jumlah Penghuni Ganjil dan Genap** — Pastikan rotasi tetap adil di kedua kasus. *(callout tip: kasus jumlah ganjil sering jadi sumber bug rotasi yang tidak merata)*
5. **Kirim**.

### L5. Ide Bebas (Rancangan)
`level: low, points_reward: 15, status: published`
**Deskripsi:** Anggota memilih sendiri ide aplikasi web kecil apa pun sesuai minatnya, bebas jenis dan temanya. Output berupa dokumen rancangan, bukan aplikasi jadi.

Track Map (generik, berlaku untuk ide apa pun):
1. **Tentukan Ide dan Pengguna** — Jelaskan ide bebasmu, siapa yang akan memakainya, dan masalah apa yang diselesaikan.
2. **Petakan Fitur Inti** — Daftar fitur yang wajib ada (inti) dan fitur tambahan (opsional kalau ada waktu lebih).
3. **Susun Alur Penggunaan** — Deskripsikan tahap demi tahap bagaimana calon pengguna memakai aplikasi ini (boleh berbentuk teks bertahap, tidak wajib gambar/wireframe).
4. **Kirim Rancangan** — Submission berupa dokumen teks berisi ketiga poin di atas.

---

## TIER MID

### M1. Manajemen Peminjaman Alat Antar Anggota Komunitas
`level: mid, points_reward: 35, status: published`
**Deskripsi:** Aplikasi pencatat peminjaman alat/barang bersama dalam sebuah komunitas (alat prakarya, kamera, proyektor, dll), mencatat siapa meminjam apa, kapan harus dikembalikan, dan status keterlambatan otomatis.

Track Map:
1. **Rancang Skema Data** — Tabel alat, peminjam, tanggal pinjam/kembali, status.
2. **Bangun CRUD Alat dan Peminjam** — Form dasar tambah/lihat/ubah/hapus untuk data alat dan peminjam.
3. **Bangun Logika Status Otomatis** — Tandai otomatis "terlambat" begitu lewat tanggal kembali tanpa dikembalikan.
4. **Uji Alur Penuh** — Pinjam → status berjalan otomatis → tandai kembali → cek histori peminjaman.
5. **Kirim** — Link repo/demo, jelaskan alur CRUD yang sudah berjalan.

### M2. Platform "Jastip" Antar Penghuni Kos
`level: mid, points_reward: 35, status: published`
**Deskripsi:** Papan permintaan titip-beli antar penghuni kos/kontrakan berdekatan, satu penghuni memasang permintaan, penghuni lain bisa mengklaim untuk memenuhinya, status berubah dari terbuka ke diklaim ke selesai.

Track Map:
1. **Rancang Alur Status** — Tentukan status permintaan (terbuka/diklaim/selesai) dan siapa yang boleh mengubah status apa.
2. **Bangun Form Pasang Permintaan** — Penghuni bisa memasang permintaan titip beli baru.
3. **Bangun Mekanisme Klaim** — Penghuni lain bisa klaim permintaan, cegah dua orang mengklaim permintaan yang sama. *(callout tip: ini kasus race condition sederhana, pikirkan pencegahannya meski di level dasar, misalnya cek ulang status sebelum simpan)*
4. **Uji Alur Penuh dari Sudut Pandang Lebih dari Satu Pengguna** — Pasang → klaim → selesai.
5. **Kirim**.

### M3. Sistem Pencatat Iuran Komunitas dengan Riwayat Transparan
`level: mid, points_reward: 35, status: published`
**Deskripsi:** Aplikasi pencatatan iuran rutin komunitas/kelas, menampilkan riwayat siapa sudah bayar bulan apa, total kas terkumpul, dan daftar anggota yang menunggak.

Track Map:
1. **Rancang Skema Data** — Anggota, periode iuran, status bayar per periode.
2. **Bangun Input Pencatatan Pembayaran** — Form mencatat siapa membayar iuran periode apa.
3. **Bangun Ringkasan Otomatis** — Total kas terkumpul dan daftar penunggak per periode, dihitung otomatis dari data pembayaran.
4. **Uji dengan Data Beberapa Bulan** — Pastikan ringkasan tetap akurat kalau ada anggota baru masuk di tengah jalan.
5. **Kirim**.

### M4. Papan Skor Turnamen Kelas/Angkatan
`level: mid, points_reward: 35, status: published`
**Deskripsi:** Aplikasi bracket turnamen sederhana untuk kompetisi kasual antar kelas/angkatan (bebas jenis kompetisinya), men-generate bagan pertandingan, mencatat skor, otomatis memajukan pemenang.

Track Map:
1. **Rancang Struktur Bracket** — Tentukan cara menyimpan pertandingan dan hubungan antar babak.
2. **Bangun Generator Bagan dari Daftar Peserta** — Otomatis buat pasangan babak pertama, tangani jumlah peserta ganjil (mekanisme "bye"/lewat babak).
3. **Bangun Pencatatan Skor dan Kemajuan Otomatis** — Input skor, pemenang otomatis maju ke bagan berikutnya.
4. **Uji Turnamen Penuh** — Jalankan skenario lengkap dari babak pertama sampai juara.
5. **Kirim**.

### M5. Ide Bebas (Aplikasi Sederhana)
`level: mid, points_reward: 35, status: published`
**Deskripsi:** Anggota memilih sendiri ide aplikasi berfungsi, bebas jenis dan temanya. Output berupa aplikasi sederhana yang benar-benar berjalan, minimal satu alur CRUD lengkap.

Track Map (generik):
1. **Tentukan Ide dan Fitur Inti** — Pastikan ada minimal satu alur CRUD penuh yang jadi fokus utama.
2. **Rancang Struktur Data** — Susun struktur data sebelum mulai menulis kode.
3. **Implementasikan dan Uji Sendiri** — Bangun fitur inti, uji alur utamanya berjalan benar.
4. **Kirim** — Link repo/demo (lokal boleh, tidak wajib publik di tier ini), jelaskan fitur inti apa yang sudah berjalan.

---

## TIER HIGH

### H1. Marketplace Barang Preloved Kos dengan Payment Gateway
`level: high, points_reward: 60, status: published`
**Deskripsi:** Marketplace kecil jual-beli barang bekas layak pakai antar penghuni kos/kampus, terintegrasi payment gateway sandbox untuk simulasi pembayaran, termasuk penanganan webhook status transaksi.

Track Map:
1. **Rancang Alur Transaksi** — Dari pilih barang sampai status pembayaran selesai, termasuk state barang (tersedia/dipesan/terjual).
2. **Integrasikan Payment Gateway Sandbox** — Daftar akun sandbox (misalnya Midtrans Sandbox), pelajari dokumentasinya, bangun alur redirect/komponen pembayaran.
3. **Tangani Webhook Status Transaksi** — Pastikan status barang berubah otomatis mengikuti notifikasi webhook dari gateway. *(callout warning: bagian ini paling sering jadi celah bug, uji skenario pembayaran gagal juga, bukan cuma yang berhasil)*
4. **Uji Transaksi End-to-End di Sandbox** — Dari checkout sampai status akhir tercermin benar di aplikasi.
5. **Deploy dan Kirim** — Link aplikasi live dan repository.

### H2. Dashboard Prediksi Waktu Terbaik Mencuci-Menjemur Berdasar Cuaca
`level: high, points_reward: 60, status: published`
**Deskripsi:** Dashboard yang mengintegrasikan API cuaca publik untuk merekomendasikan jam terbaik mencuci dan menjemur dalam 24-48 jam ke depan berdasar prediksi curah hujan dan kelembapan.

Track Map:
1. **Pelajari dan Uji Coba API Cuaca** — Daftar API key (misalnya OpenWeatherMap), pelajari format response, coba panggil manual dulu sebelum diintegrasikan ke aplikasi.
2. **Rancang Logika Rekomendasi** — Tentukan aturan mengubah data cuaca mentah jadi rekomendasi jam terbaik, bukan cuma menampilkan angka mentah.
3. **Bangun Dashboard** — Tampilkan rekomendasi dengan jelas, sertakan data pendukung (probabilitas hujan, kelembapan).
4. **Tangani Kegagalan API** — Pastikan aplikasi tidak ikut gagal total kalau API cuaca sedang tidak merespons (kaitkan ke prinsip Modul 9 unit 9.2).
5. **Deploy dan Kirim**.

### H3. Sistem Booking Ruang Diskusi dengan Notifikasi WhatsApp Otomatis
`level: high, points_reward: 60, status: published`
**Deskripsi:** Sistem pemesanan ruang diskusi/meeting point kampus real-time, mencegah bentrok jadwal, terintegrasi API pengirim pesan untuk notifikasi konfirmasi otomatis.

Track Map:
1. **Rancang Skema Data Booking** — Ruang, slot waktu, pemesan, status.
2. **Bangun Logika Pencegahan Bentrok** — Pastikan dua pemesanan tidak bisa memakai ruang dan slot waktu yang sama.
3. **Integrasikan API Pengirim Pesan** — Daftar akun sandbox/trial (misalnya Fonnte atau Twilio), kirim notifikasi otomatis begitu booking dikonfirmasi.
4. **Uji Skenario Bentrok dan Sukses** — Coba pesan slot yang sudah terisi (harus ditolak jelas), coba pesan slot kosong (notifikasi harus terkirim).
5. **Deploy dan Kirim**.

### H4. Agregator Promo Makanan Sekitar Kampus
`level: high, points_reward: 60, status: published`
**Deskripsi:** Aplikasi yang mengumpulkan dan menampilkan promo makanan dari beberapa sumber API/data terbuka sekitar area kampus, mengurutkan berdasar nilai promo atau jarak.

Track Map:
1. **Identifikasi Sumber Data** — Pilih minimal dua sumber API/data terbuka yang akan digabungkan.
2. **Rancang Format Data Gabungan** — Tentukan struktur data seragam untuk menampung hasil dari sumber yang formatnya berbeda-beda.
3. **Bangun Logika Pengambilan dan Penggabungan** — Ambil data dari tiap sumber, gabungkan dan urutkan.
4. **Tangani Sumber yang Gagal Merespons** — Pastikan satu sumber error tidak membuat seluruh halaman gagal tampil.
5. **Deploy dan Kirim**.

### H5. Ide Bebas (Web App Kompleks + Integrasi)
`level: high, points_reward: 60, status: published`
**Deskripsi:** Anggota memilih sendiri ide web app kompleks, bebas jenis dan temanya, dengan satu syarat wajib: melibatkan minimal satu integrasi pihak ketiga nyata (API eksternal apa pun, atau payment gateway).

Track Map (generik):
1. **Pilih Ide dan Integrasi Pihak Ketiga Wajib** — Tentukan API/gateway apa yang akan diintegrasikan.
2. **Rancang Alur Data dengan Integrasi Tersebut** — Petakan bagaimana data mengalir antara aplikasimu dan layanan pihak ketiga itu.
3. **Bangun dan Uji Integrasi Nyata** — Pakai mode sandbox/uji kalau tersedia, pastikan integrasi benar-benar berjalan bukan cuma di rencana.
4. **Deploy dan Laporkan** — Link live dan repository, jelaskan integrasi apa yang dipakai dan bagaimana cara kerjanya.

---

## Catatan
- Total: 15 Challenge, 15 x rata-rata 4-5 ChallengeStep = sekitar 68 ChallengeStep.
- Tiap ChallengeStep di atas akan diisi minimal 1 content_block bertipe `text` berisi deskripsi di atas, dengan `callout` tambahan di titik yang sudah ditandai secara eksplisit.
- Tidak ada starter code di mana pun, sesuai keputusan final Rancangan Modul Praktik v2.
