# Rancangan Final WEBI-SPACE v2.0 — Dokumen Hidup

**Status dokumen:** LIVING DOCUMENT — terus ditambah seiring pembahasan tiap modul selesai. Ini acuan TUNGGAL untuk implementasi tahap final v2.0.

**Konteks pemicu:** Evaluasi usability testing v1.0/v2.0-awal menyebut poin minus paling menonjol: **"desain visual terlalu generik AI / terlihat vibe-coding"**. Seluruh keputusan visual di dokumen ini bertujuan membalik itu jadi autentik, khas WEBI-SPACE, buatan tangan developer sendiri — bukan template SaaS umum.

**Struktur kerja:** dibahas & diputuskan per modul (5 modul), didokumentasikan tuntas per modul di sini, baru lanjut modul berikutnya. Setelah semua modul tuntas, dokumen ini jadi basis penyusunan Roadmap Eksekusi + Prompt Master sesi implementasi.

**5 Modul:**
1. Fondasi Lintas-Portal (visual, shell, arsitektur informasi) — SEDANG DIBAHAS
2. Autentikasi (login, logout, profil)
3. Eksplorasi (dashboard, kurikulum, unit+kuis, WEBI, referensi, forum, leaderboard)
4. Eksekusi (dashboard, project ideas, proyek, kanban, task)
5. Admin (dashboard, kelola user, kelola project ideas, kelola proyek, log WEBI)

---

## MODUL 1 — Fondasi Lintas-Portal

### 1.0 Diagnosis & Tujuan

Masalah inti: tampilan sekarang terasa generik/template AI. Penyebab teridentifikasi: pola sidebar standar SaaS, token warna dipakai terlalu kaku/minim, tidak ada identitas visual (logo), layout terasa datar tanpa karakter unik.

Tujuan: seluruh fondasi visual & struktural yang dipakai LINTAS SEMUA PORTAL (navbar, navigasi, warna, tipografi, arsitektur informasi) dirancang ulang agar terasa **autentik, khas, buatan sendiri** — bukan template.

### 1.1 Konsep Desain: RETRO-TECH, Profesional = Playful

**Tema:** Retro-Tech. Bahasa visual: motif garis/node sirkuit (sudah jadi signature Peta Kurikulum sejak v1.0), aksen neon cyan, elemen ala terminal/grid, sentuhan monospace sebagai elemen visual (bukan cuma untuk angka).

**Keseimbangan:** Profesional (struktur rapi, kredibel) = Playful (detail hidup, menyenangkan). Hasil akhir membawa kesan **joy/happiness/enjoy** saat dipakai — bukan cuma fungsional, tapi menyenangkan digunakan.

**Insight kunci:** WEBI-SPACE sudah punya bahan retro-tech tanpa disadari — cyan `#05D9E7` sudah neon-retro, JetBrains Mono sudah dipakai, motif node+garis sirkuit sudah jadi signature. Modul 1 menegaskan & memperluas ini jadi identitas menyeluruh, bukan mulai dari nol.

### 1.2 Palet Warna: RIT Official + Pendukung

**Warna inti RIT (SAKRAL — identitas resmi organisasi, tidak boleh hilang/diganti):**

| Nama | Hex | Peran |
|---|---|---|
| Ink | `#1C1515` | Teks utama, elemen gelap |
| Muted | `#979393` | Teks sekunder, border |
| Accent (Cyan) | `#05D9E7` | CTA, progres, signature retro-tech |
| Accent-soft | `#D1F8FF` | Highlight lembut, section alternatif |
| White | `#FFFFFF` | (peran berubah — lihat 1.2.1) |

**1.2.1 Background TIDAK putih polos.** Ganti dominasi background dari putih murni ke 1-2 level off-white/warm-neutral yang masih dalam keluarga RIT (turunan dari Ink/Muted dengan opacity rendah atau tint hangat sangat halus) — tujuan: terasa smooth, tidak steril/kaku, tapi tetap terang & profesional. **[DITENTUKAN SAAT IMPLEMENTASI: nilai hex konkret diserahkan ke eksekusi, dengan syarat harmonis dengan Ink/Muted, TIDAK generik abu Tailwind default.]**

**1.2.2 Warna pendukung (fungsional, bukan pengganti inti):** boleh ditambah untuk kebutuhan status/ikon/grafik/visualisasi yang butuh diferensiasi (mis. warna semantik sukses/warning/error, aksen sekunder untuk badge). Prinsip: warna inti RIT tetap dominan & jadi identitas utama; warna tambahan cuma pendukung fungsional, dipakai secukupnya, tidak menyaingi cyan sebagai signature.

**1.2.3 Larangan:** jangan pernah pakai warna generik di luar sistem (abu Tailwind default, biru/hijau standar tanpa penyesuaian) untuk elemen yang terlihat user.

### 1.2.4 Sistem Warna Kontras & Hierarki (agar tidak flat/kaku)

Gap yang harus ditutup: sekadar punya 5 warna dasar tidak cukup untuk membedakan status, tingkatan, dan kategori informasi tanpa terasa flat. Perlu **sistem turunan** dari palet inti, bukan warna acak baru:

**A. Warna Semantik/Status** (dipetakan dari kebutuhan yang sudah ada di aplikasi):

| Status | Contoh pemakaian nyata | Arah warna |
|---|---|---|
| Sukses/Selesai/Disetujui | Task done, kuis dikuasai, idea approved | Turunan hijau, desaturasi ringan (tidak neon), harmonis dgn cyan |
| Peringatan/Menunggu | Task overdue-soon, on_hold, pending | Turunan amber/kuning hangat, desaturasi ringan |
| Bahaya/Ditolak/Terkunci | Rejected, overdue, locked/error | Turunan merah, desaturasi ringan (tidak agresif) |
| Info/Netral | Draft, informasi umum | Accent-soft / Muted |
| Aktif/Sedang Berjalan | In_progress, current_unit, "kamu di sini" | Cyan (signature), dipakai tegas di sini |

Aturan: semua warna status **desaturasi ringan** (tidak neon mentah kecuali Cyan signature) supaya tetap dalam nuansa retro-tech-profesional, bukan norak/mainan.

**B. Warna Tingkatan/Level/Kategori:**
- Level Eksplorasi (1-6): tiap level BOLEH punya aksen warna berbeda secara bertahap (mis. gradasi dari Muted → Cyan seiring naik level) — memberi rasa progresi visual, bukan cuma angka berubah.
- Kategori konten/blok (dari sistem `content_blocks`): tiap tipe (Callout, Kode, dst) sudah punya kebutuhan warna beda — pastikan konsisten dengan sistem status di atas, bukan warna terpisah sendiri-sendiri.
- Badge/tag kategori (role, jenis project, dsb): pola warna konsisten & terbatas (jangan tiap kategori dapat warna baru tanpa aturan).

**C. Warna untuk Heading/Sub-heading/Body (hierarki teks):**
- Heading utama: Ink solid, ukuran & weight besar (Sora).
- Sub-heading: Ink dengan opacity sedikit diturunkan ATAU Muted gelap — harus tetap kebaca jelas tapi kelihatan bedanya dari heading utama.
- Body: Ink dengan opacity standar (bukan hitam pekat penuh untuk paragraf panjang, supaya tidak melelahkan mata) atau Muted untuk teks sekunder/caption.
- Prinsip: ada MINIMAL 3 tingkatan kontras teks yang jelas berbeda (heading > sub-heading > body/caption), bukan semua Ink solid rata seperti sekarang.

