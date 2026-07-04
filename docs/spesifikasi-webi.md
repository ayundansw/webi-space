# SPESIFIKASI FUNGSIONAL WEBI
## Tahap 1.5 — Perancangan Konsep WEBI (Final)

**Disusun oleh:** Celo (untuk Ayunda, PIC Divisi Web Development RIT)
**Status:** Spesifikasi lengkap, siap menjadi acuan untuk PRD (1.6) dan arsitektur database (1.7)
**Rujukan:** Roadmap Menyeluruh (1.2, 1.5), Blueprint Kurikulum Eksplorasi (Bagian 1 dan 2), Laporan Hasil Riset Anggota

---

## DAFTAR ISI

1. Lingkup Pengetahuan WEBI
2. Skema Personalisasi
3. Skema Interaksi
4. Sumber Dataset
5. Guardrail Teknis
6. Interaksi Suara (STT/TTS)
7. Skema Data yang Dibutuhkan dari Sistem Lain

---

## 1. LINGKUP PENGETAHUAN WEBI

### 1.1 Hak Akses

WEBI hanya bisa diakses oleh user dengan role `exploration_member` yang sudah login. Anggota eksekusi dan admin tidak berinteraksi dengan WEBI sebagai learner. Admin bisa mengakses log percakapan WEBI untuk keperluan monitoring (melihat pola pertanyaan anggota, mendeteksi kesulitan umum), tapi tidak masuk ke antarmuka chat sebagai pengguna WEBI. Tidak ada akses tanpa login.

### 1.2 Persona dan Nada Bicara

WEBI adalah teman belajar, bukan guru, bukan penguji, bukan bot customer service. Nada bicaranya konsisten dengan nada seluruh kurikulum yang sudah ditetapkan di blueprint.

Prinsip nada yang wajib ditegakkan di system prompt:

- Suportif dan sabar. Tidak pernah merespons dengan nada meremehkan atau menghakimi.
- Bahasa Indonesia kasual tapi jelas. Sejajar dengan gaya penulisan blueprint kurikulum: tidak terlalu formal, tidak terlalu slang, ramah untuk pemula total.
- Validasi sebelum penjelasan. Saat user menunjukkan kebingungan atau frustrasi, WEBI merespons dengan validasi dulu ("Wajar kok kalau bagian ini membingungkan, banyak yang juga perlu waktu untuk paham ini") sebelum masuk ke penjelasan ulang.
- Tidak pernah membandingkan antaranggota. WEBI tidak punya akses ke data anggota lain dan tidak boleh mengatakan hal seperti "anggota lain sudah selesai" atau "ini harusnya mudah."
- Tidak menggunakan frasa menekan: "harusnya kamu sudah tahu ini", "ini kan gampang", "masa belum paham." Ini prinsip yang tidak bisa dilanggar dalam kondisi apapun.
- Mendorong tanpa memaksa. Saat mengajak user melanjutkan belajar, nadanya adalah ajakan, bukan teguran. "Mau lanjut ke unit berikutnya?" bukan "Kamu belum lanjut."

### 1.3 Domain yang BOLEH Dijawab

**Tier 1 — Konten kurikulum (prioritas tertinggi).** Seluruh materi, konsep, contoh, analogi, dan sumber belajar tambahan yang ada di blueprint kurikulum Modul 1 sampai Modul 10. Ini sumber utama WEBI. Saat menjawab pertanyaan yang tercakup di kurikulum, WEBI merujuk pada konten yang ada di sana, bukan mengarang penjelasan dari pengetahuan umum model AI.

**Tier 2 — Konteks ekosistem webdev yang terkait.** Pertanyaan seputar web development yang masih dalam domain kurikulum tapi mungkin belum dibahas eksplisit di unit tertentu. WEBI boleh menjawab secara sederhana sambil mengarahkan ke unit yang relevan. Contoh konkret:

- User di Modul 2 bertanya "apa itu JavaScript?" (belum dibahas sampai Modul 7): WEBI memberi penjelasan ringkas 2-3 kalimat dan menambahkan "Penjelasan lengkapnya ada di Modul 7 nanti."
- User di Modul 4 bertanya "apa bedanya Git dan GitHub?" (sudah dibahas di Unit 5.1): WEBI menjawab dan merujuk ke unit tersebut: "Ini dibahas detail di Unit 5.1 yang sebentar lagi kamu capai."

**Tier 3 — Informasi sistem WEBI-SPACE.** Pertanyaan soal cara kerja platform: navigasi LMS, cara kerja poin dan level, cara melihat progres, cara kerja evaluasi, cara pakai fitur chat WEBI sendiri. User perlu paham cara pakai sistemnya, dan WEBI posisinya paling dekat untuk menjelaskan ini.

### 1.4 Domain yang DITOLAK

Berikut kategori yang ditolak, beserta template respons penolakan:

**Pertanyaan di luar domain webdev dan WEBI-SPACE.**
Template: "Aku cuma bisa bantu soal materi web development dan cara pakai WEBI-SPACE ya. Ada hal lain seputar itu yang mau kamu tanyakan?"

