<?php

namespace App\Providers;

use App\Models\Checkpoint;
use App\Models\ForumThread;
use App\Models\Module;
use App\Models\Project;
use App\Models\Task;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'project' => Project::class,
            'task' => Task::class,
            'unit' => Unit::class,
            'checkpoint' => Checkpoint::class,
            'module' => Module::class,
            'forum_thread' => ForumThread::class,
        ]);
    }
}
