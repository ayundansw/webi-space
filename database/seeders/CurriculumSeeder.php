<?php

namespace Database\Seeders;

use App\Models\Checkpoint;
use App\Models\LearningResource;
use App\Models\Module;
use App\Models\Unit;
use App\Models\UnitEvaluation;
use Illuminate\Database\Seeder;

/**
 * Real Eksplorasi curriculum content (task 2.3), transcribed verbatim from
 * docs/kurikulum-eksplorasi.md. This is the production/dev data source —
 * ExplorationSampleSeeder (2.2) remains separate, for automated tests only.
 *
 * Structural notes (confirmed with user before writing this seeder):
 * - Modul 4 has no Checkpoint row of its own. Its own end-of-module checklist
 *   is folded into Modul 5's Checkpoint (checklist_items = Modul 4 items + Modul 5
 *   items), since the document explicitly merges their intermezo into one at the
 *   end of Modul 5. Modul 4 is considered "completed" once all its units are done.
 * - Unit 5.8 has a mixed evaluation type ("Kuis pilihan dan esai singkat"). Stored
 *   as evaluation_type=quiz_multiple_choice (the 2 MC questions are interactive);
 *   the essay question is a third UnitEvaluation row (question_type=essay).
 *   App\Livewire\Eksplorasi\UnitEvaluation renders it as a required textarea
 *   alongside the MC radio questions and excludes it from correctness grading
 *   (no correct_answer to compare against) — fixed same-day as a follow-up to
 *   2.3, not deferred, since the essay field was previously required by
 *   validation but had no way to be filled in, permanently blocking completion.
 * - Modul 6's intermezo describes a real file-upload submission ("Tugas praktik
 *   dengan pengumpulan file"). Stored as descriptive text in intermezo_questions;
 *   no file upload mechanism exists yet (out of scope for this data-only task).
 * - "Kuis benar-salah" units are stored as evaluation_type=quiz_multiple_choice
 *   with 2-option ["Benar", "Salah"] questions (schema has no separate true/false
 *   question_type), consistent with the pattern established in 2.2.
 */
class CurriculumSeeder extends Seeder
{
    /** @var array<string, Unit> */
    private array $units = [];

    public function run(): void
    {
        $this->seedModule1();
        $this->seedModule2();
        $this->seedModule3();
        $this->seedModule4();
        $this->seedModule5();
        $this->seedModule6();
        $this->seedModule7();
        $this->seedModule8();
        $this->seedModule9();
        $this->seedModule10();
    }

    private function seedModule1(): void
    {
        $module = Module::create([
            'order_number' => 1,
            'title' => 'Dunia Software Development',
            'description' => 'Memberi gambaran utuh soal apa itu software development, siapa saja yang terlibat, bagaimana prosesnya, dan peluang karir apa yang terbuka di bidang ini. Setelah modul ini, kamu punya peta besar sebelum masuk ke hal-hal teknis.',
            'level_number' => 1,
        ]);

        $this->units['1.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Apa Itu Software Development?',
            'content' => <<<'TEXT'
Mari mulai dari yang paling dasar. **Software** adalah kumpulan instruksi yang memberitahu komputer harus melakukan apa. Aplikasi di ponselmu, website yang kamu buka, sistem kasir di minimarket, semuanya adalah software. Lawannya adalah **hardware**, yaitu bagian fisik komputer yang bisa kamu sentuh seperti layar, keyboard, dan mesin di dalamnya.

Lalu apa itu **software development**? Software development adalah keseluruhan proses membuat software, dari memikirkan ide, merancang, menulis kode, menguji, sampai merawatnya setelah jadi. Perhatikan kata kuncinya: keseluruhan proses. Menulis kode hanyalah salah satu bagian, bukan seluruhnya.

Di sinilah banyak pemula salah paham. Mari kita luruskan tiga istilah yang sering dianggap sama:

[SAJIKAN: tabel perbandingan — tiga istilah: Coding, Programming, Software Development]

- **Coding** adalah kegiatan menulis kode, yaitu menerjemahkan instruksi ke dalam bahasa yang dimengerti komputer. Ini aktivitas paling teknis dan paling sempit cakupannya.
- **Programming** sedikit lebih luas dari coding. Selain menulis kode, programming mencakup memikirkan logika, memecahkan masalah, dan menyusun alur agar kode bekerja dengan benar.
- **Software development** adalah yang paling luas. Ia mencakup coding dan programming, tapi juga meliputi memahami kebutuhan pengguna, merancang tampilan, menguji, bekerja dalam tim, sampai merilis dan merawat produk.

Analogi sederhananya seperti membangun rumah. Coding itu seperti memasang batu bata. Programming seperti tahu urutan dan cara memasang bata supaya tembok berdiri kokoh. Sedangkan software development adalah keseluruhan proyek membangun rumah, mulai dari mendengar keinginan pemilik rumah, membuat denah, membangun, mengecek kualitas, sampai memastikan rumah nyaman ditinggali.

[SAJIKAN: callout — Poin penting: Jadi kalau nanti kamu mendengar orang berkata "developer itu cuma ngoding", kamu sudah tahu bahwa itu pandangan yang terlalu sempit. Menulis kode hanyalah satu bagian dari pekerjaan yang jauh lebih besar.]

Kenapa disebut "development" (pengembangan) dan bukan sekadar "programming"? Karena software tidak dibuat sekali lalu selesai selamanya. Software terus dikembangkan, diperbaiki, dan ditingkatkan seiring waktu dan kebutuhan. Kata "development" menangkap sifat berkelanjutan ini.
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Manakah yang cakupannya paling luas: coding, programming, atau software development?',
            'options' => ['Coding', 'Programming', 'Software Development'],
            'correct_answer' => 'Software Development',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Menulis kode adalah keseluruhan pekerjaan seorang developer. (Benar/Salah)',
            'options' => ['Benar', 'Salah'],
            'correct_answer' => 'Salah',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Kenapa istilahnya "development" dan bukan sekadar "programming"?',
            'options' => [
                'Karena software terus dikembangkan, diperbaiki, dan ditingkatkan seiring waktu dan kebutuhan',
                'Karena development adalah istilah yang lebih formal',
                'Karena programming sudah tidak dipakai lagi',
            ],
            'correct_answer' => 'Karena software terus dikembangkan, diperbaiki, dan ditingkatkan seiring waktu dan kebutuhan',
            'sort_order' => 3,
        ]);

