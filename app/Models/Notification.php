<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['recipient_id', 'context_type', 'context_id', 'type', 'title', 'message', 'is_read'])]
class Notification extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Resolved via the morph map registered in AppServiceProvider. Returns null
     * when context_type is "none" (no matching morph map entry, context_id is null).
     */
    public function context(): MorphTo
    {
        return $this->morphTo('context', 'context_type', 'context_id');
    }

    /**
     * Task 2.6b: where clicking this notification should navigate. Returns
     * null both for context_type "none" AND when the referenced row has since
     * been hard-deleted (context_id no longer resolves to anything, e.g. an
     * admin-deleted Task) — callers must treat null as "render without a link",
     * never as an error.
     */
    public function linkUrl(): ?string
    {
        // "none" isn't registered in the morph map (AppServiceProvider) — it's a
        // real, valid context_type meaning "no context at all", not a class name
        // to resolve, so touching the `context` relation for it would throw.
        if ($this->context_type === 'none' || $this->context_id === null) {
            return null;
        }

        $context = $this->context;

        if (! $context) {
            return null;
        }

        return match ($this->context_type) {
            'project' => url('/eksekusi/projects/'.$context->id),
            'task' => url('/eksekusi/tasks/'.$context->id),
            'unit' => url('/eksplorasi/unit/'.$context->id),
            'checkpoint' => url('/eksplorasi/checkpoint/'.$context->id),
            // Fase 7 Batch 4: a forum_thread with project_id set is a Forum
            // Proyek thread (Eksekusi) — routes into that project's Forum
            // tab with ?thread= to auto-open it (same query-param-driven
            // auto-open pattern as Board's ?task=, Fase 7 Batch 1b).
            //
            // Forum General Eksekusi ("utang Fase 7"): project_id null is
            // no longer automatically an Eksplorasi thread — Forum General
            // threads ALSO have project_id null, distinguished only by
            // `portal` now. project_id null + portal='execution' routes to
            // the new standalone Forum General show page instead.
            'forum_thread' => match (true) {
                $context->project_id !== null => url('/eksekusi/projects/'.$context->project_id.'/forum?thread='.$context->id),
                $context->portal === 'execution' => url('/eksekusi/forum/'.$context->id),
                default => url('/eksplorasi/forum/'.$context->id),
            },
            'module' => url('/eksplorasi/kurikulum'),
            // Recipient-dependent: submission_received_alert (admin) and
            // submission_assigned_to_reviewer (execution_member reviewer) go
            // to the review page (gated by mode:execution,admin — both
            // recipients always pass it). submission_approved/needs_revision
            // go to the submitter (exploration_member), who would 403 on
            // that same route, so they land on their own challenge page
            // instead, where their submission history is visible.
            'challenge_submission' => match ($this->type) {
                'submission_received_alert', 'submission_assigned_to_reviewer' => url('/eksekusi/praktik/submissions/'.$context->id),
                default => url('/eksplorasi/praktik/'.$context->challenge_id),
            },
            default => null,
        };
    }
}
