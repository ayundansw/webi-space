# Roadmap Eksekusi — WEBI-SPACE v2.0 Final

**Basis dokumen:** `RANCANGAN_FINAL_WEBI-SPACE_v2.md` (5 modul, sudah tuntas & clean).
**Dokumen ini untuk siapa:** Aye dan Claude Chat — bukan untuk Claude Code. Ini "daftar isi" supaya kita berdua tahu urutan kerja dan tidak ada yang kelewat. Claude Code nanti cuma menerima satu prompt kecil per satu langkah kerja, disusun berdasarkan dokumen ini + Rancangan Final saat waktunya tiba.
**Penomoran:** dokumen ini pakai penomoran baru (Fase 1 sampai Fase 10), terpisah dari nomor Roadmap lama (2.2.1-2.2.8) karena banyak yang berubah sejak rancangan lama itu ditulis.

---

## Cara Membaca Dokumen Ini

Dokumen ini punya tiga lapis:
1. **Ringkasan 10 Fase** (tabel di bawah) — lihat ini dulu kalau butuh orientasi cepat, "sekarang kita di mana".
2. **Detail tiap Fase** — isi lengkap tiap fase, dipecah jadi langkah-langkah kecil bernomor.
3. **Rujukan ke Rancangan Final** — tiap langkah menyebut bagian mana di `RANCANGAN_FINAL_WEBI-SPACE_v2.md` yang jadi acuan detailnya (ditulis sebagai kata biasa, misal "Bagian 1.2", bukan simbol).

Kamu tidak perlu hafal semua isi dokumen ini sekaligus. Cukup tahu fase berapa kita sedang berada, baca detail fase itu saja, lanjut ke fase berikutnya kalau sudah selesai.

---

## Prinsip Penyusunan Urutan (kenapa urutannya begini)

1. **Fondasi visual dikerjakan sekali di depan.** Semua fitur baru (Kurikulum, Praktik, Manajemen Proyek) langsung dibangun dalam wujud visual final — bukan dibangun polos dulu lalu dipoles belakangan (itu kerja dua kali).
2. **Kebutuhan antar-fitur menentukan urutan, bukan nomor modul.** Kelola Kurikulum (Modul 5) dikerjakan sebelum Praktik (Modul 3/5) karena Praktik memakai ulang editor konten yang sama — kalau dibalik, Praktik harus bangun editornya sendiri dulu, lalu dibongkar lagi.
3. **Yang paling berisiko (Mode Ganda) dikerjakan mendekati akhir**, setelah modul besar lain stabil. Perubahan sistem akses menyentuh banyak titik, lebih aman dikerjakan saat tidak ada modul lain yang sedang berubah bersamaan.
4. **Hitung ulang threshold poin wajib setelah Praktik selesai** — baru saat itu total poin maksimal yang bisa dicapai anggota diketahui pasti.
5. **Landing Page selalu paling akhir**, sesuai kesepakatan dari awal.
6. **Checkpoint kecil, bukan batch raksasa** — tiap fase dipecah sub-langkah, dites tiap sub-langkah, sesuai pola yang sudah terbukti jalan sepanjang v2.0 sejauh ini.
7. **Backup database wajib** sebelum fase yang menyentuh skema baru — ditandai tanda peringatan di tiap fase yang perlu.

---

## Ringkasan 10 Fase (Peta Cepat)

| Fase | Isi Singkat | Ada Migrasi Skema Baru? | Rujukan Modul |
|---|---|---|---|
| 1 | Persiapan | Tidak | - |
| 2 | Fondasi Visual & Shell | Tidak | Modul 1 |
| 3 | Re-skin Eksplorasi & Eksekusi + Fitur Kecil Siap | Ya (kecil, cuma Referensi) | Modul 3, 4 |
| 4 | Admin Kelola Kurikulum | Tidak (semua tabel sudah ada) | Modul 5 bagian 1 |
| 5 | Modul Praktik | Ya | Modul 3 bagian 7, Modul 5 bagian 2 |
| 6 | Hitung Ulang Threshold Poin | Tidak | (dulu disebut 2.2.7) |
| 7 | Modul Manajemen Proyek | Ya (besar) | Modul 4, Modul 5 bagian 5 |
| 8 | Sistem Mode Ganda | Ya | Modul 2 bagian 2, Modul 5 bagian 3 |
| 9 | Landing Page | Tidak | (dulu disebut 2.2.8) |
| 10 | QA Menyeluruh | - | Semua |

