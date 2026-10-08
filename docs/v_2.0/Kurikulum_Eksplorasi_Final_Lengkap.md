# Kurikulum Eksplorasi WEBI-SPACE

**Status:** Dokumen final terpadu, lengkap Modul 1 sampai Modul 10. Setiap unit berisi isi materi dan output penugasan yang sudah konkret, siap dipakai sebagai dataset dan acuan implementasi frontend LMS.

**Filosofi dasar:** Kurikulum ini menjawab satu pertanyaan, bagaimana anggota Eksplorasi menjadi lebih dari sekadar vibe coder, punya pemahaman konsep dan judgment yang tidak bisa didapat cuma dari prompting AI. Setiap modul dibangun dengan pendekatan konkret, bukan hafalan teori statis, dan diakhiri dengan output nyata yang bisa dipakai atau dipamerkan.

**Target audiens:** Anggota yang sudah pernah mencoba tools AI atau vibe coding, sampai yang benar-benar baru. Keduanya butuh fondasi konsep yang sama.

---

# BAB 1. RINGKASAN KONSEP

| No | Modul | Esensi Konsep | Uraian Unit | Output |
|---|---|---|---|---|
| 1 | Fondasi Software Development dan Web | Membangun kerangka berpikir dasar tentang pengembangan software secara umum, menyempit ke web development, lalu masuk ke mekanisme teknis di baliknya. | 1.1 Apa Itu Software Development, dan Bagaimana Web Development Menjadi Bagiannya. 1.2 Metode Pengembangan Software: Konsep SDLC dan Penerapannya di Waterfall, Agile, dan RAD. 1.3 Arsitektur Client-Server dan Mekanisme Request-Response. 1.4 Evaluasi Kualitas Kode: Membaca Ciri Aplikasi yang Rapuh. | Kuis pilihan ganda dan esai singkat. |
| 2 | Bahasa Pemrograman, Framework, dan Tech Stack | Menjelaskan hubungan konseptual bahasa pemrograman dan framework sebagai satu rangkaian terpadu, lalu masuk ke detail spesifik tiap kategori. | 2.1 Pengantar Konseptual: Bahasa Pemrograman, Framework, dan Relasinya. 2.2 Ragam Bahasa Pemrograman Populer dan Karakteristiknya. 2.3 Ragam Framework Populer dan Fungsinya per Kategori. 2.4 Arsitektur Tech Stack: Bagaimana Komponen Saling Melengkapi. 2.5 Kurasi Sumber Belajar Kredibel per Stack. | Dokumen rancangan pemilihan stack. |
| 3 | Lanskap Industri dan Arah Pergerakan Teknologi | Melatih kemampuan membaca kenapa sebuah teknologi naik atau turun popularitasnya. | 3.1 Pola Evolusi Tech Stack. 3.2 Metode Membaca Sinyal Industri. | Esai riset singkat. |
| 4 | Peran-Peran dalam Tim Software | Memahami peran di luar developer, ketergantungan antar peran, dan kontribusi manusia yang tidak tergantikan AI. | 4.1 Struktur Peran dalam Tim Software. 4.2 Mekanisme Ketergantungan Antar Peran dalam Siklus Proyek. 4.3 Studi Kasus: Dampak Kekosongan Satu Peran terhadap Proyek. | Studi kasus tertulis. |
| 5 | Version Control dengan Git | Git sebagai cara berpikir kolaboratif, dijelaskan lewat tutorial command yang terstruktur dan lengkap. | 5.1 Mekanisme Version Control. 5.2 Command Dasar Git: Init, Add, Commit, Log. 5.3 Command Percabangan: Branch, Checkout, Merge, Konflik. 5.4 Command Kolaborasi Jarak Jauh: Clone, Push, Pull, Fetch. 5.5 Praktik Menulis Histori yang Bermakna. | Proyek dokumentasi repository. |
| 6 | Arsitektur dan Prinsip Frontend | Arsitektur informasi, sistem desain, visualisasi data, accessibility, dan peran frontend dalam tim, dijelaskan konkret dan teknis. | 6.1 Arsitektur Informasi: Pelabelan dan Pengkategorian Konten. 6.2 Sistem Desain: Design Token dan Design Pattern. 6.3 Ragam Visualisasi Data pada Web. 6.4 Prinsip Accessibility dalam Frontend. 6.5 Mekanisme Peran Frontend dalam Tim Proyek. | Audit kode. |
| 7 | Arsitektur dan Prinsip Backend | Arsitektur sistem, prinsip desain data, dan peran backend dalam tim, dijelaskan konkret dan teknis. | 7.1 Arsitektur Backend: Alur Request sampai Data Tersimpan. 7.2 Prinsip Normalisasi dan Perancangan Skema Data. 7.3 Arsitektur API: Kontrak Frontend dan Backend. 7.4 Mekanisme Peran Backend dalam Tim Proyek. | Dokumen rancangan sistem. |
| 8 | Keamanan Aplikasi Web | Setiap ancaman dijelaskan dengan penyebab teknis dan solusi strategis, cakupan selengkap mungkin. | 8.1 Ancaman Injeksi: SQL Injection dan Command Injection. 8.2 Ancaman Sisi Klien: XSS dan CSRF. 8.3 Kelemahan Autentikasi dan Manajemen Sesi. 8.4 Kesalahan Konfigurasi dan Kebocoran Data Sensitif. 8.5 Studi Kasus Kebocoran Data Nyata. 8.6 Prinsip Berpikir Defensif dalam Membangun Aplikasi. | Audit keamanan. |
| 9 | Topik Lanjutan Pengembangan Web | Konsep yang membedakan aplikasi selesai dengan aplikasi siap pakai secara nyata. | 9.1 Arsitektur Progressive Web App. 9.2 Mekanisme Integrasi API Pihak Ketiga. 9.3 Konsep Dasar Payment Gateway. 9.4 Strategi dan Metode Deployment Modern. | Dokumen rancangan. |
| 10 | Proyek Akhir dan Portofolio | Sintesis seluruh modul menjadi satu karya nyata yang dipublikasikan, disertai refleksi personal. | 10.1 Perencanaan Proyek Akhir. 10.2 Eksekusi dan Deployment Proyek. 10.3 Penyusunan Portofolio. 10.4 Refleksi Personal: Kontribusi yang Tidak Tergantikan oleh AI. | Proyek aplikasi, dokumen portofolio, esai refleksi. |

---

# BAB 2. IMPLEMENTASI

## 2.1. Modul 1: Fondasi Software Development dan Web

### 2.1.1. Apa Itu Software Development, dan Bagaimana Web Development Menjadi Bagiannya

**Isi Materi**

Software development adalah proses merancang, membangun, menguji, dan memelihara sebuah perangkat lunak agar bisa menyelesaikan masalah atau kebutuhan tertentu. Cakupannya luas, mulai dari aplikasi mobile, sistem operasi, program desktop, sampai perangkat lunak yang tertanam di mesin industri. Semua bentuk software itu punya satu kesamaan, yaitu ada proses berpikir dan bekerja yang mengubah kebutuhan menjadi sesuatu yang bisa dijalankan komputer.

Web development adalah salah satu cabang dari software development yang secara spesifik berfokus pada perangkat lunak yang berjalan dan diakses lewat web browser. Bedanya dengan cabang lain terletak pada tiga hal. Pertama, cara distribusinya, sebuah aplikasi web tidak perlu diinstal, cukup diakses lewat alamat tertentu. Kedua, lingkungan eksekusinya, kode web dijalankan di dua sisi berbeda, yaitu di perangkat milik pengguna (browser) dan di server milik penyedia layanan. Ketiga, sifatnya yang selalu terhubung ke jaringan, berbeda dari aplikasi desktop yang bisa berjalan sepenuhnya offline.

Kenapa perbedaan ini penting dipahami sejak awal. Ketika seseorang memakai tools AI untuk membuat aplikasi web hanya dengan mengetik prompt, tools tersebut sebenarnya sedang menyusun banyak keputusan teknis sekaligus, keputusan tentang bagaimana kode akan didistribusikan, di mana logika akan dijalankan, dan bagaimana koneksi jaringan akan ditangani. Kalau penggunanya tidak paham bahwa web development punya karakteristik berbeda dari software development pada umumnya, dia tidak akan tahu harus mengevaluasi apa dari hasil yang diberikan AI tersebut. Memahami posisi web development sebagai cabang spesifik dari software development adalah langkah pertama untuk bisa menilai, bukan sekadar menerima, hasil kerja siapa pun atau apa pun yang membangun aplikasi.

**Output**

Kuis pilihan ganda, 5 soal, jawaban benar ditandai di kunci jawaban.

1. Yang membedakan aplikasi web dari aplikasi desktop pada umumnya adalah:
   A. Aplikasi web selalu gratis, aplikasi desktop selalu berbayar
   B. Aplikasi web perlu diinstal terlebih dulu, aplikasi desktop tidak
   C. Aplikasi web dijalankan di dua sisi (browser dan server) serta butuh koneksi jaringan, aplikasi desktop bisa berjalan penuh secara offline
   D. Aplikasi web tidak punya tampilan antarmuka
   **Kunci: C**

2. Software development adalah:
   A. Cabang khusus dari web development yang berfokus pada database
   B. Proses merancang, membangun, menguji, dan memelihara perangkat lunak untuk menyelesaikan kebutuhan tertentu
   C. Istilah lain untuk pemrograman bahasa JavaScript
   D. Proses yang hanya berlaku untuk aplikasi mobile
   **Kunci: B**

3. Manakah pernyataan yang tepat tentang hubungan software development dan web development?
   A. Keduanya adalah bidang yang sama sekali terpisah dan tidak berkaitan
   B. Web development adalah induk dari software development
   C. Web development adalah salah satu cabang spesifik dari software development
   D. Software development hanya berlaku untuk sistem operasi
   **Kunci: C**

4. Sebuah program pengolah data gaji karyawan diinstal di komputer kantor dan tetap bisa dipakai meski internet mati. Program ini tergolong:
   A. Aplikasi web, karena mengolah data
   B. Aplikasi desktop, karena tidak butuh distribusi lewat alamat dan tidak wajib terhubung jaringan
   C. Aplikasi web, karena semua program modern adalah aplikasi web
   D. Tidak tergolong software sama sekali
   **Kunci: B**

5. Kenapa penting memahami bahwa web development adalah cabang spesifik, bukan sekadar sinonim dari software development?
   A. Supaya bisa mengklaim diri sebagai ahli IT secara umum
   B. Supaya tahu keputusan teknis apa (distribusi, lingkungan eksekusi, ketergantungan jaringan) yang perlu dievaluasi saat menilai hasil kerja, termasuk hasil AI-generate
   C. Karena web development akan segera digantikan sepenuhnya oleh AI
   D. Karena tidak ada bedanya, ini hanya soal istilah
   **Kunci: B**

Soal esai singkat tambahan (input teks bebas): "Sebuah aplikasi pencatat pengeluaran pribadi bisa diakses lewat browser di alamat catatan-uangku.com tanpa instalasi apapun, tapi juga tersedia versi yang bisa diunduh dan dipasang di laptop. Menurutmu, versi mana yang tergolong aplikasi web, dan versi mana yang tergolong aplikasi desktop? Jelaskan alasanmu berdasarkan tiga pembeda yang sudah dipelajari (distribusi, lingkungan eksekusi, ketergantungan jaringan)."

---

### 2.1.2. Metode Pengembangan Software: Konsep SDLC dan Penerapannya di Waterfall, Agile, dan RAD

**Isi Materi**

Software Development Life Cycle, atau SDLC, adalah konsep tahapan yang dilalui dalam membangun sebuah perangkat lunak, mulai dari perencanaan sampai perangkat lunak itu dipelihara setelah dipakai. Komponen fundamentalnya ada lima, yaitu perencanaan (menentukan apa yang perlu dibangun dan kenapa), analisis kebutuhan (menggali detail kebutuhan dari pengguna atau pemilik proyek), desain (merancang bagaimana sistem akan bekerja sebelum ditulis kodenya), implementasi (menulis kode sungguhan), dan pemeliharaan (memperbaiki serta mengembangkan sistem setelah dipakai).

SDLC sendiri adalah konsep, bukan cara kerja yang bisa langsung dipraktikkan begitu saja. Cara kerja nyata yang jadi penerapan konsep ini disebut metode pengembangan, dan tiap metode punya cara berbeda dalam menyusun kelima komponen SDLC tadi.

Waterfall menyusun kelima komponen itu secara berurutan dan linear, satu tahap harus selesai penuh sebelum tahap berikutnya dimulai. Metode ini cocok untuk proyek dengan kebutuhan yang sudah sangat jelas sejak awal dan jarang berubah, tapi berisiko besar kalau ternyata ada kesalahan pemahaman kebutuhan yang baru ketahuan di tahap akhir.

Agile menyusun kelima komponen itu secara berulang dalam siklus-siklus pendek yang disebut sprint, biasanya satu sampai empat minggu. Setiap sprint menghasilkan bagian kecil dari sistem yang bisa langsung dievaluasi, sehingga kesalahan pemahaman kebutuhan bisa ketahuan lebih cepat. Metode ini cocok untuk proyek yang kebutuhannya masih bisa berubah seiring proses berjalan.

Rapid Application Development, atau RAD, menekankan pembuatan prototipe cepat yang terus diuji dan diperbaiki bersama pengguna, dengan siklus umpan balik yang jauh lebih cepat dari Agile. Metode ini cocok untuk proyek yang butuh validasi ide secepat mungkin, meski konsekuensinya dokumentasi formal sering dikorbankan demi kecepatan.

Kenapa perbedaan metode ini penting. Anggota yang paham konsep SDLC saja, tapi tidak paham metode penerapannya, akan kesulitan menjelaskan kenapa timnya bekerja dengan cara tertentu, atau kenapa satu pendekatan cocok untuk satu proyek tapi tidak cocok untuk proyek lain.

**Output**

Esai pendek, tiga skenario proyek diberikan langsung berikut pertanyaannya. Jawaban ditulis di kolom teks, panjang bebas, minimal 3 kalimat per skenario.

**Skenario A.** Sebuah instansi pemerintah memesan sistem pencatatan arsip surat masuk dan keluar. Kebutuhannya sudah ditentukan lengkap lewat dokumen resmi sejak awal, ada aturan baku yang tidak akan berubah dalam waktu dekat, dan proyek harus melewati proses audit tahap demi tahap sebelum lanjut ke tahap berikutnya.
Pertanyaan: Metode pengembangan mana yang paling cocok untuk skenario ini? Jelaskan alasannya berdasarkan karakteristik metode yang sudah dipelajari.

**Skenario B.** Sebuah startup ingin membangun aplikasi belanja online, tapi tim produk masih sering mengubah fitur berdasarkan masukan pengguna yang terus masuk tiap minggu. Mereka ingin bisa merilis pembaruan kecil secara rutin tanpa menunggu seluruh aplikasi selesai.
Pertanyaan: Metode pengembangan mana yang paling cocok untuk skenario ini? Jelaskan alasannya.

**Skenario C.** Sebuah tim ingin memvalidasi apakah ide aplikasi pemesanan laundry akan diminati pasar, sebelum menginvestasikan banyak waktu dan biaya. Mereka butuh prototipe yang bisa dicoba calon pengguna dalam hitungan hari, bukan bulan, dan siap merombak total kalau ternyata idenya kurang tepat.
Pertanyaan: Metode pengembangan mana yang paling cocok untuk skenario ini? Jelaskan alasannya.

