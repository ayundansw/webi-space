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

#[Fillable(['name', 'email', 'password_hash', 'role', 'avatar_url', 'interest_field', 'membership_status'])]
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

    public function dashboardPath(): string
    {
        return match ($this->role) {
            'admin' => '/admin/dashboard',
            'exploration_member' => '/eksplorasi/dashboard',
            'execution_member' => '/eksekusi/dashboard',
        };
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
