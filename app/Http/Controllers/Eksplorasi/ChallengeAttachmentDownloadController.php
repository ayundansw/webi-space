<?php

namespace App\Http\Controllers\Eksplorasi;

use App\Http\Controllers\Controller;
use App\Models\ChallengeAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Praktik 3 Bagian B. Deliberately NOT the same strict RBAC as
 * ChallengeSubmissionDownloadController (Bagian A) — this is reference
 * material meant for every member once its challenge is published, not a
 * private submission. Only real check: a draft challenge's attachments stay
 * as invisible as the challenge itself (admin-only), matching
 * Praktik\Show::mount()'s own draft gate.
 */
class ChallengeAttachmentDownloadController extends Controller
{
    public function __invoke(Request $request, ChallengeAttachment $attachment): StreamedResponse
    {
        $user = Auth::user();
        $challenge = $attachment->challenge;

        abort_if($challenge->status !== 'published' && $user->role !== 'admin', 403);

        abort_unless(Storage::disk('attachments')->exists($attachment->file_path), 404);

        return Storage::disk('attachments')->download($attachment->file_path, $attachment->file_name);
    }
}