Kunci jawaban (untuk acuan penilaian, tidak ditampilkan ke anggota): Skenario A cocok Waterfall karena kebutuhan sudah jelas dan tidak berubah, prosesnya butuh linearitas dan audit bertahap. Skenario B cocok Agile karena kebutuhan masih berubah dan butuh evaluasi bertahap lewat sprint. Skenario C cocok RAD karena fokus utamanya validasi ide secepat mungkin lewat prototipe, dokumentasi formal bukan prioritas.

---

### 2.1.3. Arsitektur Client-Server dan Mekanisme Request-Response

**Isi Materi**

Arsitektur client-server adalah cara mengatur peran dalam sebuah sistem, di mana ada dua pihak dengan tanggung jawab berbeda. Client adalah pihak yang meminta sesuatu, biasanya berupa browser di perangkat pengguna. Server adalah pihak yang menyediakan dan memproses permintaan itu, biasanya berupa komputer yang menjalankan program khusus dan selalu menyala untuk melayani permintaan yang masuk.

Mekanisme request-response adalah cara kedua pihak itu saling berkomunikasi. Prosesnya dimulai ketika client mengirim permintaan atau request, misalnya saat seseorang mengetik alamat website di browser. Request itu berisi informasi tentang apa yang diminta, dikirim lewat jaringan internet menuju server yang dituju. Server menerima request itu, memprosesnya (bisa berarti mengambil data dari database, menjalankan logika tertentu, atau sekadar mengambil file yang diminta), lalu mengirim balik hasilnya berupa response. Response inilah yang kemudian ditampilkan browser sebagai halaman web yang terlihat oleh pengguna.

Satu siklus request-response ini bisa terjadi berkali-kali dalam satu halaman web. Bukan cuma satu kali saat halaman pertama kali dibuka, tapi juga tiap kali ada aksi seperti klik tombol yang memuat data baru, atau mengisi form yang harus dikirim ke server.

Kenapa memahami arsitektur dan mekanisme ini penting. Ketika sebuah aplikasi hasil AI-generate terasa lambat, sering error tanpa jelas kenapa, atau data yang ditampilkan tidak sesuai harapan, akar masalahnya hampir selalu ada di suatu titik dalam siklus request-response ini, entah requestnya salah bentuk, prosesnya di server bermasalah, atau responsenya tidak ditangani dengan benar oleh client. Tanpa memahami arsitektur ini, seseorang tidak akan tahu di titik mana harus mulai mencari masalah.

**Output**

Kuis studi kasus, tiga skenario masalah aplikasi diberikan berikut pilihan jawaban. Anggota memilih satu dari empat titik kemungkinan masalah untuk tiap skenario.

Pilihan titik masalah yang sama untuk ketiga skenario: (A) Request yang dikirim client salah bentuk atau tidak lengkap. (B) Server lambat atau gagal memproses permintaan. (C) Response dari server tidak sampai atau tidak ditangani dengan benar oleh client. (D) Tidak ada masalah di siklus request-response, masalah ada di tampilan visual semata.

**Skenario 1.** Seorang pengguna mengisi form pendaftaran, menekan tombol "Daftar", tapi halaman diam saja tanpa respons apapun selama lebih dari satu menit, sebelum akhirnya muncul pesan "waktu habis".
Kemungkinan besar titik masalah: **Kunci: B** (server lambat atau gagal memproses, sampai request time out)

**Skenario 2.** Seorang pengguna berhasil login, tapi daftar riwayat transaksinya tidak muncul sama sekali di halaman, padahal di database transaksi itu benar-benar ada.
Kemungkinan besar titik masalah: **Kunci: C** (server sudah mengirim response, tapi client gagal menampilkannya, atau response tidak ditangani dengan benar)

**Skenario 3.** Saat mengisi form dengan format nomor telepon yang salah, sistem langsung menampilkan error "data tidak valid" sebelum sempat terkirim ke server.
Kemungkinan besar titik masalah: **Kunci: A** (validasi di sisi client menangkap request yang salah bentuk sebelum dikirim)

Soal reflektif tambahan (input teks bebas): "Dari ketiga skenario di atas, menurutmu skenario mana yang paling sulit untuk didiagnosis oleh pengguna awam yang tidak paham arsitektur client-server? Jelaskan kenapa."

---

### 2.1.4. Evaluasi Kualitas Kode: Membaca Ciri Aplikasi yang Rapuh

**Isi Materi**

Kode yang rapuh adalah kode yang mungkin bisa berjalan sekarang, tapi berisiko besar bermasalah begitu ada perubahan kecil, dipakai lebih banyak orang, atau menghadapi input yang tidak terduga. Ciri-ciri ini penting dikenali sejak dini, karena hasil AI-generate sering terlihat berfungsi di percobaan pertama, padahal menyimpan kerapuhan yang baru terlihat belakangan.

Ciri pertama adalah dependency yang tidak jelas, yaitu kondisi di mana satu bagian kode bergantung pada bagian lain dengan cara yang tersembunyi atau tidak didokumentasikan, sehingga mengubah satu bagian bisa merusak bagian lain tanpa terlihat hubungannya secara langsung.

Ciri kedua adalah tidak adanya validasi input, yaitu kode yang langsung memproses apa pun yang dimasukkan pengguna tanpa memeriksa dulu apakah data itu masuk akal atau aman. Kode semacam ini akan berjalan normal selama penggunanya memasukkan data yang wajar, tapi rentan error atau bahkan disalahgunakan begitu ada input yang tidak sesuai ekspektasi.

Ciri ketiga adalah struktur yang berantakan, misalnya satu fungsi yang mengerjakan terlalu banyak hal sekaligus, penamaan variabel yang tidak jelas maksudnya, atau logika yang diulang-ulang di banyak tempat alih-alih ditulis satu kali dan dipakai ulang. Struktur semacam ini membuat kode sulit dipahami, sulit diperbaiki, dan mudah menimbulkan kesalahan baru setiap kali disentuh.

Kemampuan mengenali ketiga ciri ini adalah keterampilan evaluasi, bukan keterampilan menulis kode dari nol. Anggota tidak harus bisa menulis aplikasi kompleks di unit ini, tapi harus bisa melihat sebuah potongan kode dan menilai, apakah ini kode yang sehat atau kode yang menyimpan masalah.

**Output**

Latihan identifikasi, tiga potongan kode contoh diberikan langsung, masing-masing sengaja mengandung satu ciri kerapuhan. Anggota menandai ciri yang ditemukan dan menjelaskan alasannya di kolom teks.

**Potongan Kode 1**
```javascript
function hitung(a, b, c) {
  let x = a + b;
  let hasilAkhir = x * c - a + b / x + c;
  return hasilAkhir;
}
```
Pertanyaan: Ciri kerapuhan apa yang paling menonjol di potongan ini? Jelaskan.
Kunci acuan penilaian: struktur berantakan, penamaan variabel (x, hasilAkhir, a, b, c) tidak menjelaskan maksudnya, logika perhitungan bercampur tanpa pemisahan yang jelas sehingga sulit dipahami maksud bisnisnya.

**Potongan Kode 2**
```javascript
function simpanUmur(inputUmur) {
  const umur = inputUmur;
  database.simpan("umur_pengguna", umur);
  return "Data tersimpan";
}
```
Pertanyaan: Ciri kerapuhan apa yang paling menonjol di potongan ini? Jelaskan.
Kunci acuan penilaian: tidak ada validasi input, nilai `inputUmur` langsung disimpan tanpa memeriksa apakah itu angka, apakah masuk akal (misalnya bukan negatif atau ribuan tahun), atau apakah kosong.

**Potongan Kode 3**
```javascript
function updateHarga(produk) {
  produk.harga = produk.harga * diskonAktif;
  cekStokGudangUtama(produk);
}
```
Pertanyaan: Ciri kerapuhan apa yang paling menonjol di potongan ini, dan kenapa berbahaya kalau `cekStokGudangUtama` diubah atau dihapus di bagian kode lain? Jelaskan.
Kunci acuan penilaian: dependency yang tidak jelas, fungsi `updateHarga` diam-diam bergantung pada `diskonAktif` (variabel dari luar fungsi) dan pada `cekStokGudangUtama`, tanpa dokumentasi bahwa perubahan harga selalu memicu pengecekan stok, sehingga mengubah salah satu bagian bisa merusak bagian lain secara tidak terlihat.

---

## 2.2. Modul 2: Bahasa Pemrograman, Framework, dan Tech Stack

### 2.2.1. Pengantar Konseptual: Bahasa Pemrograman, Framework, dan Relasinya

**Isi Materi**

Bahasa pemrograman adalah alat untuk menuliskan instruksi yang bisa dijalankan komputer. Setiap bahasa punya sintaks (aturan penulisan) sendiri, tapi semuanya sama-sama bertugas menerjemahkan logika manusia menjadi sesuatu yang bisa dieksekusi mesin. Bahasa pemrograman berbeda dari markup language seperti HTML dan stylesheet language seperti CSS, karena bahasa pemrograman mampu membuat keputusan lewat logika (jika begini maka begitu) dan melakukan perhitungan, sedangkan HTML dan CSS hanya menyusun struktur dan tampilan tanpa kemampuan mengambil keputusan.

Framework adalah kerangka kerja siap pakai yang dibangun di atas satu bahasa pemrograman tertentu, menyediakan struktur dan alat dasar sehingga developer tidak perlu membangun semuanya dari nol setiap kali memulai proyek. Analoginya, bahasa pemrograman adalah bahan bakunya (kayu, semen, besi), sedangkan framework adalah rangka bangunan yang sudah setengah jadi, developer tinggal mengisi dan menyesuaikan sesuai kebutuhan proyek.

Relasi antara keduanya bersifat wajib satu arah. Setiap framework butuh bahasa pemrograman sebagai dasarnya, tapi tidak setiap bahasa pemrograman butuh framework untuk dipakai. React dibangun di atas JavaScript, Laravel dibangun di atas PHP, Django dibangun di atas Python. Artinya, memahami sebuah framework mengharuskan pemahaman bahasa pemrogramannya lebih dulu.

Kenapa relasi ini penting dipahami sejak awal modul ini. Anggota yang langsung loncat mempelajari framework tanpa memahami bahasa dasarnya akan kesulitan membaca error, kesulitan menyesuaikan kode di luar pola baku framework, dan cenderung hanya bisa menyalin-tempel tanpa mengerti kenapa sebuah kode bekerja.

**Output**

Kuis mencocokkan, 5 pasangan. Anggota mencocokkan framework di kolom kiri dengan bahasa induknya di kolom kanan.

Kolom kiri: (1) React, (2) Laravel, (3) Django, (4) Flutter, (5) Ruby on Rails
Kolom kanan (acak): (a) PHP, (b) Dart, (c) JavaScript, (d) Ruby, (e) Python

Kunci jawaban: 1-c, 2-a, 3-e, 4-b, 5-d

Soal esai singkat tambahan (input teks bebas): "Jelaskan dengan bahasamu sendiri, kenapa seseorang tidak disarankan langsung belajar Laravel sebelum memahami dasar PHP terlebih dulu. Kaitkan jawabanmu dengan konsep relasi bahasa pemrograman dan framework yang sudah dipelajari."

---

### 2.2.2. Ragam Bahasa Pemrograman Populer dan Karakteristiknya

**Isi Materi**

Ada ratusan bahasa pemrograman, tapi hanya segelintir yang relevan untuk pemula memulai. Setiap bahasa punya "wilayah kekuatan" masing-masing, area di mana bahasa itu paling sering dan paling efektif dipakai.

JavaScript adalah satu-satunya bahasa yang berjalan native di browser, menjadikannya wajib untuk pengembangan frontend web, dan lewat Node.js juga bisa dipakai di sisi backend. Python dikenal dengan sintaks yang mudah dibaca, populer di bidang data science, machine learning, otomasi, dan juga backend web lewat framework seperti Django atau Flask. Java banyak dipakai untuk aplikasi skala besar di perusahaan dan pengembangan Android versi lama. Kotlin dan Swift masing-masing adalah bahasa modern untuk Android dan iOS. PHP, meski sering dianggap "kuno", masih menjadi tulang punggung banyak website termasuk WordPress dan dipakai lewat framework Laravel. C dan C++ dipakai untuk aplikasi yang butuh performa sangat tinggi seperti game engine dan sistem operasi.

Bahasa pemrograman juga bisa dibedakan berdasarkan seberapa dekat dengan bahasa mesin (low-level) atau dekat dengan bahasa manusia (high-level). Hampir seluruh bahasa yang relevan untuk pemula web development, seperti JavaScript, Python, dan PHP, tergolong high-level, artinya lebih mudah dibaca dan ditulis manusia.

Memahami peta ini bukan berarti anggota harus menguasai semuanya. Tujuannya supaya anggota tahu bahasa mana yang relevan untuk tujuan spesifik yang ingin dicapai, bukan memilih bahasa secara acak atau ikut tren tanpa alasan jelas.

**Output**

Kuis pilihan ganda, 4 soal.

1. Bahasa pemrograman yang berjalan native di browser dan wajib untuk frontend web adalah:
   A. Python  
   B. JavaScript  
   C. Java  
   D. C++
   **Kunci: B**

2. Seorang mahasiswa ingin fokus di bidang data science dan machine learning. Bahasa yang paling relevan untuk dipelajari adalah:
   A. PHP  
   B. Swift  
   C. Python  
   D. C
   **Kunci: C**

3. PHP paling banyak dipakai untuk:
   A. Pengembangan game dengan performa tinggi  
   B. Backend website, termasuk lewat framework Laravel  
   C. Aplikasi Android native  
   D. Machine learning
   **Kunci: B**

4. Kotlin dan Swift adalah contoh bahasa yang masing-masing dipakai untuk:
   A. Android dan iOS  
   B. Frontend dan backend web  
   C. Data science dan otomasi  
   D. Sistem operasi dan game engine
   **Kunci: A**

Soal reflektif tambahan (input teks bebas): "Berdasarkan minatmu saat ini (frontend, backend, mobile, atau data), bahasa pemrograman apa yang paling relevan untuk kamu pelajari lebih dalam? Jelaskan alasanmu memilih bahasa itu."

---

### 2.2.3. Ragam Framework Populer dan Fungsinya per Kategori

**Isi Materi**

Framework bisa dikelompokkan berdasarkan bagian aplikasi yang ditanganinya. Framework frontend menangani apa yang dilihat dan berinteraksi langsung dengan pengguna di browser, contohnya React, Vue, dan Svelte, semuanya dibangun di atas JavaScript. Framework backend menangani logika di balik layar, pengolahan data, dan komunikasi dengan database, contohnya Laravel (PHP), Django dan Flask (Python), Express (JavaScript lewat Node.js), dan Ruby on Rails (Ruby). Ada juga framework fullstack yang menangani frontend dan backend sekaligus dalam satu ekosistem, contohnya Next.js yang dibangun di atas React.

Selain kategori frontend dan backend, ada juga framework mobile seperti Flutter (Dart) dan React Native (JavaScript) yang memungkinkan satu basis kode dipakai untuk membangun aplikasi Android dan iOS sekaligus.

Setiap framework dalam kategori yang sama biasanya punya filosofi berbeda. React misalnya lebih fleksibel dan minim aturan baku (unopinionated), developer bebas menyusun strukturnya sendiri. Laravel dan Django sebaliknya lebih opinionated, sudah punya struktur folder dan konvensi baku yang harus diikuti, memudahkan tim besar bekerja konsisten tapi mengurangi kebebasan struktur.

Memahami kategori dan filosofi framework ini penting supaya anggota bisa memilih framework yang tepat sesuai kebutuhan proyek, bukan sekadar memilih yang paling populer atau paling sering disebut di media sosial.

**Output**

