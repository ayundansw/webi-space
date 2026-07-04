<?php

namespace Database\Seeders;

use App\Models\Checkpoint;
use App\Models\LearningResource;
use App\Models\Module;
use App\Models\Unit;
use App\Models\UnitEvaluation;
use Illuminate\Database\Seeder;

/**
 * Sample Eksplorasi curriculum data for task 2.2 (functionality testing only).
 * Not the real 10-module/67-unit curriculum — that's task 2.3.
 */
class ExplorationSampleSeeder extends Seeder
{
    public function run(): void
    {
        $moduleA = Module::create([
            'order_number' => 1,
            'title' => 'Dunia Software Development',
            'description' => 'Modul contoh: gambaran dasar software development, dipakai untuk menguji fungsionalitas LMS Eksplorasi.',
            'level_number' => 1,
        ]);

        $a1 = Unit::create([
            'module_id' => $moduleA->id,
            'order_number' => 1,
            'title' => 'Apa Itu Software Development?',
            'content' => "Software adalah kumpulan instruksi yang memberitahu komputer harus melakukan apa. Software development adalah keseluruhan proses membuatnya: dari ide, merancang, menulis kode, menguji, sampai merawatnya.\n\nCoding adalah menulis kode. Programming sedikit lebih luas, mencakup logika dan pemecahan masalah. Software development adalah yang paling luas, mencakup semuanya plus memahami kebutuhan pengguna, merancang, menguji, dan bekerja dalam tim.",
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
        ]);

        UnitEvaluation::create([
            'unit_id' => $a1->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Manakah yang cakupannya paling luas?',
            'options' => ['Coding', 'Programming', 'Software Development'],
            'correct_answer' => 'Software Development',
            'sort_order' => 1,
        ]);

        UnitEvaluation::create([
            'unit_id' => $a1->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Menulis kode adalah keseluruhan pekerjaan seorang developer. (Benar/Salah)',
            'options' => ['Benar', 'Salah'],
            'correct_answer' => 'Salah',
            'sort_order' => 2,
        ]);

        $a2 = Unit::create([
            'module_id' => $moduleA->id,
            'order_number' => 2,
            'title' => 'Peran-Peran dalam Tim Development',
            'content' => "Membuat software jarang dikerjakan satu orang. Ada Frontend Developer (bagian yang dilihat pengguna), Backend Developer (bagian belakang layar), UI/UX Designer (tampilan dan pengalaman pengguna), Project Manager (mengatur jalannya proyek), dan Analis (memahami kebutuhan pengguna).\n\nDi tim kecil, satu orang bisa memegang beberapa peran sekaligus. Yang penting kamu paham dulu fungsi masing-masing.",
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'essay',
            'prerequisite_unit_id' => $a1->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $a2->id,
            'question_type' => 'essay',
            'question_text' => 'Dari semua peran dalam tim development yang sudah kamu baca (Frontend, Backend, UI/UX, PM, Analis), peran mana yang paling menarik menurutmu dan kenapa?',
            'sort_order' => 1,
        ]);

        $a3 = Unit::create([
            'module_id' => $moduleA->id,
            'order_number' => 3,
            'title' => 'Menyiapkan Code Editor (VS Code)',
            'content' => "VS Code adalah code editor gratis, ringan, dan sangat populer di kalangan developer. Yuk coba pasang di perangkatmu: buka code.visualstudio.com, unduh sesuai sistem operasimu, lalu jalankan file instalasinya.",
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $a2->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $a3->id,
            'question_type' => 'practice',
            'question_text' => 'Coba buka atau pasang VS Code di perangkatmu. Ceritakan bagaimana prosesnya, lancar atau ada kendala?',
            'sort_order' => 1,
        ]);

        $a4 = Unit::create([
            'module_id' => $moduleA->id,
            'order_number' => 4,
            'title' => 'Jenis-Jenis Produk Software',
            'content' => "Software punya banyak bentuk: Website (diakses lewat browser), Mobile App (diinstal di ponsel), Desktop App (diinstal di komputer), dan API/Service (melayani software lain di belakang layar). Kurikulum ini fokus ke website.",
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $a3->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $a4->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'WhatsApp di ponselmu termasuk jenis produk software apa?',
            'options' => ['Website', 'Mobile App', 'Desktop App', 'API'],
            'correct_answer' => 'Mobile App',
            'sort_order' => 1,
        ]);

        Checkpoint::create([
            'module_id' => $moduleA->id,
            'checklist_items' => [
                'Aku memahami perbedaan coding, programming, dan software development',
                'Aku bisa menyebutkan minimal satu peran dalam tim development',
                'Aku sudah mencoba memasang code editor di perangkatku',
                'Aku mengetahui beberapa jenis produk software',
            ],
            'intermezo_questions' => [
                'Dari modul ini, bagian mana yang paling baru buatmu?',
            ],
        ]);

        LearningResource::create([
            'module_id' => $moduleA->id,
            'title' => 'roadmap.sh',
            'url' => 'https://roadmap.sh',
            'source_name' => 'roadmap.sh',
        ]);

        $moduleB = Module::create([
            'order_number' => 2,
            'title' => 'Bagaimana Website Bekerja',
            'description' => 'Modul contoh: mekanisme dasar client-server, dipakai untuk menguji fungsionalitas LMS Eksplorasi.',
            'level_number' => 1,
        ]);

        $b1 = Unit::create([
            'module_id' => $moduleB->id,
            'order_number' => 1,
            'title' => 'Apa yang Terjadi Saat Buka Website?',
            'content' => "Saat kamu mengetik URL dan menekan Enter: browser mengirim permintaan (request) ke server, server memproses dan mengirim balasan (response), lalu browser menampilkan halamannya. Pola bolak-balik ini disebut request-response.",
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
        ]);

        UnitEvaluation::create([
            'unit_id' => $b1->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Urutan yang benar dari pola dasar cara website bekerja adalah...',
            'options' => ['Request lalu Response', 'Response lalu Request'],
            'correct_answer' => 'Request lalu Response',
            'sort_order' => 1,
        ]);

        $b2 = Unit::create([
            'module_id' => $moduleB->id,
            'order_number' => 2,
            'title' => 'Client dan Server',
            'content' => "Client adalah pihak yang meminta (biasanya browser di perangkatmu). Server adalah pihak yang melayani, komputer yang menyimpan website dan siap mengirimkannya kapan pun ada yang meminta.",
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'essay',
            'prerequisite_unit_id' => $b1->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $b2->id,
            'question_type' => 'essay',
            'question_text' => 'Coba jelaskan dengan bahasamu sendiri, apa bedanya client dan server?',
            'sort_order' => 1,
        ]);

        $b3 = Unit::create([
            'module_id' => $moduleB->id,
            'order_number' => 3,
            'title' => 'Domain dan Hosting',
            'content' => "Domain adalah alamat website yang mudah diingat (misalnya google.com). Hosting adalah layanan penyimpanan file website di server yang menyala terus-menerus. URL adalah alamat lengkap menuju halaman tertentu.",
            'estimated_minutes' => 15,
            'unit_type' => 'concept',
            'point_value' => 10,
            'evaluation_type' => 'quiz_multiple_choice',
            'prerequisite_unit_id' => $b2->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $b3->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Domain adalah...',
            'options' => ['Alamat angka server', 'Nama alamat yang mudah diingat', 'Tempat penyimpanan file website'],
            'correct_answer' => 'Nama alamat yang mudah diingat',
            'sort_order' => 1,
        ]);

        $b4 = Unit::create([
            'module_id' => $moduleB->id,
            'order_number' => 4,
            'title' => 'Praktik: Coba Browser DevTools',
            'content' => "Kamu bisa mengintip struktur sebuah website lewat DevTools bawaan browser. Klik kanan di halaman manapun, pilih Inspect, lalu lihat tab Elements.",
            'estimated_minutes' => 15,
            'unit_type' => 'practice',
            'point_value' => 15,
            'evaluation_type' => 'practice',
            'prerequisite_unit_id' => $b3->id,
        ]);

        UnitEvaluation::create([
            'unit_id' => $b4->id,
            'question_type' => 'practice',
            'question_text' => 'Buka salah satu website favoritmu, buka DevTools (klik kanan lalu Inspect), lalu ceritakan satu hal yang kamu temukan dari tab Elements.',
            'sort_order' => 1,
        ]);

        Checkpoint::create([
            'module_id' => $moduleB->id,
            'checklist_items' => [
                'Aku bisa menjelaskan alur sederhana apa yang terjadi saat membuka website',
                'Aku memahami perbedaan client dan server',
                'Aku memahami apa itu domain dan hosting',
            ],
            'intermezo_questions' => [
                'Dari modul ini, konsep mana yang menurutmu paling penting untuk diingat?',
            ],
        ]);

        LearningResource::create([
            'module_id' => $moduleB->id,
            'title' => 'MDN Web Docs — How the web works',
            'url' => 'https://developer.mozilla.org/en-US/docs/Learn_web_development/Getting_started/Web_standards/How_the_web_works',
            'source_name' => 'MDN Web Docs',
        ]);
    }
}
