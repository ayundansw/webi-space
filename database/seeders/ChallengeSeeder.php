<?php

namespace Database\Seeders;

use App\Models\Challenge;
use Illuminate\Database\Seeder;

/**
 * Praktik: 15 Challenge (5 low/mid/high) + Track Map (ChallengeStep), dari
 * docs/v_2.0/kurikulum/Konten_Challenge_dan_TrackMap.md — SATU-SATUNYA
 * sumber teks yang sah, disalin verbatim, tidak dikarang.
 *
 * `points_reward`/`level`/`status` disalin PERSIS dari sumber (low=15,
 * mid=35, high=60, semua published), tidak diubah tanpa konfirmasi Aye.
 *
 * ContentBlock per ChallengeStep: block `text` (markdown) berisi instruksi
 * langkah, PLUS block `callout` (variant tip/warning) kalau langkah itu
 * ditandai anotasi `(callout tip: ...)`/`(callout warning: ...)` di sumber —
 * body = teks di dalam kurung tersebut. Field `body` (bukan `text`) dicek
 * ulang ke callout.blade.php sebelum menulis, sesuai Koreksi Wajib yang
 * sama dari seeding kurikulum.
 *
 * Catatan: langkah terakhir tiap Challenge ("Kirim"/"Deploy dan Kirim")
 * TIDAK semuanya punya kalimat elaborasi di sumber -- hanya L1, L5, M1, M5,
 * H1, H5 yang punya penjelasan setelah em-dash; L2-L4, M2-M4, H2-H4 di
 * sumber cuma bertuliskan judul langkah + titik, tanpa elaborasi. Untuk
 * yang bare ini, `text` content_block-nya diisi PERSIS judul+titik itu
 * (bukan disamakan dengan elaborasi Challenge lain di tier yang sama),
 * supaya tidak ada kalimat yang dikarang di luar sumber.
 */
class ChallengeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->challenges() as $data) {
            $steps = $data['steps'];
            unset($data['steps']);

            $challenge = Challenge::create($data);