---

## FASE 1 — Persiapan

**Tujuan:** pastikan titik berangkat bersih, tidak ada sisa yang membingungkan.

1. Cek kondisi `app.css`/token sekarang — pastikan tidak ada sisa eksperimen desain lama yang sempat diterapkan sebagian ke dashboard Eksplorasi. Bersihkan dulu kalau ada, sebelum Fase 2 mulai.
2. Pastikan file referensi visual final (maskot WEBI, 5 gambar avatar Fox, 5 gambar avatar hewan Eksekusi) sudah tersimpan rapi dan siap dilampirkan ke prompt nanti.
3. Konfirmasi tidak ada pekerjaan menggantung dari sesi sebelumnya yang perlu dibereskan dulu.

**Ini fase verifikasi saja, belum ada prompt implementasi ke Claude Code.**

---

## FASE 2 — Fondasi Visual & Shell (Modul 1 Penuh)

**Tujuan:** kunci seluruh bahasa visual dan struktur navigasi sekali saja, jadi basis semua fase berikutnya.

1. **Design Token — Final & Konkret.** Tetapkan nilai warna pasti (background bukan putih polos, warna status seperti sukses/peringatan/bahaya, skala bayangan/shadow, sudut/radius, jarak antar-elemen) sesuai Rancangan Final bagian 1.2 sampai 1.2.4. Terapkan sebagai variabel warna di kode. Belum diterapkan ke halaman mana pun — cuma didefinisikan dulu.
2. **Logo Wordmark.** Pilih dan pasang font pixel (kandidat: Press Start 2P, VT323, atau Silkscreen), sesuai bagian 1.4.A.
3. **Maskot WEBI jadi Kode.** Terjemahkan referensi visual "Boxy Blocky" jadi gambar kode presisi (komponen yang bisa dipakai berulang). Lampirkan file referensi gambarnya ke prompt.
4. **Avatar jadi Kode + Halaman Pilih Avatar.** Terjemahkan 5 tingkat Fox dan 5 hewan Eksekusi jadi komponen kode. Bangun juga **halaman/mekanisme memilih avatar untuk Eksekusi** (karena Eksekusi bebas pilih salah satu dari 5, ini butuh UI pemilihan, bukan cuma menampilkan). Logic pembukaan otomatis Fox berdasar poin (ambang 50/100/300/500/1000+) bisa langsung dibangun sekarang, karena datanya (poin dari Materi dan Kuis) sudah ada.
5. **Bangun Ulang Shell: Navbar + Menu Popup (ganti Sidebar).** Sumber data menu (`config/navigation.php`) dan logic halaman-aktif dipertahankan seperti sekarang — cuma cara menampilkannya yang berubah total, dari daftar sidebar jadi kotak-kotak menu yang muncul saat ikon di navbar diklik (ala menu aplikasi Google). Navbar: logo di kiri, di kanan ada notifikasi, ikon menu, dan avatar akun. **Siapkan juga tempat kosong untuk "Badge Status Mode"** di navbar — belum aktif sekarang, baru berfungsi nanti di Fase 8 saat Mode Ganda dibangun.
6. **Breadcrumb — Komponen yang Dipakai di Semua Halaman.** Bangun sistem penunjuk lokasi (misal "Beranda / Peta Kurikulum / Unit 3.2"), pasang di semua halaman yang sudah ada sekarang.
7. **Halaman Login — Desain Baru.** Kartu login dengan elemen visual bergerak halus di latar belakang, maskot WEBI menyapa dengan kalimat penyemangat, form tetap sederhana dan jelas.

**Yang wajib dicek sebelum lanjut:** jalankan semua test yang ada, pastikan tetap lolos semua (perubahan di fase ini murni visual dan struktural, bukan mengubah cara kerja fitur). Aye WAJIB melihat langsung di browser — cek shell baru di ketiga portal, menu popup berfungsi, breadcrumb konsisten, tampilan di HP rapi.

---

## FASE 3 — Re-skin Eksplorasi & Eksekusi + Fitur Kecil yang Sudah Siap