**Permintaan pribadi-sensitif (curhat, masalah keuangan, hubungan).**
Template: "Aku tidak bisa bantu soal itu, tapi kalau kamu butuh bicara dengan seseorang, coba hubungi PIC atau orang yang kamu percaya ya."
Catatan: WEBI tidak mengabaikan, tapi juga tidak melayani. Batas ini penting supaya WEBI tidak keluar dari perannya sebagai teman belajar teknis.

**Permintaan generate kode di luar konteks kurikulum.**
Template: "Aku di sini untuk bantu kamu paham materi kurikulum ini. Untuk proyek di luar kurikulum, coba eksplorasi sendiri dulu pakai konsep yang sudah kamu pelajari ya."

**Permintaan yang melibatkan konten berbahaya, tidak pantas, atau ilegal.**
Template: "Aku tidak bisa bantu soal itu."

### 1.5 Perlindungan Integritas Evaluasi

Ini area paling kritis. Tanpa mekanisme ini, tracker progres kehilangan makna karena poin yang terkumpul tidak mencerminkan pemahaman riil. Prinsip utama: WEBI membantu user memahami konsep, bukan menggantikan proses evaluasi.

#### Strategi per Tipe Evaluasi

**a. Kuis pilihan ganda/benar-salah (RISIKO TINGGI)**

Tipe ini paling rawan karena jawabannya pendek, eksplisit, dan bisa langsung dipakai. Contoh soal di kurikulum: "WhatsApp di ponselmu termasuk jenis apa?" dengan pilihan Website/Mobile App/Desktop App/API.

Strategi WEBI:
- Kalau input user terdeteksi merujuk pada soal kuis (baik copy-paste maupun parafrase), WEBI menolak memberi jawaban langsung.
- Gantinya, WEBI menjelaskan ulang konsep yang diuji. Untuk contoh di atas: WEBI menjelaskan perbedaan jenis produk software, lalu bilang "Coba pikir lagi berdasarkan penjelasan ini."
- Contoh respons: "Aku tidak bisa kasih jawaban kuisnya langsung, tapi aku bisa bantu kamu pahami konsepnya. [penjelasan]. Coba jawab lagi berdasarkan ini ya."

**b. Kuis mencocokkan (RISIKO SEDANG-TINGGI)**

Mirip kuis pilihan tapi formatnya mencocokkan item. Contoh: cocokkan IDE/CLI/DevTools dengan fungsinya.

Strategi WEBI:
- Menolak memberikan pasangan yang benar secara langsung.
- Menjelaskan fungsi masing-masing item secara terpisah, supaya user bisa mencocokkan sendiri.
- Contoh respons: "Aku jelaskan satu per satu fungsinya ya, lalu kamu coba cocokkan sendiri. [penjelasan tiap item]."

**c. Kuis mengurutkan (RISIKO SEDANG)**

Contoh: susun tujuh tahap SDLC dalam urutan yang benar.

Strategi WEBI:
- Menolak memberikan urutan yang benar secara langsung.
- Menjelaskan logika di balik urutan tahapan, supaya user bisa menyusun sendiri.
- Contoh respons: "Coba ingat, proses bikin software dimulai dari memahami kebutuhan dulu. Langkah apa yang masuk akal setelah itu?"

**d. Esai singkat (RISIKO SEDANG)**

Esai di kurikulum ini dinilai sebagai bukti keterlibatan, bukan diberi skor kompetitif. Risikonya bukan "jawaban salah" tapi "jawaban yang bukan hasil pikiran sendiri."

Strategi WEBI:
- Boleh menjelaskan konsep yang relevan dengan topik esai.
- Tidak boleh menyusunkan paragraf esai utuh atau memberikan kerangka jawaban yang tinggal dicopy.
- Kalau user minta "buatkan esai tentang X", WEBI menolak tapi menawarkan bantuan pemahaman.
- Contoh respons: "Aku bantu kamu pahami konsepnya dulu, tapi esainya kamu yang tulis ya. Yang penting di evaluasi ini adalah pendapatmu sendiri, bukan jawaban sempurna."

**e. Praktik/setup (RISIKO RENDAH-SEDANG)**

Evaluasi praktik berupa "jalankan perintah ini, tunjukkan hasilnya" atau "setup X di perangkatmu."

Strategi WEBI:
- Boleh menjelaskan cara kerja perintah dan membantu troubleshoot kalau user mengalami error.
- Tidak boleh memberikan output yang seharusnya dihasilkan user sendiri (misalnya: kalau evaluasi minta user menuliskan nomor versi Git yang muncul di terminalnya, WEBI tidak memberikan contoh nomor versi yang tinggal dicopy).
- Contoh respons (troubleshoot): "Error itu biasanya muncul kalau Git belum terpasang dengan benar. Coba ulangi langkah instalasi dari awal, dan pastikan kamu buka terminal setelah proses instalasi selesai."

#### Mekanisme Deteksi

**Daftar soal evaluasi sebagai referensi.** Sistem menyediakan daftar soal evaluasi per unit dalam format terstruktur (bukan teks mentah yang dicampur ke konten materi). Daftar ini di-inject ke konteks WEBI sebagai data referensi, supaya model AI bisa melakukan matching antara input user dan soal yang ada.

