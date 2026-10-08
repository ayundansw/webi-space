<?php

use App\Http\Middleware\EnsureCanAccessMode;
use App\Http\Middleware\EnsureMembershipIsActive;
use App\Http\Middleware\EnsureProjectMembership;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'project.member' => EnsureProjectMembership::class,
            // Fase 8 Batch 2: replaces `role:execution_member[,admin]` on
            // Eksekusi routes specifically — see routes/web.php for which
            // declarations were swapped. `role:` itself is untouched and
            // still used everywhere else (Eksplorasi, admin panels).
            'mode' => EnsureCanAccessMode::class,
        ]);

        $middleware->appendToGroup('web', EnsureMembershipIsActive::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