**Tujuan:** terapkan fondasi visual dari Fase 2 ke semua halaman yang sudah ada, sekaligus selesaikan fitur kecil yang sudah dipastikan datanya siap (tidak perlu menunggu modul besar apa pun).

1. **Dashboard Eksplorasi** — terapkan warna baru, susun ulang sesuai urutan kartu final (Rancangan Final bagian 3.1), termasuk tempat kosong untuk preview Praktik dan preview Forum.
2. **Peta Kurikulum** — cuma ganti warna (struktur halamannya tidak berubah, sesuai bagian 3.2). Tambahkan ringkasan Level dan Poin di bagian atas.
3. **Halaman Materi + WEBI** — desain ulang total dari panel geser (yang lama) jadi tampilan 3 kolom berdampingan: Daftar Isi Modul di kiri, Materi di tengah, Chat WEBI di kanan (sesuai bagian 3.3). Cek dulu kondisi kode yang lama sebelum dibongkar.
4. **WEBI Chat (halaman penuh)** — desain ulang jadi 2 kolom (riwayat percakapan di kiri, chat aktif di kanan), ganti nama tampilannya jadi "WEBI Chat", ganti ikon jadi maskot resmi, pindahkan keterangan soal PIC dari atas ke bawah kotak ketik (sesuai bagian 3.6).
5. **Referensi — Fitur Baru.** *(Perlu backup database dulu, ada migrasi kecil.)* Tambah kolom penanda pembuat dan kolom deskripsi ke tabel Referensi. Bangun form baru supaya anggota bisa mengirim referensi sendiri (sekarang belum ada sama sekali). Tampilkan label "Dari Anggota" atau "Dari Admin". Terapkan desain baru.
6. **Forum** — buka opsi bikin thread tanpa terikat modul/unit tertentu (general/bebas). Tidak butuh migrasi sama sekali (sudah dipastikan lewat pengecekan sebelumnya). Terapkan desain baru.
7. **Dashboard Eksekusi** — bangun komponen-komponen sesuai analisis di bagian 4.1, pakai **data contoh/dummy dulu** supaya hasilnya bisa dinilai konkret. Tempat untuk "Antrian Review Praktik" dan "Ringkasan Kalender" disiapkan kosong dulu (aktif nanti di Fase 5 dan Fase 7).
8. **Project Ideas** — cuma "Judul Ide" yang wajib diisi, field lain jadi opsional. Satu halaman dengan dua bagian: "Menunggu Keputusan" dan "Riwayat" (dengan warna status).
9. **Profil (Eksplorasi dan Eksekusi)** — tambahkan ringkasan poin/level/avatar, galeri avatar (yang mana saja sudah terbuka), kalender aktivitas (kotak warna ala GitHub), dan daftar kontribusi per proyek (diambil dari data tugas yang sudah ada, tanpa perlu field baru). **Siapkan juga tempat kosong untuk "info lintas-mode"** — belum aktif sekarang, baru berfungsi nanti di Fase 8. Rapikan tampilan untuk desktop dan HP.
10. **Kanban/Proyek** — cuma ganti tampilan warnanya, cara kerja drag-and-drop tidak disentuh sama sekali.

**Yang wajib dicek sebelum lanjut:** semua test tetap lolos. Khusus langkah 5 dan 6, tambahkan test baru sesuai temuan pengecekan sebelumnya. Aye cek satu-satu di browser — ini batch paling banyak halamannya, jangan terburu-buru.

---

## FASE 4 — Admin Kelola Kurikulum

**Tujuan:** bangun panel yang paling sering kamu tekankan — akhirnya materi kurikulum bisa diisi lewat tampilan admin, bukan cuma lewat kode.

**Kabar baik: fase ini tidak butuh migrasi sama sekali** — semua tabel yang dibutuhkan (Modul, Unit, blok konten, soal evaluasi) sudah ada sejak sebelumnya. Ini murni membangun tampilan admin di atas data yang sudah ada.