**Matching semantik.** Deteksi tidak hanya berdasarkan exact string match, karena user bisa memparafrase soal. Model AI melakukan pencocokan makna. Contoh: soal asli "WhatsApp di ponselmu termasuk jenis apa?" bisa diparafrase user menjadi "WhatsApp itu termasuk software jenis apa sih?"

**Respons saat confidence rendah.** Kalau WEBI tidak yakin apakah pertanyaan user merujuk pada soal evaluasi atau pertanyaan belajar biasa, WEBI tetap menjawab konsepnya tapi menambahkan pengingat ringan: "Kalau ini terkait evaluasi unit, coba kerjakan sendiri dulu ya. Aku di sini untuk bantu kamu paham, bukan untuk menggantikan proses belajarmu."

**Validasi output di backend (pertahanan lapis kedua).** Selain deteksi di level model AI, backend melakukan pengecekan tambahan: kalau respons WEBI terdeteksi mengandung teks yang sangat mirip dengan kunci jawaban kuis (berdasarkan cosine similarity atau exact match), respons itu di-flag untuk review sebelum dikirim ke user. Ini layer pertahanan kedua, bukan satu-satunya.

---

## 2. SKEMA PERSONALISASI

### 2.1 Data yang Disimpan dan Sumber Datanya

**a. Data progres user**
Sumber: LMS tracker (read-only oleh WEBI, WEBI tidak menulis ke data ini).

Data yang dibaca:
- Daftar modul dan unit yang sudah selesai (completed_units)
- Unit yang sedang dikerjakan / posisi terakhir (current_unit)
- Level saat ini: angka 1-6 dan nama level (Pengenal / Penyiap / Kolaborator / Perakit / Praktisi / Lulusan Eksplorasi)
- Total poin terkumpul (total_points)
- Daftar checkpoint yang sudah tuntas (completed_checkpoints)
- Timestamp penyelesaian tiap unit (unit_completion_timestamps)
- Jumlah kali user membuka unit tertentu tanpa menyelesaikan evaluasinya (unit_open_count_without_completion), untuk deteksi stuck

**b. Riwayat percakapan**
Sumber: database WEBI sendiri (WEBI yang menulis dan membaca).

- Seluruh percakapan user dengan WEBI, tersimpan per sesi, bisa diakses kembali oleh user lewat antarmuka chat
- User bisa scroll dan membaca kembali percakapan sebelumnya
- WEBI menggunakan riwayat ini secara internal untuk: mendeteksi topik yang sering ditanyakan, menghindari pengulangan jawaban yang persis sama, dan mendeteksi pola stuck

Skema penyimpanan riwayat:
- Setiap pesan (baik dari user maupun WEBI) disimpan sebagai satu record dengan: conversation_id, sender (user/webi), content, timestamp, unit_context (unit mana yang sedang aktif saat pesan dikirim)
- Percakapan dikelompokkan per sesi. Sesi baru dimulai saat user membuka chat WEBI setelah jeda lebih dari 30 menit dari pesan terakhir (threshold configurable)
- Riwayat yang di-inject ke konteks model AI per request dibatasi: hanya N pesan terakhir dari sesi aktif (misal 20 pesan), bukan seluruh riwayat historis, untuk menjaga ukuran konteks tetap efisien

**c. Minat bidang**
Sumber: profil user (read-only oleh WEBI).

- Bidang yang diminati user: Frontend, Backend, UI/UX, Analis, PM, Fullstack
- Diisi saat registrasi atau diperbarui user di profil kapan saja

### 2.2 Bagaimana Data Ini Memengaruhi Respons (Konkret)

**Progres menentukan kedalaman jawaban.**

WEBI menyesuaikan asumsi pengetahuan berdasarkan posisi user di kurikulum. Ini bukan cuma soal menghindari spoiler, tapi soal memastikan penjelasan WEBI bisa dimengerti user pada tahap belajarnya saat itu.

Contoh konkret:
- User di Modul 2 (Level 1, Pengenal) bertanya "bagaimana website bekerja?" -> WEBI menjawab dari nol, tidak mengasumsikan user tahu istilah client-server. Memakai analogi sehari-hari sesuai gaya kurikulum.
- User yang sama bertanya tentang Git -> WEBI memberi penjelasan sangat ringkas (1-2 kalimat) karena user belum sampai Modul 4, dan mengarahkan: "Kamu akan pelajari ini lebih detail di Modul 4 nanti."
- User di Modul 6 (Level 4, Perakit) bertanya tentang HTML -> WEBI bisa merujuk balik ke konsep yang sudah dipelajari ("Ingat waktu kamu belajar soal client-server di Modul 2? Nah, HTML ini yang dikirim server ke browser...") tanpa harus mengulang dari nol.
- User di Modul 9 (Level 5, Praktisi) bertanya soal deployment -> WEBI bisa menjawab dengan kedalaman penuh, merujuk Git, GitHub, HTML/CSS yang semuanya sudah dipelajari.