Tabel isian (disajikan sebagai form dengan kolom kosong untuk diisi anggota): anggota diminta mengisi kategori (frontend/backend/fullstack/mobile) dan bahasa induk untuk lima framework berikut: Vue, Express, Next.js, React Native, Django.

Kunci jawaban acuan: Vue (frontend, JavaScript), Express (backend, JavaScript via Node.js), Next.js (fullstack, JavaScript berbasis React), React Native (mobile, JavaScript), Django (backend, Python).

Soal esai singkat tambahan (input teks bebas): "Jelaskan perbedaan antara framework yang unopinionated seperti React dan yang opinionated seperti Laravel atau Django. Menurutmu, untuk tim proyek dengan banyak anggota baru seperti Eksekusi WEBI-SPACE, framework tipe mana yang lebih menguntungkan? Jelaskan alasanmu."

---

### 2.2.4. Arsitektur Tech Stack: Bagaimana Komponen Saling Melengkapi

**Isi Materi**

Tech stack adalah kombinasi teknologi yang dipakai bersama untuk membangun satu aplikasi secara utuh, mulai dari tampilan yang dilihat pengguna sampai tempat data disimpan. Sebuah tech stack yang lengkap biasanya terdiri dari empat komponen utama, frontend (menangani tampilan dan interaksi pengguna), backend (menangani logika dan pemrosesan data), database (tempat data disimpan secara permanen), dan infrastruktur atau hosting (tempat aplikasi dijalankan agar bisa diakses lewat internet).

Komponen-komponen ini saling melengkapi lewat mekanisme komunikasi yang jelas. Frontend mengirim request ke backend, backend memproses permintaan itu dan berinteraksi dengan database untuk mengambil atau menyimpan data, lalu backend mengirim response kembali ke frontend untuk ditampilkan ke pengguna. Contoh tech stack yang dikenal adalah MERN (MongoDB, Express, React, Node.js) dan LAMP (Linux, Apache, MySQL, PHP), masing-masing punya kombinasi komponen yang sudah terbukti bekerja baik bersama.

Memilih tech stack bukan sekadar memilih komponen yang populer satu per satu, tapi memastikan seluruh komponen bisa berkomunikasi dan saling melengkapi dengan lancar. Sebuah frontend React yang canggih tidak ada artinya kalau tidak dipasangkan dengan backend dan database yang bisa diajak bekerja sama secara efisien.

Kenapa pemahaman arsitektur tech stack penting bagi anggota Eksplorasi. WEBI-SPACE sendiri memakai kombinasi Laravel, Livewire, Alpine.js, dan MySQL, sebuah contoh nyata tech stack yang saling melengkapi dan bisa dipelajari langsung dari proyek yang sedang berjalan di divisi.

**Output**

Dokumen rancangan pemilihan stack (disajikan sebagai template isian, bukan esai bebas). Anggota mengisi template berikut untuk sebuah ide aplikasi sederhana pilihan sendiri (misalnya aplikasi to-do list, aplikasi catatan, atau aplikasi galeri foto):

Template:
1. Nama dan deskripsi singkat aplikasi (1-2 kalimat)
2. Komponen frontend yang dipilih, beserta alasan (framework atau vanilla HTML/CSS/JS)
3. Komponen backend yang dipilih, beserta alasan
4. Database yang dipilih, beserta alasan
5. Diagram sederhana alur komunikasi antar komponen (boleh berupa deskripsi teks bertahap, misalnya "pengguna klik tombol A, frontend kirim request ke backend endpoint B, backend ambil data dari tabel C, data dikirim balik dan ditampilkan di halaman D")
6. Satu risiko atau tantangan yang mungkin muncul dari kombinasi stack yang dipilih

Dokumen ini dinilai berdasarkan konsistensi dan kejelasan alasan, bukan berdasarkan stack mana yang "benar", karena tidak ada satu stack yang mutlak benar untuk semua kasus.

---

### 2.2.5. Kurasi Sumber Belajar Kredibel per Stack

**Isi Materi**

Salah satu keterampilan yang sering diabaikan pemula adalah kemampuan memilih sumber belajar yang kredibel. Internet penuh dengan tutorial, sebagian ditulis dengan baik dan terus diperbarui, sebagian lain sudah usang atau bahkan mengandung praktik yang tidak lagi direkomendasikan industri.

Ciri sumber belajar kredibel bisa dikenali dari beberapa hal. Pertama, sumber resmi dari pembuat teknologi itu sendiri (dokumentasi resmi) selalu menjadi rujukan paling akurat, meskipun kadang terasa lebih teknis dibanding tutorial pihak ketiga. Kedua, tanggal publikasi atau tanggal pembaruan terakhir, teknologi web berkembang cepat sehingga tutorial berumur lebih dari dua sampai tiga tahun berisiko mengajarkan cara yang sudah ditinggalkan. Ketiga, reputasi platform atau penulisnya, platform yang dikenal luas seperti dokumentasi resmi, MDN Web Docs, atau kanal yang konsisten diakui komunitas developer, biasanya lebih bisa dipercaya dibanding blog perorangan yang tidak jelas kredibilitasnya.

Kemampuan memilah sumber ini penting karena anggota Eksplorasi akan terus belajar mandiri sepanjang kariernya, jauh melampaui apa yang diajarkan kurikulum ini. Developer yang baik bukan yang tahu segalanya, tapi yang tahu ke mana harus mencari jawaban yang benar dan bisa dipercaya.

**Output**

Latihan kurasi (disajikan sebagai tabel isian). Anggota mencari dan mencantumkan 3 sumber belajar kredibel untuk tech stack yang sudah dipilih di unit 2.4 (frontend, backend, atau database), lalu mengisi kolom berikut untuk masing-masing sumber:

| Nama sumber | Tautan | Jenis (dokumentasi resmi / platform belajar / kanal komunitas) | Alasan dianggap kredibel |
|---|---|---|---|

Soal reflektif tambahan (input teks bebas): "Ceritakan pengalamanmu, pernahkah kamu mengikuti tutorial yang ternyata sudah usang atau tidak lagi berlaku? Apa yang kamu pelajari dari pengalaman itu soal pentingnya memilih sumber belajar?"

---

## 2.3. Modul 3: Lanskap Industri dan Arah Pergerakan Teknologi

### 2.3.1. Pola Evolusi Tech Stack

**Isi Materi**

Teknologi web tidak berubah secara acak, ada pola yang bisa dibaca di balik naik turunnya popularitas sebuah tech stack. Pola pertama adalah pergeseran dari kompleksitas menuju kemudahan, teknologi yang menyederhanakan pekerjaan developer cenderung diadopsi luas, contohnya jQuery dulu populer karena menyederhanakan manipulasi DOM yang rumit di JavaScript murni, lalu digantikan React dan sejenisnya yang menyederhanakan pengelolaan tampilan yang kompleks dengan pendekatan komponen.

Pola kedua adalah dorongan dari kebutuhan skala. Teknologi yang awalnya cukup untuk aplikasi kecil, seringkali perlu digantikan atau dilengkapi teknologi baru begitu aplikasi itu dipakai jutaan pengguna, karena masalah performa dan kompleksitas yang muncul di skala besar berbeda dari masalah di skala kecil.

Pola ketiga adalah pengaruh perusahaan besar teknologi. Banyak teknologi populer lahir dari kebutuhan internal perusahaan besar seperti Meta (React), Google (Angular, Go), dan Netflix (arsitektur microservice), lalu dirilis sebagai open source dan diadopsi luas karena sudah terbukti dipakai di skala produksi nyata.

Pola keempat adalah siklus hidup yang wajar, sebuah teknologi biasanya melalui fase kemunculan, adopsi luas, matang dan stabil, lalu perlahan digantikan teknologi baru yang menyelesaikan masalah baru. Ini bukan berarti teknologi lama otomatis buruk, banyak teknologi "lama" seperti PHP tetap relevan dan terus diperbarui, hanya saja hype-nya tidak seramai dulu.

Memahami pola ini melatih anggota untuk tidak panik atau FOMO setiap kali ada teknologi baru muncul, tapi mampu menilai apakah teknologi itu benar-benar menyelesaikan masalah nyata atau sekadar tren sesaat.

**Output**

Esai riset singkat (input teks, minimal 150 kata). Anggota memilih satu dari tiga topik berikut, melakukan riset singkat mandiri (boleh mencari di internet), lalu menulis esai yang menjelaskan pola evolusi yang berlaku pada topik pilihannya, dikaitkan dengan keempat pola yang sudah dipelajari.

Pilihan topik:
1. Kenapa jQuery yang dulu sangat populer sekarang jarang dipakai untuk proyek baru?
2. Kenapa React tetap menjadi salah satu library frontend paling populer selama lebih dari satu dekade?
3. Kenapa banyak startup memilih tech stack seperti Next.js atau Laravel dibanding membangun semuanya dari nol?

Esai dinilai berdasarkan kejelasan penalaran dan kaitannya dengan pola evolusi yang sudah dipelajari, bukan berdasarkan kelengkapan riset semata.

---

### 2.3.2. Metode Membaca Sinyal Industri

**Isi Materi**

Selain memahami pola evolusi secara historis, anggota juga perlu tahu cara membaca sinyal industri secara langsung, untuk menilai apakah sebuah teknologi masih relevan dipelajari saat ini. Ada beberapa sinyal konkret yang bisa dibaca.

Sinyal pertama adalah lowongan kerja. Situs lowongan kerja seperti LinkedIn atau Glassdoor mencerminkan kebutuhan nyata industri, semakin banyak lowongan yang menyebut sebuah teknologi, semakin besar permintaan pasar terhadapnya saat ini.

Sinyal kedua adalah aktivitas komunitas open source, khususnya di GitHub. Jumlah bintang (stars), frekuensi commit terbaru, dan jumlah kontributor aktif menunjukkan seberapa hidup dan terus dikembangkan sebuah proyek teknologi.

Sinyal ketiga adalah survei developer tahunan, seperti Stack Overflow Developer Survey, yang mengumpulkan data langsung dari puluhan ribu developer tentang teknologi apa yang mereka pakai, sukai, dan ingin pelajari.

Sinyal keempat adalah dukungan dari perusahaan besar, apakah teknologi itu dipakai dan terus didukung oleh perusahaan teknologi besar, karena dukungan semacam ini biasanya menjamin teknologi itu akan terus dipelihara dalam jangka panjang.

Kemampuan membaca sinyal-sinyal ini penting supaya anggota tidak memilih arah belajar semata-mata berdasarkan opini satu video atau satu utas media sosial yang belum tentu mencerminkan kondisi industri secara luas.

**Output**

Latihan riset terpandu (disajikan sebagai form isian). Anggota memilih satu bahasa pemrograman atau framework yang ingin didalami, lalu mencari dan mengisi data konkret untuk keempat sinyal berikut:

| Sinyal | Data yang ditemukan | Sumber (tautan) |
|---|---|---|
| Jumlah lowongan kerja terkait (di LinkedIn/Glassdoor/Jobstreet, cantumkan angka perkiraan) | | |
| Aktivitas GitHub (jumlah stars dan tanggal commit terakhir dari repo resmi) | | |
| Posisi di survei developer terbaru (misalnya Stack Overflow Developer Survey) | | |
| Perusahaan besar yang diketahui memakai atau mendukung teknologi ini | | |

Setelah tabel terisi, anggota menulis satu paragraf kesimpulan (input teks): "Berdasarkan keempat sinyal yang kamu temukan, apakah teknologi ini masih layak dipelajari saat ini? Jelaskan kesimpulanmu."

---

## 2.4. Modul 4: Peran-Peran dalam Tim Software

### 2.4.1. Struktur Peran dalam Tim Software

**Isi Materi**

Membangun software, terutama yang berskala menengah sampai besar, jarang dikerjakan satu orang. Ada beragam peran yang saling melengkapi, masing-masing punya tanggung jawab spesifik.

Product Manager bertanggung jawab menentukan apa yang perlu dibangun dan kenapa, berdasarkan kebutuhan pengguna dan tujuan bisnis. UI/UX Designer merancang bagaimana pengguna akan berinteraksi dengan aplikasi, memastikan tampilan tidak cuma indah tapi juga mudah dipahami dan digunakan. Frontend Developer membangun apa yang dilihat dan disentuh langsung oleh pengguna. Backend Developer membangun logika, pemrosesan data, dan komunikasi dengan database di balik layar. Quality Assurance atau QA menguji aplikasi secara sistematis untuk menemukan bug sebelum sampai ke pengguna akhir. DevOps mengelola infrastruktur, memastikan aplikasi bisa di-deploy dan berjalan stabil di server produksi. Project Manager mengatur alur kerja, jadwal, dan komunikasi antar anggota tim agar proyek selesai tepat waktu.

Di tim kecil atau startup awal, satu orang sering merangkap beberapa peran sekaligus, misalnya seorang developer yang juga merangkap QA dan DevOps. Namun semakin besar skala proyek, pemisahan peran menjadi semakin penting agar setiap aspek mendapat perhatian yang cukup mendalam.

Kenapa memahami struktur peran ini penting bagi anggota Eksplorasi. WEBI-SPACE sendiri, lewat sistem Eksekusi, akan menempatkan anggota dalam peran-peran nyata di proyek, sehingga pemahaman struktur ini menjadi bekal langsung yang terpakai, bukan sekadar teori.

**Output**

Kuis mencocokkan (5 pasangan). Anggota mencocokkan peran di kolom kiri dengan tanggung jawab utamanya di kolom kanan.

Kolom kiri: (1) UI/UX Designer, (2) Backend Developer, (3) QA, (4) DevOps, (5) Product Manager
Kolom kanan (acak): (a) Menguji aplikasi secara sistematis untuk menemukan bug sebelum sampai ke pengguna, (b) Menentukan apa yang perlu dibangun berdasarkan kebutuhan pengguna dan tujuan bisnis, (c) Mengelola infrastruktur dan memastikan aplikasi berjalan stabil di server produksi, (d) Merancang interaksi dan tampilan yang mudah dipahami pengguna, (e) Membangun logika dan pemrosesan data di balik layar

Kunci jawaban: 1-d, 2-e, 3-a, 4-c, 5-b

---

### 2.4.2. Mekanisme Ketergantungan Antar Peran dalam Siklus Proyek

**Isi Materi**

Peran-peran dalam tim software tidak bekerja sendiri-sendiri secara terpisah, melainkan saling bergantung dalam sebuah alur kerja berurutan sekaligus saling memberi umpan balik. Product Manager menentukan kebutuhan, kebutuhan itu diterjemahkan UI/UX Designer menjadi rancangan tampilan dan alur pengguna, rancangan itu kemudian diimplementasikan Frontend Developer untuk tampilannya dan Backend Developer untuk logikanya, hasil implementasi itu diuji QA sebelum akhirnya di-deploy DevOps ke server produksi, sementara Project Manager mengawal seluruh alur ini agar berjalan sesuai jadwal.

Ketergantungan ini bersifat dua arah, bukan cuma satu arah dari atas ke bawah. Misalnya, Frontend Developer yang menemukan bahwa rancangan UI/UX sulit diimplementasikan secara teknis perlu memberi umpan balik ke Designer untuk penyesuaian. QA yang menemukan bug krusial perlu mengembalikan pekerjaan ke Developer sebelum lanjut ke tahap deploy. Ketergantungan semacam ini membuat komunikasi antar peran menjadi sama pentingnya dengan keterampilan teknis masing-masing peran.

Kegagalan satu peran dalam memenuhi tanggung jawabnya akan merambat ke peran-peran berikutnya. Kebutuhan yang salah dipahami Product Manager akan menghasilkan rancangan yang salah arah dari Designer, yang kemudian membuat Developer membangun sesuatu yang sebenarnya tidak dibutuhkan pengguna, betapa pun rapi kode yang ditulis.