**D. Warna untuk Grafik/Visualisasi Data:**
- Kalau ada grafik/chart (progres, statistik), pakai turunan sistematis dari Cyan + warna pendukung 1.2.2 — bukan warna default library chart.

**Prinsip keseluruhan 1.2.4:** semua warna kontras ini adalah TURUNAN tersistematis dari palet inti RIT (1.2) — bukan tambahan bebas tanpa aturan. Tujuannya kaya secara visual tapi tetap satu bahasa desain, bukan berantakan.

### 1.3 Tipografi

Dipertahankan (sudah khas, jadi bagian identitas): **Sora** (heading/display), **Plus Jakarta Sans** (body), **JetBrains Mono** (data/angka/elemen retro-tech). Hierarki ukuran dipertegas (judul benar-benar besar, kontras jelas dgn body — bukan semua selevel seperti sekarang).

### 1.4 Identitas Grafis WEBI-SPACE (Dua Aset, Gaya Pixel Art) — FINAL

**Keputusan arah gaya:** kedua aset memakai gaya **Pixel Art / 8-bit** (ala Minecraft/game retro klasik). Dipilih karena: (1) percobaan generate gaya ilustrasi vektor/organik konsisten gagal — hasil terasa "AI banget"; (2) pixel art memperkuat tema Retro-Tech, dan lebih presisi dibuat sebagai kode (grid SVG) daripada gambar biasa.

**A. Logo Wordmark "WEBI-SPACE"**
- Font pixel siap pakai (open source, Google Fonts: kandidat "Press Start 2P", "VT323", "Silkscreen" — pilih yang tegas tapi tetap terbaca).
- Warna: Ink untuk teks utama, Cyan sebagai aksen kecil.
- Dieksekusi langsung oleh Claude Code, tanpa Claude Design.

**B. Maskot WEBI — DESAIN FINAL DIKUNCI ("Boxy Blocky")**

Hasil eksplorasi Claude Design dipilih & dikunci pada percobaan pertama (siluet kuat, tidak perlu iterasi lebih lanjut). Spesifikasi final:

- **Bentuk:** Kepala burung hantu abstrak-geometris, dua "telinga"/jambul runcing di atas sebagai penanda ikonik burung hantu tanpa perlu detail literal. Siluet solid, jelas terbaca di ukuran besar maupun kecil (diverifikasi terbaca jelas pada simulasi ~44px avatar chat).
- **Warna dasar:** Muted (`#979393`) sebagai warna badan/kepala dominan, outline Ink (`#1C1515`) di tepi bentuk (telinga, kontur luar).
- **Mata (elemen sorot utama):** kotak Cyan (`#05D9E7`) solid di tengah, dikelilingi Accent-soft (`#D1F8FF`) sebagai efek "berpendar/glow" — ini elemen yang paling menghidupkan karakter, konsisten dengan identitas AI/tech.
- **Cakar/kaki:** aksen kecil Cyan, konsisten dengan mata, tidak berlebihan.
- **Paruh/titik fokus tengah wajah:** aksen warna **Peach/Oranye hangat** (satu warna tambahan di luar 5 warna inti RIT, disetujui eksplisit sesuai ketentuan 1.2.2 "boleh 1 warna tambahan kalau menghidupkan karakter"). Berfungsi sebagai titik hangat di tengah komposisi, menyeimbangkan dominasi abu-abu+cyan yang bisa terasa dingin sendirian.
- **Karakter yang tercapai:** bijaksana-ramah, tidak intimidating, sesuai target.

**Status:** FINAL, siap diterjemahkan jadi kode (SVG grid pixel presisi) oleh Claude Code saat masuk tahap implementasi. Referensi visual: screenshot hasil Claude Design (disimpan Aye, dilampirkan saat prompt implementasi dibuat).

**Catatan warna tambahan (Peach) untuk konsistensi ke depan:** karena maskot ini akan jadi elemen visual yang sering tampil (avatar chat WEBI, kemungkinan ilustrasi/empty-state terkait WEBI), warna Peach ini sebaiknya HANYA dipakai terbatas pada konteks maskot/WEBI, TIDAK menyebar jadi warna UI umum di luar itu — supaya tetap "milik" karakter WEBI, bukan jadi warna sistem baru yang mengaburkan palet inti RIT.

### 1.5 Navigasi: Navbar + Popup Menu (Sidebar DIHILANGKAN)

**Keputusan besar:** sidebar dihapus total. Navigasi menu dipindah jadi **popup menu** dari navbar, terinspirasi pola Google Apps grid (ikon grid titik-titik rounded → popup berisi grid ikon berwarna + label teks di bawah tiap ikon, persis pola "Favorit Anda" Google/Chrome).

**Alasan:** sidebar dengan sedikit item (5-7 menu per portal) terasa generik-SaaS dan menyisakan ruang kosong di bawah — salah satu penyebab kesan "template AI". Popup menu lebih distinctive dan pas untuk jumlah menu yang tidak banyak.

**Struktur Navbar (final, update — tambah Badge Status Mode):**
- **Kiri:** Logo/wordmark WEBI-SPACE (lihat 1.4)
- **Kanan (urut):** Badge Status Mode (lihat catatan) → Menu Notifikasi (bell) → Ikon Navigasi Grid (popup menu) → Avatar/Akun (CTA "Keluar" di dalam sub-menu)
- **Badge Status Mode:** menunjukkan mode aktif user (Eksplorasi/Eksekusi) — HANYA tampil untuk user yang punya kapabilitas Mode Ganda (lihat Modul 2 §2.2: origin Eksekusi dengan akses baca Eksplorasi, atau origin Eksplorasi yang disetujui masuk Eksekusi). User dengan satu mode tetap TIDAK melihat badge ini (tidak relevan untuk mereka).
- **Perilaku:** Navbar **sticky/fixed** di semua portal & semua halaman — TIDAK ikut scroll, selalu terlihat.

**Popup Menu Navigasi (pengganti sidebar):**
- Dipicu klik ikon grid titik-titik di navbar kanan.
- Berisi grid menu SESUAI ROLE (isi & sumber data TETAP dari `config/navigation.php` yang sudah ada sejak 2.2.1 — cuma cara TAMPILnya yang berubah dari list sidebar jadi grid popup).
- Tiap item menu: ikon berwarna (bukan monokrom) + label teks di bawah ikon (pola sama seperti grid Google Apps).
- Item yang disabled (slot v2.0 belum aktif) tetap tampil non-aktif secara visual, konsisten dgn pola `enabled=false` yang sudah ada.

**Efek interaktif wajib di navbar & popup menu:**
- Hover state jelas di semua elemen interaktif.
- **Efek "jejak"** (indikasi visual) untuk halaman/menu yang sedang aktif/dikunjungi — user harus bisa merasakan navigasi itu hidup & responsif.

### 1.6 Breadcrumb (WAJIB, Baru — Pengganti Fungsi Orientasi Sidebar)

**Konsekuensi dari 1.5:** karena sidebar (yang tadinya jadi penanda orientasi permanen "kamu sedang di mana") dihilangkan, breadcrumb BUKAN LAGI nice-to-have, tapi **wajib** hadir konsisten di SEMUA halaman, SEMUA portal, sebagai pengganti fungsi orientasi tersebut.

- Format: hierarki jelas (mis. Beranda / Peta Kurikulum / Unit 3.2).
- Posisi konsisten di semua halaman (mis. tepat di bawah navbar, di atas konten).
- Membantu meringankan beban memori pengguna soal posisinya dalam aplikasi.

### 1.7 Arsitektur Informasi (Berlaku Lintas Portal)