1. **Kelola Modul & Unit** — admin bisa membuat, mengedit, menghapus, dan mengatur urutan Modul dan Unit, termasuk menentukan syarat unit sebelumnya, nilai poin, dan tipe evaluasinya.
2. **Editor Konten Blok** — form terstruktur untuk 9 tipe blok (Heading, Teks, Gambar, Callout, Kode, Video, List, Tabel, Custom HTML). Admin pilih tipe dari daftar, isi form sesuai tipe, atur urutan dengan tombol naik/turun, ada tombol pratinjau sebelum diterbitkan.
3. **Kelola Evaluasi/Kuis** — form berbeda untuk tiap tipe soal (pilihan ganda paling sederhana, mencocokkan pasangan otomatis menghasilkan kunci jawaban dari pasangan yang diisi, esai/praktik cuma butuh teks instruksi). **Sekalian perbaiki soal urutan (ordering)**: sekarang urutan tampil dan kunci jawaban selalu sama persis (jadi bisa "benar" tanpa menggeser apa pun) — form baru akan meminta admin mengisi urutan tampil secara acak terpisah dari kunci jawabannya, supaya soal benar-benar menguji.
4. **Migrasi 67 Materi Lama** — dipindahkan manual ke sistem blok baru, dibantu Claude Code, lewat editor yang baru dibangun. Ini pekerjaan konten yang besar, bisa dicicil per modul, tidak harus sekaligus 67.
5. **Update Sistem Baca WEBI** — pastikan WEBI membaca materi dari sistem blok yang baru, bukan dari teks lama.
6. **Tambahan kecil:** tambah kartu ringkasan status Kurikulum (jumlah modul/unit, mana yang sudah terbit) di Dashboard Admin. Tambah juga entry menu baru "Kelola Kurikulum" ke navigasi admin.

**Yang wajib dicek sebelum lanjut:** semua test tetap lolos. Aye coba sendiri membuat satu unit baru dari nol lewat panel, isi semua jenis blok, buat soal tiap jenis, pastikan tampil benar di sisi anggota.

---

## FASE 5 — Modul Praktik

**Tujuan:** bangun modul Praktik lengkap, memakai ulang editor konten dari Fase 4.

**Perlu backup database dulu** — ada tabel baru (Challenge dan Submission).

1. **Skema Data** — tabel Challenge (judul, deskripsi, level, poin, status) dan Submission (siapa, challenge mana, isi submission, status, feedback, nomor percobaan, poin didapat, siapa reviewernya).
2. **Editor Track Map (Admin)** — memakai ulang persis editor blok konten dari Fase 4, tidak dibangun dari nol lagi.
3. **Kelola Challenge (Admin)** — buat/edit challenge, atur level dan poinnya, atur status draft/terbit.
4. **Anggota: Jelajah & Kirim Submission** — daftar challenge bisa difilter per level, buka satu challenge untuk baca panduannya, kirim hasil (link/teks/file), bisa kirim ulang tanpa batas.
5. **Admin: Antrian Review** — admin bisa mereview langsung, atau menugaskan ke satu anggota Eksekusi tertentu untuk mereview, dengan notifikasi otomatis ke reviewer.
6. **Reviewer: Halaman Review di Portal Eksekusi** — anggota Eksekusi yang ditugaskan bisa mereview langsung dari portalnya sendiri, tanpa perlu berganti mode. Aktifkan kartu "Antrian Review" di Dashboard Eksekusi yang sudah disiapkan kosong di Fase 3.
7. **Poin Menurun Bertahap** — submit ulang yang disetujui dapat 70% dari poin submission sebelumnya (angka tetap di kode, tidak perlu pengaturan tambahan).
8. **Satukan Sistem Poin.** Ini momen pentingnya: sekarang tiga sumber poin (Materi, Kuis, Praktik) sudah lengkap semua. Bangun satu sistem terpusat yang mengurus penambahan poin, alihkan titik-titik lama (dari Materi dan Kuis) ke situ, plus titik baru dari Praktik. Cek dulu kondisi titik-titik lama sebelum diubah.
9. **Tampilan halaman Praktik** — gaya tampilannya (mirip LeetCode/W3Schools/dll) masih belum diputuskan Aye. Bangun dulu dengan tampilan yang rapi dan fungsional memakai token yang sudah ada, gaya final bisa menyusul kapan saja tanpa mengganggu fase ini.
10. **Tambahan kecil:** tambah kartu ringkasan Antrian Review Praktik di Dashboard Admin. Tambah entry menu "Kelola Praktik" ke navigasi admin.

