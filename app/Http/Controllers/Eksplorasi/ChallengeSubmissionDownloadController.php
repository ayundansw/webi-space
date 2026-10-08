<?php

namespace App\Http\Controllers\Eksplorasi;

use App\Http\Controllers\Controller;
use App\Models\ChallengeSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Praktik 3 Bagian A. Same pattern as
 * App\Http\Controllers\Eksekusi\AttachmentDownloadController (2.9) — auth +
 * scoped access check before streaming, 403 (not 404) on failure, never a
 * raw static file request. Access rule is DELIBERATELY wider than that
 * controller's "project members only": admin, the assigned reviewer, OR the
 * submission's own owner (so a member can re-download their own file later).
 */
class ChallengeSubmissionDownloadController extends Controller
{
    public function __invoke(Request $request, ChallengeSubmission $submission): StreamedResponse
    {
        $user = Auth::user();

        abort_if(
            $user->role !== 'admin'
                && $submission->assigned_reviewer_id !== $user->id
                && $submission->user_id !== $user->id,
            403,
        );

        abort_if($submission->submission_type !== 'file', 404);

        abort_unless(Storage::disk('attachments')->exists($submission->file_path), 404);

        return Storage::disk('attachments')->download($submission->file_path, $submission->file_name);
    }
}