Memahami mekanisme saling ketergantungan ini penting supaya anggota tidak memandang perannya sendiri secara terisolasi, tapi memahami dampak pekerjaannya terhadap peran lain di sepanjang siklus proyek.

**Output**

Diagram alur isian (disajikan sebagai latihan menyusun urutan). Anggota diberi delapan kartu pernyataan acak yang mewakili tahapan siklus proyek, lalu diminta menyusun urutan yang benar berdasarkan alur ketergantungan yang sudah dipelajari.

Kartu (acak): 
(1) QA menemukan bug dan mengembalikan ke Developer
(2) DevOps men-deploy aplikasi ke server produksi
(3) Product Manager menentukan kebutuhan fitur baru
(4) Frontend dan Backend Developer mengimplementasikan rancangan
(5) UI/UX Designer merancang tampilan dan alur pengguna
(6) Developer memperbaiki bug yang ditemukan QA
(7) QA menguji aplikasi yang sudah dibangun
(8) Project Manager memastikan seluruh tahapan berjalan sesuai jadwal (berlangsung sepanjang siklus)

Kunci urutan yang benar: 3, 5, 4, 7, 1, 6, 2, dengan (8) berjalan paralel mengawal seluruh tahapan.

---

### 2.4.3. Studi Kasus: Dampak Kekosongan Satu Peran terhadap Proyek

**Isi Materi**

Bagian ini menyintesiskan dua unit sebelumnya lewat studi kasus konkret, memperlihatkan apa yang terjadi kalau satu peran hilang atau tidak berfungsi dengan baik dalam sebuah proyek.

Ketika tidak ada Product Manager yang jelas, tim developer sering membangun fitur berdasarkan asumsi sendiri, menghasilkan aplikasi yang secara teknis berjalan tapi tidak menjawab kebutuhan pengguna sebenarnya, ujungnya harus dibangun ulang setelah diluncurkan dan mendapat masukan negatif.

Ketika tidak ada QA, bug-bug yang seharusnya bisa ditemukan sebelum rilis justru ditemukan pengguna di produksi, merusak kepercayaan dan reputasi produk, serta memaksa tim memadamkan masalah secara darurat alih-alih bekerja terencana.

Ketika tidak ada UI/UX Designer, developer sering merancang tampilan berdasarkan seleranya sendiri tanpa riset pengguna, menghasilkan aplikasi yang membingungkan meski secara fungsi lengkap, akhirnya banyak pengguna berhenti memakai karena kesulitan menavigasinya.

Studi kasus semacam ini menunjukkan bahwa AI generatif, betapapun canggih, tidak otomatis mengisi kekosongan peran-peran ini. AI bisa membantu menulis kode atau bahkan menyarankan rancangan tampilan, tapi keputusan tentang kebutuhan pengguna sebenarnya, penilaian kualitas lewat pengujian sistematis, dan riset pengalaman pengguna nyata tetap membutuhkan penilaian dan tanggung jawab manusia.

**Output**

Studi kasus tertulis (input teks, minimal 200 kata). Anggota memilih satu dari tiga skenario berikut, lalu menulis analisis dampak dan solusinya.

**Skenario 1.** Sebuah tim kecil membangun aplikasi manajemen tugas tanpa Product Manager. Setelah tiga bulan pengembangan, aplikasi diluncurkan lengkap dengan fitur kompleks seperti integrasi kalender dan notifikasi bertingkat, tapi pengguna ternyata hanya butuh fitur pencatatan tugas sederhana. Aplikasi sepi pengguna.
Pertanyaan: Apa dampak kekosongan peran Product Manager dalam kasus ini? Langkah apa yang seharusnya dilakukan tim sebelum mulai membangun untuk mencegah hal ini terjadi?

**Skenario 2.** Sebuah aplikasi e-commerce diluncurkan tanpa proses QA yang memadai. Dalam minggu pertama, pengguna melaporkan tombol checkout yang kadang gagal memproses pembayaran tapi tetap mengurangi stok barang.
Pertanyaan: Apa dampak nyata dari kekosongan peran QA dalam kasus ini, baik dari sisi bisnis maupun kepercayaan pengguna? Langkah apa yang seharusnya dilakukan sebelum peluncuran?

**Skenario 3.** Sebuah aplikasi dibangun sepenuhnya oleh developer tanpa keterlibatan UI/UX Designer. Semua fitur yang diminta berhasil dibangun, tapi menu navigasinya membingungkan dan banyak pengguna baru kesulitan menemukan fitur utama.
Pertanyaan: Apa dampak kekosongan peran UI/UX Designer dalam kasus ini? Bagaimana peran ini seharusnya dilibatkan sejak tahap awal proyek?

---

## 2.5. Modul 5: Version Control dengan Git

### 2.5.1. Mekanisme Version Control

**Isi Materi**

Version control adalah sistem yang mencatat setiap perubahan pada file-file proyek dari waktu ke waktu, memungkinkan seseorang melihat riwayat perubahan, membandingkan versi berbeda, dan kembali ke versi sebelumnya kapan pun diperlukan. Tanpa version control, mengelola perubahan kode biasanya dilakukan dengan cara manual yang rapuh, misalnya menyimpan banyak file dengan nama seperti "proyek_final.zip", "proyek_final_revisi.zip", "proyek_final_revisi_beneran.zip", cara yang mudah membingungkan dan rawan kehilangan data.

Git adalah sistem version control yang paling banyak dipakai di dunia, bekerja secara terdesentralisasi, artinya setiap orang yang bekerja dengan Git punya salinan lengkap riwayat proyek di komputernya masing-masing, bukan hanya bergantung pada satu server pusat. Ini membuat Git tetap bisa dipakai meski sedang tidak terhubung internet, dan history bisa dipulihkan dari salinan siapa pun dalam tim kalau terjadi masalah di satu titik.

Git bekerja dengan konsep commit, yaitu titik penyimpanan (snapshot) dari keadaan proyek pada momen tertentu, lengkap dengan pesan yang menjelaskan perubahan apa yang dilakukan. Rangkaian commit inilah yang membentuk riwayat proyek yang bisa ditelusuri kembali kapan saja.

Git berbeda dari GitHub. Git adalah alat/software yang berjalan di komputer, sedangkan GitHub adalah platform online yang menyimpan proyek Git di internet dan menambahkan fitur kolaborasi seperti Pull Request. Perbedaan ini akan diperjelas dengan praktik di unit-unit berikutnya.

**Output**

Kuis pilihan ganda, 4 soal.

1. Version control pada dasarnya berfungsi untuk:
   A. Mempercepat koneksi internet saat coding
   B. Mencatat riwayat perubahan file proyek dan memungkinkan kembali ke versi sebelumnya
   C. Menggantikan kebutuhan menulis dokumentasi
   D. Menghapus file yang tidak dipakai secara otomatis
   **Kunci: B**

2. Perbedaan mendasar antara Git dan GitHub adalah:
   A. Git untuk pemula, GitHub untuk profesional
   B. Git adalah alat version control yang berjalan di komputer, GitHub adalah platform online untuk menyimpan dan berkolaborasi lewat Git
   C. Keduanya sama persis, hanya beda nama
   D. Git hanya untuk bahasa Python, GitHub untuk semua bahasa
   **Kunci: B**

3. Commit dalam Git berarti:
   A. Menghapus seluruh riwayat proyek
   B. Titik penyimpanan (snapshot) keadaan proyek pada momen tertentu, disertai pesan penjelasan
   C. Mengunggah proyek ke internet
   D. Membuat salinan proyek di komputer lain
   **Kunci: B**

4. Kenapa Git disebut bekerja secara terdesentralisasi?
   A. Karena tidak butuh internet sama sekali selamanya
   B. Karena setiap orang punya salinan lengkap riwayat proyek di komputernya masing-masing, bukan cuma bergantung pada satu server pusat
   C. Karena hanya satu orang yang boleh menyimpan riwayat proyek
   D. Karena Git hanya berjalan di server perusahaan besar
   **Kunci: B**

---

### 2.5.2. Command Dasar Git: Init, Add, Commit, Log

**Isi Materi**

Setelah memahami konsepnya, saatnya masuk ke praktik command dasar Git yang dijalankan lewat terminal. Empat command ini adalah fondasi yang akan terus dipakai sepanjang bekerja dengan Git.

`git init` menginisialisasi folder yang sedang dikerjakan menjadi sebuah repository Git, artinya folder itu mulai dilacak riwayat perubahannya. Command ini hanya dijalankan sekali di awal sebuah proyek baru.

`git add` menandai file mana saja yang perubahannya ingin dimasukkan ke commit berikutnya, proses ini disebut staging. Menjalankan `git add nama_file` menandai satu file tertentu, sedangkan `git add .` menandai seluruh file yang berubah di folder tersebut.

`git commit -m "pesan"` menyimpan seluruh perubahan yang sudah di-stage tadi sebagai satu titik riwayat baru, disertai pesan yang menjelaskan perubahan apa yang dilakukan. Pesan commit yang jelas sangat penting, karena riwayat proyek yang baik adalah riwayat yang bisa dibaca dan dipahami tanpa harus membuka isi kodenya satu per satu.

`git log` menampilkan riwayat seluruh commit yang pernah dibuat, lengkap dengan penulis, tanggal, dan pesan masing-masing commit, berguna untuk menelusuri kembali apa saja yang sudah terjadi pada sebuah proyek.

Urutan alur kerja dasarnya selalu sama, ubah file, `git add` untuk stage perubahan, `git commit` untuk menyimpannya sebagai titik riwayat, ulangi seterusnya seiring proyek berkembang.

**Output**

Praktik langsung (input teks untuk melaporkan hasil). Anggota diminta membuka terminal, membuat folder baru bernama `latihan-git`, lalu menjalankan urutan command berikut secara berurutan: `git init`, membuat satu file teks sederhana (misalnya `catatan.txt` berisi satu kalimat), `git add catatan.txt`, `git commit -m "commit pertama: menambahkan catatan.txt"`, mengubah isi file itu, lalu mengulang proses add dan commit dengan pesan baru, dan terakhir menjalankan `git log` untuk melihat riwayatnya.

Pertanyaan pelaporan: "Tempelkan hasil output dari `git log` setelah kamu menyelesaikan langkah-langkah di atas. Ada berapa commit yang tercatat, dan apakah pesan commit-nya jelas menjelaskan perubahan yang dilakukan?"

---

### 2.5.3. Command Percabangan: Branch, Checkout, Merge, Konflik

**Isi Materi**

Branch adalah cabang independen dari riwayat proyek, memungkinkan seseorang mengembangkan fitur atau perbaikan baru tanpa mengganggu kode utama yang sudah stabil. Secara default, Git menyediakan satu branch utama yang biasanya disebut `main`. Saat ingin mengerjakan sesuatu yang baru, developer membuat branch terpisah dari `main`, mengerjakan perubahan di sana, baru kemudian menggabungkannya kembali setelah selesai dan teruji.

`git branch nama-branch` membuat branch baru. `git checkout nama-branch` berpindah ke branch tersebut untuk mulai bekerja di sana (bisa juga digabung jadi satu lewat `git checkout -b nama-branch` untuk membuat sekaligus berpindah). `git merge nama-branch` menggabungkan perubahan dari branch tersebut ke branch yang sedang aktif, biasanya dilakukan saat menggabungkan pekerjaan yang sudah selesai kembali ke `main`.

Merge conflict terjadi ketika Git tidak bisa otomatis menggabungkan dua perubahan karena keduanya mengubah bagian yang sama pada file yang sama dengan cara berbeda. Saat ini terjadi, Git akan menandai bagian yang bertentangan langsung di dalam file dan meminta developer memutuskan secara manual versi mana yang dipertahankan, sebelum melanjutkan proses commit untuk menyelesaikan merge tersebut.

Merge conflict bukan tanda kesalahan atau kegagalan, melainkan bagian wajar dari kerja kolaboratif yang melibatkan banyak orang mengubah kode yang sama. Yang penting adalah memahami cara membacanya dan menyelesaikannya dengan tenang, bukan menghindarinya sama sekali.

**Output**

Praktik simulasi (input teks untuk melaporkan hasil). Melanjutkan folder `latihan-git` dari unit sebelumnya, anggota diminta: membuat branch baru bernama `fitur-baru` dan berpindah ke sana, mengubah isi `catatan.txt` dan melakukan commit di branch ini, berpindah kembali ke `main`, lalu menjalankan `git merge fitur-baru`.

Pertanyaan pelaporan: "Apakah proses merge berjalan lancar tanpa konflik? Jelaskan command apa saja yang kamu jalankan secara berurutan. Jika kamu sempat mengalami merge conflict (baik sengaja dipicu atau tidak sengaja), ceritakan bagaimana kamu menyelesaikannya."

---

### 2.5.4. Command Kolaborasi Jarak Jauh: Clone, Push, Pull, Fetch

**Isi Materi**

Empat command ini menghubungkan Git yang berjalan lokal di komputer dengan repository yang tersimpan di layanan online seperti GitHub, memungkinkan kolaborasi jarak jauh dengan anggota tim lain.

`git clone url-repository` menyalin sebuah repository yang sudah ada di GitHub ke komputer lokal, lengkap dengan seluruh riwayat commit-nya. Ini biasanya jadi langkah pertama saat bergabung ke proyek yang sudah berjalan.

`git push` mengirim commit yang sudah dibuat di komputer lokal menuju repository di GitHub, sehingga anggota tim lain bisa melihat perubahan tersebut. `git pull` mengambil perubahan terbaru dari GitHub dan langsung menggabungkannya ke branch lokal yang sedang aktif, memastikan pekerjaan seseorang selalu sinkron dengan perubahan terbaru dari tim.

`git fetch` mirip dengan pull, mengambil perubahan terbaru dari GitHub, tapi tidak langsung menggabungkannya ke branch lokal. Fetch berguna saat seseorang ingin melihat dulu perubahan apa saja yang masuk sebelum memutuskan untuk menggabungkannya, memberi kesempatan meninjau lebih dulu sebelum kode orang lain benar-benar tercampur dengan pekerjaan sendiri.

Sebagai pemula, `pull` akan jauh lebih sering dipakai dibanding `fetch`, karena kebanyakan situasi kolaborasi memang membutuhkan sinkronisasi langsung. `Fetch` menjadi pilihan yang lebih hati-hati saat bekerja di proyek dengan banyak kontributor aktif dan perubahan besar yang perlu ditinjau dulu.

**Output**

Praktik terpandu (input teks untuk melaporkan hasil dan tautan). Anggota diminta membuat akun GitHub kalau belum punya, membuat satu repository baru langsung dari GitHub bernama `latihan-remote`, lalu men-clone repository itu ke komputer lokal dengan `git clone`. Setelah itu, tambahkan satu file baru, lakukan `git add` dan `git commit`, lalu `git push` perubahan itu ke GitHub.

Pertanyaan pelaporan: "Tempelkan tautan repository GitHub-mu yang sudah berisi commit hasil push. Jelaskan urutan command yang kamu jalankan dari clone sampai push berhasil."

---

### 2.5.5. Praktik Menulis Histori yang Bermakna

**Isi Materi**

Menguasai command Git saja belum cukup, kualitas riwayat proyek juga sangat ditentukan oleh cara menulis pesan commit. Pesan commit yang buruk seperti "update", "fix", atau "asdasd" tidak memberi informasi apapun tentang perubahan yang sebenarnya terjadi, membuat riwayat proyek sulit ditelusuri saat dibutuhkan di kemudian hari, misalnya saat mencari commit mana yang menyebabkan sebuah bug muncul.