- **Pengkategorian & pelabelan informasi** yang konsisten — istilah/label menu, section, dan komponen dipakai seragam di seluruh aplikasi (tidak ada dua istilah beda untuk konsep yang sama).
- **Sitemap & navigasi**: struktur navigasi (via `config/navigation.php` + popup menu 1.5) mencerminkan hierarki informasi yang logis per role.
- **Breadcrumb** (1.6) sebagai bagian dari sistem ini.
- **Ikon untuk komponen informasi**: dipakai konsisten untuk meringankan beban tekstual — user mengenali lewat ikon + warna kontras, bukan cuma teks panjang.
- **Warna kontras untuk elemen informasi** diatur sesuai kebutuhan (status, kategori, dll) — bukan flat/hitam-putih semua, tapi juga tidak asal random; sistematis sesuai palet 1.2.

### 1.8 Layout: Dinamis (3 Aspek)

1. **DRY (Don't Repeat Yourself)** secara teknis: komponen bersama (`<x-stat-card>`, `<x-content-blocks>`, dll) diperluas & dipertahankan sebagai prinsip wajib. Tidak ada duplikasi markup/style yang harusnya jadi satu komponen.
2. **Responsivitas presisi**: rapi & pas di semua ukuran perangkat (desktop, tablet, mobile) — bukan cuma "tidak pecah", tapi benar-benar disusun ulang secara presisi per breakpoint.
3. **Animasi/transisi antar state**: elemen & perpindahan state (hover, buka/tutup popup, pindah halaman, loading) punya transisi halus — TAPI wajib tetap smooth & performa cepat, tidak berat/lag.

### 1.9 Keputusan Terkunci — Ringkasan Modul 1

| Area | Keputusan |
|---|---|
| Tema visual | Retro-Tech, Profesional=Playful, kesan joy/enjoy |
| Warna inti | RIT resmi (Ink, Muted, Cyan, Accent-soft) — SAKRAL, dipertahankan |
| Background | Bukan putih polos — off-white/warm-neutral turunan RIT |
| Warna pendukung | Boleh ditambah untuk status/ikon/grafik, sifatnya fungsional bukan pengganti |
| Tipografi | Sora/Plus Jakarta Sans/JetBrains Mono dipertahankan, hierarki dipertegas |
| Logo | Wordmark "WEBI-SPACE" bergaya (ala Google), SVG oleh Claude Code, geometris/line-art |
| Sidebar | DIHAPUS TOTAL |
| Navigasi menu | Popup grid dari ikon navbar kanan (ala Google Apps), data dari `config/navigation.php` yang sudah ada |
| Navbar | Sticky/fixed semua halaman; kiri=logo, kanan=Notifikasi→Akun(+Keluar di submenu)→Grid Navigasi |
| Breadcrumb | WAJIB, konsisten, semua halaman semua portal |
| Arsitektur informasi | Kategorisasi & label konsisten, ikon+warna kontras utk kurangi beban teks |
| Layout | DRY teknis + responsif presisi + animasi smooth performa cepat |

### 1.10 Catatan Teknis untuk Implementasi (bekal Roadmap nanti)

- Perlu RECON dulu sebelum implementasi: cek kondisi `app.css`/token sekarang (ada sisa eksperimen token v2 dari sesi sebelumnya yang sempat diterapkan parsial ke dashboard Eksplorasi — pastikan status & bersihkan/lanjutkan sesuai arah final ini).
- Konversi sidebar→popup: `config/navigation.php` & logic active-route (dari 2.2.1c) DIPERTAHANKAN sepenuhnya sebagai sumber data — cuma layer presentasi yang diganti total.
- Breadcrumb: perlu dirancang cara generate-nya (per route/per halaman) — otomatis dari struktur navigasi atau manual per halaman, diputuskan saat implementasi.
- Logo & Maskot: dibuat via Claude Design dulu (lihat 1.4), setelah dipilih Aye baru diintegrasikan Claude Code sebagai komponen Blade reusable (logo ganti teks navbar; maskot dipakai di titik-titik yang relevan seperti avatar chat WEBI/empty state).

---

## MODUL 2 — Autentikasi

### 2.1 Halaman Login — Redesign Kreatif (First Impression)

Login adalah direct-landing pertama pengguna — kesan pertama aplikasi. Redesign harus outstanding, autentik, konsisten tema Retro-Tech.

**Elemen yang ditambahkan:**
- Layout kartu login dengan **komponen 2D interaktif** di background (mis. motif garis sirkuit/node yang bergerak halus, partikel kecil, atau elemen dekoratif ala retro-tech — bukan statis polos).
- **Maskot WEBI (hasil Modul 1, "Boxy Blocky") muncul menyapa pengguna** di halaman login, dengan **sapaan persuasif yang hangat** — mendorong semangat untuk login/membuka aplikasi (bukan sekadar "Selamat datang", tapi kalimat yang membangkitkan semangat belajar/kerja, konsisten nada suportif WEBI-SPACE).
- Form login sendiri tetap simpel & jelas (email, password, tombol masuk) — kreativitas ada di sekitar form, bukan mengorbankan kejelasan form itu sendiri.

**Prinsip:** kreatif & outstanding TAPI form tetap fungsional-jelas (ini pintu masuk aplikasi, jangan sampai bingung/menghambat login).

### 2.2 Sistem Mode Ganda (Dual-Mode Account) — DESAIN FINAL

**Keputusan:** dibangun sebagai modul kerja tersendiri di final v2.0 (dipilih Aye, sadar akan besarnya scope). Menggantikan rencana "promosi manual sederhana" — digantikan mekanisme lebih kaya berikut.

**Konsep inti:** satu akun (satu email/login) punya **role asal** (origin, ditentukan admin saat akun dibuat, TIDAK berubah) — dan secara opsional bisa memperoleh **akses mode tambahan**, dengan mekanisme berbeda tergantung arahnya (asimetris, disengaja).

**A. Origin = Eksekusi → Mode Eksplorasi (BEBAS, tanpa persetujuan, TAPI READ-ONLY):**
- Anggota Eksekusi bisa kapan saja beralih ke "Mode Eksplorasi" TANPA perlu pengajuan/persetujuan.
- Aksesnya **cuma baca/referensi**: bisa membuka Peta Kurikulum, membaca materi Unit, membaca Referensi — TIDAK bisa submit kuis/evaluasi, TIDAK ada `UserExplorationProgress` yang dibuat/diubah, TIDAK dapat poin, TIDAK ikut/masuk hitungan Leaderboard Eksplorasi.
- **Sistem lock/unlock unit DILEWATI** untuk mode ini — karena tidak ada progres yang bisa dijadikan syarat, seluruh materi ditampilkan terbuka untuk dibaca (murni referensi, bukan menjalani jalur belajar). *(Ini keputusan implementasi yang aku ambil dengan alasan di atas — beri tahu kalau maumu beda.)*
- UI wajib menampilkan **keterangan jelas** di halaman terkait (leaderboard, profil, dashboard saat mode ini aktif): "Kamu login sebagai anggota Eksekusi yang sedang menjelajah Eksplorasi — poin & peringkat tidak berlaku untuk mode ini."
- WEBI tetap bisa diakses sebagai bantuan baca kontekstual (personalisasi otomatis fallback aman karena tidak ada progress record — pola `?->` null-safe yang sudah ada di kode menangani ini).

**B. Origin = Eksplorasi → Mode Eksekusi (BUTUH PENGAJUAN + PERSETUJUAN ADMIN, LALU FUNGSIONAL PENUH):**
- Anggota Eksplorasi mengajukan permintaan (via UI baru, terhubung sistem notifikasi yang sudah ada) untuk mendapat akses mode Eksekusi.
- **Admin meninjau & memutuskan** (accept/reject) lewat panel admin baru.
- Setelah disetujui: anggota mendapat **akses fungsional penuh** ke Eksekusi — bisa di-assign task/proyek nyata oleh admin, sama seperti anggota Eksekusi asli.
- **Placement UI (klarifikasi Modul 4):** begitu admin approve, **link/kartu navigasi ke portal Eksekusi muncul di halaman Profil** anggota tsb (bukan cuma dropdown Akun) — titik pertama dia sadar aksesnya terbuka. Untuk bolak-balik selanjutnya (switch mode), lewat menu Akun (navbar).
- **Dua riwayat data berjalan paralel & permanen**: data Eksplorasi (poin, level, progres kurikulum) TETAP ADA, tidak terhapus/terarsip, sekalipun sedang aktif di mode Eksekusi. Data Eksekusi (assignment, task) terbangun terpisah sejak persetujuan.
- **Admin bisa mencabut akses ini kapan saja** — begitu dicabut, mode otomatis kembali ke Eksplorasi (mode asal), akses Eksekusi tertutup. *(Detail: nasib assignment/task yang sudah ada saat dicabut — perlu diputuskan lebih lanjut saat spesifikasi teknis detail, kemungkinan tetap sebagai riwayat, tidak otomatis unassign.)*

**C. Mekanisme umum:**
- Status mode aktif **disimpan permanen** (bukan sesi) — begitu switch, tetap di mode itu sampai di-switch lagi, termasuk lintas login/device.
- Switching dilakukan lewat menu Akun (navbar, hasil Modul 1).
- Admin **tidak** ikut sistem mode ganda ini (role admin tetap tunggal/tetap seperti sekarang).

**D. Dampak Teknis (bekal Roadmap, BUKAN detail final — perlu RECON khusus sebelum implementasi):**
- Skema: perlu field/tabel tambahan di `users` (origin role tetap `role` yang ada; perlu kolom baru utk status grant akses-mode-kedua + mode aktif saat ini) + tabel baru untuk alur pengajuan-persetujuan (mirip pola notifikasi yang sudah ada).
- **Prinsip wajib:** seluruh pengecekan akses (siapa boleh apa) HARUS lewat SATU titik pengecekan terpusat (satu Gate/middleware canonical), bukan dicek tersebar beda-beda cara di tiap route — supaya tidak ada halaman yang lupa dicek dan jadi celah keamanan.
- Leaderboard Eksplorasi: TIDAK perlu berubah query-nya — karena origin `role` anggota Eksplorasi yang disetujui masuk Eksekusi tetap `exploration_member` selamanya (cuma dapat tambahan akses), jadi otomatis tetap terhitung benar tanpa perubahan.
- Rute yang sekarang di-gate `role:execution_member` perlu diperluas jadi "role asal Eksekusi ATAU (origin Eksplorasi DENGAN akses granted DAN mode aktif=eksekusi)".
- Rute Eksplorasi tertentu (Peta Kurikulum, Unit-show, Referensi) perlu varian akses baca-saja untuk origin Eksekusi dalam mode Eksplorasi — TAPI rute submit kuis/checkpoint HARUS tetap tertutup untuk mereka.
- **Sebelum implementasi:** wajib RECON khusus untuk memetakan SEMUA rute/middleware yang tersentuh (pola sama seperti recon-recon sebelumnya), supaya tidak ada yang terlewat & regresi ke RBAC yang sudah teruji ketat.

**Status:** desain FINAL disetujui Aye. Detail teknis presisi (skema pasti, nama Gate, dll) akan difinalisasi saat modul ini masuk giliran kerja di Roadmap Eksekusi (dengan RECON terlebih dahulu).

### 2.3 Profil — Detail Dibahas per Modul

Mekanisme dasar Profil (edit nama, email read-only, ganti password, minat bidang) sudah selesai di Fase 2.2 (2.2.3), TIDAK diubah di sini. Konten TAMBAHAN yang berhubungan dengan role/keanggotaan (statistik Eksplorasi, ringkasan proyek Eksekusi, dst) dibahas & dirancang di Modul 3 dan Modul 4 masing-masing, bukan di Modul 2.

### 2.4 Sistem Avatar — Rancangan Awal (Lintas Modul 2/3/4)

Avatar bagian dari identitas Profil, tapi kontennya berbeda per portal — didokumentasikan di sini sebagai sistem, detail final tiap portal menyusul di Modul 3 & 4.

**Eksplorasi — Fox Bertingkat:**
- Satu karakter: **Rubah (Fox)** — simbol penjelajah cerdik, penasaran, adaptif.
- 5 tingkatan "kostum" yang makin epik seiring naik tingkat, unlock berdasar ambang poin: 50 / 100 / 300 / 500 / 1000+.
- **[BELUM DIPUTUSKAN — perlu keputusan Aye]:** sistem level Eksplorasi yang sudah ada berjumlah **6 tingkatan** (Level 1 "Pengenal" s.d. Level 6), sedangkan avatar disebut **5 tingkatan**. Perlu diputuskan: (a) avatar independen dari sistem level, threshold sendiri (5 tingkatan terpisah dari 6 level), atau (b) avatar disatukan dengan sistem level (perlu jadi 6 tingkatan, ambang menyesuaikan `level_thresholds` yang memang masih berstatus draft & akan dihitung ulang di 2.2.7).

**Eksekusi — 5 Hewan Pilihan Bebas:**
- 5 karakter: **Elang, Serigala, Singa, Harimau, Cheetah** — bebas dipilih, TANPA mekanisme unlock (konsisten: Eksekusi tidak punya sistem level/poin).
- Semua dalam gaya visual sama (pixel art, konsisten dgn maskot WEBI & Fox Eksplorasi).

**Gaya visual:** Pixel Art / 8-bit, konsisten dengan maskot WEBI (1.4) dan palet RIT + aksen sekunder sewajarnya.

**[BELUM DIPUTUSKAN — perlu keputusan Aye]:** "animasi avatar" — apakah dimaksud sprite bergerak (multi-frame, lebih berat) atau gambar statis dengan efek halus CSS (mis. kedip/napas ringan — lebih ringan, konsisten prinsip performa cepat di 1.8)? Rekomendasi: opsi kedua (statis + micro-animation CSS), kecuali ada alasan kuat untuk sprite penuh.

**Status kerja:** Eksplorasi visual (bentuk kostum Fox 5 tingkatan + 5 hewan Eksekusi) dimulai SEKARANG via Claude Design (tidak menunggu 2 keputusan di atas, karena eksplorasi bentuk independen dari angka threshold/jenis animasi). Prompt: `PROMPT_Claude_Design_Avatar.md`.

**DESAIN FINAL DIKUNCI:**

**Set A — Fox Eksplorasi (5 tingkat, independen dari sistem 6-level tampilan):**
| Tingkat | Nama | Ciri visual |
|---|---|---|
| 1 | Baru mulai | Fox polos, tanpa aksesori |
| 2 | Belajar aktif | + syal/kerah abu-abu |
| 3 | Percaya diri | Kerah dengan aksen cyan di tengah |
| 4 | Mahir | Aksen cyan meluas ke mata + kerah lebih besar |
| 5 | Master | Transformasi signifikan: helm abu-abu menutup kepala, aksen cyan di pelipis & bahu, kerah emas, elemen sparkle/bintang di sudut atas |

Ambang unlock: 50 / 100 / 300 / 500 / 1000+ poin (independen dari `level_thresholds` sistem 6-level tampilan yang masih draft).

**Set B — 5 Hewan Eksekusi (pilihan bebas, tanpa unlock):**
| Hewan | Sifat | Ciri pembeda visual |
|---|---|---|
| Elang | Tajam, visioner | Struktur mata ala teropong, ada paruh |
| Serigala | Tim, siaga | Dominan abu-biru dingin |
| Singa | Kepemimpinan | Surai gelap di atas kepala |
| Harimau | Kuat, presisi | Aksen garis-garis (stripe) |
| Cheetah | Cepat, gesit | Aksen totol-totol (spot) |

Semua share basis desain sama (wajah krem, mata beraksen cyan, struktur blok konsisten) — satu keluarga visual, lima kepribadian berbeda.

**Animasi:** statis + micro-animation CSS ringan (bukan sprite multi-frame), sesuai prinsip performa cepat (1.8).

**File referensi visual final:** `Eksplor_Level_1.png` s.d. `Eksplor_Level_5.png`, `Eksekusi_Elang.png`, `Eksekusi_Serigala.png`, `Eksekusi_Singa.png`, `Eksekusi_Harimau.png`, `Eksekusi_Cheetah.png` — disimpan Aye, dilampirkan saat prompt implementasi kode dibuat.

## MODUL 3 — Eksplorasi

### 3.0 Prinsip Umum

- Layout dinamis (DRY, responsif presisi, animasi smooth) & breadcrumb wajib di SEMUA halaman submodul Eksplorasi tanpa terkecuali — sesuai Modul 1.
- Warna background/kartu/aksen ikut sistem Modul 1 (§1.2, §1.2.4) — tidak flat hitam-putih.
- Navbar: lihat update struktur di §1.5 (Badge Status Mode ditambahkan).

### 3.1 Dashboard Eksplorasi — Layout Terkunci

Urutan & komposisi FINAL (sesuai wireframe Aye), atas ke bawah:

1. **Baris atas (3 kolom):** Ikon WEBI + sapaan persuasif dinamis (sudah ada dari 2.2.2a, dipertahankan) — Level saat ini + avatar sesuai level — *(kolom tengah judul sapaan sudah cukup lebar)*.
2. **Baris kedua (3 kolom):** Total Poin (stat-card) + Progres Keseluruhan (stat-card) [ditumpuk kiri] — Leaderboard (tengah, lebih tinggi, span 2 baris) — Daftar Praktik (kanan, PREVIEW — slot menunggu sampai modul Praktik dibangun, tampil sebagai "segera hadir" untuk sekarang).
3. **Baris ketiga (2 kolom):** Modul yang Sedang Dikerjakan (lebar) — Kartu WEBI AI (CTA ke WEBI Chat, versi rapi dari kartu "Tanya WEBI" yang sudah ada).
4. **Baris keempat (2 kolom):** Preview Forum Thread terbaru (lebar, BARU — perlu query baru: ambil beberapa thread terbaru) — Log Aktivitas (sudah ada).

Semua kartu ringkasan WAJIB py CTA/link ke halaman sumber lengkapnya.

### 3.2 Peta Kurikulum

**Klarifikasi arsitektur (menjawab kebingungan Aye):** sistem editor konten admin (dinamis, ala panel SaaS — direncanakan untuk Modul 5) itu MENGATUR ISI satu halaman Unit/materi (blok heading/teks/callout/dst), BUKAN halaman Peta Kurikulum itu sendiri. Peta Kurikulum cuma menampilkan daftar Modul/Unit + status lock dari database — begitu admin membuat Unit baru lewat panel nanti, otomatis muncul di Peta Kurikulum tanpa kerja tambahan (relasi Eloquent yang sudah ada menangani ini). **Tidak perlu penyambungan khusus.**

**Visual:** DIPERTAHANKAN seperti sekarang (vertikal/linear, circuit-path per modul — sudah terbukti stabil di mobile sejak 2.2.3). TIDAK ada redesign struktural. Cuma ikut re-skin warna global Modul 1.

**Tambahan:** ringkasan Level saat ini + Total Poin ditambahkan di bagian atas halaman.

### 3.3 Halaman Materi (Unit) + WEBI Split-Screen — REDESIGN (Menggantikan Slide-Over 2.2.3)

**Perubahan besar:** WEBI kontekstual di halaman Unit TIDAK LAGI berupa panel geser (slide-over) yang menutupi konten (hasil 2.2.3 sub-modul 4b-2). Diganti jadi **layout split-screen 3 kolom**:

1. **Kolom kiri — Daftar Isi Modul:** navigasi antar-unit dalam modul yang sama, bisa diklik langsung pindah unit, bisa **di-minimize**.
2. **Kolom tengah — Isi Materi:** konten unit (dari sistem `content_blocks`).
3. **Kolom kanan — Chat WEBI:** muncul saat diaktifkan (ikon WEBI di bagian bawah/toolbar), bisa **minimize/maximize**. Reuse komponen Chat yang sudah ada (2.2.3), cuma wadahnya berubah dari slide-over jadi kolom split.

**Elemen tetap ada:** stack poin/progres (atas kiri), breadcrumb (atas kanan).

**Catatan implementasi:** ini supersede hasil 2.2.3 sub-modul 4b-2 — saat masuk implementasi, bagian ini dibangun ulang dengan struktur 3-kolom, bukan revisi kecil dari slide-over.

### 3.4 Referensi

- **Admin dapat menambah referensi baru** (format: Judul/topik, deskripsi, hyperlink) lewat panel admin (dicatat untuk Modul 5).
- **Anggota juga bisa mengirim referensi sendiri** (format sama), tayang di halaman ini.
- **Keputusan moderasi (default, bisa diubah):** submission anggota **langsung tayang** (auto-publish), ditandai label pembeda "Dari Anggota" vs "Resmi Admin" untuk transparansi. Admin tetap punya hak hapus (moderasi reaktif, bukan approval-dulu) — dipilih karena tim kecil (12 orang saling kenal), approval-dulu cuma memperlambat berbagi. *(Bisa diubah ke mode approval kalau Aye mau lebih ketat.)*
- Redesign visual kartu & warna — ikut prinsip Modul 1, tidak flat hitam-putih.

### 3.5 Forum — DESAIN FINAL (Recon Selesai, Nol Migrasi)

- **Thread opsional terikat Modul/Unit ATAU general/bebas** — user memilih saat membuat thread baru.
- **Hasil recon:** skema `forum_threads` (`module_id`, `unit_id`) SUDAH nullable sejak migrasi v1.0 — **TIDAK BUTUH MIGRASI SAMA SEKALI**. Yang memaksa thread wajib terikat modul/unit murni pengecekan manual di kode (`Forum\Create::save()`), bukan aturan database.
- **Perubahan implementasi (kecil, terlokalisir):**
  1. Hapus/ubah blok pengecekan manual yang mewajibkan salah satu dari `moduleId`/`unitId` terisi — ganti jadi opsi eksplisit di UI (mis. toggle/radio "Terikat modul/unit" vs "Diskusi umum") supaya jelas ini pilihan sadar, bukan lupa isi.
  2. Perbaiki microcopy form: label "Pilih Modul" perlu tanda "(opsional)" seperti label unit yang sudah ada.
  3. `index.blade.php` dan `show.blade.php` TIDAK perlu disentuh — sudah pakai `@if` kondisional, otomatis menangani thread tanpa modul/unit.
- **Test:** `test_thread_without_module_or_unit_is_rejected` perlu diganti (bukan dihapus diam-diam) karena namanya sendiri mengunci perilaku lama yang dibalik — jadi test yang membuktikan thread general berhasil dibuat.
- Redesign visual — ikut prinsip Modul 1.

**Status: SIAP diimplementasikan, tidak ada yang menggantung.**

### 3.6 WEBI Chat — Redesign (Menggantikan Layout 2.2.3)

- **Nama dieksplisitkan jadi "WEBI Chat"** di seluruh UI (nav, judul halaman) — membedakan dari nama aplikasi.
- **Layout 2 kolom ala chatbot umum** (ChatGPT/Claude-style): **kolom kiri = daftar riwayat percakapan** (menggantikan dropdown panel dari 2.2.3 sub-modul 4a — sekarang jadi kolom persisten, mengisi ruang yang kosong sejak sidebar global dihapus), **kolom kanan = konten chat aktif**.
- **Ikon WEBI** di header/avatar chat diganti pakai maskot resmi (Boxy Blocky) begitu dikodekan — bukan ikon sparkle sementara.
- **Notice transparansi PIC** ("Percakapanmu dengan WEBI bisa diakses PIC...") dipindah dari ATAS ke **BAWAH panel ketik** (posisi sekarang di atas, dipindah ke bawah).

### 3.7 Praktik — Struktur SUDAH FINAL (Fase 2.1), Cuma Visual Browsing yang Ditunda

**Koreksi dari catatan sebelumnya:** Praktik BUKAN modul kosong yang belum dirancang — struktur & mekanismenya sudah final sejak Fase 2.1 (`Rancangan_Modul_Praktik_v2.md`), tinggal diimplementasikan sesuai rancangan itu:
- Challenge berjenjang (low/mid/high), dipilih bebas tanpa gating dari progres Materi.
- Track Map (langkah panduan) memakai sistem blok konten YANG SAMA dengan Materi (`content_blocks`, polymorphic `blockable_type='ChallengeStep'`) — otomatis konsisten visual, tidak perlu sistem terpisah.
- Submission (link/teks/file), status `pending`/`disetujui`/`perlu_revisi`, submit ulang tanpa batas dengan label versi eksplisit.
- Review didelegasikan PIC ke `execution_member` per-submission (`assigned_reviewer_id`, RBAC ketat — reviewer cuma akses submission yang ditugaskan ke dia, prinsip sama dengan pelajaran RBAC attachment v1.0).
- Poin diminishing return: submission pertama disetujui dapat poin penuh, submit ulang berikutnya dapat lebih kecil (bukan nol).
- Tidak ada starter code, cukup instruksi konkret di track map.

**[SUPERSEDE]** Catatan avatar di rancangan lama (§8, "avatar unlock ikut 6 level yang sudah ada") **digantikan** keputusan Modul 2 §2.4 (Fox 5-tingkat, threshold independen 50/100/300/500/1000+).

**Yang MASIH ditunda:** HANYA gaya visual halaman browsing/challenge (konsep LeetCode/W3Schools/roadmap.sh/lainnya) — Aye belum punya arah. Ini tidak menghalangi apa pun karena struktur datanya sudah pasti; visual bisa diputuskan belakangan tanpa mengubah rancangan mekanismenya.

### 3.8 Profil — Tambahan Konteks Eksplorasi

Melengkapi mekanisme dasar Profil (2.2.3, tidak diubah):
- **Ringkasan Poin, Level, dan Avatar** ditambahkan ke halaman Profil.
- **Kartu galeri avatar**: menunjukkan avatar level mana saja yang sudah terbuka (dari 5 tingkat Fox, Modul 2 §2.4).
- **Layout dimaksimalkan** desktop & mobile — tidak boleh terasa kosong/flat seperti sekarang.
- **Kalender Aktivitas (heatmap ala GitHub/Claude Code) — FITUR BARU:** grid warna per hari menunjukkan aktivitas penyelesaian (materi/kuis/praktik). **Deteksi otomatis dari sistem** (TIDAK perlu check-in manual) — diturunkan dari timestamp yang SUDAH ADA (`UserUnitProgress.completed_at`, `CheckpointCompletion.completed_at`, nanti submission Praktik), dikelompokkan per tanggal. Tidak butuh mekanisme pelacakan baru, murni query + visualisasi dari data yang sudah tersimpan.

## MODUL 4 — Eksekusi

### 4.0 Prinsip Umum

Layout dinamis, breadcrumb wajib semua halaman, warna ikut Modul 1 — sama seperti Modul 3, tidak ada pengecualian.

**Prinsip implementasi baru (berlaku modul ini, baik diterapkan modul lain):** saat implementasi, buat **data dummy realistis** dulu sebelum finalisasi visual — supaya review tampilan dilakukan dengan konten konkret, bukan state kosong yang sulit dinilai.

### 4.1 Dashboard Eksekusi — Komponen (Analisis, bukan wireframe kaku)

Karena Aye belum py wireframe pasti untuk dashboard ini (beda dari Modul 3), berikut daftar komponen hasil analisis kebutuhan, urutan final diputuskan saat implementasi bersama data dummy:

1. Greeting + Badge Status Mode (kalau user berkapabilitas dual-mode)
2. Stat-card ringkas: Proyek diikuti, Task aktif (Todo/In Progress/In Review), **Praktik menunggu direview** (BARU — cuma tampil kalau user sedang di-assign sebagai reviewer, lihat §4.5)
3. **Ringkasan Kalender** (BARU — beberapa tanggal terdekat, bukan kalender penuh, terus diperbarui)
4. Kartu ringkasan per-Proyek (sudah ada dari 2.2.3, redesign visual)
5. Alert Panel (sudah ada, scoped ke proyek anggota)
6. **Kartu Antrian Review Praktik** (BARU, direct link ke submission yang ditugaskan — lihat §4.5)
7. **Kartu cepat "Ajukan Project Idea"** (BARU, CTA langsung)

Semua kartu WAJIB py CTA/link ke halaman sumber. Visual: hindari kartu kotak seragam generik — pakai grid asimetris/hierarki ukuran + aksen sirkuit sudut (diselesaikan visual saat implementasi, dicek di browser).

### 4.2 Project Ideas

- Form: HANYA "Judul Ide" wajib. "Deskripsi singkat" dan "Tujuan/relevansi" jadi OPSIONAL.
- **Satu halaman, dua bagian visual** (BUKAN halaman terpisah): section "Menunggu Keputusan" (status pending) dan section "Riwayat" (approved/rejected, warna status sesuai §1.2.4) — dipisah secara ruang/space dalam satu halaman yang sama.

### 4.3 Kalender — SUDAH DIRANCANG (Rancangan Fase 2.1), Penamaan Diluruskan

**Klarifikasi:** modul Kalender ini SUDAH dirancang lengkap sejak Fase 2.1 (`Rancangan_Modul_Manajemen_Proyek_v2.md`), dan rancangannya SUDAH sesuai maksud Aye — bukan "kalender pribadi", tapi kalender terpadu berbasis tim/proyek. Cuma penamaan yang diluruskan:

- **Dua kategori** (warna sesuai palet RIT): **Kegiatan** (Ink, otomatis dari deadline Task/Milestone yang sudah ada — tidak disimpan dobel) dan **Acara** (Cyan, input manual — lomba, meeting, kumpul rutin, dll di luar progress kerja proyek).
- **Dua tingkat tampilan** dari sumber data sama: tab "Kalender" di halaman tiap proyek (filter satu proyek), dan **halaman "Kalender"** tersendiri (bukan lagi disebut "Personal" — namanya menyesatkan) yang menggabungkan Kegiatan+Acara dari SEMUA proyek yang diikuti user, plus Acara umum lintas-proyek.
- Tabel baru `calendar_events` untuk Acara manual; Kegiatan dihitung on-the-fly dari Task/Milestone (tidak ada tabel baru).
- **Tambahan dari diskusi ini:** ringkasan Kalender di dashboard (§4.1 poin 3) — beberapa tanggal terdekat, bukan kalender penuh.

**Status: sudah final secara struktur, siap dieksekusi bagian dari Roadmap 2.2.5 (Manajemen Proyek) — bukan pekerjaan baru dari nol.**

### 4.4 Akun/Profil — Tambahan Konteks Eksekusi

Melengkapi mekanisme dasar Profil (2.2.3, tidak diubah):
- Ringkasan: jumlah proyek diikuti, avatar (5 pilihan bebas, Modul 2 §2.4).
- **Kalender Aktivitas (heatmap ala GitHub/Claude Code)** — pola SAMA seperti Modul 3 §3.8, sumber data timestamp yang sudah ada (Task completion, Progress Update), deteksi otomatis tanpa check-in manual.
- **"Kontribusi per Proyek" — DESAIN FINAL (recon selesai, KEPUTUSAN: TIDAK bikin field peran sama sekali):** Recon (`RECON_project_member_peran.md`) menemukan `project_members` tidak punya kolom peran, dan menyarankan tambah field (teks bebas atau enum). **Aye menolak keduanya** — realitas kerja tim tidak serapi taksonomi peran tetap (FE/BE/Fullstack/dll), bisa jadi "fullstack per fitur" atau pola lain yang tidak terprediksi. **Solusi yang dipilih: TIDAK ada field peran baru sama sekali.** Sebagai gantinya, "kontribusi" ditampilkan otomatis dari data `task_assignments` yang SUDAH ADA — untuk tiap proyek yang diikuti user, tampilkan daftar task yang pernah di-assign ke dia di proyek itu, dengan link ke ringkasan proyek. **Nol migrasi, nol keputusan skema baru** — murni query baru (join `TaskAssignment` per user, kelompokkan per proyek).
- **Ruang/bidang info lintas-mode** (status dual-mode, lihat Modul 2 §2.2) ditambahkan ke Profil.
- Layout dimaksimalkan, terutama desktop — hasil akhir terasa seperti halaman portofolio, bukan sekadar form data.

### 4.5 Notifikasi & Antrian Review Praktik (Lintas Modul 3-4) — KEPUTUSAN

Anggota Eksekusi yang ditugaskan admin untuk mereview submission Praktik (Modul 3 §3.7, `assigned_reviewer_id`) butuh cara mengakses & bertindak atas tugas itu.

**Keputusan:** kemampuan mereview submission Praktik diakses SEBAGAI BAGIAN DARI PORTAL EKSEKUSI LANGSUNG (halaman/kartu tersendiri), **TIDAK memerlukan switch ke mode Eksplorasi**. Gate akses berdasarkan "apakah submission ini ditugaskan ke user ini" (`assigned_reviewer_id = user`), BUKAN berdasarkan mode aktif. Alasan: mereview adalah tugas administratif yang di-assign admin, bukan "memakai Eksplorasi untuk belajar" — jadi tidak perlu melibatkan mekanisme Mode Ganda sama sekali.

- Notifikasi (sistem yang sudah ada) mengarahkan langsung ke halaman review ini.
- Kartu di dashboard (§4.1 poin 6) sebagai entry point tambahan.

### 4.6 Manajemen Proyek — Prinsip Kualitas (untuk Roadmap 2.2.5)

Aye menegaskan standar kualitas untuk modul besar Manajemen Proyek (belum dikerjakan, Roadmap 2.2.5): **setara platform manajemen proyek matang** (ClickUp, Taiga, Notion) — bukan versi sederhana/setengah jadi. Dicatat sebagai prinsip kualitas yang mengikat saat modul itu dikerjakan, struktur teknisnya sendiri sudah final di `Rancangan_Modul_Manajemen_Proyek_v2.md` (tab Kanban/Roadmap/Gantt/Kalender/Forum/Anggota, subtask nested, dependency Gantt, dll — lihat ringkasan di §4.3).

## MODUL 5 — Admin

### 5.0 Prinsip Umum

**Berbeda dari Modul 1-4:** estetika visual TIDAK ditekankan (hanya PIC yang akses, kepadatan informasi bukan masalah). Yang WAJIB: **arsitektur informasi terstruktur, terkategori, informatif, eksekutif, implementatif** — admin harus bisa scan cepat dan langsung bertindak, bukan tersesat di antara banyak data.

**Metodologi dokumen ini:** disusun dengan menelusuri ULANG seluruh keputusan Modul 1-4 untuk mengidentifikasi SETIAP konsekuensi yang butuh tindakan/panel Admin — supaya tidak ada satu komponen pun yang terlewat dan berdampak ke keberlangsungan ekosistem.

### 5.1 Kelola Kurikulum — PUSAT PERHATIAN (Konsep "OrderHero-Style")

**Ini yang PALING ditekankan Aye berulang kali, dan ternyata BELUM PERNAH dirinci sebagai kebutuhan Admin** — 2.2.4a baru membangun fondasi teknis (skema `content_blocks` polymorphic + renderer generik, lihat status Roadmap), TAPI panel tempat Admin benar-benar mengarang konten belum pernah masuk rancangan detail. Ini WAJIB jadi bagian implementasi 2.2.4 lanjutan.

**A. CRUD Modul & Unit:**
- Kelola Modul: buat/edit/hapus/atur urutan Modul.
- Kelola Unit dalam Modul: buat/edit/hapus/atur urutan, set `prerequisite_unit_id`, `point_value`, tipe evaluasi (`quiz_multiple_choice`/`matching`/`ordering`/`essay`/`practice`/`none`).
- Saat ini (sesuai 2.2.4a & rancangan lama) Unit/Modul HANYA bisa dibuat lewat `CurriculumSeeder` — TIDAK ADA UI admin untuk ini. Modul 5 menutup gap ini sepenuhnya.

**B. Editor Konten Blok per Unit (inti konsep "OrderHero"):**
- Form terstruktur: pilih tipe blok dari dropdown (Heading, Teks, Gambar, Callout, Kode, Video, List, Tabel, Custom HTML), isi form sesuai tipe, blok masuk daftar berurutan.
- Atur ulang urutan blok (tombol naik/turun, bukan drag-drop — sesuai rancangan asli, lebih murah dibangun).
- Edit/hapus blok kapan saja.
- **Custom HTML** sebagai escape hatch — inilah yang menjawab keinginan "custom HTML/CSS/JS" ala platform SaaS: admin bisa sisipkan kode mentah kapan pun dibutuhkan visualisasi di luar tipe blok standar (SUDAH disanitasi otomatis oleh sistem sejak 2.2.4a, aman dari XSS).
- **Preview** sebelum publish — admin lihat hasil render persis seperti yang akan dilihat anggota.
- Sistem yang SAMA persis dipakai untuk Track Map Praktik (§5.2) — satu implementasi, dua pemakai (DRY, sesuai prinsip Modul 1).

**C. Kelola Evaluasi/Kuis per Unit — DESAIN FINAL (Recon Selesai):**
- Data tersimpan di tabel `unit_evaluations` (bukan kolom di `units`), field: `unit_id`, `question_type` (**TANPA prefix `quiz_`** — beda dari `units.evaluation_type` yang PAKAI prefix `quiz_multiple_choice` dst — catatan gotcha wajib diperhatikan saat bangun form, gampang tertukar), `question_text`, `options` (json), `correct_answer` (json), `sort_order`.
- **Kompleksitas berbeda per tipe, form TIDAK bisa satu-generik-untuk-semua:**
  - `multiple_choice`: sederhana — repeater teks bebas untuk opsi + pilih satu sebagai kunci.
  - `ordering`: sederhana secara struktur, TAPI ada keputusan UX (lihat di bawah).
  - `matching`: PALING RUMIT — form perlu repeater pasangan kiri-kanan, `correct_answer` diturunkan OTOMATIS dari pasangan itu (bukan input terpisah, mencegah admin salah ketik dua kali).
  - `essay`/`practice`: paling sederhana, cuma teks instruksi.
- Satu Unit bisa punya BEBERAPA baris soal (`sort_order`), TERMASUK kasus campuran (quiz + essay dalam satu unit) — form harus mendukung banyak baris per unit dengan tipe campuran, bukan dibatasi satu tipe per unit.
- **[KEPUTUSAN AYE]** Bug UX ditemukan lewat recon: untuk soal `ordering`, `options` (urutan tampil) dan `correct_answer` (kunci) di data sekarang SELALU identik — user bisa submit tanpa mengubah apa pun dan otomatis benar. **DIPUTUSKAN: diperbaiki sekalian saat bangun form admin** — form akan meminta admin input urutan tampil (teracak) TERPISAH dari kunci jawaban, supaya soal benar-benar menguji.

**D. Migrasi 67 Unit Lama:**
- Sudah direncanakan (`RECON_konten_dinamis.md`, dikerjakan manual dibantu Claude Code) — dikerjakan lewat panel/editor yang sama begitu B selesai dibangun.

**E. [Dependensi Teknis, bukan UI Admin] Update WEBI:**
- `CurriculumContextBuilder` (WEBI) WAJIB diupdate membaca dari `content_blocks` baru, bukan `units.content` lama, begitu migrasi (D) berjalan — ini pekerjaan backend, dicatat sebagai bagian tak terpisahkan dari 2.2.4, bukan panel admin baru.

### 5.2 Kelola Praktik

- **CRUD Challenge**: judul, deskripsi, level (low/mid/high), poin reward, status (draft/published).
- **Track Map Editor**: MEMAKAI SISTEM YANG SAMA dengan §5.1.B (`content_blocks`, `blockable_type='ChallengeStep'`) — tidak ada editor terpisah, satu implementasi dua pemakai.
- **Antrian Review Submission**: daftar submission `pending`, admin bisa (a) review & kasih feedback langsung, atau (b) delegasikan ke satu `execution_member` tertentu (`assigned_reviewer_id`) — assignment manual per-submission, memicu notifikasi ke reviewer (lihat Modul 4 §4.5, reviewer akses dari portal Eksekusi langsung tanpa switch mode).
- **Poin diminishing return — DIKUNCI:** angka tetap di kode, BUKAN diatur admin. Submission pertama disetujui dapat poin penuh, submit ulang berikutnya dapat 70% dari poin submission sebelumnya (bukan dari poin penuh — menurun bertahap tiap versi). Tidak perlu UI konfigurasi tambahan.

### 5.3 Kelola Mode Ganda (Modul 2 §2.2) — FITUR ADMIN BARU

- **Antrian Permintaan Mode Eksekusi**: daftar pengajuan dari anggota Eksplorasi yang ingin akses Eksekusi, admin accept/reject.
- **Cabut Akses**: dari halaman Manajemen Akun (user yang sudah py akses ganda), admin bisa mencabut kapan saja (sesuai Modul 2 §2.2.B).
- Ini fitur yang SAMA SEKALI BARU, belum ada padanannya di v1.0 — perlu dibangun dari nol (mirip pola sistem notifikasi yang sudah ada).

### 5.4 Kelola Referensi (Modul 3 §3.4) — DESAIN FINAL (Recon Selesai)

**Hasil recon:** modul ini PALING "dari nol" dibanding fitur lain — sekarang cuma halaman baca statis dari seeder, TIDAK ADA form/Livewire component untuk menulis sama sekali (beda dari Forum yang formnya sudah ada). Perlu:
- **Migrasi baru** (additive, risiko rendah): tambah `created_by` (nullable — NULL berarti "Dari Admin"/konten resmi, otomatis benar untuk semua data lama tanpa backfill) + kolom `description` baru.
- **Temuan tambahan & keputusan:** kolom `source_name` yang ada sekarang SEHARUSNYA label pendek ("Mozilla"), tapi di data nyata isinya kalimat deskripsi panjang. **DIKUNCI: dibiarkan apa adanya** — entri lama tetap dengan `source_name` panjangnya (tidak dimigrasi ke `description` baru), entri baru ke depan mengikuti pemisahan yang benar (source_name = label pendek, description = penjelasan). Cuma soal kerapian historis, bukan bug fungsional, migrasi data tidak sepadan risikonya.
- **Livewire component baru** untuk form submit (belum ada sama sekali) + validasi URL.
- Label "Dari Anggota"/"Dari Admin" — pekerjaan kecil setelah kolom `created_by` ada (pola sama seperti badge target Forum peer/pic yang sudah ada).
- Admin hapus — pekerjaan kecil, pola otorisasi yang sudah konsisten di halaman lain.

**Status: siap diimplementasikan, tidak ada yang menggantung.**

### 5.5 Fitur Existing — Dipertahankan, Diperluas Sesuai Roadmap

Tidak berubah struktural di Modul 5 ini, cuma disebutkan supaya peta lengkap:
- **Manajemen Akun** (CRUD user, ubah role, reset password) — sudah ada, dipertahankan.
- **Manajemen Proyek** (CRUD proyek, milestone, assign anggota) — sudah ada, akan DIPERLUAS signifikan saat Roadmap 2.2.5 (Kalender, Roadmap, Gantt+dependency, Forum, subtask — lihat Modul 4 §4.3, §4.6) dikerjakan. Bukan pekerjaan Modul 5 sekarang.
- **Project Ideas** (approve/reject) — sudah ada, tampilan mengikuti pembaruan Modul 4 §4.2 (dua section: menunggu + riwayat).
- **Log WEBI** (monitoring percakapan + guardrail flag) — sudah ada, tidak berubah.

### 5.6 Dashboard Admin — Update Cakupan

Dashboard admin (sudah diredesign visual di 2.2.3 Batch 1) perlu DIPERLUAS cakupannya untuk mencerminkan seluruh area baru Modul 5:
- Ringkasan status Kurikulum (jumlah modul/unit, mana yang draft/published).
- **Antrian Review Praktik** (jumlah pending).
- **Antrian Permintaan Mode Ganda** (jumlah pending).
- Item existing (leaderboard, ringkasan proyek, alert, WEBI log) dipertahankan.

**Prinsip:** dashboard admin jadi "pusat komando" — sekali lihat, admin tahu semua yang butuh tindakannya, dengan link langsung ke tiap antrian.

### 5.7 Peta Navigasi Admin (Ringkasan Struktur)

Kategori menu admin yang lengkap, hasil analisis di atas: Dashboard — Manajemen Akun — Kelola Kurikulum (Modul, Unit, Konten Blok, Evaluasi) — Kelola Praktik (Challenge, Track Map, Antrian Review) — Kelola Referensi — Permintaan Mode Ganda — Manajemen Proyek (existing, diperluas nanti) — Project Ideas — Log WEBI.
