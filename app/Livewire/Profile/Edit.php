<?php

namespace App\Livewire\Profile;

use App\Models\Project;
use App\Models\User;
use App\Services\DualMode\DualModeService;
use App\Services\Exploration\FoxAvatarService;
use App\Services\Exploration\ProgressService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Self-service profile, all three roles. Deliberately does NOT expose
 * `role` or `membership_status` anywhere on this component — those stay
 * admin-only (App\Livewire\Admin\Users\Edit). Only `name`/`interest_field`
 * are writable; `email` is read-only (risk of login lockout/typos).
 *
 * The read-only summary sections below (level/poin, avatar galleries,
 * heatmap, project contributions) need no migration. The Eksekusi avatar
 * picker itself is NOT reimplemented — `<livewire:eksekusi.avatar-picker />`
 * embeds the real component as-is, this only decides WHETHER to show it.
 */
#[Layout('components.layouts.app')]
#[Title('Profil Saya')]
class Edit extends Component
{
    /**
     * 6 months (182 days = 26 weeks) of heatmap history — long enough to
     * resemble a GitHub-style grid, short enough that new members aren't
     * mostly blank tiles.
     */
    private const HEATMAP_DAYS = 182;

    public string $name = '';

    /** @var array<int, string> */
    public array $interestField = [];

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->interestField = $user->interest_field ?? [];
    }

    public function saveProfile(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'interestField' => ['array'],
            'interestField.*' => ['in:frontend,backend,ui_ux,analyst,pm,fullstack'],
        ]);

        // Only ever touches name/interest_field — role and membership_status
        // are never read from this component's state, so there is nothing
        // for a tampered request to smuggle in through this update() call.
        Auth::user()->update([
            'name' => $validated['name'],
            'interest_field' => $validated['interestField'] ?: null,
        ]);

        session()->flash('status', 'Profil berhasil diperbarui.');
    }

    public function changePassword(): void
    {
        $validated = $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (! Hash::check($validated['current_password'], $user->password_hash)) {
            $this->addError('current_password', 'Password lama yang kamu masukkan salah.');

            return;
        }

        $user->update(['password_hash' => bcrypt($validated['password'])]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('password_status', 'Password berhasil diganti.');

        // UI-only hook (not business logic) so the Ganti Password modal
        // (Alpine `passwordModalOpen` state, blade-side) can close itself on
        // success — it's never dispatched on the validation/wrong-password
        // paths above, so the modal correctly stays open to show those errors.
        $this->dispatch('password-changed');
    }

    /**
     * Fase 8 Batch 4: member-facing trigger for the request flow
     * (docs/v_2.0/archive/sumber-konsolidasi/RANCANGAN_FINAL_WEBI-SPACE_v2.md §2.2.B) — all status
     * logic (double-request guard, admin broadcast notification) lives in
     * DualModeService::submitRequest(), not here. Approve/reject/revoke
     * (admin side) is Batch 5.
     */
    public function requestDualModeAccess(DualModeService $service): void
    {
        try {
            $service->submitRequest(Auth::user());
        } catch (ValidationException $e) {
            $this->addError('dual_mode', collect($e->errors())->flatten()->first());

            return;
        }

        session()->flash('status', 'Permintaan akses Eksekusi terkirim. Tunggu keputusan admin.');
    }

    public function render(FoxAvatarService $foxAvatarService, ProgressService $progressService)
    {
        $user = Auth::user();
        $explorationProgress = $user->role === 'exploration_member' ? $user->explorationProgress : null;

        return view('livewire.profile.edit', [
            'user' => $user,
            'explorationProgress' => $explorationProgress,
            'foxTier' => $explorationProgress ? $foxAvatarService->tierForPoints($explorationProgress->total_points) : null,
            'foxTiers' => config('exploration.fox_avatar_tiers'),
            'heatmapWeeks' => $explorationProgress ? $this->heatmapWeeks($user, $progressService) : [],
            // Fase 8 Batch 6 (gap #2 dari Batch 2): dulu keyed ke role
            // literal ('execution_member' saja), jadi anggota Mode Ganda
            // (exploration_member yang approved+pernah aktif mode Eksekusi,
            // sungguhan jadi ProjectMember/TaskAssignment) tidak pernah
            // melihat kontribusi proyek MEREKA SENDIRI. Sekarang dihitung
            // untuk SIAPA PUN — query-nya sendiri (projectContributions())
            // sudah otomatis kosong untuk user yang memang tidak pernah jadi
            // ProjectMember, jadi tidak perlu guard role di sini; blade yang
            // memutuskan kapan section ini ditampilkan (lihat komentar di
            // edit.blade.php).
            'projectContributions' => $this->projectContributions($user),
            // Fase 8 Batch 4: dual_mode_status alone can't distinguish
            // "never applied" from "applied, got rejected, status reset to
            // none" (see DualModeService::reject()'s docblock) — the
            // latest request row (if any) carries that extra context
            // (status/note) for the Profil slot to show. Tie-broken by
            // `id` DESC too (not just `created_at`) — perbaikan pasca-Batch
            // 5 introduced a submit -> approve -> revoke sequence that can
            // land multiple rows within the same second in tests/fast
            // requests, where `created_at` alone ties and `latest()`'s
            // result becomes unpredictable.
            'latestDualModeRequest' => $user->role === 'exploration_member'
                ? $user->dualModeRequests()->latest()->latest('id')->first()
                : null,
        ]);
    }

    /**
     * Builds a GitHub-style week grid (columns = weeks, 7 rows = Sun..Sat)
     * covering the last self::HEATMAP_DAYS days, aligned back to the
     * preceding Sunday so every column is a complete week. Cells before the
     * tracked range (from that alignment) get `count => null` — rendered as
     * blank, distinct from `count => 0` ("tracked day, no activity").
     *
     * @return array<int, array<int, array{date: Carbon, count: int|null}>>
     */
    private function heatmapWeeks(User $user, ProgressService $progressService): array
    {
        $counts = $progressService->activityHeatmap($user, self::HEATMAP_DAYS);

        $end = Carbon::today();
        $rangeStart = $end->copy()->subDays(self::HEATMAP_DAYS - 1);
        $cursor = $rangeStart->copy()->startOfWeek(Carbon::SUNDAY);

        $weeks = [];

        while ($cursor->lte($end)) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $inRange = $cursor->gte($rangeStart) && $cursor->lte($end);

                $week[] = [
                    'date' => $cursor->copy(),
                    'count' => $inRange ? ($counts[$cursor->format('Y-m-d')] ?? 0) : null,
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * Projects this user belongs to (ProjectMember) plus how many tasks
     * they're assigned to in each (TaskAssignment) — deliberately WITHOUT a
     * "role in project" column, per the standing product decision
     * (RECON_project_member_peran.md) that a per-project role field is a
     * schema change requiring separate explicit confirmation, not something
     * to slip into a read-only summary batch. Purely membership-driven
     * (`whereHas('members', ...)`), never role-driven — naturally covers
     * both a native execution_member AND a Mode Ganda exploration_member
     * who has actually joined a project through their Eksekusi access
     * (Fase 8 Batch 6, gap #2), with zero special-casing needed here.
     *
     * @return Collection<int, array{project: Project, task_count: int}>
     */
    private function projectContributions(User $user): Collection
    {
        return Project::whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Project $project) => [
                'project' => $project,
                // Fase 7 Batch 2a audit: found via grep sweep, not in the
                // original 5-file recon list — NOT filtered to
                // whereNull('parent_task_id') for the same reason as
                // Eksekusi\Dashboard's taskCounts: this counts THIS user's
                // own assigned work, and a subtask assigned to them is real
                // personal contribution.
                'task_count' => $project->tasks()->whereHas('assignments', fn ($q) => $q->where('user_id', $user->id))->count(),
            ]);
    }
}
