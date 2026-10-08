<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Seeds 3 varied demo projects for reviewing Dashboard Eksekusi visuals
 * with realistic data instead of an empty state. LOCAL-ONLY (never runs in
 * production, never touches DatabaseSeeder) — Dashboard::render() has no
 * fallback branch for fake data, a real member with zero projects always
 * sees the genuine empty state. Titles suffixed "[Demo]"; idempotent via
 * that marker so re-running after `migrate:fresh` doesn't duplicate rows.
 *
 * The 3 projects are deliberately varied to exercise every alert type
 * (overdue/due-soon/stalled/idle/milestone-at-risk) and the on_hold
 * alert-suppression rule in one seed.
 */
class SeedEksekusiDemo extends Command
{
    protected $signature = 'app:seed-eksekusi-demo {email? : Email anggota eksekusi yang akan diberi data dummy}';

    protected $description = 'Isi 3 proyek dummy realistis (task + alert bervariasi) untuk review visual Dashboard Eksekusi. Hanya jalan di environment local.';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Command ini cuma boleh jalan di environment local: data dummy, bukan untuk production.');

            return self::FAILURE;
        }

        $email = $this->argument('email') ?? $this->ask('Email anggota eksekusi target (harus sudah punya akun execution_member)');

        $member = User::where('email', $email)->where('role', 'execution_member')->first();

        if (! $member) {
            $this->error("Tidak ditemukan akun execution_member dengan email '{$email}'. Buat dulu lewat halaman Kelola Anggota admin.");

            return self::FAILURE;
        }

        $admin = User::where('role', 'admin')->first();

        if (! $admin) {
            $this->error('Butuh minimal 1 akun admin (sebagai creator proyek/task): buat dulu lewat php artisan app:create-admin.');

            return self::FAILURE;
        }

        if (Project::where('title', 'Website Portofolio RIT [Demo]')->exists()) {
            $this->warn('Data dummy sepertinya sudah pernah dibuat (proyek dengan judul yang sama ditemukan). Tidak membuat duplikat.');

            return self::SUCCESS;
        }

        $this->seedActiveHealthyProject($admin, $member);
        $this->seedNeglectedProject($admin, $member);
        $this->seedOnHoldProject($admin, $member);

        $this->info("Data dummy Dashboard Eksekusi berhasil dibuat untuk {$member->email}.");

        return self::SUCCESS;
    }

    private function seedActiveHealthyProject(User $admin, User $member): void
    {
        $project = Project::create([
            'title' => 'Website Portofolio RIT [Demo]',
            'description' => 'Redesign website portofolio publik Divisi Web Development.',
            'objective' => 'Menampilkan portofolio proyek RIT ke publik dengan tampilan modern.',
            'project_type' => 'internal',
            'status' => 'active',
            'start_date' => now()->subDays(14)->toDateString(),
            'target_end_date' => now()->addDays(30)->toDateString(),
            'created_by' => $admin->id,
        ]);

        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        $milestone = Milestone::create([
            'project_id' => $project->id,
            'title' => 'Rilis Beta',
            'target_date' => now()->addDays(10)->toDateString(),
            'sort_order' => 1,
        ]);

        // Task 6 (Tahap B, Isi Proyek revisi), opsional/prioritas rendah:
        // created_at disebar (bukan seluruhnya default ke saat command
        // dijalankan) supaya bar Gantt task dummy ini tidak sejajar di
        // tampilan demo — logika posisi bar-nya sendiri sudah benar sejak
        // awal (Keputusan poin 1), ini murni kosmetik data seed.
        $done = $this->task($project, $milestone, $admin, 'Desain Wireframe Halaman Utama', 'done', 'medium', now()->subDays(5), now()->subDays(13));
        $this->task($project, $milestone, $admin, 'Implementasi Komponen Navbar', 'done', 'medium', now()->subDays(2), now()->subDays(11));
        $inReview = $this->task($project, $milestone, $admin, 'Integrasi API Testimoni', 'in_review', 'medium', now()->addDays(4), now()->subDays(8));
        $dueSoon = $this->task($project, $milestone, $admin, 'Setup CI/CD Pipeline', 'todo', 'high', now()->addDays(2), now()->subDays(5));
        $overdue = $this->task($project, $milestone, $admin, 'Perbaikan Bug Responsif Navbar', 'in_progress', 'high', now()->subDays(1), now()->subDays(3));

        foreach ([$done, $inReview, $dueSoon, $overdue] as $task) {
            TaskAssignment::create(['task_id' => $task->id, 'user_id' => $member->id, 'assigned_by' => $admin->id]);
        }

        ActivityLog::create([
            'project_id' => $project->id,
            'task_id' => $inReview->id,
            'user_id' => $member->id,
            'action_type' => 'task_status_changed',
            'description' => "{$member->name} memindahkan task 'Integrasi API Testimoni' ke In Review.",
        ]);
    }

    private function seedNeglectedProject(User $admin, User $member): void
    {
        $project = Project::create([
            'title' => 'Sistem Presensi Divisi Web [Demo]',
            'description' => 'Sistem presensi internal berbasis QR code untuk kegiatan divisi.',
            'objective' => 'Menggantikan presensi manual dengan scan QR.',
            'project_type' => 'internal',
            'status' => 'active',
            'start_date' => now()->subDays(25)->toDateString(),
            'target_end_date' => now()->addDays(15)->toDateString(),
            'created_by' => $admin->id,
        ]);

        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        $milestone = Milestone::create([
            'project_id' => $project->id,
            'title' => 'Fitur Absensi QR',
            'target_date' => now()->subDays(3)->toDateString(),
            'sort_order' => 1,
        ]);

        $this->task($project, $milestone, $admin, 'Rancang Skema Database Presensi', 'done', 'medium', now()->subDays(10));
        $stalled = $this->task($project, $milestone, $admin, 'Endpoint Generate QR', 'in_progress', 'medium', now()->addDays(20));
        $this->task($project, $milestone, $admin, 'Halaman Riwayat Presensi', 'todo', 'low', now()->addDays(15));

        TaskAssignment::create(['task_id' => $stalled->id, 'user_id' => $member->id, 'assigned_by' => $admin->id]);

        // Sengaja TIDAK ada ActivityLog sama sekali untuk proyek ini -- lihat
        // docblock kelas: null activity otomatis dibaca AlertService sebagai
        // idle (proyek) & stalled (task in_progress ini), tanpa perlu
        // backdating manual.
    }

    private function seedOnHoldProject(User $admin, User $member): void
    {
        $project = Project::create([
            'title' => 'Landing Page Lomba Web RIT [Demo]',
            'description' => 'Landing page untuk pendaftaran lomba web internal.',
            'objective' => 'Meningkatkan jumlah pendaftar lomba lewat halaman promosi.',
            'project_type' => 'competition',
            'status' => 'on_hold',
            'start_date' => now()->subDays(5)->toDateString(),
            'target_end_date' => now()->addDays(45)->toDateString(),
            'created_by' => $admin->id,
        ]);

        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        $milestone = Milestone::create([
            'project_id' => $project->id,
            'title' => 'Rilis Landing Page',
            'target_date' => now()->addDays(20)->toDateString(),
            'sort_order' => 1,
        ]);

        $this->task($project, $milestone, $admin, 'Riset Referensi Desain', 'todo', 'low', now()->addDays(18));
    }

    private function task(Project $project, Milestone $milestone, User $admin, string $title, string $status, string $priority, \Illuminate\Support\Carbon $deadline, ?\Illuminate\Support\Carbon $createdAt = null): Task
    {
        $task = Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'title' => $title,
            'status' => $status,
            'priority' => $priority,
            'deadline' => $deadline->toDateString(),
            'created_by' => $admin->id,
        ]);

        if ($createdAt !== null) {
            $task->forceFill(['created_at' => $createdAt])->save();
        }

        return $task;
    }
}