Pesan commit yang baik biasanya mengikuti pola singkat namun jelas, dimulai dengan kata kerja yang menjelaskan tindakan (menambahkan, memperbaiki, mengubah, menghapus), diikuti objek spesifik yang terkena dampak. Contohnya "menambahkan validasi email di form pendaftaran" jauh lebih informatif dibanding "update form".

Praktik lain yang penting adalah menjaga satu commit tetap fokus pada satu perubahan logis, bukan mencampur banyak perubahan tak berhubungan dalam satu commit besar. Commit yang fokus memudahkan penelusuran dan, kalau diperlukan, memudahkan membatalkan (revert) satu perubahan tanpa ikut membatalkan perubahan lain yang tidak berkaitan.

Kebiasaan menulis histori yang bermakna ini adalah salah satu tanda developer yang matang, karena menunjukkan kepedulian terhadap kolaborator lain di masa depan, termasuk diri sendiri yang mungkin lupa konteks perubahan setelah berbulan-bulan berlalu.

**Output**

Latihan evaluasi dan perbaikan (input teks). Anggota diberi lima contoh pesan commit yang buruk, diminta menuliskan versi perbaikannya yang lebih jelas dan bermakna.

Pesan commit yang perlu diperbaiki:
1. "update"
2. "fix bug"
3. "asdasd"
4. "perubahan banyak hal"
5. "wip"

Kunci acuan penilaian (contoh jawaban yang dianggap baik, bukan satu-satunya jawaban benar): 
1. "menambahkan fitur pencarian di halaman utama"
2. "memperbaiki bug tombol submit yang tidak merespons klik"
3. (pesan yang jelas menggantikan teks tidak bermakna, misalnya "menghapus file konfigurasi yang tidak terpakai")
4. dipecah menjadi beberapa commit terpisah, masing-masing fokus satu perubahan
5. "menyiapkan struktur awal halaman profil (belum selesai)"

---

## 2.6. Modul 6: Arsitektur dan Prinsip Frontend

### 2.6.1. Arsitektur Informasi: Pelabelan dan Pengkategorian Konten

**Isi Materi**

Arsitektur informasi adalah cara menyusun, melabeli, dan mengelompokkan konten dalam sebuah aplikasi supaya pengguna bisa menemukan apa yang mereka cari dengan mudah dan cepat. Ini adalah pekerjaan yang terjadi sebelum satu baris kode tampilan pun ditulis, karena struktur informasi yang buruk tidak bisa diperbaiki hanya dengan mempercantik visual.

Pelabelan (labeling) berarti memilih kata atau istilah yang dipakai untuk menu, tombol, dan kategori, harus mencerminkan bahasa yang dipahami penggunanya, bukan istilah internal tim yang membingungkan orang luar. Misalnya, label "Beranda" lebih jelas dibanding "Landing" bagi pengguna awam berbahasa Indonesia.

Pengkategorian (categorization) berarti mengelompokkan konten berdasarkan kesamaan yang masuk akal bagi pengguna, bukan berdasarkan struktur teknis di balik layar yang hanya dipahami tim developer. Prinsip yang sering dipakai adalah kategori MECE, mutually exclusive (satu konten idealnya hanya masuk satu kategori jelas, tidak tumpang tindih) dan collectively exhaustive (seluruh kategori bersama-sama mencakup semua konten, tidak ada yang terlewat tanpa tempat).

Struktur navigasi yang baik biasanya mengikuti pola hierarki yang tidak lebih dari tiga sampai empat tingkat kedalaman, karena pengguna cenderung frustrasi dan berhenti mencari kalau harus mengklik terlalu banyak lapisan menu hanya untuk sampai ke konten yang dicari.

**Output**

Audit kode dan struktur (input teks untuk laporan audit). Anggota diberi struktur navigasi contoh berikut, lalu diminta mengaudit dan mengusulkan perbaikannya.

Struktur navigasi yang perlu diaudit (menu sebuah aplikasi toko online fiktif):
```
Menu Utama
├── Home
├── Kategori Produk
│   ├── Data-Produk-Elektronik-2023
│   ├── barang_rumah
│   └── Misc
├── Akun
│   ├── Pengaturan Preferensi Notifikasi Lanjutan
│   └── Info
└── Bantuan
```

Pertanyaan audit: "Identifikasi minimal tiga masalah pelabelan atau pengkategorian pada struktur menu di atas (misalnya label yang tidak konsisten, label teknis yang membingungkan pengguna awam, kategori yang tumpang tindih atau tidak jelas cakupannya seperti 'Misc'). Tuliskan usulan struktur menu perbaikan versimu."

---

### 2.6.2. Sistem Desain: Design Token dan Design Pattern

**Isi Materi**

Design token adalah nilai-nilai desain dasar yang disimpan sebagai variabel bernama, misalnya warna, ukuran font, jarak (spacing), dan radius sudut, yang dipakai berulang di seluruh aplikasi. Alih-alih menulis kode warna `#3B82F6` berulang kali di banyak tempat, design token menyimpannya sekali sebagai misalnya `color-primary`, lalu dipakai ulang di mana pun dibutuhkan. Keuntungannya, kalau suatu saat warna utama aplikasi ingin diubah, cukup ubah satu token, seluruh bagian aplikasi yang memakainya otomatis ikut berubah konsisten.

Design pattern adalah solusi desain yang sudah teruji dan dipakai berulang untuk masalah antarmuka yang umum terjadi, misalnya pola modal (jendela pop-up) untuk konfirmasi aksi penting, pola kartu (card) untuk menampilkan ringkasan item dalam daftar, atau pola breadcrumb untuk menunjukkan posisi pengguna dalam hierarki halaman.

Keduanya saling melengkapi. Design token menjaga konsistensi visual level detail (warna, ukuran, jarak), sementara design pattern menjaga konsistensi struktural level lebih besar (bagaimana sebuah jenis interaksi disajikan). Aplikasi yang konsisten secara visual dan struktural terasa lebih profesional dan lebih mudah dipelajari pengguna, karena begitu mereka paham satu pola di satu bagian aplikasi, pola yang sama di bagian lain akan terasa familiar.

WEBI-SPACE sendiri menerapkan design token lewat dokumentasi `docs_design-tokens.md` sebagai acuan konsistensi visual identitas Retro-Tech di seluruh portalnya.

**Output**

Audit kode (input teks untuk laporan audit). Anggota diberi potongan kode CSS berikut yang tidak memakai design token, lalu diminta mengidentifikasi masalah dan menuliskan versi perbaikan memakai token.

Kode yang perlu diaudit:
```css
.tombol-simpan { background-color: #3B82F6; padding: 12px 20px; border-radius: 8px; }
.tombol-hapus { background-color: #3B82F6; padding: 12px 20px; border-radius: 8px; }
.kartu-produk { background-color: #3B82F6; padding: 12px 20px; border-radius: 4px; }
```

Pertanyaan audit: "Identifikasi masalah konsistensi pada kode di atas (perhatikan bahwa tombol hapus memakai warna yang sama dengan tombol simpan, padahal secara fungsi keduanya berbeda tingkat risiko, dan radius kartu-produk tidak konsisten dengan dua elemen lain). Tuliskan versi perbaikan memakai pendekatan design token (boleh dalam bentuk daftar variabel bernama beserta nilainya, lalu bagaimana tiap kelas CSS memakainya)."

---

### 2.6.3. Ragam Visualisasi Data pada Web

**Isi Materi**

Visualisasi data adalah cara menyajikan data mentah menjadi bentuk visual yang lebih mudah dipahami dan dimaknai dibanding sekadar tabel angka. Pemilihan jenis visualisasi yang tepat sangat bergantung pada jenis data dan pesan yang ingin disampaikan.

Grafik batang (bar chart) cocok untuk membandingkan nilai antar kategori yang berbeda, misalnya membandingkan jumlah penjualan antar produk. Grafik garis (line chart) cocok untuk menunjukkan tren atau perubahan nilai dari waktu ke waktu, misalnya pertumbuhan jumlah pengguna per bulan. Grafik lingkaran (pie chart) cocok untuk menunjukkan proporsi bagian terhadap keseluruhan, tapi sebaiknya dipakai hanya untuk sedikit kategori karena terlalu banyak potongan membuat perbandingan sulit dibaca. Heatmap cocok untuk menunjukkan intensitas atau kepadatan data dalam dua dimensi, misalnya heatmap aktivitas kontribusi seperti yang dipakai di halaman profil GitHub.

Kesalahan umum yang sering terjadi adalah memilih jenis visualisasi berdasarkan selera visual semata tanpa mempertimbangkan jenis data, misalnya memakai pie chart untuk data yang sebenarnya menunjukkan tren waktu, yang justru membuat pesan datanya menjadi lebih sulit dipahami, bukan lebih mudah.

WEBI-SPACE sendiri menerapkan heatmap aktivitas bergaya GitHub di halaman Profil, sebuah contoh nyata penerapan visualisasi data yang sesuai dengan jenis datanya (data aktivitas dari waktu ke waktu, dipetakan dalam grid harian).

**Output**

Latihan pemilihan visualisasi (input teks). Anggota diberi tiga set data fiktif, diminta menentukan jenis visualisasi paling tepat beserta alasannya.

**Data 1.** Jumlah anggota baru yang mendaftar WEBI-SPACE tiap bulan, dari Januari sampai Desember.
Pertanyaan: Jenis visualisasi apa yang paling tepat, dan kenapa?
Kunci acuan: grafik garis (line chart), karena menunjukkan tren perubahan dari waktu ke waktu.

**Data 2.** Proporsi anggota divisi Web Development yang tergabung di track Eksplorasi dibanding track Eksekusi.
Pertanyaan: Jenis visualisasi apa yang paling tepat, dan kenapa?
Kunci acuan: grafik lingkaran (pie chart), karena hanya dua kategori dan yang ditunjukkan adalah proporsi terhadap keseluruhan.

**Data 3.** Perbandingan jumlah submission Praktik yang dikumpulkan lima anggota Eksplorasi berbeda dalam satu bulan.
Pertanyaan: Jenis visualisasi apa yang paling tepat, dan kenapa?
Kunci acuan: grafik batang (bar chart), karena membandingkan nilai antar kategori (antar anggota) yang berbeda, bukan menunjukkan tren waktu maupun proporsi.

---

### 2.6.4. Prinsip Accessibility dalam Frontend

**Isi Materi**

Accessibility (aksesibilitas) dalam frontend berarti memastikan aplikasi bisa dipakai oleh pengguna seluas mungkin, termasuk pengguna dengan keterbatasan penglihatan, pendengaran, motorik, atau kognitif. Ini bukan fitur tambahan opsional, melainkan tanggung jawab dasar yang sering terlewat karena tidak terlihat langsung dampaknya bagi developer yang tidak memiliki keterbatasan tersebut.

Beberapa prinsip dasar yang wajib dipahami. Pertama, kontras warna yang cukup antara teks dan latar belakang, supaya pengguna dengan gangguan penglihatan tertentu (termasuk buta warna parsial) tetap bisa membaca konten dengan jelas. Kedua, teks alternatif (alt text) pada gambar, supaya pengguna yang memakai screen reader (alat bantu baca layar untuk tunanetra) tetap mendapat informasi tentang apa yang ditampilkan gambar tersebut. Ketiga, navigasi yang bisa dioperasikan penuh lewat keyboard, tanpa harus bergantung pada mouse, penting bagi pengguna dengan keterbatasan motorik. Keempat, struktur heading (H1, H2, H3, dan seterusnya) yang logis dan berurutan, membantu pengguna screen reader memahami hierarki konten tanpa harus melihat tampilan visualnya.

Standar acuan internasional yang umum dipakai adalah WCAG (Web Content Accessibility Guidelines), yang mendefinisikan tingkat kepatuhan aksesibilitas mulai dari level A (dasar) sampai AAA (paling ketat).

Mengabaikan accessibility bukan hanya soal etika inklusi, tapi juga berdampak langsung pada jangkauan pengguna aplikasi. Aplikasi yang tidak aksesibel secara otomatis kehilangan sebagian penggunanya tanpa developer pernah menyadarinya.

**Output**

Audit kode (input teks untuk laporan audit). Anggota diberi potongan kode HTML berikut, diminta mengidentifikasi masalah accessibility dan menuliskan perbaikannya.

Kode yang perlu diaudit:
```html
<div onclick="submitForm()">Kirim</div>
<img src="grafik-penjualan.png">
<h1>Judul Halaman</h1>
<h3>Sub Bagian Pertama</h3>
```

Pertanyaan audit: "Identifikasi minimal tiga masalah accessibility pada kode di atas (perhatikan penggunaan elemen `div` sebagai tombol yang tidak bisa diakses lewat keyboard secara default, gambar tanpa atribut `alt`, dan lompatan struktur heading dari H1 langsung ke H3 tanpa H2). Tuliskan versi perbaikan kode HTML di atas."

---

### 2.6.5. Mekanisme Peran Frontend dalam Tim Proyek

**Isi Materi**

Setelah memahami arsitektur informasi, sistem desain, visualisasi data, dan accessibility, unit ini menyintesiskan bagaimana seorang Frontend Developer benar-benar bekerja dalam mekanisme tim proyek sehari-hari.

Frontend Developer menerima rancangan dari UI/UX Designer, biasanya dalam bentuk file desain (seperti Figma), lalu menerjemahkannya menjadi kode yang benar-benar berjalan di browser. Proses ini bukan sekadar meniru tampilan piksel demi piksel, tapi juga mempertimbangkan bagaimana tampilan itu tetap responsive (menyesuaikan diri di berbagai ukuran layar) dan tetap accessible sesuai prinsip yang sudah dipelajari.

Frontend Developer juga berkomunikasi intensif dengan Backend Developer untuk menyepakati bentuk data yang akan dipertukarkan (kontrak API, akan dipelajari lebih detail di Modul 7), memastikan data yang dikirim backend bisa ditampilkan dengan benar di sisi frontend, dan sebaliknya data yang dikirim dari form frontend sesuai format yang diharapkan backend.

Selain itu, Frontend Developer bertanggung jawab menjaga performa tampilan, memastikan halaman tidak lambat dimuat meski datanya kompleks, dan bekerja sama dengan QA untuk memastikan tampilan berjalan konsisten di berbagai browser dan perangkat.

Memahami mekanisme kerja ini penting supaya anggota Eksplorasi yang nanti berpindah ke track Eksekusi sudah punya bayangan realistis tentang bagaimana perannya akan terhubung dengan peran-peran lain dalam proyek nyata.

**Output**

Studi kasus tertulis (input teks, minimal 150 kata). Skenario: "Kamu ditugaskan sebagai Frontend Developer untuk membangun halaman daftar tugas proyek. UI/UX Designer sudah memberi rancangan tampilan, tapi kamu baru sadar rancangan itu mengasumsikan setiap tugas hanya punya satu penanggung jawab, padahal Backend Developer menjelaskan bahwa dari sisi database, satu tugas bisa punya banyak penanggung jawab sekaligus."

Pertanyaan: "Sebagai Frontend Developer, langkah apa yang akan kamu ambil menghadapi ketidaksesuaian antara rancangan Designer dan struktur data dari Backend? Kepada siapa saja kamu perlu berkomunikasi, dan apa yang perlu didiskusikan?"

---

## 2.7. Modul 7: Arsitektur dan Prinsip Backend

### 2.7.1. Arsitektur Backend: Alur Request sampai Data Tersimpan

**Isi Materi**

Backend adalah bagian aplikasi yang bekerja di balik layar, tidak terlihat langsung oleh pengguna, tapi menangani seluruh logika bisnis dan pengolahan data. Memahami arsitektur backend berarti memahami perjalanan lengkap sebuah request sejak diterima sampai data akhirnya tersimpan atau dikembalikan sebagai response.