        $this->units['1.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Peran-Peran dalam Tim Development',
            'content' => <<<'TEXT'
Membuat software yang utuh jarang dikerjakan satu orang. Biasanya ada tim dengan peran berbeda-beda yang saling melengkapi. Memahami peran-peran ini penting supaya kamu tahu posisi mana yang paling menarik untukmu nanti. Berikut peran-peran utama.

[SAJIKAN: kartu — satu kartu per peran, berisi nama peran, satu kalimat inti, dan ikon]

**Frontend Developer.** Bertanggung jawab atas bagian yang dilihat dan disentuh langsung oleh pengguna: tombol, teks, warna, tata letak, dan animasi di layar. Kalau kamu membuka sebuah website dan mengklik tombol, semua yang tampak di depan matamu itu hasil kerja frontend developer.

**Backend Developer.** Bertanggung jawab atas bagian yang bekerja di belakang layar dan tidak terlihat pengguna: mengolah data, menyimpan informasi, memeriksa apakah password benar, dan menjalankan logika di balik setiap tindakan. Saat kamu login dan sistem mengecek apakah passwordmu cocok, itu pekerjaan backend.

**UI/UX Designer.** UI (User Interface) berurusan dengan tampilan, sedangkan UX (User Experience) berurusan dengan pengalaman dan kenyamanan pengguna. Designer merancang bagaimana software terlihat dan terasa saat dipakai, supaya enak dilihat sekaligus mudah digunakan. Mereka biasanya bekerja sebelum developer mulai menulis kode.

**Project Manager (PM).** Bertanggung jawab mengatur jalannya proyek: membagi tugas, mengatur jadwal, memastikan tim berkomunikasi dengan baik, dan proyek selesai tepat waktu. PM adalah jembatan antara tim teknis dan kebutuhan proyek secara keseluruhan.

**Analis (Business/System Analyst).** Bertanggung jawab memahami kebutuhan sebenarnya dari pengguna atau klien, lalu menerjemahkannya menjadi hal yang bisa dikerjakan tim teknis. Analis banyak berpikir soal "apa yang sebenarnya dibutuhkan" sebelum tim mulai membangun.

[SAJIKAN: callout — Catatan: Peran-peran ini tidak selalu terpisah kaku. Di tim kecil, satu orang bisa memegang beberapa peran sekaligus. Yang penting kamu paham dulu fungsi masing-masing.]

Kelima peran ini bekerja sama dalam satu alur. Analis memahami kebutuhan, designer merancang tampilan, PM mengatur jalannya kerja, lalu frontend dan backend developer membangunnya menjadi software nyata.
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_matching',
            'prerequisite_unit_id' => $this->units['1.1']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.2']->id,
            'question_type' => 'matching',
            'question_text' => 'Cocokkan lima peran (Frontend Developer, Backend Developer, UI/UX Designer, Project Manager, Analis) dengan deskripsi tugasnya masing-masing.',
            'options' => [
                'pairs' => [
                    ['left' => 'Frontend Developer', 'right' => 'Bertanggung jawab atas bagian yang dilihat dan disentuh langsung oleh pengguna'],
                    ['left' => 'Backend Developer', 'right' => 'Bertanggung jawab atas bagian yang bekerja di belakang layar dan tidak terlihat pengguna'],
                    ['left' => 'UI/UX Designer', 'right' => 'Merancang bagaimana software terlihat dan terasa saat dipakai'],
                    ['left' => 'Project Manager (PM)', 'right' => 'Mengatur jalannya proyek: membagi tugas, mengatur jadwal, memastikan tim berkomunikasi dengan baik'],
                    ['left' => 'Analis', 'right' => 'Memahami kebutuhan sebenarnya dari pengguna atau klien, lalu menerjemahkannya menjadi hal yang bisa dikerjakan tim teknis'],
                ],
            ],
            'correct_answer' => [
                'Frontend Developer' => 'Bertanggung jawab atas bagian yang dilihat dan disentuh langsung oleh pengguna',
                'Backend Developer' => 'Bertanggung jawab atas bagian yang bekerja di belakang layar dan tidak terlihat pengguna',
                'UI/UX Designer' => 'Merancang bagaimana software terlihat dan terasa saat dipakai',
                'Project Manager (PM)' => 'Mengatur jalannya proyek: membagi tugas, mengatur jadwal, memastikan tim berkomunikasi dengan baik',
                'Analis' => 'Memahami kebutuhan sebenarnya dari pengguna atau klien, lalu menerjemahkannya menjadi hal yang bisa dikerjakan tim teknis',
            ],
            'sort_order' => 1,
        ]);

        $this->units['1.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Siklus Hidup Pengembangan Software (SDLC)',
            'content' => <<<'TEXT'
Software yang baik tidak dibuat asal jadi. Ada urutan tahapan yang biasa diikuti dari awal ide sampai software dirawat setelah dipakai. Urutan tahapan ini disebut **SDLC** (Software Development Life Cycle), atau dalam bahasa Indonesia Siklus Hidup Pengembangan Software.

Disebut "siklus hidup" karena tahapan ini menggambarkan perjalanan hidup sebuah software, dari lahir sampai terus dirawat. Mari kita lihat tahap demi tahap.

[SAJIKAN: diagram alur — tujuh tahap SDLC berurutan dengan panah: Perencanaan, Analisis Kebutuhan, Desain, Development, Testing, Deployment, Maintenance. Beri panah melingkar kembali dari Maintenance untuk menunjukkan sifat siklus]

**1. Perencanaan.** Menentukan software apa yang akan dibuat, untuk siapa, dan kenapa. Di tahap ini tim memikirkan tujuan besar dan apakah proyeknya masuk akal untuk dikerjakan.

**2. Analisis Kebutuhan.** Menggali secara detail apa saja yang dibutuhkan software ini. Fitur apa yang harus ada, masalah apa yang ingin diselesaikan. Ini pekerjaan yang banyak melibatkan analis.

**3. Desain.** Merancang bagaimana software akan terlihat dan bekerja. Termasuk merancang tampilan (UI/UX) dan merancang struktur teknis di baliknya.

**4. Development.** Tahap menulis kode. Di sinilah frontend dan backend developer membangun software berdasarkan desain yang sudah dibuat.

**5. Testing (Pengujian).** Memeriksa apakah software bekerja dengan benar dan bebas dari kesalahan (bug). Software diuji sebelum sampai ke tangan pengguna.

**6. Deployment (Perilisan).** Software yang sudah jadi dan lolos uji dirilis supaya bisa diakses dan dipakai pengguna nyata.

**7. Maintenance (Pemeliharaan).** Setelah dipakai, software terus dirawat: memperbaiki masalah yang muncul, menambah fitur baru, dan memastikan tetap berjalan lancar.

[SAJIKAN: callout — Catatan: Urutan di atas adalah gambaran umum. Di praktik nyata, tim tidak selalu menjalankannya lurus dari 1 sampai 7. Ada berbagai metode yang mengatur cara menjalani tahapan ini, dan itu akan kamu pelajari lebih dalam di Modul 8.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_ordering',
            'prerequisite_unit_id' => $this->units['1.2']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.3']->id,
            'question_type' => 'ordering',
            'question_text' => 'Susun tujuh tahap SDLC dalam urutan yang benar.',
            'options' => ['Perencanaan', 'Analisis Kebutuhan', 'Desain', 'Development', 'Testing', 'Deployment', 'Maintenance'],
            'correct_answer' => ['Perencanaan', 'Analisis Kebutuhan', 'Desain', 'Development', 'Testing', 'Deployment', 'Maintenance'],
            'sort_order' => 1,
        ]);

        $this->units['1.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Jenis-Jenis Produk Software',
            'content' => <<<'TEXT'
Saat mendengar kata "software", banyak orang langsung membayangkan website. Padahal software punya banyak bentuk. Memahami ragam ini membantumu melihat gambaran besar dunia yang akan kamu masuki.

[SAJIKAN: kartu — satu kartu per jenis produk, dengan contoh nyata yang familiar]

**Website (Aplikasi Web).** Software yang diakses lewat browser seperti Chrome atau Firefox, tanpa perlu diinstal. Contohnya Google, Tokopedia, dan WEBI-SPACE yang sedang kamu pakai ini. Inilah jenis yang akan menjadi fokus utama kurikulum ini.

**Mobile App (Aplikasi Seluler).** Software yang diinstal dan dijalankan di ponsel, baik Android maupun iOS. Contohnya WhatsApp, Instagram, dan Gojek.

**Desktop App (Aplikasi Desktop).** Software yang diinstal dan dijalankan di komputer atau laptop. Contohnya Microsoft Word, aplikasi edit foto, dan VS Code yang nanti akan kamu pakai.

**API / Service.** Ini jenis yang agak berbeda karena tidak punya tampilan untuk pengguna biasa. API adalah software yang bertugas melayani software lain, menyediakan data atau fungsi tertentu di belakang layar. Misalnya, saat sebuah aplikasi menampilkan ramalan cuaca, data cuacanya sering diambil dari API milik penyedia layanan cuaca. Konsep API akan kamu pelajari lebih lanjut di Modul 7.

[SAJIKAN: callout — Poin penting: Satu produk bisa punya beberapa jenis sekaligus. Contohnya Gojek: ada mobile app yang kamu pakai, website untuk mitra, dan banyak API yang bekerja di belakang layar menghubungkan semuanya.]

Sepanjang kurikulum ini, fokus kita adalah website. Tapi banyak konsep dasar yang kamu pelajari, seperti cara berpikir developer, version control, dan kerja tim, berlaku untuk semua jenis software di atas.
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['1.3']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.4']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'WhatsApp di ponselmu termasuk jenis produk software apa?',
            'options' => ['Website', 'Mobile App', 'Desktop App', 'API'],
            'correct_answer' => 'Mobile App',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.4']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Microsoft Word termasuk jenis produk software apa?',
            'options' => ['Website', 'Mobile App', 'Desktop App', 'API'],
            'correct_answer' => 'Desktop App',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.4']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Software yang bertugas melayani software lain, menyediakan data atau fungsi di belakang layar, disebut apa?',
            'options' => ['Website', 'Mobile App', 'API / Service'],
            'correct_answer' => 'API / Service',
            'sort_order' => 3,
        ]);

        $this->units['1.5'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 5,
            'title' => 'Peluang Karir di Software Development: Skala Indonesia',
            'content' => <<<'TEXT'
Sekarang bagian yang mungkin paling kamu tunggu: apa peluang kerjanya? Bidang software development termasuk salah satu bidang dengan kebutuhan tenaga kerja yang terus tumbuh di Indonesia. Mari lihat gambaran konkretnya.

**Di mana biasanya developer bekerja di Indonesia?**

[SAJIKAN: kartu — satu kartu per tempat kerja, dengan karakteristik singkat]

**Startup.** Perusahaan rintisan berbasis teknologi. Contohnya perusahaan seperti Gojek, Tokopedia, dan banyak startup lebih kecil. Ciri khasnya adalah lingkungan yang cepat berubah, tim yang relatif ramping, dan kesempatan belajar banyak hal sekaligus.

**Software House.** Perusahaan yang pekerjaannya membuat software untuk klien lain. Kalau bekerja di sini, kamu akan mengerjakan berbagai proyek dari berbagai klien. Cocok untuk yang ingin cepat terpapar banyak jenis proyek.

**Perusahaan Non-Teknologi (Corporate).** Bank, perusahaan asuransi, retail besar, dan instansi pemerintah juga butuh developer untuk membangun dan merawat sistem internal mereka. Lingkungan kerjanya biasanya lebih stabil dan terstruktur.

**Freelance.** Bekerja mandiri, menerima proyek dari klien secara lepas. Cocok untuk yang ingin fleksibilitas waktu, meskipun butuh kemandirian dan kemampuan mengatur diri yang kuat.

**Posisi apa yang umum dicari?** Posisi-posisi yang paling sering muncul di lowongan kerja Indonesia sejalan dengan peran yang sudah kamu pelajari di Unit 1.2: Frontend Developer, Backend Developer, Fullstack Developer (menguasai keduanya), Mobile Developer, UI/UX Designer, dan Quality Assurance (penguji software).

[SAJIKAN: callout — Tips: Sebagai gambaran, posisi entry-level atau junior developer biasanya ditujukan untuk yang baru mulai berkarir. Jadi kamu tidak perlu langsung ahli untuk masuk ke industri ini. Yang penting punya fondasi yang kuat dan portofolio yang bisa ditunjukkan, yang justru menjadi tujuan akhir kurikulum ini.]

Kabar baiknya, banyak posisi ini bisa dimasuki dari jalur belajar mandiri dan portofolio, tidak selalu menuntut latar belakang formal tertentu. Inilah kenapa membangun portofolio nyata (tujuan akhir kurikulum ini) sangat berharga.
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'essay',
            'prerequisite_unit_id' => $this->units['1.2']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.5']->id,
            'question_type' => 'essay',
            'question_text' => 'Dari empat tempat kerja yang dijelaskan (startup, software house, corporate, freelance), mana yang paling menarik untukmu saat ini dan kenapa?',
            'sort_order' => 1,
        ]);

        $this->units['1.6'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 6,
            'title' => 'Peluang Karir di Software Development: Skala Global',
            'content' => <<<'TEXT'
Salah satu hal istimewa dari bidang software adalah pekerjaannya tidak terbatas oleh lokasi. Selama ada komputer dan koneksi internet, seorang developer di Indonesia bisa bekerja untuk perusahaan di negara mana saja. Mari lihat peluang di skala global.

**Kerja Remote Internasional.** Banyak perusahaan luar negeri membuka lowongan remote, artinya kamu bisa bekerja dari rumah di Indonesia untuk perusahaan di Amerika, Eropa, atau Singapura. Salah satu daya tariknya adalah standar gaji yang biasanya lebih tinggi dibanding rata-rata lokal.

**Perusahaan Teknologi Global.** Perusahaan besar seperti Google, Microsoft, dan banyak lainnya punya kantor atau merekrut talenta dari berbagai negara termasuk Indonesia. Sebagian membuka jalur bagi developer berbakat untuk bergabung.

**Freelance Marketplace Internasional.** Platform seperti Upwork dan Fiverr mempertemukan freelancer dengan klien dari seluruh dunia. Kamu bisa menawarkan jasa dan menerima proyek dari klien mancanegara.

[SAJIKAN: infografis — tiga jalur global: Kerja Remote, Perusahaan Global, Freelance Internasional, dengan ikon dan satu kalimat inti tiap jalur]

**Bagaimana developer Indonesia bisa masuk ke pasar internasional?** Kuncinya ada beberapa hal:

[SAJIKAN: kartu — daftar bekal untuk masuk pasar global]

- **Kemampuan teknis yang solid.** Fondasi yang kamu bangun di kurikulum ini adalah awalnya.
- **Portofolio yang bisa diakses publik.** Website portofolio dan akun GitHub yang berisi karyamu menjadi bukti kemampuan yang bisa dilihat siapa saja, di mana saja.
- **Kemampuan berbahasa Inggris.** Sebagian besar dokumentasi teknis, komunikasi tim internasional, dan lowongan global menggunakan bahasa Inggris. Ini bekal yang sangat berharga untuk dikuasai secara bertahap.

[SAJIKAN: callout — Catatan penyemangat: Jarak antara "pemula total" dan "developer yang bekerja untuk klien global" memang terlihat jauh sekarang. Tapi jalannya jelas dan bisa ditempuh langkah demi langkah. Portofolio yang akan kamu buat di akhir kurikulum ini adalah batu pijakan pertama menuju ke sana.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'essay',
            'prerequisite_unit_id' => $this->units['1.5']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.6']->id,
            'question_type' => 'essay',
            'question_text' => 'Dari tiga jalur global yang dijelaskan, mana yang menurutmu paling ingin kamu coba suatu hari nanti? Apa satu hal yang menurutmu perlu kamu siapkan dari sekarang?',
            'sort_order' => 1,
        ]);

        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Aku memahami perbedaan antara coding, programming, dan software development',
                'Aku bisa menyebutkan minimal 5 peran dalam tim development dan tugasnya',
                'Aku memahami alur umum siklus pengembangan software (SDLC)',
                'Aku mengetahui jenis-jenis produk software (web, mobile, desktop, API)',
                'Aku punya gambaran konkret peluang karir di bidang ini, baik skala Indonesia maupun global',
            ],
            'intermezo_questions' => [
                'Dari semua peran dalam tim development yang sudah kamu pelajari (Frontend, Backend, UI/UX, PM, Analis), peran mana yang paling menarik menurutmu dan kenapa?',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'roadmap.sh',
            'url' => 'https://roadmap.sh',
            'source_name' => 'Peta jalur belajar berbagai peran developer (frontend, backend, fullstack, dan lainnya) yang disusun komunitas developer. Sangat cocok untuk melihat gambaran besar skill apa saja yang dibutuhkan tiap peran yang sudah kamu pelajari di modul ini.',
        ]);
    }

    private function seedModule2(): void
    {
        $module = Module::create([
            'order_number' => 2,
            'title' => 'Bagaimana Website Bekerja',
            'description' => 'Memahami mekanisme dasar di balik layar setiap kali seseorang membuka website. Bukan untuk menghafal istilah, tapi supaya kamu punya gambaran (mental model) yang benar sebelum mulai menulis kode.',
            'level_number' => 1,
        ]);

        $this->units['2.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Apa yang Terjadi Saat Kamu Buka Website?',
            'content' => <<<'TEXT'
Setiap hari kamu membuka website tanpa memikirkan apa yang terjadi di baliknya. Padahal dalam hitungan detik, ada rangkaian proses yang berjalan. Mari kita bongkar pelan-pelan.

Bayangkan kamu mengetik sebuah alamat website di browser lalu menekan Enter. Berikut yang terjadi:

[SAJIKAN: diagram alur — lima langkah berurutan dengan panah: 1. Kamu ketik URL, 2. Browser kirim permintaan (request), 3. Server memproses, 4. Server kirim balasan (response), 5. Browser menampilkan halaman]

**1. Kamu mengetik URL.** Kamu memasukkan alamat website, misalnya alamat sebuah toko online, ke kolom alamat browser.

**2. Browser mengirim permintaan (request).** Browser di perangkatmu mengirim pesan ke komputer tempat website itu disimpan, isinya kurang lebih: "Tolong kirimkan halaman ini."

**3. Server memproses permintaan.** Komputer tempat website disimpan (disebut server) menerima permintaanmu, mencari halaman yang diminta, dan menyiapkannya.

**4. Server mengirim balasan (response).** Server mengirim kembali isi halaman yang diminta ke browsermu, berupa file-file yang menyusun website itu.

**5. Browser menampilkan halaman.** Browser menerima file-file itu, lalu merakit dan menampilkannya di layar sebagai halaman yang bisa kamu lihat dan gunakan.

Seluruh proses ini biasanya berlangsung dalam waktu kurang dari satu detik. Pola bolak-balik ini, yaitu kamu meminta lalu server membalas, disebut model **request-response** (permintaan dan balasan). Ini adalah pola paling mendasar tentang cara website bekerja.

[SAJIKAN: callout — Poin penting: Ingat dua kata ini baik-baik: request (permintaan) dan response (balasan). Hampir semua yang terjadi di web adalah variasi dari pola sederhana ini. Kamu akan menemuinya lagi berkali-kali sepanjang kurikulum.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_ordering',
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.1']->id,
            'question_type' => 'ordering',
            'question_text' => 'Susun lima langkah proses membuka website dalam urutan yang benar.',
            'options' => ['Kamu ketik URL', 'Browser kirim permintaan (request)', 'Server memproses', 'Server kirim balasan (response)', 'Browser menampilkan halaman'],
            'correct_answer' => ['Kamu ketik URL', 'Browser kirim permintaan (request)', 'Server memproses', 'Server kirim balasan (response)', 'Browser menampilkan halaman'],
            'sort_order' => 1,
        ]);

        $this->units['2.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Client dan Server',
            'content' => <<<'TEXT'
Di unit sebelumnya muncul dua pihak: browser di perangkatmu, dan komputer tempat website disimpan. Dua pihak ini punya nama resmi dalam dunia web: **client** dan **server**. Memahami keduanya adalah kunci memahami hampir semua hal tentang web.

**Client** adalah pihak yang meminta. Dalam konteks web, client biasanya adalah browser di perangkatmu (laptop atau ponsel). Client yang memulai percakapan dengan mengirim permintaan.

**Server** adalah pihak yang melayani. Server adalah komputer (biasanya komputer khusus yang menyala terus-menerus) yang menyimpan website dan siap mengirimkannya kapan pun ada yang meminta.

[SAJIKAN: diagram alur — dua kotak berhadapan: "CLIENT (Browser-mu)" di kiri dan "SERVER (Komputer penyimpan website)" di kanan. Panah dari client ke server bertuliskan "Request (minta)", panah balik dari server ke client bertuliskan "Response (balas)"]

**Kenapa keduanya perlu dibedakan?** Karena mereka punya tugas yang berbeda dan berada di tempat yang berbeda. Client ada di tanganmu, server ada di suatu tempat lain (bisa jadi di negara lain). Pembagian tugas ini memungkinkan satu server melayani ribuan bahkan jutaan client sekaligus.

Analogi yang mudah: bayangkan restoran. Kamu sebagai pelanggan adalah client. Kamu memesan makanan (request). Dapur restoran adalah server, ia menyiapkan pesananmu lalu mengantarkannya kembali ke mejamu (response). Satu dapur bisa melayani banyak pelanggan sekaligus, sama seperti satu server melayani banyak client.

[SAJIKAN: callout — Catatan: Kata "server" merujuk pada peran, bukan bentuk fisik tertentu. Server bisa berupa satu komputer, atau kumpulan banyak komputer yang bekerja bersama. Yang membuatnya disebut server adalah tugasnya: melayani permintaan.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['2.1']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Browser di perangkatmu berperan sebagai apa?',
            'options' => ['Client', 'Server'],
            'correct_answer' => 'Client',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Komputer yang menyimpan website dan mengirimkannya saat diminta berperan sebagai apa?',
            'options' => ['Client', 'Server'],
            'correct_answer' => 'Server',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Dalam analogi restoran, dapur restoran berperan sebagai apa?',
            'options' => ['Client', 'Server'],
            'correct_answer' => 'Server',
            'sort_order' => 3,
        ]);

        $this->units['2.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Domain, Hosting, dan URL',
            'content' => <<<'TEXT'
Sekarang kamu tahu ada client dan server. Tapi bagaimana browsermu tahu harus meminta ke server yang mana di antara jutaan server di dunia? Di sinilah domain, hosting, dan URL berperan.

**Domain (Nama Domain).** Domain adalah alamat website yang mudah diingat, misalnya `google.com` atau `tokopedia.com`. Sebenarnya setiap server punya alamat berupa deretan angka yang disebut IP address (misalnya `142.250.4.100`), tapi angka seperti itu sulit diingat manusia. Domain hadir sebagai nama yang mudah diingat, yang di belakang layar diterjemahkan menjadi alamat angka tersebut.

**Hosting.** Hosting adalah layanan penyimpanan file website di sebuah server yang menyala terus-menerus dan terhubung ke internet. Ingat, website terdiri dari file-file. File-file itu harus disimpan di suatu tempat yang bisa diakses kapan saja. Tempat itulah hosting. Tanpa hosting, website-mu hanya ada di komputermu sendiri dan tidak bisa diakses orang lain.

**URL.** URL (Uniform Resource Locator) adalah alamat lengkap menuju halaman atau file tertentu di sebuah website. Domain adalah bagian dari URL, tapi URL bisa lebih spesifik menunjuk ke halaman tertentu.

[SAJIKAN: infografis — anatomi sebuah URL yang dipecah bagian per bagian, contoh: https://www.tokopedia.com/promo dengan label pada tiap bagian: "https:// (protokol)", "www.tokopedia.com (domain)", "/promo (halaman spesifik)"]

**Bagaimana ketiganya berhubungan?** Analogi sederhananya seperti alamat rumah:

[SAJIKAN: tabel perbandingan — analogi: Domain = nama jalan yang mudah diingat, Hosting = tanah dan bangunan tempat rumah berdiri, URL = alamat lengkap sampai nomor rumah dan ruangan tertentu]

Jadi ketika kamu mengetik URL di browser, browser menggunakan domain untuk menemukan server tempat website di-hosting, lalu meminta halaman spesifik yang ditunjuk URL tersebut.

[SAJIKAN: callout — Poin penting: Kamu akan mengurus domain dan hosting secara nyata di Modul 9 saat merilis website-mu ke internet. Untuk sekarang, cukup pahami konsepnya dulu.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_matching',
            'prerequisite_unit_id' => $this->units['2.2']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.3']->id,
            'question_type' => 'matching',
            'question_text' => 'Cocokkan tiga istilah (Domain, Hosting, URL) dengan penjelasan dan analoginya.',
            'options' => [
                'pairs' => [
                    ['left' => 'Domain', 'right' => 'Alamat website yang mudah diingat, seperti nama jalan'],
                    ['left' => 'Hosting', 'right' => 'Layanan penyimpanan file website di server, seperti tanah dan bangunan tempat rumah berdiri'],
                    ['left' => 'URL', 'right' => 'Alamat lengkap menuju halaman tertentu, seperti alamat lengkap sampai nomor rumah'],
                ],
            ],
            'correct_answer' => [
                'Domain' => 'Alamat website yang mudah diingat, seperti nama jalan',
                'Hosting' => 'Layanan penyimpanan file website di server, seperti tanah dan bangunan tempat rumah berdiri',
                'URL' => 'Alamat lengkap menuju halaman tertentu, seperti alamat lengkap sampai nomor rumah',
            ],
            'sort_order' => 1,
        ]);

        $this->units['2.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Frontend, Backend, dan Fullstack: Garis Besarnya',
            'content' => <<<'TEXT'
Di Modul 1 kamu sudah berkenalan sekilas dengan frontend dan backend lewat peran developernya. Sekarang kita perdalam dari sisi bagian websitenya, bukan orangnya. Ada tiga istilah yang perlu kamu pahami betul: frontend, backend, dan fullstack.

**Frontend (Bagian Depan).** Frontend adalah bagian website yang dilihat dan disentuh langsung oleh pengguna. Semua yang tampil di layar, mulai dari teks, gambar, tombol, warna, sampai tata letak, adalah frontend. Frontend berjalan di sisi client, yaitu di browser pengguna. Ketika kamu mengklik tombol atau mengisi formulir, kamu sedang berinteraksi dengan frontend.

**Backend (Bagian Belakang).** Backend adalah bagian website yang bekerja di belakang layar dan tidak terlihat pengguna. Backend berjalan di sisi server. Tugasnya mengolah data, menyimpan informasi, memeriksa keamanan, dan menjalankan logika. Misalnya, saat kamu login, backend-lah yang memeriksa apakah email dan password-mu cocok dengan data yang tersimpan.

**Fullstack (Depan dan Belakang).** Fullstack merujuk pada kemampuan atau bagian yang mencakup keduanya, frontend dan backend sekaligus. Seorang fullstack developer adalah developer yang mampu mengerjakan baik sisi tampilan maupun sisi belakang layar. Istilah "stack" merujuk pada keseluruhan tumpukan teknologi yang membangun sebuah aplikasi, dan "full" berarti menguasai seluruhnya.

[SAJIKAN: diagram alur — website digambarkan sebagai dua lapis: lapis atas "FRONTEND (yang dilihat pengguna, jalan di browser/client)" dan lapis bawah "BACKEND (bekerja di belakang layar, jalan di server)". Tambahkan label "FULLSTACK = menguasai kedua lapis ini" yang mencakup keduanya. Sertakan panah request-response yang menghubungkan frontend ke backend]

**Bagaimana ketiganya berhubungan dalam satu proyek?** Frontend dan backend saling berkomunikasi lewat pola request-response yang sudah kamu pelajari. Frontend meminta data ke backend (misalnya "berikan daftar produk"), backend memprosesnya dan mengirim balik data itu, lalu frontend menampilkannya dengan rapi ke pengguna. Fullstack adalah orang atau pendekatan yang menjembatani keduanya.

[SAJIKAN: callout — Analogi: Frontend seperti ruang makan restoran yang dilihat pelanggan: meja, dekorasi, menu. Backend seperti dapur yang tidak terlihat pelanggan tapi menyiapkan semua makanan. Fullstack seperti orang yang paham cara kerja ruang makan sekaligus dapur.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['2.2']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.4']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Memeriksa apakah password benar termasuk bagian mana?',
            'options' => ['Frontend', 'Backend', 'Fullstack'],
            'correct_answer' => 'Backend',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.4']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Warna, tombol, dan tata letak yang kamu lihat di layar termasuk bagian mana?',
            'options' => ['Frontend', 'Backend', 'Fullstack'],
            'correct_answer' => 'Frontend',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.4']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Developer yang mampu mengerjakan baik sisi tampilan maupun sisi belakang layar disebut apa?',
            'options' => ['Frontend Developer', 'Backend Developer', 'Fullstack Developer'],
            'correct_answer' => 'Fullstack Developer',
            'sort_order' => 3,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.4']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Frontend berjalan di sisi mana?',
            'options' => ['Client (browser pengguna)', 'Server'],
            'correct_answer' => 'Client (browser pengguna)',
            'sort_order' => 4,
        ]);

        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Aku bisa menjelaskan alur sederhana apa yang terjadi saat membuka website',
                'Aku memahami perbedaan client dan server',
                'Aku memahami apa itu domain dan hosting',
                'Aku bisa membedakan frontend, backend, dan fullstack',
            ],
            'intermezo_questions' => [
                'Kuis singkat (mencocokkan): Cocokkan tujuh istilah berikut dengan penjelasannya masing-masing: client, server, domain, hosting, frontend, backend, fullstack.',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'MDN Web Docs (Mozilla)',
            'url' => 'https://developer.mozilla.org/en-US/docs/Learn_web_development/Getting_started/Web_standards/How_the_web_works',
            'source_name' => 'Dokumentasi resmi dari Mozilla yang menjelaskan cara kerja web, termasuk client, server, dan cara file website dikirim. Ini rujukan tepercaya yang dipakai developer di seluruh dunia.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'MDN Web Docs: What is a web server?',
            'url' => 'https://developer.mozilla.org/en-US/docs/Learn_web_development/Howto/Web_mechanics/What_is_a_web_server',
            'source_name' => 'Penjelasan khusus soal apa itu web server dan bagaimana ia melayani permintaan, memperdalam konsep client-server di unit ini.',
        ]);
    }

    private function seedModule3(): void
    {
        $module = Module::create([
            'order_number' => 3,
            'title' => 'Peralatan dan Ekosistem Kerja Developer',
            'description' => "Memahami kerangka besar tools, bahasa pemrograman, dan framework yang dipakai developer, lalu menyiapkan peralatan kerja dasar. Setelah modul ini, kamu punya peta utuh soal \"pakai apa untuk apa\" dan sudah siap secara tools.",
            'level_number' => 2,
        ]);

        $this->units['3.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Kategori Tools Developer: Gambaran Besar',
            'content' => <<<'TEXT'
Sebelum menyentuh tools spesifik, penting untuk memahami dulu kategori besarnya. Developer memakai banyak alat, tapi sebagian besar masuk ke dalam beberapa kategori utama. Kalau kamu paham kategorinya lebih dulu, kamu tidak akan bingung saat bertemu nama-nama tools baru nanti.

[SAJIKAN: kartu — tiga kategori tools, satu kartu masing-masing]

**IDE (Integrated Development Environment).** IDE adalah aplikasi tempat developer menulis kode, lengkap dengan berbagai fitur bantuan dalam satu paket terpadu. Kata "integrated" (terpadu) menunjukkan bahwa IDE menyatukan banyak alat sekaligus: tempat menulis kode, alat menjalankan kode, alat mencari kesalahan, dan lainnya. Bayangkan IDE seperti dapur lengkap yang sudah berisi kompor, pisau, talenan, dan wastafel dalam satu ruangan.

**CLI (Command Line Interface).** CLI adalah cara berinteraksi dengan komputer lewat mengetik perintah teks, bukan mengklik ikon dan tombol. Lawannya adalah GUI (Graphical User Interface), yaitu tampilan grafis yang biasa kamu pakai sehari-hari dengan klik dan sentuh. Lewat CLI, developer bisa memberi perintah ke komputer secara cepat dan tepat hanya dengan mengetik.

**Browser Developer Tools (DevTools).** DevTools adalah seperangkat alat yang sudah tertanam di dalam browser (seperti Chrome atau Firefox) yang membantu developer memeriksa dan memahami cara sebuah website bekerja. Dengan DevTools, kamu bisa mengintip struktur halaman, melihat pesan kesalahan, dan banyak lagi.

[SAJIKAN: tabel perbandingan — tiga kategori: IDE (tempat menulis kode), CLI (memberi perintah lewat teks), DevTools (memeriksa website di browser), masing-masing dengan kolom "Fungsi utama" dan "Kapan dipakai"]

[SAJIKAN: callout — Catatan: Jangan khawatir kalau istilah-istilah ini terasa asing. Di unit-unit berikutnya kamu akan berkenalan langsung dengan contoh nyata tiap kategori: VS Code sebagai contoh IDE, terminal sebagai contoh CLI, dan DevTools di browsermu.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_matching',
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.1']->id,
            'question_type' => 'matching',
            'question_text' => 'Cocokkan tiga kategori tools (IDE, CLI, DevTools) dengan fungsi dan contohnya.',
            'options' => [
                'pairs' => [
                    ['left' => 'IDE', 'right' => 'Aplikasi tempat menulis kode, contoh: VS Code'],
                    ['left' => 'CLI', 'right' => 'Cara memberi perintah lewat teks, contoh: terminal'],
                    ['left' => 'DevTools', 'right' => 'Alat memeriksa website dari dalam browser'],
                ],
            ],
            'correct_answer' => [
                'IDE' => 'Aplikasi tempat menulis kode, contoh: VS Code',
                'CLI' => 'Cara memberi perintah lewat teks, contoh: terminal',
                'DevTools' => 'Alat memeriksa website dari dalam browser',
            ],
            'sort_order' => 1,
        ]);

        $this->units['3.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Code Editor dan IDE: Mengenal dan Menyiapkan VS Code',
            'content' => <<<'TEXT'
Sekarang kita berkenalan dengan alat kerja utama developer: tempat menulis kode. Tapi ada satu perbedaan kecil yang perlu diluruskan lebih dulu.

**Code Editor vs IDE.** Code editor adalah aplikasi sederhana untuk menulis dan mengedit kode. IDE adalah code editor yang dilengkapi banyak fitur tambahan terpadu, seperti alat menjalankan kode dan mencari kesalahan. Batas antara keduanya kadang kabur. Ada aplikasi yang secara resmi disebut code editor tapi lewat tambahan fitur bisa berfungsi hampir seperti IDE.

Contoh terbaiknya adalah **VS Code** (Visual Studio Code), yang akan kita pakai di kurikulum ini. Secara teknis VS Code adalah code editor, tapi dengan tambahan (extension) yang bisa dipasang, kemampuannya bisa menyaingi IDE penuh.

**Kenapa VS Code?** Beberapa alasan:

[SAJIKAN: kartu — alasan memilih VS Code]

- **Gratis.** Bisa dipakai tanpa biaya.
- **Ringan.** Tidak terlalu berat untuk kebanyakan perangkat.
- **Sangat populer.** Dipakai jutaan developer di dunia, sehingga mudah mencari bantuan dan tutorial saat kamu bingung.
- **Bisa dikembangkan.** Punya ribuan extension untuk menambah kemampuan sesuai kebutuhanmu.

**Langkah menyiapkan VS Code:**

[SAJIKAN: diagram alur — langkah instalasi: 1. Buka situs code.visualstudio.com, 2. Unduh sesuai sistem operasimu (Windows/Mac/Linux), 3. Jalankan file instalasi, 4. Ikuti proses instalasi sampai selesai, 5. Buka VS Code]

Setelah terbuka, kamu akan melihat antarmuka VS Code. Bagian-bagian utamanya:

[SAJIKAN: infografis — tata letak antarmuka VS Code dengan label pada bagian utama: area menulis kode (editor) di tengah, panel file (explorer) di kiri, dan bilah aktivitas di sisi kiri]

- **Editor:** area besar di tengah tempat kamu menulis kode.
- **Explorer:** panel di kiri yang menampilkan daftar file dan folder proyekmu.
- **Activity Bar:** bilah ikon di paling kiri untuk berpindah antar fitur.

[SAJIKAN: callout — Tips: Kalau tampilan VS Code-mu berbahasa Inggris dan itu membuatmu tidak nyaman, ada extension bahasa Indonesia yang bisa dipasang. Tapi disarankan tetap terbiasa dengan istilah Inggris karena itu yang umum dipakai di dunia developer.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['3.1']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.2']->id,
            'question_type' => 'practice',
            'question_text' => 'Pasang VS Code di perangkatmu, lalu jawab: "Apakah VS Code berhasil terpasang dan terbuka di perangkatmu?" (Ya/Belum/Butuh bantuan). Jika "Butuh bantuan", anggota diarahkan ke forum diskusi.',
            'sort_order' => 1,
        ]);

        $this->units['3.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Terminal / CLI: Teman yang Kelihatan Seram tapi Penting',
            'content' => <<<'TEXT'
Saat pertama melihat terminal, banyak pemula merasa gugup. Layar hitam dengan teks berkedip terlihat seperti sesuatu yang rumit dan berbahaya. Padahal terminal adalah salah satu teman terbaik developer, dan setelah terbiasa, kamu akan merasa nyaman dengannya.

**Apa itu terminal?** Terminal adalah jendela tempat kamu mengetik perintah teks untuk memberitahu komputer melakukan sesuatu. Terminal adalah wujud nyata dari CLI (Command Line Interface) yang sudah kamu pelajari di Unit 3.1. Kata "terminal", "command line", dan "console" sering dipakai bergantian dengan arti yang mirip.

**Kenapa developer butuh terminal?** Karena banyak tugas developer jauh lebih cepat dan tepat dilakukan lewat ketikan perintah dibanding klik-klik menu. Selain itu, banyak alat developer (termasuk Git yang akan kamu pelajari di Modul 4) memang dioperasikan lewat terminal.

**Perintah dasar yang perlu kamu kenal.** Berikut beberapa perintah paling dasar untuk mulai. Perintah ini berlaku umum, meski ada sedikit perbedaan antara sistem operasi.

[SAJIKAN: tabel perbandingan — tabel dua kolom "Perintah" dan "Fungsi", berisi perintah dasar. Tampilkan varian umum untuk Windows dan Mac/Linux jika berbeda]

| Perintah | Fungsi |
|---|---|
| `pwd` | Menampilkan lokasi folder tempat kamu berada sekarang |
| `ls` (Mac/Linux) atau `dir` (Windows) | Menampilkan daftar isi folder |
| `cd nama_folder` | Masuk ke dalam sebuah folder |
| `cd ..` | Keluar ke folder induk (satu tingkat di atas) |
| `mkdir nama_folder` | Membuat folder baru |
| `touch nama_file` (Mac/Linux) | Membuat file baru |

[SAJIKAN: blok kode — contoh sesi terminal sederhana yang menunjukkan membuat folder lalu masuk ke dalamnya:
mkdir latihan_pertama
cd latihan_pertama
pwd]

[SAJIKAN: callout — Tips: Jangan menghafal semua perintah sekaligus. Cukup pahami beberapa yang dasar dulu. Perintah lain akan kamu pelajari saat memang dibutuhkan. Terbiasa itu datang dari latihan, bukan hafalan.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['3.2']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.3']->id,
            'question_type' => 'practice',
            'question_text' => 'Buka terminal di perangkatmu, buat sebuah folder baru bernama `latihan_webi`, lalu masuk ke dalamnya. Tuliskan perintah apa saja yang kamu ketik.',
            'sort_order' => 1,
        ]);

        $this->units['3.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Browser DevTools: Mengintip di Balik Website',
            'content' => <<<'TEXT'
Pernahkah kamu penasaran bagaimana sebuah website dibuat dari dalam? Ternyata kamu bisa mengintipnya langsung dari browsermu, gratis, dengan alat bernama DevTools.

**Cara membuka DevTools.** Di browser seperti Chrome, klik kanan di halaman website manapun lalu pilih "Inspect" atau "Periksa". Bisa juga dengan menekan tombol F12. Sebuah panel akan terbuka, biasanya di sisi kanan atau bawah layar. Itulah DevTools.

**Dua tab yang paling penting untuk dikenal sekarang:**

[SAJIKAN: kartu — dua tab DevTools]

**Tab Elements (Elemen).** Menampilkan struktur halaman website yang sedang kamu buka, yaitu kode HTML yang menyusunnya (HTML akan kamu pelajari di Modul 6). Saat kamu mengarahkan kursor ke bagian kode di tab ini, bagian yang bersangkutan di halaman akan tersorot. Ini cara bagus untuk memahami bagaimana sebuah halaman disusun.

**Tab Console (Konsol).** Menampilkan pesan-pesan dari website, termasuk pesan kesalahan (error) jika ada. Bagi developer, console adalah tempat pertama yang dilihat saat mencari tahu kenapa sesuatu tidak berjalan sebagaimana mestinya.

[SAJIKAN: callout — Tips menyenangkan: Coba buka DevTools di website berita favoritmu, buka tab Elements, dan arahkan kursor ke berbagai bagian. Kamu akan melihat bagaimana halaman itu tersusun. Ini cara aman untuk "mengintip dapur" website manapun tanpa merusak apapun. Perubahan yang kamu buat di sini hanya sementara di layarmu dan tidak mengubah website aslinya.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['3.1']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.4']->id,
            'question_type' => 'practice',
            'question_text' => 'Buka salah satu website favoritmu, buka DevTools, dan lihat tab Elements. Ceritakan satu hal yang kamu temukan atau perhatikan dari struktur halaman itu.',
            'sort_order' => 1,
        ]);

        $this->units['3.5'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 5,
            'title' => 'Peta Bahasa Pemrograman: Level, Kategori, dan Kegunaannya',
            'content' => <<<'TEXT'
Ada ratusan bahasa pemrograman di dunia. Pemula sering bingung harus mulai dari mana dan bertanya "bahasa mana yang paling bagus?". Pertanyaan itu sebenarnya kurang tepat. Yang lebih tepat adalah "bahasa mana yang cocok untuk tujuan apa?". Mari kita bangun petanya.

**Bahasa pemrograman punya tingkatan (level).** Istilah "level" di sini merujuk pada seberapa dekat bahasa itu dengan cara berpikir manusia versus cara kerja mesin.

[SAJIKAN: tabel perbandingan — dua level: Low-level Language dan High-level Language, dengan kolom "Ciri" dan "Contoh"]

- **Low-level language (bahasa tingkat rendah).** Lebih dekat ke bahasa mesin, lebih rumit dibaca manusia, tapi memberi kontrol sangat detail atas perangkat keras. Contohnya Assembly. Jarang dipakai pemula.
- **High-level language (bahasa tingkat tinggi).** Lebih dekat ke bahasa manusia, lebih mudah dibaca dan ditulis. Hampir semua bahasa yang akan kamu temui sebagai pemula termasuk kategori ini. Contohnya Python, JavaScript, dan Java.

Semakin tinggi levelnya, semakin mudah dipelajari manusia. Sebagai pemula, kamu hampir pasti akan bekerja dengan high-level language, jadi tidak perlu khawatir soal low-level.

**Bahasa cocok untuk output yang berbeda.** Inilah bagian terpenting. Tiap bahasa punya "wilayah" yang menjadi kekuatannya.

[SAJIKAN: tabel perbandingan — tabel: kolom "Bahasa", "Cocok untuk apa", "Catatan singkat"]

| Bahasa | Cocok untuk | Catatan singkat |
|---|---|---|
| JavaScript | Web (frontend dan backend) | Bahasa utama yang berjalan di browser |
| Python | Data science, AI, backend, otomasi | Terkenal mudah dibaca, cocok pemula |
| Java | Aplikasi skala besar, Android | Banyak dipakai perusahaan besar |
| Kotlin / Swift | Mobile app (Android / iOS) | Kotlin untuk Android, Swift untuk iOS |
| PHP | Web (backend) | Banyak dipakai website lama dan CMS |
| C / C++ | Aplikasi berperforma tinggi, game | Lebih dekat ke level rendah |

[SAJIKAN: callout — Poin penting: Untuk kurikulum ini, kamu tidak diminta menguasai bahasa pemrograman apapun dulu. Modul ini hanya membekali peta, supaya nanti saat kamu memilih bidang spesifik, kamu sudah tahu bahasa mana yang relevan untuk tujuanmu.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_matching',
            'prerequisite_unit_id' => $this->units['3.1']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.5']->id,
            'question_type' => 'matching',
            'question_text' => 'Cocokkan beberapa bahasa pemrograman dengan output atau kegunaan utamanya.',
            'options' => [
                'pairs' => [
                    ['left' => 'JavaScript', 'right' => 'Web (frontend dan backend)'],
                    ['left' => 'Python', 'right' => 'Data science, AI, backend, otomasi'],
                    ['left' => 'Java', 'right' => 'Aplikasi skala besar, Android'],
                    ['left' => 'Kotlin / Swift', 'right' => 'Mobile app (Android / iOS)'],
                    ['left' => 'PHP', 'right' => 'Web (backend)'],
                ],
            ],
            'correct_answer' => [
                'JavaScript' => 'Web (frontend dan backend)',
                'Python' => 'Data science, AI, backend, otomasi',
                'Java' => 'Aplikasi skala besar, Android',
                'Kotlin / Swift' => 'Mobile app (Android / iOS)',
                'PHP' => 'Web (backend)',
            ],
            'sort_order' => 1,
        ]);

        $this->units['3.6'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 6,
            'title' => 'Framework: Apa, Kenapa, dan Bagaimana',
            'content' => <<<'TEXT'
Kamu sudah punya peta bahasa pemrograman. Sekarang muncul istilah yang sangat sering kamu dengar: framework. Apa itu, dan kenapa developer hampir selalu memakainya?

**Apa itu framework?** Framework adalah kerangka kerja siap pakai yang menyediakan struktur dan alat dasar, sehingga developer tidak perlu membangun semuanya dari nol. Kata "framework" secara harfiah berarti "kerangka kerja".

**Kenapa developer tidak selalu mulai dari nol?** Bayangkan setiap kali membuat website, kamu harus menulis ulang semua hal dasar seperti cara menangani halaman, cara mengatur data, dan cara memproses formulir. Itu melelahkan dan boros waktu. Framework sudah menyediakan pekerjaan dasar itu, sehingga developer bisa fokus membangun hal yang benar-benar khas dari proyeknya.

Analogi sederhananya: membangun rumah dari nol berarti mencetak batu bata sendiri, membuat rangka sendiri, semuanya dari awal. Memakai framework seperti membeli rumah setengah jadi yang fondasi dan rangkanya sudah siap, tinggal kamu isi dan sesuaikan.

**Hubungan framework dengan bahasa pemrograman.** Setiap framework dibangun di atas bahasa pemrograman tertentu. Jadi kamu harus paham bahasanya dulu, baru bisa memakai framework-nya. Berikut beberapa contoh:

[SAJIKAN: tabel perbandingan — tabel: kolom "Framework", "Bahasa induk", "Untuk apa"]

| Framework | Bahasa Induk | Untuk apa |
|---|---|---|
| React | JavaScript | Membangun tampilan web (frontend) |
| Laravel | PHP | Membangun backend web |
| Django | Python | Membangun backend web |
| Flutter | Dart | Membangun mobile app |

[SAJIKAN: callout — Catatan: Perhatikan bahwa React dan Laravel keduanya untuk web, tapi memakai bahasa berbeda dan mengurus bagian berbeda (React untuk tampilan, Laravel untuk belakang layar). Ini menunjukkan bahwa pilihan framework tergantung bahasa yang kamu kuasai dan bagian apa yang ingin kamu bangun.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['3.5']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.6']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'React dibangun di atas bahasa apa?',
            'options' => ['JavaScript', 'PHP', 'Python', 'Dart'],
            'correct_answer' => 'JavaScript',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.6']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Laravel dibangun di atas bahasa apa?',
            'options' => ['JavaScript', 'PHP', 'Python', 'Dart'],
            'correct_answer' => 'PHP',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.6']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa yang disediakan framework supaya developer tidak perlu membangun semuanya dari nol?',
            'options' => ['Struktur dan alat dasar siap pakai', 'Hardware tambahan', 'Bahasa pemrograman baru'],
            'correct_answer' => 'Struktur dan alat dasar siap pakai',
            'sort_order' => 3,
        ]);

        $this->units['3.7'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 7,
            'title' => 'Klarifikasi Penting: Hal-Hal yang Sering Salah Kaprah Pemula',
            'content' => <<<'TEXT'
Unit ini penting karena membereskan kesalahpahaman yang sangat umum di kalangan pemula. Kalau kamu memahami unit ini dengan baik, kamu sudah lebih paham daripada banyak orang yang baru mulai belajar.

**Kesalahpahaman 1: "HTML dan CSS adalah bahasa pemrograman."** Ini keliru, dan penting untuk diluruskan sejak awal.

[SAJIKAN: tabel perbandingan — tiga hal: Bahasa Pemrograman, Markup Language, Stylesheet Language, dengan kolom "Apa fungsinya" dan "Contoh"]

- **HTML** adalah **markup language** (bahasa penanda), bukan bahasa pemrograman. Tugasnya menandai dan menyusun struktur konten, misalnya "ini judul", "ini paragraf", "ini gambar". HTML tidak bisa membuat keputusan atau melakukan perhitungan.
- **CSS** adalah **stylesheet language** (bahasa penata gaya). Tugasnya mengatur tampilan, misalnya warna, ukuran, dan tata letak. CSS juga tidak membuat keputusan atau perhitungan.
- **Bahasa pemrograman** (seperti JavaScript) mampu membuat keputusan, melakukan perhitungan, dan menjalankan logika. Misalnya "jika pengguna menekan tombol, tampilkan pesan".

Jadi apa bedanya secara inti? Bahasa pemrograman bisa "berpikir" lewat logika (jika begini maka begitu), sedangkan HTML dan CSS tidak. HTML menyusun, CSS mempercantik, dan bahasa pemrograman menjalankan logika.

[SAJIKAN: callout — Analogi: Kalau website adalah tubuh manusia, HTML adalah kerangka tulang (struktur), CSS adalah penampilan luar seperti kulit dan pakaian (tampilan), dan JavaScript adalah otak dan otot yang membuat tubuh bisa bergerak dan mengambil keputusan (logika).]

**Kesalahpahaman 2: "Framework dan library itu sama."** Keduanya sama-sama membantu developer tidak mulai dari nol, tapi berbeda cara pakainya. Perbedaan ini akan dibahas tuntas di Unit 3.8.

**Kesalahpahaman 3: "Belajar satu bahasa berarti bisa semua."** Setiap bahasa punya wilayah kekuatannya sendiri, seperti yang kamu lihat di Unit 3.5. Menguasai satu bahasa adalah awal yang baik, tapi bidang yang berbeda kadang menuntut bahasa yang berbeda.
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['3.6']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.7']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'HTML adalah bahasa pemrograman. (Benar/Salah)',
            'options' => ['Benar', 'Salah'],
            'correct_answer' => 'Salah',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.7']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'CSS bertugas mengatur tampilan halaman. (Benar/Salah)',
            'options' => ['Benar', 'Salah'],
            'correct_answer' => 'Benar',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.7']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Framework dan library itu benar-benar sama persis. (Benar/Salah)',
            'options' => ['Benar', 'Salah'],
            'correct_answer' => 'Salah',
            'sort_order' => 3,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.7']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Belajar satu bahasa pemrograman berarti otomatis bisa memakai semua bahasa lain. (Benar/Salah)',
            'options' => ['Benar', 'Salah'],
            'correct_answer' => 'Salah',
            'sort_order' => 4,
        ]);

        $this->units['3.8'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 8,
            'title' => 'Package Manager dan Library: Konsep Dasar',
            'content' => <<<'TEXT'
Di dunia developer, ada prinsip sederhana: jangan buat ulang sesuatu yang sudah dibuat orang lain dengan baik. Prinsip ini melahirkan konsep library dan package manager.

**Apa itu library?** Library (pustaka) adalah kumpulan kode siap pakai yang dibuat orang lain untuk menyelesaikan tugas tertentu. Daripada menulis sendiri kode untuk, misalnya, menampilkan tanggal dengan format cantik, kamu bisa memakai library yang sudah menyediakannya. Kamu tinggal memanggilnya saat butuh.

**Apa itu package?** Package (paket) pada dasarnya adalah cara library dibungkus dan didistribusikan supaya mudah dipasang dan dipakai. Dalam praktik sehari-hari, istilah package dan library sering dipakai bergantian.

**Apa itu package manager?** Package manager adalah alat yang membantu developer mencari, memasang, dan mengelola package atau library dalam sebuah proyek. Tanpa package manager, kamu harus mengunduh dan mengatur setiap library secara manual, yang merepotkan dan mudah keliru.

Contoh package manager paling terkenal di dunia web adalah **npm** (Node Package Manager). Dengan npm, memasang sebuah library cukup dilakukan lewat satu perintah di terminal.

[SAJIKAN: blok kode — contoh perintah npm memasang library (contoh konsep, belum praktik):
npm install nama-library]

**Bedanya library dan framework.** Ini pertanyaan yang tadi tertunda dari Unit 3.7. Perbedaannya ada pada "siapa yang memegang kendali":

[SAJIKAN: tabel perbandingan — Library vs Framework, kolom "Siapa pegang kendali" dan "Analogi"]

- **Library:** kamu yang memegang kendali. Kamu memanggil library saat kamu butuh, seperti mengambil satu alat dari kotak perkakas ketika diperlukan.
- **Framework:** framework yang memegang kendali. Kamu menaruh kodemu di dalam struktur yang sudah disediakan framework, dan framework yang mengatur kapan kodemu dijalankan. Seperti mengikuti aturan main sebuah permainan yang sudah ditentukan.

[SAJIKAN: callout — Cara mudah mengingat: Dengan library, kamu memanggil kodenya. Dengan framework, kodenya yang memanggil kamu.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_matching',
            'prerequisite_unit_id' => $this->units['3.6']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.8']->id,
            'question_type' => 'matching',
            'question_text' => 'Cocokkan istilah (library, package, package manager, framework) dengan penjelasannya, dan bedakan mana yang "kamu pegang kendali" versus "kerangka yang pegang kendali".',
            'options' => [
                'pairs' => [
                    ['left' => 'Library', 'right' => 'Kumpulan kode siap pakai yang kamu panggil saat butuh — kamu pegang kendali'],
                    ['left' => 'Package', 'right' => 'Cara library dibungkus dan didistribusikan supaya mudah dipasang'],
                    ['left' => 'Package manager', 'right' => 'Alat yang membantu mencari, memasang, dan mengelola package/library'],
                    ['left' => 'Framework', 'right' => 'Kerangka kerja yang mengatur kapan kodemu dijalankan — framework pegang kendali'],
                ],
            ],
            'correct_answer' => [
                'Library' => 'Kumpulan kode siap pakai yang kamu panggil saat butuh — kamu pegang kendali',
                'Package' => 'Cara library dibungkus dan didistribusikan supaya mudah dipasang',
                'Package manager' => 'Alat yang membantu mencari, memasang, dan mengelola package/library',
                'Framework' => 'Kerangka kerja yang mengatur kapan kodemu dijalankan — framework pegang kendali',
            ],
            'sort_order' => 1,
        ]);

        $this->units['3.9'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 9,
            'title' => 'Paradigma Membuat Proyek: Dengan dan Tanpa Framework/Library',
            'content' => <<<'TEXT'
Sekarang muncul pertanyaan praktis: kalau ada framework dan library yang mempermudah, kenapa tidak selalu pakai saja? Jawabannya tidak sesederhana itu. Memilih cara membangun proyek adalah soal menimbang keadaan, bukan soal mana yang paling canggih.

**Tiga pendekatan membangun proyek:**

[SAJIKAN: kartu — tiga pendekatan]

**1. Dari nol (vanilla).** Istilah "vanilla" berarti murni tanpa tambahan, hanya memakai bahasa dasar tanpa framework atau library. Membangun dari nol memberi kontrol penuh dan pemahaman mendalam, tapi butuh waktu dan usaha lebih banyak.

**2. Dengan library.** Memakai potongan-potongan kode siap pakai untuk tugas tertentu, sambil tetap menyusun sendiri struktur besar proyeknya. Pendekatan ini fleksibel: kamu ambil bantuan hanya di bagian yang kamu butuhkan.

**3. Dengan framework.** Memakai kerangka kerja lengkap yang sudah menentukan struktur proyek. Pendekatan ini paling cepat untuk proyek besar dan kompleks, tapi kamu harus mengikuti aturan main framework tersebut.

**Kapan masing-masing tepat dipakai?**

[SAJIKAN: tabel perbandingan — tabel: kolom "Pendekatan", "Kapan cocok", "Konsekuensi"]

| Pendekatan | Kapan cocok | Konsekuensi |
|---|---|---|
| Dari nol (vanilla) | Belajar fundamental, proyek sangat kecil dan sederhana | Paham mendalam, tapi lambat untuk proyek besar |
| Dengan library | Butuh bantuan di bagian tertentu tapi ingin fleksibilitas | Seimbang antara kendali dan kecepatan |
| Dengan framework | Proyek besar, kompleks, dikerjakan tim, butuh cepat | Cepat dan terstruktur, tapi harus ikut aturan framework |

[SAJIKAN: callout — Poin penting untuk pemula: Justru karena kamu sedang belajar, kurikulum ini nanti mengajakmu membangun dengan HTML dan CSS dari nol (vanilla) di Modul 6. Tujuannya supaya kamu benar-benar paham dasarnya. Setelah fondasimu kuat, memakai framework akan terasa jauh lebih masuk akal karena kamu tahu apa yang sebenarnya dikerjakan framework itu untukmu.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'essay',
            'prerequisite_unit_id' => $this->units['3.8']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.9']->id,
            'question_type' => 'essay',
            'question_text' => 'Dengan bahasamu sendiri, jelaskan kenapa seorang pemula justru disarankan belajar membangun dari nol dulu sebelum memakai framework.',
            'sort_order' => 1,
        ]);

        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Aku memahami perbedaan IDE, CLI, dan browser DevTools',
                'VS Code sudah terpasang dan bisa dijalankan di perangkatku',
                'Aku sudah mencoba perintah dasar di terminal',
                'Aku memahami peta bahasa pemrograman dan kegunaannya',
                'Aku memahami apa itu framework dan library, serta perbedaannya',
                'Aku paham bahwa HTML dan CSS bukan bahasa pemrograman',
            ],
            'intermezo_questions' => [
                'Kuis persiapan dan pemetaan personal. Jawaban dari kuis ini akan diterima langsung oleh PIC sebagai bahan pertimbangan pendampingan ke depan. Tidak ada jawaban benar atau salah, jawab sejujurnya sesuai kondisimu.',
                'Apakah kamu sudah familiar dengan tools IDE? (pilihan: Sudah / Sedikit / Belum sama sekali)',
                'Apa IDE atau code editor yang sudah terpasang di perangkatmu? (input teks)',
                'Apa bahasa pemrograman yang sudah kamu ketahui, kalau ada? (input teks, boleh dikosongkan)',
                'Apa framework yang kamu rasa tertarik untuk dicoba? (input teks)',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'roadmap.sh',
            'url' => 'https://roadmap.sh',
            'source_name' => 'Untuk melihat peta bahasa pemrograman dan framework yang relevan dengan tiap bidang. Kamu bisa melihat, misalnya, framework apa yang biasa dipakai frontend developer atau backend developer.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'W3Schools',
            'url' => 'https://www.w3schools.com',
            'source_name' => 'Platform belajar yang menyediakan pengenalan berbagai bahasa pemrograman dan teknologi web dengan contoh sederhana. Cocok untuk mengintip seperti apa bentuk kode dari bahasa-bahasa yang disebut di modul ini.',
        ]);
    }

    private function seedModule4(): void
    {
        $module = Module::create([
            'order_number' => 4,
            'title' => 'Version Control dengan Git',
            'description' => 'Menguasai konsep dan praktik dasar Git secara lokal (di komputer sendiri). Ini pondasi yang harus kuat sebelum masuk ke GitHub dan kolaborasi tim. Modul ini tidak punya tugas atau intermezo tersendiri, karena akan digabung dengan Modul 5.',
            'level_number' => 3,
        ]);

        $this->units['4.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Apa Itu Git?',
            'content' => <<<'TEXT'
Git adalah salah satu alat paling penting dalam dunia developer, dan hebatnya, banyak pemula tidak pernah diajari Git dengan benar. Modul ini mengubah itu. Kita mulai dari definisi yang jelas.

**Apa itu Git?** Git adalah sistem version control (kontrol versi), yaitu alat yang mencatat setiap perubahan yang kamu buat pada file-file proyekmu, sehingga kamu bisa melihat riwayat perubahan, kembali ke versi sebelumnya, dan bekerja bersama orang lain tanpa saling menimpa pekerjaan.

Mari kita pecah istilahnya:

[SAJIKAN: infografis — pecahan istilah "version control": "version" = versi/tahapan pekerjaan, "control" = kendali/kemampuan mengatur]

- **Version (versi)** merujuk pada keadaan proyekmu pada suatu titik waktu. Setiap kali kamu menyimpan perubahan penting, kamu membuat versi baru.
- **Control (kontrol)** merujuk pada kemampuan mengatur versi-versi itu: melihatnya, membandingkannya, dan kembali ke versi lama jika perlu.

**Di mana posisi Git dalam ekosistem developer?** Git adalah alat yang berjalan di komputermu dan dioperasikan lewat terminal (yang sudah kamu pelajari di Modul 3). Hampir semua developer profesional di dunia memakai Git. Ia sudah menjadi standar industri untuk mengelola kode.

[SAJIKAN: callout — Catatan penting: Git (alat kontrol versi) sering tertukar dengan GitHub (platform online). Keduanya berbeda dan akan dijelaskan bedanya di Modul 5. Untuk sekarang, fokuslah dulu pada Git sebagai alat di komputermu.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['3.3']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa itu Git?',
            'options' => ['Sistem version control (kontrol versi)', 'Platform online penyimpan repository', 'Bahasa pemrograman'],
            'correct_answer' => 'Sistem version control (kontrol versi)',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Git dioperasikan lewat apa?',
            'options' => ['Terminal', 'Aplikasi desain grafis', 'Browser saja'],
            'correct_answer' => 'Terminal',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Git dan GitHub adalah dua nama untuk hal yang sama persis. (Benar/Salah)',
            'options' => ['Benar', 'Salah'],
            'correct_answer' => 'Salah',
            'sort_order' => 3,
        ]);

        $this->units['4.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Kenapa Git Itu Penting? (Dengan Analogi Konkret)',
            'content' => <<<'TEXT'
Definisi saja belum cukup membuatmu merasakan kenapa Git begitu penting. Mari kita lihat lewat dua cerita nyata yang mungkin pernah kamu alami versinya sendiri.

**Cerita tanpa Git.** Bayangkan kamu mengerjakan sebuah tugas dokumen. Kamu menyimpannya sebagai `tugas.docx`. Lalu kamu mengubahnya dan takut versi lama hilang, jadi kamu simpan `tugas_revisi.docx`. Lalu ada revisi lagi, jadi `tugas_revisi_fix.docx`. Lalu `tugas_revisi_fix_beneran.docx`. Lalu `tugas_final_FIX_banget.docx`. Beberapa hari kemudian kamu kebingungan sendiri: mana yang paling baru? Apa saja yang berubah di antara versi-versi itu? Kalau kamu mau kembali ke bagian yang kemarin sempat kamu hapus, apakah masih ada?

[SAJIKAN: infografis — tumpukan file dengan nama berantakan: "tugas.docx", "tugas_revisi.docx", "tugas_fix.docx", "tugas_final_beneran.docx", dengan tanda tanya besar dan wajah bingung]

Ini kekacauan yang dialami banyak orang. Sekarang bayangkan situasi ini terjadi bukan pada satu dokumen, tapi pada proyek dengan ratusan file kode, dan dikerjakan oleh beberapa orang sekaligus. Kekacauannya berlipat ganda.

**Cerita dengan Git.** Dengan Git, kamu cukup punya satu folder proyek. Setiap kali kamu menyelesaikan perubahan penting, kamu "menyimpan titik" (istilahnya commit, akan dipelajari nanti). Git mencatat:

[SAJIKAN: kartu — apa yang dicatat Git di tiap titik simpan]

- Apa saja yang berubah dari versi sebelumnya
- Kapan perubahan itu dibuat
- Siapa yang membuatnya
- Catatan singkat kenapa perubahan itu dibuat

Hasilnya, kamu punya riwayat lengkap dan rapi. Kamu bisa melihat perjalanan proyekmu dari awal, membandingkan versi, dan kembali ke titik manapun kalau ada yang salah. Tidak ada lagi file bernama `final_beneran_fix`.

[SAJIKAN: tabel perbandingan — "Tanpa Git" vs "Dengan Git", baris: cara menyimpan versi, kemudahan melihat perubahan, kemudahan kembali ke versi lama, kerja bareng orang lain]

[SAJIKAN: callout — Inti dari unit ini: Git menyelesaikan tiga masalah besar sekaligus: kekacauan versi file, hilangnya riwayat perubahan, dan kesulitan bekerja bersama orang lain. Itulah kenapa Git menjadi alat yang tidak bisa ditawar bagi developer.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['4.1']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa masalah utama yang diselesaikan Git berdasarkan cerita di unit ini?',
            'options' => ['Kekacauan versi file, hilangnya riwayat, dan kesulitan kerja bersama', 'Kecepatan internet', 'Ukuran file yang besar'],
            'correct_answer' => 'Kekacauan versi file, hilangnya riwayat, dan kesulitan kerja bersama',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Istilah "menyimpan titik" perubahan penting di Git disebut apa?',
            'options' => ['Commit', 'Save As', 'Export'],
            'correct_answer' => 'Commit',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Tanpa Git, apa yang biasanya terjadi saat merevisi dokumen berkali-kali?',
            'options' => ['Muncul banyak file dengan nama membingungkan seperti "final_beneran_fix"', 'File otomatis tersinkronisasi rapi', 'Tidak ada masalah apapun'],
            'correct_answer' => 'Muncul banyak file dengan nama membingungkan seperti "final_beneran_fix"',
            'sort_order' => 3,
        ]);

        $this->units['4.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Bagaimana Cara Kerja Git?',
            'content' => <<<'TEXT'
Sekarang kamu paham kenapa Git penting. Mari kita pahami cara kerjanya, karena ini yang akan membuatmu nyaman saat mulai praktik.

**Apa yang di-track (dilacak) Git?** Git melacak perubahan pada file-file di dalam sebuah folder proyek yang kamu tandai untuk dipantau Git. Git memperhatikan file mana yang baru, mana yang diubah, dan mana yang dihapus.

**Bagaimana Git menyimpan perubahan?** Setiap kali kamu membuat titik simpan (commit), Git mengambil semacam "foto" keadaan proyekmu saat itu. Foto ini disebut **snapshot**. Jadi riwayat proyekmu di Git adalah rangkaian snapshot berurutan, seperti album foto yang menunjukkan perjalanan proyek dari waktu ke waktu.

[SAJIKAN: diagram alur — rangkaian snapshot berurutan sebagai kotak-kotak terhubung panah: Snapshot 1, Snapshot 2, Snapshot 3, dengan keterangan "tiap snapshot = keadaan proyek pada satu titik waktu"]

[SAJIKAN: callout — Catatan untuk yang penasaran: Sebagian sistem kontrol versi lain menyimpan perubahan sebagai daftar selisih (disebut diff), yaitu hanya mencatat apa yang berbeda. Git bekerja lebih seperti mengambil snapshot utuh tiap titik simpan, meski secara cerdas Git tetap hemat ruang penyimpanan. Kamu tidak perlu memusingkan detail teknis ini sekarang, cukup pahami idenya: Git menyimpan riwayat sebagai rangkaian keadaan proyek.]

**Kapan Git digunakan?** Ini pertanyaan penting yang jarang dijawab jelas untuk pemula:

[SAJIKAN: kartu — jawaban tiga pertanyaan umum tentang kapan pakai Git]

- **Apakah untuk setiap proyek?** Idealnya ya, untuk setiap proyek kode yang serius, sekecil apapun. Membiasakan diri memakai Git sejak proyek kecil membuatmu terbiasa saat mengerjakan proyek besar.
- **Kapan mulai menggunakannya?** Sebaiknya sejak awal proyek, bukan di tengah jalan. Semakin awal kamu memulai Git, semakin lengkap riwayat yang tercatat.
- **Apakah Git jalan otomatis?** Tidak. Git tidak menyimpan titik secara otomatis. Kamu yang memutuskan kapan membuat titik simpan (commit), biasanya setiap kali menyelesaikan satu bagian pekerjaan yang bermakna.

[SAJIKAN: callout — Poin penting: Karena Git tidak otomatis, membuat titik simpan secara teratur adalah kebiasaan yang perlu kamu bangun. Anggap seperti menyimpan progres di permainan: kamu simpan setiap kali mencapai titik penting supaya aman.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['4.2']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.3']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Git menyimpan titik perubahan secara otomatis tanpa perlu perintah. (Benar/Salah)',
            'options' => ['Benar', 'Salah'],
            'correct_answer' => 'Salah',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.3']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Setiap titik simpan (commit) di Git mengambil sebuah "snapshot" keadaan proyek. (Benar/Salah)',
            'options' => ['Benar', 'Salah'],
            'correct_answer' => 'Benar',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.3']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Sebaiknya Git mulai dipakai di tengah-tengah proyek saja, bukan sejak awal. (Benar/Salah)',
            'options' => ['Benar', 'Salah'],
            'correct_answer' => 'Salah',
            'sort_order' => 3,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.3']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Git idealnya dipakai untuk setiap proyek kode yang serius, sekecil apapun. (Benar/Salah)',
            'options' => ['Benar', 'Salah'],
            'correct_answer' => 'Benar',
            'sort_order' => 4,
        ]);

        $this->units['4.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Instalasi Git dan Konfigurasi Awal',
            'content' => <<<'TEXT'
Waktunya menyiapkan Git di komputermu. Ikuti langkah-langkah ini pelan-pelan.

**Langkah 1: Instalasi Git.**

[SAJIKAN: diagram alur — langkah instalasi: 1. Buka situs git-scm.com, 2. Unduh sesuai sistem operasimu, 3. Jalankan file instalasi, 4. Ikuti proses instalasi dengan pilihan default, 5. Selesai]

**Langkah 2: Verifikasi instalasi.** Setelah terpasang, buka terminal (yang kamu pelajari di Modul 3) dan ketik perintah berikut untuk memastikan Git sudah terpasang:

[SAJIKAN: blok kode:
git --version]

Kalau muncul nomor versi (misalnya `git version 2.40.0`), berarti Git sudah berhasil terpasang.

**Langkah 3: Konfigurasi awal.** Git perlu tahu siapa kamu, supaya bisa mencatat siapa pembuat tiap perubahan. Atur nama dan email dengan perintah berikut (ganti dengan nama dan emailmu):

[SAJIKAN: blok kode:
git config --global user.name "Nama Kamu"
git config --global user.email "email@kamu.com"]

Pengaturan ini cukup dilakukan sekali di satu komputer. Kata `--global` berarti pengaturan berlaku untuk semua proyekmu di komputer itu.

[SAJIKAN: callout — Tips: Gunakan nama dan email yang sama dengan yang nanti akan kamu pakai untuk akun GitHub di Modul 5, supaya keduanya terhubung rapi. Kalau kamu belum punya email khusus, tidak masalah pakai email yang biasa kamu gunakan.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['4.3']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.4']->id,
            'question_type' => 'practice',
            'question_text' => 'Pasang Git, lalu jalankan `git --version`. "Apakah Git berhasil terpasang? Tuliskan nomor versi yang muncul." (input teks). Jika gagal, arahkan ke forum.',
            'sort_order' => 1,
        ]);

        $this->units['4.5'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 5,
            'title' => 'Repository: Tempat Semua Bermula',
            'content' => <<<'TEXT'
Semua pekerjaan dengan Git berpusat pada satu hal: repository. Mari pahami apa itu dan cara membuatnya.

**Apa itu repository?** Repository (sering disingkat repo) adalah folder proyek yang dipantau oleh Git. Begitu sebuah folder dijadikan repository, Git mulai melacak semua perubahan di dalamnya. Bisa dibilang, repository adalah "wilayah kerja" Git.

**Membuat repository dengan `git init`.** Untuk mengubah sebuah folder biasa menjadi repository Git, kamu masuk ke folder itu lewat terminal, lalu jalankan:

[SAJIKAN: blok kode:
git init]

Setelah perintah ini dijalankan, Git membuat sebuah folder tersembunyi bernama `.git` di dalam folder proyekmu. Folder `.git` inilah "otak" Git: di sinilah Git menyimpan seluruh riwayat dan catatan perubahan. Kamu tidak perlu mengutak-atik isi folder ini, cukup tahu bahwa keberadaannya menandakan folder itu sudah menjadi repository.

[SAJIKAN: infografis — sebuah folder proyek dengan folder tersembunyi ".git" di dalamnya, diberi label ".git = otak Git tempat riwayat disimpan"]

**Konsep working directory.** Working directory (direktori kerja) adalah folder tempat kamu benar-benar bekerja dan mengedit file. Inilah file-file yang kamu lihat dan ubah secara langsung. Git mengamati working directory ini dan membandingkannya dengan riwayat yang tersimpan, untuk tahu apa saja yang berubah.

[SAJIKAN: callout — Ringkasnya: repository = folder proyek yang dipantau Git. Folder .git = tempat Git menyimpan riwayat. Working directory = file-file yang sedang kamu kerjakan. Ketiganya bekerja bersama.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['4.4']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.5']->id,
            'question_type' => 'practice',
            'question_text' => 'Buat sebuah folder baru, masuk ke dalamnya lewat terminal, dan jalankan `git init`. Tuliskan pesan apa yang muncul di terminal setelah perintah itu dijalankan.',
            'sort_order' => 1,
        ]);

        $this->units['4.6'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 6,
            'title' => 'Alur Dasar Git: Add, Commit, Status',
            'content' => <<<'TEXT'
Ini unit inti dari seluruh Modul 4. Siklus add-commit adalah kegiatan paling sering kamu lakukan dengan Git. Mari pahami langkah demi langkah.

Bayangkan alur kerja Git punya tiga area:

[SAJIKAN: diagram alur — tiga area berurutan dengan panah: "Working Directory (tempat kamu edit)" → (git add) → "Staging Area (ruang siap-siap)" → (git commit) → "Repository (riwayat tersimpan)"]

**1. Working Directory.** Tempat kamu mengedit file, seperti yang sudah kamu pelajari.

**2. Staging Area (Ruang Persiapan).** Sebelum menyimpan perubahan secara resmi, kamu memilih dulu perubahan mana yang mau disimpan. Perubahan yang kamu pilih masuk ke staging area, semacam ruang tunggu sebelum benar-benar disimpan. Perintahnya adalah `git add`.

**3. Repository (Riwayat).** Setelah perubahan siap di staging area, kamu menyimpannya secara resmi sebagai satu titik dalam riwayat. Perintahnya adalah `git commit`.

**Perintah-perintahnya:**

[SAJIKAN: blok kode — contoh siklus lengkap:
git status
git add nama_file.txt
git commit -m "Menambahkan file pertama"]

Mari kita bedah:

[SAJIKAN: kartu — tiga perintah dengan penjelasan]

- **`git status`** menampilkan keadaan terkini: file mana yang berubah, mana yang sudah masuk staging area, mana yang belum. Perintah ini aman dijalankan kapan saja untuk mengecek keadaan. Biasakan sering menjalankannya.
- **`git add nama_file`** memasukkan file yang kamu sebut ke staging area. Untuk memasukkan semua perubahan sekaligus, gunakan `git add .` (dengan titik).
- **`git commit -m "pesan"`** menyimpan perubahan di staging area sebagai satu titik resmi dalam riwayat. Bagian `-m` diikuti pesan singkat yang menjelaskan apa yang kamu ubah. Pesan ini disebut commit message.

[SAJIKAN: callout — Analogi belanja: Working directory seperti berkeliling toko dan mengambil barang. Staging area seperti keranjang belanja tempat kamu menaruh barang yang mau dibeli (git add). Commit seperti membayar di kasir dan mendapat struk resmi (git commit). Struk itulah catatan permanen belanjaanmu.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['4.5']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.6']->id,
            'question_type' => 'practice',
            'question_text' => 'Di repository yang kamu buat sebelumnya, buat sebuah file teks, lalu lakukan `git add` dan `git commit` dengan pesan yang bermakna. Tuliskan urutan perintah yang kamu jalankan.',
            'sort_order' => 1,
        ]);

        $this->units['4.7'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 7,
            'title' => 'Membaca Riwayat: Git Log',
            'content' => <<<'TEXT'
Setelah kamu membuat beberapa commit, kamu tentu ingin melihat riwayatnya. Di sinilah `git log` berperan.

**Perintah `git log`.** Perintah ini menampilkan daftar semua commit yang pernah dibuat di repository, dari yang terbaru ke yang terlama. Untuk tiap commit, kamu bisa melihat:

[SAJIKAN: kartu — informasi yang ditampilkan git log]

- Kode unik commit (deretan huruf dan angka yang menjadi penanda tiap commit)
- Nama dan email pembuat commit
- Tanggal dan waktu commit dibuat
- Commit message (pesan yang kamu tulis saat commit)

[SAJIKAN: blok kode — contoh tampilan git log sederhana:
commit a1b2c3d4 (HEAD)
Author: Nama Kamu <email@kamu.com>
Date:   Mon Apr 20 10:30:00 2026
    Menambahkan file pertama]

**Kenapa commit message yang jelas itu penting?** Perhatikan bagian pesan di riwayat. Kalau pesanmu jelas seperti "Menambahkan halaman kontak", kamu dan tim mudah memahami sejarah proyek hanya dengan membaca log. Tapi kalau pesanmu asal seperti "update", "fix", atau "aaa", riwayatmu menjadi tidak berguna karena tidak ada yang tahu apa yang sebenarnya berubah.

[SAJIKAN: tabel perbandingan — "Commit message buruk" (update, fix, asdf, revisi) vs "Commit message baik" (Menambahkan halaman kontak, Memperbaiki warna tombol login, Menghapus gambar yang tidak terpakai)]

[SAJIKAN: callout — Kebiasaan baik: Tulis commit message yang menjelaskan APA yang kamu ubah, singkat tapi bermakna. Anggap kamu sedang meninggalkan pesan untuk dirimu di masa depan atau untuk rekan setim. Mereka akan berterima kasih.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['4.6']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.7']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Manakah commit message yang baik?',
            'options' => ['update', 'Menambahkan halaman kontak', 'aaa', 'fix'],
            'correct_answer' => 'Menambahkan halaman kontak',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.7']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Perintah apa yang menampilkan daftar semua commit dari yang terbaru ke terlama?',
            'options' => ['git log', 'git status', 'git add'],
            'correct_answer' => 'git log',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.7']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Informasi apa yang TIDAK ditampilkan git log untuk tiap commit?',
            'options' => ['Harga proyek', 'Kode unik commit', 'Nama dan email pembuat commit', 'Commit message'],
            'correct_answer' => 'Harga proyek',
            'sort_order' => 3,
        ]);

        $this->units['4.8'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 8,
            'title' => 'Branching: Kerja Paralel Tanpa Nabrak',
            'content' => <<<'TEXT'
Branching adalah salah satu fitur paling kuat dari Git, dan sering disebut sebagai fitur andalannya. Mari pahami dengan tenang.

**Apa itu branch?** Branch (cabang) adalah jalur pengerjaan terpisah di dalam proyekmu. Bayangkan proyekmu seperti pohon. Ada batang utama, lalu kamu bisa membuat cabang untuk mengerjakan sesuatu tanpa mengganggu batang utama. Setelah selesai dan yakin, hasil di cabang itu bisa digabungkan kembali ke batang utama.

[SAJIKAN: diagram alur — visual percabangan: garis utama (main) lurus, lalu bercabang ke atas menjadi branch "fitur-baru", bekerja beberapa commit di cabang, lalu bergabung kembali ke main]

**Kenapa butuh branch?** Bayangkan kamu sedang mengerjakan fitur baru yang belum tentu berhasil. Kalau kamu kerjakan langsung di batang utama, kamu berisiko merusak versi yang sudah berjalan baik. Dengan branch, kamu bisa bereksperimen di jalur terpisah dengan aman. Kalau berhasil, gabungkan. Kalau gagal, cukup buang cabangnya tanpa merusak apapun.

Branch juga memungkinkan beberapa orang bekerja bersamaan pada bagian berbeda tanpa saling menimpa, yang akan sangat berguna saat kerja tim di Modul 5.

**Perintah dasar branch:**

[SAJIKAN: blok kode — perintah branch:
git branch
git branch fitur-baru
git switch fitur-baru]

[SAJIKAN: kartu — penjelasan perintah branch]

- **`git branch`** menampilkan daftar semua branch dan menandai branch tempat kamu berada sekarang.
- **`git branch nama-branch`** membuat branch baru dengan nama yang kamu tentukan.
- **`git switch nama-branch`** berpindah ke branch tersebut. (Di materi lama kamu mungkin menemui `git checkout nama-branch` untuk hal yang sama; `git switch` adalah perintah yang lebih baru dan lebih jelas untuk berpindah branch.)

[SAJIKAN: callout — Catatan: Branch utama biasanya bernama `main` (di beberapa proyek lama bernama `master`). Ini adalah jalur utama proyekmu, tempat versi yang dianggap stabil berada.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['4.6']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.8']->id,
            'question_type' => 'practice',
            'question_text' => 'Di repository latihanmu, buat sebuah branch baru bernama `latihan-branch`, lalu pindah ke branch itu. Tuliskan perintah yang kamu jalankan, dan hasil dari `git branch`.',
            'sort_order' => 1,
        ]);

        $this->units['4.9'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 9,
            'title' => 'Merging: Menggabungkan Hasil Kerja',
            'content' => <<<'TEXT'
Setelah kamu bekerja di sebuah branch dan hasilnya sudah siap, langkah berikutnya adalah menggabungkannya kembali ke branch utama. Proses ini disebut merging.

**Apa itu merge?** Merge (gabung) adalah proses menyatukan perubahan dari satu branch ke branch lain. Biasanya kamu menggabungkan branch fitur ke branch utama (main) setelah fitur itu selesai.

**Perintah dasar merge:** Untuk menggabungkan branch `fitur-baru` ke `main`, kamu pindah dulu ke branch tujuan (main), lalu jalankan perintah merge:

[SAJIKAN: blok kode:
git switch main
git merge fitur-baru]

Perintah di atas berarti "gabungkan perubahan dari fitur-baru ke dalam branch tempat aku berada sekarang (main)".

[SAJIKAN: diagram alur — dua branch (main dan fitur-baru) yang tadinya terpisah, lalu menyatu di satu titik dengan label "merge". Perubahan dari fitur-baru kini ada di main]

**Pengenalan merge conflict.** Kebanyakan merge berjalan mulus. Tapi kadang, jika dua branch mengubah bagian yang sama dari file yang sama, Git tidak tahu versi mana yang harus dipilih. Situasi ini disebut **merge conflict** (konflik penggabungan). Git akan berhenti dan meminta kamu memutuskan versi mana yang benar.

[SAJIKAN: callout — Jangan khawatir dulu: Merge conflict terdengar menakutkan bagi pemula, tapi sebenarnya wajar dan bisa diselesaikan. Kamu akan mempelajari cara menangani merge conflict secara lengkap di Modul 5, karena konflik paling sering muncul saat kerja tim. Untuk sekarang, cukup tahu bahwa konflik itu ada dan bisa diatasi.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['4.8']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.9']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa itu merge?',
            'options' => ['Proses menyatukan perubahan dari satu branch ke branch lain', 'Proses menghapus sebuah branch', 'Proses membuat repository baru'],
            'correct_answer' => 'Proses menyatukan perubahan dari satu branch ke branch lain',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.9']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Merge conflict terjadi ketika...',
            'options' => ['Dua branch mengubah bagian yang sama dari file yang sama secara berbeda', 'Kamu lupa commit', 'Repository terlalu besar'],
            'correct_answer' => 'Dua branch mengubah bagian yang sama dari file yang sama secara berbeda',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.9']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Untuk menggabungkan branch fitur-baru ke main, langkah pertamanya adalah...',
            'options' => ['Pindah dulu ke branch main (git switch main)', 'Hapus branch fitur-baru', 'Buat repository baru'],
            'correct_answer' => 'Pindah dulu ke branch main (git switch main)',
            'sort_order' => 3,
        ]);

        $this->units['4.10'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 10,
            'title' => 'Ringkasan Command Git',
            'content' => <<<'TEXT'
Unit ini adalah cheat sheet (contekan) yang bisa kamu buka kapan saja. Berikut rangkuman semua perintah Git yang sudah kamu pelajari di Modul 4, beserta fungsinya.

[SAJIKAN: tabel perbandingan — tabel besar dua kolom "Perintah" dan "Fungsi/Tujuan", ditata rapi sebagai referensi cepat]

| Perintah | Fungsi / Tujuan |
|---|---|
| `git --version` | Mengecek apakah Git sudah terpasang dan versinya |
| `git config --global user.name "Nama"` | Mengatur nama pembuat commit |
| `git config --global user.email "email"` | Mengatur email pembuat commit |
| `git init` | Menjadikan folder sebagai repository Git |
| `git status` | Melihat keadaan terkini file (berubah, staged, dll) |
| `git add nama_file` | Memasukkan file tertentu ke staging area |
| `git add .` | Memasukkan semua perubahan ke staging area |
| `git commit -m "pesan"` | Menyimpan perubahan sebagai satu titik dalam riwayat |
| `git log` | Melihat riwayat semua commit |
| `git branch` | Melihat daftar branch |
| `git branch nama-branch` | Membuat branch baru |
| `git switch nama-branch` | Berpindah ke branch tertentu |
| `git merge nama-branch` | Menggabungkan branch ke branch tempat kamu berada |

[SAJIKAN: callout — Cara pakai: Simpan atau tandai halaman ini. Saat kamu lupa perintah tertentu di tengah praktik, kembali ke sini. Wajar untuk sering melihat contekan di awal, lama-lama akan hafal dengan sendirinya karena terbiasa.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'none',
            'prerequisite_unit_id' => $this->units['4.9']->id,
        ]);

        // Modul 4 has no Checkpoint of its own — see class docblock. Its checklist
        // is folded into Modul 5's Checkpoint below.
    }

    private function seedModule5(): void
    {
        $module = Module::create([
            'order_number' => 5,
            'title' => 'Kolaborasi dengan GitHub',
            'description' => 'Memahami dan mempraktikkan GitHub sebagai platform kolaborasi, dengan pendalaman khusus pada cara kerja merge dan penanganan konflik dalam konteks kerja tim nyata.',
            'level_number' => 3,
        ]);

        $this->units['5.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Apa Itu GitHub dan Bedanya dengan Git?',
            'content' => <<<'TEXT'
Ini unit yang meluruskan kebingungan paling umum di kalangan pemula: perbedaan Git dan GitHub. Namanya mirip, tapi keduanya adalah hal yang berbeda.

**Git adalah alat.** Seperti yang kamu pelajari di Modul 4, Git adalah sistem kontrol versi yang berjalan di komputermu. Git bekerja secara lokal, artinya semua riwayat proyek tersimpan di komputermu sendiri.

**GitHub adalah platform online.** GitHub adalah layanan berbasis web tempat kamu bisa menyimpan repository Git di internet (di server milik GitHub), sehingga bisa diakses dari mana saja dan dibagikan ke orang lain. GitHub membangun banyak fitur kolaborasi di atas Git.

[SAJIKAN: tabel perbandingan — Git vs GitHub, kolom: "Apa itu", "Di mana", "Fungsi utama"]

| | Git | GitHub |
|---|---|---|
| Apa itu | Alat kontrol versi | Platform online penyimpan repository |
| Di mana | Di komputermu (lokal) | Di internet (server GitHub) |
| Fungsi utama | Mencatat riwayat perubahan | Menyimpan, membagikan, dan berkolaborasi |

**Kenapa perlu keduanya?** Git mengurus pencatatan riwayat di komputermu. GitHub membuat riwayat itu bisa disimpan aman di internet, diakses dari perangkat lain, dan yang terpenting, dikerjakan bersama tim. Tanpa GitHub, proyek Git-mu hanya ada di satu komputer. Dengan GitHub, proyek itu bisa hidup di internet dan dikerjakan banyak orang.

[SAJIKAN: callout — Analogi: Git seperti kemampuan menulis dan menyimpan dokumen di komputermu. GitHub seperti layanan penyimpanan awan (mirip Google Drive) khusus untuk kode, yang juga punya fitur untuk mengerjakan dokumen bersama-sama. Selain GitHub, ada platform serupa seperti GitLab dan Bitbucket, tapi GitHub yang paling populer.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Manakah pernyataan yang benar?',
            'options' => ['Git adalah alat lokal, GitHub adalah platform online', 'Git dan GitHub adalah hal yang sama persis', 'GitHub adalah alat lokal, Git adalah platform online'],
            'correct_answer' => 'Git adalah alat lokal, GitHub adalah platform online',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Riwayat proyek Git tersimpan di mana secara default (sebelum ada GitHub)?',
            'options' => ['Di komputermu sendiri (lokal)', 'Otomatis di internet', 'Di email kamu'],
            'correct_answer' => 'Di komputermu sendiri (lokal)',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa fungsi utama GitHub?',
            'options' => ['Menyimpan, membagikan, dan berkolaborasi', 'Mencatat riwayat perubahan secara lokal saja', 'Menjadi bahasa pemrograman baru'],
            'correct_answer' => 'Menyimpan, membagikan, dan berkolaborasi',
            'sort_order' => 3,
        ]);

        $this->units['5.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Membuat Akun dan Repository di GitHub',
            'content' => <<<'TEXT'
Waktunya masuk ke GitHub secara nyata. Unit ini memandumu membuat akun dan repository pertama.

**Langkah 1: Membuat akun GitHub.**

[SAJIKAN: diagram alur — langkah buat akun: 1. Buka github.com, 2. Klik Sign Up, 3. Isi email, password, dan username, 4. Verifikasi sesuai instruksi, 5. Akun siap]

[SAJIKAN: callout — Tips memilih username: Pilih username yang profesional dan mudah diingat, karena ini akan menjadi bagian dari identitasmu sebagai developer dan muncul di alamat portofolio serta proyekmu. Hindari nama yang terlalu main-main, karena akun ini bisa kamu tunjukkan ke calon pemberi kerja nanti.]

**Langkah 2: Membuat repository di GitHub.** Setelah punya akun, kamu bisa membuat repository baru langsung di website GitHub:

[SAJIKAN: diagram alur — langkah buat repo: 1. Klik tombol "New" atau tanda "+", 2. Beri nama repository, 3. Pilih Public (bisa dilihat umum) atau Private (hanya kamu), 4. Klik Create repository, 5. Repository online siap]

**Public vs Private.** Repository Public bisa dilihat siapa saja di internet, cocok untuk karya yang ingin kamu tunjukkan (seperti portofolio). Repository Private hanya bisa diakses olehmu dan orang yang kamu izinkan, cocok untuk proyek yang belum siap dibagikan.

[SAJIKAN: callout — Catatan: Untuk latihan dan portofolio di kurikulum ini, disarankan memakai repository Public supaya karyamu bisa dilihat dan menjadi bukti kemampuanmu. Tidak ada data sensitif dalam proyek belajar ini, jadi aman untuk publik.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['5.1']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.2']->id,
            'question_type' => 'practice',
            'question_text' => 'Buat akun GitHub dan satu repository pertama. "Apakah akun GitHub dan repository pertamamu berhasil dibuat? Tuliskan username GitHub-mu." (input teks). Jika butuh bantuan, arahkan ke forum.',
            'sort_order' => 1,
        ]);

        $this->units['5.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Push, Pull, Clone: Menghubungkan Lokal dan GitHub',
            'content' => <<<'TEXT'
Sekarang kamu punya repository di komputer (lokal) dan repository di GitHub (online). Bagaimana menghubungkan keduanya? Lewat tiga perintah kunci: push, pull, dan clone.

[SAJIKAN: diagram alur — dua sisi: "LOKAL (komputermu)" dan "GITHUB (online)". Panah dari lokal ke GitHub berlabel "push (unggah)", panah dari GitHub ke lokal berlabel "pull (unduh perubahan)" dan "clone (salin seluruh repo)"]

**`git remote`: menghubungkan lokal ke GitHub.** Sebelum bisa mengunggah, repository lokalmu perlu tahu ke repository GitHub mana ia terhubung. Ini diatur lewat perintah remote (GitHub menyediakan alamat repo-mu saat kamu membuatnya):

[SAJIKAN: blok kode:
git remote add origin https://github.com/username/nama-repo.git]

Kata `origin` adalah nama panggilan standar untuk repository GitHub yang terhubung.

**`git push`: mengunggah ke GitHub.** Perintah push mengirim commit dari komputermu ke GitHub, sehingga riwayat di GitHub ikut terbarui:

[SAJIKAN: blok kode:
git push origin main]

**`git pull`: mengunduh perubahan dari GitHub.** Kalau ada perubahan di GitHub (misalnya dibuat rekan setim), perintah pull menariknya ke komputermu supaya versimu ikut terbarui:

[SAJIKAN: blok kode:
git pull origin main]

**`git clone`: menyalin seluruh repository.** Kalau kamu ingin menyalin repository yang sudah ada di GitHub ke komputermu (misalnya proyek tim atau proyek orang lain), gunakan clone:

[SAJIKAN: blok kode:
git clone https://github.com/username/nama-repo.git]

[SAJIKAN: kartu — ringkasan tiga aksi: Push = unggah ke GitHub, Pull = unduh perubahan terbaru, Clone = salin seluruh repo pertama kali]

[SAJIKAN: callout — Cara mengingat arah: Push berarti mendorong keluar (dari komputer ke GitHub). Pull berarti menarik masuk (dari GitHub ke komputer). Clone berarti menyalin utuh sebuah repo ke komputer untuk pertama kali.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['5.2']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.3']->id,
            'question_type' => 'practice',
            'question_text' => 'Hubungkan repository lokal latihanmu ke repository GitHub yang kamu buat, lalu push. Tuliskan urutan perintah yang kamu jalankan.',
            'sort_order' => 1,
        ]);

        $this->units['5.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Pull Request: Cara Tim Mereview Kode',
            'content' => <<<'TEXT'
Pull Request adalah salah satu fitur paling khas dari GitHub dan jantung dari kolaborasi tim. Mari pahami baik-baik.

**Apa itu Pull Request (PR)?** Pull Request adalah cara mengusulkan perubahan ke sebuah repository, sekaligus meminta orang lain memeriksa perubahan itu sebelum digabungkan ke branch utama. Namanya "pull request" karena kamu meminta (request) agar perubahanmu ditarik (pull) masuk ke branch utama.

**Kenapa tidak langsung push ke branch utama?** Di proyek tim, langsung menggabungkan perubahan ke branch utama tanpa diperiksa berisiko. Bisa jadi ada kesalahan yang belum ketahuan, atau perubahan itu bertabrakan dengan pekerjaan orang lain. Pull Request memberi kesempatan tim memeriksa dulu sebelum perubahan resmi masuk.

**Alur Pull Request:**

[SAJIKAN: diagram alur — alur PR: 1. Buat branch baru untuk pekerjaanmu, 2. Kerjakan dan commit di branch itu, 3. Push branch ke GitHub, 4. Buka Pull Request di GitHub, 5. Rekan tim mereview dan memberi komentar, 6. Setelah disetujui, PR di-merge ke branch utama]

[SAJIKAN: callout — Poin penting: Pull Request bukan sekadar menggabungkan kode, tapi juga ruang diskusi. Di dalam PR, tim bisa berkomentar, mengusulkan perbaikan, dan berdiskusi soal perubahan sebelum disetujui. Inilah yang membuat kualitas kode tim tetap terjaga.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['5.3']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.4']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa itu Pull Request?',
            'options' => ['Cara mengusulkan perubahan sekaligus meminta orang lain memeriksa sebelum digabungkan', 'Perintah untuk menghapus branch', 'Cara membuat akun GitHub baru'],
            'correct_answer' => 'Cara mengusulkan perubahan sekaligus meminta orang lain memeriksa sebelum digabungkan',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.4']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Kenapa tim tidak langsung push ke branch utama?',
            'options' => ['Karena berisiko ada kesalahan atau bentrok yang belum diperiksa', 'Karena push ke branch utama dilarang secara teknis oleh Git', 'Karena tidak ada alasan khusus'],
            'correct_answer' => 'Karena berisiko ada kesalahan atau bentrok yang belum diperiksa',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.4']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Selain menggabungkan kode, Pull Request juga berfungsi sebagai apa?',
            'options' => ['Ruang diskusi dan review', 'Tempat menyimpan password', 'Pengganti terminal'],
            'correct_answer' => 'Ruang diskusi dan review',
            'sort_order' => 3,
        ]);

        $this->units['5.5'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 5,
            'title' => 'Issues dan Project Board di GitHub',
            'content' => <<<'TEXT'
GitHub bukan hanya tempat menyimpan kode, tapi juga alat mengelola pekerjaan tim. Dua fitur pentingnya adalah Issues dan Project Board.

**Apa itu Issues?** Issues (isu) adalah fitur untuk mencatat tugas, ide, atau masalah (bug) yang perlu dikerjakan dalam sebuah proyek. Setiap issue seperti satu tiket pekerjaan. Misalnya, "Tombol login tidak berfungsi" bisa dicatat sebagai satu issue supaya tidak terlupa dan bisa ditugaskan ke seseorang.

[SAJIKAN: kartu — contoh isi sebuah Issue: judul masalah, deskripsi, siapa yang ditugaskan, dan label seperti "bug" atau "fitur baru"]

**Apa itu Project Board?** Project Board (papan proyek) adalah papan visual untuk melacak kemajuan pekerjaan, biasanya berbentuk kolom-kolom seperti "To Do" (akan dikerjakan), "In Progress" (sedang dikerjakan), dan "Done" (selesai). Tugas-tugas dipindahkan antar kolom seiring kemajuannya.

[SAJIKAN: infografis — papan dengan tiga kolom: "To Do", "In Progress", "Done", masing-masing berisi kartu tugas, menunjukkan alur tugas berpindah dari kiri ke kanan]

[SAJIKAN: callout — Kaitan dengan modul lain: Konsep Issues dan Project Board ini akan kamu pelajari lagi kaitannya dengan pembagian tugas tim di Modul 8. Untuk sekarang, cukup pahami bahwa GitHub membantu tim tidak hanya menyimpan kode, tapi juga mengatur siapa mengerjakan apa.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_matching',
            'prerequisite_unit_id' => $this->units['5.2']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.5']->id,
            'question_type' => 'matching',
            'question_text' => 'Cocokkan fitur (Issues, Project Board, kolom To Do/In Progress/Done) dengan fungsinya.',
            'options' => [
                'pairs' => [
                    ['left' => 'Issues', 'right' => 'Mencatat tugas, ide, atau masalah (bug) yang perlu dikerjakan'],
                    ['left' => 'Project Board', 'right' => 'Papan visual untuk melacak kemajuan pekerjaan'],
                    ['left' => 'Kolom To Do/In Progress/Done', 'right' => 'Menunjukkan tahap kemajuan tiap tugas'],
                ],
            ],
            'correct_answer' => [
                'Issues' => 'Mencatat tugas, ide, atau masalah (bug) yang perlu dikerjakan',
                'Project Board' => 'Papan visual untuk melacak kemajuan pekerjaan',
                'Kolom To Do/In Progress/Done' => 'Menunjukkan tahap kemajuan tiap tugas',
            ],
            'sort_order' => 1,
        ]);

        $this->units['5.6'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 6,
            'title' => 'Workflow Kolaborasi Tim: Dari Tugas Sampai Merge',
            'content' => <<<'TEXT'
Sekarang kita satukan semua yang sudah kamu pelajari menjadi satu alur kerja tim yang utuh. Inilah gambaran bagaimana developer bekerja bersama sehari-hari.

Bayangkan kamu anggota tim yang mengerjakan sebuah website bersama. Berikut alur lengkapnya dari mendapat tugas sampai pekerjaanmu masuk ke proyek utama:

[SAJIKAN: diagram alur — alur kerja tim lengkap dengan langkah bernomor:
1. Ambil tugas (dari Issue di Project Board)
2. Buat branch baru untuk tugas itu
3. Kerjakan tugas dan buat commit di branch tersebut
4. Push branch ke GitHub
5. Buka Pull Request
6. Rekan tim mereview dan memberi masukan
7. Perbaiki jika ada masukan
8. Setelah disetujui, PR di-merge ke branch utama
9. Branch yang selesai bisa dihapus, tugas ditandai Done]

Perhatikan bahwa alur ini menggabungkan hampir semua yang kamu pelajari: branch (Modul 4), commit (Modul 4), push (Unit 5.3), Pull Request (Unit 5.4), Issue dan Project Board (Unit 5.5), dan merge (yang akan diperdalam di unit berikutnya).

[SAJIKAN: callout — Kenapa alur ini penting: Alur inilah yang membedakan "belajar coding sendiri" dengan "siap bekerja dalam tim". Menguasai alur ini membuatmu bisa langsung nyambung saat bergabung ke proyek tim nyata, termasuk proyek di divisi kita nanti.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'quiz_ordering',
            'prerequisite_unit_id' => $this->units['5.4']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.6']->id,
            'question_type' => 'ordering',
            'question_text' => 'Susun langkah-langkah workflow kolaborasi tim dalam urutan yang benar.',
            'options' => [
                'Ambil tugas (dari Issue di Project Board)',
                'Buat branch baru untuk tugas itu',
                'Kerjakan tugas dan buat commit di branch tersebut',
                'Push branch ke GitHub',
                'Buka Pull Request',
                'Rekan tim mereview dan memberi masukan',
                'Perbaiki jika ada masukan',
                'Setelah disetujui, PR di-merge ke branch utama',
                'Branch yang selesai bisa dihapus, tugas ditandai Done',
            ],
            'correct_answer' => [
                'Ambil tugas (dari Issue di Project Board)',
                'Buat branch baru untuk tugas itu',
                'Kerjakan tugas dan buat commit di branch tersebut',
                'Push branch ke GitHub',
                'Buka Pull Request',
                'Rekan tim mereview dan memberi masukan',
                'Perbaiki jika ada masukan',
                'Setelah disetujui, PR di-merge ke branch utama',
                'Branch yang selesai bisa dihapus, tugas ditandai Done',
            ],
            'sort_order' => 1,
        ]);

        $this->units['5.7'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 7,
            'title' => 'Deep Dive: Merge dalam Konteks Proyek Tim',
            'content' => <<<'TEXT'
Di Modul 4 kamu sudah berkenalan dengan merge secara dasar. Sekarang kita perdalam merge dalam situasi yang paling sering terjadi: kerja tim. Mari kita ikuti sebuah cerita konkret.

**Skenario nyata.** Bayangkan tim kalian bertiga sedang membangun sebuah website toko online. Kalian membagi tugas:

[SAJIKAN: kartu — tiga anggota tim dan tugasnya: Ani mengerjakan halaman utama, Budi mengerjakan halaman keranjang belanja, Cici mengerjakan halaman login]

Kalian semua bekerja dari branch utama (main) yang sama, tapi masing-masing membuat branch sendiri untuk tugasnya. Ani di branch `halaman-utama`, Budi di branch `keranjang`, Cici di branch `login`. Mereka bekerja bersamaan tanpa saling mengganggu, karena tiap orang di jalur terpisah.

**Di mana peran merge?** Merge berperan saat pekerjaan tiap orang sudah selesai dan siap disatukan ke branch utama. Misalnya, Ani menyelesaikan halaman utama lebih dulu. Ani membuka Pull Request, timnya mereview, lalu branch `halaman-utama` di-merge ke main. Sekarang main sudah berisi halaman utama.

Berikutnya Budi menyelesaikan keranjang. Saat branch `keranjang` di-merge ke main, Git menggabungkan pekerjaan Budi dengan isi main yang kini sudah termasuk pekerjaan Ani. Begitu seterusnya sampai semua bagian menyatu menjadi satu website utuh.

[SAJIKAN: diagram alur — branch utama (main) di tengah, tiga branch (halaman-utama, keranjang, login) bekerja paralel, lalu satu per satu di-merge kembali ke main secara berurutan, sampai main berisi seluruh pekerjaan]

**Kapan merge dilakukan dan siapa yang melakukan?** Merge biasanya dilakukan setelah Pull Request disetujui. Di banyak tim, yang menekan tombol merge adalah pemimpin tim atau reviewer, sebagai tanda bahwa perubahan sudah diperiksa dan layak masuk. Ini menjaga agar branch utama selalu berisi kode yang sudah terverifikasi.

[SAJIKAN: callout — Inti pemahaman: Merge adalah momen menyatukan hasil kerja yang tadinya terpisah. Dalam tim, merge terjadi berulang kali seiring tiap bagian pekerjaan selesai, sedikit demi sedikit membangun produk utuh dari potongan-potongan pekerjaan tiap orang.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['5.6']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.7']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Dalam skenario tim Ani, Budi, dan Cici, kapan merge biasanya dilakukan?',
            'options' => ['Setelah Pull Request disetujui', 'Sebelum tugas dimulai', 'Tidak pernah'],
            'correct_answer' => 'Setelah Pull Request disetujui',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.7']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Di banyak tim, siapa yang biasanya menekan tombol merge?',
            'options' => ['Pemimpin tim atau reviewer', 'Siapa saja secara acak', 'Tidak ada yang menekan, otomatis'],
            'correct_answer' => 'Pemimpin tim atau reviewer',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.7']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Kenapa Ani, Budi, dan Cici masing-masing membuat branch sendiri?',
            'options' => ['Supaya bisa bekerja bersamaan tanpa saling mengganggu', 'Karena diwajibkan GitHub', 'Supaya kodenya lebih pendek'],
            'correct_answer' => 'Supaya bisa bekerja bersamaan tanpa saling mengganggu',
            'sort_order' => 3,
        ]);

        $mixedUnit58 = Unit::create([
            'module_id' => $module->id,
            'order_number' => 8,
            'title' => 'Menangani Merge Conflict: Ketika Kerjaan Dua Orang Bertabrakan',
            'content' => <<<'TEXT'
Ini unit yang sering ditakuti pemula, tapi setelah paham, kamu akan tahu bahwa merge conflict adalah hal biasa yang bisa diselesaikan dengan tenang.

**Apa itu merge conflict?** Merge conflict (konflik penggabungan) terjadi saat Git mencoba menggabungkan dua branch, tapi menemukan bahwa bagian yang sama dari file yang sama diubah secara berbeda di kedua branch. Git tidak bisa menebak versi mana yang benar, jadi ia berhenti dan meminta manusia yang memutuskan.

**Kenapa terjadi? Contoh konkret.** Kembali ke tim tadi. Misalkan Ani dan Budi tanpa sengaja sama-sama mengubah baris judul di file yang sama. Ani mengubahnya menjadi "Toko Online Terbaik", sementara Budi mengubahnya menjadi "Belanja Mudah dan Cepat". Saat kedua branch di-merge, Git bingung: judul mana yang harus dipakai? Inilah konflik.

[SAJIKAN: diagram alur — dua branch mengubah baris yang sama secara berbeda, bertemu di titik merge, muncul tanda "CONFLICT", lalu manusia memilih versi final]

**Bagaimana Git menandai konflik?** Saat konflik terjadi, Git menandai bagian yang bermasalah langsung di dalam file, dengan tanda khusus:

[SAJIKAN: blok kode — contoh tanda konflik di dalam file:
<<<<<<< HEAD
Toko Online Terbaik
=======
Belanja Mudah dan Cepat
>>>>>>> keranjang]

Penjelasan tandanya:
- Bagian antara `<<<<<<< HEAD` dan `=======` adalah versi dari branch tempat kamu berada sekarang.
- Bagian antara `=======` dan `>>>>>>>` adalah versi dari branch yang sedang digabungkan.

**Langkah menyelesaikan konflik:**

[SAJIKAN: diagram alur — langkah resolusi: 1. Buka file yang konflik, 2. Cari tanda konflik, 3. Putuskan versi final (pilih satu, atau gabungkan keduanya), 4. Hapus semua tanda konflik (<<<, ===, >>>), 5. Simpan file, 6. git add dan git commit untuk menyelesaikan merge]

**Tips mencegah konflik:**

[SAJIKAN: kartu — tips mencegah merge conflict]

- Sering-sering menarik perubahan terbaru (git pull) supaya versimu tidak terlalu jauh tertinggal dari tim.
- Bagi tugas dengan jelas supaya dua orang tidak mengerjakan bagian yang sama bersamaan.
- Buat commit dan merge secara teratur, jangan menumpuk banyak perubahan besar sekaligus.

[SAJIKAN: callout — Menenangkan: Merge conflict bukan pertanda kamu melakukan kesalahan. Ini bagian normal dari kerja tim. Yang penting adalah tahu cara membacanya dan menyelesaikannya dengan tenang, bukan panik. Kalau ragu, komunikasikan dengan rekan yang bagiannya bertabrakan denganmu untuk memutuskan versi terbaik bersama.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            // Mixed evaluation type in the document ("Kuis pilihan dan esai singkat").
            // Stored as quiz_multiple_choice per user confirmation — the 2 MC questions
            // below are interactive; the essay question is stored as a third
            // UnitEvaluation row for completeness but the 2.2 UnitEvaluation UI does
            // not yet render a combined quiz+essay experience for a single unit.
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['5.7']->id,
        ]);
        $this->units['5.8'] = $mixedUnit58;

        UnitEvaluation::create([
            'unit_id' => $mixedUnit58->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Merge conflict terjadi ketika...',
            'options' => ['Bagian yang sama dari file yang sama diubah secara berbeda di dua branch', 'Kamu lupa membuat branch', 'Repository terlalu besar'],
            'correct_answer' => 'Bagian yang sama dari file yang sama diubah secara berbeda di dua branch',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $mixedUnit58->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Bagian di antara `<<<<<<< HEAD` dan `=======` adalah versi dari branch mana?',
            'options' => ['Branch tempat kamu berada sekarang', 'Branch yang sedang digabungkan', 'Branch yang sudah dihapus'],
            'correct_answer' => 'Branch tempat kamu berada sekarang',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $mixedUnit58->id,
            'question_type' => 'essay',
            'question_text' => 'Dengan bahasamu sendiri, kenapa merge conflict bisa terjadi saat dua orang bekerja dalam satu proyek?',
            'sort_order' => 3,
        ]);

        $this->units['5.9'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 9,
            'title' => 'Ringkasan Command GitHub',
            'content' => <<<'TEXT'
Seperti di Modul 4, unit ini adalah cheat sheet untuk perintah-perintah yang berkaitan dengan menghubungkan Git lokal dengan GitHub. Simpan halaman ini sebagai referensi cepat.

[SAJIKAN: tabel perbandingan — tabel besar dua kolom "Perintah" dan "Fungsi/Tujuan"]

| Perintah | Fungsi / Tujuan |
|---|---|
| `git remote add origin [url]` | Menghubungkan repository lokal ke repository GitHub |
| `git remote -v` | Melihat repository GitHub mana yang terhubung |
| `git push origin main` | Mengunggah commit dari lokal ke GitHub |
| `git push origin nama-branch` | Mengunggah branch tertentu ke GitHub |
| `git pull origin main` | Mengunduh perubahan terbaru dari GitHub ke lokal |
| `git clone [url]` | Menyalin seluruh repository GitHub ke komputer |
| `git fetch` | Mengambil informasi perubahan dari GitHub tanpa langsung menggabungkan |

[SAJIKAN: callout — Catatan: `git fetch` dan `git pull` mirip tapi berbeda. Fetch hanya mengambil informasi perubahan tanpa menggabungkannya ke pekerjaanmu, sedangkan pull mengambil sekaligus menggabungkan. Sebagai pemula, kamu akan lebih sering memakai pull. Fetch berguna saat kamu ingin melihat dulu perubahan sebelum menggabungkannya.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'none',
            'prerequisite_unit_id' => $this->units['5.8']->id,
        ]);

        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                // Modul 4 checklist, folded in per user confirmation.
                'Git sudah terpasang di perangkatku',
                'Aku memahami konsep dasar cara kerja Git (snapshot dan riwayat)',
                'Aku bisa melakukan `git init`, `git add`, `git commit`, dan `git log`',
                'Aku memahami konsep branching dan merging',
                // Modul 5 checklist.
                'Akun GitHub telah dibuat',
                'Aku bisa melakukan push, pull, dan clone',
                'Aku memahami alur Pull Request',
                'Aku memahami cara kerja merge dan cara menangani merge conflict',
            ],
            'intermezo_questions' => [
                'Kuis pemahaman Git dan GitHub. Kuis ini menggabungkan materi Modul 4 dan Modul 5 untuk memastikan kamu sudah memahami cara kerja Git dan GitHub secara konsep maupun praktik. Kuis bersifat pemahaman, bukan ujian menghakimi.',
                'Perbedaan Git dan GitHub',
                'Fungsi perintah dasar (init, add, commit, push, pull)',
                'Konsep branch dan merge',
                'Kapan dan kenapa merge conflict terjadi',
                'Alur kerja kolaborasi tim dari tugas sampai merge',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Situs Resmi Git',
            'url' => 'https://git-scm.com',
            'source_name' => 'Situs resmi Git, tempat mengunduh Git sekaligus rujukan resmi. Bagian dokumentasinya adalah sumber paling tepercaya soal Git.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Pro Git Book',
            'url' => 'https://git-scm.com/book/en/v2',
            'source_name' => 'Buku lengkap tentang Git yang bisa dibaca gratis secara online. Bab-bab awalnya menjelaskan konsep dasar Git yang sejalan dengan modul ini, cocok untuk pendalaman.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'W3Schools Git Tutorial',
            'url' => 'https://www.w3schools.com/git/',
            'source_name' => 'Tutorial Git dengan gaya sederhana dan bertahap, cocok untuk pemula yang ingin latihan ulang perintah dasar.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'GitHub Docs',
            'url' => 'https://docs.github.com',
            'source_name' => 'Dokumentasi resmi GitHub. Menjelaskan cara membuat repository, Pull Request, Issues, dan fitur kolaborasi lainnya langsung dari sumbernya.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Pro Git Book, Bab GitHub',
            'url' => 'https://git-scm.com/book/en/v2',
            'source_name' => 'Bagian khusus tentang GitHub di buku Pro Git, menjelaskan cara berkontribusi dan berkolaborasi lewat GitHub.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'W3Schools Git and GitHub Tutorial',
            'url' => 'https://www.w3schools.com/git/',
            'source_name' => 'Tutorial bertahap yang mencakup penggunaan Git bersama GitHub, cocok untuk latihan ulang perintah push, pull, dan clone.',
        ]);
    }

    private function seedModule6(): void
    {
        $module = Module::create([
            'order_number' => 6,
            'title' => 'Dasar-Dasar Frontend',
            'description' => 'Memahami dan mempraktikkan HTML dan CSS dasar sebagai fondasi membangun tampilan website. Cukup untuk bisa membuat halaman statis sederhana. Ini modul praktik pertama tempat kamu benar-benar membuat sesuatu yang bisa dilihat.',
            'level_number' => 4,
        ]);

        $this->units['6.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Apa Itu HTML? Struktur Dasar Halaman Web',
            'content' => <<<'TEXT'
Selamat, kamu sampai di modul tempat kamu akhirnya membuat halaman web sungguhan. Kita mulai dari HTML, kerangka setiap website.

**Apa itu HTML?** HTML (HyperText Markup Language) adalah bahasa penanda yang dipakai untuk menyusun struktur dan isi sebuah halaman web. Ingat dari Modul 3: HTML bukan bahasa pemrograman, melainkan bahasa penanda. Tugasnya menandai bagian-bagian konten, misalnya "ini judul", "ini paragraf", "ini gambar".

**Tiga istilah kunci: tag, elemen, dan atribut.**

[SAJIKAN: kartu — tiga istilah dasar HTML dengan contoh]

- **Tag** adalah penanda yang ditulis di antara tanda `<` dan `>`. Umumnya tag berpasangan: tag pembuka dan tag penutup. Contoh: `<p>` (pembuka paragraf) dan `</p>` (penutup paragraf).
- **Elemen** adalah keseluruhan bagian, dari tag pembuka, isi, sampai tag penutup. Contoh: `<p>Halo dunia</p>` adalah satu elemen paragraf.
- **Atribut** adalah informasi tambahan di dalam tag pembuka. Contoh: pada `<a href="https://google.com">`, bagian `href` adalah atribut yang menyebutkan alamat tujuan link.

**Struktur minimal sebuah halaman HTML.** Setiap halaman HTML punya kerangka dasar yang sama:

[SAJIKAN: blok kode — struktur dasar HTML:
<!DOCTYPE html>
<html>
<head>
    <title>Judul Halaman</title>
</head>
<body>
    <p>Isi halaman muncul di sini</p>
</body>
</html>]

[SAJIKAN: kartu — penjelasan tiap bagian kerangka]

- **`<!DOCTYPE html>`** memberi tahu browser bahwa ini dokumen HTML modern.
- **`<html>`** adalah pembungkus seluruh isi halaman.
- **`<head>`** berisi informasi tentang halaman yang tidak tampil langsung di layar, seperti judul di tab browser dan tautan ke file CSS.
- **`<body>`** berisi semua konten yang benar-benar terlihat pengguna: teks, gambar, tombol, dan lainnya.

[SAJIKAN: callout — Praktik pertamamu: Buka VS Code, buat file baru bernama `index.html`, ketik ulang kerangka di atas, simpan, lalu buka file itu dengan browser. Kamu akan melihat tulisan "Isi halaman muncul di sini". Selamat, itu halaman web pertamamu.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['3.2']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['6.1']->id,
            'question_type' => 'practice',
            'question_text' => 'Buat file `index.html` dengan struktur dasar HTML, isi bagian body dengan satu paragraf berisi namamu, lalu buka di browser. Tuliskan apa yang muncul di layar dan di tab browser.',
            'sort_order' => 1,
        ]);

        $this->units['6.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Elemen-Elemen HTML yang Paling Sering Dipakai',
            'content' => <<<'TEXT'
Sekarang kamu punya kerangka. Waktunya mengisinya dengan elemen-elemen yang paling sering dipakai. Ini seperti belajar kosakata dasar sebuah bahasa.

[SAJIKAN: tabel perbandingan — tabel tiga kolom "Elemen", "Fungsi", "Contoh penulisan"]

| Elemen | Fungsi | Contoh Penulisan |
|---|---|---|
| Heading | Judul dan subjudul, dari `<h1>` (terbesar) sampai `<h6>` (terkecil) | `<h1>Judul Utama</h1>` |
| Paragraf | Blok teks biasa | `<p>Ini paragraf.</p>` |
| Link | Tautan ke halaman lain | `<a href="url">Klik di sini</a>` |
| Gambar | Menampilkan gambar | `<img src="foto.jpg" alt="deskripsi">` |
| List (daftar) | Daftar berurutan atau tidak | `<ul><li>Item</li></ul>` |
| Div | Wadah/pengelompok bagian | `<div>Sekelompok konten</div>` |
| Span | Penanda bagian kecil dalam teks | `<span>bagian teks</span>` |

**Penjelasan singkat yang perlu diperhatikan:**

[SAJIKAN: kartu — catatan penting tiap elemen]

- **Heading** punya tingkatan. Gunakan `<h1>` untuk judul terpenting halaman, dan turun sesuai tingkat kepentingannya. Ini bukan soal ukuran saja, tapi soal struktur.
- **Link** memakai atribut `href` untuk menentukan tujuan. Tanpa href, link tidak menuju ke mana-mana.
- **Gambar** memakai atribut `src` untuk sumber gambar, dan `alt` untuk teks pengganti bila gambar gagal dimuat. Atribut `alt` juga penting untuk aksesibilitas, membantu orang yang memakai pembaca layar.
- **Div dan span** sama-sama pembungkus, bedanya div untuk blok besar (mengambil satu baris penuh), span untuk bagian kecil di dalam teks.

[SAJIKAN: callout — Tips belajar: Jangan hafal semua sekaligus. Coba tulis satu per satu di file `index.html`-mu, simpan, dan lihat hasilnya di browser. Belajar HTML paling efektif dengan langsung mencoba, bukan menghafal tabel.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['6.1']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['6.2']->id,
            'question_type' => 'practice',
            'question_text' => 'Tambahkan ke halamanmu: satu heading, dua paragraf, satu link ke website favoritmu, dan satu daftar berisi tiga hal yang ingin kamu pelajari. Tuliskan kode HTML yang kamu buat.',
            'sort_order' => 1,
        ]);

        $this->units['6.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Apa Itu CSS? Memberi Gaya pada Halaman',
            'content' => <<<'TEXT'
Halamanmu sekarang punya isi, tapi masih polos: teks hitam, latar putih, tanpa gaya. Di sinilah CSS masuk. CSS mengubah halaman polos menjadi menarik.

**Apa itu CSS?** CSS (Cascading Style Sheets) adalah bahasa penata gaya yang mengatur tampilan halaman web: warna, ukuran, jarak, jenis huruf, dan tata letak. Ingat dari Modul 3: CSS bukan bahasa pemrograman, melainkan bahasa penata gaya. HTML menyusun, CSS mempercantik.

**Tiga cara menghubungkan CSS ke HTML:**

[SAJIKAN: tabel perbandingan — tiga cara: Inline, Internal, External, dengan kolom "Cara" dan "Kapan dipakai"]

- **Inline:** menulis gaya langsung di dalam tag HTML lewat atribut `style`. Cepat tapi berantakan kalau banyak. Contoh: `<p style="color: red;">`.
- **Internal:** menulis semua gaya dalam satu blok `<style>` di bagian head. Cocok untuk satu halaman.
- **External:** menulis gaya di file terpisah berekstensi `.css`, lalu menghubungkannya ke HTML. Ini cara paling rapi dan paling disarankan, karena satu file CSS bisa dipakai banyak halaman sekaligus.

**Cara kerja CSS: selector dan properti.** CSS bekerja dengan memilih elemen (selector) lalu memberinya aturan gaya (properti dan nilai):

[SAJIKAN: blok kode — contoh aturan CSS:
p {
    color: blue;
    font-size: 18px;
}]

Penjelasan: `p` adalah selector (memilih semua elemen paragraf). Di dalam kurung kurawal ada properti dan nilainya: `color: blue` mengubah warna teks jadi biru, `font-size: 18px` mengatur ukuran huruf.

[SAJIKAN: callout — Praktik: Buat file `style.css`, tulis aturan untuk mengubah warna paragrafmu, lalu hubungkan ke `index.html` dengan menambahkan baris ini di bagian head: `<link rel="stylesheet" href="style.css">`. Simpan keduanya, buka browser, dan lihat warnanya berubah.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['6.2']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['6.3']->id,
            'question_type' => 'practice',
            'question_text' => 'Buat file `style.css`, hubungkan ke halamanmu, lalu ubah warna dan ukuran huruf paragrafmu. Tuliskan kode CSS yang kamu buat dan apa yang berubah di halaman.',
            'sort_order' => 1,
        ]);

        $this->units['6.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Layout Dasar dengan CSS',
            'content' => <<<'TEXT'
Setelah bisa mengubah warna dan huruf, langkah berikutnya adalah mengatur tata letak, yaitu bagaimana elemen ditempatkan dan diberi ruang. Ini yang membuat halaman terlihat rapi, bukan sekadar tumpukan teks.

**Box Model: setiap elemen adalah sebuah kotak.** Konsep paling penting dalam layout CSS adalah box model. Setiap elemen HTML sebenarnya adalah sebuah kotak, dan kotak itu punya beberapa lapisan:

[SAJIKAN: infografis — box model sebagai kotak berlapis dari dalam ke luar: Content (isi) di tengah, dikelilingi Padding (ruang dalam), lalu Border (garis tepi), lalu Margin (ruang luar). Beri label tiap lapisan]

- **Content (isi):** isi sebenarnya, misalnya teks atau gambar.
- **Padding (ruang dalam):** jarak antara isi dan garis tepi. Seperti bantalan di dalam kotak.
- **Border (garis tepi):** garis yang mengelilingi elemen.
- **Margin (ruang luar):** jarak antara elemen ini dengan elemen lain di sekitarnya.

**Display: block vs inline.** Elemen HTML punya cara tampil yang berbeda:

[SAJIKAN: tabel perbandingan — Block vs Inline, kolom "Ciri" dan "Contoh elemen"]

- **Block:** mengambil satu baris penuh, elemen berikutnya turun ke bawah. Contoh: `<div>`, `<p>`, heading.
- **Inline:** hanya mengambil ruang seperlunya, elemen bisa berdampingan dalam satu baris. Contoh: `<span>`, `<a>`.

**Flexbox: alat penata letak yang fleksibel.** Flexbox adalah cara modern menata elemen, misalnya menyusun beberapa kotak berdampingan dengan jarak rata. Kamu cukup memberi properti `display: flex` pada wadahnya:

[SAJIKAN: blok kode — contoh flexbox sederhana:
.wadah {
    display: flex;
    gap: 10px;
}]

Dengan ini, elemen-elemen di dalam `.wadah` akan tersusun berdampingan dengan jarak 10 piksel. Flexbox punya banyak kemampuan lain, tapi untuk sekarang cukup pahami idenya: flexbox membantu menata elemen dengan rapi.

[SAJIKAN: callout — Catatan: Layout adalah bagian CSS yang paling butuh latihan. Wajar kalau awalnya membingungkan. Kunci memahaminya adalah mencoba langsung: ubah nilai padding dan margin, lihat apa yang terjadi di browser. Bereksperimen adalah cara terbaik memahami box model.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['6.3']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['6.4']->id,
            'question_type' => 'practice',
            'question_text' => 'Beri padding dan margin pada salah satu elemen di halamanmu, lalu tambahkan border. Tuliskan kode CSS yang kamu pakai dan jelaskan perubahan yang kamu lihat.',
            'sort_order' => 1,
        ]);

        $this->units['6.5'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 5,
            'title' => 'Responsive Design: Tampil Baik di Semua Layar',
            'content' => <<<'TEXT'
Website-mu akan dibuka dari berbagai perangkat: laptop layar lebar, tablet, dan ponsel layar kecil. Responsive design memastikan halamanmu tetap terlihat baik di semua ukuran layar.

**Apa itu responsive design?** Responsive design adalah pendekatan membuat halaman web yang tampilannya menyesuaikan dengan ukuran layar perangkat. Di layar besar tampil lapang, di layar kecil menyusun ulang diri supaya tetap nyaman dibaca tanpa perlu memperbesar atau menggeser ke samping.

**Viewport meta tag.** Langkah pertama membuat halaman responsive adalah menambahkan satu baris di bagian head HTML:

[SAJIKAN: blok kode:
<meta name="viewport" content="width=device-width, initial-scale=1.0">]

Baris ini memberi tahu browser agar menyesuaikan lebar halaman dengan lebar layar perangkat. Tanpa baris ini, halaman di ponsel akan tampil mengecil seperti versi desktop yang dipaksakan.

**Media queries.** Media query adalah aturan CSS yang berlaku hanya pada ukuran layar tertentu. Dengan ini kamu bisa memberi gaya berbeda untuk layar kecil:

[SAJIKAN: blok kode — contoh media query:
@media (max-width: 600px) {
    p {
        font-size: 14px;
    }
}]

Penjelasan: aturan di dalam blok ini hanya berlaku saat lebar layar 600 piksel atau kurang (biasanya ponsel). Dalam contoh ini, ukuran huruf paragraf dikecilkan menjadi 14 piksel di layar kecil.

[SAJIKAN: callout — Cara menguji responsive: Buka halamanmu di browser, buka DevTools (ingat dari Modul 3), lalu cari ikon perangkat mobile di DevTools. Kamu bisa melihat tampilan halamanmu di berbagai ukuran layar tanpa perlu ponsel sungguhan. Coba perkecil dan perbesar, perhatikan bagaimana halamanmu menyesuaikan.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['6.4']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['6.5']->id,
            'question_type' => 'practice',
            'question_text' => 'Tambahkan viewport meta tag dan satu media query yang mengubah sesuatu di layar kecil. Uji lewat DevTools. Tuliskan kode yang kamu tambahkan dan apa yang berubah saat layar dikecilkan.',
            'sort_order' => 1,
        ]);

        $this->units['6.6'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 6,
            'title' => 'Membaca Dokumentasi: MDN Web Docs',
            'content' => <<<'TEXT'
Tidak ada developer yang menghafal semua tag HTML dan properti CSS. Yang membedakan developer berpengalaman adalah kemampuan mencari jawaban sendiri dari dokumentasi. Unit ini mengajarimu keterampilan itu.

**Apa itu MDN Web Docs?** MDN Web Docs (dikelola Mozilla) adalah rujukan resmi dan tepercaya untuk HTML, CSS, dan JavaScript. Hampir semua developer di dunia memakainya sebagai kamus saat lupa atau butuh detail suatu properti.

**Cara memakai MDN untuk mencari jawaban:**

[SAJIKAN: diagram alur — langkah mencari di MDN: 1. Buka mesin pencari, 2. Ketik "MDN" diikuti nama properti atau tag yang dicari (misal "MDN flexbox"), 3. Buka hasil dari developer.mozilla.org, 4. Baca bagian penjelasan dan contoh, 5. Coba contohnya di kodemu]

**Bagian yang biasanya paling berguna di halaman MDN:**

[SAJIKAN: kartu — bagian penting halaman MDN]

- **Penjelasan singkat** di bagian atas: menjelaskan properti atau tag itu untuk apa.
- **Syntax:** cara penulisan yang benar.
- **Examples (contoh):** potongan kode nyata yang bisa kamu tiru dan ubah.
- **Interactive demo:** beberapa halaman punya contoh interaktif yang bisa kamu utak-atik langsung.

[SAJIKAN: callout — Kenapa keterampilan ini penting: Teknologi web terus berkembang. Kemampuan membaca dokumentasi sendiri membuatmu mandiri dan tidak selalu bergantung pada tutorial atau bertanya orang lain. Ini keterampilan yang akan kamu pakai sepanjang karirmu sebagai developer.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['6.3']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['6.6']->id,
            'question_type' => 'practice',
            'question_text' => 'Cari satu properti CSS yang belum pernah kamu pakai di MDN (misalnya `background-color`, `text-align`, atau `border-radius`), lalu coba terapkan di halamanmu. Tuliskan properti apa yang kamu temukan dan apa fungsinya menurut MDN.',
            'sort_order' => 1,
        ]);

        $this->units['6.7'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 7,
            'title' => 'Rangkuman Teknis HTML dan CSS',
            'content' => <<<'TEXT'
Unit ini adalah cheat sheet HTML dan CSS yang bisa kamu buka kapan saja, merangkum semua yang sudah kamu pelajari di modul ini.

**Rangkuman Konsep HTML:**

[SAJIKAN: tabel perbandingan — tabel konsep HTML: kolom "Istilah" dan "Arti singkat", berisi: Tag, Elemen, Atribut, head, body]

| Istilah | Arti Singkat |
|---|---|
| Tag | Penanda di antara `<` dan `>`, biasanya berpasangan |
| Elemen | Keseluruhan dari tag pembuka sampai penutup beserta isinya |
| Atribut | Informasi tambahan di dalam tag pembuka (misal `href`, `src`, `alt`) |
| `<head>` | Bagian informasi halaman yang tidak tampil di layar |
| `<body>` | Bagian konten yang terlihat pengguna |

**Rangkuman Elemen HTML yang Sering Dipakai:**

[SAJIKAN: tabel perbandingan — tabel elemen: Heading (h1-h6), Paragraf (p), Link (a), Gambar (img), List (ul/ol/li), Div, Span, dengan kolom fungsi]

**Rangkuman Konsep CSS:**

[SAJIKAN: tabel perbandingan — tabel CSS: kolom "Konsep" dan "Arti singkat", berisi: Selector, Properti, Nilai, Box Model, Padding, Margin, Border, Display, Flexbox, Media Query]

| Konsep | Arti Singkat |
|---|---|
| Selector | Pemilih elemen yang akan diberi gaya |
| Properti dan Nilai | Aturan gaya, misal `color: blue` |
| Box Model | Setiap elemen adalah kotak berlapis (content, padding, border, margin) |
| Padding | Ruang dalam, antara isi dan garis tepi |
| Margin | Ruang luar, antara elemen dan sekitarnya |
| Border | Garis tepi elemen |
| Display (block/inline) | Cara elemen tampil dan mengambil ruang |
| Flexbox | Alat menata elemen berdampingan dengan rapi |
| Media Query | Aturan CSS untuk ukuran layar tertentu (responsive) |

[SAJIKAN: callout — Cara pakai: Tandai halaman ini sebagai referensi. Saat mengerjakan tugas modul ini atau membangun portofolio nanti, kembali ke sini kalau lupa suatu konsep atau penulisan.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'none',
            'prerequisite_unit_id' => $this->units['6.6']->id,
        ]);

        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Aku bisa membuat file HTML dari nol dengan struktur yang benar',
                'Aku memahami tag-tag HTML yang paling sering dipakai',
                'Aku bisa menghubungkan dan menulis CSS dasar',
                'Aku memahami konsep box model dan Flexbox dasar',
                'Aku bisa membuat halaman yang responsive',
            ],
            'intermezo_questions' => [
                // Document describes a real file-upload submission ("Tugas praktik
                // dengan pengumpulan file", tombol "Kumpulkan") — stored as descriptive
                // text per user confirmation; no file upload mechanism built yet.
                'Tugas praktik (dengan pengumpulan file): Buat satu halaman web sederhana menggunakan HTML dan CSS, dan pastikan halaman itu responsive (tampil baik di layar besar maupun kecil). Isi halaman bebas, boleh tentang dirimu, hobimu, atau apa saja. Tidak perlu di-push ke GitHub. Setelah halamanmu selesai, klik tombol "Kumpulkan" untuk mengirimkan hasilnya langsung ke PIC.',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'W3Schools HTML Tutorial',
            'url' => 'https://www.w3schools.com/html/',
            'source_name' => 'Tutorial HTML paling ramah pemula, dengan editor "Try it Yourself" yang memungkinkanmu mencoba kode langsung di browser tanpa setup apapun. Sangat cocok untuk latihan tiap elemen HTML.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'W3Schools CSS Tutorial',
            'url' => 'https://www.w3schools.com/css/',
            'source_name' => 'Tutorial CSS bertahap dengan contoh interaktif. Cocok untuk latihan ulang selector, box model, dan flexbox.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'MDN Web Docs: HTML',
            'url' => 'https://developer.mozilla.org/en-US/docs/Web/HTML',
            'source_name' => 'Rujukan resmi HTML untuk mencari detail tag dan atribut.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'MDN Web Docs: CSS',
            'url' => 'https://developer.mozilla.org/en-US/docs/Web/CSS',
            'source_name' => 'Rujukan resmi CSS untuk mencari detail properti dan nilai.',
        ]);
    }

    private function seedModule7(): void
    {
        $module = Module::create([
            'order_number' => 7,
            'title' => 'Mengenal Backend dan Database',
            'description' => 'Memahami peran backend dan database secara konseptual dan melihat contoh nyata sederhananya. Bukan untuk menguasai backend, tapi supaya kamu punya gambaran utuh bahwa website bukan cuma tampilan.',
            'level_number' => 4,
        ]);

        $this->units['7.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Apa yang Backend Kerjakan?',
            'content' => <<<'TEXT'
Sejauh ini kamu belajar membangun tampilan (frontend). Sekarang kita mengintip sisi yang tidak terlihat pengguna: backend. Kamu tidak akan membangun backend di kurikulum ini, tapi memahaminya membuat gambaranmu tentang website menjadi utuh.

**Apa yang backend kerjakan?** Backend adalah bagian website yang bekerja di belakang layar, berjalan di sisi server. Tugas utamanya:

[SAJIKAN: kartu — tiga tugas utama backend]

- **Mengolah data.** Menghitung, menyusun, dan memproses informasi. Misalnya menghitung total belanjaan di keranjang.
- **Menyimpan dan mengambil data.** Menyimpan informasi ke database dan mengambilnya kembali saat dibutuhkan. Misalnya menyimpan data akun penggunamu.
- **Menjalankan logika dan keamanan.** Memutuskan apa yang boleh dan tidak. Misalnya memeriksa apakah password yang dimasukkan benar sebelum mengizinkan login.

**Analogi dapur restoran.** Ingat analogi restoran dari Modul 2? Frontend adalah ruang makan yang dilihat pelanggan. Backend adalah dapurnya. Pelanggan tidak melihat dapur, tapi di sanalah makanan disiapkan. Saat kamu memesan (frontend mengirim permintaan), dapur (backend) memasak dan menyiapkan pesananmu, lalu mengirimnya kembali ke meja.

[SAJIKAN: infografis — restoran terbagi dua: "Ruang Makan = Frontend (dilihat pelanggan)" dan "Dapur = Backend (bekerja tersembunyi)", dengan alur pesanan bolak-balik di antaranya]

[SAJIKAN: callout — Poin penting: Kamu tidak perlu bisa membangun dapur untuk paham bahwa dapur itu ada dan penting. Begitu juga backend. Modul ini membekalimu pemahaman, bukan menuntutmu menjadi backend developer sekarang.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['2.4']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['7.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Memeriksa password saat login itu tugas frontend atau backend?',
            'options' => ['Frontend', 'Backend'],
            'correct_answer' => 'Backend',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['7.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Menyimpan dan mengambil data pengguna adalah tugas siapa?',
            'options' => ['Frontend', 'Backend'],
            'correct_answer' => 'Backend',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['7.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Dalam analogi restoran, backend diibaratkan sebagai apa?',
            'options' => ['Ruang makan', 'Dapur', 'Pelanggan'],
            'correct_answer' => 'Dapur',
            'sort_order' => 3,
        ]);

        $this->units['7.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'API: Jembatan antara Frontend dan Backend',
            'content' => <<<'TEXT'
Frontend dan backend adalah dua bagian terpisah. Bagaimana keduanya bicara satu sama lain? Lewat sesuatu bernama API. Kamu sudah sempat mendengar istilah ini di Modul 1, sekarang kita perdalam.

**Apa itu API?** API (Application Programming Interface) adalah perantara yang memungkinkan dua program saling berbicara dan bertukar data. Dalam konteks web, API adalah jembatan yang dipakai frontend untuk meminta data ke backend.

**Cara kerja API dalam pola request-response.** Ingat pola request-response dari Modul 2? API bekerja dengan pola yang sama:

[SAJIKAN: diagram alur — alur API: Frontend mengirim permintaan ke API ("berikan daftar produk") → API meneruskan ke backend → backend mengambil data → API mengirim balik data ke frontend → frontend menampilkannya]

Contoh nyata: saat kamu membuka aplikasi cuaca, frontend aplikasi itu mengirim permintaan ke API layanan cuaca. API mengembalikan data cuaca terkini, lalu frontend menampilkannya dengan rapi berupa angka suhu dan ikon awan.

**Bentuk data yang dikirim API.** Data yang dikirim API biasanya berupa teks terstruktur yang mudah dibaca program, format paling umum disebut JSON. Kamu tidak perlu menguasainya sekarang, cukup tahu bahwa API mengirim data dalam format rapi yang bisa diolah frontend.

[SAJIKAN: callout — Analogi: API seperti pelayan restoran. Kamu (frontend) tidak masuk ke dapur (backend) sendiri. Kamu memberi tahu pelayan (API) apa yang kamu mau, pelayan menyampaikannya ke dapur, lalu membawakan pesananmu kembali. Pelayan adalah perantara yang menghubungkan meja dan dapur.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['7.1']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['7.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa itu API?',
            'options' => ['Perantara yang memungkinkan dua program saling berbicara dan bertukar data', 'Bahasa pemrograman baru', 'Nama lain dari database'],
            'correct_answer' => 'Perantara yang memungkinkan dua program saling berbicara dan bertukar data',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['7.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Format data yang paling umum dikirim API disebut apa?',
            'options' => ['JSON', 'DOCX', 'MP3'],
            'correct_answer' => 'JSON',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['7.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Dalam analogi restoran, API diibaratkan sebagai apa?',
            'options' => ['Pelayan', 'Dapur', 'Meja makan'],
            'correct_answer' => 'Pelayan',
            'sort_order' => 3,
        ]);

        $this->units['7.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Database: Tempat Data Disimpan',
            'content' => <<<'TEXT'
Setiap website yang menyimpan informasi, mulai dari akun pengguna sampai daftar produk, butuh tempat menyimpan data itu. Tempat itu disebut database.

**Apa itu database?** Database adalah tempat penyimpanan data yang terstruktur dan tertata, sehingga data mudah disimpan, dicari, dan diambil kembali. Backend berkomunikasi dengan database untuk menyimpan dan mengambil informasi.

**Kenapa butuh database, kenapa tidak simpan di file biasa?** Bayangkan sebuah website dengan ribuan pengguna. Kalau datanya disimpan di file teks biasa, mencari satu pengguna tertentu akan sangat lambat dan berantakan. Database dirancang khusus untuk menyimpan banyak data secara rapi dan mencarinya dengan cepat, bahkan saat datanya jutaan.

**Dua jenis database secara garis besar:**

[SAJIKAN: tabel perbandingan — Database Relasional vs Non-Relasional, kolom "Cara menyimpan", "Cocok untuk", "Contoh"]

- **Database Relasional.** Menyimpan data dalam bentuk tabel, mirip spreadsheet dengan baris dan kolom. Antar tabel bisa saling berhubungan. Cocok untuk data yang terstruktur rapi. Contoh: MySQL, PostgreSQL.
- **Database Non-Relasional (NoSQL).** Menyimpan data dengan cara lebih fleksibel, tidak harus berbentuk tabel kaku. Cocok untuk data yang bentuknya beragam. Contoh: MongoDB.

**Contoh sederhana struktur data.** Bayangkan tabel pengguna dalam database relasional:

[SAJIKAN: tabel perbandingan — contoh tabel database dengan kolom "id", "nama", "email", dan dua baris data contoh, untuk menunjukkan bentuk penyimpanan tabel]

| id | nama | email |
|---|---|---|
| 1 | Ani | ani@email.com |
| 2 | Budi | budi@email.com |

[SAJIKAN: callout — Poin penting: Sama seperti backend, kamu tidak perlu menguasai database sekarang. Cukup pahami bahwa data website disimpan di tempat khusus bernama database, dan ada beberapa jenisnya. Ini melengkapi gambaranmu tentang bagaimana website bekerja secara utuh.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_matching',
            'prerequisite_unit_id' => $this->units['7.1']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['7.3']->id,
            'question_type' => 'matching',
            'question_text' => 'Cocokkan istilah (database, relasional, non-relasional, tabel) dengan penjelasannya.',
            'options' => [
                'pairs' => [
                    ['left' => 'Database', 'right' => 'Tempat penyimpanan data yang terstruktur dan tertata'],
                    ['left' => 'Relasional', 'right' => 'Menyimpan data dalam bentuk tabel yang saling berhubungan, contoh: MySQL'],
                    ['left' => 'Non-Relasional (NoSQL)', 'right' => 'Menyimpan data dengan cara lebih fleksibel, tidak harus berbentuk tabel kaku, contoh: MongoDB'],
                    ['left' => 'Tabel', 'right' => 'Bentuk penyimpanan data mirip spreadsheet dengan baris dan kolom'],
                ],
            ],
            'correct_answer' => [
                'Database' => 'Tempat penyimpanan data yang terstruktur dan tertata',
                'Relasional' => 'Menyimpan data dalam bentuk tabel yang saling berhubungan, contoh: MySQL',
                'Non-Relasional (NoSQL)' => 'Menyimpan data dengan cara lebih fleksibel, tidak harus berbentuk tabel kaku, contoh: MongoDB',
                'Tabel' => 'Bentuk penyimpanan data mirip spreadsheet dengan baris dan kolom',
            ],
            'sort_order' => 1,
        ]);

        $this->units['7.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Melihat Backend Bekerja: Demo Sederhana',
            'content' => <<<'TEXT'
Sampai sini kamu sudah paham backend, API, dan database secara konsep. Sekarang mari lihat contoh nyatanya, supaya kamu bisa berkata "oh, ternyata begini bentuknya", meskipun belum membuatnya sendiri.

**Melihat data dari sebuah API publik.** Ada banyak API publik gratis yang bisa kamu buka langsung di browser untuk melihat bentuk data yang dikirim backend. Salah satu contoh sederhana adalah API yang mengembalikan data percobaan.

[SAJIKAN: callout — Coba sendiri: Buka salah satu API publik gratis di browsermu, misalnya sebuah alamat API yang mengembalikan data contoh dalam format JSON. Kamu akan melihat teks terstruktur berisi data. Itulah bentuk mentah data yang dikirim backend sebelum frontend mengubahnya jadi tampilan cantik. Salah satu contoh API publik ramah pemula adalah JSONPlaceholder di alamat jsonplaceholder.typicode.com.]

**Apa yang akan kamu lihat.** Saat membuka alamat API di browser, kamu akan melihat data mentah berbentuk teks terstruktur seperti ini:

[SAJIKAN: blok kode — contoh respons JSON sederhana:
{
    "id": 1,
    "judul": "Contoh Data",
    "selesai": false
}]

Data seperti inilah yang diterima frontend dari backend lewat API. Frontend lalu mengolahnya dan menampilkannya sebagai bagian dari halaman yang rapi dan enak dilihat.

**Menghubungkan semua yang sudah kamu pahami.** Sekarang kamu bisa melihat gambaran utuhnya:

[SAJIKAN: diagram alur — gambaran utuh: Pengguna berinteraksi dengan Frontend → Frontend meminta data lewat API → Backend memproses dan mengambil dari Database → data dikirim balik lewat API → Frontend menampilkan ke pengguna]

[SAJIKAN: callout — Selamat: Kamu sekarang punya gambaran lengkap tentang bagaimana sebuah website bekerja dari ujung ke ujung, dari yang dilihat pengguna sampai data yang tersimpan di belakang layar. Pemahaman ini yang membedakan orang yang cuma bisa "bikin tampilan" dengan orang yang paham sistem secara menyeluruh.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['7.2']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['7.4']->id,
            'question_type' => 'practice',
            'question_text' => 'Buka salah satu API publik gratis di browsermu (misalnya jsonplaceholder.typicode.com/todos/1), lihat data yang muncul, lalu ceritakan apa yang kamu lihat dan bagaimana bentuknya.',
            'sort_order' => 1,
        ]);

        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Aku memahami peran backend dalam sebuah aplikasi',
                'Aku memahami konsep API dan cara frontend berkomunikasi dengan backend',
                'Aku memahami fungsi database dan perbedaan jenis database secara umum',
            ],
            'intermezo_questions' => [
                'Jelaskan dengan bahasamu sendiri bagaimana frontend, backend, dan database bekerja sama saat seseorang login ke sebuah website. (Bayangkan langkah demi langkah dari saat pengguna menekan tombol login sampai berhasil masuk.)',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'MDN Web Docs: Introduction to the server side',
            'url' => 'https://developer.mozilla.org/en-US/docs/Learn_web_development/Extensions/Server-side/First_steps/Introduction',
            'source_name' => 'Pengenalan resmi soal sisi server (backend) dari MDN, menjelaskan apa yang dikerjakan backend dengan bahasa ramah pemula.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'W3Schools SQL Tutorial',
            'url' => 'https://www.w3schools.com/sql/',
            'source_name' => 'Untuk yang penasaran bagaimana data diambil dari database relasional, tutorial ini mengenalkan dasar SQL dengan contoh interaktif.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'MySQL Tutorial',
            'url' => 'https://www.mysqltutorial.org',
            'source_name' => 'Tutorial khusus database MySQL yang menjelaskan konsep database relasional secara bertahap, cocok untuk pendalaman jika tertarik ke bidang backend atau data.',
        ]);
    }

    private function seedModule8(): void
    {
        $module = Module::create([
            'order_number' => 8,
            'title' => 'Kolaborasi Tim dan Metodologi Pengembangan Software',
            'description' => 'Memahami bagaimana kerja tim dalam proyek software riil, mengenal berbagai metodologi pengembangan yang dipakai industri, dan bagaimana AI mengubah lanskap penerapannya saat ini.',
            'level_number' => 5,
        ]);

        $this->units['8.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Kenapa Kolaborasi Tim Itu Berbeda dari Kerja Sendiri?',
            'content' => <<<'TEXT'
Kamu sudah belajar Git dan GitHub yang menjadi alat kolaborasi. Sekarang kita bahas sisi manusianya: kenapa kerja tim menuntut hal-hal yang tidak diperlukan saat kerja sendiri.

**Tantangan yang muncul saat kerja tim:**

[SAJIKAN: kartu — tiga tantangan utama kerja tim]

- **Kode saling bentrok.** Dua orang mengubah bagian yang sama bisa menyebabkan konflik, seperti merge conflict yang sudah kamu pelajari di Modul 5.
- **Miskomunikasi.** Kalau tidak ada komunikasi jelas, dua orang bisa salah paham soal siapa mengerjakan apa, atau bagaimana suatu bagian seharusnya bekerja.
- **Duplikasi kerja.** Tanpa koordinasi, dua orang bisa mengerjakan hal yang sama tanpa sadar, membuang waktu.

**Kenapa perlu aturan dan kesepakatan?** Saat kerja sendiri, kamu bebas melakukan apa saja karena hanya kamu yang terlibat. Saat kerja tim, kalian butuh kesepakatan bersama: bagaimana membagi tugas, bagaimana berkomunikasi, dan bagaimana menggabungkan pekerjaan. Aturan ini bukan untuk mengekang, tapi untuk mencegah kekacauan.

[SAJIKAN: callout — Poin penting: Justru inilah alasan metodologi pengembangan software ada, yaitu memberikan cara terstruktur bagi tim untuk bekerja bersama tanpa kekacauan. Itu yang akan kamu pelajari di Bagian B modul ini.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Manakah yang TERMASUK tantangan kerja tim yang dijelaskan di unit ini?',
            'options' => ['Duplikasi kerja tanpa koordinasi', 'Harga hosting yang mahal', 'Warna tema website'],
            'correct_answer' => 'Duplikasi kerja tanpa koordinasi',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Kenapa tim butuh aturan dan kesepakatan bersama?',
            'options' => ['Untuk mencegah kekacauan, bukan untuk mengekang', 'Supaya semua orang mengerjakan hal yang sama persis', 'Karena diwajibkan GitHub'],
            'correct_answer' => 'Untuk mencegah kekacauan, bukan untuk mengekang',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Kenapa metodologi pengembangan software ada?',
            'options' => ['Memberikan cara terstruktur bagi tim bekerja bersama tanpa kekacauan', 'Untuk mempercantik tampilan website', 'Untuk mengganti kebutuhan Git'],
            'correct_answer' => 'Memberikan cara terstruktur bagi tim bekerja bersama tanpa kekacauan',
            'sort_order' => 3,
        ]);

        $this->units['8.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Komunikasi dalam Tim Dev',
            'content' => <<<'TEXT'
Kemampuan teknis saja tidak cukup untuk jadi anggota tim yang baik. Cara berkomunikasi sama pentingnya. Bahkan sering, developer yang komunikasinya baik lebih dihargai daripada yang jago teknis tapi sulit diajak kerja sama.

**Etika komunikasi di channel kerja.** Tim biasanya berkomunikasi lewat aplikasi seperti Slack, Discord, atau grup chat. Beberapa etika dasar:

[SAJIKAN: kartu — etika komunikasi tim]

- **Jelas dan spesifik.** Sampaikan maksudmu dengan jelas, jangan bertele-tele atau ambigu.
- **Tanggapi tepat waktu.** Balas pesan penting dalam waktu wajar, jangan biarkan rekan menunggu tanpa kepastian.
- **Hargai waktu orang lain.** Sebelum bertanya, coba cari dulu sendiri. Kalau bertanya, sampaikan sudah mencoba apa saja.

**Cara melaporkan progres.** Laporkan kemajuanmu secara jujur dan teratur. Kalau ada hambatan, sampaikan lebih awal, jangan tunggu sampai deadline. Laporan progres yang baik menyebutkan: apa yang sudah selesai, apa yang sedang dikerjakan, dan hambatan apa yang dihadapi.

**Cara minta bantuan tanpa ragu.** Berdasarkan pengalaman, banyak anggota baru takut bertanya karena merasa membebani. Padahal bertanya adalah hal wajar dan sehat dalam tim. Kuncinya adalah bertanya dengan baik: jelaskan masalahmu, apa yang sudah kamu coba, dan bantuan spesifik apa yang kamu butuhkan.

[SAJIKAN: callout — Untuk kamu yang sering ragu bertanya: Meminta bantuan bukan tanda lemah, justru tanda kamu serius menyelesaikan masalah. Rekan tim yang baik akan lebih senang membantu sejak awal daripada menemukan masalah besar di akhir karena kamu ragu bertanya. Bertanya dengan baik adalah keterampilan tim yang berharga.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['8.1']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Manakah cara komunikasi tim yang baik?',
            'options' => ['Jelas, spesifik, dan menanggapi tepat waktu', 'Menunggu sampai deadline baru melapor', 'Tidak pernah bertanya apapun ke rekan tim'],
            'correct_answer' => 'Jelas, spesifik, dan menanggapi tepat waktu',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Laporan progres yang baik sebaiknya menyebutkan apa saja?',
            'options' => ['Apa yang sudah selesai, sedang dikerjakan, dan hambatan yang dihadapi', 'Hanya keluhan pribadi', 'Tidak perlu menyebutkan apapun'],
            'correct_answer' => 'Apa yang sudah selesai, sedang dikerjakan, dan hambatan yang dihadapi',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Meminta bantuan ke rekan tim saat kesulitan adalah tanda...',
            'options' => ['Serius menyelesaikan masalah, bukan tanda lemah', 'Kelemahan yang harus dihindari', 'Sesuatu yang harus disembunyikan'],
            'correct_answer' => 'Serius menyelesaikan masalah, bukan tanda lemah',
            'sort_order' => 3,
        ]);

        $this->units['8.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Code Review: Belajar dari Kode Orang Lain',
            'content' => <<<'TEXT'
Ingat Pull Request dari Modul 5? Di dalam PR, rekan tim memeriksa kode sebelum digabungkan. Proses pemeriksaan itu disebut code review, dan ini salah satu kegiatan paling berharga untuk belajar dalam tim.

**Apa itu code review?** Code review adalah proses memeriksa kode yang ditulis orang lain sebelum kode itu digabungkan ke proyek utama. Tujuannya menemukan kesalahan lebih awal, menjaga kualitas, dan berbagi pengetahuan antar anggota tim.

**Kenapa code review penting?**

[SAJIKAN: kartu — manfaat code review]

- **Menemukan kesalahan lebih awal.** Mata kedua sering melihat masalah yang terlewat oleh penulisnya.
- **Menjaga kualitas dan konsistensi.** Memastikan kode seluruh tim mengikuti standar yang sama.
- **Belajar dari satu sama lain.** Kamu belajar cara orang lain menyelesaikan masalah, dan mereka belajar darimu.

**Cara memberi komentar yang konstruktif.** Saat mereview kode rekan, sampaikan masukan dengan sopan dan membangun. Fokus pada kode, bukan pada orangnya. Alih-alih "ini salah", lebih baik "bagaimana kalau bagian ini dibuat begini, supaya lebih mudah dibaca?".

**Cara merespons review.** Saat kodemu direview, jangan anggap masukan sebagai serangan pribadi. Anggap sebagai bantuan untuk membuat kodemu lebih baik. Kalau tidak setuju, sampaikan alasanmu dengan baik, bukan defensif.

[SAJIKAN: callout — Nada yang sehat: Code review yang baik adalah percakapan antar rekan yang sama-sama ingin hasil terbaik, bukan ajang menghakimi. Baik memberi maupun menerima review, tujuannya sama: kode yang lebih baik dan tim yang saling belajar.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['5.4']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.3']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa itu code review?',
            'options' => ['Proses memeriksa kode orang lain sebelum digabungkan ke proyek utama', 'Proses menghapus kode yang tidak dipakai', 'Proses membuat akun GitHub baru'],
            'correct_answer' => 'Proses memeriksa kode orang lain sebelum digabungkan ke proyek utama',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.3']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Manakah komentar review yang lebih konstruktif?',
            'options' => ['"Bagaimana kalau bagian ini dibuat begini, supaya lebih mudah dibaca?"', '"Ini salah."', '"Kode siapa ini, jelek sekali."'],
            'correct_answer' => '"Bagaimana kalau bagian ini dibuat begini, supaya lebih mudah dibaca?"',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.3']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Saat kodemu direview dan mendapat masukan, sikap yang tepat adalah...',
            'options' => ['Menganggapnya sebagai bantuan untuk membuat kode lebih baik', 'Menganggapnya sebagai serangan pribadi', 'Mengabaikannya sepenuhnya'],
            'correct_answer' => 'Menganggapnya sebagai bantuan untuk membuat kode lebih baik',
            'sort_order' => 3,
        ]);

        $this->units['8.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Apa Itu Metodologi SDLC dan Kenapa Tim Butuh Ini?',
            'content' => <<<'TEXT'
Di Modul 1 kamu belajar tahapan SDLC (perencanaan, desain, development, testing, dan seterusnya). Sekarang muncul pertanyaan: bagaimana cara tim menjalani tahapan itu? Ternyata ada banyak cara, dan cara-cara itu disebut metodologi SDLC.

**Apa itu metodologi SDLC?** Metodologi SDLC adalah pendekatan atau cara terstruktur yang dipakai tim untuk menjalani tahapan pengembangan software. Ini seperti resep yang mengatur bagaimana urutan dan ritme kerja tim dari awal sampai software jadi.

**Kenapa bukan cuma satu metode?** Karena setiap proyek berbeda. Ada proyek yang kebutuhannya sudah jelas dan pasti dari awal, ada yang kebutuhannya masih berubah-ubah. Ada tim besar, ada tim kecil. Metode yang cocok untuk satu situasi belum tentu cocok untuk situasi lain.

[SAJIKAN: callout — Analogi: Metodologi SDLC seperti gaya perjalanan. Kalau tujuanmu sudah pasti dan jalannya jelas, kamu bisa merencanakan seluruh rute dari awal (mirip Waterfall). Kalau tujuanmu masih bisa berubah dan kamu ingin fleksibel, kamu jalan bertahap sambil menyesuaikan arah (mirip Agile). Tidak ada yang mutlak benar, tergantung situasimu.]

**Kenapa tim perlu memilih metode yang tepat?** Memilih metode yang salah bisa membuat proyek lambat, kaku, atau kacau. Memahami pilihan yang ada membantu tim bekerja dengan cara yang paling sesuai dengan kebutuhan mereka. Di unit-unit berikutnya, kamu akan mengenal metode-metode utama.
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['1.3']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.4']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa itu metodologi SDLC?',
            'options' => ['Pendekatan atau cara terstruktur yang dipakai tim untuk menjalani tahapan pengembangan software', 'Bahasa pemrograman khusus tim', 'Nama lain dari Git'],
            'correct_answer' => 'Pendekatan atau cara terstruktur yang dipakai tim untuk menjalani tahapan pengembangan software',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.4']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Kenapa ada banyak metodologi SDLC, bukan cuma satu?',
            'options' => ['Karena tiap proyek dan tim punya kebutuhan yang berbeda', 'Karena aturan dari GitHub', 'Karena developer suka membuat istilah baru'],
            'correct_answer' => 'Karena tiap proyek dan tim punya kebutuhan yang berbeda',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.4']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa akibatnya kalau tim memilih metode SDLC yang salah untuk situasinya?',
            'options' => ['Proyek bisa jadi lambat, kaku, atau kacau', 'Tidak ada akibat apapun', 'Proyek otomatis selesai lebih cepat'],
            'correct_answer' => 'Proyek bisa jadi lambat, kaku, atau kacau',
            'sort_order' => 3,
        ]);

        $this->units['8.5'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 5,
            'title' => 'Waterfall: Metode Klasik Bertahap',
            'content' => <<<'TEXT'
Kita mulai dari metode tertua dan paling mudah dipahami: Waterfall.

**Cara kerja Waterfall.** Waterfall (air terjun) adalah metode yang menjalani tahapan SDLC secara berurutan dan satu arah, seperti air terjun yang mengalir turun. Satu tahap harus selesai sepenuhnya sebelum lanjut ke tahap berikutnya, dan biasanya tidak kembali ke tahap sebelumnya.

[SAJIKAN: diagram alur — tahapan Waterfall menurun seperti anak tangga: Kebutuhan → Desain → Development → Testing → Deployment, tiap tahap mengalir ke bawah tanpa panah balik]

**Standarisasi dan ciri khas.** Waterfall menekankan perencanaan dan dokumentasi menyeluruh di awal. Semua kebutuhan ditetapkan di depan dan diharapkan tidak berubah selama proyek berjalan.

[SAJIKAN: tabel perbandingan — Waterfall: kolom "Kelebihan" dan "Kekurangan"]

- **Kelebihan:** terstruktur jelas, mudah dipahami, dokumentasi lengkap, cocok untuk proyek dengan kebutuhan yang sudah pasti.
- **Kekurangan:** kaku, sulit berubah di tengah jalan. Kalau ada kesalahan yang baru ketahuan di tahap akhir, memperbaikinya bisa sangat mahal dan merepotkan.

**Kapan cocok dipakai?** Waterfall cocok untuk proyek yang kebutuhannya sudah sangat jelas sejak awal dan kecil kemungkinan berubah, misalnya proyek dengan aturan ketat dan dokumentasi wajib.

[SAJIKAN: callout — Catatan sejarah menarik: Waterfall pertama kali dijelaskan dalam sebuah makalah justru sebagai contoh pendekatan yang berisiko. Tapi karena strukturnya jelas dan mudah dipahami, metode ini malah menjadi standar selama puluhan tahun sebelum muncul pendekatan yang lebih fleksibel.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['8.4']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.5']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Bagaimana cara kerja Waterfall?',
            'options' => ['Tahapan dijalani berurutan dan satu arah, satu tahap selesai dulu sebelum lanjut', 'Tahapan dikerjakan acak tanpa urutan', 'Semua tahap dikerjakan bersamaan'],
            'correct_answer' => 'Tahapan dijalani berurutan dan satu arah, satu tahap selesai dulu sebelum lanjut',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.5']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa kekurangan utama Waterfall?',
            'options' => ['Kaku dan sulit berubah di tengah jalan', 'Terlalu fleksibel', 'Tidak terstruktur'],
            'correct_answer' => 'Kaku dan sulit berubah di tengah jalan',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.5']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Waterfall paling cocok dipakai untuk proyek seperti apa?',
            'options' => ['Kebutuhan sudah sangat jelas sejak awal dan kecil kemungkinan berubah', 'Kebutuhan sering berubah-ubah', 'Proyek tanpa dokumentasi sama sekali'],
            'correct_answer' => 'Kebutuhan sudah sangat jelas sejak awal dan kecil kemungkinan berubah',
            'sort_order' => 3,
        ]);

        $this->units['8.6'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 6,
            'title' => 'Agile: Iteratif dan Adaptif',
            'content' => <<<'TEXT'
Sebagai jawaban atas kekakuan Waterfall, muncul pendekatan yang lebih luwes bernama Agile. Ini metode yang paling banyak dipakai perusahaan software saat ini.

**Apa itu Agile?** Agile (lincah) bukan satu metode spesifik, melainkan sebuah mindset atau cara berpikir dalam pengembangan software. Intinya: bekerja secara bertahap dalam potongan-potongan kecil, sering meminta masukan, dan siap menyesuaikan diri dengan perubahan.

**Cara kerja Agile.** Alih-alih merencanakan semuanya di awal seperti Waterfall, tim Agile bekerja dalam siklus-siklus pendek. Tiap siklus menghasilkan bagian software yang bisa dipakai, lalu tim meminta masukan dan menyesuaikan rencana untuk siklus berikutnya.

[SAJIKAN: diagram alur — siklus Agile berputar: Rencana kecil → Kerjakan → Hasil bisa dipakai → Minta masukan → kembali ke Rencana kecil (siklus berulang)]

**Prinsip-prinsip Agile:**

[SAJIKAN: kartu — prinsip inti Agile]

- **Iteratif dan bertahap.** Bekerja dalam potongan kecil berulang, bukan satu proyek besar sekaligus.
- **Terbuka pada perubahan.** Kebutuhan boleh berubah seiring jalan, dan itu diterima sebagai hal wajar.
- **Kolaborasi dan masukan.** Sering berkomunikasi dengan pengguna dan sesama tim untuk memastikan arah tetap benar.

[SAJIKAN: tabel perbandingan — Agile: kolom "Kelebihan" dan "Kekurangan"]

- **Kelebihan:** fleksibel, cepat menghasilkan sesuatu yang bisa dipakai, mudah menyesuaikan perubahan, banyak masukan pengguna.
- **Kekurangan:** butuh komunikasi intens, kurang cocok kalau kebutuhan harus sangat pasti dan terdokumentasi kaku sejak awal.

**Kapan cocok dipakai?** Agile cocok untuk proyek yang kebutuhannya bisa berubah, butuh cepat menghasilkan, dan mengutamakan masukan pengguna. Ini sebabnya banyak startup dan perusahaan teknologi memakai Agile.

[SAJIKAN: callout — Perbandingan cepat: Kalau Waterfall merencanakan seluruh perjalanan sebelum berangkat, Agile berangkat dengan rencana kecil lalu menyesuaikan arah di tiap persimpangan berdasarkan apa yang ditemui.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['8.5']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.6']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa itu Agile?',
            'options' => ['Mindset bekerja bertahap dalam potongan kecil dan siap menyesuaikan perubahan', 'Satu metode spesifik yang kaku', 'Bahasa pemrograman baru'],
            'correct_answer' => 'Mindset bekerja bertahap dalam potongan kecil dan siap menyesuaikan perubahan',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.6']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa bedanya cara kerja Agile dengan Waterfall?',
            'options' => ['Agile bekerja dalam siklus pendek dan terbuka pada perubahan, Waterfall merencanakan semua di awal', 'Tidak ada bedanya sama sekali', 'Agile tidak butuh komunikasi tim'],
            'correct_answer' => 'Agile bekerja dalam siklus pendek dan terbuka pada perubahan, Waterfall merencanakan semua di awal',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.6']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Agile cocok dipakai untuk proyek seperti apa?',
            'options' => ['Kebutuhan bisa berubah dan butuh cepat menghasilkan', 'Kebutuhan sudah pasti dan tidak akan berubah', 'Proyek tanpa pengguna sama sekali'],
            'correct_answer' => 'Kebutuhan bisa berubah dan butuh cepat menghasilkan',
            'sort_order' => 3,
        ]);

        $this->units['8.7'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 7,
            'title' => 'Scrum: Framework Populer dalam Agile',
            'content' => <<<'TEXT'
Agile adalah mindset. Tapi bagaimana cara menerapkannya secara nyata sehari-hari? Di sinilah Scrum masuk. Scrum adalah cara paling populer untuk menjalankan Agile.

**Apa itu Scrum?** Scrum adalah framework (kerangka kerja) untuk menerapkan Agile. Scrum memberi struktur konkret berupa peran, kegiatan, dan ritme kerja yang teratur.

**Konsep-konsep kunci dalam Scrum:**

[SAJIKAN: kartu — konsep utama Scrum]

- **Sprint.** Siklus kerja pendek dengan durasi tetap, biasanya 1 sampai 4 minggu. Di akhir tiap sprint, tim menghasilkan bagian software yang bisa dipakai.
- **Daily Standup.** Pertemuan singkat harian (biasanya berdiri, supaya cepat) tempat tiap anggota menyampaikan tiga hal: apa yang dikerjakan kemarin, apa yang akan dikerjakan hari ini, dan hambatan apa yang dihadapi.
- **Retrospective.** Pertemuan di akhir sprint untuk merefleksikan apa yang berjalan baik, apa yang perlu diperbaiki, dan bagaimana tim bisa bekerja lebih baik di sprint berikutnya.

**Peran-peran dalam Scrum:**

[SAJIKAN: tabel perbandingan — tiga peran Scrum: Product Owner, Scrum Master, Development Team, dengan kolom "Tugas utama"]

- **Product Owner:** menentukan apa yang harus dikerjakan dan prioritasnya, mewakili kebutuhan pengguna.
- **Scrum Master:** memastikan tim menjalankan Scrum dengan baik dan membantu menyingkirkan hambatan.
- **Development Team:** anggota yang mengerjakan pembangunan software.

[SAJIKAN: callout — Kaitan dengan Modul 5: Ingat GitHub Issues dan Project Board? Papan dengan kolom To Do, In Progress, dan Done sangat cocok dipakai untuk mengelola pekerjaan dalam sprint Scrum. Inilah contoh bagaimana alat teknis dan metodologi kerja saling melengkapi.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_matching',
            'prerequisite_unit_id' => $this->units['8.6']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.7']->id,
            'question_type' => 'matching',
            'question_text' => 'Cocokkan konsep dan peran Scrum (Sprint, Daily Standup, Retrospective, Product Owner, Scrum Master) dengan penjelasannya.',
            'options' => [
                'pairs' => [
                    ['left' => 'Sprint', 'right' => 'Siklus kerja pendek dengan durasi tetap, biasanya 1 sampai 4 minggu'],
                    ['left' => 'Daily Standup', 'right' => 'Pertemuan singkat harian membahas kemarin, hari ini, dan hambatan'],
                    ['left' => 'Retrospective', 'right' => 'Pertemuan akhir sprint untuk merefleksikan dan memperbaiki cara kerja tim'],
                    ['left' => 'Product Owner', 'right' => 'Menentukan apa yang harus dikerjakan dan prioritasnya'],
                    ['left' => 'Scrum Master', 'right' => 'Memastikan tim menjalankan Scrum dengan baik dan menyingkirkan hambatan'],
                ],
            ],
            'correct_answer' => [
                'Sprint' => 'Siklus kerja pendek dengan durasi tetap, biasanya 1 sampai 4 minggu',
                'Daily Standup' => 'Pertemuan singkat harian membahas kemarin, hari ini, dan hambatan',
                'Retrospective' => 'Pertemuan akhir sprint untuk merefleksikan dan memperbaiki cara kerja tim',
                'Product Owner' => 'Menentukan apa yang harus dikerjakan dan prioritasnya',
                'Scrum Master' => 'Memastikan tim menjalankan Scrum dengan baik dan menyingkirkan hambatan',
            ],
            'sort_order' => 1,
        ]);

        $this->units['8.8'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 8,
            'title' => 'Metode SDLC Lainnya: Kanban, V-Model, Spiral, RAD',
            'content' => <<<'TEXT'
Selain Waterfall, Agile, dan Scrum, ada beberapa metode lain yang perlu kamu kenal secara garis besar. Kamu tidak perlu menguasai semuanya, cukup tahu keberadaannya dan perbedaan intinya.

[SAJIKAN: kartu — empat metode tambahan, satu kartu masing-masing]

**Kanban.** Metode yang berfokus pada memvisualisasikan alur kerja lewat papan berkolom (seperti To Do, In Progress, Done), dan membatasi jumlah pekerjaan yang dikerjakan bersamaan supaya tidak kewalahan. Kanban fleksibel dan mudah diterapkan di atas proses yang sudah ada. Cocok untuk pekerjaan yang mengalir terus seperti pemeliharaan dan perbaikan.

**V-Model.** Pengembangan dari Waterfall yang menekankan pengujian di tiap tahap. Bentuknya seperti huruf V: sisi kiri menurun untuk tahap perancangan, sisi kanan menaik untuk tahap pengujian yang sesuai tiap perancangan. Cocok untuk proyek yang menuntut pengujian sangat ketat.

**Spiral.** Metode yang menggabungkan perencanaan bertahap dengan fokus kuat pada pengelolaan risiko. Proyek berjalan dalam putaran-putaran (spiral), dan di tiap putaran, risiko dievaluasi. Cocok untuk proyek besar dan berisiko tinggi.

**RAD (Rapid Application Development).** Metode yang menekankan pembuatan cepat lewat prototipe (versi awal yang bisa dicoba) dan masukan pengguna yang intens. Cocok untuk proyek yang butuh hasil cepat dan sering diuji ke pengguna.

[SAJIKAN: tabel perbandingan — tabel ringkas semua metode: kolom "Metode", "Fokus utama", "Cocok untuk". Baris: Waterfall, Agile, Scrum, Kanban, V-Model, Spiral, RAD]

[SAJIKAN: callout — Poin penting: Kamu tidak perlu hafal detail tiap metode. Yang penting kamu paham bahwa ada banyak pendekatan, masing-masing punya fokus dan situasi yang cocok. Pemahaman ini membuatmu tidak kaget saat bertemu istilah-istilah ini di dunia kerja nanti.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_matching',
            'prerequisite_unit_id' => $this->units['8.7']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.8']->id,
            'question_type' => 'matching',
            'question_text' => 'Cocokkan metode (Kanban, V-Model, Spiral, RAD) dengan fokus utama dan situasi yang cocok.',
            'options' => [
                'pairs' => [
                    ['left' => 'Kanban', 'right' => 'Memvisualisasikan alur kerja lewat papan kolom, cocok untuk pekerjaan yang mengalir terus'],
                    ['left' => 'V-Model', 'right' => 'Menekankan pengujian di tiap tahap, cocok untuk proyek yang menuntut pengujian ketat'],
                    ['left' => 'Spiral', 'right' => 'Menggabungkan perencanaan bertahap dengan fokus pengelolaan risiko, cocok untuk proyek besar dan berisiko tinggi'],
                    ['left' => 'RAD', 'right' => 'Menekankan pembuatan cepat lewat prototipe, cocok untuk proyek yang butuh hasil cepat'],
                ],
            ],
            'correct_answer' => [
                'Kanban' => 'Memvisualisasikan alur kerja lewat papan kolom, cocok untuk pekerjaan yang mengalir terus',
                'V-Model' => 'Menekankan pengujian di tiap tahap, cocok untuk proyek yang menuntut pengujian ketat',
                'Spiral' => 'Menggabungkan perencanaan bertahap dengan fokus pengelolaan risiko, cocok untuk proyek besar dan berisiko tinggi',
                'RAD' => 'Menekankan pembuatan cepat lewat prototipe, cocok untuk proyek yang butuh hasil cepat',
            ],
            'sort_order' => 1,
        ]);

        $this->units['8.9'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 9,
            'title' => 'Membagi dan Melacak Tugas',
            'content' => <<<'TEXT'
Metodologi apapun yang dipakai, ada keterampilan mendasar yang selalu dibutuhkan: membagi pekerjaan besar menjadi tugas kecil dan melacaknya. Unit ini menghubungkan konsep metodologi dengan alat praktik yang sudah kamu pelajari.

**Memecah pekerjaan besar menjadi tugas kecil.** Pekerjaan besar seperti "membuat website toko online" terasa menakutkan kalau dilihat sebagai satu bongkahan. Kuncinya adalah memecahnya jadi tugas-tugas kecil yang jelas, misalnya: "buat halaman utama", "buat form login", "buat halaman keranjang". Tugas kecil lebih mudah dikerjakan, dibagi, dan dilacak.

[SAJIKAN: diagram alur — satu kotak besar "Buat Website Toko" pecah menjadi beberapa kotak kecil: "Halaman Utama", "Form Login", "Halaman Keranjang", "Halaman Produk"]

**Cara assign (menugaskan) tugas.** Setelah dipecah, tiap tugas diberikan ke anggota yang bertanggung jawab. Ini mencegah kebingungan soal siapa mengerjakan apa dan mencegah duplikasi kerja.

**Cara melacak progres.** Progres dilacak dengan memindahkan tugas melalui tahapan: yang akan dikerjakan, sedang dikerjakan, dan selesai. Di sinilah GitHub Issues dan Project Board yang kamu pelajari di Modul 5 berperan langsung.

[SAJIKAN: callout — Menghubungkan semuanya: GitHub Issues untuk mencatat tiap tugas, Project Board untuk melacak perpindahannya dari To Do ke Done. Kalau kamu memakai Scrum, tugas-tugas ini diatur dalam sprint. Semua konsep yang kamu pelajari saling terhubung dan dipakai bersama dalam kerja nyata.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['5.5']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.9']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Kenapa pekerjaan besar perlu dipecah menjadi tugas-tugas kecil?',
            'options' => ['Supaya lebih mudah dikerjakan, dibagi, dan dilacak', 'Supaya terlihat lebih rumit', 'Supaya tidak perlu ditugaskan ke siapapun'],
            'correct_answer' => 'Supaya lebih mudah dikerjakan, dibagi, dan dilacak',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.9']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa gunanya meng-assign (menugaskan) tugas ke anggota tertentu?',
            'options' => ['Mencegah kebingungan siapa mengerjakan apa dan mencegah duplikasi kerja', 'Supaya satu orang mengerjakan semuanya', 'Tidak ada gunanya'],
            'correct_answer' => 'Mencegah kebingungan siapa mengerjakan apa dan mencegah duplikasi kerja',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.9']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Fitur GitHub apa yang berperan langsung dalam melacak progres tugas?',
            'options' => ['Issues dan Project Board', 'Git log', 'Merge conflict'],
            'correct_answer' => 'Issues dan Project Board',
            'sort_order' => 3,
        ]);

        $this->units['8.10'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 10,
            'title' => 'Gap antara Teori SDLC dan Realitas Saat Ini',
            'content' => <<<'TEXT'
Semua metodologi yang kamu pelajari tadi dirumuskan sebelum AI secanggih sekarang menjadi bagian keseharian developer. Unit ini membahas kesenjangan (gap) antara teori metodologi klasik dengan realitas kerja developer di era AI, dan bagaimana menyikapinya.

**Apa yang berubah dengan hadirnya AI?** Alat AI seperti asisten koding sekarang bisa membantu developer menulis kode, mencari kesalahan, menjelaskan konsep, dan bahkan membuat bagian aplikasi dengan cepat. Ini mempercepat banyak tahapan yang dulu memakan waktu lama.

[SAJIKAN: kartu — pergeseran yang terjadi karena AI]

- **Tahap development lebih cepat.** Menulis kode yang dulu berjam-jam bisa dipercepat dengan bantuan AI, meski tetap butuh pemeriksaan manusia.
- **Batas antar tahap makin cair.** Prototipe cepat jadi lebih mudah, sehingga siklus merencanakan, membangun, dan menguji bisa berputar lebih cepat, sejalan dengan semangat Agile.
- **Peran developer bergeser.** Fokus developer bergeser dari sekadar menulis tiap baris kode ke arah merancang, mengarahkan, memeriksa, dan memastikan kualitas hasil kerja AI.

**Bagaimana metodologi beradaptasi?** Metodologi seperti Agile yang mengutamakan kecepatan dan iterasi justru semakin relevan di era AI, karena AI mempercepat tiap putaran. Namun prinsip dasarnya tetap: rencana yang jelas, komunikasi tim, dan pemeriksaan kualitas tetap dibutuhkan. AI adalah alat bantu yang mempercepat, bukan pengganti pemahaman dan tanggung jawab manusia.

[SAJIKAN: callout — Poin penting dan jujur: AI membuat pekerjaan developer lebih cepat, tapi tidak menghapus kebutuhan untuk paham dasar. Justru sebaliknya. Kalau kamu tidak paham fundamental (yang kamu pelajari di seluruh kurikulum ini), kamu tidak akan bisa menilai apakah hasil kerja AI itu benar atau salah. Fundamental yang kuat membuatmu bisa memanfaatkan AI secara cerdas, bukan bergantung buta padanya.]

**Bagaimana memanfaatkan AI secara efektif dan efisien?** Gunakan AI untuk mempercepat hal-hal yang berulang dan untuk membantu memahami hal baru, tapi selalu periksa dan pahami hasilnya. Jangan menyalin hasil AI tanpa mengerti. AI adalah rekan kerja yang cepat, tapi kamu tetap yang bertanggung jawab atas hasil akhir.
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'essay',
            'prerequisite_unit_id' => $this->units['8.8']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.10']->id,
            'question_type' => 'essay',
            'question_text' => 'Menurutmu, kenapa memahami dasar-dasar pengembangan software tetap penting meskipun sekarang ada AI yang bisa membantu menulis kode?',
            'sort_order' => 1,
        ]);

        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Aku memahami tantangan kerja tim dan pentingnya komunikasi',
                'Aku bisa menyebutkan dan membedakan minimal 4 metode SDLC',
                'Aku memahami cara kerja Agile dan Scrum secara dasar',
                'Aku punya gambaran bagaimana AI mempengaruhi penerapan SDLC saat ini',
            ],
            'intermezo_questions' => [
                'Kuis pemahaman metode SDLC. Kuis mencakup berbagai metode (Waterfall, Agile, Scrum, Kanban, V-Model, Spiral, RAD), bukan hanya Agile dan Scrum. Contoh cakupan: membedakan ciri tiap metode, mencocokkan metode dengan situasi yang cocok, dan memahami perbedaan pendekatan berurutan (Waterfall) versus iteratif (Agile).',
                'Kalau kamu jadi anggota tim dalam proyek web, metode SDLC mana yang menurutmu paling cocok untuk timmu dan kenapa? Bagaimana caramu berkontribusi berdasarkan semua yang sudah kamu pelajari?',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Atlassian Agile Coach',
            'url' => 'https://www.atlassian.com/agile',
            'source_name' => 'Panduan lengkap dan ramah pemula soal Agile, Scrum, dan Kanban dari Atlassian (pembuat Jira dan Trello). Menjelaskan tiap konsep dengan contoh praktik nyata.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'roadmap.sh',
            'url' => 'https://roadmap.sh',
            'source_name' => 'Untuk melihat bagaimana keterampilan kerja tim dan pemahaman metodologi masuk dalam jalur belajar berbagai peran developer.',
        ]);
    }

    private function seedModule9(): void
    {
        $module = Module::create([
            'order_number' => 9,
            'title' => 'Deployment, Membuat Website Bisa Diakses Dunia',
            'description' => 'Memahami konsep deployment dan mempraktikkan deploy website sederhana ke internet. Supaya kamu merasakan langsung hasil kerjamu bisa diakses siapa saja.',
            'level_number' => 5,
        ]);

        $this->units['9.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Apa Itu Deployment?',
            'content' => <<<'TEXT'
Kamu sudah bisa membuat halaman web di komputermu. Tapi sejauh ini, halaman itu hanya bisa dilihat olehmu sendiri. Bagaimana caranya supaya orang lain di seluruh dunia bisa mengaksesnya? Jawabannya: deployment.

**Apa itu deployment?** Deployment (perilisan) adalah proses memindahkan website dari komputer lokalmu ke sebuah server yang terhubung ke internet, sehingga bisa diakses publik lewat sebuah alamat (URL). Selama website hanya ada di komputermu, hanya kamu yang bisa melihatnya. Setelah di-deploy, siapa saja bisa membukanya.

[SAJIKAN: diagram alur — perpindahan: "Website di Komputermu (hanya kamu yang lihat)" → proses deployment → "Website di Server Internet (bisa diakses siapa saja lewat URL)"]

**Menghubungkan dengan konsep hosting.** Ingat konsep hosting dari Modul 2? Deployment pada dasarnya adalah menaruh file website-mu di layanan hosting supaya tersimpan di server yang menyala terus dan terhubung ke internet. Deployment dan hosting adalah dua konsep yang sangat berkaitan: hosting adalah tempatnya, deployment adalah proses menaruhnya ke sana.

[SAJIKAN: callout — Momen penting: Deployment adalah saat karyamu benar-benar "hidup" di internet. Ini salah satu momen paling memuaskan bagi developer pemula, melihat sesuatu yang kamu buat sendiri bisa dibuka oleh siapa saja, di mana saja, lewat sebuah link.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['2.3']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['9.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Apa itu deployment?',
            'options' => ['Proses memindahkan website ke server internet supaya bisa diakses publik', 'Proses menghapus website', 'Proses menulis kode HTML'],
            'correct_answer' => 'Proses memindahkan website ke server internet supaya bisa diakses publik',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['9.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Bagaimana hubungan deployment dengan hosting?',
            'options' => ['Hosting adalah tempatnya, deployment adalah proses menaruh website ke sana', 'Keduanya tidak berhubungan sama sekali', 'Hosting adalah proses, deployment adalah tempatnya'],
            'correct_answer' => 'Hosting adalah tempatnya, deployment adalah proses menaruh website ke sana',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['9.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Sebelum di-deploy, siapa yang bisa melihat website yang hanya ada di komputer lokal?',
            'options' => ['Hanya kamu sendiri', 'Semua orang di internet', 'Hanya rekan tim'],
            'correct_answer' => 'Hanya kamu sendiri',
            'sort_order' => 3,
        ]);

        $this->units['9.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Pilihan Platform Deployment untuk Pemula',
            'content' => <<<'TEXT'
Kabar baiknya, sekarang ada banyak platform yang memungkinkanmu men-deploy website secara gratis dan mudah, tanpa perlu mengurus server rumit. Mari kenali tiga yang paling ramah pemula.

[SAJIKAN: kartu — tiga platform deployment gratis]

**GitHub Pages.** Layanan gratis dari GitHub untuk men-deploy website statis (halaman HTML dan CSS) langsung dari repository GitHub-mu. Karena kamu sudah punya akun GitHub dari Modul 5, ini pilihan paling mulus untuk pemula. Cocok untuk portofolio dan halaman sederhana.

**Vercel.** Platform deployment yang sangat mudah dipakai, cukup hubungkan repository GitHub-mu dan website langsung ter-deploy. Vercel populer untuk proyek yang lebih modern dan punya banyak fitur otomatis. Punya paket gratis yang memadai untuk belajar.

**Netlify.** Mirip Vercel, Netlify juga memudahkan deployment dengan menghubungkan repository GitHub. Terkenal ramah pemula dan punya banyak fitur bantuan. Juga menyediakan paket gratis.

[SAJIKAN: tabel perbandingan — tabel tiga platform: kolom "Platform", "Kelebihan", "Cocok untuk". Baris GitHub Pages, Vercel, Netlify]

| Platform | Kelebihan | Cocok untuk |
|---|---|---|
| GitHub Pages | Terintegrasi dengan GitHub, paling sederhana | Portofolio dan halaman statis |
| Vercel | Sangat mudah, banyak fitur otomatis | Proyek modern, latihan lanjutan |
| Netlify | Ramah pemula, banyak fitur bantuan | Halaman statis dan proyek kecil |

[SAJIKAN: callout — Rekomendasi untuk kurikulum ini: Karena kamu sudah punya akun dan repository GitHub, GitHub Pages adalah pilihan paling mulus untuk men-deploy portofolio kurikulum ini. Tapi ketiganya sama-sama baik, dan mencoba yang lain nanti adalah latihan yang bagus.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_matching',
            'prerequisite_unit_id' => $this->units['9.1']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['9.2']->id,
            'question_type' => 'matching',
            'question_text' => 'Cocokkan tiga platform (GitHub Pages, Vercel, Netlify) dengan kelebihan dan kecocokannya.',
            'options' => [
                'pairs' => [
                    ['left' => 'GitHub Pages', 'right' => 'Terintegrasi dengan GitHub, paling sederhana, cocok untuk portofolio dan halaman statis'],
                    ['left' => 'Vercel', 'right' => 'Sangat mudah, banyak fitur otomatis, cocok untuk proyek modern'],
                    ['left' => 'Netlify', 'right' => 'Ramah pemula, banyak fitur bantuan, cocok untuk halaman statis dan proyek kecil'],
                ],
            ],
            'correct_answer' => [
                'GitHub Pages' => 'Terintegrasi dengan GitHub, paling sederhana, cocok untuk portofolio dan halaman statis',
                'Vercel' => 'Sangat mudah, banyak fitur otomatis, cocok untuk proyek modern',
                'Netlify' => 'Ramah pemula, banyak fitur bantuan, cocok untuk halaman statis dan proyek kecil',
            ],
            'sort_order' => 1,
        ]);

        $this->units['9.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Praktik Deploy: Publish Website ke Internet',
            'content' => <<<'TEXT'
Waktunya melakukannya sendiri. Unit ini memandumu men-deploy halaman web-mu ke internet lewat GitHub Pages, sampai benar-benar bisa diakses lewat URL.

**Langkah men-deploy dengan GitHub Pages:**

[SAJIKAN: diagram alur — langkah deploy GitHub Pages:
1. Pastikan file website (HTML dan CSS) sudah ada di repository GitHub (push jika belum, ingat cara dari Modul 5)
2. Buka repository di GitHub, masuk ke menu Settings
3. Cari bagian "Pages" di menu samping
4. Pilih branch yang akan di-deploy (biasanya main) dan simpan
5. Tunggu beberapa saat, GitHub akan memberi URL website-mu
6. Buka URL itu, website-mu kini live dan bisa diakses siapa saja]

**Setelah deploy berhasil.** GitHub Pages akan memberimu sebuah alamat URL (biasanya berformat `username.github.io/nama-repo`). Buka alamat itu di browser, atau kirim ke temanmu, dan mereka bisa melihat website-mu. Itulah bukti karyamu sudah hidup di internet.

[SAJIKAN: callout — Kalau tidak langsung muncul: Wajar kalau website tidak langsung tampil beberapa menit setelah deploy, karena butuh waktu proses. Kalau muncul halaman kosong atau error, cek kembali apakah file utamamu bernama `index.html`, karena GitHub Pages mencari file itu sebagai halaman pembuka. Kalau masih bingung, tanyakan di forum diskusi.]

[SAJIKAN: callout — Selamat: Kalau website-mu berhasil tampil di URL, kamu baru saja melakukan sesuatu yang dilakukan developer sungguhan setiap hari. Karyamu kini bisa diakses dari mana saja di dunia. Simpan URL-nya, kamu akan memakainya lagi di modul terakhir.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['9.2']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['9.3']->id,
            'question_type' => 'practice',
            'question_text' => 'Deploy salah satu halaman web-mu ke GitHub Pages. Tuliskan URL website-mu yang sudah live, dan ceritakan bagaimana perasaanmu melihat karyamu bisa diakses lewat internet.',
            'sort_order' => 1,
        ]);

        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Aku memahami konsep deployment',
                'Aku mengetahui pilihan platform deployment yang tersedia',
                'Aku berhasil men-deploy minimal satu halaman ke internet',
            ],
            'intermezo_questions' => [
                // Requires only a URL as text, tractable with existing text-based intermezo (no real file upload needed).
                'Tugas praktik (dengan pengumpulan URL): Deploy halaman web-mu (bisa halaman "Tentang Saya" atau halaman dari Modul 6) ke internet lewat GitHub Pages atau platform lain pilihanmu. Setelah live, lampirkan URL website-mu yang bisa diakses publik sebagai bukti.',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'GitHub Pages Documentation',
            'url' => 'https://docs.github.com/en/pages',
            'source_name' => 'Dokumentasi resmi GitHub Pages, panduan langkah demi langkah men-deploy website langsung dari repository GitHub.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Vercel Documentation',
            'url' => 'https://vercel.com/docs',
            'source_name' => 'Dokumentasi resmi Vercel untuk yang ingin mencoba platform deployment alternatif dengan fitur lebih modern.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'W3Schools: How To Publish a Website',
            'url' => 'https://www.w3schools.com/',
            'source_name' => 'Referensi dasar seputar mempublikasikan website, cocok untuk memperkuat pemahaman konsep deployment.',
        ]);
    }

    private function seedModule10(): void
    {
        $module = Module::create([
            'order_number' => 10,
            'title' => 'Proyek Akhir, Portofolio Pribadi',
            'description' => 'Menggabungkan semua yang sudah kamu pelajari menjadi satu output nyata, yaitu website portofolio pribadi yang sudah live di internet dan bisa ditunjukkan ke siapa saja. Ini puncak dari seluruh perjalananmu.',
            'level_number' => 6,
        ]);

        $this->units['10.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Merencanakan Portofolio: Apa Isinya?',
            'content' => <<<'TEXT'
Sebelum menulis satu baris kode, developer yang baik merencanakan dulu. Unit ini membantumu menyusun rencana portofolio sebelum membangunnya.

**Apa itu portofolio?** Portofolio adalah website pribadi yang menampilkan siapa dirimu, kemampuan apa yang kamu punya, dan karya apa yang sudah kamu buat. Ini seperti "kartu nama digital" yang bisa kamu tunjukkan ke calon pemberi kerja, klien, atau siapa saja.

**Struktur konten portofolio.** Portofolio sederhana biasanya berisi bagian-bagian berikut:

[SAJIKAN: kartu — bagian-bagian portofolio]

- **Tentang Diri.** Perkenalan singkat: siapa kamu, apa yang kamu pelajari, dan minatmu di bidang teknologi.
- **Skill (Kemampuan).** Daftar keterampilan yang sudah kamu pelajari, misalnya HTML, CSS, Git, dan GitHub.
- **Karya atau Hasil Belajar.** Hasil tugas atau proyek yang kamu buat selama kurikulum ini. Bisa menampilkan halaman-halaman yang sudah kamu buat di modul sebelumnya.
- **Kontak.** Cara menghubungimu: email, tautan GitHub, atau media sosial profesional.

**Merencanakan sebelum ngoding.** Sebelum membangun, tuliskan dulu rencanamu: bagian apa saja yang akan kamu masukkan, apa isi tiap bagian, dan bagaimana urutannya. Perencanaan ini membuat proses membangun jauh lebih lancar.

[SAJIKAN: callout — Tips: Portofolio pertamamu tidak harus sempurna atau rumit. Yang penting jujur menampilkan perjalanan belajarmu. Portofolio sederhana yang selesai dan live jauh lebih berharga daripada portofolio megah yang tidak pernah selesai.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['10.1']->id,
            'question_type' => 'practice',
            'question_text' => 'Tuliskan rencana portofolio pribadimu: bagian apa saja yang akan kamu masukkan, dan apa isi singkat tiap bagian.',
            'sort_order' => 1,
        ]);

        $this->units['10.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Membangun Struktur HTML Portofolio',
            'content' => <<<'TEXT'
Dengan rencana di tangan, sekarang waktunya membangun kerangka HTML portofoliomu. Kamu akan memakai semua yang sudah kamu pelajari di Modul 6.

**Menyusun kerangka berdasarkan rencana.** Ubah tiap bagian dari rencanamu di Unit 10.1 menjadi bagian HTML. Gunakan elemen-elemen yang sudah kamu kenal:

[SAJIKAN: kartu — pemetaan bagian portofolio ke elemen HTML]

- **Tentang Diri:** gunakan heading (`<h1>` untuk namamu) dan paragraf (`<p>`) untuk perkenalan.
- **Skill:** gunakan daftar (`<ul>` dan `<li>`) untuk mendaftar kemampuanmu.
- **Karya:** gunakan link (`<a>`) menuju halaman atau proyek yang sudah kamu buat, bisa juga dengan gambar (`<img>`).
- **Kontak:** gunakan link untuk email dan tautan ke GitHub-mu.

[SAJIKAN: blok kode — contoh kerangka HTML portofolio sederhana:
<!DOCTYPE html>
<html>
<head>
    <title>Portofolio Saya</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Nama Kamu</h1>
    <p>Perkenalan singkat tentang dirimu</p>
    <h2>Skill</h2>
    <ul>
        <li>HTML</li>
        <li>CSS</li>
        <li>Git dan GitHub</li>
    </ul>
    <h2>Karya</h2>
    <p>Tautan ke karya-karyamu</p>
    <h2>Kontak</h2>
    <p>Email dan tautan GitHub</p>
</body>
</html>]

[SAJIKAN: callout — Fokus dulu ke struktur: Di unit ini, jangan pusingkan tampilan atau warna dulu. Fokuslah memastikan semua bagian dan isinya ada dengan struktur HTML yang benar. Mempercantiknya adalah tugas unit berikutnya.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['10.1']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['10.2']->id,
            'question_type' => 'practice',
            'question_text' => 'Buat file HTML portofolio dengan semua bagian dari rencanamu (tentang diri, skill, karya, kontak). Tuliskan kode HTML yang kamu buat.',
            'sort_order' => 1,
        ]);

        $this->units['10.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Menyempurnakan Tampilan dengan CSS',
            'content' => <<<'TEXT'
Kerangka portofoliomu sudah jadi, tapi masih polos. Sekarang saatnya mempercantiknya dengan CSS, memakai semua yang kamu pelajari di Modul 6.

**Hal-hal yang bisa kamu atur dengan CSS:**

[SAJIKAN: kartu — aspek tampilan yang bisa disempurnakan]

- **Warna.** Pilih warna latar dan warna teks yang nyaman dilihat dan serasi.
- **Tipografi.** Atur jenis dan ukuran huruf supaya mudah dibaca dan terlihat rapi.
- **Tata letak (layout).** Gunakan box model (padding, margin) untuk memberi ruang, dan flexbox jika perlu menata bagian berdampingan.
- **Responsive.** Pastikan portofoliomu tetap bagus dilihat di ponsel, dengan viewport meta tag dan media query.

[SAJIKAN: callout — Tips desain untuk pemula: Kesederhanaan itu elegan. Portofolio dengan warna terbatas (2 sampai 3 warna serasi), ruang yang cukup lapang, dan huruf yang mudah dibaca akan terlihat jauh lebih profesional daripada yang penuh warna dan ramai. Kalau bingung soal warna, cari inspirasi dari website yang menurutmu enak dilihat.]

**Ingat keterampilan mencari sendiri.** Kalau kamu ingin efek tertentu yang belum kamu pelajari, ingat keterampilan membaca dokumentasi dari Modul 6. Cari di MDN atau W3Schools, banyak properti CSS menarik yang bisa kamu coba.
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['10.2']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['10.3']->id,
            'question_type' => 'practice',
            'question_text' => 'Beri gaya CSS pada portofoliomu: atur warna, huruf, dan tata letak, serta pastikan responsive. Tuliskan gambaran gaya yang kamu terapkan dan kode CSS utamanya.',
            'sort_order' => 1,
        ]);

        $this->units['10.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Push ke GitHub dan Deploy',
            'content' => <<<'TEXT'
Portofoliomu sudah jadi dan cantik. Sekarang saatnya membuatnya hidup di internet, menggabungkan keterampilan dari Modul 5 (GitHub) dan Modul 9 (deployment).

**Langkah-langkahnya:**

[SAJIKAN: diagram alur — langkah publikasi portofolio:
1. Buat repository baru di GitHub khusus untuk portofolio (misalnya beri nama "portofolio")
2. Hubungkan folder portofolio lokalmu ke repository itu (git remote)
3. Push semua file portofolio ke GitHub (git add, commit, push)
4. Aktifkan GitHub Pages di pengaturan repository (seperti di Modul 9)
5. Tunggu proses, lalu buka URL portofoliomu yang sudah live]

**Menggabungkan semua keterampilanmu.** Perhatikan bahwa langkah ini memakai hampir semua yang kamu pelajari: git add, commit, push (Modul 4 dan 5), repository GitHub (Modul 5), dan GitHub Pages (Modul 9). Inilah momen semua kepingan menyatu.

[SAJIKAN: callout — Momen puncak: Ketika portofoliomu live di internet, kamu telah menyelesaikan perjalanan dari "tidak tahu apa-apa" menjadi "punya website sendiri yang bisa diakses dunia". Itu pencapaian nyata yang layak kamu banggakan.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['10.3']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['10.4']->id,
            'question_type' => 'practice',
            'question_text' => 'Push portofoliomu ke GitHub dan deploy lewat GitHub Pages. Tuliskan URL portofoliomu yang sudah live.',
            'sort_order' => 1,
        ]);

        $this->units['10.5'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 5,
            'title' => 'Review dan Penyempurnaan',
            'content' => <<<'TEXT'
Portofoliomu sudah live. Langkah terakhir adalah memeriksa dan menyempurnakannya, kebiasaan penting yang membedakan hasil kerja yang matang dari yang asal jadi.

**Self-review: periksa sendiri karyamu.**

[SAJIKAN: kartu — daftar hal yang perlu diperiksa]

- **Cek di berbagai ukuran layar.** Buka portofoliomu di laptop dan di ponsel (atau lewat DevTools). Pastikan tampilannya tetap baik di keduanya.
- **Cek semua link.** Pastikan setiap tautan (ke karya, GitHub, email) berfungsi dan menuju tujuan yang benar.
- **Cek ejaan dan isi.** Baca ulang teksnya, pastikan tidak ada salah ketik dan isinya sudah sesuai keinginanmu.
- **Perbaiki hal-hal kecil.** Jarak yang kurang pas, warna yang kurang serasi, atau huruf yang terlalu kecil. Detail kecil membuat perbedaan besar.

**Minta feedback dari orang lain.** Bagikan URL portofoliomu di forum diskusi WEBI-SPACE. Minta anggota lain atau PIC memberi masukan. Mata orang lain sering melihat hal yang terlewat olehmu, seperti prinsip code review yang kamu pelajari di Modul 8.

[SAJIKAN: callout — Nada suportif: Meminta feedback bukan tanda karyamu jelek, tapi tanda kamu ingin berkembang. Terima masukan dengan lapang, ambil yang berguna, dan ingat bahwa setiap developer, sehebat apapun, terus menyempurnakan karyanya. Portofoliomu boleh terus berkembang seiring kemampuanmu bertambah.]
TEXT,
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['10.4']->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['10.5']->id,
            'question_type' => 'practice',
            'question_text' => 'Lakukan self-review portofoliomu memakai daftar periksa di atas, lalu bagikan URL-mu di forum untuk minta feedback. Tuliskan apa saja yang kamu perbaiki setelah memeriksa dan setelah mendapat masukan.',
            'sort_order' => 1,
        ]);

        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Portofolioku sudah punya konten lengkap (tentang diri, skill, karya, kontak)',
                'HTML dan CSS-nya sudah rapi dan responsive',
                'Sudah di-push ke GitHub',
                'Sudah live di internet dan bisa diakses lewat URL',
            ],
            'intermezo_questions' => [
                'Refleksi akhir (esai via input teks): Tulis perjalananmu dari awal sampai sekarang. Apa yang paling berkesan, apa yang paling sulit, dan apa yang ingin kamu pelajari selanjutnya?',
                'Lampirkan URL portofolio final sebagai penanda kelulusanmu dari kategori Eksplorasi.',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'roadmap.sh Frontend',
            'url' => 'https://roadmap.sh/frontend',
            'source_name' => 'Setelah menyelesaikan portofolio, ini peta jalur lengkap untuk mendalami frontend, jika kamu ingin melanjutkan ke bidang ini.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'W3Schools How To',
            'url' => 'https://www.w3schools.com/howto/',
            'source_name' => 'Kumpulan contoh komponen web siap tiru (kartu, navigasi, galeri) yang bisa memperkaya portofoliomu.',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'MDN Web Docs',
            'url' => 'https://developer.mozilla.org/',
            'source_name' => 'Rujukan utama untuk terus mengembangkan keterampilan HTML, CSS, dan seterusnya.',
        ]);
    }
}
