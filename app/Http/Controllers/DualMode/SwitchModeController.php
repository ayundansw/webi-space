<?php

namespace App\Http\Controllers\DualMode;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Member-facing counterpart to DualModeService's approve/reject/revoke —
 * lets an ALREADY-approved exploration_member flip `active_mode` between
 * null (mode asal) and 'execution' themselves, from the account-menu.
 *
 * execution_member's "Mode Eksplorasi" side is NOT handled here — that's
 * pure navigation (canAccessExploration() is unconditional for that role),
 * so account-menu links them straight to the destination instead.
 *
 * SECURITY: anyone other than an approved exploration_member is refused
 * outright (403), never a silent no-op.
 */
class SwitchModeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user->role === 'exploration_member' && $user->dual_mode_status === 'approved', 403);

        $validated = $request->validate(['mode' => ['required', 'in:execution,origin']]);

        $user->update(['active_mode' => $validated['mode'] === 'execution' ? 'execution' : null]);

        return redirect($validated['mode'] === 'execution' ? route('eksekusi.dashboard') : route('eksplorasi.dashboard'));
    }
}