Alurnya biasanya melewati beberapa lapisan. Pertama, routing, yaitu bagian yang menentukan request yang masuk (misalnya request untuk menyimpan tugas baru) harus diarahkan ke bagian kode mana yang menanganinya. Kedua, controller, yaitu bagian yang menerima request tersebut dan mengoordinasikan apa yang harus dilakukan, tapi biasanya tidak menyimpan logika bisnis mendetail di dalamnya sendiri. Ketiga, lapisan logika bisnis (sering disebut service), tempat aturan-aturan bisnis sesungguhnya dijalankan, misalnya memeriksa apakah pengguna berhak melakukan aksi tertentu, atau menghitung poin yang harus diberikan. Keempat, lapisan model atau repository, yang berkomunikasi langsung dengan database untuk menyimpan atau mengambil data.

Pemisahan lapisan ini bukan formalitas semata. Memisahkan controller (menerima request) dari logika bisnis (memproses aturan) dan model (mengurus data) membuat kode lebih mudah diuji, lebih mudah diubah tanpa merusak bagian lain, dan lebih mudah dipahami anggota tim baru yang bergabung di tengah proyek. WEBI-SPACE sendiri menerapkan pemisahan ini secara konsisten, misalnya lewat `PointService` yang memusatkan seluruh logika perhitungan poin di satu tempat, bukan tersebar di banyak controller.

**Output**

Dokumen rancangan sistem (disajikan sebagai template isian). Anggota merancang alur backend untuk sebuah fitur sederhana pilihan sendiri (misalnya fitur "like" pada postingan, atau fitur "tambah ke keranjang" pada toko online), mengisi template berikut:

1. Nama fitur dan deskripsi singkat
2. Request apa yang dikirim dari frontend (metode dan data yang dibawa)
3. Apa yang dilakukan lapisan routing (endpoint apa yang dituju)
4. Apa yang dilakukan controller (langkah penerimaan dan pengecekan awal)
5. Aturan bisnis apa yang perlu dijalankan di lapisan logika bisnis (misalnya validasi, perhitungan, pengecekan izin)
6. Data apa yang akhirnya disimpan atau diubah di database
7. Response apa yang dikirim balik ke frontend

---

### 2.7.2. Prinsip Normalisasi dan Perancangan Skema Data

**Isi Materi**

Normalisasi data adalah proses menyusun struktur tabel database supaya data tidak disimpan berulang-ulang secara tidak perlu (redundan), dan supaya perubahan data hanya perlu dilakukan di satu tempat, bukan tersebar di banyak baris yang berpotensi menjadi tidak konsisten satu sama lain.

Prinsip dasarnya bisa dijelaskan lewat contoh. Bayangkan sebuah tabel pesanan yang menyimpan nama, alamat, dan nomor telepon pelanggan langsung di setiap baris pesanan. Kalau satu pelanggan memesan sepuluh kali, data nama dan alamatnya akan berulang sepuluh kali, dan kalau pelanggan itu pindah alamat, harus diubah di sepuluh baris berbeda, rawan ada yang terlewat sehingga datanya menjadi tidak konsisten.

Solusinya adalah memisahkan data pelanggan ke tabel tersendiri (misalnya tabel `pelanggan`), lalu tabel pesanan cukup menyimpan referensi (foreign key) ke tabel pelanggan tersebut. Dengan begitu, data pelanggan hanya tersimpan satu kali, dan perubahan alamat cukup dilakukan di satu tempat, otomatis berlaku untuk seluruh pesanan pelanggan itu.

Relasi antar tabel dalam database umumnya terbagi tiga jenis, one-to-one (satu-ke-satu, misalnya satu pengguna punya satu profil), one-to-many (satu-ke-banyak, misalnya satu pengguna punya banyak pesanan), dan many-to-many (banyak-ke-banyak, misalnya banyak proyek punya banyak anggota, dan satu anggota bisa terlibat di banyak proyek, biasanya butuh tabel perantara/pivot untuk menyimpan relasi ini).

Perancangan skema data yang baik sejak awal akan menghemat banyak masalah di kemudian hari, karena database yang berantakan jauh lebih sulit diperbaiki setelah aplikasi berjalan dan sudah berisi banyak data sungguhan, dibanding diperbaiki sebelum aplikasi diluncurkan.

**Output**

Latihan perancangan skema (disajikan sebagai template isian dan gambar/deskripsi relasi). Anggota diberi kasus berikut dan diminta merancang skema tabelnya.

Kasus: "Sebuah aplikasi perpustakaan mencatat buku, anggota perpustakaan, dan transaksi peminjaman. Satu buku bisa dipinjam berkali-kali oleh anggota berbeda dari waktu ke waktu (tapi hanya satu peminjam aktif dalam satu waktu untuk satu eksemplar buku). Satu anggota bisa meminjam banyak buku."

Pertanyaan:
1. Tabel apa saja yang perlu dibuat? Sebutkan nama tabel dan kolom-kolom utamanya.
2. Jenis relasi apa yang menghubungkan tabel buku, anggota, dan transaksi peminjaman (one-to-one, one-to-many, atau many-to-many)? Jelaskan alasannya.
3. Kalau data alamat anggota disimpan langsung di tabel transaksi peminjaman alih-alih di tabel anggota tersendiri, masalah apa yang berpotensi muncul?

---

### 2.7.3. Arsitektur API: Kontrak Frontend dan Backend

**Isi Materi**

API (Application Programming Interface) dalam konteks web adalah antarmuka yang memungkinkan frontend dan backend berkomunikasi lewat aturan yang sudah disepakati bersama, aturan itulah yang disebut kontrak API. Kontrak ini menentukan endpoint (alamat) yang bisa diakses, metode yang dipakai (GET untuk mengambil data, POST untuk mengirim data baru, PUT atau PATCH untuk mengubah data, DELETE untuk menghapus data), serta bentuk data yang dikirim dan diterima, biasanya dalam format JSON.

Kontrak API penting karena frontend dan backend seringkali dikerjakan orang atau tim berbeda, bahkan dikembangkan pada waktu yang tidak selalu bersamaan. Tanpa kontrak yang jelas dan disepakati lebih dulu, frontend bisa saja mengharapkan data dalam bentuk tertentu, sementara backend justru mengirim dalam bentuk lain, menyebabkan aplikasi gagal berfungsi meski masing-masing pihak merasa kodenya sudah benar.

Format response API yang baik biasanya konsisten, misalnya selalu menyertakan status (berhasil atau gagal), data (isi sesungguhnya), dan pesan (penjelasan tambahan bila diperlukan, terutama saat gagal). Dokumentasi API yang jelas, mencantumkan seluruh endpoint beserta contoh request dan response-nya, menjadi jembatan komunikasi yang sangat penting antara Frontend dan Backend Developer.

Kesalahan memahami atau mengabaikan kontrak API adalah salah satu sumber bug paling umum dalam kerja tim, karena bug semacam ini seringkali baru terlihat saat kedua sisi (frontend dan backend) sudah selesai dikerjakan terpisah dan mulai disatukan.

**Output**

Dokumen rancangan API (disajikan sebagai template isian). Anggota merancang kontrak API untuk sebuah fitur "menambahkan komentar pada sebuah postingan", mengisi template berikut:

1. Endpoint (contoh format: `/api/postingan/{id}/komentar`)
2. Metode HTTP yang dipakai (GET/POST/PUT/DELETE) beserta alasannya
3. Data yang dikirim frontend ke backend (contoh format JSON, sebutkan field dan tipe datanya, misalnya `isi_komentar` bertipe teks)
4. Contoh response yang dikembalikan backend jika berhasil (format JSON, sertakan status, data, pesan)
5. Contoh response yang dikembalikan backend jika gagal, misalnya kalau `isi_komentar` dikirim kosong (format JSON, sertakan status, pesan error yang jelas)

---

### 2.7.4. Mekanisme Peran Backend dalam Tim Proyek

**Isi Materi**

Melengkapi pemahaman arsitektur, sistem data, dan API, unit ini menyintesiskan bagaimana seorang Backend Developer benar-benar bekerja dalam mekanisme tim proyek sehari-hari.

Backend Developer bertanggung jawab menerjemahkan kebutuhan bisnis (dari Product Manager) menjadi aturan-aturan konkret dalam kode, misalnya aturan siapa yang boleh mengakses data tertentu, bagaimana poin dihitung, atau bagaimana status sebuah tugas berubah dari waktu ke waktu. Ini menuntut Backend Developer memahami logika bisnis secara mendalam, bukan sekadar menulis kode yang "jalan".

Backend Developer juga bertanggung jawab menjaga keamanan data, memastikan hanya pengguna yang berhak yang bisa mengakses atau mengubah data tertentu (akan dibahas mendalam di Modul 8), dan menjaga performa, memastikan proses pengambilan data dari database tidak lambat meski jumlah data terus bertambah seiring aplikasi dipakai lebih banyak orang.

Kolaborasi dengan Frontend Developer terjadi lewat kontrak API yang sudah disepakati, sementara kolaborasi dengan QA terjadi lewat pengujian skenario-skenario yang mungkin gagal, termasuk skenario yang jarang terpikirkan seperti data kosong, input yang sangat besar, atau permintaan yang datang bersamaan dalam jumlah banyak (concurrent request).

Memahami mekanisme kerja ini penting supaya anggota Eksplorasi yang nanti berpindah ke track Eksekusi bisa langsung berkontribusi dengan bayangan realistis tentang tanggung jawab yang akan diembannya sebagai Backend Developer dalam proyek nyata WEBI-SPACE.

**Output**

Studi kasus tertulis (input teks, minimal 150 kata). Skenario: "Kamu ditugaskan sebagai Backend Developer untuk fitur pemberian poin saat anggota menyelesaikan submission Praktik. Setelah fitur ini berjalan beberapa minggu, ditemukan bahwa ada anggota yang mendapat poin dobel karena mengirim submission yang sama dua kali dalam waktu berdekatan akibat koneksi internet yang lambat membuat mereka menekan tombol kirim berkali-kali."

Pertanyaan: "Sebagai Backend Developer, aturan atau pengecekan apa yang seharusnya ada di lapisan logika bisnis untuk mencegah masalah ini? Jelaskan juga kenapa masalah ini sebaiknya dicegah di sisi backend, bukan hanya mengandalkan frontend menonaktifkan tombol setelah diklik."

---

## 2.8. Modul 8: Keamanan Aplikasi Web

### 2.8.1. Ancaman Injeksi: SQL Injection dan Command Injection

**Isi Materi**

Serangan injeksi terjadi ketika penyerang menyisipkan perintah berbahaya lewat input yang seharusnya hanya berisi data biasa, memanfaatkan celah di mana aplikasi memproses input pengguna sebagai bagian dari perintah yang dijalankan sistem, alih-alih memperlakukannya murni sebagai data.

SQL Injection terjadi ketika input pengguna langsung digabungkan ke dalam perintah query database tanpa penyaringan, memungkinkan penyerang menyisipkan perintah SQL tambahan. Misalnya, sebuah form login yang menyusun query dengan menggabungkan langsung nilai input pengguna, memungkinkan penyerang mengetik input khusus yang membuat query tersebut selalu bernilai benar, sehingga bisa login tanpa mengetahui password yang sesungguhnya, atau bahkan mengambil seluruh isi database.

Command Injection terjadi ketika aplikasi menjalankan perintah sistem operasi menggunakan input pengguna tanpa penyaringan, memungkinkan penyerang menyisipkan perintah tambahan yang dijalankan langsung oleh server, misalnya perintah untuk menghapus file atau membuka akses tidak sah ke server.

Solusi teknis untuk mencegah kedua ancaman ini adalah dengan tidak pernah menggabungkan input pengguna langsung ke dalam perintah, melainkan memakai teknik parameterized query atau prepared statement untuk SQL (di mana input selalu diperlakukan murni sebagai data, bukan bagian dari perintah), serta menghindari sama sekali menjalankan perintah sistem operasi berdasarkan input pengguna mentah, atau jika benar-benar diperlukan, melakukan validasi dan penyaringan sangat ketat.

**Output**

Audit keamanan (input teks untuk laporan audit). Anggota diberi potongan kode berikut yang rentan SQL Injection, diminta mengidentifikasi celahnya dan menjelaskan solusinya.

Kode yang perlu diaudit (contoh pseudocode PHP):
```php
$username = $_POST['username'];
$password = $_POST['password'];
$query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
$result = mysqli_query($koneksi, $query);
```

Pertanyaan audit: "Jelaskan bagaimana penyerang bisa memanfaatkan kode di atas untuk login tanpa mengetahui password yang benar (jelaskan konsepnya, tidak perlu menuliskan payload/string serangan yang sesungguhnya). Jelaskan solusi teknis (prepared statement/parameterized query) yang seharusnya dipakai untuk menutup celah ini."

---

### 2.8.2. Ancaman Sisi Klien: XSS dan CSRF

**Isi Materi**

Cross-Site Scripting (XSS) terjadi ketika penyerang berhasil menyisipkan kode skrip berbahaya ke dalam halaman web yang kemudian dijalankan di browser pengguna lain yang mengunjungi halaman tersebut, memanfaatkan celah di mana aplikasi menampilkan input pengguna secara langsung tanpa membersihkan atau menyaringnya lebih dulu. Contoh dampaknya, skrip yang disisipkan bisa mencuri data sesi pengguna lain atau mengarahkan mereka ke halaman palsu.

Cross-Site Request Forgery (CSRF) terjadi ketika penyerang menipu browser pengguna yang sedang login di sebuah aplikasi untuk secara tidak sadar mengirim permintaan (misalnya mengubah password atau melakukan transaksi) tanpa sepengetahuan pengguna tersebut, memanfaatkan fakta bahwa browser otomatis menyertakan data sesi login setiap kali mengirim permintaan ke aplikasi itu, dari halaman manapun permintaan itu dipicu.

Solusi untuk XSS adalah selalu membersihkan (sanitize) dan meng-encode data yang berasal dari input pengguna sebelum ditampilkan kembali di halaman, memastikan skrip yang disisipkan diperlakukan sebagai teks biasa, bukan dijalankan sebagai kode. Solusi untuk CSRF adalah memakai token CSRF, yaitu kode unik rahasia yang disertakan di setiap form dan diperiksa server, memastikan permintaan benar-benar berasal dari halaman aplikasi itu sendiri, bukan dipicu diam-diam dari halaman lain.

Kedua ancaman ini menunjukkan pentingnya tidak pernah mempercayai input pengguna secara membabi buta, prinsip yang berlaku di seluruh aspek keamanan aplikasi web.

**Output**

Audit keamanan (input teks untuk laporan audit). Anggota diberi dua potongan kode berikut, diminta mengidentifikasi ancaman spesifik pada masing-masing dan solusinya.

**Kode 1** (menampilkan komentar pengguna):
```html
<div class="komentar"><?php echo $_POST['isi_komentar']; ?></div>
```
Pertanyaan: Ancaman apa yang mengintai kode ini, dan bagaimana solusinya?
Kunci acuan: XSS, karena input pengguna langsung ditampilkan tanpa disaring/di-encode. Solusinya adalah meng-encode output (misalnya lewat fungsi seperti `htmlspecialchars`) sebelum ditampilkan.

**Kode 2** (form ubah password tanpa token):
```html
<form action="/ubah-password" method="POST">
  <input type="password" name="password_baru">
  <button type="submit">Ubah Password</button>
</form>
```
Pertanyaan: Ancaman apa yang mengintai form ini karena tidak menyertakan token keamanan, dan bagaimana solusinya?
Kunci acuan: CSRF, karena tidak ada token unik yang memverifikasi permintaan benar-benar berasal dari form aplikasi itu sendiri. Solusinya adalah menyertakan CSRF token tersembunyi di form dan memverifikasinya di server sebelum memproses permintaan.