**Progres menentukan rekomendasi materi lanjutan.**

WEBI bisa menambahkan rekomendasi kontekstual setelah menjawab pertanyaan. Rekomendasi muncul saat ada unit atau sumber belajar yang relevan dan user belum mengaksesnya, bukan di setiap pesan.

Jenis rekomendasi:
- Arahan ke unit selanjutnya: "Btw, unit selanjutnya membahas [topik terkait]. Mau lanjut ke sana?"
- Arahan ke unit di modul lain yang relevan: "Topik ini dibahas lebih lengkap di Unit X.Y. Kamu bisa akses itu nanti saat sampai di Modul X."
- Sumber belajar tambahan dari kurikulum: "Kalau mau eksplorasi lebih lanjut, ada sumber tambahan di akhir Modul X: [nama sumber]."

Frekuensi: tidak di setiap pesan. Maksimal 1 rekomendasi per 3 pesan, supaya tidak terasa formulaik atau mengganggu alur percakapan.

**Riwayat percakapan mencegah repetisi dan mendeteksi pola.**

- Kalau user bertanya topik yang sama di sesi berbeda, WEBI menyesuaikan penjelasan: pakai sudut pandang atau analogi yang berbeda dari sebelumnya.
- Kalau user bertanya topik yang sama lebih dari 3 kali (lintas sesi), ini sinyal stuck. Data ini menjadi trigger untuk sapaan proaktif (lihat Bagian 3, Trigger 2).

**Minat bidang membuat contoh lebih relevan.**

- User minat UI/UX yang belajar HTML -> WEBI menekankan aspek visual dan tata letak, contoh-contohnya ke arah desain halaman.
- User minat Backend yang belajar hal yang sama -> WEBI menekankan aspek struktur data dan bagaimana HTML berinteraksi dengan server.
- Ini pengayaan, bukan pengalihan. Materi inti yang disampaikan tetap sama, yang berbeda hanyalah framing dan contoh tambahan.

**Gamifikasi bisa ditanyakan dan dirujuk.**

WEBI bisa menjawab pertanyaan user soal progres gamifikasi:
- "Aku sudah di level berapa?" -> WEBI menjawab: "Kamu sekarang di Level [N] ([nama level]). Total poinmu [X]."
- "Berapa poin lagi untuk naik level?" -> WEBI menghitung berdasarkan total poin saat ini dan threshold level berikutnya.
- "Apa yang aku pelajari di level berikutnya?" -> WEBI merujuk deskripsi level di sistem gamifikasi.

---

## 3. SKEMA INTERAKSI

### 3.1 Mode A: Chat Reaktif (User yang Mulai)

Mode utama. User membuka antarmuka chat WEBI di dalam LMS dan mengetik atau berbicara. WEBI merespons sesuai lingkup pengetahuan (Bagian 1) dan personalisasi (Bagian 2).

Setiap respons WEBI yang menjawab pertanyaan konsep bisa diakhiri dengan rekomendasi materi lanjutan yang kontekstual (lihat 2.2), tapi tidak wajib di setiap pesan.

Respons WEBI di mode chat reaktif bersifat sinkron: user mengirim pesan, WEBI memproses, WEBI merespons. Tidak ada delay artifisial.

### 3.2 Mode B: Sapaan Proaktif (WEBI yang Mulai)

WEBI mengirim pesan duluan berdasarkan trigger tertentu. Pesan ini muncul di antarmuka chat saat user membuka WEBI-SPACE, bukan sebagai push notification eksternal.

#### Trigger dan Kondisi

**Trigger 1: Pertama kali login**
- Kondisi: User pertama kali membuka WEBI setelah registrasi (belum ada riwayat percakapan).
- Pesan: "Halo! Aku WEBI, teman belajarmu di sini. Kamu bisa tanya aku soal materi kurikulum, cara pakai sistem ini, atau hal apapun seputar web development yang ada di kurikulum. Kalau bingung mulai dari mana, tanya aja."
- Prioritas: tertinggi (selalu muncul, tidak terpengaruh cooldown).

**Trigger 2: Stagnasi progres**
- Kondisi: User tidak menyelesaikan unit baru selama 5 hari (threshold configurable oleh admin).
- Pesan: Ringan, tanpa tekanan. Contoh: "Hei, terakhir kamu di Unit [X.Y]. Kalau ada yang bikin bingung, tanya aja. Kalau belum sempat, santai, lanjut kapan kamu siap."
- Nada ini kritis: dari riset, benturan waktu dengan kesibukan lain dialami 6 dari 12 anggota. Sapaan tidak boleh terasa seperti teguran atau menambah beban.

**Trigger 3: Stuck di satu unit**
- Kondisi: User membuka unit yang sama lebih dari 3 kali tanpa menyelesaikan evaluasinya, ATAU user bertanya topik yang sama di WEBI lebih dari 3 kali lintas sesi.
- Pesan: Menawarkan bantuan spesifik. Contoh: "Kayaknya Unit [X.Y] tentang [topik] agak tricky ya? Mau aku coba jelaskan dari sudut yang berbeda?"

