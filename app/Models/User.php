<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password_hash', 'role', 'avatar_url', 'interest_field', 'membership_status', 'dual_mode_status', 'active_mode'])]
#[Hidden(['password_hash'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interest_field' => 'array',
        ];
    }

    /**
     * Auth expects a "password" column by default; the schema names it password_hash.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Same reason as getAuthPassword(): rehash-on-login writes the new hash
     * to this column name, which defaults to "password" (does not exist here).
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function dashboardPath(): string
    {
        return match ($this->role) {
            'admin' => '/admin/dashboard',
            'exploration_member' => '/eksplorasi/dashboard',
            'execution_member' => '/eksekusi/dashboard',
        };
    }

    /**
     * True for BOTH directions of Mode Ganda: an execution_member always has
     * capability (Eksplorasi read access is unconditional, §2.2.A); an
     * exploration_member only once admin-approved, regardless of
     * `active_mode` (capability means "can switch", not "is currently
     * switched") — unlike canAccessExecution(), which DOES require
     * `active_mode === 'execution'`.
     */
    public function hasDualModeCapability(): bool
    {
        return $this->role === 'execution_member'
            || ($this->role === 'exploration_member' && $this->dual_mode_status === 'approved');
    }

    /**
     * True when this dual-mode-capable user is currently using the "other"
     * portal relative to their origin role.
     *
     * - exploration_member: reads the persisted `active_mode` flag (a real
     *   switch with real consequences — canAccessExecution() depends on it).
     * - execution_member: derived from the CURRENT request's route instead —
     *   their Eksplorasi access needs no DB state at all (§2.2.A), so a
     *   persisted "current mode" column here would just be a second source
     *   of truth that could drift across browser tabs.
     */
    public function isInSwitchedMode(): bool
    {
        if ($this->role === 'exploration_member') {
            return $this->active_mode === 'execution';
        }

        if ($this->role === 'execution_member') {
            return request()->routeIs('eksplorasi.*');
        }

        return false;
    }

    /**
     * Fase 8 Batch 6: label for <x-shell.mode-badge> (Fase 2 Langkah 5
     * slot, structurally present since then but always empty until now).
     */
    public function currentModeBadgeLabel(): ?string
    {
        if (! $this->hasDualModeCapability()) {
            return null;
        }

        if ($this->role === 'exploration_member') {
            return $this->isInSwitchedMode() ? 'Mode: Eksekusi' : 'Mode: Eksplorasi';
        }

        return $this->isInSwitchedMode() ? 'Mode: Eksplorasi (Baca)' : 'Mode: Eksekusi';
    }

    /**
     * Fase 8 Batch 1: plain getters over the new dual_mode_status/active_mode
     * columns.
     */
    public function dualModeRequests(): HasMany
    {
        return $this->hasMany(DualModeRequest::class);
    }

    public function hasApprovedDualModeAccess(): bool
    {
        return $this->dual_mode_status === 'approved';
    }

    public function hasPendingDualModeRequest(): bool
    {
        return $this->dual_mode_status === 'pending';
    }

    /**
     * THE single canonical check for "may this user use Eksekusi
     * features" — any future check (component-level guard, query scoping)
     * must call THIS method rather than re-deriving the same logic.
     *
     * Deliberately does NOT include `role === 'admin'` — admin is not part
     * of the dual-mode system at all; routes that also allow admin combine
     * this with a separate `role === 'admin'` check at the call site.
     */
    public function canAccessExecution(): bool
    {
        return $this->role === 'execution_member'
            || ($this->role === 'exploration_member'
                && $this->dual_mode_status === 'approved'
                && $this->active_mode === 'execution');
    }

    /**
     * Fase 8 Batch 3 (RANCANGAN_FINAL_WEBI-SPACE_v2.md §2.2.A — "Origin
     * Eksekusi -> Mode Eksplorasi (BEBAS, tanpa persetujuan)"). Unlike
     * canAccessExecution(), this has NO approval/active-mode precondition
     * at all — an execution_member can read Eksplorasi content any time,
     * unconditionally. Only decides "can this user OPEN the page at all";
     * whether they can WRITE anything on it is a separate question, see
     * isReadOnlyExploration() below.
     */
    public function canAccessExploration(): bool
    {
        return $this->role === 'exploration_member' || $this->role === 'execution_member';
    }

    /**
     * True exactly when this user's access to Eksplorasi is the read-only
     * kind (origin execution_member) rather than the real thing (origin
     * exploration_member, full read/write). Every write action reachable
     * from a page canAccessExploration() opens up MUST check this and
     * refuse — see RECON_fase8_mode_ganda.md poin 1d/3 for the full list
     * (UnitEvaluation's 3 progress-writing methods, CheckpointShow::submit(),
     * Resources\Index::submit(), Forum\Create::save(), Forum\Show::reply()).
     */
    public function isReadOnlyExploration(): bool
    {
        return $this->role === 'execution_member';
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'recipient_id');
    }

    public function proposedProjectIdeas(): HasMany
    {
        return $this->hasMany(ProjectIdea::class, 'proposed_by');
    }

    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(ProgressUpdate::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploaded_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function unitProgress(): HasMany
    {
        return $this->hasMany(UserUnitProgress::class);
    }

    public function evaluationSubmissions(): HasMany
    {
        return $this->hasMany(EvaluationSubmission::class);
    }

    public function checkpointCompletions(): HasMany
    {
        return $this->hasMany(CheckpointCompletion::class);
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class, 'created_by');
    }

    public function forumReplies(): HasMany
    {
        return $this->hasMany(ForumReply::class);
    }

    public function explorationProgress(): HasOne
    {
        return $this->hasOne(UserExplorationProgress::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function proactiveLogs(): HasMany
    {
        return $this->hasMany(ProactiveLog::class);
    }
}
