<?php

namespace Database\Seeders;

use App\Models\Checkpoint;
use App\Models\LearningResource;
use App\Models\Module;
use App\Models\Unit;
use App\Models\UnitEvaluation;
use Illuminate\Database\Seeder;

/**
 * Kurikulum v2.0 (menggantikan v1.0), ditulis ulang bertahap per modul dari
 * docs/v_2.0/kurikulum/Kurikulum_Eksplorasi_Final_Lengkap.md — SATU-SATUNYA
 * sumber teks materi yang sah, disalin/diparafrase minimal, tidak dikarang.
 *
 * Konten (paragraf naratif) dipecah jadi ContentBlock terstruktur (heading/
 * text/list/table/callout/code), bukan satu blok teks panjang — lihat
 * `units.content` yang sengaja dikosongkan ('') untuk unit yang sudah
 * memakai content_blocks, pola yang sama dengan Editor Blok Konten
 * (App\Livewire\Admin\Curriculum\Units\Create/ContentEditor).
 *
 * PENYIMPANGAN dari kontrak spek yang ditemukan saat implementasi (dicek ke
 * kode dulu, bukan diasumsikan):
 * - callout.blade.php membaca `$data['body']`, bukan `$data['text']` —
 *   dipakai `body` di seluruh seeder ini.
 * - `unit_evaluations.correct_answer` untuk question_type=multiple_choice
 *   HARUS berisi teks pilihan lengkap yang identik dengan salah satu isi
 *   `options` (dibandingkan `===` di UnitEvaluation::isCorrectAnswer()) —
 *   BUKAN huruf kunci (A/B/C/D). Opsi tidak diberi prefix huruf di array
 *   `options` (radio button merender apa adanya), jadi correct_answer juga
 *   harus teks penuh tanpa huruf, kalau tidak grading rusak total karena
 *   perbandingan string tidak akan pernah cocok. Sesuai pola yang sudah
 *   dipakai CurriculumSeeder v1.0 sebelumnya.
 * - Unit 1.2 (3 skenario esai) dan Unit 1.4 (3 potongan kode praktik):
 *   `UnitEvaluation::submitFreeText()` (dipakai evaluation_type=essay/practice)
 *   HANYA membaca `evaluations()->first()`, satu textarea — tidak pernah
 *   loop banyak baris seperti submitQuiz(). Dikonfirmasi ke Aye
 *   (AskUserQuestion, 2026-07-16): ketiga skenario/potongan digabung jadi
 *   SATU baris UnitEvaluation per unit (satu question_text panjang berisi
 *   ketiganya dengan pemisah jelas, satu correct_answer gabungan untuk
 *   acuan), bukan 3 baris terpisah — supaya semua skenario benar-benar
 *   tampil dan bisa dijawab, bukan diam-diam jadi data mati.
 *
 * Checkpoint tiap modul disusun BARU oleh Celo (blueprint sama sekali tidak
 * berisi konten Checkpoint) — WAJIB direview Aye, lihat catatan di tiap
 * method seedModuleN().
 *
 * PENYIMPANGAN TAMBAHAN yang ditemukan saat Modul 2-4 (generalisasi dari
 * temuan Modul 1 di atas — dikonfirmasi berlaku untuk SEMUA unit ber-
 * evaluation_type essay/practice, bukan cuma yang eksplisit disebut "banyak
 * skenario"):
 * - Unit 2.3 dan 2.5: instruksi asli meminta output tabel isian (practice)
 *   DAN esai reflektif terpisah sebagai 2 baris UnitEvaluation. Tapi kedua
 *   unit ini evaluation_type=practice, yang SAMA-SAMA lewat submitFreeText()
 *   (satu textarea, cuma baca evaluations()->first()) seperti Unit 1.2/1.4 —
 *   bukan submitQuiz() yang loop semua baris. Digabung jadi SATU baris per
 *   unit (tabel isian + pertanyaan reflektif jadi satu question_text, satu
 *   jawaban), sama seperti Modul 1.
 * - Field `options` TIDAK PERNAH dirender untuk question_type=essay/practice
 *   (blade cuma pakai `question_text` + satu textarea di jalur ini). Template
 *   6-poin Unit 2.4 karena itu ditulis langsung di `question_text`, BUKAN di
 *   `options` (walau instruksi asli menyebut "options berisi daftar 6
 *   pertanyaan template") — kalau disimpan di `options` templatenya tidak
 *   akan pernah terlihat user.
 * - Unit 4.2 (ordering 8 kartu, salah satunya "paralel/tidak ikut diurutkan"):
 *   `correct_answer` untuk question_type=ordering HARUS array teks lengkap
 *   dalam urutan benar (dibandingkan `===` ke `quizAnswers` yang isinya teks,
 *   bukan array indeks) — instruksi asli minta "correct_answer berisi urutan
 *   indeks [3,5,4,7,1,6,2]", tapi itu tidak match cara UnitEvaluation
 *   membandingkan jawaban. Kartu ke-8 (Project Manager, paralel) DIKELUARKAN
 *   dari 7 kartu yang diurutkan (hanya 7 kartu berurutan yang py correct_answer
 *   pasti), sifat paralelnya dijelaskan sebagai catatan di `question_text`
 *   supaya tidak hilang tapi juga tidak merusak grading strict-equality.
 *
 * FLAG UNTUK AYE (Modul 5, Unit 5.5): prompt sendiri sudah menandai ini
 * sebagai keputusan Celo yang perlu dikonfirmasi — Unit 5.5 "Praktik Menulis
 * Histori yang Bermakna" di-seed sebagai `unit_type=concept` (10 poin),
 * BUKAN `practice` (15 poin) seperti Unit 5.2-5.4, karena outputnya evaluatif/
 * menulis ulang pesan commit (tidak menjalankan command Git sungguhan di
 * terminal). Kalau Aye menilai unit ini tetap harus `practice`/15 karena
 * masih bagian rangkaian skill Git, tinggal diubah lewat admin panel
 * "Kelola Unit" -- tidak perlu re-seed.
 *
 * Unit 6.3 (Ragam Visualisasi Data): instruksi asli minta "3 skenario Output
 * = 3 baris practice" -- digabung jadi SATU baris (generalisasi Koreksi
 * Wajib poin 3 yang sama, evaluation_type=practice cuma pernah tampil satu
 * baris pertama), sama seperti Unit 2.3/2.5 di Modul 2.
 *
 * Modul 8 (Keamanan): batasan konten eksplisit dari prompt diikuti persis --
 * TIDAK ADA payload/string serangan asli ditambahkan di mana pun (SQL
 * Injection/XSS), cuma kode YANG RENTAN persis seperti sumber. Unit 8.2 dan
 * 8.4 digabung jadi SATU baris masing-masing (2 potongan kode / 4 kondisi
 * sekaligus), generalisasi Koreksi Wajib poin 3 yang sama.
 *
 * Modul 10, Unit 10.2 dan 10.3: `estimated_minutes` diisi jauh lebih besar
 * (90 dan 45) dibanding unit lain -- keputusan sendiri, karena keduanya
 * benar-benar membangun+deploy proyek/portofolio nyata (bukan sesi belajar
 * 15-25 menit), sesuai instruksi modul ini sendiri ("eksekusi nyata").
 * Sesuai catatan eksplisit prompt, unit 10.2/10.3 TIDAK dihubungkan ke tabel
 * `projects`/`project_ideas` milik Eksekusi -- murni pelaporan URL lewat
 * jawaban practice/essay di Eksplorasi.
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
            'title' => 'Fondasi Software Development dan Web',
            'description' => 'Membangun kerangka berpikir dasar tentang pengembangan software secara umum, menyempit ke web development, lalu masuk ke mekanisme teknis di baliknya.',
            'level_number' => 1,
        ]);

        $this->seedUnit11($module);
        $this->seedUnit12($module);
        $this->seedUnit13($module);
        $this->seedUnit14($module);

        // Checkpoint Modul 1 -- KONTEN BARU disusun oleh Celo, blueprint sama
        // sekali tidak berisi Checkpoint/intermezo untuk modul ini. WAJIB
        // direview Aye sebelum dianggap final, bukan bagian asli blueprint.
        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Saya memahami perbedaan aplikasi web dan aplikasi desktop, serta posisi web development sebagai cabang spesifik dari software development.',
                'Saya memahami konsep SDLC dan bisa membedakan penerapannya di Waterfall, Agile, dan RAD sesuai karakteristik proyek.',
                'Saya memahami arsitektur client-server dan mekanisme request-response, serta bisa memperkirakan di titik mana masalah aplikasi biasanya terjadi.',
                'Saya bisa mengenali tiga ciri kode yang rapuh: dependency yang tidak jelas, tidak ada validasi input, dan struktur yang berantakan.',
            ],
            'intermezo_questions' => [
                'Dari seluruh Modul 1, konsep mana yang paling mengubah cara pandangmu terhadap hasil kerja tools AI dalam membuat aplikasi web? Jelaskan.',
                'Kalau kamu diminta menjelaskan ke teman yang belum paham sama sekali, kenapa penting memahami dasar software development sebelum memakai AI untuk membuat aplikasi, apa yang akan kamu katakan?',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'How does the Internet work?',
            'url' => 'https://developer.mozilla.org/en-US/docs/Learn_web_development/Howto/Web_mechanics/How_does_the_Internet_work',
            'source_name' => 'MDN Web Docs',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'What is a web server?',
            'url' => 'https://developer.mozilla.org/en-US/docs/Learn_web_development/Howto/Web_mechanics/What_is_a_web_server',
            'source_name' => 'MDN Web Docs',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Full Stack Roadmap',
            'url' => 'https://roadmap.sh/full-stack',
            'source_name' => 'roadmap.sh',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Agile Coach: Waterfall vs Agile',
            'url' => 'https://www.atlassian.com/agile',
            'source_name' => 'Atlassian',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'freeCodeCamp — Software Development Curriculum',
            'url' => 'https://www.freecodecamp.org/learn',
            'source_name' => 'freeCodeCamp',
        ]);
    }

    /**
     * §2.1.1 -- Apa Itu Software Development, dan Bagaimana Web Development
     * Menjadi Bagiannya.
     */
    private function seedUnit11(Module $module): void
    {
        $this->units['1.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Apa Itu Software Development, dan Bagaimana Web Development Menjadi Bagiannya',
            'content' => '',
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
        ]);

        $order = 1;

        $this->units['1.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Software development adalah proses merancang, membangun, menguji, dan memelihara sebuah perangkat lunak agar bisa menyelesaikan masalah atau kebutuhan tertentu. Cakupannya luas, mulai dari aplikasi mobile, sistem operasi, program desktop, sampai perangkat lunak yang tertanam di mesin industri. Semua bentuk software itu punya satu kesamaan, yaitu ada proses berpikir dan bekerja yang mengubah kebutuhan menjadi sesuatu yang bisa dijalankan komputer.',
            ],
        ]);

        $this->units['1.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Web development adalah salah satu cabang dari software development yang secara spesifik berfokus pada perangkat lunak yang berjalan dan diakses lewat web browser. Bedanya dengan cabang lain terletak pada tiga hal:',
            ],
        ]);

        $this->units['1.1']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'unordered',
                'items' => [
                    'Cara distribusinya: sebuah aplikasi web tidak perlu diinstal, cukup diakses lewat alamat tertentu.',
                    'Lingkungan eksekusinya: kode web dijalankan di dua sisi berbeda, yaitu di perangkat milik pengguna (browser) dan di server milik penyedia layanan.',
                    'Sifatnya yang selalu terhubung ke jaringan, berbeda dari aplikasi desktop yang bisa berjalan sepenuhnya offline.',
                ],
            ],
        ]);

        $this->units['1.1']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Ketika seseorang memakai tools AI untuk membuat aplikasi web hanya dengan mengetik prompt, tools tersebut sebenarnya sedang menyusun banyak keputusan teknis sekaligus, keputusan tentang bagaimana kode akan didistribusikan, di mana logika akan dijalankan, dan bagaimana koneksi jaringan akan ditangani. Kalau penggunanya tidak paham bahwa web development punya karakteristik berbeda dari software development pada umumnya, dia tidak akan tahu harus mengevaluasi apa dari hasil yang diberikan AI tersebut. Memahami posisi web development sebagai cabang spesifik dari software development adalah langkah pertama untuk bisa menilai, bukan sekadar menerima, hasil kerja siapa pun atau apa pun yang membangun aplikasi.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Yang membedakan aplikasi web dari aplikasi desktop pada umumnya adalah:',
            'options' => [
                'Aplikasi web selalu gratis, aplikasi desktop selalu berbayar',
                'Aplikasi web perlu diinstal terlebih dulu, aplikasi desktop tidak',
                'Aplikasi web dijalankan di dua sisi (browser dan server) serta butuh koneksi jaringan, aplikasi desktop bisa berjalan penuh secara offline',
                'Aplikasi web tidak punya tampilan antarmuka',
            ],
            'correct_answer' => 'Aplikasi web dijalankan di dua sisi (browser dan server) serta butuh koneksi jaringan, aplikasi desktop bisa berjalan penuh secara offline',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Software development adalah:',
            'options' => [
                'Cabang khusus dari web development yang berfokus pada database',
                'Proses merancang, membangun, menguji, dan memelihara perangkat lunak untuk menyelesaikan kebutuhan tertentu',
                'Istilah lain untuk pemrograman bahasa JavaScript',
                'Proses yang hanya berlaku untuk aplikasi mobile',
            ],
            'correct_answer' => 'Proses merancang, membangun, menguji, dan memelihara perangkat lunak untuk menyelesaikan kebutuhan tertentu',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Manakah pernyataan yang tepat tentang hubungan software development dan web development?',
            'options' => [
                'Keduanya adalah bidang yang sama sekali terpisah dan tidak berkaitan',
                'Web development adalah induk dari software development',
                'Web development adalah salah satu cabang spesifik dari software development',
                'Software development hanya berlaku untuk sistem operasi',
            ],
            'correct_answer' => 'Web development adalah salah satu cabang spesifik dari software development',
            'sort_order' => 3,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Sebuah program pengolah data gaji karyawan diinstal di komputer kantor dan tetap bisa dipakai meski internet mati. Program ini tergolong:',
            'options' => [
                'Aplikasi web, karena mengolah data',
                'Aplikasi desktop, karena tidak butuh distribusi lewat alamat dan tidak wajib terhubung jaringan',
                'Aplikasi web, karena semua program modern adalah aplikasi web',
                'Tidak tergolong software sama sekali',
            ],
            'correct_answer' => 'Aplikasi desktop, karena tidak butuh distribusi lewat alamat dan tidak wajib terhubung jaringan',
            'sort_order' => 4,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Kenapa penting memahami bahwa web development adalah cabang spesifik, bukan sekadar sinonim dari software development?',
            'options' => [
                'Supaya bisa mengklaim diri sebagai ahli IT secara umum',
                'Supaya tahu keputusan teknis apa (distribusi, lingkungan eksekusi, ketergantungan jaringan) yang perlu dievaluasi saat menilai hasil kerja, termasuk hasil AI-generate',
                'Karena web development akan segera digantikan sepenuhnya oleh AI',
                'Karena tidak ada bedanya, ini hanya soal istilah',
            ],
            'correct_answer' => 'Supaya tahu keputusan teknis apa (distribusi, lingkungan eksekusi, ketergantungan jaringan) yang perlu dievaluasi saat menilai hasil kerja, termasuk hasil AI-generate',
            'sort_order' => 5,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.1']->id,
            'question_type' => 'essay',
            'question_text' => 'Sebuah aplikasi pencatat pengeluaran pribadi bisa diakses lewat browser di alamat catatan-uangku.com tanpa instalasi apapun, tapi juga tersedia versi yang bisa diunduh dan dipasang di laptop. Menurutmu, versi mana yang tergolong aplikasi web, dan versi mana yang tergolong aplikasi desktop? Jelaskan alasanmu berdasarkan tiga pembeda yang sudah dipelajari (distribusi, lingkungan eksekusi, ketergantungan jaringan).',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 6,
        ]);
    }

    /**
     * §2.1.2 -- Metode Pengembangan Software: Konsep SDLC dan Penerapannya
     * di Waterfall, Agile, dan RAD.
     */
    private function seedUnit12(Module $module): void
    {
        $this->units['1.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Metode Pengembangan Software: Konsep SDLC dan Penerapannya di Waterfall, Agile, dan RAD',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'essay',
            'prerequisite_unit_id' => $this->units['1.1']->id,
        ]);

        $order = 1;

        $this->units['1.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Software Development Life Cycle, atau SDLC, adalah konsep tahapan yang dilalui dalam membangun sebuah perangkat lunak, mulai dari perencanaan sampai perangkat lunak itu dipelihara setelah dipakai. Komponen fundamentalnya ada lima:',
            ],
        ]);

        $this->units['1.2']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'ordered',
                'items' => [
                    'Perencanaan — menentukan apa yang perlu dibangun dan kenapa.',
                    'Analisis kebutuhan — menggali detail kebutuhan dari pengguna atau pemilik proyek.',
                    'Desain — merancang bagaimana sistem akan bekerja sebelum ditulis kodenya.',
                    'Implementasi — menulis kode sungguhan.',
                    'Pemeliharaan — memperbaiki serta mengembangkan sistem setelah dipakai.',
                ],
            ],
        ]);

        $this->units['1.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'SDLC sendiri adalah konsep, bukan cara kerja yang bisa langsung dipraktikkan begitu saja. Cara kerja nyata yang jadi penerapan konsep ini disebut metode pengembangan, dan tiap metode punya cara berbeda dalam menyusun kelima komponen SDLC tadi.',
            ],
        ]);

        $this->units['1.2']->contentBlocks()->create([
            'type' => 'heading',
            'order' => $order++,
            'content' => ['text' => 'Waterfall', 'level' => 3],
        ]);

        $this->units['1.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Waterfall menyusun kelima komponen itu secara berurutan dan linear, satu tahap harus selesai penuh sebelum tahap berikutnya dimulai. Metode ini cocok untuk proyek dengan kebutuhan yang sudah sangat jelas sejak awal dan jarang berubah, tapi berisiko besar kalau ternyata ada kesalahan pemahaman kebutuhan yang baru ketahuan di tahap akhir.',
            ],
        ]);

        $this->units['1.2']->contentBlocks()->create([
            'type' => 'heading',
            'order' => $order++,
            'content' => ['text' => 'Agile', 'level' => 3],
        ]);

        $this->units['1.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Agile menyusun kelima komponen itu secara berulang dalam siklus-siklus pendek yang disebut sprint, biasanya satu sampai empat minggu. Setiap sprint menghasilkan bagian kecil dari sistem yang bisa langsung dievaluasi, sehingga kesalahan pemahaman kebutuhan bisa ketahuan lebih cepat. Metode ini cocok untuk proyek yang kebutuhannya masih bisa berubah seiring proses berjalan.',
            ],
        ]);

        $this->units['1.2']->contentBlocks()->create([
            'type' => 'heading',
            'order' => $order++,
            'content' => ['text' => 'Rapid Application Development (RAD)', 'level' => 3],
        ]);

        $this->units['1.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Rapid Application Development, atau RAD, menekankan pembuatan prototipe cepat yang terus diuji dan diperbaiki bersama pengguna, dengan siklus umpan balik yang jauh lebih cepat dari Agile. Metode ini cocok untuk proyek yang butuh validasi ide secepat mungkin, meski konsekuensinya dokumentasi formal sering dikorbankan demi kecepatan.',
            ],
        ]);

        $this->units['1.2']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Anggota yang paham konsep SDLC saja, tapi tidak paham metode penerapannya, akan kesulitan menjelaskan kenapa timnya bekerja dengan cara tertentu, atau kenapa satu pendekatan cocok untuk satu proyek tapi tidak cocok untuk proyek lain.',
            ],
        ]);

        // Ketiga skenario digabung jadi SATU baris (lihat catatan penyimpangan
        // di docblock kelas) supaya submitFreeText() -- yang cuma pernah
        // membaca evaluations()->first() -- benar-benar menampilkan dan bisa
        // menerima jawaban untuk ketiga skenario, bukan cuma Skenario A.
        UnitEvaluation::create([
            'unit_id' => $this->units['1.2']->id,
            'question_type' => 'essay',
            'question_text' => "Baca ketiga skenario proyek berikut, lalu jawab pertanyaan di tiap skenario. Jawaban ditulis di satu kolom teks, panjang bebas, minimal 3 kalimat per skenario.\n\n".
                "Skenario A. Sebuah instansi pemerintah memesan sistem pencatatan arsip surat masuk dan keluar. Kebutuhannya sudah ditentukan lengkap lewat dokumen resmi sejak awal, ada aturan baku yang tidak akan berubah dalam waktu dekat, dan proyek harus melewati proses audit tahap demi tahap sebelum lanjut ke tahap berikutnya.\n".
                "Pertanyaan A: Metode pengembangan mana yang paling cocok untuk skenario ini? Jelaskan alasannya berdasarkan karakteristik metode yang sudah dipelajari.\n\n".
                "Skenario B. Sebuah startup ingin membangun aplikasi belanja online, tapi tim produk masih sering mengubah fitur berdasarkan masukan pengguna yang terus masuk tiap minggu. Mereka ingin bisa merilis pembaruan kecil secara rutin tanpa menunggu seluruh aplikasi selesai.\n".
                "Pertanyaan B: Metode pengembangan mana yang paling cocok untuk skenario ini? Jelaskan alasannya.\n\n".
                "Skenario C. Sebuah tim ingin memvalidasi apakah ide aplikasi pemesanan laundry akan diminati pasar, sebelum menginvestasikan banyak waktu dan biaya. Mereka butuh prototipe yang bisa dicoba calon pengguna dalam hitungan hari, bukan bulan, dan siap merombak total kalau ternyata idenya kurang tepat.\n".
                'Pertanyaan C: Metode pengembangan mana yang paling cocok untuk skenario ini? Jelaskan alasannya.',
            'options' => null,
            'correct_answer' => 'Skenario A cocok Waterfall karena kebutuhan sudah jelas dan tidak berubah, prosesnya butuh linearitas dan audit bertahap. Skenario B cocok Agile karena kebutuhan masih berubah dan butuh evaluasi bertahap lewat sprint. Skenario C cocok RAD karena fokus utamanya validasi ide secepat mungkin lewat prototipe, dokumentasi formal bukan prioritas.',
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.1.3 -- Arsitektur Client-Server dan Mekanisme Request-Response.
     */
    private function seedUnit13(Module $module): void
    {
        $this->units['1.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Arsitektur Client-Server dan Mekanisme Request-Response',
            'content' => '',
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['1.2']->id,
        ]);

        $order = 1;

        $this->units['1.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Arsitektur client-server adalah cara mengatur peran dalam sebuah sistem, di mana ada dua pihak dengan tanggung jawab berbeda. Client adalah pihak yang meminta sesuatu, biasanya berupa browser di perangkat pengguna. Server adalah pihak yang menyediakan dan memproses permintaan itu, biasanya berupa komputer yang menjalankan program khusus dan selalu menyala untuk melayani permintaan yang masuk.',
            ],
        ]);

        $this->units['1.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Mekanisme request-response adalah cara kedua pihak itu saling berkomunikasi. Prosesnya dimulai ketika client mengirim permintaan atau request, misalnya saat seseorang mengetik alamat website di browser. Request itu berisi informasi tentang apa yang diminta, dikirim lewat jaringan internet menuju server yang dituju. Server menerima request itu, memprosesnya (bisa berarti mengambil data dari database, menjalankan logika tertentu, atau sekadar mengambil file yang diminta), lalu mengirim balik hasilnya berupa response. Response inilah yang kemudian ditampilkan browser sebagai halaman web yang terlihat oleh pengguna.',
            ],
        ]);

        $this->units['1.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Satu siklus request-response ini bisa terjadi berkali-kali dalam satu halaman web. Bukan cuma satu kali saat halaman pertama kali dibuka, tapi juga tiap kali ada aksi seperti klik tombol yang memuat data baru, atau mengisi form yang harus dikirim ke server.',
            ],
        ]);

        $this->units['1.3']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Ketika sebuah aplikasi hasil AI-generate terasa lambat, sering error tanpa jelas kenapa, atau data yang ditampilkan tidak sesuai harapan, akar masalahnya hampir selalu ada di suatu titik dalam siklus request-response ini, entah requestnya salah bentuk, prosesnya di server bermasalah, atau responsenya tidak ditangani dengan benar oleh client. Tanpa memahami arsitektur ini, seseorang tidak akan tahu di titik mana harus mulai mencari masalah.',
            ],
        ]);

        $titikMasalah = [
            'Request yang dikirim client salah bentuk atau tidak lengkap.',
            'Server lambat atau gagal memproses permintaan.',
            'Response dari server tidak sampai atau tidak ditangani dengan benar oleh client.',
            'Tidak ada masalah di siklus request-response, masalah ada di tampilan visual semata.',
        ];

        UnitEvaluation::create([
            'unit_id' => $this->units['1.3']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Skenario 1: Seorang pengguna mengisi form pendaftaran, menekan tombol "Daftar", tapi halaman diam saja tanpa respons apapun selama lebih dari satu menit, sebelum akhirnya muncul pesan "waktu habis". Kemungkinan besar titik masalahnya adalah:',
            'options' => $titikMasalah,
            'correct_answer' => 'Server lambat atau gagal memproses permintaan.',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.3']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Skenario 2: Seorang pengguna berhasil login, tapi daftar riwayat transaksinya tidak muncul sama sekali di halaman, padahal di database transaksi itu benar-benar ada. Kemungkinan besar titik masalahnya adalah:',
            'options' => $titikMasalah,
            'correct_answer' => 'Response dari server tidak sampai atau tidak ditangani dengan benar oleh client.',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.3']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Skenario 3: Saat mengisi form dengan format nomor telepon yang salah, sistem langsung menampilkan error "data tidak valid" sebelum sempat terkirim ke server. Kemungkinan besar titik masalahnya adalah:',
            'options' => $titikMasalah,
            'correct_answer' => 'Request yang dikirim client salah bentuk atau tidak lengkap.',
            'sort_order' => 3,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['1.3']->id,
            'question_type' => 'essay',
            'question_text' => 'Dari ketiga skenario di atas, menurutmu skenario mana yang paling sulit untuk didiagnosis oleh pengguna awam yang tidak paham arsitektur client-server? Jelaskan kenapa.',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 4,
        ]);
    }

    /**
     * §2.1.4 -- Evaluasi Kualitas Kode: Membaca Ciri Aplikasi yang Rapuh.
     */
    private function seedUnit14(Module $module): void
    {
        $this->units['1.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Evaluasi Kualitas Kode: Membaca Ciri Aplikasi yang Rapuh',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['1.3']->id,
        ]);

        $order = 1;

        $this->units['1.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Kode yang rapuh adalah kode yang mungkin bisa berjalan sekarang, tapi berisiko besar bermasalah begitu ada perubahan kecil, dipakai lebih banyak orang, atau menghadapi input yang tidak terduga. Ciri-ciri ini penting dikenali sejak dini, karena hasil AI-generate sering terlihat berfungsi di percobaan pertama, padahal menyimpan kerapuhan yang baru terlihat belakangan.',
            ],
        ]);

        $this->units['1.4']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'ordered',
                'items' => [
                    'Ciri pertama: dependency yang tidak jelas, yaitu kondisi di mana satu bagian kode bergantung pada bagian lain dengan cara yang tersembunyi atau tidak didokumentasikan, sehingga mengubah satu bagian bisa merusak bagian lain tanpa terlihat hubungannya secara langsung.',
                    'Ciri kedua: tidak adanya validasi input, yaitu kode yang langsung memproses apa pun yang dimasukkan pengguna tanpa memeriksa dulu apakah data itu masuk akal atau aman. Kode semacam ini akan berjalan normal selama penggunanya memasukkan data yang wajar, tapi rentan error atau bahkan disalahgunakan begitu ada input yang tidak sesuai ekspektasi.',
                    'Ciri ketiga: struktur yang berantakan, misalnya satu fungsi yang mengerjakan terlalu banyak hal sekaligus, penamaan variabel yang tidak jelas maksudnya, atau logika yang diulang-ulang di banyak tempat alih-alih ditulis satu kali dan dipakai ulang. Struktur semacam ini membuat kode sulit dipahami, sulit diperbaiki, dan mudah menimbulkan kesalahan baru setiap kali disentuh.',
                ],
            ],
        ]);

        $this->units['1.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Kemampuan mengenali ketiga ciri ini adalah keterampilan evaluasi, bukan keterampilan menulis kode dari nol. Anggota tidak harus bisa menulis aplikasi kompleks di unit ini, tapi harus bisa melihat sebuah potongan kode dan menilai, apakah ini kode yang sehat atau kode yang menyimpan masalah.',
            ],
        ]);

        // Ketiga potongan kode digabung jadi SATU baris (lihat catatan
        // penyimpangan di docblock kelas, keputusan sama dengan Unit 1.2) --
        // question_text bukan markdown (dikonfirmasi ke blade, {{ }} polos),
        // jadi kode disertakan apa adanya dengan indentasi rapi tanpa fence.
        UnitEvaluation::create([
            'unit_id' => $this->units['1.4']->id,
            'question_type' => 'practice',
            'question_text' => "Ketiga potongan kode berikut masing-masing sengaja mengandung satu ciri kerapuhan. Tandai ciri yang kamu temukan di tiap potongan dan jelaskan alasannya, satu per satu, di kolom jawaban.\n\n".
                "Potongan Kode 1:\n".
                "function hitung(a, b, c) {\n".
                "  let x = a + b;\n".
                "  let hasilAkhir = x * c - a + b / x + c;\n".
                "  return hasilAkhir;\n".
                "}\n".
                "Pertanyaan 1: Ciri kerapuhan apa yang paling menonjol di potongan ini? Jelaskan.\n\n".
                "Potongan Kode 2:\n".
                "function simpanUmur(inputUmur) {\n".
                "  const umur = inputUmur;\n".
                "  database.simpan(\"umur_pengguna\", umur);\n".
                "  return \"Data tersimpan\";\n".
                "}\n".
                "Pertanyaan 2: Ciri kerapuhan apa yang paling menonjol di potongan ini? Jelaskan.\n\n".
                "Potongan Kode 3:\n".
                "function updateHarga(produk) {\n".
                "  produk.harga = produk.harga * diskonAktif;\n".
                "  cekStokGudangUtama(produk);\n".
                "}\n".
                'Pertanyaan 3: Ciri kerapuhan apa yang paling menonjol di potongan ini, dan kenapa berbahaya kalau cekStokGudangUtama diubah atau dihapus di bagian kode lain? Jelaskan.',
            'options' => null,
            'correct_answer' => 'Kode 1: struktur berantakan, penamaan variabel (x, hasilAkhir, a, b, c) tidak menjelaskan maksudnya, logika perhitungan bercampur tanpa pemisahan yang jelas sehingga sulit dipahami maksud bisnisnya. Kode 2: tidak ada validasi input, nilai inputUmur langsung disimpan tanpa memeriksa apakah itu angka, apakah masuk akal (misalnya bukan negatif atau ribuan tahun), atau apakah kosong. Kode 3: dependency yang tidak jelas, fungsi updateHarga diam-diam bergantung pada diskonAktif (variabel dari luar fungsi) dan pada cekStokGudangUtama, tanpa dokumentasi bahwa perubahan harga selalu memicu pengecekan stok, sehingga mengubah salah satu bagian bisa merusak bagian lain secara tidak terlihat.',
            'sort_order' => 1,
        ]);
    }

    private function seedModule2(): void
    {
        $module = Module::create([
            'order_number' => 2,
            'title' => 'Bahasa Pemrograman, Framework, dan Tech Stack',
            'description' => 'Menjelaskan hubungan konseptual bahasa pemrograman dan framework sebagai satu rangkaian terpadu, lalu masuk ke detail spesifik tiap kategori.',
            'level_number' => 1,
        ]);

        $this->seedUnit21($module);
        $this->seedUnit22($module);
        $this->seedUnit23($module);
        $this->seedUnit24($module);
        $this->seedUnit25($module);

        // Checkpoint Modul 2 -- KONTEN BARU disusun oleh Celo, WAJIB direview Aye.
        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Saya memahami relasi wajib satu arah antara bahasa pemrograman dan framework.',
                'Saya bisa memetakan bahasa pemrograman populer ke wilayah kekuatannya masing-masing.',
                'Saya bisa mengelompokkan framework populer berdasarkan kategori (frontend/backend/fullstack/mobile) dan bahasa induknya.',
                'Saya memahami bagaimana komponen tech stack (frontend, backend, database, hosting) saling melengkapi.',
                'Saya bisa mengenali ciri sumber belajar yang kredibel dibanding yang usang atau tidak jelas kredibilitasnya.',
            ],
            'intermezo_questions' => [
                'Setelah Modul 2, tech stack apa yang paling ingin kamu dalami lebih jauh, dan kenapa?',
                'Bagaimana pemahaman relasi bahasa-framework di modul ini mengubah caramu menilai proyek yang dibangun lewat AI-generate?',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Frontend Roadmap',
            'url' => 'https://roadmap.sh/frontend',
            'source_name' => 'roadmap.sh',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Backend Roadmap',
            'url' => 'https://roadmap.sh/backend',
            'source_name' => 'roadmap.sh',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'PHP Roadmap',
            'url' => 'https://roadmap.sh/php',
            'source_name' => 'roadmap.sh',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'freeCodeCamp — full curriculum',
            'url' => 'https://www.freecodecamp.org/learn',
            'source_name' => 'freeCodeCamp',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'MDN — Programming languages overview',
            'url' => 'https://developer.mozilla.org/en-US/docs/Learn_web_development',
            'source_name' => 'MDN Web Docs',
        ]);
    }

    /**
     * §2.2.1 -- Pengantar Konseptual: Bahasa Pemrograman, Framework, dan Relasinya.
     */
    private function seedUnit21(Module $module): void
    {
        $this->units['2.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Pengantar Konseptual: Bahasa Pemrograman, Framework, dan Relasinya',
            'content' => '',
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_matching',
        ]);

        $order = 1;

        $this->units['2.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Bahasa pemrograman adalah alat untuk menuliskan instruksi yang bisa dijalankan komputer. Setiap bahasa punya sintaks (aturan penulisan) sendiri, tapi semuanya sama-sama bertugas menerjemahkan logika manusia menjadi sesuatu yang bisa dieksekusi mesin. Bahasa pemrograman berbeda dari markup language seperti `HTML` dan stylesheet language seperti `CSS`, karena bahasa pemrograman mampu membuat keputusan lewat logika (jika begini maka begitu) dan melakukan perhitungan, sedangkan HTML dan CSS hanya menyusun struktur dan tampilan tanpa kemampuan mengambil keputusan.',
            ],
        ]);

        $this->units['2.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Framework adalah kerangka kerja siap pakai yang dibangun di atas satu bahasa pemrograman tertentu, menyediakan struktur dan alat dasar sehingga developer tidak perlu membangun semuanya dari nol setiap kali memulai proyek.',
            ],
        ]);

        $this->units['2.1']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'info',
                'title' => 'Analogi',
                'body' => 'Bahasa pemrograman adalah bahan bakunya (kayu, semen, besi), sedangkan framework adalah rangka bangunan yang sudah setengah jadi, developer tinggal mengisi dan menyesuaikan sesuai kebutuhan proyek.',
            ],
        ]);

        $this->units['2.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Relasi antara keduanya bersifat wajib satu arah. Setiap framework butuh bahasa pemrograman sebagai dasarnya, tapi tidak setiap bahasa pemrograman butuh framework untuk dipakai. `React` dibangun di atas JavaScript, `Laravel` dibangun di atas PHP, `Django` dibangun di atas Python. Artinya, memahami sebuah framework mengharuskan pemahaman bahasa pemrogramannya lebih dulu.',
            ],
        ]);

        $this->units['2.1']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Anggota yang langsung loncat mempelajari framework tanpa memahami bahasa dasarnya akan kesulitan membaca error, kesulitan menyesuaikan kode di luar pola baku framework, dan cenderung hanya bisa menyalin-tempel tanpa mengerti kenapa sebuah kode bekerja.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.1']->id,
            'question_type' => 'matching',
            'question_text' => 'Cocokkan tiap framework di kolom kiri dengan bahasa pemrograman induknya di kolom kanan.',
            'options' => [
                'pairs' => [
                    ['left' => 'React', 'right' => 'JavaScript'],
                    ['left' => 'Laravel', 'right' => 'PHP'],
                    ['left' => 'Django', 'right' => 'Python'],
                    ['left' => 'Flutter', 'right' => 'Dart'],
                    ['left' => 'Ruby on Rails', 'right' => 'Ruby'],
                ],
            ],
            'correct_answer' => [
                'React' => 'JavaScript',
                'Laravel' => 'PHP',
                'Django' => 'Python',
                'Flutter' => 'Dart',
                'Ruby on Rails' => 'Ruby',
            ],
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.1']->id,
            'question_type' => 'essay',
            'question_text' => 'Jelaskan dengan bahasamu sendiri, kenapa seseorang tidak disarankan langsung belajar Laravel sebelum memahami dasar PHP terlebih dulu. Kaitkan jawabanmu dengan konsep relasi bahasa pemrograman dan framework yang sudah dipelajari.',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 2,
        ]);
    }

    /**
     * §2.2.2 -- Ragam Bahasa Pemrograman Populer dan Karakteristiknya.
     */
    private function seedUnit22(Module $module): void
    {
        $this->units['2.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Ragam Bahasa Pemrograman Populer dan Karakteristiknya',
            'content' => '',
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $this->units['2.1']->id,
        ]);

        $order = 1;

        $this->units['2.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Ada ratusan bahasa pemrograman, tapi hanya segelintir yang relevan untuk pemula memulai. Setiap bahasa punya "wilayah kekuatan" masing-masing, area di mana bahasa itu paling sering dan paling efektif dipakai.',
            ],
        ]);

        $this->units['2.2']->contentBlocks()->create([
            'type' => 'table',
            'order' => $order++,
            'content' => [
                'headers' => ['Bahasa', 'Wilayah Kekuatan'],
                'rows' => [
                    ['JavaScript', 'Satu-satunya bahasa yang berjalan native di browser, wajib untuk frontend web; lewat Node.js juga dipakai di backend.'],
                    ['Python', 'Sintaks mudah dibaca; populer di data science, machine learning, otomasi, dan backend web (Django/Flask).'],
                    ['Java', 'Aplikasi skala besar di perusahaan dan pengembangan Android versi lama.'],
                    ['Kotlin & Swift', 'Bahasa modern masing-masing untuk Android dan iOS.'],
                    ['PHP', "Meski sering dianggap \"kuno\", masih jadi tulang punggung banyak website termasuk WordPress, dipakai lewat framework Laravel."],
                    ['C & C++', 'Aplikasi yang butuh performa sangat tinggi seperti game engine dan sistem operasi.'],
                ],
            ],
        ]);

        $this->units['2.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Bahasa pemrograman juga bisa dibedakan berdasarkan seberapa dekat dengan bahasa mesin (low-level) atau dekat dengan bahasa manusia (high-level). Hampir seluruh bahasa yang relevan untuk pemula web development, seperti JavaScript, Python, dan PHP, tergolong high-level, artinya lebih mudah dibaca dan ditulis manusia.',
            ],
        ]);

        $this->units['2.2']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Memahami peta ini bukan berarti anggota harus menguasai semuanya. Tujuannya supaya anggota tahu bahasa mana yang relevan untuk tujuan spesifik yang ingin dicapai, bukan memilih bahasa secara acak atau ikut tren tanpa alasan jelas.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Bahasa pemrograman yang berjalan native di browser dan wajib untuk frontend web adalah:',
            'options' => ['Python', 'JavaScript', 'Java', 'C++'],
            'correct_answer' => 'JavaScript',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Seorang mahasiswa ingin fokus di bidang data science dan machine learning. Bahasa yang paling relevan untuk dipelajari adalah:',
            'options' => ['PHP', 'Swift', 'Python', 'C'],
            'correct_answer' => 'Python',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'PHP paling banyak dipakai untuk:',
            'options' => [
                'Pengembangan game dengan performa tinggi',
                'Backend website, termasuk lewat framework Laravel',
                'Aplikasi Android native',
                'Machine learning',
            ],
            'correct_answer' => 'Backend website, termasuk lewat framework Laravel',
            'sort_order' => 3,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.2']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Kotlin dan Swift adalah contoh bahasa yang masing-masing dipakai untuk:',
            'options' => [
                'Android dan iOS',
                'Frontend dan backend web',
                'Data science dan otomasi',
                'Sistem operasi dan game engine',
            ],
            'correct_answer' => 'Android dan iOS',
            'sort_order' => 4,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['2.2']->id,
            'question_type' => 'essay',
            'question_text' => 'Berdasarkan minatmu saat ini (frontend, backend, mobile, atau data), bahasa pemrograman apa yang paling relevan untuk kamu pelajari lebih dalam? Jelaskan alasanmu memilih bahasa itu.',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 5,
        ]);
    }

    /**
     * §2.2.3 -- Ragam Framework Populer dan Fungsinya per Kategori.
     */
    private function seedUnit23(Module $module): void
    {
        $this->units['2.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Ragam Framework Populer dan Fungsinya per Kategori',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['2.2']->id,
        ]);

        $order = 1;

        $this->units['2.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Framework bisa dikelompokkan berdasarkan bagian aplikasi yang ditanganinya. Framework frontend menangani apa yang dilihat dan berinteraksi langsung dengan pengguna di browser. Framework backend menangani logika di balik layar, pengolahan data, dan komunikasi dengan database. Ada juga framework fullstack yang menangani frontend dan backend sekaligus dalam satu ekosistem.',
            ],
        ]);

        $this->units['2.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Selain kategori frontend dan backend, ada juga framework mobile seperti Flutter dan React Native yang memungkinkan satu basis kode dipakai untuk membangun aplikasi Android dan iOS sekaligus.',
            ],
        ]);

        $this->units['2.3']->contentBlocks()->create([
            'type' => 'table',
            'order' => $order++,
            'content' => [
                'headers' => ['Framework', 'Kategori', 'Bahasa Induk'],
                'rows' => [
                    ['React', 'Frontend', 'JavaScript'],
                    ['Vue', 'Frontend', 'JavaScript'],
                    ['Svelte', 'Frontend', 'JavaScript'],
                    ['Laravel', 'Backend', 'PHP'],
                    ['Django', 'Backend', 'Python'],
                    ['Flask', 'Backend', 'Python'],
                    ['Express', 'Backend', 'JavaScript (Node.js)'],
                    ['Ruby on Rails', 'Backend', 'Ruby'],
                    ['Next.js', 'Fullstack', 'JavaScript (berbasis React)'],
                    ['Flutter', 'Mobile', 'Dart'],
                    ['React Native', 'Mobile', 'JavaScript'],
                ],
            ],
        ]);

        $this->units['2.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Setiap framework dalam kategori yang sama biasanya punya filosofi berbeda. React misalnya lebih fleksibel dan minim aturan baku (unopinionated), developer bebas menyusun strukturnya sendiri. Laravel dan Django sebaliknya lebih opinionated, sudah punya struktur folder dan konvensi baku yang harus diikuti, memudahkan tim besar bekerja konsisten tapi mengurangi kebebasan struktur.',
            ],
        ]);

        $this->units['2.3']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Memahami kategori dan filosofi framework ini penting supaya anggota bisa memilih framework yang tepat sesuai kebutuhan proyek, bukan sekadar memilih yang paling populer atau paling sering disebut di media sosial.',
            ],
        ]);

        // Tabel isian + esai reflektif DIGABUNG jadi satu baris (lihat catatan
        // penyimpangan di docblock kelas) -- evaluation_type=practice cuma
        // pernah lewat submitFreeText(), 1 textarea saja.
        UnitEvaluation::create([
            'unit_id' => $this->units['2.3']->id,
            'question_type' => 'practice',
            'question_text' => "Isi kategori (frontend/backend/fullstack/mobile) dan bahasa induk untuk lima framework berikut, lalu jawab pertanyaan reflektif di bawahnya dalam jawaban yang sama.\n\n".
                "Framework yang perlu diisi: Vue, Express, Next.js, React Native, Django.\n\n".
                'Pertanyaan reflektif: Jelaskan perbedaan antara framework yang unopinionated seperti React dan yang opinionated seperti Laravel atau Django. Menurutmu, untuk tim proyek dengan banyak anggota baru seperti Eksekusi WEBI-SPACE, framework tipe mana yang lebih menguntungkan? Jelaskan alasanmu.',
            'options' => null,
            'correct_answer' => 'Vue (frontend, JavaScript), Express (backend, JavaScript via Node.js), Next.js (fullstack, JavaScript berbasis React), React Native (mobile, JavaScript), Django (backend, Python). Bagian esai reflektif dinilai kualitatif, tidak ada kunci pasti.',
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.2.4 -- Arsitektur Tech Stack: Bagaimana Komponen Saling Melengkapi.
     */
    private function seedUnit24(Module $module): void
    {
        $this->units['2.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Arsitektur Tech Stack: Bagaimana Komponen Saling Melengkapi',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['2.3']->id,
        ]);

        $order = 1;

        $this->units['2.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Tech stack adalah kombinasi teknologi yang dipakai bersama untuk membangun satu aplikasi secara utuh, mulai dari tampilan yang dilihat pengguna sampai tempat data disimpan. Sebuah tech stack yang lengkap biasanya terdiri dari empat komponen utama:',
            ],
        ]);

        $this->units['2.4']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'ordered',
                'items' => [
                    'Frontend — menangani tampilan dan interaksi pengguna.',
                    'Backend — menangani logika dan pemrosesan data.',
                    'Database — tempat data disimpan secara permanen.',
                    'Infrastruktur/hosting — tempat aplikasi dijalankan agar bisa diakses lewat internet.',
                ],
            ],
        ]);

        $this->units['2.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Komponen-komponen ini saling melengkapi lewat mekanisme komunikasi yang jelas. Frontend mengirim request ke backend, backend memproses permintaan itu dan berinteraksi dengan database untuk mengambil atau menyimpan data, lalu backend mengirim response kembali ke frontend untuk ditampilkan ke pengguna. Contoh tech stack yang dikenal adalah `MERN` (MongoDB, Express, React, Node.js) dan `LAMP` (Linux, Apache, MySQL, PHP), masing-masing punya kombinasi komponen yang sudah terbukti bekerja baik bersama.',
            ],
        ]);

        $this->units['2.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Memilih tech stack bukan sekadar memilih komponen yang populer satu per satu, tapi memastikan seluruh komponen bisa berkomunikasi dan saling melengkapi dengan lancar. Sebuah frontend React yang canggih tidak ada artinya kalau tidak dipasangkan dengan backend dan database yang bisa diajak bekerja sama secara efisien.',
            ],
        ]);

        $this->units['2.4']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'WEBI-SPACE sendiri memakai kombinasi Laravel, Livewire, Alpine.js, dan MySQL, sebuah contoh nyata tech stack yang saling melengkapi dan bisa dipelajari langsung dari proyek yang sedang berjalan di divisi.',
            ],
        ]);

        // Template 6 poin ditulis langsung di question_text (lihat catatan
        // penyimpangan di docblock kelas soal field `options` tidak pernah
        // dirender untuk essay/practice).
        UnitEvaluation::create([
            'unit_id' => $this->units['2.4']->id,
            'question_type' => 'practice',
            'question_text' => "Buat rancangan pemilihan stack untuk sebuah ide aplikasi sederhana pilihanmu sendiri (misalnya aplikasi to-do list, aplikasi catatan, atau aplikasi galeri foto). Isi template berikut dalam satu jawaban:\n\n".
                "1. Nama dan deskripsi singkat aplikasi (1-2 kalimat)\n".
                "2. Komponen frontend yang dipilih, beserta alasan (framework atau vanilla HTML/CSS/JS)\n".
                "3. Komponen backend yang dipilih, beserta alasan\n".
                "4. Database yang dipilih, beserta alasan\n".
                "5. Diagram sederhana alur komunikasi antar komponen (boleh berupa deskripsi teks bertahap, misalnya \"pengguna klik tombol A, frontend kirim request ke backend endpoint B, backend ambil data dari tabel C, data dikirim balik dan ditampilkan di halaman D\")\n".
                "6. Satu risiko atau tantangan yang mungkin muncul dari kombinasi stack yang dipilih\n\n".
                'Dokumen ini dinilai berdasarkan konsistensi dan kejelasan alasan, bukan berdasarkan stack mana yang "benar", karena tidak ada satu stack yang mutlak benar untuk semua kasus.',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.2.5 -- Kurasi Sumber Belajar Kredibel per Stack.
     */
    private function seedUnit25(Module $module): void
    {
        $this->units['2.5'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 5,
            'title' => 'Kurasi Sumber Belajar Kredibel per Stack',
            'content' => '',
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['2.4']->id,
        ]);

        $order = 1;

        $this->units['2.5']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Salah satu keterampilan yang sering diabaikan pemula adalah kemampuan memilih sumber belajar yang kredibel. Internet penuh dengan tutorial, sebagian ditulis dengan baik dan terus diperbarui, sebagian lain sudah usang atau bahkan mengandung praktik yang tidak lagi direkomendasikan industri.',
            ],
        ]);

        $this->units['2.5']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'ordered',
                'items' => [
                    'Sumber resmi dari pembuat teknologi itu sendiri (dokumentasi resmi) — selalu menjadi rujukan paling akurat, meskipun kadang terasa lebih teknis dibanding tutorial pihak ketiga.',
                    'Tanggal publikasi atau tanggal pembaruan terakhir — teknologi web berkembang cepat, tutorial berumur lebih dari dua sampai tiga tahun berisiko mengajarkan cara yang sudah ditinggalkan.',
                    'Reputasi platform atau penulisnya — platform yang dikenal luas seperti dokumentasi resmi, MDN Web Docs, atau kanal yang konsisten diakui komunitas developer, biasanya lebih bisa dipercaya dibanding blog perorangan yang tidak jelas kredibilitasnya.',
                ],
            ],
        ]);

        $this->units['2.5']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Kemampuan memilah sumber ini penting karena anggota Eksplorasi akan terus belajar mandiri sepanjang kariernya, jauh melampaui apa yang diajarkan kurikulum ini. Developer yang baik bukan yang tahu segalanya, tapi yang tahu ke mana harus mencari jawaban yang benar dan bisa dipercaya.',
            ],
        ]);

        // Tabel isian 3 sumber + esai reflektif DIGABUNG jadi satu baris
        // (lihat catatan penyimpangan di docblock kelas), sama alasannya
        // dengan Unit 2.3.
        UnitEvaluation::create([
            'unit_id' => $this->units['2.5']->id,
            'question_type' => 'practice',
            'question_text' => "Cari dan cantumkan 3 sumber belajar kredibel untuk tech stack yang sudah kamu pilih di Unit 2.4 (frontend, backend, atau database). Untuk masing-masing sumber, isi: nama sumber, tautan, jenis (dokumentasi resmi / platform belajar / kanal komunitas), dan alasan dianggap kredibel.\n\n".
                'Setelah ketiganya terisi, jawab juga pertanyaan reflektif berikut dalam jawaban yang sama: Ceritakan pengalamanmu, pernahkah kamu mengikuti tutorial yang ternyata sudah usang atau tidak lagi berlaku? Apa yang kamu pelajari dari pengalaman itu soal pentingnya memilih sumber belajar?',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    private function seedModule3(): void
    {
        $module = Module::create([
            'order_number' => 3,
            'title' => 'Lanskap Industri dan Arah Pergerakan Teknologi',
            'description' => 'Melatih kemampuan membaca kenapa sebuah teknologi naik atau turun popularitasnya.',
            'level_number' => 2,
        ]);

        $this->seedUnit31($module);
        $this->seedUnit32($module);

        // Checkpoint Modul 3 -- KONTEN BARU disusun oleh Celo, WAJIB direview Aye.
        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Saya bisa menjelaskan minimal dua dari empat pola evolusi tech stack (kompleksitas ke kemudahan, dorongan skala, pengaruh perusahaan besar, siklus hidup wajar).',
                'Saya bisa membaca sinyal industri (lowongan kerja, aktivitas GitHub, survei developer, dukungan perusahaan besar) untuk menilai relevansi sebuah teknologi.',
            ],
            'intermezo_questions' => [
                'Teknologi apa yang menurutmu sedang naik daun saat ini, dan pola evolusi mana dari Modul 3 yang paling menjelaskan kenapa?',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Stack Overflow Developer Survey',
            'url' => 'https://survey.stackoverflow.co/',
            'source_name' => 'Stack Overflow',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'GitHub Trending',
            'url' => 'https://github.com/trending',
            'source_name' => 'GitHub',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'State of JS',
            'url' => 'https://stateofjs.com/',
            'source_name' => 'State of JS',
        ]);
    }

    /**
     * §2.3.1 -- Pola Evolusi Tech Stack.
     */
    private function seedUnit31(Module $module): void
    {
        $this->units['3.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Pola Evolusi Tech Stack',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'essay',
        ]);

        $order = 1;

        $this->units['3.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Teknologi web tidak berubah secara acak, ada pola yang bisa dibaca di balik naik turunnya popularitas sebuah tech stack:',
            ],
        ]);

        $this->units['3.1']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'ordered',
                'items' => [
                    'Pergeseran dari kompleksitas menuju kemudahan — teknologi yang menyederhanakan pekerjaan developer cenderung diadopsi luas, contohnya jQuery dulu populer karena menyederhanakan manipulasi DOM yang rumit di JavaScript murni, lalu digantikan React dan sejenisnya yang menyederhanakan pengelolaan tampilan kompleks dengan pendekatan komponen.',
                    'Dorongan dari kebutuhan skala — teknologi yang awalnya cukup untuk aplikasi kecil seringkali perlu digantikan atau dilengkapi teknologi baru begitu aplikasi itu dipakai jutaan pengguna, karena masalah performa dan kompleksitas di skala besar berbeda dari skala kecil.',
                    'Pengaruh perusahaan besar teknologi — banyak teknologi populer lahir dari kebutuhan internal perusahaan besar seperti Meta (React), Google (Angular, Go), dan Netflix (arsitektur microservice), lalu dirilis sebagai open source dan diadopsi luas karena terbukti dipakai di skala produksi nyata.',
                    'Siklus hidup yang wajar — sebuah teknologi biasanya melalui fase kemunculan, adopsi luas, matang dan stabil, lalu perlahan digantikan teknologi baru; ini bukan berarti teknologi lama otomatis buruk, banyak teknologi "lama" seperti PHP tetap relevan dan terus diperbarui, hanya hype-nya tidak seramai dulu.',
                ],
            ],
        ]);

        $this->units['3.1']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Memahami pola ini melatih anggota untuk tidak panik atau FOMO setiap kali ada teknologi baru muncul, tapi mampu menilai apakah teknologi itu benar-benar menyelesaikan masalah nyata atau sekadar tren sesaat.',
            ],
        ]);

        // Ketiga pilihan topik digabung jadi SATU baris (lihat catatan
        // penyimpangan di docblock kelas), member memilih salah satu untuk
        // dijawab lewat satu textarea.
        UnitEvaluation::create([
            'unit_id' => $this->units['3.1']->id,
            'question_type' => 'essay',
            'question_text' => "Pilih SATU dari tiga topik berikut, lakukan riset singkat mandiri (boleh mencari di internet), lalu tulis esai (minimal 150 kata) yang menjelaskan pola evolusi yang berlaku pada topik pilihanmu, dikaitkan dengan keempat pola yang sudah dipelajari.\n\n".
                "Topik 1: Kenapa jQuery yang dulu sangat populer sekarang jarang dipakai untuk proyek baru?\n".
                "Topik 2: Kenapa React tetap menjadi salah satu library frontend paling populer selama lebih dari satu dekade?\n".
                'Topik 3: Kenapa banyak startup memilih tech stack seperti Next.js atau Laravel dibanding membangun semuanya dari nol?',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.3.2 -- Metode Membaca Sinyal Industri.
     */
    private function seedUnit32(Module $module): void
    {
        $this->units['3.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Metode Membaca Sinyal Industri',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['3.1']->id,
        ]);

        $order = 1;

        $this->units['3.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Selain memahami pola evolusi secara historis, anggota juga perlu tahu cara membaca sinyal industri secara langsung, untuk menilai apakah sebuah teknologi masih relevan dipelajari saat ini. Ada beberapa sinyal konkret yang bisa dibaca:',
            ],
        ]);

        $this->units['3.2']->contentBlocks()->create([
            'type' => 'table',
            'order' => $order++,
            'content' => [
                'headers' => ['Sinyal', 'Cara Membaca'],
                'rows' => [
                    ['Lowongan kerja', 'Situs seperti LinkedIn atau Glassdoor mencerminkan kebutuhan nyata industri — semakin banyak lowongan menyebut sebuah teknologi, semakin besar permintaan pasar terhadapnya saat ini.'],
                    ['Aktivitas komunitas open source (GitHub)', 'Jumlah bintang (stars), frekuensi commit terbaru, dan jumlah kontributor aktif menunjukkan seberapa hidup dan terus dikembangkan sebuah proyek teknologi.'],
                    ['Survei developer tahunan', 'Survei seperti Stack Overflow Developer Survey mengumpulkan data langsung dari puluhan ribu developer tentang teknologi yang mereka pakai, sukai, dan ingin pelajari.'],
                    ['Dukungan perusahaan besar', 'Apakah teknologi itu dipakai dan terus didukung oleh perusahaan teknologi besar — dukungan semacam ini biasanya menjamin teknologi itu terus dipelihara dalam jangka panjang.'],
                ],
            ],
        ]);

        $this->units['3.2']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Kemampuan membaca sinyal-sinyal ini penting supaya anggota tidak memilih arah belajar semata-mata berdasarkan opini satu video atau satu utas media sosial yang belum tentu mencerminkan kondisi industri secara luas.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['3.2']->id,
            'question_type' => 'practice',
            'question_text' => "Pilih satu bahasa pemrograman atau framework yang ingin kamu dalami. Cari dan tuliskan data konkret untuk keempat sinyal berikut, cantumkan sumbernya, lalu tutup dengan satu paragraf kesimpulan.\n\n".
                "1. Jumlah lowongan kerja terkait (di LinkedIn/Glassdoor/Jobstreet, cantumkan angka perkiraan)\n".
                "2. Aktivitas GitHub (jumlah stars dan tanggal commit terakhir dari repo resmi)\n".
                "3. Posisi di survei developer terbaru (misalnya Stack Overflow Developer Survey)\n".
                "4. Perusahaan besar yang diketahui memakai atau mendukung teknologi ini\n\n".
                'Setelah keempat data terisi, tutup dengan satu paragraf kesimpulan: berdasarkan keempat sinyal yang kamu temukan, apakah teknologi ini masih layak dipelajari saat ini? Jelaskan kesimpulanmu.',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    private function seedModule4(): void
    {
        $module = Module::create([
            'order_number' => 4,
            'title' => 'Peran-Peran dalam Tim Software',
            'description' => 'Memahami peran di luar developer, ketergantungan antar peran, dan kontribusi manusia yang tidak tergantikan AI.',
            'level_number' => 3,
        ]);

        $this->seedUnit41($module);
        $this->seedUnit42($module);
        $this->seedUnit43($module);

        // Checkpoint Modul 4 -- KONTEN BARU disusun oleh Celo, WAJIB direview Aye.
        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Saya memahami tanggung jawab utama tiap peran dalam tim software (PM, UI/UX, Frontend, Backend, QA, DevOps, Project Manager).',
                'Saya memahami alur ketergantungan antar peran sepanjang siklus proyek, termasuk sifatnya yang dua arah bukan cuma satu arah.',
                'Saya bisa menganalisis dampak nyata ketika satu peran hilang atau tidak berfungsi dalam sebuah proyek, dan memahami kenapa AI generatif tidak otomatis mengisi kekosongan itu.',
            ],
            'intermezo_questions' => [
                'Dari tujuh peran yang dipelajari, peran mana yang paling menarik minatmu untuk didalami di jalur Eksekusi nanti? Jelaskan alasannya.',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Atlassian — Team roles in software development',
            'url' => 'https://www.atlassian.com/agile/teams',
            'source_name' => 'Atlassian',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'roadmap.sh — Product Manager',
            'url' => 'https://roadmap.sh/product-manager',
            'source_name' => 'roadmap.sh',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'roadmap.sh — QA',
            'url' => 'https://roadmap.sh/qa',
            'source_name' => 'roadmap.sh',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'roadmap.sh — UX Design',
            'url' => 'https://roadmap.sh/ux-design',
            'source_name' => 'roadmap.sh',
        ]);
    }

    /**
     * §2.4.1 -- Struktur Peran dalam Tim Software.
     */
    private function seedUnit41(Module $module): void
    {
        $this->units['4.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Struktur Peran dalam Tim Software',
            'content' => '',
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_matching',
        ]);

        $order = 1;

        $this->units['4.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Membangun software, terutama yang berskala menengah sampai besar, jarang dikerjakan satu orang. Ada beragam peran yang saling melengkapi, masing-masing punya tanggung jawab spesifik.',
            ],
        ]);

        $this->units['4.1']->contentBlocks()->create([
            'type' => 'table',
            'order' => $order++,
            'content' => [
                'headers' => ['Peran', 'Tanggung Jawab Utama'],
                'rows' => [
                    ['Product Manager', 'Menentukan apa yang perlu dibangun dan kenapa, berdasarkan kebutuhan pengguna dan tujuan bisnis.'],
                    ['UI/UX Designer', 'Merancang bagaimana pengguna akan berinteraksi dengan aplikasi, memastikan tampilan indah sekaligus mudah dipahami dan digunakan.'],
                    ['Frontend Developer', 'Membangun apa yang dilihat dan disentuh langsung oleh pengguna.'],
                    ['Backend Developer', 'Membangun logika, pemrosesan data, dan komunikasi dengan database di balik layar.'],
                    ['QA (Quality Assurance)', 'Menguji aplikasi secara sistematis untuk menemukan bug sebelum sampai ke pengguna akhir.'],
                    ['DevOps', 'Mengelola infrastruktur, memastikan aplikasi bisa di-deploy dan berjalan stabil di server produksi.'],
                    ['Project Manager', 'Mengatur alur kerja, jadwal, dan komunikasi antar anggota tim agar proyek selesai tepat waktu.'],
                ],
            ],
        ]);

        $this->units['4.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Di tim kecil atau startup awal, satu orang sering merangkap beberapa peran sekaligus, misalnya seorang developer yang juga merangkap QA dan DevOps. Namun semakin besar skala proyek, pemisahan peran menjadi semakin penting agar setiap aspek mendapat perhatian yang cukup mendalam.',
            ],
        ]);

        $this->units['4.1']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'WEBI-SPACE sendiri, lewat sistem Eksekusi, akan menempatkan anggota dalam peran-peran nyata di proyek, sehingga pemahaman struktur ini menjadi bekal langsung yang terpakai, bukan sekadar teori.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['4.1']->id,
            'question_type' => 'matching',
            'question_text' => 'Cocokkan tiap peran di kolom kiri dengan tanggung jawab utamanya di kolom kanan.',
            'options' => [
                'pairs' => [
                    ['left' => 'UI/UX Designer', 'right' => 'Merancang interaksi dan tampilan yang mudah dipahami pengguna'],
                    ['left' => 'Backend Developer', 'right' => 'Membangun logika dan pemrosesan data di balik layar'],
                    ['left' => 'QA', 'right' => 'Menguji aplikasi secara sistematis untuk menemukan bug sebelum sampai ke pengguna'],
                    ['left' => 'DevOps', 'right' => 'Mengelola infrastruktur dan memastikan aplikasi berjalan stabil di server produksi'],
                    ['left' => 'Product Manager', 'right' => 'Menentukan apa yang perlu dibangun berdasarkan kebutuhan pengguna dan tujuan bisnis'],
                ],
            ],
            'correct_answer' => [
                'UI/UX Designer' => 'Merancang interaksi dan tampilan yang mudah dipahami pengguna',
                'Backend Developer' => 'Membangun logika dan pemrosesan data di balik layar',
                'QA' => 'Menguji aplikasi secara sistematis untuk menemukan bug sebelum sampai ke pengguna',
                'DevOps' => 'Mengelola infrastruktur dan memastikan aplikasi berjalan stabil di server produksi',
                'Product Manager' => 'Menentukan apa yang perlu dibangun berdasarkan kebutuhan pengguna dan tujuan bisnis',
            ],
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.4.2 -- Mekanisme Ketergantungan Antar Peran dalam Siklus Proyek.
     */
    private function seedUnit42(Module $module): void
    {
        $this->units['4.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Mekanisme Ketergantungan Antar Peran dalam Siklus Proyek',
            'content' => '',
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_ordering',
            'prerequisite_unit_id' => $this->units['4.1']->id,
        ]);

        $order = 1;

        $this->units['4.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Peran-peran dalam tim software tidak bekerja sendiri-sendiri secara terpisah, melainkan saling bergantung dalam sebuah alur kerja berurutan sekaligus saling memberi umpan balik. Product Manager menentukan kebutuhan, kebutuhan itu diterjemahkan UI/UX Designer menjadi rancangan tampilan dan alur pengguna, rancangan itu kemudian diimplementasikan Frontend Developer untuk tampilannya dan Backend Developer untuk logikanya, hasil implementasi itu diuji QA sebelum akhirnya di-deploy DevOps ke server produksi, sementara Project Manager mengawal seluruh alur ini agar berjalan sesuai jadwal.',
            ],
        ]);

        $this->units['4.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Ketergantungan ini bersifat dua arah, bukan cuma satu arah dari atas ke bawah. Misalnya, Frontend Developer yang menemukan bahwa rancangan UI/UX sulit diimplementasikan secara teknis perlu memberi umpan balik ke Designer untuk penyesuaian. QA yang menemukan bug krusial perlu mengembalikan pekerjaan ke Developer sebelum lanjut ke tahap deploy. Ketergantungan semacam ini membuat komunikasi antar peran menjadi sama pentingnya dengan keterampilan teknis masing-masing peran.',
            ],
        ]);

        $this->units['4.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Kegagalan satu peran dalam memenuhi tanggung jawabnya akan merambat ke peran-peran berikutnya. Kebutuhan yang salah dipahami Product Manager akan menghasilkan rancangan yang salah arah dari Designer, yang kemudian membuat Developer membangun sesuatu yang sebenarnya tidak dibutuhkan pengguna, betapa pun rapi kode yang ditulis.',
            ],
        ]);

        $this->units['4.2']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Memahami mekanisme saling ketergantungan ini penting supaya anggota tidak memandang perannya sendiri secara terisolasi, tapi memahami dampak pekerjaannya terhadap peran lain di sepanjang siklus proyek.',
            ],
        ]);

        // Kartu (8) "Project Manager memastikan seluruh tahapan berjalan
        // sesuai jadwal" DIKELUARKAN dari 7 kartu yang diurutkan (lihat
        // catatan penyimpangan di docblock kelas) -- sifat paralelnya
        // dijelaskan di question_text, bukan ikut diberi posisi urutan.
        UnitEvaluation::create([
            'unit_id' => $this->units['4.2']->id,
            'question_type' => 'ordering',
            'question_text' => 'Susun ketujuh kartu berikut menjadi urutan alur ketergantungan antar peran yang benar. (Catatan: peran "Project Manager memastikan seluruh tahapan berjalan sesuai jadwal" sengaja tidak ikut disusun karena posisinya paralel/berjalan sepanjang siklus, bukan satu tahap berurutan tertentu.)',
            'options' => [
                'QA menemukan bug dan mengembalikan ke Developer',
                'DevOps men-deploy aplikasi ke server produksi',
                'Product Manager menentukan kebutuhan fitur baru',
                'Frontend dan Backend Developer mengimplementasikan rancangan',
                'UI/UX Designer merancang tampilan dan alur pengguna',
                'Developer memperbaiki bug yang ditemukan QA',
                'QA menguji aplikasi yang sudah dibangun',
            ],
            'correct_answer' => [
                'Product Manager menentukan kebutuhan fitur baru',
                'UI/UX Designer merancang tampilan dan alur pengguna',
                'Frontend dan Backend Developer mengimplementasikan rancangan',
                'QA menguji aplikasi yang sudah dibangun',
                'QA menemukan bug dan mengembalikan ke Developer',
                'Developer memperbaiki bug yang ditemukan QA',
                'DevOps men-deploy aplikasi ke server produksi',
            ],
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.4.3 -- Studi Kasus: Dampak Kekosongan Satu Peran terhadap Proyek.
     */
    private function seedUnit43(Module $module): void
    {
        $this->units['4.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Studi Kasus: Dampak Kekosongan Satu Peran terhadap Proyek',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'essay',
            'prerequisite_unit_id' => $this->units['4.2']->id,
        ]);

        $order = 1;

        $this->units['4.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Bagian ini menyintesiskan dua unit sebelumnya lewat studi kasus konkret, memperlihatkan apa yang terjadi kalau satu peran hilang atau tidak berfungsi dengan baik dalam sebuah proyek.',
            ],
        ]);

        $this->units['4.3']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'unordered',
                'items' => [
                    'Tanpa Product Manager yang jelas: tim developer sering membangun fitur berdasarkan asumsi sendiri, menghasilkan aplikasi yang secara teknis berjalan tapi tidak menjawab kebutuhan pengguna sebenarnya, ujungnya harus dibangun ulang setelah diluncurkan dan mendapat masukan negatif.',
                    'Tanpa QA: bug-bug yang seharusnya bisa ditemukan sebelum rilis justru ditemukan pengguna di produksi, merusak kepercayaan dan reputasi produk, serta memaksa tim memadamkan masalah secara darurat alih-alih bekerja terencana.',
                    'Tanpa UI/UX Designer: developer sering merancang tampilan berdasarkan seleranya sendiri tanpa riset pengguna, menghasilkan aplikasi yang membingungkan meski secara fungsi lengkap, akhirnya banyak pengguna berhenti memakai karena kesulitan menavigasinya.',
                ],
            ],
        ]);

        $this->units['4.3']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'AI generatif, betapapun canggih, tidak otomatis mengisi kekosongan peran-peran ini. AI bisa membantu menulis kode atau bahkan menyarankan rancangan tampilan, tapi keputusan tentang kebutuhan pengguna sebenarnya, penilaian kualitas lewat pengujian sistematis, dan riset pengalaman pengguna nyata tetap membutuhkan penilaian dan tanggung jawab manusia.',
            ],
        ]);

        // Ketiga skenario digabung jadi SATU baris (lihat catatan penyimpangan
        // di docblock kelas), member memilih salah satu skenario untuk
        // dianalisis (minimal 200 kata) lewat satu textarea.
        UnitEvaluation::create([
            'unit_id' => $this->units['4.3']->id,
            'question_type' => 'essay',
            'question_text' => "Pilih SATU dari tiga skenario berikut, lalu tulis analisis dampak dan solusinya (minimal 200 kata).\n\n".
                "Skenario 1. Sebuah tim kecil membangun aplikasi manajemen tugas tanpa Product Manager. Setelah tiga bulan pengembangan, aplikasi diluncurkan lengkap dengan fitur kompleks seperti integrasi kalender dan notifikasi bertingkat, tapi pengguna ternyata hanya butuh fitur pencatatan tugas sederhana. Aplikasi sepi pengguna.\n".
                "Pertanyaan 1: Apa dampak kekosongan peran Product Manager dalam kasus ini? Langkah apa yang seharusnya dilakukan tim sebelum mulai membangun untuk mencegah hal ini terjadi?\n\n".
                "Skenario 2. Sebuah aplikasi e-commerce diluncurkan tanpa proses QA yang memadai. Dalam minggu pertama, pengguna melaporkan tombol checkout yang kadang gagal memproses pembayaran tapi tetap mengurangi stok barang.\n".
                "Pertanyaan 2: Apa dampak nyata dari kekosongan peran QA dalam kasus ini, baik dari sisi bisnis maupun kepercayaan pengguna? Langkah apa yang seharusnya dilakukan sebelum peluncuran?\n\n".
                "Skenario 3. Sebuah aplikasi dibangun sepenuhnya oleh developer tanpa keterlibatan UI/UX Designer. Semua fitur yang diminta berhasil dibangun, tapi menu navigasinya membingungkan dan banyak pengguna baru kesulitan menemukan fitur utama.\n".
                'Pertanyaan 3: Apa dampak kekosongan peran UI/UX Designer dalam kasus ini? Bagaimana peran ini seharusnya dilibatkan sejak tahap awal proyek?',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    private function seedModule5(): void
    {
        $module = Module::create([
            'order_number' => 5,
            'title' => 'Version Control dengan Git',
            'description' => 'Git sebagai cara berpikir kolaboratif, dijelaskan lewat tutorial command yang terstruktur dan lengkap.',
            'level_number' => 3,
        ]);

        $this->seedUnit51($module);
        $this->seedUnit52($module);
        $this->seedUnit53($module);
        $this->seedUnit54($module);
        $this->seedUnit55($module);

        // Checkpoint Modul 5 -- KONTEN BARU disusun oleh Celo, WAJIB direview Aye.
        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Saya memahami konsep version control dan perbedaan mendasar antara Git dan GitHub.',
                'Saya bisa menjalankan alur dasar Git: init, add, commit, log.',
                'Saya bisa bekerja dengan branch: membuat, berpindah, dan merge, serta memahami merge conflict sebagai hal wajar.',
                'Saya bisa berkolaborasi jarak jauh lewat clone, push, pull, dan fetch.',
                "Saya bisa menulis pesan commit yang jelas dan bermakna, bukan sekadar 'update' atau 'fix'.",
            ],
            'intermezo_questions' => [
                'Dari seluruh command Git yang sudah kamu praktikkan, command atau konsep mana yang paling terasa sulit di awal, dan bagaimana kamu memahaminya?',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Pro Git Book (Bahasa Indonesia tersedia)',
            'url' => 'https://git-scm.com/book/id/v2',
            'source_name' => 'git-scm.com (resmi)',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Git and GitHub Roadmap',
            'url' => 'https://roadmap.sh/git-github',
            'source_name' => 'roadmap.sh',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'GitHub Docs — Get started',
            'url' => 'https://docs.github.com/en/get-started',
            'source_name' => 'GitHub',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Learn Git Branching (interaktif)',
            'url' => 'https://learngitbranching.js.org/',
            'source_name' => 'Learn Git Branching',
        ]);
    }

    /**
     * §2.5.1 -- Mekanisme Version Control.
     */
    private function seedUnit51(Module $module): void
    {
        $this->units['5.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Mekanisme Version Control',
            'content' => '',
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
        ]);

        $order = 1;

        $this->units['5.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Version control adalah sistem yang mencatat setiap perubahan pada file-file proyek dari waktu ke waktu, memungkinkan seseorang melihat riwayat perubahan, membandingkan versi berbeda, dan kembali ke versi sebelumnya kapan pun diperlukan. Tanpa version control, mengelola perubahan kode biasanya dilakukan dengan cara manual yang rapuh, misalnya menyimpan banyak file dengan nama seperti "proyek_final.zip", "proyek_final_revisi.zip", "proyek_final_revisi_beneran.zip", cara yang mudah membingungkan dan rawan kehilangan data.',
            ],
        ]);

        $this->units['5.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Git adalah sistem version control yang paling banyak dipakai di dunia, bekerja secara terdesentralisasi, artinya setiap orang yang bekerja dengan Git punya salinan lengkap riwayat proyek di komputernya masing-masing, bukan hanya bergantung pada satu server pusat. Ini membuat Git tetap bisa dipakai meski sedang tidak terhubung internet, dan history bisa dipulihkan dari salinan siapa pun dalam tim kalau terjadi masalah di satu titik.',
            ],
        ]);

        $this->units['5.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Git bekerja dengan konsep commit, yaitu titik penyimpanan (snapshot) dari keadaan proyek pada momen tertentu, lengkap dengan pesan yang menjelaskan perubahan apa yang dilakukan. Rangkaian commit inilah yang membentuk riwayat proyek yang bisa ditelusuri kembali kapan saja.',
            ],
        ]);

        $this->units['5.1']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'info',
                'title' => 'Git vs GitHub',
                'body' => 'Git adalah alat/software yang berjalan di komputer, sedangkan GitHub adalah platform online yang menyimpan proyek Git di internet dan menambahkan fitur kolaborasi seperti Pull Request. Perbedaan ini akan diperjelas dengan praktik di unit-unit berikutnya.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Version control pada dasarnya berfungsi untuk:',
            'options' => [
                'Mempercepat koneksi internet saat coding',
                'Mencatat riwayat perubahan file proyek dan memungkinkan kembali ke versi sebelumnya',
                'Menggantikan kebutuhan menulis dokumentasi',
                'Menghapus file yang tidak dipakai secara otomatis',
            ],
            'correct_answer' => 'Mencatat riwayat perubahan file proyek dan memungkinkan kembali ke versi sebelumnya',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Perbedaan mendasar antara Git dan GitHub adalah:',
            'options' => [
                'Git untuk pemula, GitHub untuk profesional',
                'Git adalah alat version control yang berjalan di komputer, GitHub adalah platform online untuk menyimpan dan berkolaborasi lewat Git',
                'Keduanya sama persis, hanya beda nama',
                'Git hanya untuk bahasa Python, GitHub untuk semua bahasa',
            ],
            'correct_answer' => 'Git adalah alat version control yang berjalan di komputer, GitHub adalah platform online untuk menyimpan dan berkolaborasi lewat Git',
            'sort_order' => 2,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Commit dalam Git berarti:',
            'options' => [
                'Menghapus seluruh riwayat proyek',
                'Titik penyimpanan (snapshot) keadaan proyek pada momen tertentu, disertai pesan penjelasan',
                'Mengunggah proyek ke internet',
                'Membuat salinan proyek di komputer lain',
            ],
            'correct_answer' => 'Titik penyimpanan (snapshot) keadaan proyek pada momen tertentu, disertai pesan penjelasan',
            'sort_order' => 3,
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.1']->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Kenapa Git disebut bekerja secara terdesentralisasi?',
            'options' => [
                'Karena tidak butuh internet sama sekali selamanya',
                'Karena setiap orang punya salinan lengkap riwayat proyek di komputernya masing-masing, bukan cuma bergantung pada satu server pusat',
                'Karena hanya satu orang yang boleh menyimpan riwayat proyek',
                'Karena Git hanya berjalan di server perusahaan besar',
            ],
            'correct_answer' => 'Karena setiap orang punya salinan lengkap riwayat proyek di komputernya masing-masing, bukan cuma bergantung pada satu server pusat',
            'sort_order' => 4,
        ]);
    }

    /**
     * §2.5.2 -- Command Dasar Git: Init, Add, Commit, Log.
     */
    private function seedUnit52(Module $module): void
    {
        $this->units['5.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Command Dasar Git: Init, Add, Commit, Log',
            'content' => '',
            'estimated_minutes' => 25,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['5.1']->id,
        ]);

        $order = 1;

        $this->units['5.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Setelah memahami konsepnya, saatnya masuk ke praktik command dasar Git yang dijalankan lewat terminal. Empat command ini adalah fondasi yang akan terus dipakai sepanjang bekerja dengan Git.',
            ],
        ]);

        $this->units['5.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => '`git init` menginisialisasi folder yang sedang dikerjakan menjadi sebuah repository Git, artinya folder itu mulai dilacak riwayat perubahannya. Command ini hanya dijalankan sekali di awal sebuah proyek baru.',
            ],
        ]);

        $this->units['5.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => '`git add` menandai file mana saja yang perubahannya ingin dimasukkan ke commit berikutnya, proses ini disebut staging. Menjalankan `git add nama_file` menandai satu file tertentu, sedangkan `git add .` menandai seluruh file yang berubah di folder tersebut.',
            ],
        ]);

        $this->units['5.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => '`git commit -m "pesan"` menyimpan seluruh perubahan yang sudah di-stage tadi sebagai satu titik riwayat baru, disertai pesan yang menjelaskan perubahan apa yang dilakukan. Pesan commit yang jelas sangat penting, karena riwayat proyek yang baik adalah riwayat yang bisa dibaca dan dipahami tanpa harus membuka isi kodenya satu per satu.',
            ],
        ]);

        $this->units['5.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => '`git log` menampilkan riwayat seluruh commit yang pernah dibuat, lengkap dengan penulis, tanggal, dan pesan masing-masing commit, berguna untuk menelusuri kembali apa saja yang sudah terjadi pada sebuah proyek.',
            ],
        ]);

        $this->units['5.2']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Alur Kerja Dasar',
                'body' => 'Urutan alur kerja dasarnya selalu sama: ubah file, `git add` untuk stage perubahan, `git commit` untuk menyimpannya sebagai titik riwayat, ulangi seterusnya seiring proyek berkembang.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.2']->id,
            'question_type' => 'practice',
            'question_text' => "Buka terminal, buat folder baru bernama latihan-git, lalu jalankan urutan berikut secara berurutan:\n\n".
                "1. git init\n".
                "2. Buat satu file teks sederhana (misalnya catatan.txt berisi satu kalimat)\n".
                "3. git add catatan.txt\n".
                "4. git commit -m \"commit pertama: menambahkan catatan.txt\"\n".
                "5. Ubah isi file itu\n".
                "6. Ulangi proses add dan commit dengan pesan baru\n".
                "7. Jalankan git log untuk melihat riwayatnya\n\n".
                'Pertanyaan pelaporan: Tempelkan hasil output dari git log setelah kamu menyelesaikan langkah-langkah di atas. Ada berapa commit yang tercatat, dan apakah pesan commit-nya jelas menjelaskan perubahan yang dilakukan?',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.5.3 -- Command Percabangan: Branch, Checkout, Merge, Konflik.
     */
    private function seedUnit53(Module $module): void
    {
        $this->units['5.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Command Percabangan: Branch, Checkout, Merge, Konflik',
            'content' => '',
            'estimated_minutes' => 25,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['5.2']->id,
        ]);

        $order = 1;

        $this->units['5.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Branch adalah cabang independen dari riwayat proyek, memungkinkan seseorang mengembangkan fitur atau perbaikan baru tanpa mengganggu kode utama yang sudah stabil. Secara default, Git menyediakan satu branch utama yang biasanya disebut `main`. Saat ingin mengerjakan sesuatu yang baru, developer membuat branch terpisah dari `main`, mengerjakan perubahan di sana, baru kemudian menggabungkannya kembali setelah selesai dan teruji.',
            ],
        ]);

        $this->units['5.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => '`git branch nama-branch` membuat branch baru. `git checkout nama-branch` berpindah ke branch tersebut untuk mulai bekerja di sana (bisa juga digabung jadi satu lewat `git checkout -b nama-branch` untuk membuat sekaligus berpindah). `git merge nama-branch` menggabungkan perubahan dari branch tersebut ke branch yang sedang aktif, biasanya dilakukan saat menggabungkan pekerjaan yang sudah selesai kembali ke `main`.',
            ],
        ]);

        $this->units['5.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Merge conflict terjadi ketika Git tidak bisa otomatis menggabungkan dua perubahan karena keduanya mengubah bagian yang sama pada file yang sama dengan cara berbeda. Saat ini terjadi, Git akan menandai bagian yang bertentangan langsung di dalam file dan meminta developer memutuskan secara manual versi mana yang dipertahankan, sebelum melanjutkan proses commit untuk menyelesaikan merge tersebut.',
            ],
        ]);

        $this->units['5.3']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Merge conflict bukan tanda kesalahan atau kegagalan, melainkan bagian wajar dari kerja kolaboratif yang melibatkan banyak orang mengubah kode yang sama. Yang penting adalah memahami cara membacanya dan menyelesaikannya dengan tenang, bukan menghindarinya sama sekali.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.3']->id,
            'question_type' => 'practice',
            'question_text' => "Melanjutkan folder latihan-git dari unit sebelumnya:\n\n".
                "1. Buat branch baru bernama fitur-baru dan berpindah ke sana\n".
                "2. Ubah isi catatan.txt dan lakukan commit di branch ini\n".
                "3. Berpindah kembali ke main\n".
                "4. Jalankan git merge fitur-baru\n\n".
                'Pertanyaan pelaporan: Apakah proses merge berjalan lancar tanpa konflik? Jelaskan command apa saja yang kamu jalankan secara berurutan. Jika kamu sempat mengalami merge conflict (baik sengaja dipicu atau tidak sengaja), ceritakan bagaimana kamu menyelesaikannya.',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.5.4 -- Command Kolaborasi Jarak Jauh: Clone, Push, Pull, Fetch.
     */
    private function seedUnit54(Module $module): void
    {
        $this->units['5.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Command Kolaborasi Jarak Jauh: Clone, Push, Pull, Fetch',
            'content' => '',
            'estimated_minutes' => 25,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['5.3']->id,
        ]);

        $order = 1;

        $this->units['5.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Empat command ini menghubungkan Git yang berjalan lokal di komputer dengan repository yang tersimpan di layanan online seperti GitHub, memungkinkan kolaborasi jarak jauh dengan anggota tim lain.',
            ],
        ]);

        $this->units['5.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => '`git clone url-repository` menyalin sebuah repository yang sudah ada di GitHub ke komputer lokal, lengkap dengan seluruh riwayat commit-nya. Ini biasanya jadi langkah pertama saat bergabung ke proyek yang sudah berjalan.',
            ],
        ]);

        $this->units['5.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => '`git push` mengirim commit yang sudah dibuat di komputer lokal menuju repository di GitHub, sehingga anggota tim lain bisa melihat perubahan tersebut. `git pull` mengambil perubahan terbaru dari GitHub dan langsung menggabungkannya ke branch lokal yang sedang aktif, memastikan pekerjaan seseorang selalu sinkron dengan perubahan terbaru dari tim.',
            ],
        ]);

        $this->units['5.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => '`git fetch` mirip dengan pull, mengambil perubahan terbaru dari GitHub, tapi tidak langsung menggabungkannya ke branch lokal. Fetch berguna saat seseorang ingin melihat dulu perubahan apa saja yang masuk sebelum memutuskan untuk menggabungkannya, memberi kesempatan meninjau lebih dulu sebelum kode orang lain benar-benar tercampur dengan pekerjaan sendiri.',
            ],
        ]);

        $this->units['5.4']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Sebagai pemula, `pull` akan jauh lebih sering dipakai dibanding `fetch`, karena kebanyakan situasi kolaborasi memang membutuhkan sinkronisasi langsung. Fetch menjadi pilihan yang lebih hati-hati saat bekerja di proyek dengan banyak kontributor aktif dan perubahan besar yang perlu ditinjau dulu.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['5.4']->id,
            'question_type' => 'practice',
            'question_text' => "1. Buat akun GitHub kalau belum punya\n".
                "2. Buat satu repository baru langsung dari GitHub bernama latihan-remote\n".
                "3. Clone repository itu ke komputer lokal dengan git clone\n".
                "4. Tambahkan satu file baru, lakukan git add dan git commit\n".
                "5. git push perubahan itu ke GitHub\n\n".
                "Catatan: gunakan akun GitHub pribadimu sendiri, bukan akun WEBI-SPACE.\n\n".
                'Pertanyaan pelaporan: Tempelkan tautan repository GitHub-mu yang sudah berisi commit hasil push. Jelaskan urutan command yang kamu jalankan dari clone sampai push berhasil.',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.5.5 -- Praktik Menulis Histori yang Bermakna.
     *
     * FLAG UNTUK AYE: unit_type=concept/10 poin (bukan practice/15) --
     * keputusan Celo, lihat docblock kelas.
     */
    private function seedUnit55(Module $module): void
    {
        $this->units['5.5'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 5,
            'title' => 'Praktik Menulis Histori yang Bermakna',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['5.4']->id,
        ]);

        $order = 1;

        $this->units['5.5']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Menguasai command Git saja belum cukup, kualitas riwayat proyek juga sangat ditentukan oleh cara menulis pesan commit. Pesan commit yang buruk seperti "update", "fix", atau "asdasd" tidak memberi informasi apapun tentang perubahan yang sebenarnya terjadi, membuat riwayat proyek sulit ditelusuri saat dibutuhkan di kemudian hari, misalnya saat mencari commit mana yang menyebabkan sebuah bug muncul.',
            ],
        ]);

        $this->units['5.5']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Pesan commit yang baik biasanya mengikuti pola singkat namun jelas, dimulai dengan kata kerja yang menjelaskan tindakan (menambahkan, memperbaiki, mengubah, menghapus), diikuti objek spesifik yang terkena dampak. Contohnya "menambahkan validasi email di form pendaftaran" jauh lebih informatif dibanding "update form".',
            ],
        ]);

        $this->units['5.5']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Praktik lain yang penting adalah menjaga satu commit tetap fokus pada satu perubahan logis, bukan mencampur banyak perubahan tak berhubungan dalam satu commit besar. Commit yang fokus memudahkan penelusuran dan, kalau diperlukan, memudahkan membatalkan (revert) satu perubahan tanpa ikut membatalkan perubahan lain yang tidak berkaitan.',
            ],
        ]);

        $this->units['5.5']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Kebiasaan menulis histori yang bermakna ini adalah salah satu tanda developer yang matang, karena menunjukkan kepedulian terhadap kolaborator lain di masa depan, termasuk diri sendiri yang mungkin lupa konteks perubahan setelah berbulan-bulan berlalu.',
            ],
        ]);

        // Kelima pesan+kunci acuan digabung jadi SATU baris (lihat catatan
        // penyimpangan di docblock kelas).
        UnitEvaluation::create([
            'unit_id' => $this->units['5.5']->id,
            'question_type' => 'practice',
            'question_text' => "Berikut lima contoh pesan commit yang buruk. Tuliskan versi perbaikannya yang lebih jelas dan bermakna untuk masing-masing.\n\n".
                "1. \"update\"\n".
                "2. \"fix bug\"\n".
                "3. \"asdasd\"\n".
                "4. \"perubahan banyak hal\"\n".
                '5. "wip"',
            'options' => null,
            'correct_answer' => 'Contoh jawaban yang dianggap baik (bukan satu-satunya jawaban benar): 1. "menambahkan fitur pencarian di halaman utama". 2. "memperbaiki bug tombol submit yang tidak merespons klik". 3. Pesan yang jelas menggantikan teks tidak bermakna, misalnya "menghapus file konfigurasi yang tidak terpakai". 4. Dipecah menjadi beberapa commit terpisah, masing-masing fokus satu perubahan. 5. "menyiapkan struktur awal halaman profil (belum selesai)".',
            'sort_order' => 1,
        ]);
    }

    private function seedModule6(): void
    {
        $module = Module::create([
            'order_number' => 6,
            'title' => 'Arsitektur dan Prinsip Frontend',
            'description' => 'Arsitektur informasi, sistem desain, visualisasi data, accessibility, dan peran frontend dalam tim, dijelaskan konkret dan teknis.',
            'level_number' => 4,
        ]);

        $this->seedUnit61($module);
        $this->seedUnit62($module);
        $this->seedUnit63($module);
        $this->seedUnit64($module);
        $this->seedUnit65($module);

        // Checkpoint Modul 6 -- KONTEN BARU disusun oleh Celo, WAJIB direview Aye.
        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Saya memahami prinsip arsitektur informasi: pelabelan yang jelas dan pengkategorian MECE.',
                'Saya memahami peran design token dan design pattern dalam menjaga konsistensi visual dan struktural.',
                'Saya bisa memilih jenis visualisasi data yang tepat (bar/line/pie/heatmap) sesuai jenis data dan pesannya.',
                'Saya memahami prinsip accessibility dasar dalam frontend dan bisa mengaudit kode HTML terhadap prinsip itu.',
                'Saya memahami bagaimana Frontend Developer bekerja dan berkomunikasi dengan peran lain dalam tim proyek.',
            ],
            'intermezo_questions' => [
                'Setelah Modul 6, aspek frontend mana (arsitektur informasi, design system, visualisasi data, atau accessibility) yang paling ingin kamu terapkan langsung di proyek Eksekusi nanti?',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Frontend Roadmap',
            'url' => 'https://roadmap.sh/frontend',
            'source_name' => 'roadmap.sh',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Web Accessibility Initiative (WAI)',
            'url' => 'https://www.w3.org/WAI/fundamentals/',
            'source_name' => 'W3C',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'web.dev — Learn Accessibility',
            'url' => 'https://web.dev/learn/accessibility',
            'source_name' => 'web.dev (Google)',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Design Systems Roadmap',
            'url' => 'https://roadmap.sh/design-system',
            'source_name' => 'roadmap.sh',
        ]);
    }

    /**
     * §2.6.1 -- Arsitektur Informasi: Pelabelan dan Pengkategorian Konten.
     */
    private function seedUnit61(Module $module): void
    {
        $this->units['6.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Arsitektur Informasi: Pelabelan dan Pengkategorian Konten',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
        ]);

        $order = 1;

        $this->units['6.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Arsitektur informasi adalah cara menyusun, melabeli, dan mengelompokkan konten dalam sebuah aplikasi supaya pengguna bisa menemukan apa yang mereka cari dengan mudah dan cepat. Ini adalah pekerjaan yang terjadi sebelum satu baris kode tampilan pun ditulis, karena struktur informasi yang buruk tidak bisa diperbaiki hanya dengan mempercantik visual.',
            ],
        ]);

        $this->units['6.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Pelabelan (labeling) berarti memilih kata atau istilah yang dipakai untuk menu, tombol, dan kategori, harus mencerminkan bahasa yang dipahami penggunanya, bukan istilah internal tim yang membingungkan orang luar. Misalnya, label "Beranda" lebih jelas dibanding "Landing" bagi pengguna awam berbahasa Indonesia.',
            ],
        ]);

        $this->units['6.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Pengkategorian (categorization) berarti mengelompokkan konten berdasarkan kesamaan yang masuk akal bagi pengguna, bukan berdasarkan struktur teknis di balik layar yang hanya dipahami tim developer.',
            ],
        ]);

        $this->units['6.1']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'info',
                'title' => 'Prinsip MECE',
                'body' => 'Mutually exclusive: satu konten idealnya hanya masuk satu kategori jelas, tidak tumpang tindih. Collectively exhaustive: seluruh kategori bersama-sama mencakup semua konten, tidak ada yang terlewat tanpa tempat.',
            ],
        ]);

        $this->units['6.1']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Struktur navigasi yang baik biasanya mengikuti pola hierarki yang tidak lebih dari tiga sampai empat tingkat kedalaman, karena pengguna cenderung frustrasi dan berhenti mencari kalau harus mengklik terlalu banyak lapisan menu hanya untuk sampai ke konten yang dicari.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['6.1']->id,
            'question_type' => 'practice',
            'question_text' => "Berikut struktur navigasi menu sebuah aplikasi toko online fiktif yang perlu kamu audit:\n\n".
                "Menu Utama\n".
                "├── Home\n".
                "├── Kategori Produk\n".
                "│   ├── Data-Produk-Elektronik-2023\n".
                "│   ├── barang_rumah\n".
                "│   └── Misc\n".
                "├── Akun\n".
                "│   ├── Pengaturan Preferensi Notifikasi Lanjutan\n".
                "│   └── Info\n".
                "└── Bantuan\n\n".
                'Pertanyaan audit: Identifikasi minimal tiga masalah pelabelan atau pengkategorian pada struktur menu di atas (misalnya label yang tidak konsisten, label teknis yang membingungkan pengguna awam, kategori yang tumpang tindih atau tidak jelas cakupannya seperti "Misc"). Tuliskan usulan struktur menu perbaikan versimu.',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.6.2 -- Sistem Desain: Design Token dan Design Pattern.
     */
    private function seedUnit62(Module $module): void
    {
        $this->units['6.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Sistem Desain: Design Token dan Design Pattern',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['6.1']->id,
        ]);

        $order = 1;

        $this->units['6.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Design token adalah nilai-nilai desain dasar yang disimpan sebagai variabel bernama, misalnya warna, ukuran font, jarak (spacing), dan radius sudut, yang dipakai berulang di seluruh aplikasi. Alih-alih menulis kode warna `#3B82F6` berulang kali di banyak tempat, design token menyimpannya sekali sebagai misalnya `color-primary`, lalu dipakai ulang di mana pun dibutuhkan. Keuntungannya, kalau suatu saat warna utama aplikasi ingin diubah, cukup ubah satu token, seluruh bagian aplikasi yang memakainya otomatis ikut berubah konsisten.',
            ],
        ]);

        $this->units['6.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Design pattern adalah solusi desain yang sudah teruji dan dipakai berulang untuk masalah antarmuka yang umum terjadi, misalnya pola modal (jendela pop-up) untuk konfirmasi aksi penting, pola kartu (card) untuk menampilkan ringkasan item dalam daftar, atau pola breadcrumb untuk menunjukkan posisi pengguna dalam hierarki halaman.',
            ],
        ]);

        $this->units['6.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Keduanya saling melengkapi. Design token menjaga konsistensi visual level detail (warna, ukuran, jarak), sementara design pattern menjaga konsistensi struktural level lebih besar (bagaimana sebuah jenis interaksi disajikan). Aplikasi yang konsisten secara visual dan struktural terasa lebih profesional dan lebih mudah dipelajari pengguna, karena begitu mereka paham satu pola di satu bagian aplikasi, pola yang sama di bagian lain akan terasa familiar.',
            ],
        ]);

        $this->units['6.2']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Penerapan Nyata di WEBI-SPACE',
                'body' => 'WEBI-SPACE sendiri menerapkan design token lewat dokumentasi docs/design-tokens.md sebagai acuan konsistensi visual identitas Retro-Tech di seluruh portalnya.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['6.2']->id,
            'question_type' => 'practice',
            'question_text' => "Berikut potongan kode CSS yang tidak memakai design token, perlu kamu audit:\n\n".
                ".tombol-simpan { background-color: #3B82F6; padding: 12px 20px; border-radius: 8px; }\n".
                ".tombol-hapus { background-color: #3B82F6; padding: 12px 20px; border-radius: 8px; }\n".
                ".kartu-produk { background-color: #3B82F6; padding: 12px 20px; border-radius: 4px; }\n\n".
                'Pertanyaan audit: Identifikasi masalah konsistensi pada kode di atas (perhatikan bahwa tombol hapus memakai warna yang sama dengan tombol simpan, padahal secara fungsi keduanya berbeda tingkat risiko, dan radius kartu-produk tidak konsisten dengan dua elemen lain). Tuliskan versi perbaikan memakai pendekatan design token (boleh dalam bentuk daftar variabel bernama beserta nilainya, lalu bagaimana tiap kelas CSS memakainya).',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.6.3 -- Ragam Visualisasi Data pada Web.
     */
    private function seedUnit63(Module $module): void
    {
        $this->units['6.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Ragam Visualisasi Data pada Web',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['6.2']->id,
        ]);

        $order = 1;

        $this->units['6.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Visualisasi data adalah cara menyajikan data mentah menjadi bentuk visual yang lebih mudah dipahami dan dimaknai dibanding sekadar tabel angka. Pemilihan jenis visualisasi yang tepat sangat bergantung pada jenis data dan pesan yang ingin disampaikan.',
            ],
        ]);

        $this->units['6.3']->contentBlocks()->create([
            'type' => 'table',
            'order' => $order++,
            'content' => [
                'headers' => ['Jenis', 'Kapan Dipakai'],
                'rows' => [
                    ['Grafik batang (bar chart)', 'Membandingkan nilai antar kategori yang berbeda, misalnya membandingkan jumlah penjualan antar produk.'],
                    ['Grafik garis (line chart)', 'Menunjukkan tren atau perubahan nilai dari waktu ke waktu, misalnya pertumbuhan jumlah pengguna per bulan.'],
                    ['Grafik lingkaran (pie chart)', 'Menunjukkan proporsi bagian terhadap keseluruhan, sebaiknya hanya untuk sedikit kategori karena terlalu banyak potongan membuat perbandingan sulit dibaca.'],
                    ['Heatmap', 'Menunjukkan intensitas atau kepadatan data dalam dua dimensi, misalnya heatmap aktivitas kontribusi seperti di halaman profil GitHub.'],
                ],
            ],
        ]);

        $this->units['6.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Kesalahan umum yang sering terjadi adalah memilih jenis visualisasi berdasarkan selera visual semata tanpa mempertimbangkan jenis data, misalnya memakai pie chart untuk data yang sebenarnya menunjukkan tren waktu, yang justru membuat pesan datanya menjadi lebih sulit dipahami, bukan lebih mudah.',
            ],
        ]);

        $this->units['6.3']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Penerapan Nyata di WEBI-SPACE',
                'body' => 'WEBI-SPACE sendiri menerapkan heatmap aktivitas bergaya GitHub di halaman Profil, sebuah contoh nyata penerapan visualisasi data yang sesuai dengan jenis datanya (data aktivitas dari waktu ke waktu, dipetakan dalam grid harian).',
            ],
        ]);

        // Ketiga skenario digabung jadi SATU baris (lihat catatan penyimpangan
        // di docblock kelas).
        UnitEvaluation::create([
            'unit_id' => $this->units['6.3']->id,
            'question_type' => 'practice',
            'question_text' => "Berikut tiga set data fiktif. Untuk masing-masing, tentukan jenis visualisasi paling tepat beserta alasannya.\n\n".
                "Data 1. Jumlah anggota baru yang mendaftar WEBI-SPACE tiap bulan, dari Januari sampai Desember.\n".
                "Pertanyaan 1: Jenis visualisasi apa yang paling tepat, dan kenapa?\n\n".
                "Data 2. Proporsi anggota divisi Web Development yang tergabung di track Eksplorasi dibanding track Eksekusi.\n".
                "Pertanyaan 2: Jenis visualisasi apa yang paling tepat, dan kenapa?\n\n".
                "Data 3. Perbandingan jumlah submission Praktik yang dikumpulkan lima anggota Eksplorasi berbeda dalam satu bulan.\n".
                'Pertanyaan 3: Jenis visualisasi apa yang paling tepat, dan kenapa?',
            'options' => null,
            'correct_answer' => 'Data 1: grafik garis (line chart), karena menunjukkan tren perubahan dari waktu ke waktu. Data 2: grafik lingkaran (pie chart), karena hanya dua kategori dan yang ditunjukkan adalah proporsi terhadap keseluruhan. Data 3: grafik batang (bar chart), karena membandingkan nilai antar kategori (antar anggota) yang berbeda, bukan menunjukkan tren waktu maupun proporsi.',
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.6.4 -- Prinsip Accessibility dalam Frontend.
     */
    private function seedUnit64(Module $module): void
    {
        $this->units['6.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Prinsip Accessibility dalam Frontend',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['6.3']->id,
        ]);

        $order = 1;

        $this->units['6.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Accessibility (aksesibilitas) dalam frontend berarti memastikan aplikasi bisa dipakai oleh pengguna seluas mungkin, termasuk pengguna dengan keterbatasan penglihatan, pendengaran, motorik, atau kognitif. Ini bukan fitur tambahan opsional, melainkan tanggung jawab dasar yang sering terlewat karena tidak terlihat langsung dampaknya bagi developer yang tidak memiliki keterbatasan tersebut.',
            ],
        ]);

        $this->units['6.4']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'ordered',
                'items' => [
                    'Kontras warna yang cukup antara teks dan latar belakang, supaya pengguna dengan gangguan penglihatan tertentu (termasuk buta warna parsial) tetap bisa membaca konten dengan jelas.',
                    'Teks alternatif (alt text) pada gambar, supaya pengguna yang memakai screen reader tetap mendapat informasi tentang apa yang ditampilkan gambar tersebut.',
                    'Navigasi yang bisa dioperasikan penuh lewat keyboard, tanpa harus bergantung pada mouse, penting bagi pengguna dengan keterbatasan motorik.',
                    'Struktur heading (H1, H2, H3, dan seterusnya) yang logis dan berurutan, membantu pengguna screen reader memahami hierarki konten tanpa harus melihat tampilan visualnya.',
                ],
            ],
        ]);

        $this->units['6.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Standar acuan internasional yang umum dipakai adalah WCAG (Web Content Accessibility Guidelines), yang mendefinisikan tingkat kepatuhan aksesibilitas mulai dari level A (dasar) sampai AAA (paling ketat).',
            ],
        ]);

        $this->units['6.4']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Mengabaikan accessibility bukan hanya soal etika inklusi, tapi juga berdampak langsung pada jangkauan pengguna aplikasi. Aplikasi yang tidak aksesibel secara otomatis kehilangan sebagian penggunanya tanpa developer pernah menyadarinya.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['6.4']->id,
            'question_type' => 'practice',
            'question_text' => "Berikut potongan kode HTML yang perlu kamu audit:\n\n".
                "<div onclick=\"submitForm()\">Kirim</div>\n".
                "<img src=\"grafik-penjualan.png\">\n".
                "<h1>Judul Halaman</h1>\n".
                "<h3>Sub Bagian Pertama</h3>\n\n".
                'Pertanyaan audit: Identifikasi minimal tiga masalah accessibility pada kode di atas (perhatikan penggunaan elemen div sebagai tombol yang tidak bisa diakses lewat keyboard secara default, gambar tanpa atribut alt, dan lompatan struktur heading dari H1 langsung ke H3 tanpa H2). Tuliskan versi perbaikan kode HTML di atas.',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.6.5 -- Mekanisme Peran Frontend dalam Tim Proyek.
     */
    private function seedUnit65(Module $module): void
    {
        $this->units['6.5'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 5,
            'title' => 'Mekanisme Peran Frontend dalam Tim Proyek',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'essay',
            'prerequisite_unit_id' => $this->units['6.4']->id,
        ]);

        $order = 1;

        $this->units['6.5']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Setelah memahami arsitektur informasi, sistem desain, visualisasi data, dan accessibility, unit ini menyintesiskan bagaimana seorang Frontend Developer benar-benar bekerja dalam mekanisme tim proyek sehari-hari.',
            ],
        ]);

        $this->units['6.5']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Frontend Developer menerima rancangan dari UI/UX Designer, biasanya dalam bentuk file desain (seperti Figma), lalu menerjemahkannya menjadi kode yang benar-benar berjalan di browser. Proses ini bukan sekadar meniru tampilan piksel demi piksel, tapi juga mempertimbangkan bagaimana tampilan itu tetap responsive (menyesuaikan diri di berbagai ukuran layar) dan tetap accessible sesuai prinsip yang sudah dipelajari.',
            ],
        ]);

        $this->units['6.5']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Frontend Developer juga berkomunikasi intensif dengan Backend Developer untuk menyepakati bentuk data yang akan dipertukarkan (kontrak API, akan dipelajari lebih detail di Modul 7), memastikan data yang dikirim backend bisa ditampilkan dengan benar di sisi frontend, dan sebaliknya data yang dikirim dari form frontend sesuai format yang diharapkan backend.',
            ],
        ]);

        $this->units['6.5']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Selain itu, Frontend Developer bertanggung jawab menjaga performa tampilan, memastikan halaman tidak lambat dimuat meski datanya kompleks, dan bekerja sama dengan QA untuk memastikan tampilan berjalan konsisten di berbagai browser dan perangkat.',
            ],
        ]);

        $this->units['6.5']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Memahami mekanisme kerja ini penting supaya anggota Eksplorasi yang nanti berpindah ke track Eksekusi sudah punya bayangan realistis tentang bagaimana perannya akan terhubung dengan peran-peran lain dalam proyek nyata.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['6.5']->id,
            'question_type' => 'essay',
            'question_text' => 'Skenario: Kamu ditugaskan sebagai Frontend Developer untuk membangun halaman daftar tugas proyek. UI/UX Designer sudah memberi rancangan tampilan, tapi kamu baru sadar rancangan itu mengasumsikan setiap tugas hanya punya satu penanggung jawab, padahal Backend Developer menjelaskan bahwa dari sisi database, satu tugas bisa punya banyak penanggung jawab sekaligus.'.
                "\n\nPertanyaan: Sebagai Frontend Developer, langkah apa yang akan kamu ambil menghadapi ketidaksesuaian antara rancangan Designer dan struktur data dari Backend? Kepada siapa saja kamu perlu berkomunikasi, dan apa yang perlu didiskusikan? (Minimal 150 kata.)",
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    private function seedModule7(): void
    {
        $module = Module::create([
            'order_number' => 7,
            'title' => 'Arsitektur dan Prinsip Backend',
            'description' => 'Arsitektur sistem, prinsip desain data, dan peran backend dalam tim, dijelaskan konkret dan teknis.',
            'level_number' => 4,
        ]);

        $this->seedUnit71($module);
        $this->seedUnit72($module);
        $this->seedUnit73($module);
        $this->seedUnit74($module);

        // Checkpoint Modul 7 -- KONTEN BARU disusun oleh Celo, WAJIB direview Aye.
        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Saya memahami alur lengkap sebuah request backend, dari diterima sampai data tersimpan atau response dikembalikan.',
                'Saya memahami prinsip normalisasi data dan tiga jenis relasi antar tabel (one-to-one, one-to-many, many-to-many).',
                'Saya memahami konsep kontrak API dan kenapa kontrak yang jelas penting untuk kolaborasi Frontend-Backend.',
                'Saya memahami bagaimana Backend Developer bekerja dan berkomunikasi dengan peran lain dalam tim proyek.',
            ],
            'intermezo_questions' => [
                'Setelah Modul 7, bagian backend mana yang menurutmu paling menantang untuk dikuasai: arsitektur alur request, desain skema data, atau kontrak API? Jelaskan.',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Backend Roadmap',
            'url' => 'https://roadmap.sh/backend',
            'source_name' => 'roadmap.sh',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Laravel Roadmap',
            'url' => 'https://roadmap.sh/laravel',
            'source_name' => 'roadmap.sh',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'SQL Roadmap',
            'url' => 'https://roadmap.sh/sql',
            'source_name' => 'roadmap.sh',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'MDN — HTTP overview',
            'url' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Overview',
            'source_name' => 'MDN Web Docs',
        ]);
    }

    /**
     * §2.7.1 -- Arsitektur Backend: Alur Request sampai Data Tersimpan.
     */
    private function seedUnit71(Module $module): void
    {
        $this->units['7.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Arsitektur Backend: Alur Request sampai Data Tersimpan',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
        ]);

        $order = 1;

        $this->units['7.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Backend adalah bagian aplikasi yang bekerja di balik layar, tidak terlihat langsung oleh pengguna, tapi menangani seluruh logika bisnis dan pengolahan data. Memahami arsitektur backend berarti memahami perjalanan lengkap sebuah request sejak diterima sampai data akhirnya tersimpan atau dikembalikan sebagai response.',
            ],
        ]);

        $this->units['7.1']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'ordered',
                'items' => [
                    'Routing — bagian yang menentukan request yang masuk (misalnya request untuk menyimpan tugas baru) harus diarahkan ke bagian kode mana yang menanganinya.',
                    'Controller — bagian yang menerima request tersebut dan mengoordinasikan apa yang harus dilakukan, tapi biasanya tidak menyimpan logika bisnis mendetail di dalamnya sendiri.',
                    'Lapisan logika bisnis (sering disebut service) — tempat aturan-aturan bisnis sesungguhnya dijalankan, misalnya memeriksa apakah pengguna berhak melakukan aksi tertentu, atau menghitung poin yang harus diberikan.',
                    'Lapisan model atau repository — yang berkomunikasi langsung dengan database untuk menyimpan atau mengambil data.',
                ],
            ],
        ]);

        $this->units['7.1']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Pemisahan lapisan ini bukan formalitas semata. Memisahkan controller (menerima request) dari logika bisnis (memproses aturan) dan model (mengurus data) membuat kode lebih mudah diuji, lebih mudah diubah tanpa merusak bagian lain, dan lebih mudah dipahami anggota tim baru yang bergabung di tengah proyek. WEBI-SPACE sendiri menerapkan pemisahan ini secara konsisten, misalnya lewat `PointService` yang memusatkan seluruh logika perhitungan poin di satu tempat, bukan tersebar di banyak controller.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['7.1']->id,
            'question_type' => 'practice',
            'question_text' => "Rancang alur backend untuk sebuah fitur sederhana pilihanmu sendiri (misalnya fitur \"like\" pada postingan, atau fitur \"tambah ke keranjang\" pada toko online). Isi template berikut dalam satu jawaban:\n\n".
                "1. Nama fitur dan deskripsi singkat\n".
                "2. Request apa yang dikirim dari frontend (metode dan data yang dibawa)\n".
                "3. Apa yang dilakukan lapisan routing (endpoint apa yang dituju)\n".
                "4. Apa yang dilakukan controller (langkah penerimaan dan pengecekan awal)\n".
                "5. Aturan bisnis apa yang perlu dijalankan di lapisan logika bisnis (misalnya validasi, perhitungan, pengecekan izin)\n".
                "6. Data apa yang akhirnya disimpan atau diubah di database\n".
                '7. Response apa yang dikirim balik ke frontend',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.7.2 -- Prinsip Normalisasi dan Perancangan Skema Data.
     */
    private function seedUnit72(Module $module): void
    {
        $this->units['7.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Prinsip Normalisasi dan Perancangan Skema Data',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['7.1']->id,
        ]);

        $order = 1;

        $this->units['7.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Normalisasi data adalah proses menyusun struktur tabel database supaya data tidak disimpan berulang-ulang secara tidak perlu (redundan), dan supaya perubahan data hanya perlu dilakukan di satu tempat, bukan tersebar di banyak baris yang berpotensi menjadi tidak konsisten satu sama lain.',
            ],
        ]);

        $this->units['7.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Prinsip dasarnya bisa dijelaskan lewat contoh. Bayangkan sebuah tabel pesanan yang menyimpan nama, alamat, dan nomor telepon pelanggan langsung di setiap baris pesanan. Kalau satu pelanggan memesan sepuluh kali, data nama dan alamatnya akan berulang sepuluh kali, dan kalau pelanggan itu pindah alamat, harus diubah di sepuluh baris berbeda, rawan ada yang terlewat sehingga datanya menjadi tidak konsisten.',
            ],
        ]);

        $this->units['7.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Solusinya adalah memisahkan data pelanggan ke tabel tersendiri (misalnya tabel `pelanggan`), lalu tabel pesanan cukup menyimpan referensi (foreign key) ke tabel pelanggan tersebut. Dengan begitu, data pelanggan hanya tersimpan satu kali, dan perubahan alamat cukup dilakukan di satu tempat, otomatis berlaku untuk seluruh pesanan pelanggan itu.',
            ],
        ]);

        $this->units['7.2']->contentBlocks()->create([
            'type' => 'table',
            'order' => $order++,
            'content' => [
                'headers' => ['Jenis Relasi', 'Contoh'],
                'rows' => [
                    ['One-to-one (satu-ke-satu)', 'Satu pengguna punya satu profil.'],
                    ['One-to-many (satu-ke-banyak)', 'Satu pengguna punya banyak pesanan.'],
                    ['Many-to-many (banyak-ke-banyak)', 'Banyak proyek punya banyak anggota, satu anggota bisa terlibat di banyak proyek — biasanya butuh tabel perantara/pivot untuk menyimpan relasi ini.'],
                ],
            ],
        ]);

        $this->units['7.2']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Perancangan skema data yang baik sejak awal akan menghemat banyak masalah di kemudian hari, karena database yang berantakan jauh lebih sulit diperbaiki setelah aplikasi berjalan dan sudah berisi banyak data sungguhan, dibanding diperbaiki sebelum aplikasi diluncurkan.',
            ],
        ]);

        // Ketiga pertanyaan digabung jadi SATU baris (lihat catatan
        // penyimpangan di docblock kelas).
        UnitEvaluation::create([
            'unit_id' => $this->units['7.2']->id,
            'question_type' => 'practice',
            'question_text' => "Kasus: Sebuah aplikasi perpustakaan mencatat buku, anggota perpustakaan, dan transaksi peminjaman. Satu buku bisa dipinjam berkali-kali oleh anggota berbeda dari waktu ke waktu (tapi hanya satu peminjam aktif dalam satu waktu untuk satu eksemplar buku). Satu anggota bisa meminjam banyak buku.\n\n".
                "Jawab ketiga pertanyaan berikut dalam satu jawaban:\n\n".
                "1. Tabel apa saja yang perlu dibuat? Sebutkan nama tabel dan kolom-kolom utamanya.\n".
                "2. Jenis relasi apa yang menghubungkan tabel buku, anggota, dan transaksi peminjaman (one-to-one, one-to-many, atau many-to-many)? Jelaskan alasannya.\n".
                '3. Kalau data alamat anggota disimpan langsung di tabel transaksi peminjaman alih-alih di tabel anggota tersendiri, masalah apa yang berpotensi muncul?',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.7.3 -- Arsitektur API: Kontrak Frontend dan Backend.
     */
    private function seedUnit73(Module $module): void
    {
        $this->units['7.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Arsitektur API: Kontrak Frontend dan Backend',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['7.2']->id,
        ]);

        $order = 1;

        $this->units['7.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'API (Application Programming Interface) dalam konteks web adalah antarmuka yang memungkinkan frontend dan backend berkomunikasi lewat aturan yang sudah disepakati bersama, aturan itulah yang disebut kontrak API. Kontrak ini menentukan endpoint (alamat) yang bisa diakses, metode HTTP yang dipakai, serta bentuk data yang dikirim dan diterima (biasanya dalam format JSON):',
            ],
        ]);

        $this->units['7.3']->contentBlocks()->create([
            'type' => 'table',
            'order' => $order++,
            'content' => [
                'headers' => ['Metode HTTP', 'Kegunaan'],
                'rows' => [
                    ['GET', 'Mengambil data.'],
                    ['POST', 'Mengirim data baru.'],
                    ['PUT / PATCH', 'Mengubah data.'],
                    ['DELETE', 'Menghapus data.'],
                ],
            ],
        ]);

        $this->units['7.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Kontrak API penting karena frontend dan backend seringkali dikerjakan orang atau tim berbeda, bahkan dikembangkan pada waktu yang tidak selalu bersamaan. Tanpa kontrak yang jelas dan disepakati lebih dulu, frontend bisa saja mengharapkan data dalam bentuk tertentu, sementara backend justru mengirim dalam bentuk lain, menyebabkan aplikasi gagal berfungsi meski masing-masing pihak merasa kodenya sudah benar.',
            ],
        ]);

        $this->units['7.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Format response API yang baik biasanya konsisten, misalnya selalu menyertakan status (berhasil atau gagal), data (isi sesungguhnya), dan pesan (penjelasan tambahan bila diperlukan, terutama saat gagal). Dokumentasi API yang jelas, mencantumkan seluruh endpoint beserta contoh request dan response-nya, menjadi jembatan komunikasi yang sangat penting antara Frontend dan Backend Developer.',
            ],
        ]);

        $this->units['7.3']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Kesalahan memahami atau mengabaikan kontrak API adalah salah satu sumber bug paling umum dalam kerja tim, karena bug semacam ini seringkali baru terlihat saat kedua sisi (frontend dan backend) sudah selesai dikerjakan terpisah dan mulai disatukan.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['7.3']->id,
            'question_type' => 'practice',
            'question_text' => "Rancang kontrak API untuk sebuah fitur \"menambahkan komentar pada sebuah postingan\". Isi template berikut dalam satu jawaban:\n\n".
                "1. Endpoint (contoh format: /api/postingan/{id}/komentar)\n".
                "2. Metode HTTP yang dipakai (GET/POST/PUT/DELETE) beserta alasannya\n".
                "3. Data yang dikirim frontend ke backend (contoh format JSON, sebutkan field dan tipe datanya, misalnya isi_komentar bertipe teks)\n".
                "4. Contoh response yang dikembalikan backend jika berhasil (format JSON, sertakan status, data, pesan)\n".
                '5. Contoh response yang dikembalikan backend jika gagal, misalnya kalau isi_komentar dikirim kosong (format JSON, sertakan status, pesan error yang jelas)',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.7.4 -- Mekanisme Peran Backend dalam Tim Proyek.
     */
    private function seedUnit74(Module $module): void
    {
        $this->units['7.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Mekanisme Peran Backend dalam Tim Proyek',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'essay',
            'prerequisite_unit_id' => $this->units['7.3']->id,
        ]);

        $order = 1;

        $this->units['7.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Melengkapi pemahaman arsitektur, sistem data, dan API, unit ini menyintesiskan bagaimana seorang Backend Developer benar-benar bekerja dalam mekanisme tim proyek sehari-hari.',
            ],
        ]);

        $this->units['7.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Backend Developer bertanggung jawab menerjemahkan kebutuhan bisnis (dari Product Manager) menjadi aturan-aturan konkret dalam kode, misalnya aturan siapa yang boleh mengakses data tertentu, bagaimana poin dihitung, atau bagaimana status sebuah tugas berubah dari waktu ke waktu. Ini menuntut Backend Developer memahami logika bisnis secara mendalam, bukan sekadar menulis kode yang "jalan".',
            ],
        ]);

        $this->units['7.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Backend Developer juga bertanggung jawab menjaga keamanan data, memastikan hanya pengguna yang berhak yang bisa mengakses atau mengubah data tertentu (akan dibahas mendalam di Modul 8), dan menjaga performa, memastikan proses pengambilan data dari database tidak lambat meski jumlah data terus bertambah seiring aplikasi dipakai lebih banyak orang.',
            ],
        ]);

        $this->units['7.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Kolaborasi dengan Frontend Developer terjadi lewat kontrak API yang sudah disepakati, sementara kolaborasi dengan QA terjadi lewat pengujian skenario-skenario yang mungkin gagal, termasuk skenario yang jarang terpikirkan seperti data kosong, input yang sangat besar, atau permintaan yang datang bersamaan dalam jumlah banyak (concurrent request).',
            ],
        ]);

        $this->units['7.4']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Memahami mekanisme kerja ini penting supaya anggota Eksplorasi yang nanti berpindah ke track Eksekusi bisa langsung berkontribusi dengan bayangan realistis tentang tanggung jawab yang akan diembannya sebagai Backend Developer dalam proyek nyata WEBI-SPACE.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['7.4']->id,
            'question_type' => 'essay',
            'question_text' => 'Skenario: Kamu ditugaskan sebagai Backend Developer untuk fitur pemberian poin saat anggota menyelesaikan submission Praktik. Setelah fitur ini berjalan beberapa minggu, ditemukan bahwa ada anggota yang mendapat poin dobel karena mengirim submission yang sama dua kali dalam waktu berdekatan akibat koneksi internet yang lambat membuat mereka menekan tombol kirim berkali-kali.'.
                "\n\nPertanyaan: Sebagai Backend Developer, aturan atau pengecekan apa yang seharusnya ada di lapisan logika bisnis untuk mencegah masalah ini? Jelaskan juga kenapa masalah ini sebaiknya dicegah di sisi backend, bukan hanya mengandalkan frontend menonaktifkan tombol setelah diklik. (Minimal 150 kata.)",
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    private function seedModule8(): void
    {
        $module = Module::create([
            'order_number' => 8,
            'title' => 'Keamanan Aplikasi Web',
            'description' => 'Setiap ancaman dijelaskan dengan penyebab teknis dan solusi strategis, cakupan selengkap mungkin.',
            'level_number' => 5,
        ]);

        $this->seedUnit81($module);
        $this->seedUnit82($module);
        $this->seedUnit83($module);
        $this->seedUnit84($module);
        $this->seedUnit85($module);
        $this->seedUnit86($module);

        // Checkpoint Modul 8 -- KONTEN BARU disusun oleh Celo, WAJIB direview Aye.
        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Saya memahami mekanisme SQL Injection dan Command Injection, serta solusi parameterized query/prepared statement.',
                'Saya memahami mekanisme XSS dan CSRF, serta solusi output encoding dan CSRF token.',
                'Saya memahami kelemahan umum autentikasi dan manajemen sesi (password hashing, kebijakan password, kedaluwarsa sesi).',
                'Saya bisa mengenali kesalahan konfigurasi yang menyebabkan kebocoran data sensitif.',
                'Saya memahami bahwa kebocoran data nyata biasanya akumulasi kelemahan dasar, bukan serangan super canggih.',
                'Saya memahami lima prinsip berpikir defensif dan bisa menerapkannya untuk mengevaluasi sebuah fitur.',
            ],
            'intermezo_questions' => [
                'Dari seluruh ancaman keamanan di Modul 8, mana yang menurutmu paling mudah terlewat saat memakai kode hasil AI-generate tanpa ditinjau ulang? Jelaskan.',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'OWASP Top 10',
            'url' => 'https://owasp.org/www-project-top-ten/',
            'source_name' => 'OWASP (resmi)',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'OWASP Cheat Sheet Series',
            'url' => 'https://cheatsheetseries.owasp.org/',
            'source_name' => 'OWASP (resmi)',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Cybersecurity Roadmap',
            'url' => 'https://roadmap.sh/cyber-security',
            'source_name' => 'roadmap.sh',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'MDN — Web security',
            'url' => 'https://developer.mozilla.org/en-US/docs/Web/Security',
            'source_name' => 'MDN Web Docs',
        ]);
    }

    /**
     * §2.8.1 -- Ancaman Injeksi: SQL Injection dan Command Injection.
     */
    private function seedUnit81(Module $module): void
    {
        $this->units['8.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Ancaman Injeksi: SQL Injection dan Command Injection',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
        ]);

        $order = 1;

        $this->units['8.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Serangan injeksi terjadi ketika penyerang menyisipkan perintah berbahaya lewat input yang seharusnya hanya berisi data biasa, memanfaatkan celah di mana aplikasi memproses input pengguna sebagai bagian dari perintah yang dijalankan sistem, alih-alih memperlakukannya murni sebagai data.',
            ],
        ]);

        $this->units['8.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'SQL Injection terjadi ketika input pengguna langsung digabungkan ke dalam perintah query database tanpa penyaringan, memungkinkan penyerang menyisipkan perintah SQL tambahan. Misalnya, sebuah form login yang menyusun query dengan menggabungkan langsung nilai input pengguna, memungkinkan penyerang mengetik input khusus yang membuat query tersebut selalu bernilai benar, sehingga bisa login tanpa mengetahui password yang sesungguhnya, atau bahkan mengambil seluruh isi database.',
            ],
        ]);

        $this->units['8.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Command Injection terjadi ketika aplikasi menjalankan perintah sistem operasi menggunakan input pengguna tanpa penyaringan, memungkinkan penyerang menyisipkan perintah tambahan yang dijalankan langsung oleh server, misalnya perintah untuk menghapus file atau membuka akses tidak sah ke server.',
            ],
        ]);

        $this->units['8.1']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Solusi Teknis',
                'body' => 'Tidak pernah menggabungkan input pengguna langsung ke dalam perintah, melainkan memakai teknik parameterized query atau prepared statement untuk SQL (di mana input selalu diperlakukan murni sebagai data, bukan bagian dari perintah), serta menghindari sama sekali menjalankan perintah sistem operasi berdasarkan input pengguna mentah, atau jika benar-benar diperlukan, melakukan validasi dan penyaringan sangat ketat.',
            ],
        ]);

        // Kode rentan disalin apa adanya dari sumber -- TIDAK ada payload
        // serangan asli ditambahkan, sesuai batasan konten keamanan prompt ini.
        UnitEvaluation::create([
            'unit_id' => $this->units['8.1']->id,
            'question_type' => 'practice',
            'question_text' => "Berikut potongan kode (contoh pseudocode PHP) yang rentan SQL Injection, perlu kamu audit:\n\n".
                "\$username = \$_POST['username'];\n".
                "\$password = \$_POST['password'];\n".
                "\$query = \"SELECT * FROM users WHERE username = '\$username' AND password = '\$password'\";\n".
                "\$result = mysqli_query(\$koneksi, \$query);\n\n".
                'Pertanyaan audit: Jelaskan bagaimana penyerang bisa memanfaatkan kode di atas untuk login tanpa mengetahui password yang benar (jelaskan konsepnya, tidak perlu menuliskan payload/string serangan yang sesungguhnya). Jelaskan solusi teknis (prepared statement/parameterized query) yang seharusnya dipakai untuk menutup celah ini.',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.8.2 -- Ancaman Sisi Klien: XSS dan CSRF.
     */
    private function seedUnit82(Module $module): void
    {
        $this->units['8.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Ancaman Sisi Klien: XSS dan CSRF',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['8.1']->id,
        ]);

        $order = 1;

        $this->units['8.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Cross-Site Scripting (XSS) terjadi ketika penyerang berhasil menyisipkan kode skrip berbahaya ke dalam halaman web yang kemudian dijalankan di browser pengguna lain yang mengunjungi halaman tersebut, memanfaatkan celah di mana aplikasi menampilkan input pengguna secara langsung tanpa membersihkan atau menyaringnya lebih dulu. Contoh dampaknya, skrip yang disisipkan bisa mencuri data sesi pengguna lain atau mengarahkan mereka ke halaman palsu.',
            ],
        ]);

        $this->units['8.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Cross-Site Request Forgery (CSRF) terjadi ketika penyerang menipu browser pengguna yang sedang login di sebuah aplikasi untuk secara tidak sadar mengirim permintaan (misalnya mengubah password atau melakukan transaksi) tanpa sepengetahuan pengguna tersebut, memanfaatkan fakta bahwa browser otomatis menyertakan data sesi login setiap kali mengirim permintaan ke aplikasi itu, dari halaman manapun permintaan itu dipicu.',
            ],
        ]);

        $this->units['8.2']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Solusi',
                'body' => 'Solusi untuk XSS adalah selalu membersihkan (sanitize) dan meng-encode data yang berasal dari input pengguna sebelum ditampilkan kembali di halaman, memastikan skrip yang disisipkan diperlakukan sebagai teks biasa, bukan dijalankan sebagai kode. Solusi untuk CSRF adalah memakai token CSRF, yaitu kode unik rahasia yang disertakan di setiap form dan diperiksa server, memastikan permintaan benar-benar berasal dari halaman aplikasi itu sendiri, bukan dipicu diam-diam dari halaman lain.',
            ],
        ]);

        $this->units['8.2']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Kedua ancaman ini menunjukkan pentingnya tidak pernah mempercayai input pengguna secara membabi buta, prinsip yang berlaku di seluruh aspek keamanan aplikasi web.',
            ],
        ]);

        // Kedua potongan kode digabung jadi SATU baris (Koreksi Wajib poin 3),
        // disalin apa adanya dari sumber tanpa payload serangan asli.
        UnitEvaluation::create([
            'unit_id' => $this->units['8.2']->id,
            'question_type' => 'practice',
            'question_text' => "Berikut dua potongan kode. Identifikasi ancaman spesifik pada masing-masing dan solusinya.\n\n".
                "Kode 1 (menampilkan komentar pengguna):\n".
                "<div class=\"komentar\"><?php echo \$_POST['isi_komentar']; ?></div>\n".
                "Pertanyaan 1: Ancaman apa yang mengintai kode ini, dan bagaimana solusinya?\n\n".
                "Kode 2 (form ubah password tanpa token):\n".
                "<form action=\"/ubah-password\" method=\"POST\">\n".
                "  <input type=\"password\" name=\"password_baru\">\n".
                "  <button type=\"submit\">Ubah Password</button>\n".
                "</form>\n".
                'Pertanyaan 2: Ancaman apa yang mengintai form ini karena tidak menyertakan token keamanan, dan bagaimana solusinya?',
            'options' => null,
            'correct_answer' => 'Kode 1: XSS, karena input pengguna langsung ditampilkan tanpa disaring/di-encode. Solusinya adalah meng-encode output (misalnya lewat fungsi seperti htmlspecialchars) sebelum ditampilkan. Kode 2: CSRF, karena tidak ada token unik yang memverifikasi permintaan benar-benar berasal dari form aplikasi itu sendiri. Solusinya adalah menyertakan CSRF token tersembunyi di form dan memverifikasinya di server sebelum memproses permintaan.',
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.8.3 -- Kelemahan Autentikasi dan Manajemen Sesi.
     */
    private function seedUnit83(Module $module): void
    {
        $this->units['8.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Kelemahan Autentikasi dan Manajemen Sesi',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['8.2']->id,
        ]);

        $order = 1;

        $this->units['8.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Autentikasi adalah proses memverifikasi identitas pengguna, biasanya lewat kombinasi username dan password. Manajemen sesi adalah cara aplikasi mengingat bahwa seorang pengguna sudah login, sehingga tidak perlu memasukkan password berulang kali di setiap halaman yang dibuka.',
            ],
        ]);

        $this->units['8.3']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'ordered',
                'items' => [
                    'Penyimpanan password yang tidak aman — misalnya menyimpan password dalam bentuk teks biasa (plain text) di database, sehingga kalau database bocor, seluruh password pengguna langsung terekspos. Solusinya adalah selalu melakukan hashing pada password sebelum disimpan, menggunakan algoritma hashing yang dirancang khusus untuk password (seperti bcrypt).',
                    'Kebijakan password yang lemah — mengizinkan password yang terlalu pendek atau terlalu umum, memudahkan penyerang menebaknya lewat teknik brute force atau memakai daftar password umum yang sudah bocor sebelumnya.',
                    'Manajemen sesi yang buruk — misalnya sesi login yang tidak pernah kedaluwarsa, atau ID sesi yang mudah ditebak, memungkinkan penyerang membajak sesi pengguna lain. Solusinya termasuk menetapkan waktu kedaluwarsa sesi yang wajar, menghasilkan ID sesi yang acak dan sulit ditebak, serta selalu membuat ulang ID sesi saat pengguna berhasil login.',
                ],
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.3']->id,
            'question_type' => 'practice',
            'question_text' => 'Skenario: Sebuah aplikasi menyimpan password pengguna dalam bentuk teks biasa di database (bisa dibaca langsung tanpa proses apapun), mengizinkan password sependek tiga karakter, dan sesi login pengguna tidak pernah kedaluwarsa sampai mereka klik tombol logout secara manual.'.
                "\n\nPertanyaan: Identifikasi tiga kelemahan keamanan pada skenario di atas. Untuk masing-masing kelemahan, jelaskan solusi teknis yang seharusnya diterapkan (mengacu pada konsep hashing, kebijakan password, dan manajemen kedaluwarsa sesi yang sudah dipelajari).",
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.8.4 -- Kesalahan Konfigurasi dan Kebocoran Data Sensitif.
     */
    private function seedUnit84(Module $module): void
    {
        $this->units['8.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Kesalahan Konfigurasi dan Kebocoran Data Sensitif',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['8.3']->id,
        ]);

        $order = 1;

        $this->units['8.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Kesalahan konfigurasi adalah salah satu penyebab kebocoran data paling umum, seringkali bukan karena kode aplikasinya yang salah, tapi karena pengaturan di sekitar aplikasi yang lengah. Beberapa contoh konkret perlu dikenali.',
            ],
        ]);

        $this->units['8.4']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'ordered',
                'items' => [
                    'File konfigurasi yang berisi kredensial sensitif (seperti password database atau API key) ikut terunggah ke repository publik seperti GitHub, sehingga siapapun bisa melihat dan menyalahgunakannya. File seperti `.env` yang berisi kredensial harus selalu dimasukkan ke `.gitignore` agar tidak pernah ikut ter-commit.',
                    'Pesan error yang terlalu detail ditampilkan langsung ke pengguna di lingkungan produksi, misalnya menampilkan struktur query database atau lokasi file server saat terjadi error, informasi yang sangat berguna bagi penyerang untuk memahami struktur sistem dan mencari celah lebih lanjut.',
                    'Folder atau file yang seharusnya bersifat privat justru bisa diakses langsung lewat URL karena pengaturan izin akses (permission) yang tidak tepat, misalnya folder penyimpanan file unggahan pengguna yang bisa diakses siapapun tanpa autentikasi.',
                    'Penggunaan versi software atau library yang sudah usang dan diketahui memiliki celah keamanan yang belum ditambal (patch), padahal pembaruan yang menutup celah tersebut sudah tersedia tapi belum dipasang.',
                ],
            ],
        ]);

        $this->units['8.4']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Keamanan bukan cuma soal kode aplikasi yang ditulis, tapi juga soal bagaimana keseluruhan sistem di sekitarnya dikonfigurasi dan dijaga tetap mutakhir.',
            ],
        ]);

        // Keempat kondisi digabung jadi SATU baris (Koreksi Wajib poin 3).
        UnitEvaluation::create([
            'unit_id' => $this->units['8.4']->id,
            'question_type' => 'practice',
            'question_text' => "Berikut empat kondisi yang perlu kamu audit. Tandai mana yang berisiko dan jelaskan risikonya masing-masing.\n\n".
                "1. File .env berisi password database ikut ter-commit dan terlihat di repository GitHub publik\n".
                "2. Halaman error di lingkungan produksi menampilkan detail lengkap query SQL yang gagal dijalankan\n".
                "3. Folder storage/pribadi/ yang berisi dokumen pribadi anggota bisa diakses langsung lewat URL tanpa login\n".
                "4. Aplikasi memakai versi library yang dirilis tiga tahun lalu dan sudah ada tiga pembaruan keamanan sejak saat itu yang belum dipasang\n\n".
                'Pertanyaan: Untuk setiap kondisi di atas, jelaskan risiko konkretnya dan langkah perbaikan yang seharusnya dilakukan.',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.8.5 -- Studi Kasus Kebocoran Data Nyata.
     */
    private function seedUnit85(Module $module): void
    {
        $this->units['8.5'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 5,
            'title' => 'Studi Kasus Kebocoran Data Nyata',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'essay',
            'prerequisite_unit_id' => $this->units['8.4']->id,
        ]);

        $order = 1;

        $this->units['8.5']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Unit ini menyintesiskan empat unit sebelumnya lewat pembelajaran dari kasus kebocoran data yang benar-benar terjadi di dunia nyata, memperlihatkan bahwa ancaman-ancaman yang sudah dipelajari bukan sekadar teori di buku, tapi benar-benar menyebabkan kerugian nyata bagi perusahaan dan jutaan pengguna.',
            ],
        ]);

        $this->units['8.5']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Banyak kasus kebocoran data besar yang terdokumentasi publik ternyata disebabkan kombinasi dari kelemahan-kelemahan yang sudah dipelajari di unit sebelumnya, bukan serangan super canggih yang mustahil dicegah. Kasus umum yang berulang polanya termasuk password yang disimpan tanpa hashing yang layak, celah SQL Injection yang tidak ditambal bertahun-tahun meski sudah diketahui, kesalahan konfigurasi server yang membuka akses ke database secara tidak sengaja, dan penggunaan software usang yang celahnya sudah diketahui publik tapi belum ditambal perusahaan bersangkutan.',
            ],
        ]);

        $this->units['8.5']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Kebocoran data besar jarang disebabkan satu kesalahan tunggal yang sangat canggih, melainkan akumulasi dari beberapa kelemahan dasar yang masing-masing sebenarnya bisa dicegah dengan praktik keamanan yang sudah dipelajari di modul ini. Ini menegaskan bahwa memahami dan menerapkan prinsip dasar keamanan secara konsisten jauh lebih penting daripada mengejar solusi keamanan yang rumit dan canggih tapi mengabaikan dasar-dasarnya.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.5']->id,
            'question_type' => 'essay',
            'question_text' => "Cari secara mandiri satu kasus kebocoran data perusahaan yang pernah dipublikasikan secara luas di media (boleh perusahaan Indonesia atau internasional), lalu tulis analisis singkat (minimal 150 kata) menjawab pertanyaan panduan berikut:\n\n".
                "1. Perusahaan atau layanan apa yang mengalami kebocoran data tersebut, dan kapan?\n".
                "2. Berdasarkan yang kamu baca, penyebab teknis apa yang paling mungkin menjadi akar masalahnya (kaitkan dengan ancaman-ancaman yang sudah dipelajari di Modul 8, seperti SQL Injection, kesalahan konfigurasi, atau autentikasi lemah)?\n".
                '3. Menurutmu, langkah pencegahan apa yang seharusnya sudah diterapkan perusahaan tersebut sebelum insiden terjadi?',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.8.6 -- Prinsip Berpikir Defensif dalam Membangun Aplikasi.
     */
    private function seedUnit86(Module $module): void
    {
        $this->units['8.6'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 6,
            'title' => 'Prinsip Berpikir Defensif dalam Membangun Aplikasi',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['8.5']->id,
        ]);

        $order = 1;

        $this->units['8.6']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Setelah mempelajari berbagai ancaman spesifik, unit penutup Modul 8 ini merangkumnya menjadi satu prinsip berpikir menyeluruh yang perlu tertanam dalam cara anggota membangun aplikasi ke depannya, disebut defensive programming atau berpikir defensif.',
            ],
        ]);

        $this->units['8.6']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'ordered',
                'items' => [
                    'Jangan pernah mempercayai input darimanapun asalnya, baik dari pengguna, dari aplikasi lain, maupun dari API pihak ketiga — selalu validasi dan saring input sebelum diproses lebih lanjut.',
                    'Terapkan prinsip least privilege — berikan akses seminimal mungkin yang benar-benar dibutuhkan untuk setiap peran atau komponen sistem, jangan memberi akses lebih luas dari yang diperlukan hanya demi kepraktisan.',
                    'Asumsikan bahwa kesalahan akan terjadi — siapkan penanganan error yang baik (tidak menampilkan detail sensitif ke pengguna, tapi tetap mencatat detail itu untuk keperluan debugging internal).',
                    'Terapkan pertahanan berlapis (defense in depth) — jangan bergantung pada satu lapisan keamanan saja, karena kalau satu lapisan gagal ditembus, lapisan lain masih bisa mencegah dampak lebih jauh.',
                    'Selalu ikuti pembaruan keamanan dari teknologi yang dipakai, dan jangan menunda pemasangan pembaruan yang menutup celah yang sudah diketahui publik.',
                ],
            ],
        ]);

        $this->units['8.6']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Bukan Checklist Sekali Jalan',
                'body' => 'Prinsip berpikir defensif ini bukan checklist sekali jalan, melainkan kebiasaan berpikir yang harus terus dipraktikkan setiap kali menulis kode baru, termasuk saat mengevaluasi kode hasil AI-generate yang mungkin terlihat berfungsi tapi belum tentu memikirkan aspek keamanan sama sekali.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['8.6']->id,
            'question_type' => 'practice',
            'question_text' => 'Deskripsi fitur: Sebuah fitur upload foto profil, di mana pengguna mengunggah file gambar, lalu sistem langsung menyimpan file itu dengan nama asli yang diberikan pengguna ke folder publik di server, tanpa pengecekan tipe file, tanpa batas ukuran, dan file itu langsung bisa diakses semua orang lewat URL.'.
                "\n\nPertanyaan: Evaluasi fitur ini berdasarkan kelima prinsip berpikir defensif (validasi input, least privilege, penanganan error, pertahanan berlapis, pembaruan berkala). Untuk masing-masing prinsip yang relevan, jelaskan risiko yang mengintai dan perbaikan konkret yang perlu dilakukan pada fitur ini.",
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    private function seedModule9(): void
    {
        $module = Module::create([
            'order_number' => 9,
            'title' => 'Topik Lanjutan Pengembangan Web',
            'description' => 'Konsep yang membedakan aplikasi selesai dengan aplikasi siap pakai secara nyata.',
            'level_number' => 5,
        ]);

        $this->seedUnit91($module);
        $this->seedUnit92($module);
        $this->seedUnit93($module);
        $this->seedUnit94($module);

        // Checkpoint Modul 9 -- KONTEN BARU disusun oleh Celo, WAJIB direview Aye.
        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Saya memahami tiga komponen teknis PWA: service worker, web app manifest, dan HTTPS.',
                'Saya memahami mekanisme dasar integrasi API pihak ketiga, termasuk keharusan menyimpan API key di sisi server.',
                'Saya memahami konsep dasar payment gateway dan kenapa aplikasi sebaiknya tidak menyimpan data pembayaran sensitif sendiri.',
                'Saya memahami berbagai metode deployment modern (shared hosting, VPS, PaaS, containerization) dan kapan masing-masing cocok dipakai.',
            ],
            'intermezo_questions' => [
                'Dari empat topik lanjutan di Modul 9, mana yang paling relevan dengan ide proyek akhir yang mulai kamu pikirkan untuk Modul 10?',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'web.dev — Progressive Web Apps',
            'url' => 'https://web.dev/explore/progressive-web-apps',
            'source_name' => 'web.dev (Google)',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'MDN — Progressive web apps',
            'url' => 'https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps',
            'source_name' => 'MDN Web Docs',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'DevOps Roadmap (deployment/CI-CD)',
            'url' => 'https://roadmap.sh/devops',
            'source_name' => 'roadmap.sh',
        ]);
    }

    /**
     * §2.9.1 -- Arsitektur Progressive Web App.
     */
    private function seedUnit91(Module $module): void
    {
        $this->units['9.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Arsitektur Progressive Web App',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
        ]);

        $order = 1;

        $this->units['9.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Progressive Web App (PWA) adalah pendekatan membangun aplikasi web sehingga terasa dan berfungsi mirip aplikasi native (aplikasi yang diinstal dari App Store atau Play Store), meski tetap dijalankan lewat teknologi web biasa. PWA menjadi jembatan antara kemudahan distribusi aplikasi web (tidak perlu instalasi lewat toko aplikasi) dengan pengalaman pengguna yang lebih baik layaknya aplikasi native.',
            ],
        ]);

        $this->units['9.1']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'ordered',
                'items' => [
                    'Service worker — skrip yang berjalan terpisah di latar belakang browser, memungkinkan aplikasi tetap berfungsi sebagian meski koneksi internet terputus, lewat mekanisme caching (menyimpan salinan data atau tampilan sebelumnya).',
                    'Web app manifest — file konfigurasi yang mendefinisikan bagaimana aplikasi tampil saat "diinstal" ke perangkat pengguna, termasuk ikon, nama, dan warna tema.',
                    'HTTPS — PWA mewajibkan koneksi aman karena service worker punya akses cukup dalam ke browser sehingga harus dipastikan tidak disalahgunakan lewat koneksi yang tidak terenkripsi.',
                ],
            ],
        ]);

        $this->units['9.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Manfaat konkret PWA termasuk kemampuan bekerja offline atau dengan koneksi tidak stabil, kemampuan ditambahkan ke layar utama perangkat pengguna layaknya aplikasi biasa, dan performa yang lebih responsif berkat caching, tanpa pengguna harus mengunduh dari toko aplikasi maupun developer harus melalui proses review yang panjang dari platform seperti App Store.',
            ],
        ]);

        $this->units['9.1']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Memahami PWA penting sebagai pembeda antara aplikasi web yang sekadar "selesai dibangun" dengan aplikasi web yang benar-benar dirancang matang untuk pengalaman pengguna nyata di berbagai kondisi jaringan.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['9.1']->id,
            'question_type' => 'practice',
            'question_text' => "Rancang penerapan PWA untuk sebuah aplikasi web sederhana pilihanmu sendiri. Isi template berikut dalam satu jawaban:\n\n".
                "1. Nama aplikasi dan fitur utamanya\n".
                "2. Fitur apa yang tetap perlu berfungsi meski aplikasi offline (misalnya melihat data yang sudah pernah dimuat sebelumnya), dan data apa yang perlu di-cache oleh service worker untuk mendukung itu\n".
                "3. Isi web app manifest untuk aplikasi ini (nama aplikasi, deskripsi ikon yang sesuai, warna tema utama)\n".
                '4. Satu risiko atau keterbatasan yang perlu diwaspadai kalau menerapkan PWA untuk aplikasi ini (misalnya data yang di-cache menjadi usang/tidak sinkron dengan server)',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.9.2 -- Mekanisme Integrasi API Pihak Ketiga.
     */
    private function seedUnit92(Module $module): void
    {
        $this->units['9.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Mekanisme Integrasi API Pihak Ketiga',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['9.1']->id,
        ]);

        $order = 1;

        $this->units['9.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Banyak aplikasi modern tidak membangun semua fiturnya dari nol, melainkan mengintegrasikan layanan pihak ketiga lewat API, misalnya API peta (seperti Google Maps), API cuaca, atau API pengiriman pesan (seperti WhatsApp Business API).',
            ],
        ]);

        $this->units['9.2']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Penerapan Nyata di WEBI-SPACE',
                'body' => 'WEBI-SPACE sendiri mengintegrasikan API model AI pihak ketiga (Gemini API) untuk fitur WEBI Chat — contoh nyata integrasi API yang sedang berjalan di proyek ini.',
            ],
        ]);

        $this->units['9.2']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'ordered',
                'items' => [
                    'Mendaftar dan mendapatkan API key — kode rahasia unik yang mengidentifikasi aplikasi yang berhak mengakses layanan tersebut, sekaligus dipakai penyedia layanan untuk menghitung pemakaian dan tagihan biaya.',
                    'Membaca dokumentasi API — memahami endpoint yang tersedia, format request yang diharapkan, dan format response yang akan diterima.',
                    'Menangani rate limit — batas jumlah permintaan yang boleh dikirim dalam periode waktu tertentu, penting diperhatikan supaya aplikasi tidak tiba-tiba berhenti berfungsi karena melebihi batas yang diizinkan.',
                ],
            ],
        ]);

        $this->units['9.2']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Keamanan menjadi perhatian khusus dalam integrasi API pihak ketiga. API key harus selalu disimpan di sisi server (backend), tidak pernah ditulis langsung di kode frontend yang bisa dilihat siapapun lewat DevTools browser, karena API key yang bocor bisa disalahgunakan orang lain dan menyebabkan tagihan biaya membengkak atau data disalahgunakan.',
            ],
        ]);

        $this->units['9.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Kegagalan menangani skenario ketika API pihak ketiga sedang bermasalah atau lambat merespons juga perlu dipikirkan sejak desain awal, aplikasi sebaiknya tetap punya penanganan yang baik (misalnya pesan error yang jelas) alih-alih ikut mati total hanya karena satu layanan eksternal sedang bermasalah.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['9.2']->id,
            'question_type' => 'practice',
            'question_text' => "Rancang integrasi salah satu API pihak ketiga (pilih salah satu: API cuaca, API peta, atau API model AI) untuk sebuah fitur aplikasi sederhana. Isi template berikut dalam satu jawaban:\n\n".
                "1. API pihak ketiga yang dipilih dan fitur yang akan dibangun\n".
                "2. Data apa yang perlu dikirim ke API tersebut, dan data apa yang diharapkan diterima kembali\n".
                "3. Di lapisan mana (frontend atau backend) API key seharusnya disimpan, dan kenapa\n".
                '4. Apa yang seharusnya terjadi di aplikasi kalau API pihak ketiga tersebut sedang tidak merespons atau error, supaya aplikasi tidak ikut gagal total',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.9.3 -- Konsep Dasar Payment Gateway.
     */
    private function seedUnit93(Module $module): void
    {
        $this->units['9.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Konsep Dasar Payment Gateway',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['9.2']->id,
        ]);

        $order = 1;

        $this->units['9.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Payment gateway adalah layanan yang menjembatani transaksi pembayaran online antara aplikasi, pengguna, dan lembaga keuangan (bank atau penyedia dompet digital), menangani seluruh proses sensitif pemrosesan pembayaran sehingga aplikasi tidak perlu (dan sebaiknya tidak pernah) menangani sendiri data kartu atau rekening pengguna secara langsung.',
            ],
        ]);

        $this->units['9.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Alur dasarnya secara konsep, pengguna memilih metode pembayaran di aplikasi, aplikasi mengarahkan proses pembayaran itu ke payment gateway (baik lewat halaman terpisah milik gateway, atau lewat komponen yang disediakan gateway dan ditanam di halaman aplikasi), payment gateway memproses transaksi langsung dengan lembaga keuangan terkait, lalu mengirim notifikasi hasil transaksi (berhasil atau gagal) kembali ke aplikasi lewat mekanisme yang disebut webhook, yaitu API yang dipanggil otomatis oleh payment gateway untuk memberi tahu aplikasi tentang status transaksi terbaru.',
            ],
        ]);

        $this->units['9.3']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Prinsip Keamanan',
                'body' => 'Aplikasi sebaiknya tidak pernah menyimpan sendiri data sensitif seperti nomor kartu kredit lengkap di database miliknya, karena ini menciptakan tanggung jawab keamanan dan kepatuhan regulasi (seperti standar PCI DSS) yang sangat berat. Sebagai gantinya, aplikasi mengandalkan payment gateway yang sudah punya sertifikasi keamanan resmi untuk menangani data sensitif tersebut.',
            ],
        ]);

        $this->units['9.3']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Memahami konsep dasar ini penting bagi anggota Eksplorasi supaya ke depannya, kalau proyek Eksekusi membutuhkan fitur pembayaran, mereka sudah punya bayangan konseptual yang benar tentang pembagian tanggung jawab antara aplikasi dan payment gateway, bukan mencoba membangun sistem pemrosesan pembayaran sendiri dari nol yang berisiko tinggi.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['9.3']->id,
            'question_type' => 'practice',
            'question_text' => "Rancang alur pembayaran untuk sebuah fitur \"donasi online\" sederhana. Isi template berikut dalam satu jawaban:\n\n".
                "1. Langkah yang dilakukan pengguna di aplikasi sebelum diarahkan ke payment gateway\n".
                "2. Data apa yang aplikasi kirim ke payment gateway (misalnya jumlah donasi, ID transaksi internal), dan data sensitif apa yang TIDAK boleh disimpan sendiri oleh aplikasi\n".
                "3. Apa yang terjadi setelah payment gateway selesai memproses transaksi (jelaskan peran webhook dalam memberi tahu status transaksi ke aplikasi)\n".
                '4. Apa yang seharusnya ditampilkan ke pengguna jika status transaksi ternyata gagal',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.9.4 -- Strategi dan Metode Deployment Modern.
     */
    private function seedUnit94(Module $module): void
    {
        $this->units['9.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Strategi dan Metode Deployment Modern',
            'content' => '',
            'estimated_minutes' => 20,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['9.3']->id,
        ]);

        $order = 1;

        $this->units['9.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Deployment adalah proses membuat aplikasi yang sudah dibangun bisa diakses publik lewat internet, memindahkannya dari lingkungan pengembangan (development) ke lingkungan produksi (production) yang benar-benar dipakai pengguna sungguhan.',
            ],
        ]);

        $this->units['9.4']->contentBlocks()->create([
            'type' => 'table',
            'order' => $order++,
            'content' => [
                'headers' => ['Metode', 'Karakteristik'],
                'rows' => [
                    ['Shared hosting', 'Satu server fisik dipakai bersama banyak pengguna berbeda, biaya murah tapi kontrol dan performa terbatas, cocok untuk aplikasi skala kecil sampai menengah. (WEBI-SPACE sendiri berjalan di lingkungan shared hosting Rumahweb.)'],
                    ['Virtual Private Server (VPS)', 'Kontrol lebih penuh atas server virtual yang dipakai sendiri, cocok untuk aplikasi yang butuh konfigurasi lebih spesifik.'],
                    ['Platform-as-a-Service (PaaS)', 'Seperti Vercel atau Railway — menyederhanakan proses deployment lewat integrasi otomatis dengan repository Git, developer cukup push kode dan platform menangani sisanya.'],
                    ['Containerization (Docker)', 'Mengemas aplikasi beserta seluruh dependensinya menjadi satu paket konsisten yang bisa dijalankan di lingkungan manapun tanpa masalah "kok di komputer saya jalan tapi di server tidak".'],
                ],
            ],
        ]);

        $this->units['9.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Konsep CI/CD (Continuous Integration/Continuous Deployment) juga penting dipahami, yaitu praktik otomatisasi pengujian dan deployment setiap kali ada perubahan kode baru yang di-push, mengurangi kesalahan manual dan mempercepat siklus rilis fitur baru.',
            ],
        ]);

        $this->units['9.4']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Pemilihan strategi deployment yang tepat bergantung pada skala aplikasi, anggaran, dan tingkat kontrol yang dibutuhkan, bukan sekadar mengikuti tren tanpa mempertimbangkan kebutuhan nyata proyek.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['9.4']->id,
            'question_type' => 'practice',
            'question_text' => "Rancang strategi deployment untuk aplikasi hasil proyek akhirmu nanti di Modul 10. Isi template berikut dalam satu jawaban:\n\n".
                "1. Metode deployment yang dipilih (shared hosting/VPS/PaaS/lainnya) beserta alasannya, dipertimbangkan dari sisi biaya, skala, dan tingkat kontrol yang dibutuhkan\n".
                "2. Langkah-langkah deployment secara garis besar yang perlu dilakukan (dari kode selesai sampai bisa diakses publik lewat URL)\n".
                '3. Apa yang perlu dipersiapkan agar proses deployment ke depannya bisa lebih otomatis (kaitkan dengan konsep CI/CD yang sudah dipelajari, meski implementasi penuhnya belum wajib dilakukan di kurikulum ini)',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    private function seedModule10(): void
    {
        $module = Module::create([
            'order_number' => 10,
            'title' => 'Proyek Akhir dan Portofolio',
            'description' => 'Sintesis seluruh modul menjadi satu karya nyata yang dipublikasikan, disertai refleksi personal.',
            'level_number' => 6,
        ]);

        $this->seedUnit101($module);
        $this->seedUnit102($module);
        $this->seedUnit103($module);
        $this->seedUnit104($module);

        // Checkpoint Modul 10 (TERAKHIR seluruh kurikulum) -- KONTEN BARU
        // disusun oleh Celo, WAJIB direview Aye.
        Checkpoint::create([
            'module_id' => $module->id,
            'checklist_items' => [
                'Saya sudah merencanakan proyek akhir dengan fitur inti yang jelas dan realistis untuk waktu yang tersedia.',
                'Saya sudah membangun dan men-deploy proyek akhir sehingga bisa diakses publik lewat internet.',
                'Saya sudah menyusun dan men-deploy portofolio pribadi yang menampilkan proyek akhir ini.',
                'Saya sudah merefleksikan kontribusi personal yang tidak tergantikan AI sepanjang perjalanan kurikulum ini.',
            ],
            'intermezo_questions' => [
                'Selamat menyelesaikan seluruh kurikulum Eksplorasi WEBI-SPACE. Satu pesan atau harapan apa yang ingin kamu sampaikan untuk dirimu sendiri di titik ini, sebelum melangkah ke proyek nyata di Eksekusi?',
            ],
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'GitHub Pages Documentation',
            'url' => 'https://docs.github.com/en/pages',
            'source_name' => 'GitHub',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'web.dev — Portfolio best practices',
            'url' => 'https://web.dev/learn',
            'source_name' => 'web.dev (Google)',
        ]);

        LearningResource::create([
            'module_id' => $module->id,
            'title' => 'Full Stack Roadmap (rekap akhir)',
            'url' => 'https://roadmap.sh/full-stack',
            'source_name' => 'roadmap.sh',
        ]);
    }

    /**
     * §2.10.1 -- Perencanaan Proyek Akhir.
     */
    private function seedUnit101(Module $module): void
    {
        $this->units['10.1'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 1,
            'title' => 'Perencanaan Proyek Akhir',
            'content' => '',
            'estimated_minutes' => 25,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'practice',
        ]);

        $order = 1;

        $this->units['10.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Modul 10 adalah puncak dari seluruh kurikulum Eksplorasi, tempat anggota mensintesiskan semua yang sudah dipelajari dari Modul 1 sampai Modul 9 menjadi satu karya nyata yang bisa dipamerkan sebagai portofolio. Unit ini menekankan pentingnya perencanaan matang sebelum mulai membangun, sebuah kebiasaan yang membedakan proyek yang selesai dengan baik dari proyek yang terbengkalai di tengah jalan.',
            ],
        ]);

        $this->units['10.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Perencanaan yang baik dimulai dari memilih ide proyek yang realistis untuk diselesaikan dalam waktu yang tersedia, lebih baik proyek kecil yang selesai penuh dan rapi dibanding proyek ambisius yang berakhir setengah jadi. Ide yang baik biasanya juga mencerminkan minat pribadi anggota, karena minat ini menjadi motivasi yang membantu bertahan sampai proyek selesai.',
            ],
        ]);

        $this->units['10.1']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Perencanaan mencakup menentukan fitur inti (fitur yang benar-benar wajib ada supaya proyek berfungsi dan bermakna) dan memisahkannya dari fitur tambahan (fitur "bagus kalau ada" yang bisa ditambahkan belakangan kalau waktu masih tersisa). Anggota juga perlu menentukan tech stack yang akan dipakai, mengacu kembali pada pemahaman dari Modul 2, dan memastikan tech stack itu memang dikuasai atau realistis dipelajari dalam waktu pengerjaan proyek.',
            ],
        ]);

        $this->units['10.1']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Susun Timeline Sejak Awal',
                'body' => 'Menyusun timeline sederhana juga penting, membagi pengerjaan proyek ke beberapa tahap dengan target waktu masing-masing, supaya proyek tidak dikerjakan mendadak di akhir waktu yang tersisa.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['10.1']->id,
            'question_type' => 'practice',
            'question_text' => "Buat dokumen rancangan proyek akhir. Isi template berikut dalam satu jawaban (ini akan menjadi dasar bagi unit-unit berikutnya di modul ini):\n\n".
                "1. Nama dan deskripsi singkat proyek (apa masalah atau kebutuhan yang dijawab proyek ini)\n".
                "2. Fitur inti (wajib ada, maksimal 3-5 fitur)\n".
                "3. Fitur tambahan (opsional, kalau waktu memungkinkan)\n".
                "4. Tech stack yang akan dipakai (frontend, backend jika ada, database jika ada), mengacu pada Modul 2\n".
                '5. Timeline pengerjaan sederhana (bagi ke minimal 3 tahap dengan target waktu masing-masing, misalnya tahap perancangan, tahap pembangunan, tahap deployment dan penyempurnaan)',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.10.2 -- Eksekusi dan Deployment Proyek.
     *
     * TIDAK terhubung ke tabel projects/project_ideas Eksekusi -- murni
     * pelaporan URL lewat jawaban practice ini, sesuai catatan eksplisit
     * prompt.
     */
    private function seedUnit102(Module $module): void
    {
        $this->units['10.2'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 2,
            'title' => 'Eksekusi dan Deployment Proyek',
            'content' => '',
            'estimated_minutes' => 90,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['10.1']->id,
        ]);

        $order = 1;

        $this->units['10.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Setelah rancangan matang, unit ini masuk ke tahap eksekusi nyata, membangun proyek sesuai rencana yang sudah disusun di unit sebelumnya, lalu men-deploy-nya agar bisa diakses publik lewat internet.',
            ],
        ]);

        $this->units['10.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Selama tahap membangun, penting menerapkan seluruh prinsip yang sudah dipelajari sepanjang kurikulum, bukan sekadar membuat sesuatu yang "terlihat jalan":',
            ],
        ]);

        $this->units['10.2']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'unordered',
                'items' => [
                    'Struktur kode yang rapi dan tidak rapuh (Modul 1).',
                    'Git yang disiplin dengan histori commit yang bermakna sepanjang proses pembangunan (Modul 5).',
                    'Prinsip frontend seperti accessibility dan sistem desain yang konsisten kalau proyek melibatkan tampilan (Modul 6).',
                    'Prinsip backend seperti skema data yang ternormalisasi kalau proyek melibatkan penyimpanan data (Modul 7).',
                    'Prinsip keamanan dasar seperti validasi input (Modul 8).',
                ],
            ],
        ]);

        $this->units['10.2']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Tahap deployment mengikuti strategi yang sudah dirancang di Modul 9, memindahkan proyek dari lingkungan pengembangan ke lingkungan yang bisa diakses publik. Penting untuk menguji proyek yang sudah di-deploy secara menyeluruh, memastikan seluruh fitur inti benar-benar berfungsi di lingkungan produksi, bukan cuma berfungsi baik di komputer sendiri.',
            ],
        ]);

        $this->units['10.2']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Adaptasi Itu Wajar',
                'body' => 'Proses eksekusi jarang berjalan mulus seratus persen sesuai rencana, dan itu wajar. Bagian penting dari unit ini adalah kemampuan beradaptasi, menyesuaikan rencana ketika ternyata ada kendala teknis yang tidak terduga, tanpa kehilangan arah dari tujuan utama proyek.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['10.2']->id,
            'question_type' => 'practice',
            'question_text' => "Bangun proyek sesuai rancangan dari Unit 10.1, lalu deploy proyek itu agar bisa diakses publik. Setelah itu, laporkan hasilnya dengan menjawab ketiga pertanyaan berikut dalam satu jawaban:\n\n".
                "1. Tempelkan tautan (URL) proyek yang sudah live dan bisa diakses publik, beserta tautan repository GitHub-nya.\n".
                "2. Ceritakan satu kendala teknis nyata yang kamu temui selama membangun atau men-deploy proyek ini, dan bagaimana kamu menyelesaikannya.\n".
                '3. Dari seluruh prinsip yang sudah dipelajari sepanjang kurikulum (struktur kode, Git, frontend, backend, keamanan), sebutkan satu prinsip yang benar-benar kamu terapkan secara sadar di proyek ini, dan jelaskan bagaimana penerapannya.',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.10.3 -- Penyusunan Portofolio.
     *
     * TIDAK terhubung ke tabel projects/project_ideas Eksekusi, sama seperti
     * Unit 10.2.
     */
    private function seedUnit103(Module $module): void
    {
        $this->units['10.3'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 3,
            'title' => 'Penyusunan Portofolio',
            'content' => '',
            'estimated_minutes' => 45,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $this->units['10.2']->id,
        ]);

        $order = 1;

        $this->units['10.3']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Proyek yang bagus tidak akan dilihat orang lain kalau tidak ditampilkan dengan baik. Unit ini membahas cara menyusun portofolio pribadi, sebuah halaman web yang menampilkan diri dan karya-karya anggota kepada dunia luar, termasuk kepada calon pemberi kerja atau kolaborator di masa depan.',
            ],
        ]);

        $this->units['10.3']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'ordered',
                'items' => [
                    'Tentang (about) — memperkenalkan siapa pemiliknya secara singkat dan apa yang ditekuni.',
                    'Keahlian (skills) — menampilkan teknologi dan bahasa pemrograman yang dikuasai.',
                    'Karya (projects) — bagian paling penting, menampilkan proyek-proyek yang sudah dibuat lengkap dengan deskripsi singkat, teknologi yang dipakai, tautan demo langsung, dan tautan repository kodenya.',
                    'Kontak — memudahkan orang yang tertarik untuk menghubungi.',
                ],
            ],
        ]);

        $this->units['10.3']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Prinsip Penulisan Deskripsi Proyek',
                'body' => 'Jelaskan bukan cuma apa yang dibuat, tapi juga masalah apa yang diselesaikan proyek itu dan keputusan teknis penting apa yang diambil selama membangunnya. Ini menunjukkan proses berpikir di balik proyek, bukan sekadar hasil akhirnya, sesuatu yang sangat dihargai siapapun yang meninjau portofolio, karena menunjukkan pemahaman, bukan cuma kemampuan meniru.',
            ],
        ]);

        $this->units['10.3']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Portofolio itu sendiri, karena berbentuk halaman web, juga menjadi kesempatan langsung mempraktikkan prinsip HTML, CSS, dan accessibility yang sudah dipelajari di Modul 6, sekaligus menjadi bukti nyata kemampuan teknis pemiliknya.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['10.3']->id,
            'question_type' => 'practice',
            'question_text' => 'Bangun halaman portofolio pribadi memuat keempat bagian yang sudah dijelaskan (tentang diri, keahlian, karya, kontak), masukkan minimal proyek yang sudah kamu buat di Unit 10.2, lalu deploy portofolio itu (misalnya lewat GitHub Pages).'.
                "\n\nPertanyaan pelaporan: Tempelkan URL portofoliomu yang sudah live. Jelaskan singkat bagaimana kamu menuliskan deskripsi proyek di portofolio ini, apakah sudah mencakup masalah yang diselesaikan dan keputusan teknis penting, bukan cuma daftar fitur.",
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }

    /**
     * §2.10.4 -- Refleksi Personal: Kontribusi yang Tidak Tergantikan oleh AI.
     *
     * Unit penutup RESMI seluruh kurikulum Eksplorasi.
     */
    private function seedUnit104(Module $module): void
    {
        $this->units['10.4'] = Unit::create([
            'module_id' => $module->id,
            'order_number' => 4,
            'title' => 'Refleksi Personal: Kontribusi yang Tidak Tergantikan oleh AI',
            'content' => '',
            'estimated_minutes' => 25,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'essay',
            'prerequisite_unit_id' => $this->units['10.3']->id,
        ]);

        $order = 1;

        $this->units['10.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Unit penutup seluruh kurikulum ini mengajak anggota merenungkan kembali perjalanan belajarnya, sekaligus menjawab pertanyaan yang menjadi filosofi dasar kurikulum sejak awal, apa yang membuat seorang developer manusia tetap relevan dan bernilai di tengah kemampuan AI generatif yang terus berkembang.',
            ],
        ]);

        $this->units['10.4']->contentBlocks()->create([
            'type' => 'text',
            'order' => $order++,
            'content' => [
                'markdown' => 'Sepanjang kurikulum ini, anggota sudah belajar bahwa AI bisa menulis kode dengan cepat, tapi tidak bisa menggantikan:',
            ],
        ]);

        $this->units['10.4']->contentBlocks()->create([
            'type' => 'list',
            'order' => $order++,
            'content' => [
                'style' => 'unordered',
                'items' => [
                    'Penilaian (judgment) tentang kebutuhan pengguna sesungguhnya (Modul 1 dan Modul 4).',
                    'Keputusan strategis memilih tech stack yang tepat untuk konteks tertentu (Modul 2).',
                    'Kemampuan membaca arah industri berdasarkan konteks yang terus berubah (Modul 3).',
                    'Tanggung jawab kolaborasi manusia lewat Git dan komunikasi tim (Modul 5).',
                    'Kepekaan mengevaluasi keamanan dan kualitas kode secara kritis (Modul 8), karena AI bisa saja menghasilkan kode yang terlihat berfungsi tapi menyimpan kerapuhan atau celah keamanan yang hanya bisa dikenali lewat pemahaman manusia.',
                ],
            ],
        ]);

        $this->units['10.4']->contentBlocks()->create([
            'type' => 'callout',
            'order' => $order++,
            'content' => [
                'variant' => 'tip',
                'title' => 'Kenapa Ini Penting',
                'body' => 'Kontribusi manusia yang tidak tergantikan itu pada akhirnya adalah kemampuan menilai, bukan sekadar menghasilkan. Developer yang paham konsep bisa mengarahkan AI dengan tepat, mengevaluasi hasilnya secara kritis, dan bertanggung jawab penuh atas keputusan akhir, sesuatu yang tidak bisa dilakukan seseorang yang hanya bisa mengetik prompt tanpa pemahaman di baliknya.',
            ],
        ]);

        UnitEvaluation::create([
            'unit_id' => $this->units['10.4']->id,
            'question_type' => 'essay',
            'question_text' => "Refleksi akhir (minimal 200 kata) — jawab pertanyaan panduan berikut, ini menjadi penutup resmi kurikulum Eksplorasi:\n\n".
                "1. Dari seluruh 10 modul yang sudah kamu lalui, sebutkan satu konsep yang paling mengubah cara pandangmu tentang web development, dan jelaskan kenapa.\n".
                "2. Ceritakan satu momen selama mengerjakan proyek akhir (Modul 10) di mana pemahaman konsep, bukan sekadar mengikuti instruksi AI, benar-benar membantumu mengambil keputusan atau menyelesaikan masalah.\n".
                "3. Setelah menyelesaikan kurikulum ini, kontribusi seperti apa yang kamu rasa kini bisa kamu berikan sebagai developer, yang tidak bisa digantikan sepenuhnya oleh AI?\n\n".
                'Lampirkan juga tautan portofolio final dari Unit 10.3 sebagai penanda kelulusan dari kurikulum Eksplorasi.',
            'options' => null,
            'correct_answer' => null,
            'sort_order' => 1,
        ]);
    }
}
