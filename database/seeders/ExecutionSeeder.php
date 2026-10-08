<?php

namespace Database\Seeders;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectIdea;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Services\Execution\TaskDependencyService;
use Illuminate\Database\Seeder;

/**
 * Portal Eksekusi: 4 Project Ideas (Bagian A) + 1 Project Dummy lengkap
 * (Bagian B), dari docs/v_2.0/kurikulum/Project_Ideas_dan_Dummy_Project.md
 * (draft yang sudah di-ACC Aye) — SATU-SATUNYA sumber teks yang sah.
 *
 * Kontributor Project Dummy SENGAJA HANYA 2 akun (ayundanasywaa@gmail.com,
 * eksekusiuser@example.com) — bukan seluruh 16 akun, sesuai batasan
 * eksplisit prompt.
 *
 * Keputusan mandiri (tidak ada di sumber, dicatat eksplisit):
 * - `ProjectMember.joined_at` TIDAK ada di daftar #[Fillable] model (dicek
 *   dulu ke kode -- `ProjectService::addMember()` sendiri juga TIDAK PERNAH
 *   mengisi field ini, selalu null). Prompt tetap eksplisit minta diisi
 *   sama dengan start_date proyek, jadi diset lewat assignment atribut
 *   langsung + save() (bukan mass-assignment) supaya tidak perlu mengubah
 *   Fillable model demi satu baris seed data ini.
 * - Priority kedua Subtask (source menandai "-", tidak menyebutkan nilai)
 *   diisi `medium` -- disamakan dengan default yang dipakai
 *   `TaskService::createSubtask()` sendiri untuk subtask baru (hardcoded
 *   'medium', tidak ada picker priority di form subtask), bukan ditebak
 *   sendiri.
 * - TaskDependency dibuat lewat `TaskDependencyService::addDependency()`
 *   (bukan `TaskDependency::create()` langsung), sesuai instruksi eksplisit
 *   prompt supaya validasi anti-siklus/subtask/lintas-proyek yang sudah ada
 *   tetap dilalui.
 * - TIDAK menambahkan ActivityLog -- tidak disebutkan di daftar entitas
 *   sumber (Project/ProjectMember/Milestone/Task/Subtask/TaskAssignment/
 *   TaskDependency), jadi dibiarkan kosong konsisten dengan batasan
 *   "HANYA yang tertulis di sumber".
 */
class ExecutionSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedProjectIdeas();
        $this->seedDummyProject();
    }

    private function seedProjectIdeas(): void
    {
        $admin = User::where('email', 'ayundanasywaher@gmail.com')->firstOrFail();

        $ideas = [
            [
                'title' => 'Sistem Deteksi dan Prioritas Otomatis Kerusakan Fasilitas Kampus',
                'purpose' => 'Kerusakan fasilitas kampus (AC, proyektor, lampu, WiFi per titik akses) biasanya dilaporkan lewat grup WhatsApp atau form kertas yang gampang tenggelam dan tidak ada yang bertanggung jawab menindaklanjuti. Aplikasi ini menjawab kebutuhan pelaporan yang terlacak dan transparan.',
                'description' => 'Mahasiswa/staf melaporkan kerusakan lewat foto + lokasi. Sistem menghitung skor prioritas otomatis berdasar kombinasi: frekuensi laporan serupa di titik yang sama, kategori ruangan (lab/kelas praktikum diberi bobot lebih tinggi dari ruang non-akademik), dan lama waktu sejak laporan pertama masuk tanpa ditindaklanjuti. Ada dashboard publik yang menunjukkan status tiap laporan (masuk/diproses/selesai) supaya pelapor tidak perlu bertanya-tanya lagi progresnya. Niche-nya ada di algoritma skor prioritas yang disesuaikan konteks kampus, bukan sekadar sistem tiket generik.',
            ],
            [
                'title' => 'Time Bank: Marketplace Barter Skill Antar Mahasiswa',
                'purpose' => 'Banyak mahasiswa punya skill yang bisa saling membantu (les privat, desain, edit foto/video, proofreading) tapi tidak semua bisa membayar jasa itu dengan uang. Sistem barter berbasis waktu menjawab kesenjangan ini tanpa perlu transaksi uang sama sekali.',
                'description' => 'Setiap anggota punya "saldo jam" yang didapat dari memberi jasa ke orang lain (1 jam mengajar = 1 jam kredit), lalu kredit itu dipakai untuk menukar jasa dari anggota lain, terlepas dari jenis skillnya (1 jam desain = 1 jam bantuan matematika, nilainya setara karena diukur dari waktu bukan skill yang "lebih mahal"). Niche-nya ada di model ekonomi barter waktu (time banking), sesuatu yang jarang diimplementasikan dengan baik di skala kampus Indonesia, beda dari marketplace jasa berbasis uang pada umumnya.',
            ],
            [
                'title' => 'Pencocokan Kelompok Belajar Berdasar Gaya Belajar dan Jadwal Kosong',
                'purpose' => 'Aplikasi pencari teman belajar yang sudah ada biasanya cuma mencocokkan berdasar mata kuliah yang sama, hasilnya kelompok belajar sering tidak cocok karena gaya belajar dan waktu luang anggotanya berbeda-beda.',
                'description' => 'Anggota mengisi profil gaya belajar (visual/auditori/kinestetik) dan jadwal kelas mingguan. Sistem mencari irisan waktu kosong antar anggota DAN kecocokan gaya belajar sebelum membentuk kelompok otomatis, bukan sekadar mencocokkan mata kuliah. Niche-nya ada di algoritma pencocokan dua dimensi (waktu + gaya belajar) sekaligus, lebih canggih dari sekadar filter mata kuliah.',
            ],
            [
                'title' => 'Bank Sampah Digital dengan Jaringan Tukar Poin ke UMKM Sekitar Kampus',
                'purpose' => 'Program bank sampah sering berhenti di tahap pencatatan setoran sampah tanpa insentif nyata yang dirasakan langsung, sehingga partisipasinya cepat menurun.',
                'description' => 'Setoran sampah dicatat lewat aplikasi dan dikonversi jadi poin, tapi bedanya dari bank sampah digital pada umumnya, poin ini bisa ditukar langsung ke warung, laundry, atau fotokopi sekitar kampus yang jadi mitra jaringan (bukan cuma ditukar uang tunai kembali di titik bank sampah). Niche-nya ada di integrasi ekonomi hyperlocal antara program lingkungan dan UMKM sekitar kampus, nilai sosial dan lingkungannya tinggi.',
            ],
        ];

        foreach ($ideas as $idea) {
            ProjectIdea::create([
                'title' => $idea['title'],
                'description' => $idea['description'],
                'purpose' => $idea['purpose'],
                'proposed_by' => $admin->id,
                'status' => 'approved',
                'rejection_reason' => null,
                'promoted_to_project_id' => null,
            ]);
        }
    }

    private function seedDummyProject(): void
    {
        $ayunda = User::where('email', 'ayundanasywaa@gmail.com')->firstOrFail();
        $eksekusiUser = User::where('email', 'eksekusiuser@example.com')->firstOrFail();

        $project = Project::create([
            'title' => 'Redesain Alur Onboarding Anggota Baru RIT Webdev [Demo]',
            'description' => 'Merancang ulang alur penyambutan dan orientasi anggota baru Divisi Web Development supaya tidak lagi bingung harus mulai dari mana begitu bergabung.',
            'objective' => 'Mengurangi waktu adaptasi anggota baru dan meningkatkan kejelasan langkah awal yang perlu diambil setelah bergabung ke divisi.',
            'project_type' => 'internal',
            'status' => 'active',
            'originated_from_idea_id' => null,
            'start_date' => '2026-07-01',
            'target_end_date' => '2026-08-15',
            'created_by' => $ayunda->id,
        ]);

        foreach ([$ayunda, $eksekusiUser] as $user) {
            $member = ProjectMember::create([
                'project_id' => $project->id,
                'user_id' => $user->id,
            ]);
            // joined_at bukan Fillable (lihat docblock kelas) -- di-set lewat
            // assignment atribut langsung, bukan mass-assignment.
            $member->joined_at = $project->start_date;
            $member->save();
        }

        $milestone1 = Milestone::create([
            'project_id' => $project->id,
            'title' => 'Riset dan Rancangan Alur Onboarding',
            'target_date' => '2026-07-25',
            'sort_order' => 1,
        ]);

        $milestone2 = Milestone::create([
            'project_id' => $project->id,
            'title' => 'Implementasi dan Uji Coba Alur Baru',
            'target_date' => '2026-08-15',
            'sort_order' => 2,
        ]);

        // ---- Milestone 1 ----
        $auditTask = $this->task($project, $milestone1, $ayunda, 'Audit alur onboarding member baru saat ini', 'done', 'medium', '2026-07-18', $ayunda);

        $wawancaraTask = $this->task($project, $milestone1, $ayunda, 'Wawancara singkat 3 anggota baru soal pengalaman onboarding', 'in_progress', 'high', '2026-07-22', $eksekusiUser);

        // Subtask -- parent_task_id, BUKAN task_dependencies, mewarisi
        // project_id/milestone_id dari task induk (Wawancara).
        $subtask1 = Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone1->id,
            'parent_task_id' => $wawancaraTask->id,
            'title' => 'Susun daftar pertanyaan wawancara',
            'status' => 'done',
            'priority' => 'medium',
            'deadline' => '2026-07-19',
            'created_by' => $ayunda->id,
        ]);
        TaskAssignment::create([
            'task_id' => $subtask1->id,
            'user_id' => $eksekusiUser->id,
            'assigned_by' => $ayunda->id,
        ]);

        $subtask2 = Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone1->id,
            'parent_task_id' => $wawancaraTask->id,
            'title' => 'Rangkum hasil wawancara jadi insight',
            'status' => 'todo',
            'priority' => 'medium',
            'deadline' => '2026-07-23',
            'created_by' => $ayunda->id,
        ]);
        TaskAssignment::create([
            'task_id' => $subtask2->id,
            'user_id' => $eksekusiUser->id,
            'assigned_by' => $ayunda->id,
        ]);

        $rancangTask = $this->task($project, $milestone1, $ayunda, 'Rancang alur onboarding baru (diagram alur)', 'todo', 'high', '2026-07-25', $ayunda);

        // ---- Milestone 2 ----
        $implementasiTask = $this->task($project, $milestone2, $ayunda, 'Implementasi perubahan halaman welcome/checklist onboarding', 'todo', 'medium', '2026-08-05', $ayunda);

        $ujiCobaTask = $this->task($project, $milestone2, $ayunda, 'Uji coba alur baru dengan 2 anggota baru simulasi', 'todo', 'medium', '2026-08-10', $eksekusiUser);

        $this->task($project, $milestone2, $ayunda, 'Dokumentasikan alur onboarding final ke panduan member baru', 'todo', 'low', '2026-08-15', $ayunda);

        // ---- Task dependencies (task besar saja, lewat service supaya
        // validasi anti-siklus/subtask/lintas-proyek yang sudah ada tetap
        // dilalui, bukan insert manual) ----
        $dependencyService = app(TaskDependencyService::class);
        $dependencyService->addDependency($rancangTask, $wawancaraTask);
        $dependencyService->addDependency($ujiCobaTask, $implementasiTask);
    }

    private function task(Project $project, Milestone $milestone, User $creator, string $title, string $status, string $priority, string $deadline, User $assignee): Task
    {
        $task = Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'title' => $title,
            'status' => $status,
            'priority' => $priority,
            'deadline' => $deadline,
            'created_by' => $creator->id,
        ]);

        TaskAssignment::create([
            'task_id' => $task->id,
            'user_id' => $assignee->id,
            'assigned_by' => $creator->id,
        ]);

        return $task;
    }
}
