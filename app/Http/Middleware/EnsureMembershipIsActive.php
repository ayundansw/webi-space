<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Task 2.6 Batch 3 finding: App\Livewire\Auth\Login already blocks a
 * deactivated member from logging in, but nothing previously re-checked
 * membership_status on an EXISTING session — an admin deactivating a
 * currently logged-in member (PRD 5.13 scenario) had no effect until that
 * session expired naturally. Runs globally on every request (registered on
 * the `web` group in bootstrap/app.php), not just role-gated routes, so
 * deactivation takes effect on the member's very next request.
 */
class EnsureMembershipIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->membership_status !== 'active') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors(['email' => 'Akun kamu sudah tidak aktif. Hubungi admin kalau ini keliru.']);
        }

        return $next($request);
    }
}
