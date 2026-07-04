<?php

namespace App\Http\Controllers\Eksekusi;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Task 2.9: attachment files were previously served as static assets directly
 * by the web server (disk `storage_files`, public_path()-rooted — see task
 * 2.8) with zero access control: anyone with the URL could view/download a
 * task attachment regardless of login or project membership. This route
 * replaces that with an authenticated, membership-checked stream, mirroring
 * the exact access rule already used by `App\Livewire\Eksekusi\Tasks\Show::mount()`
 * (admin, or a member of the same project) — not a new access pattern.
 */
class AttachmentDownloadController extends Controller
{
    public function __invoke(Request $request, Attachment $attachment): StreamedResponse
    {
        $user = Auth::user();
        $project = $attachment->task->project;

        abort_if(
            $user->role !== 'admin' && ! $project->members()->where('user_id', $user->id)->exists(),
            403,
        );

        // 'link' and 'text' attachments have no underlying file to stream —
        // file_url holds an external URL / the note body, not disk content.
        abort_if(in_array($attachment->file_type, ['link', 'text'], true), 404);

        [$disk, $path] = $this->resolveDiskAndPath($attachment);

        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->download($path, $attachment->file_name);
    }

    /**
     * New uploads (post task 2.9) store a relative path on the private
     * `attachments` disk in `file_url` (its own disk — never `local`, which
     * Livewire's temporary file upload mechanism also defaults to; see
     * config/filesystems.php's 'attachments' disk comment for the production
     * incident that taught this). Attachments uploaded during the brief
     * window between task 2.8 (storage_files, public) and this task stored a
     * full URL under `storage_files` instead — none exist as of 2.9 (checked
     * live), but this fallback keeps them working if any ever surface,
     * without needing a data migration for what is currently an empty case.
     */
    private function resolveDiskAndPath(Attachment $attachment): array
    {
        if (str_contains($attachment->file_url, '/storage_files/')) {
            return ['storage_files', Str::after($attachment->file_url, '/storage_files/')];
        }

        return ['attachments', $attachment->file_url];
    }
}
