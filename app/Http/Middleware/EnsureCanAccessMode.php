<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level access gate, parametrized like `role:` (EnsureUserHasRole):
 * `mode:execution` or `mode:execution,admin`. `execution` defers to
 * User::canAccessExecution(); `exploration` to canAccessExploration()
 * (free, no approval, per §2.2.A); `admin` is a plain literal role check.
 *
 * SECURITY: this only gates whether a route can be OPENED — it says
 * NOTHING about whether a specific WRITE action on that route is allowed.
 * Read-only enforcement is User::isReadOnlyExploration(), checked inside
 * each write method itself (route middleware can't discriminate read from
 * write when both live on the same route).
 */
class EnsureCanAccessMode
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$modes): Response
    {
        $user = $request->user();

        abort_if(! $user, 403);

        foreach ($modes as $mode) {
            $allowed = match ($mode) {
                'execution' => $user->canAccessExecution(),
                'exploration' => $user->canAccessExploration(),
                'admin' => $user->role === 'admin',
                default => false,
            };

            if ($allowed) {
                return $next($request);
            }
        }

        abort(403);
    }
}
