<?php

namespace App\Livewire\Eksplorasi\Forum;

use App\Models\ForumThread;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Forum Diskusi Eksplorasi')]
class Index extends Component
{
    public function render()
    {
        // Fase 7 Batch 4: forum_threads.project_id now exists (Forum Proyek/
        // Forum General, Eksekusi-only) — filtered out explicitly so this
        // list (and its target-icon markup, which assumes a real peer/pic
        // value) never mixes in an Eksekusi thread, per the task prompt's
        // explicit "JANGAN gabungkan thread Eksplorasi dan Eksekusi".
        // Forum General Eksekusi ("utang Fase 7") ALSO has project_id null
        // — whereNull('project_id') alone is no longer sufficient to
        // isolate Eksplorasi threads, so `portal` is now the explicit,
        // primary filter (project_id stays too, harmlessly redundant for
        // Eksplorasi rows, but no longer load-bearing on its own).
        $threads = ForumThread::with(['creator', 'module', 'unit', 'replies'])
            ->where('portal', 'exploration')
            ->whereNull('project_id')
            ->latest()
            ->get();

        return view('livewire.eksplorasi.forum.index', [
            'threads' => $threads,
            'isReadOnlyExploration' => Auth::user()->isReadOnlyExploration(),
        ]);
    }
}