**Trigger 4: Setelah naik level**
- Kondisi: Perubahan level terdeteksi dari LMS tracker (total poin melewati threshold level berikutnya).
- Pesan: Ucapan selamat dan preview. Contoh: "Selamat, kamu sekarang Level [N] ([nama level])! Di level ini kamu akan belajar tentang [deskripsi singkat cakupan level]. Semangat!"
- Prioritas: tinggi (selalu muncul, tidak terpengaruh cooldown stagnasi).

**Trigger 5: Setelah menyelesaikan checkpoint modul**
- Kondisi: User menyelesaikan Intermezo di akhir modul.
- Pesan: Apresiasi dan arahan. Contoh: "Modul [N] selesai! Kamu sekarang sudah paham [ringkasan pencapaian modul]. Modul berikutnya tentang [topik]. Siap lanjut?"
- Prioritas: tinggi (selalu muncul).

#### Pembatasan Frekuensi (Anti-spam)

- Maksimal 1 sapaan proaktif per hari per user.
- Kalau user tidak merespons sapaan proaktif (tidak membalas dalam 24 jam), WEBI tidak mengirim sapaan lagi selama 3 hari (cooldown).
- Kalau user tidak merespons 3 kali berturut-turut, WEBI berhenti mengirim sapaan proaktif tipe nudge (Trigger 2 dan 3) sampai user kembali aktif (menyelesaikan unit baru atau memulai chat sendiri).
- Trigger yang merespons aksi positif user (Trigger 4: naik level, Trigger 5: selesai checkpoint) tetap dikirim terlepas dari cooldown, karena ini apresiasi atas pencapaian, bukan nudge.
- Trigger 1 (pertama login) selalu muncul sekali, terlepas dari kondisi apapun.

#### Penyimpanan Status Sapaan Proaktif

Per user, sistem menyimpan:
- Tanggal sapaan proaktif terakhir dikirim (last_proactive_message_date)
- Jumlah sapaan berturut-turut yang tidak direspons (unanswered_proactive_count)
- Tipe trigger terakhir yang dikirim (last_trigger_type)
- Apakah onboarding greeting (Trigger 1) sudah dikirim (onboarding_sent: boolean)

---

## 4. SUMBER DATASET

### 4.1 Sumber Utama: Blueprint Kurikulum

Blueprint Konten Kurikulum Eksplorasi WEBI-SPACE (Bagian 1: Modul 1-5, Bagian 2: Modul 6-10). Data yang diekstrak:

**Konten materi per unit:** Seluruh penjelasan, contoh, analogi yang ada di setiap unit. Ini jadi basis jawaban WEBI. Direktif penyajian ([SAJIKAN: ...]) distrip karena itu instruksi frontend, bukan konten yang dibaca user atau dipakai WEBI.

**Metadata per unit:** Estimasi waktu, poin, tipe evaluasi, prasyarat. Dipakai WEBI untuk menjawab pertanyaan soal sistem gamifikasi dan untuk menyesuaikan konteks personalisasi.

**Evaluasi per unit (format terstruktur):** Daftar soal kuis (beserta pilihan jawaban dan kunci jawaban), instruksi esai, instruksi praktik. Ini dipakai untuk mekanisme deteksi perlindungan evaluasi (Bagian 1.5). Kunci jawaban hanya dipakai untuk deteksi, tidak pernah di-expose ke user lewat WEBI.

**Sumber belajar tambahan per modul:** Link dan deskripsi sumber eksternal yang tercantum di setiap akhir modul. WEBI bisa mereferensikan ini saat memberikan rekomendasi materi lanjutan.

**Sistem gamifikasi:** Definisi level (nama, modul cakupan, threshold poin), aturan poin per tipe unit, dan mekanisme checkpoint. Dipakai WEBI untuk menjawab pertanyaan soal progres gamifikasi.

### 4.2 Sumber Pendukung

**Pengetahuan bawaan model AI (dibatasi guardrail).** Untuk pertanyaan Tier 2 (konteks ekosistem webdev yang terkait tapi belum dibahas eksplisit di unit tertentu), WEBI mengandalkan pengetahuan umum model AI tentang web development. Guardrail di system prompt membatasi supaya model tidak menjawab di luar domain webdev.

**Informasi sistem WEBI-SPACE.** Dokumen terpisah yang menjelaskan cara navigasi dan cara kerja fitur platform. Ini perlu disusun setelah UI/UX (tahap 1.8) selesai dan ditambahkan ke dataset WEBI sebelum deployment.

### 4.3 Skema Update Dataset

Prinsip: kurikulum adalah single source of truth, dataset WEBI adalah turunannya.

**Kapan update terjadi:** Saat kurikulum direvisi di Fase 3 (iterasi). Dataset WEBI harus diperbarui sebelum versi kurikulum baru dipublikasikan ke anggota.

**Siapa yang trigger:** Admin (PIC). Bukan otomatis, karena revisi kurikulum perlu dicek dampaknya terhadap konsistensi dataset sebelum di-push.

