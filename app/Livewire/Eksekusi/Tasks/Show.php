<?php

namespace App\Livewire\Eksekusi\Tasks;

use App\Models\Task;
use App\Models\User;
use App\Services\Execution\TaskService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Detail Task')]
class Show extends Component
{
    use WithFileUploads;

    public Task $task;

    public string $newDeadline = '';

    public string $newPriority = '';

    public string $reassignFromId = '';

    public string $reassignToId = '';

    public string $commentContent = '';

    /** 'file', 'link', or 'text' */
    public string $attachmentMode = 'file';

    public mixed $attachmentFile = null;

    public string $attachmentLinkUrl = '';

    public string $attachmentLinkLabel = '';

    public string $attachmentTextLabel = '';

    public string $attachmentTextContent = '';

    public string $progressContent = '';

    public string $progressAttachmentUrl = '';

    public function mount(Task $task): void
    {
        $user = Auth::user();
        $project = $task->project;

        if ($user->role !== 'admin' && ! $project->members()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        $this->task = $task;
        $this->newDeadline = $task->deadline->toDateString();
        $this->newPriority = $task->priority;
    }

    public function changeStatus(string $newStatus, TaskService $service): void
    {
        try {
            $service->changeStatus($this->task, $newStatus, Auth::user());
        } catch (ValidationException $e) {
            $this->addError('status', collect($e->errors())->flatten()->first());

            return;
        }

        $this->task->refresh();
    }

    public function changeDeadline(TaskService $service): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $validated = $this->validate(['newDeadline' => ['required', 'date']]);
        $service->changeDeadline($this->task, $validated['newDeadline'], Auth::user());
        $this->task->refresh();
    }

    public function changePriority(TaskService $service): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $validated = $this->validate(['newPriority' => ['required', 'in:low,medium,high']]);
        $service->changePriority($this->task, $validated['newPriority'], Auth::user());
        $this->task->refresh();
    }

    public function reassign(TaskService $service): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $validated = $this->validate([
            'reassignFromId' => ['required', 'uuid'],
            'reassignToId' => ['required', 'uuid', 'different:reassignFromId'],
        ]);

        $from = User::findOrFail($validated['reassignFromId']);
        $to = User::findOrFail($validated['reassignToId']);

        $service->reassign($this->task, $from, $to, Auth::user());
        $this->reset(['reassignFromId', 'reassignToId']);
        $this->task->refresh();
    }

    public function delete(TaskService $service): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $project = $this->task->project;
        $service->delete($this->task, Auth::user());

        $this->redirect('/eksekusi/projects/'.$project->id.'/board', navigate: false);
    }

    public function addComment(TaskService $service): void
    {
        $validated = $this->validate(['commentContent' => ['required', 'string']]);

        $service->addComment($this->task, Auth::user(), $validated['commentContent']);
        $this->reset('commentContent');
        $this->task->refresh();
    }

    public function addAttachment(TaskService $service): void
    {
        abort_if($this->task->status === 'done', 403, 'Attachment tidak bisa ditambah lagi, task sudah done.');

        if ($this->attachmentMode === 'link') {
            $validated = $this->validate([
                'attachmentLinkUrl' => ['required', 'url'],
                'attachmentLinkLabel' => ['required', 'string', 'max:255'],
            ]);

            $service->addAttachmentLink($this->task, Auth::user(), $validated['attachmentLinkUrl'], $validated['attachmentLinkLabel']);
            $this->reset(['attachmentLinkUrl', 'attachmentLinkLabel']);
        } elseif ($this->attachmentMode === 'text') {
            $validated = $this->validate([
                'attachmentTextLabel' => ['required', 'string', 'max:255'],
                'attachmentTextContent' => ['required', 'string'],
            ]);

            $service->addAttachmentText($this->task, Auth::user(), $validated['attachmentTextContent'], $validated['attachmentTextLabel']);
            $this->reset(['attachmentTextLabel', 'attachmentTextContent']);
        } else {
            $this->validate(['attachmentFile' => ['required', 'file', 'max:10240']]);

            $service->addAttachmentFile($this->task, Auth::user(), $this->attachmentFile);
            $this->reset('attachmentFile');
        }

        $this->task->refresh();
    }

    public function addProgressUpdate(TaskService $service): void
    {
        $validated = $this->validate([
            'progressContent' => ['required', 'string'],
            'progressAttachmentUrl' => ['nullable', 'url'],
        ]);

        $service->addProgressUpdate(
            $this->task,
            Auth::user(),
            $validated['progressContent'],
            $validated['progressAttachmentUrl'] ?: null,
        );

        $this->reset(['progressContent', 'progressAttachmentUrl']);
        $this->task->refresh();
    }

    public function render()
    {
        return view('livewire.eksekusi.tasks.show', [
            'assignments' => $this->task->assignments()->with('user')->get(),
            'projectMembers' => $this->task->project->members()->with('user')->get(),
            'comments' => $this->task->comments()->with('user')->orderBy('created_at')->get(),
            'attachments' => $this->task->attachments()->with('uploader')->orderByDesc('created_at')->get(),
            'progressUpdates' => $this->task->progressUpdates()->with('user')->orderByDesc('created_at')->get(),
        ]);
    }
}