---

### 2.8.3. Kelemahan Autentikasi dan Manajemen Sesi

**Isi Materi**

Autentikasi adalah proses memverifikasi identitas pengguna, biasanya lewat kombinasi username dan password. Manajemen sesi adalah cara aplikasi mengingat bahwa seorang pengguna sudah login, sehingga tidak perlu memasukkan password berulang kali di setiap halaman yang dibuka.

Kelemahan umum pertama adalah penyimpanan password yang tidak aman, misalnya menyimpan password dalam bentuk teks biasa (plain text) di database, sehingga kalau database bocor, seluruh password pengguna langsung terekspos. Solusinya adalah selalu melakukan hashing pada password sebelum disimpan, menggunakan algoritma hashing yang dirancang khusus untuk password (seperti bcrypt), yang membuat proses membalikkan hash menjadi password asli menjadi sangat sulit secara komputasi.

Kelemahan kedua adalah kebijakan password yang lemah, mengizinkan password yang terlalu pendek atau terlalu umum, memudahkan penyerang menebaknya lewat teknik brute force (mencoba banyak kombinasi secara otomatis) atau memakai daftar password umum yang sudah bocor sebelumnya.

Kelemahan ketiga adalah manajemen sesi yang buruk, misalnya sesi login yang tidak pernah kedaluwarsa, atau ID sesi yang mudah ditebak, memungkinkan penyerang membajak sesi pengguna lain. Solusinya termasuk menetapkan waktu kedaluwarsa sesi yang wajar, menghasilkan ID sesi yang acak dan sulit ditebak, serta selalu membuat ulang ID sesi saat pengguna berhasil login.

**Output**

Audit keamanan (input teks untuk laporan audit). Anggota diberi skenario berikut, diminta mengidentifikasi kelemahan dan solusinya.

Skenario: "Sebuah aplikasi menyimpan password pengguna dalam bentuk teks biasa di database (bisa dibaca langsung tanpa proses apapun), mengizinkan password sependek tiga karakter, dan sesi login pengguna tidak pernah kedaluwarsa sampai mereka klik tombol logout secara manual."

Pertanyaan: "Identifikasi tiga kelemahan keamanan pada skenario di atas. Untuk masing-masing kelemahan, jelaskan solusi teknis yang seharusnya diterapkan (mengacu pada konsep hashing, kebijakan password, dan manajemen kedaluwarsa sesi yang sudah dipelajari)."

---

### 2.8.4. Kesalahan Konfigurasi dan Kebocoran Data Sensitif

**Isi Materi**

Kesalahan konfigurasi adalah salah satu penyebab kebocoran data paling umum, seringkali bukan karena kode aplikasinya yang salah, tapi karena pengaturan di sekitar aplikasi yang lengah. Beberapa contoh konkret perlu dikenali.

Pertama, file konfigurasi yang berisi kredensial sensitif (seperti password database atau API key) ikut terunggah ke repository publik seperti GitHub, sehingga siapapun bisa melihat dan menyalahgunakannya. Ini kenapa file seperti `.env` yang berisi kredensial harus selalu dimasukkan ke `.gitignore` agar tidak pernah ikut ter-commit.

Kedua, pesan error yang terlalu detail ditampilkan langsung ke pengguna di lingkungan produksi, misalnya menampilkan struktur query database atau lokasi file server saat terjadi error, informasi yang sebenarnya sangat berguna bagi penyerang untuk memahami struktur sistem dan mencari celah lebih lanjut.

Ketiga, folder atau file yang seharusnya bersifat privat justru bisa diakses langsung lewat URL karena pengaturan izin akses (permission) yang tidak tepat, misalnya folder penyimpanan file unggahan pengguna yang bisa diakses siapapun tanpa autentikasi.

Keempat, penggunaan versi software atau library yang sudah usang dan diketahui memiliki celah keamanan yang belum ditambal (patch), padahal pembaruan yang menutup celah tersebut sudah tersedia tapi belum dipasang.

Prinsip umumnya, keamanan bukan cuma soal kode aplikasi yang ditulis, tapi juga soal bagaimana keseluruhan sistem di sekitarnya dikonfigurasi dan dijaga tetap mutakhir.

**Output**

Audit keamanan (input teks untuk laporan audit). Anggota diberi daftar kondisi berikut, diminta menandai mana yang berisiko dan menjelaskan risikonya.

Kondisi yang perlu diaudit:
1. File `.env` berisi password database ikut ter-commit dan terlihat di repository GitHub publik
2. Halaman error di lingkungan produksi menampilkan detail lengkap query SQL yang gagal dijalankan
3. Folder `storage/pribadi/` yang berisi dokumen pribadi anggota bisa diakses langsung lewat URL tanpa login
4. Aplikasi memakai versi library yang dirilis tiga tahun lalu dan sudah ada tiga pembaruan keamanan sejak saat itu yang belum dipasang

Pertanyaan: "Untuk setiap kondisi di atas, jelaskan risiko konkretnya dan langkah perbaikan yang seharusnya dilakukan."

---

### 2.8.5. Studi Kasus Kebocoran Data Nyata

**Isi Materi**

Unit ini menyintesiskan empat unit sebelumnya lewat pembelajaran dari kasus kebocoran data yang benar-benar terjadi di dunia nyata, memperlihatkan bahwa ancaman-ancaman yang sudah dipelajari bukan sekadar teori di buku, tapi benar-benar menyebabkan kerugian nyata bagi perusahaan dan jutaan pengguna.

Banyak kasus kebocoran data besar yang terdokumentasi publik ternyata disebabkan kombinasi dari kelemahan-kelemahan yang sudah dipelajari di unit sebelumnya, bukan serangan super canggih yang mustahil dicegah. Kasus umum yang berulang polanya termasuk password yang disimpan tanpa hashing yang layak, celah SQL Injection yang tidak ditambal bertahun-tahun meski sudah diketahui, kesalahan konfigurasi server yang membuka akses ke database secara tidak sengaja, dan penggunaan software usang yang celahnya sudah diketahui publik tapi belum ditambal perusahaan bersangkutan.

Pelajaran pentingnya, kebocoran data besar jarang disebabkan satu kesalahan tunggal yang sangat canggih, melainkan akumulasi dari beberapa kelemahan dasar yang masing-masing sebenarnya bisa dicegah dengan praktik keamanan yang sudah dipelajari di modul ini. Ini menegaskan bahwa memahami dan menerapkan prinsip dasar keamanan secara konsisten jauh lebih penting daripada mengejar solusi keamanan yang rumit dan canggih tapi mengabaikan dasar-dasarnya.

**Output**

Esai riset singkat (input teks, minimal 150 kata). Anggota mencari secara mandiri satu kasus kebocoran data perusahaan yang pernah dipublikasikan secara luas di media (boleh perusahaan Indonesia atau internasional), lalu menulis analisis singkat.

Pertanyaan panduan:
1. Perusahaan atau layanan apa yang mengalami kebocoran data tersebut, dan kapan?
2. Berdasarkan yang kamu baca, penyebab teknis apa yang paling mungkin menjadi akar masalahnya (kaitkan dengan ancaman-ancaman yang sudah dipelajari di Modul 8, seperti SQL Injection, kesalahan konfigurasi, atau autentikasi lemah)?
3. Menurutmu, langkah pencegahan apa yang seharusnya sudah diterapkan perusahaan tersebut sebelum insiden terjadi?

---

### 2.8.6. Prinsip Berpikir Defensif dalam Membangun Aplikasi

**Isi Materi**

Setelah mempelajari berbagai ancaman spesifik, unit penutup Modul 8 ini merangkumnya menjadi satu prinsip berpikir menyeluruh yang perlu tertanam dalam cara anggota membangun aplikasi ke depannya, disebut defensive programming atau berpikir defensif.

Prinsip pertama, jangan pernah mempercayai input darimanapun asalnya, baik dari pengguna, dari aplikasi lain, maupun dari API pihak ketiga, selalu validasi dan saring input sebelum diproses lebih lanjut. Prinsip kedua, terapkan prinsip least privilege, artinya berikan akses seminimal mungkin yang benar-benar dibutuhkan untuk setiap peran atau komponen sistem, jangan memberi akses lebih luas dari yang diperlukan hanya demi kepraktisan.

Prinsip ketiga, asumsikan bahwa kesalahan akan terjadi, siapkan penanganan error yang baik (tidak menampilkan detail sensitif ke pengguna, tapi tetap mencatat detail itu untuk keperluan debugging internal). Prinsip keempat, terapkan pertahanan berlapis (defense in depth), jangan bergantung pada satu lapisan keamanan saja, karena kalau satu lapisan gagal ditembus, lapisan lain masih bisa mencegah dampak lebih jauh.

Prinsip kelima, selalu ikuti pembaruan keamanan dari teknologi yang dipakai, dan jangan menunda pemasangan pembaruan yang menutup celah yang sudah diketahui publik. Prinsip berpikir defensif ini bukan checklist sekali jalan, melainkan kebiasaan berpikir yang harus terus dipraktikkan setiap kali menulis kode baru, termasuk saat mengevaluasi kode hasil AI-generate yang mungkin terlihat berfungsi tapi belum tentu memikirkan aspek keamanan sama sekali.

**Output**

Audit keamanan menyeluruh (input teks, sintesis akhir modul). Anggota diberi deskripsi fitur berikut, diminta mengevaluasinya memakai kelima prinsip berpikir defensif yang sudah dipelajari.

Deskripsi fitur: "Sebuah fitur upload foto profil, di mana pengguna mengunggah file gambar, lalu sistem langsung menyimpan file itu dengan nama asli yang diberikan pengguna ke folder publik di server, tanpa pengecekan tipe file, tanpa batas ukuran, dan file itu langsung bisa diakses semua orang lewat URL."

Pertanyaan: "Evaluasi fitur ini berdasarkan kelima prinsip berpikir defensif (validasi input, least privilege, penanganan error, pertahanan berlapis, pembaruan berkala). Untuk masing-masing prinsip yang relevan, jelaskan risiko yang mengintai dan perbaikan konkret yang perlu dilakukan pada fitur ini."

---

## 2.9. Modul 9: Topik Lanjutan Pengembangan Web

### 2.9.1. Arsitektur Progressive Web App

**Isi Materi**

Progressive Web App (PWA) adalah pendekatan membangun aplikasi web sehingga terasa dan berfungsi mirip aplikasi native (aplikasi yang diinstal dari App Store atau Play Store), meski tetap dijalankan lewat teknologi web biasa. PWA menjadi jembatan antara kemudahan distribusi aplikasi web (tidak perlu instalasi lewat toko aplikasi) dengan pengalaman pengguna yang lebih baik layaknya aplikasi native.

Tiga komponen teknis utama PWA. Pertama, service worker, yaitu skrip yang berjalan terpisah di latar belakang browser, memungkinkan aplikasi tetap berfungsi sebagian meski koneksi internet terputus, lewat mekanisme caching (menyimpan salinan data atau tampilan sebelumnya). Kedua, web app manifest, yaitu file konfigurasi yang mendefinisikan bagaimana aplikasi tampil saat "diinstal" ke perangkat pengguna, termasuk ikon, nama, dan warna tema. Ketiga, HTTPS, PWA mewajibkan koneksi aman karena service worker punya akses cukup dalam ke browser sehingga harus dipastikan tidak disalahgunakan lewat koneksi yang tidak terenkripsi.

Manfaat konkret PWA termasuk kemampuan bekerja offline atau dengan koneksi tidak stabil, kemampuan ditambahkan ke layar utama perangkat pengguna layaknya aplikasi biasa, dan performa yang lebih responsif berkat caching, tanpa pengguna harus mengunduh dari toko aplikasi maupun developer harus melalui proses review yang panjang dari platform seperti App Store.

Memahami PWA penting sebagai pembeda antara aplikasi web yang sekadar "selesai dibangun" dengan aplikasi web yang benar-benar dirancang matang untuk pengalaman pengguna nyata di berbagai kondisi jaringan.

**Output**

Dokumen rancangan (disajikan sebagai template isian). Anggota merancang penerapan PWA untuk sebuah aplikasi web sederhana pilihan sendiri, mengisi template berikut:

1. Nama aplikasi dan fitur utamanya
2. Fitur apa yang tetap perlu berfungsi meski aplikasi offline (misalnya melihat data yang sudah pernah dimuat sebelumnya), dan data apa yang perlu di-cache oleh service worker untuk mendukung itu
3. Isi web app manifest untuk aplikasi ini (nama aplikasi, deskripsi ikon yang sesuai, warna tema utama)
4. Satu risiko atau keterbatasan yang perlu diwaspadai kalau menerapkan PWA untuk aplikasi ini (misalnya data yang di-cache menjadi usang/tidak sinkron dengan server)

---

### 2.9.2. Mekanisme Integrasi API Pihak Ketiga

**Isi Materi**

Banyak aplikasi modern tidak membangun semua fiturnya dari nol, melainkan mengintegrasikan layanan pihak ketiga lewat API, misalnya API peta (seperti Google Maps), API cuaca, API pengiriman pesan (seperti WhatsApp Business API), atau API model AI (seperti yang dipakai WEBI-SPACE untuk fitur WEBI Chat lewat Gemini API).

Mekanisme integrasi umumnya melibatkan beberapa langkah. Pertama, mendaftar dan mendapatkan API key, yaitu kode rahasia unik yang mengidentifikasi aplikasi yang berhak mengakses layanan tersebut, sekaligus dipakai penyedia layanan untuk menghitung pemakaian dan tagihan biaya. Kedua, membaca dokumentasi API untuk memahami endpoint yang tersedia, format request yang diharapkan, dan format response yang akan diterima. Ketiga, menangani rate limit, yaitu batas jumlah permintaan yang boleh dikirim dalam periode waktu tertentu, penting diperhatikan supaya aplikasi tidak tiba-tiba berhenti berfungsi karena melebihi batas yang diizinkan.

Keamanan menjadi perhatian khusus dalam integrasi API pihak ketiga. API key harus selalu disimpan di sisi server (backend), tidak pernah ditulis langsung di kode frontend yang bisa dilihat siapapun lewat DevTools browser, karena API key yang bocor bisa disalahgunakan orang lain dan menyebabkan tagihan biaya membengkak atau data disalahgunakan.

Kegagalan menangani skenario ketika API pihak ketiga sedang bermasalah atau lambat merespons juga perlu dipikirkan sejak desain awal, aplikasi sebaiknya tetap punya penanganan yang baik (misalnya pesan error yang jelas) alih-alih ikut mati total hanya karena satu layanan eksternal sedang bermasalah.

**Output**

Dokumen rancangan (disajikan sebagai template isian). Anggota merancang integrasi salah satu API pihak ketiga (pilih salah satu: API cuaca, API peta, atau API model AI) untuk sebuah fitur aplikasi sederhana, mengisi template berikut:

1. API pihak ketiga yang dipilih dan fitur yang akan dibangun
2. Data apa yang perlu dikirim ke API tersebut, dan data apa yang diharapkan diterima kembali
3. Di lapisan mana (frontend atau backend) API key seharusnya disimpan, dan kenapa
4. Apa yang seharusnya terjadi di aplikasi kalau API pihak ketiga tersebut sedang tidak merespons atau error, supaya aplikasi tidak ikut gagal total

---

### 2.9.3. Konsep Dasar Payment Gateway

**Isi Materi**