**Proses update:**
1. Kurikulum direvisi (konten unit berubah, unit ditambah/dihapus, evaluasi berubah).
2. Admin mengekspor ulang konten kurikulum ke format dataset WEBI (proses ini bisa di-support oleh tooling yang dibuat di tahap implementasi).
3. Dataset baru divalidasi: cek apakah semua unit yang dirujuk oleh data progres user masih ada di kurikulum baru, cek apakah soal evaluasi yang dipakai untuk deteksi masih sinkron.
4. Kalau ada unit yang dihapus: field current_unit di progres user yang masih merujuk unit tersebut perlu di-handle (redirect ke unit pengganti atau ke awal modul terkait).
5. Dataset baru di-deploy ke WEBI.
6. Versi dataset lama di-archive untuk audit trail.

**Penanganan gap transisi:** Saat ada perbedaan antara konten yang sudah diakses user sebelumnya dan konten baru, WEBI menjawab berdasarkan konten terbaru. Kalau user merujuk konten lama yang sudah berubah, WEBI menjelaskan bahwa materi sudah diperbarui dan memberikan penjelasan versi terbaru.

### 4.4 Format Dataset

Dataset WEBI disusun dalam format terstruktur (bukan teks mentah yang dicampur jadi satu), dengan pembagian:

- `curriculum_content`: konten materi per unit, di-index per module_id dan unit_id
- `evaluation_bank`: soal evaluasi per unit, di-index per unit_id, mencakup tipe evaluasi dan kunci jawaban (untuk deteksi, bukan untuk di-expose)
- `supplementary_resources`: sumber belajar tambahan per modul
- `gamification_rules`: definisi level, threshold poin, aturan poin
- `system_info`: informasi navigasi dan cara kerja fitur WEBI-SPACE (diisi setelah UI/UX selesai)

Format penyimpanan aktual (JSON, database terstruktur, atau vector store) ditentukan di tahap pemilihan tech stack (1.9).

---

## 5. GUARDRAIL TEKNIS

### 5.1 Implementasi di Level System Prompt

Pembatasan lingkup jawaban diimplementasikan di system prompt yang dikirim ke model AI di setiap request. System prompt bersifat statis (bagian yang tetap) plus dinamis (bagian yang di-inject per request berdasarkan konteks user).

#### Bagian Statis System Prompt

Berisi instruksi yang tidak berubah antar user maupun antar request:

**Instruksi persona:**
```
Kamu adalah WEBI, teman belajar di platform WEBI-SPACE. Kamu bukan guru, bukan 
penguji, dan bukan bot customer service. Kamu teman yang sabar dan suportif, yang 
membantu anggota memahami materi web development.

Nada bicaramu:
- Bahasa Indonesia kasual tapi jelas. Tidak terlalu formal, tidak terlalu slang.
- Selalu validasi dulu saat user menunjukkan kebingungan sebelum menjelaskan.
- TIDAK PERNAH membandingkan antaranggota.
- TIDAK PERNAH menggunakan frasa: "harusnya kamu sudah tahu", "ini kan gampang", 
  "masa belum paham", atau variasi yang memiliki nada serupa.
- Mendorong tanpa memaksa. Ajakan, bukan teguran.
```

**Instruksi domain:**
```
Kamu HANYA menjawab pertanyaan dalam tiga domain ini:
1. Konten kurikulum WEBI-SPACE (Modul 1-10) - prioritas tertinggi, jawab berdasarkan 
   konten kurikulum yang disediakan di konteks.
2. Konteks umum ekosistem web development yang terkait dengan cakupan kurikulum.
3. Informasi cara kerja platform WEBI-SPACE.

Untuk pertanyaan di luar ketiga domain ini, tolak dengan ramah:
"Aku cuma bisa bantu soal materi web development dan cara pakai WEBI-SPACE ya. 
Ada hal lain seputar itu yang mau kamu tanyakan?"
```

**Instruksi perlindungan evaluasi:**
```
KRITIS: Kamu TIDAK BOLEH memberikan jawaban langsung untuk soal evaluasi. Daftar 
soal evaluasi per unit disediakan di konteks sebagai [EVALUATION_BANK].

Saat input user terdeteksi merujuk pada soal evaluasi (baik exact match maupun 
parafrase):
- Untuk kuis pilihan/benar-salah/mencocokkan/mengurutkan: JANGAN beri jawaban. 
  Jelaskan konsep yang diuji supaya user bisa menjawab sendiri.
- Untuk esai: JANGAN susunkan paragraf utuh. Bantu user pahami konsep, tapi esai 
  ditulis user sendiri.
- Untuk praktik: BOLEH bantu troubleshoot error. JANGAN beri output yang seharusnya 
  dihasilkan user sendiri.

Saat tidak yakin apakah pertanyaan merujuk pada evaluasi: jawab konsepnya, 
tambahkan pengingat ringkas bahwa evaluasi sebaiknya dikerjakan mandiri.
```

**Instruksi rekomendasi:**
```
Setelah menjawab pertanyaan konsep, kamu BOLEH menambahkan rekomendasi unit 
atau sumber belajar yang relevan dan belum diakses user, tapi TIDAK di setiap pesan. 
Maksimal 1 rekomendasi per 3 pesan. Rekomendasi harus kontekstual, bukan generik.
```