            foreach ($steps as $index => $step) {
                $challengeStep = $challenge->challengeSteps()->create([
                    'title' => $step['title'],
                    'order_number' => $index + 1,
                ]);

                $challengeStep->contentBlocks()->create([
                    'type' => 'text',
                    'order' => 1,
                    'content' => ['markdown' => $step['text']],
                ]);

                if (isset($step['callout'])) {
                    $challengeStep->contentBlocks()->create([
                        'type' => 'callout',
                        'order' => 2,
                        'content' => [
                            'variant' => $step['callout']['variant'],
                            'title' => $step['title'],
                            'body' => $step['callout']['body'],
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * @return array<int, array{title: string, description: string, level: string, points_reward: int, status: string, steps: array<int, array{title: string, text: string, callout?: array{variant: string, body: string}}>}>
     */
    private function challenges(): array
    {
        return [
            // ---------------- TIER LOW ----------------
            [
                'title' => 'Simulator Antrian Fotokopi Kampus',
                'description' => 'Bangun simulator antrian untuk kios fotokopi kampus. Pengguna memasukkan jumlah halaman yang mau dicetak dan memilih kecepatan mesin (lambat/sedang/cepat), simulator menghitung estimasi waktu tunggu dan menampilkan animasi sederhana posisi antrian yang bergerak maju.',
                'level' => 'low',
                'points_reward' => 15,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Rancang Variabel Input', 'text' => 'Tentukan input apa saja (jumlah halaman, kecepatan mesin) dan bagaimana masing-masing memengaruhi waktu tunggu. Tulis rumus kasarnya di atas kertas dulu sebelum ngoding.'],
                    ['title' => 'Bangun Logika Perhitungan Waktu', 'text' => 'Tulis fungsi yang menghitung estimasi waktu berdasar variabel di atas, uji lewat console dulu sebelum disambungkan ke tampilan.'],
                    ['title' => 'Bangun Tampilan dan Animasi Antrian', 'text' => 'Tampilkan hasil perhitungan dan animasi sederhana posisi antrian yang bergerak (boleh CSS transition, tidak perlu library animasi berat).'],
                    ['title' => 'Uji dengan Beberapa Skenario', 'text' => 'Coba kombinasi jumlah halaman dan kecepatan berbeda, termasuk nilai ekstrem (0 halaman, jumlah sangat besar), pastikan hasil masuk akal.', 'callout' => ['variant' => 'tip', 'body' => 'Nilai ekstrem sering menyingkap bug yang tidak kelihatan di kasus normal.']],
                    ['title' => 'Kirim', 'text' => 'Submission berupa link repository atau kode yang bisa dijalankan.'],
                ],
            ],
            [
                'title' => 'Konversi Takaran Masak "Kira-Kira" ke Ukuran Presisi',
                'description' => 'Bangun tool yang mengonversi takaran masakan rumahan ("segenggam", "secukupnya", "sejumput") ke estimasi gram/ml berdasar tabel referensi bahan umum, lengkap catatan bahwa hasilnya perkiraan.',
                'level' => 'low',
                'points_reward' => 15,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Riset dan Susun Tabel Referensi', 'text' => 'Kumpulkan minimal 8-10 takaran umum dan estimasi gramnya per jenis bahan (segenggam beras berbeda beratnya dari segenggam kacang, catat perbedaan ini).'],
                    ['title' => 'Bangun Struktur Data Lookup', 'text' => 'Simpan tabel referensi sebagai objek/array yang mudah dicari berdasar nama bahan dan jenis takaran.'],
                    ['title' => 'Bangun Form dan Logika Konversi', 'text' => 'Pengguna pilih bahan dan takaran, sistem menampilkan estimasi gram/ml.'],
                    ['title' => 'Tambahkan Disclaimer yang Jelas', 'text' => 'Tampilkan secara mencolok bahwa ini perkiraan, bukan ukuran presisi laboratorium.', 'callout' => ['variant' => 'warning', 'body' => 'Penting secara etis, jangan sampai tool ini terlihat seperti menyampaikan fakta ilmiah pasti.']],
                    ['title' => 'Kirim', 'text' => 'Kirim.'],
                ],
            ],
            [
                'title' => 'Generator Julukan Alien Berdasarkan Nama dan Tanggal Lahir',
                'description' => 'Tool yang menghasilkan "nama alien" unik dari kombinasi nama dan tanggal lahir pengguna, memakai transformasi deterministik (input sama selalu menghasilkan output sama, bukan acak murni).',
                'level' => 'low',
                'points_reward' => 15,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Rancang Algoritma Deterministik', 'text' => 'Tentukan cara mengubah kombinasi nama+tanggal lahir jadi nilai konsisten (misalnya jumlah kode karakter dimodulo ke panjang daftar kata).'],
                    ['title' => 'Susun Daftar Kata/Pola Julukan', 'text' => 'Siapkan daftar suku kata atau pola alien yang akan dikombinasikan hasil algoritma di atas.'],
                    ['title' => 'Implementasikan dan Uji Konsistensi', 'text' => 'Pastikan input yang sama SELALU menghasilkan output yang sama.', 'callout' => ['variant' => 'tip', 'body' => 'Ini beda dari sekadar random, uji dengan memasukkan nama dan tanggal yang sama dua kali berturut-turut, hasilnya harus identik.']],
                    ['title' => 'Kirim', 'text' => 'Kirim.'],
                ],
            ],
            [
                'title' => 'Pelacak Jadwal Piket Kos dengan Rotasi Otomatis',
                'description' => 'Tool yang menerima daftar nama penghuni kos dan daftar tugas piket, lalu men-generate jadwal rotasi mingguan otomatis supaya semua orang kebagian semua tugas secara adil.',
                'level' => 'low',
                'points_reward' => 15,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Rancang Struktur Data Penghuni dan Tugas', 'text' => 'Simpan daftar nama dan daftar tugas sebagai dua kumpulan data terpisah.'],
                    ['title' => 'Bangun Logika Rotasi', 'text' => 'Tulis fungsi yang menggeser pasangan orang-tugas tiap minggu supaya tidak ada yang dapat tugas sama berturut-turut sebelum semua kebagian merata.'],
                    ['title' => 'Tampilkan Jadwal Mingguan', 'text' => 'Render jadwal dalam bentuk tabel per minggu.'],
                    ['title' => 'Uji dengan Jumlah Penghuni Ganjil dan Genap', 'text' => 'Pastikan rotasi tetap adil di kedua kasus.', 'callout' => ['variant' => 'tip', 'body' => 'Kasus jumlah ganjil sering jadi sumber bug rotasi yang tidak merata.']],
                    ['title' => 'Kirim', 'text' => 'Kirim.'],
                ],
            ],
            [
                'title' => 'Ide Bebas (Rancangan)',
                'description' => 'Anggota memilih sendiri ide aplikasi web kecil apa pun sesuai minatnya, bebas jenis dan temanya. Output berupa dokumen rancangan, bukan aplikasi jadi.',
                'level' => 'low',
                'points_reward' => 15,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Tentukan Ide dan Pengguna', 'text' => 'Jelaskan ide bebasmu, siapa yang akan memakainya, dan masalah apa yang diselesaikan.'],
                    ['title' => 'Petakan Fitur Inti', 'text' => 'Daftar fitur yang wajib ada (inti) dan fitur tambahan (opsional kalau ada waktu lebih).'],
                    ['title' => 'Susun Alur Penggunaan', 'text' => 'Deskripsikan tahap demi tahap bagaimana calon pengguna memakai aplikasi ini (boleh berbentuk teks bertahap, tidak wajib gambar/wireframe).'],
                    ['title' => 'Kirim Rancangan', 'text' => 'Submission berupa dokumen teks berisi ketiga poin di atas.'],
                ],
            ],
            // ---------------- TIER MID ----------------
            [
                'title' => 'Manajemen Peminjaman Alat Antar Anggota Komunitas',
                'description' => 'Aplikasi pencatat peminjaman alat/barang bersama dalam sebuah komunitas (alat prakarya, kamera, proyektor, dll), mencatat siapa meminjam apa, kapan harus dikembalikan, dan status keterlambatan otomatis.',
                'level' => 'mid',
                'points_reward' => 35,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Rancang Skema Data', 'text' => 'Tabel alat, peminjam, tanggal pinjam/kembali, status.'],
                    ['title' => 'Bangun CRUD Alat dan Peminjam', 'text' => 'Form dasar tambah/lihat/ubah/hapus untuk data alat dan peminjam.'],
                    ['title' => 'Bangun Logika Status Otomatis', 'text' => 'Tandai otomatis "terlambat" begitu lewat tanggal kembali tanpa dikembalikan.'],
                    ['title' => 'Uji Alur Penuh', 'text' => 'Pinjam → status berjalan otomatis → tandai kembali → cek histori peminjaman.'],
                    ['title' => 'Kirim', 'text' => 'Link repo/demo, jelaskan alur CRUD yang sudah berjalan.'],
                ],
            ],
            [
                'title' => 'Platform "Jastip" Antar Penghuni Kos',
                'description' => 'Papan permintaan titip-beli antar penghuni kos/kontrakan berdekatan, satu penghuni memasang permintaan, penghuni lain bisa mengklaim untuk memenuhinya, status berubah dari terbuka ke diklaim ke selesai.',
                'level' => 'mid',
                'points_reward' => 35,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Rancang Alur Status', 'text' => 'Tentukan status permintaan (terbuka/diklaim/selesai) dan siapa yang boleh mengubah status apa.'],
                    ['title' => 'Bangun Form Pasang Permintaan', 'text' => 'Penghuni bisa memasang permintaan titip beli baru.'],
                    ['title' => 'Bangun Mekanisme Klaim', 'text' => 'Penghuni lain bisa klaim permintaan, cegah dua orang mengklaim permintaan yang sama.', 'callout' => ['variant' => 'tip', 'body' => 'Ini kasus race condition sederhana, pikirkan pencegahannya meski di level dasar, misalnya cek ulang status sebelum simpan.']],
                    ['title' => 'Uji Alur Penuh dari Sudut Pandang Lebih dari Satu Pengguna', 'text' => 'Pasang → klaim → selesai.'],
                    ['title' => 'Kirim', 'text' => 'Kirim.'],
                ],
            ],
            [
                'title' => 'Sistem Pencatat Iuran Komunitas dengan Riwayat Transparan',
                'description' => 'Aplikasi pencatatan iuran rutin komunitas/kelas, menampilkan riwayat siapa sudah bayar bulan apa, total kas terkumpul, dan daftar anggota yang menunggak.',
                'level' => 'mid',
                'points_reward' => 35,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Rancang Skema Data', 'text' => 'Anggota, periode iuran, status bayar per periode.'],
                    ['title' => 'Bangun Input Pencatatan Pembayaran', 'text' => 'Form mencatat siapa membayar iuran periode apa.'],
                    ['title' => 'Bangun Ringkasan Otomatis', 'text' => 'Total kas terkumpul dan daftar penunggak per periode, dihitung otomatis dari data pembayaran.'],
                    ['title' => 'Uji dengan Data Beberapa Bulan', 'text' => 'Pastikan ringkasan tetap akurat kalau ada anggota baru masuk di tengah jalan.'],
                    ['title' => 'Kirim', 'text' => 'Kirim.'],
                ],
            ],
            [
                'title' => 'Papan Skor Turnamen Kelas/Angkatan',
                'description' => 'Aplikasi bracket turnamen sederhana untuk kompetisi kasual antar kelas/angkatan (bebas jenis kompetisinya), men-generate bagan pertandingan, mencatat skor, otomatis memajukan pemenang.',
                'level' => 'mid',
                'points_reward' => 35,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Rancang Struktur Bracket', 'text' => 'Tentukan cara menyimpan pertandingan dan hubungan antar babak.'],
                    ['title' => 'Bangun Generator Bagan dari Daftar Peserta', 'text' => 'Otomatis buat pasangan babak pertama, tangani jumlah peserta ganjil (mekanisme "bye"/lewat babak).'],
                    ['title' => 'Bangun Pencatatan Skor dan Kemajuan Otomatis', 'text' => 'Input skor, pemenang otomatis maju ke bagan berikutnya.'],
                    ['title' => 'Uji Turnamen Penuh', 'text' => 'Jalankan skenario lengkap dari babak pertama sampai juara.'],
                    ['title' => 'Kirim', 'text' => 'Kirim.'],
                ],
            ],
            [
                'title' => 'Ide Bebas (Aplikasi Sederhana)',
                'description' => 'Anggota memilih sendiri ide aplikasi berfungsi, bebas jenis dan temanya. Output berupa aplikasi sederhana yang benar-benar berjalan, minimal satu alur CRUD lengkap.',
                'level' => 'mid',
                'points_reward' => 35,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Tentukan Ide dan Fitur Inti', 'text' => 'Pastikan ada minimal satu alur CRUD penuh yang jadi fokus utama.'],
                    ['title' => 'Rancang Struktur Data', 'text' => 'Susun struktur data sebelum mulai menulis kode.'],
                    ['title' => 'Implementasikan dan Uji Sendiri', 'text' => 'Bangun fitur inti, uji alur utamanya berjalan benar.'],
                    ['title' => 'Kirim', 'text' => 'Link repo/demo (lokal boleh, tidak wajib publik di tier ini), jelaskan fitur inti apa yang sudah berjalan.'],
                ],
            ],
            // ---------------- TIER HIGH ----------------
            [
                'title' => 'Marketplace Barang Preloved Kos dengan Payment Gateway',
                'description' => 'Marketplace kecil jual-beli barang bekas layak pakai antar penghuni kos/kampus, terintegrasi payment gateway sandbox untuk simulasi pembayaran, termasuk penanganan webhook status transaksi.',
                'level' => 'high',
                'points_reward' => 60,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Rancang Alur Transaksi', 'text' => 'Dari pilih barang sampai status pembayaran selesai, termasuk state barang (tersedia/dipesan/terjual).'],
                    ['title' => 'Integrasikan Payment Gateway Sandbox', 'text' => 'Daftar akun sandbox (misalnya Midtrans Sandbox), pelajari dokumentasinya, bangun alur redirect/komponen pembayaran.'],
                    ['title' => 'Tangani Webhook Status Transaksi', 'text' => 'Pastikan status barang berubah otomatis mengikuti notifikasi webhook dari gateway.', 'callout' => ['variant' => 'warning', 'body' => 'Bagian ini paling sering jadi celah bug, uji skenario pembayaran gagal juga, bukan cuma yang berhasil.']],
                    ['title' => 'Uji Transaksi End-to-End di Sandbox', 'text' => 'Dari checkout sampai status akhir tercermin benar di aplikasi.'],
                    ['title' => 'Deploy dan Kirim', 'text' => 'Link aplikasi live dan repository.'],
                ],
            ],
            [
                'title' => 'Dashboard Prediksi Waktu Terbaik Mencuci-Menjemur Berdasar Cuaca',
                'description' => 'Dashboard yang mengintegrasikan API cuaca publik untuk merekomendasikan jam terbaik mencuci dan menjemur dalam 24-48 jam ke depan berdasar prediksi curah hujan dan kelembapan.',
                'level' => 'high',
                'points_reward' => 60,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Pelajari dan Uji Coba API Cuaca', 'text' => 'Daftar API key (misalnya OpenWeatherMap), pelajari format response, coba panggil manual dulu sebelum diintegrasikan ke aplikasi.'],
                    ['title' => 'Rancang Logika Rekomendasi', 'text' => 'Tentukan aturan mengubah data cuaca mentah jadi rekomendasi jam terbaik, bukan cuma menampilkan angka mentah.'],
                    ['title' => 'Bangun Dashboard', 'text' => 'Tampilkan rekomendasi dengan jelas, sertakan data pendukung (probabilitas hujan, kelembapan).'],
                    ['title' => 'Tangani Kegagalan API', 'text' => 'Pastikan aplikasi tidak ikut gagal total kalau API cuaca sedang tidak merespons (kaitkan ke prinsip Modul 9 unit 9.2).'],
                    ['title' => 'Deploy dan Kirim', 'text' => 'Deploy dan Kirim.'],
                ],
            ],
            [
                'title' => 'Sistem Booking Ruang Diskusi dengan Notifikasi WhatsApp Otomatis',
                'description' => 'Sistem pemesanan ruang diskusi/meeting point kampus real-time, mencegah bentrok jadwal, terintegrasi API pengirim pesan untuk notifikasi konfirmasi otomatis.',
                'level' => 'high',
                'points_reward' => 60,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Rancang Skema Data Booking', 'text' => 'Ruang, slot waktu, pemesan, status.'],
                    ['title' => 'Bangun Logika Pencegahan Bentrok', 'text' => 'Pastikan dua pemesanan tidak bisa memakai ruang dan slot waktu yang sama.'],
                    ['title' => 'Integrasikan API Pengirim Pesan', 'text' => 'Daftar akun sandbox/trial (misalnya Fonnte atau Twilio), kirim notifikasi otomatis begitu booking dikonfirmasi.'],
                    ['title' => 'Uji Skenario Bentrok dan Sukses', 'text' => 'Coba pesan slot yang sudah terisi (harus ditolak jelas), coba pesan slot kosong (notifikasi harus terkirim).'],
                    ['title' => 'Deploy dan Kirim', 'text' => 'Deploy dan Kirim.'],
                ],
            ],
            [
                'title' => 'Agregator Promo Makanan Sekitar Kampus',
                'description' => 'Aplikasi yang mengumpulkan dan menampilkan promo makanan dari beberapa sumber API/data terbuka sekitar area kampus, mengurutkan berdasar nilai promo atau jarak.',
                'level' => 'high',
                'points_reward' => 60,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Identifikasi Sumber Data', 'text' => 'Pilih minimal dua sumber API/data terbuka yang akan digabungkan.'],
                    ['title' => 'Rancang Format Data Gabungan', 'text' => 'Tentukan struktur data seragam untuk menampung hasil dari sumber yang formatnya berbeda-beda.'],
                    ['title' => 'Bangun Logika Pengambilan dan Penggabungan', 'text' => 'Ambil data dari tiap sumber, gabungkan dan urutkan.'],
                    ['title' => 'Tangani Sumber yang Gagal Merespons', 'text' => 'Pastikan satu sumber error tidak membuat seluruh halaman gagal tampil.'],
                    ['title' => 'Deploy dan Kirim', 'text' => 'Deploy dan Kirim.'],
                ],
            ],
            [
                'title' => 'Ide Bebas (Web App Kompleks + Integrasi)',
                'description' => 'Anggota memilih sendiri ide web app kompleks, bebas jenis dan temanya, dengan satu syarat wajib: melibatkan minimal satu integrasi pihak ketiga nyata (API eksternal apa pun, atau payment gateway).',
                'level' => 'high',
                'points_reward' => 60,
                'status' => 'published',
                'steps' => [
                    ['title' => 'Pilih Ide dan Integrasi Pihak Ketiga Wajib', 'text' => 'Tentukan API/gateway apa yang akan diintegrasikan.'],
                    ['title' => 'Rancang Alur Data dengan Integrasi Tersebut', 'text' => 'Petakan bagaimana data mengalir antara aplikasimu dan layanan pihak ketiga itu.'],
                    ['title' => 'Bangun dan Uji Integrasi Nyata', 'text' => 'Pakai mode sandbox/uji kalau tersedia, pastikan integrasi benar-benar berjalan bukan cuma di rencana.'],
                    ['title' => 'Deploy dan Laporkan', 'text' => 'Link live dan repository, jelaskan integrasi apa yang dipakai dan bagaimana cara kerjanya.'],
                ],
            ],
        ];
    }
}