Payment gateway adalah layanan yang menjembatani transaksi pembayaran online antara aplikasi, pengguna, dan lembaga keuangan (bank atau penyedia dompet digital), menangani seluruh proses sensitif pemrosesan pembayaran sehingga aplikasi tidak perlu (dan sebaiknya tidak pernah) menangani sendiri data kartu atau rekening pengguna secara langsung.

Alur dasarnya secara konsep, pengguna memilih metode pembayaran di aplikasi, aplikasi mengarahkan proses pembayaran itu ke payment gateway (baik lewat halaman terpisah milik gateway, atau lewat komponen yang disediakan gateway dan ditanam di halaman aplikasi), payment gateway memproses transaksi langsung dengan lembaga keuangan terkait, lalu mengirim notifikasi hasil transaksi (berhasil atau gagal) kembali ke aplikasi lewat mekanisme yang disebut webhook, yaitu API yang dipanggil otomatis oleh payment gateway untuk memberi tahu aplikasi tentang status transaksi terbaru.

Prinsip keamanan paling penting dalam topik ini, aplikasi sebaiknya tidak pernah menyimpan sendiri data sensitif seperti nomor kartu kredit lengkap di database miliknya, karena ini menciptakan tanggung jawab keamanan dan kepatuhan regulasi (seperti standar PCI DSS) yang sangat berat. Sebagai gantinya, aplikasi mengandalkan payment gateway yang sudah punya sertifikasi keamanan resmi untuk menangani data sensitif tersebut.

Memahami konsep dasar ini penting bagi anggota Eksplorasi supaya ke depannya, kalau proyek Eksekusi membutuhkan fitur pembayaran, mereka sudah punya bayangan konseptual yang benar tentang pembagian tanggung jawab antara aplikasi dan payment gateway, bukan mencoba membangun sistem pemrosesan pembayaran sendiri dari nol yang berisiko tinggi.

**Output**

Dokumen rancangan (disajikan sebagai template isian). Anggota merancang alur pembayaran untuk sebuah fitur "donasi online" sederhana, mengisi template berikut:

1. Langkah yang dilakukan pengguna di aplikasi sebelum diarahkan ke payment gateway
2. Data apa yang aplikasi kirim ke payment gateway (misalnya jumlah donasi, ID transaksi internal), dan data sensitif apa yang TIDAK boleh disimpan sendiri oleh aplikasi
3. Apa yang terjadi setelah payment gateway selesai memproses transaksi (jelaskan peran webhook dalam memberi tahu status transaksi ke aplikasi)
4. Apa yang seharusnya ditampilkan ke pengguna jika status transaksi ternyata gagal

---

### 2.9.4. Strategi dan Metode Deployment Modern

**Isi Materi**

Deployment adalah proses membuat aplikasi yang sudah dibangun bisa diakses publik lewat internet, memindahkannya dari lingkungan pengembangan (development) ke lingkungan produksi (production) yang benar-benar dipakai pengguna sungguhan.

Beberapa metode deployment modern yang umum dipahami. Shared hosting, di mana satu server fisik dipakai bersama banyak pengguna berbeda, biayanya murah tapi kontrol dan performanya terbatas, cocok untuk aplikasi skala kecil sampai menengah (WEBI-SPACE sendiri berjalan di lingkungan shared hosting Rumahweb). Virtual Private Server (VPS), memberi kontrol lebih penuh atas server virtual yang dipakai sendiri, cocok untuk aplikasi yang butuh konfigurasi lebih spesifik. Platform-as-a-Service (PaaS) seperti Vercel atau Railway, menyederhanakan proses deployment lewat integrasi otomatis dengan repository Git, developer cukup push kode dan platform menangani sisanya. Containerization dengan teknologi seperti Docker, mengemas aplikasi beserta seluruh dependensinya menjadi satu paket konsisten yang bisa dijalankan di lingkungan manapun tanpa masalah "kok di komputer saya jalan tapi di server tidak".

Konsep CI/CD (Continuous Integration/Continuous Deployment) juga penting dipahami, yaitu praktik otomatisasi pengujian dan deployment setiap kali ada perubahan kode baru yang di-push, mengurangi kesalahan manual dan mempercepat siklus rilis fitur baru.

Pemilihan strategi deployment yang tepat bergantung pada skala aplikasi, anggaran, dan tingkat kontrol yang dibutuhkan, bukan sekadar mengikuti tren tanpa mempertimbangkan kebutuhan nyata proyek.

**Output**

Dokumen rancangan (disajikan sebagai template isian). Anggota merancang strategi deployment untuk aplikasi hasil proyek akhirnya nanti di Modul 10, mengisi template berikut:

1. Metode deployment yang dipilih (shared hosting/VPS/PaaS/lainnya) beserta alasannya, dipertimbangkan dari sisi biaya, skala, dan tingkat kontrol yang dibutuhkan
2. Langkah-langkah deployment secara garis besar yang perlu dilakukan (dari kode selesai sampai bisa diakses publik lewat URL)
3. Apa yang perlu dipersiapkan agar proses deployment ke depannya bisa lebih otomatis (kaitkan dengan konsep CI/CD yang sudah dipelajari, meski implementasi penuhnya belum wajib dilakukan di kurikulum ini)

---

## 2.10. Modul 10: Proyek Akhir dan Portofolio

### 2.10.1. Perencanaan Proyek Akhir

**Isi Materi**

Modul 10 adalah puncak dari seluruh kurikulum Eksplorasi, tempat anggota mensintesiskan semua yang sudah dipelajari dari Modul 1 sampai Modul 9 menjadi satu karya nyata yang bisa dipamerkan sebagai portofolio. Unit ini menekankan pentingnya perencanaan matang sebelum mulai membangun, sebuah kebiasaan yang membedakan proyek yang selesai dengan baik dari proyek yang terbengkalai di tengah jalan.

Perencanaan yang baik dimulai dari memilih ide proyek yang realistis untuk diselesaikan dalam waktu yang tersedia, lebih baik proyek kecil yang selesai penuh dan rapi dibanding proyek ambisius yang berakhir setengah jadi. Ide yang baik biasanya juga mencerminkan minat pribadi anggota, karena minat ini menjadi motivasi yang membantu bertahan sampai proyek selesai.

Perencanaan mencakup menentukan fitur inti (fitur yang benar-benar wajib ada supaya proyek berfungsi dan bermakna) dan memisahkannya dari fitur tambahan (fitur "bagus kalau ada" yang bisa ditambahkan belakangan kalau waktu masih tersisa). Anggota juga perlu menentukan tech stack yang akan dipakai, mengacu kembali pada pemahaman dari Modul 2, dan memastikan tech stack itu memang dikuasai atau realistis dipelajari dalam waktu pengerjaan proyek.

Menyusun timeline sederhana juga penting, membagi pengerjaan proyek ke beberapa tahap dengan target waktu masing-masing, supaya proyek tidak dikerjakan mendadak di akhir waktu yang tersisa.

**Output**

Dokumen rancangan proyek akhir (disajikan sebagai template isian, menjadi dasar bagi unit-unit berikutnya di modul ini).

Template:
1. Nama dan deskripsi singkat proyek (apa masalah atau kebutuhan yang dijawab proyek ini)
2. Fitur inti (wajib ada, maksimal 3-5 fitur)
3. Fitur tambahan (opsional, kalau waktu memungkinkan)
4. Tech stack yang akan dipakai (frontend, backend jika ada, database jika ada), mengacu pada Modul 2
5. Timeline pengerjaan sederhana (bagi ke minimal 3 tahap dengan target waktu masing-masing, misalnya tahap perancangan, tahap pembangunan, tahap deployment dan penyempurnaan)

---

### 2.10.2. Eksekusi dan Deployment Proyek

**Isi Materi**

Setelah rancangan matang, unit ini masuk ke tahap eksekusi nyata, membangun proyek sesuai rencana yang sudah disusun di unit sebelumnya, lalu men-deploy-nya agar bisa diakses publik lewat internet.

Selama tahap membangun, penting menerapkan seluruh prinsip yang sudah dipelajari sepanjang kurikulum, bukan sekadar membuat sesuatu yang "terlihat jalan". Ini termasuk menerapkan struktur kode yang rapi dan tidak rapuh (Modul 1), memakai Git secara disiplin dengan histori commit yang bermakna sepanjang proses pembangunan (Modul 5), menerapkan prinsip frontend seperti accessibility dan sistem desain yang konsisten kalau proyek melibatkan tampilan (Modul 6), menerapkan prinsip backend seperti skema data yang ternormalisasi kalau proyek melibatkan penyimpanan data (Modul 7), dan menerapkan prinsip keamanan dasar seperti validasi input (Modul 8).

Tahap deployment mengikuti strategi yang sudah dirancang di Modul 9, memindahkan proyek dari lingkungan pengembangan ke lingkungan yang bisa diakses publik. Penting untuk menguji proyek yang sudah di-deploy secara menyeluruh, memastikan seluruh fitur inti benar-benar berfungsi di lingkungan produksi, bukan cuma berfungsi baik di komputer sendiri.

Proses eksekusi jarang berjalan mulus seratus persen sesuai rencana, dan itu wajar. Bagian penting dari unit ini adalah kemampuan beradaptasi, menyesuaikan rencana ketika ternyata ada kendala teknis yang tidak terduga, tanpa kehilangan arah dari tujuan utama proyek.

**Output**

Praktik langsung dengan pelaporan (input teks dan tautan). Anggota membangun proyek sesuai rancangan dari unit 10.1, lalu men-deploy-nya, kemudian melaporkan hasilnya lewat pertanyaan berikut:

1. "Tempelkan tautan (URL) proyek yang sudah live dan bisa diakses publik, beserta tautan repository GitHub-nya."
2. "Ceritakan satu kendala teknis nyata yang kamu temui selama membangun atau men-deploy proyek ini, dan bagaimana kamu menyelesaikannya."
3. "Dari seluruh prinsip yang sudah dipelajari sepanjang kurikulum (struktur kode, Git, frontend, backend, keamanan), sebutkan satu prinsip yang benar-benar kamu terapkan secara sadar di proyek ini, dan jelaskan bagaimana penerapannya."

---

### 2.10.3. Penyusunan Portofolio

**Isi Materi**

Proyek yang bagus tidak akan dilihat orang lain kalau tidak ditampilkan dengan baik. Unit ini membahas cara menyusun portofolio pribadi, sebuah halaman web yang menampilkan diri dan karya-karya anggota kepada dunia luar, termasuk kepada calon pemberi kerja atau kolaborator di masa depan.

Portofolio yang efektif biasanya memuat beberapa bagian inti. Bagian tentang diri (about), memperkenalkan siapa pemiliknya secara singkat dan apa yang ditekuni. Bagian keahlian (skills), menampilkan teknologi dan bahasa pemrograman yang dikuasai. Bagian karya (projects), bagian paling penting, menampilkan proyek-proyek yang sudah dibuat lengkap dengan deskripsi singkat, teknologi yang dipakai, tautan demo langsung, dan tautan repository kodenya. Bagian kontak, memudahkan orang yang tertarik untuk menghubungi.

Prinsip penulisan deskripsi proyek yang baik, jelaskan bukan cuma apa yang dibuat, tapi juga masalah apa yang diselesaikan proyek itu dan keputusan teknis penting apa yang diambil selama membangunnya. Ini menunjukkan proses berpikir di balik proyek, bukan sekadar hasil akhirnya, sesuatu yang sangat dihargai siapapun yang meninjau portofolio, karena menunjukkan pemahaman, bukan cuma kemampuan meniru.

Portofolio itu sendiri, karena berbentuk halaman web, juga menjadi kesempatan langsung mempraktikkan prinsip HTML, CSS, dan accessibility yang sudah dipelajari di Modul 6, sekaligus menjadi bukti nyata kemampuan teknis pemiliknya.

**Output**

Praktik langsung dengan pelaporan (input teks dan tautan). Anggota membangun halaman portofolio pribadi memuat keempat bagian yang sudah dijelaskan (tentang diri, keahlian, karya, kontak), memasukkan minimal proyek yang sudah dibuat di unit 10.2, lalu men-deploy-nya (misalnya lewat GitHub Pages).

Pertanyaan pelaporan: "Tempelkan URL portofoliomu yang sudah live. Jelaskan singkat bagaimana kamu menuliskan deskripsi proyek di portofolio ini, apakah sudah mencakup masalah yang diselesaikan dan keputusan teknis penting, bukan cuma daftar fitur."

---

### 2.10.4. Refleksi Personal: Kontribusi yang Tidak Tergantikan oleh AI

**Isi Materi**

Unit penutup seluruh kurikulum ini mengajak anggota merenungkan kembali perjalanan belajarnya, sekaligus menjawab pertanyaan yang menjadi filosofi dasar kurikulum sejak awal, apa yang membuat seorang developer manusia tetap relevan dan bernilai di tengah kemampuan AI generatif yang terus berkembang.

Sepanjang kurikulum ini, anggota sudah belajar bahwa AI bisa menulis kode dengan cepat, tapi tidak bisa menggantikan penilaian (judgment) tentang kebutuhan pengguna sesungguhnya (Modul 1 dan Modul 4), tidak bisa menggantikan keputusan strategis memilih tech stack yang tepat untuk konteks tertentu (Modul 2), tidak bisa menggantikan kemampuan membaca arah industri berdasarkan konteks yang terus berubah (Modul 3), tidak bisa menggantikan tanggung jawab kolaborasi manusia lewat Git dan komunikasi tim (Modul 5), dan tidak bisa menggantikan kepekaan mengevaluasi keamanan dan kualitas kode secara kritis (Modul 8), karena AI bisa saja menghasilkan kode yang terlihat berfungsi tapi menyimpan kerapuhan atau celah keamanan yang hanya bisa dikenali lewat pemahaman manusia yang sudah dibangun di kurikulum ini.

Kontribusi manusia yang tidak tergantikan itu pada akhirnya adalah kemampuan menilai, bukan sekadar menghasilkan. Developer yang paham konsep bisa mengarahkan AI dengan tepat, mengevaluasi hasilnya secara kritis, dan bertanggung jawab penuh atas keputusan akhir, sesuatu yang tidak bisa dilakukan seseorang yang hanya bisa mengetik prompt tanpa pemahaman di baliknya.

**Output**

Refleksi akhir (input teks, minimal 200 kata, menjadi penutup resmi kurikulum Eksplorasi).

Pertanyaan panduan refleksi: 
1. "Dari seluruh 10 modul yang sudah kamu lalui, sebutkan satu konsep yang paling mengubah cara pandangmu tentang web development, dan jelaskan kenapa."
2. "Ceritakan satu momen selama mengerjakan proyek akhir (Modul 10) di mana pemahaman konsep, bukan sekadar mengikuti instruksi AI, benar-benar membantumu mengambil keputusan atau menyelesaikan masalah."
3. "Setelah menyelesaikan kurikulum ini, kontribusi seperti apa yang kamu rasa kini bisa kamu berikan sebagai developer, yang tidak bisa digantikan sepenuhnya oleh AI?"

Anggota juga melampirkan tautan portofolio final dari unit 10.3 sebagai penanda kelulusan dari kurikulum Eksplorasi.

---

## Catatan Progres Bab 2

Seluruh 10 modul (42 unit) sudah lengkap, mengikuti standar kedalaman dan gaya naratif yang konsisten dari Modul 1 sebagai percontohan. Setiap unit kini memiliki isi materi konkret sekaligus output penugasan yang sudah konkret, siap dipakai sebagai dataset dan diimplementasikan langsung ke sistem LMS Eksplorasi WEBI-SPACE. Dokumen ini siap ditinjau Aye sebelum masuk tahap implementasi teknis (migrasi ke struktur `content_blocks` dan input ke admin panel).