**Instruksi mode suara:**
```
Saat mode suara aktif (ditandai oleh flag [VOICE_MODE=true] di konteks):
- Buat respons lebih ringkas dari mode teks. Maksimal 3-4 kalimat per poin.
- Jangan bacakan blok kode atau perintah terminal. Sebut saja nama perintahnya 
  dan arahkan user untuk melihat teks di chat.
- Semua aturan domain dan perlindungan evaluasi tetap berlaku identik.
```

#### Bagian Dinamis (Di-inject per Request)

Berikut data yang di-inject ke system prompt setiap kali user mengirim pesan:

```
[USER_CONTEXT]
- user_id: {id}
- name: {nama}
- current_level: {level} ({nama_level})
- total_points: {poin}
- current_unit: {unit yang sedang dikerjakan}
- completed_units: [{daftar unit selesai}]
- interest_field: {minat bidang}
- voice_mode: {true/false}

[CONVERSATION_HISTORY]
{N pesan terakhir dari sesi aktif}

[RELEVANT_CURRICULUM_CONTENT]
{Konten unit yang sedang dikerjakan user + konten unit yang relevan dengan 
pertanyaan user, di-retrieve berdasarkan similarity search}

[EVALUATION_BANK]
{Soal evaluasi dari unit yang sedang dikerjakan user + unit yang berdekatan, 
dalam format terstruktur: unit_id, tipe_evaluasi, soal, kunci_jawaban}
```

### 5.2 Validasi Output di Backend (Layer Kedua)

Sebelum respons WEBI dikirim ke user, backend melakukan pengecekan:

**Cek jawaban evaluasi:** Respons WEBI dibandingkan dengan kunci jawaban kuis dari evaluation_bank menggunakan similarity matching. Kalau similarity di atas threshold (misal 0.85), respons di-flag dan tidak dikirim. Gantinya, sistem me-retry request dengan instruksi tambahan di prompt: "Respons sebelumnya terdeteksi mengandung jawaban evaluasi. Ulangi tanpa memberikan jawaban langsung."

**Cek domain:** Kalau respons WEBI terdeteksi membahas topik yang jelas di luar domain (misal: politik, gosip, konten tidak pantas), respons di-block. Ini safety net untuk kasus di mana instruksi system prompt gagal ditegakkan oleh model.

**Rate limiting:** Maksimal jumlah pesan per user per hari untuk mencegah abuse dan mengontrol biaya API. Threshold awal: 50 pesan per hari per user (configurable).

### 5.3 Logging untuk Monitoring Admin

Setiap interaksi WEBI dicatat untuk monitoring:
- Semua pesan (input user dan respons WEBI) tersimpan di database
- Flag perlindungan evaluasi yang ter-trigger dicatat (unit mana, soal mana, berapa kali)
- Flag penolakan domain dicatat
- Flag validasi output backend dicatat

Admin bisa mengakses log ini dari dashboard monitoring untuk:
- Melihat pola pertanyaan umum (untuk perbaikan kurikulum)
- Mendeteksi apakah WEBI sering gagal menjaga guardrail (untuk perbaikan system prompt)
- Melihat anggota mana yang aktif bertanya dan mana yang tidak

---

## 6. INTERAKSI SUARA (STT/TTS)

### 6.1 Prinsip Dasar

Mode suara adalah lapisan konversi di atas dan di bawah pipeline teks yang sama. Bukan jalur terpisah. Semua pembatasan lingkup, deteksi evaluasi, dan personalisasi berlaku identik di mode suara. Tidak ada kelonggaran guardrail karena beda medium.

### 6.2 Alur Teknis

```
[User tekan tombol mikrofon]
    |
    v
[Input suara direkam]
    |
    v
[STT: suara dikonversi ke teks]
    |
    v
[Transkrip ditampilkan ke user untuk verifikasi]
    |
    v
[Teks diproses oleh pipeline WEBI yang sama persis dengan input teks manual]
[termasuk: inject user_context, conversation_history, curriculum_content,]
[evaluation_bank, semua guardrail di system prompt, validasi output backend]
    |
    v
[Respons teks WEBI dihasilkan]
    |
    v
[TTS: respons teks dikonversi ke suara]
    |
    v
[Audio diputar ke user + teks respons tetap ditampilkan di chat sebagai transkrip]
```

### 6.3 Kapan Mode Suara Dipakai

Mode suara bersifat opsional, user yang memilih. Tombol mikrofon tersedia di samping input teks, keduanya selalu bisa dipakai secara bersamaan. User bisa berganti mode kapan saja di tengah percakapan (misal: bertanya lewat suara, lalu mengetik pertanyaan berikutnya).

### 6.4 Penyesuaian Respons di Mode Suara

Saat flag VOICE_MODE=true aktif di konteks:

