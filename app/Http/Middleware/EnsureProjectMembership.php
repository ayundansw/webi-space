<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\Task;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Consolidates the "is this user a member of this project (or admin)"
 * check into route-level middleware — every project-scoped route (current
 * and future) is covered automatically just by sitting inside this
 * middleware's route group, no per-component check to remember.
 *
 * Resolves the Project either directly from a bound {project} route
 * parameter, or via {task}->project for routes scoped by task instead
 * (e.g. /eksekusi/tasks/{task}, not nested under /eksekusi/projects/{project}/...).
 */
class EnsureProjectMembership
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');

        if (! $project instanceof Project) {
            $task = $request->route('task');
            $project = $task instanceof Task ? $task->project : null;
        }

        abort_if(! $project instanceof Project, 500, 'EnsureProjectMembership: no {project} or {task} route parameter to resolve from.');

        $user = $request->user();

        abort_if(
            $user->role !== 'admin' && ! $project->members()->where('user_id', $user->id)->exists(),
            403,
        );

        return $next($request);
    }
}
