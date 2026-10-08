<?php

namespace App\Livewire\Admin\Curriculum\Challenges;

use App\Models\Challenge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Kelola Challenge')]
class Edit extends Component
{
    use WithFileUploads;

    public Challenge $challenge;

    public string $title = '';

    public string $description = '';

    public string $level = '';

    public string $points_reward = '';

    public string $status = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $newAttachments = [];

    public function mount(Challenge $challenge): void
    {
        $this->challenge = $challenge;
        $this->title = $challenge->title;
        $this->description = $challenge->description;
        $this->level = $challenge->level;
        $this->points_reward = (string) $challenge->points_reward;
        $this->status = $challenge->status;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'level' => ['required', 'in:low,mid,high'],
            'points_reward' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $this->challenge->update($validated);

        session()->flash('status', 'Perubahan challenge disimpan.');
    }

    /**
     * Praktik 3 Bagian B: admin-side reference material for the challenge
     * (briefs, starter assets) — separate domain from member Submission
     * files (Bagian A). Same disk ('attachments'), own subfolder, own table.
     */
    public function uploadAttachments(): void
    {
        $this->validate([
            'newAttachments' => ['required', 'array', 'min:1'],
            'newAttachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,mp4,mov,webm,zip'],
        ]);

        foreach ($this->newAttachments as $file) {
            $path = $file->store('challenge-attachments', 'attachments');

            $this->challenge->attachments()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
            ]);
        }

        $this->reset('newAttachments');

        session()->flash('status', 'Lampiran berhasil diunggah.');
    }

    public function deleteAttachment(string $attachmentId): void
    {
        $attachment = $this->challenge->attachments()->where('id', $attachmentId)->first();

        if ($attachment) {
            Storage::disk('attachments')->delete($attachment->file_path);
            $attachment->delete();
        }

        session()->flash('status', 'Lampiran dihapus.');
    }

    public function render()
    {
        return view('livewire.admin.curriculum.challenges.edit', [
            'stepCount' => $this->challenge->challengeSteps()->count(),
            'attachments' => $this->challenge->attachments,
        ]);
    }
}