**Yang wajib dicek sebelum lanjut:** semua test tetap lolos, ditambah test keamanan khusus (reviewer cuma bisa akses submission yang ditugaskan ke dia, tidak bisa lihat punya orang lain). Aye coba sendiri: kirim submission, tugaskan reviewer, review dari portal Eksekusi, cek poin menurun bertahap benar saat kirim ulang.

---

## FASE 6 — Hitung Ulang Threshold Poin

**Tujuan:** sekarang tiga sumber poin (Materi, Kuis, Praktik) sudah lengkap, batas poin tiap level yang tadinya masih perkiraan sekarang dihitung ulang jadi final.

1. Hitung total poin maksimal yang bisa dicapai (semua materi + semua kuis + estimasi Praktik).
2. Tetapkan batas poin final untuk tiap 6 level tampilan.
3. Cek tidak ada anggota yang levelnya "turun" akibat perubahan batas ini — kalau ada, putuskan cara menanganinya.

**Yang wajib dicek:** test perhitungan level tetap lolos dengan batas baru.

---

## FASE 7 — Modul Manajemen Proyek

**Tujuan:** rombak total portal Eksekusi jadi sistem manajemen proyek yang lengkap — ini bagian PALING BESAR, wajib dipecah pelan-pelan.

**Perlu backup database dulu** — banyak tabel baru (acara kalender, ketergantungan antar-task, kolom baru di tabel task, tabel forum baru).

1. **Fondasi + Susun Ulang Halaman Proyek jadi Tab** — tab Kanban, Roadmap, Gantt, Kalender, Forum Proyek, Anggota. Kanban tetap bersih (cuma task besar). Cek dulu apakah detail task sekarang sudah berupa panel geser atau masih halaman penuh, sesuaikan kalau perlu.
2. **Subtask** — task kecil di dalam task besar, ditampilkan sebagai daftar mini di panel detail, bukan Kanban mini.
3. **Gantt + Ketergantungan Antar-Task** — bar per task dengan tanggal, bisa ditandai "menunggu task lain selesai dulu", ada peringatan visual kalau yang ditunggu molor (tidak otomatis menjadwalkan ulang).
4. **Roadmap** — garis waktu besar berdasar milestone yang sudah ada.
5. **Kalender** — acara manual (lomba, meeting, dll) plus kegiatan otomatis dari deadline task/milestone. Ada tampilan per-proyek dan tampilan gabungan semua proyek. Aktifkan ringkasan tanggal terdekat di Dashboard Eksekusi yang sudah disiapkan kosong di Fase 3.
6. **Forum Eksekusi** — forum umum lintas-proyek plus forum khusus tiap proyek (tabel baru, terpisah dari Forum Eksplorasi), cuma bisa diakses anggota Eksekusi dan admin.
7. **Kualitas visual** — pastikan setara platform manajemen proyek yang matang (ClickUp, Taiga, Notion), bukan versi sederhana.
8. **Tambahan kecil:** pastikan ringkasan proyek dan peringatan di Dashboard Admin tetap konsisten dengan struktur baru.

**Yang wajib dicek sebelum lanjut:** semua test tetap lolos, ditambah test keamanan (anggota Eksplorasi tetap tidak bisa akses Forum Eksekusi). Aye WAJIB cek tiap langkah satu-satu di browser sebelum lanjut ke langkah berikutnya — jangan tunggu semua selesai baru dicek sekaligus, ini bagian paling besar dan paling berisiko kalau ada yang salah tidak ketahuan sejak awal.

---

## FASE 8 — Sistem Mode Ganda

**Tujuan:** fitur paling rumit dan paling berisiko soal keamanan akses — dikerjakan setelah semua modul besar lain stabil.

**Wajib cek kode dulu sebelum mulai** (belum pernah dilakukan) — memetakan semua bagian yang perlu disentuh, supaya tidak ada yang lupa diperiksa. **Perlu backup database** setelahnya, ada skema baru.