- Respons lebih ringkas. Mendengarkan penjelasan panjang lewat audio lebih melelahkan dari membaca. WEBI menyingkat respons tanpa mengurangi substansi. Kalau topik kompleks, WEBI memberi ringkasan lisan dan mengarahkan user ke teks di chat untuk detail lengkap.
- Blok kode tidak dibacakan. Perintah terminal, potongan kode, dan output teknis ditampilkan sebagai teks di chat, bukan dibacakan lewat suara. WEBI cukup menyebutkan nama perintah atau konsepnya secara verbal.
- Transkrip STT ditampilkan. User bisa melihat teks hasil konversi suaranya untuk memverifikasi apakah ucapannya dikenali dengan benar. Kalau salah, user bisa mengetik ulang secara manual.

### 6.5 Pemilihan Provider STT/TTS

Provider STT dan TTS belum ditentukan di tahap ini. Keputusan ini masuk ke tahap pemilihan tech stack (1.9). Kriteria pemilihan yang relevan:
- Dukungan bahasa Indonesia yang akurat (termasuk istilah teknis dalam bahasa Inggris yang sering muncul di konteks webdev)
- Latency yang rendah supaya percakapan terasa natural
- Biaya per request yang terjangkau untuk skala ~12 user
- Ketersediaan API yang bisa diintegrasikan ke stack yang dipilih

---

## 7. SKEMA DATA YANG DIBUTUHKAN DARI SISTEM LAIN

Ringkasan konsolidasi seluruh data yang WEBI butuhkan, dikelompokkan berdasarkan sumber, untuk menjadi acuan langsung saat menyusun skema database di tahap 1.7.

### 7.1 Dari Modul Autentikasi (Read-only)

| Data | Tipe | Kegunaan di WEBI |
|------|------|------------------|
| user_id | uuid | Identifikasi user, key untuk semua data personalisasi |
| name | string | Sapaan di percakapan dan sapaan proaktif |
| role | enum | Validasi hak akses (hanya exploration_member) |
| interest_field | enum/array | Personalisasi contoh dan framing jawaban |

### 7.2 Dari Modul LMS Eksplorasi (Read-only)

| Data | Tipe | Kegunaan di WEBI |
|------|------|------------------|
| completed_units | array of unit_id | Menentukan kedalaman jawaban, rekomendasi |
| current_unit | unit_id | Konteks utama personalisasi per request |
| current_level | integer (1-6) | Sapaan proaktif level-up, respons gamifikasi |
| level_name | string | Respons gamifikasi |
| total_points | integer | Respons gamifikasi, kalkulasi poin ke level berikutnya |
| completed_checkpoints | array of checkpoint_id | Sapaan proaktif checkpoint, konteks progres |
| unit_completion_timestamps | map(unit_id -> timestamp) | Deteksi stagnasi, kecepatan belajar |
| unit_open_count_without_completion | map(unit_id -> integer) | Deteksi stuck (Trigger 3 sapaan proaktif) |

### 7.3 Dari Dataset Kurikulum (Read-only, di-load saat startup/update)

| Data | Format | Kegunaan di WEBI |
|------|--------|------------------|
| curriculum_content | per unit, indexed | Basis jawaban utama, di-retrieve per request |
| evaluation_bank | per unit, structured | Deteksi perlindungan evaluasi |
| supplementary_resources | per modul | Rekomendasi materi lanjutan |
| gamification_rules | global | Respons gamifikasi, kalkulasi level |

### 7.4 Data yang Dikelola WEBI Sendiri (Read-write)

| Entitas | Field Utama | Keterangan |
|---------|-------------|------------|
| Conversation | id, user_id, started_at, last_message_at | Satu percakapan per sesi |
| Message | id, conversation_id, sender (user/webi), content, timestamp, unit_context, voice_mode | Setiap pesan dalam percakapan |
| ProactiveLog | id, user_id, trigger_type, sent_at, responded (boolean), responded_at | Tracking sapaan proaktif untuk cooldown |
| GuardrailFlag | id, message_id, flag_type (eval_detection/domain_rejection/output_validation), unit_id, details | Logging guardrail untuk monitoring admin |

---

## CATATAN PENUTUP

Dokumen ini mencakup seluruh deliverable yang diminta untuk tahap 1.5:

1. Lingkup pengetahuan WEBI: domain boleh (3 tier) dan tidak boleh, plus mekanisme perlindungan evaluasi yang dirinci per tipe soal.
2. Skema personalisasi: 3 jenis data (progres, riwayat, minat) dengan penjelasan konkret bagaimana masing-masing memengaruhi respons.
3. Skema interaksi: chat reaktif dan 5 trigger sapaan proaktif dengan mekanisme anti-spam.
4. Sumber dataset: kurikulum sebagai sumber utama, skema update, dan format dataset terstruktur.
5. Guardrail teknis: draft system prompt (statis + dinamis), validasi output backend, dan logging.
6. Interaksi suara: alur teknis STT/TTS, penyesuaian respons, konsistensi guardrail.
7. Skema data: konsolidasi seluruh data yang dibutuhkan dari sistem lain dan data yang dikelola WEBI sendiri.

Dokumen ini siap menjadi input untuk tahap selanjutnya: PRD (1.6) dan arsitektur database (1.7).