1. **Pemetaan Menyeluruh** — cari semua bagian aplikasi yang mengecek "siapa boleh akses apa", rencanakan satu titik pengecekan terpusat yang baru.
2. **Skema Baru** — tambahan data untuk menandai akses mode kedua dan mode yang sedang aktif, plus tabel baru untuk alur pengajuan-persetujuan.
3. **Titik Pengecekan Terpusat** — satu tempat tunggal yang menentukan boleh-tidaknya akses, dipakai konsisten di semua bagian yang terdampak, supaya tidak ada yang lupa dicek dan jadi celah.
4. **Origin Eksekusi → Mode Eksplorasi (baca saja)** — bisa langsung tanpa perlu izin, tapi cuma bisa membaca (tidak bisa ikut kuis, tidak dapat poin, tidak masuk leaderboard), dengan keterangan jelas di tampilan.
5. **Origin Eksplorasi → Mode Eksekusi (perlu izin)** — anggota mengajukan, admin menyetujui/menolak, setelah disetujui dapat akses penuh, riwayat data dua-duanya tetap ada dan terpisah.
6. **Admin: Cabut Akses** — dari halaman Manajemen Akun, admin bisa mencabut akses ini kapan saja.
7. **Aktifkan Bagian yang Sudah Disiapkan** — Badge Status Mode di navbar (dari Fase 2), tempat info lintas-mode di Profil (dari Fase 3), sekarang diaktifkan. Tambahkan tautan ke portal Eksekusi yang muncul di halaman Profil begitu disetujui.
8. **Tambahan kecil:** tambah kartu Antrian Permintaan Mode Ganda di Dashboard Admin. Tambah entry menu "Permintaan Mode Ganda" ke navigasi admin.

**Yang wajib dicek sebelum lanjut:** semua test tetap lolos, ditambah test keamanan yang ketat (anggota tanpa izin tidak bisa akses mode lain, anggota mode baca tidak bisa submit kuis, dst) — fase ini paling rawan celah keamanan, test-nya harus paling teliti di antara semua fase. Aye WAJIB coba sendiri kedua arah perpindahan mode, pastikan tidak ada yang bocor.

---

## FASE 9 — Landing Page

Dikerjakan paling akhir sesuai kesepakatan dari awal. Detailnya belum dibahas di lima modul rancangan ini — akan dibahas terpisah begitu fase ini tiba waktunya.

---

## FASE 10 — QA Menyeluruh

1. Jalankan seluruh test yang ada, pastikan semuanya lolos.
2. Review manual menyeluruh memakai panduan `ROADMAP_Review_Revisi_WEBI-SPACE.md` (cek tiap halaman dari 5 sisi: Fungsi, Data, Visual, Bahasa, Tampilan Mobile).
3. Cek ulang seluruh sistem akses (siapa boleh apa), khusus bagian yang disentuh Fase 8.
4. Cek performa — halaman berat (Dashboard, Kanban, Kalender) tidak terasa lambat.
5. Perbarui catatan status proyek — tandai bahwa dua utang teknis lama (fitur upload materi, tampilan evaluasi campuran) sudah benar-benar tertutup lewat migrasi di Fase 4.

---

## Cara Pakai Roadmap Ini

1. Kerjakan fase berurutan (1 sampai 10), jangan lompat — tiap fase dibangun di atas fase sebelumnya.
2. Tiap langkah bernomor dalam satu fase = satu prompt Claude Code terpisah. Aku (Claude Chat) yang menyusun prompt-nya waktu kamu bilang "ayo kerjakan langkah ini", dengan format sama seperti yang sudah kita pakai sepanjang v2.0 (cek kode dulu kalau perlu, batasan jelas, wajib ada test).
3. Setelah tiap fase (atau langkah besar dalam satu fase), **Aye cek hasilnya di browser** sebelum lanjut — terutama Fase 2 dan 3 (visual, wajib dilihat), Fase 7 (paling besar), Fase 8 (soal keamanan).
4. Kalau ragu soal detail suatu langkah saat waktunya tiba, rujuk balik ke bagian modul terkait di `RANCANGAN_FINAL_WEBI-SPACE_v2.md` — dokumen itu tetap jadi sumber kebenaran detailnya, roadmap ini cuma urutan kerjanya.
5. Backup database sebelum mulai Fase 3 langkah 5 (Referensi), Fase 5, Fase 7, dan Fase 8 — jangan sampai lewat.
