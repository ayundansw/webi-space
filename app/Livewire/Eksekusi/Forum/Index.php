<?php

namespace App\Livewire\Eksekusi\Forum;

use App\Models\ForumThread;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Forum General Eksekusi ("utang Fase 7 Batch 4") — lintas-proyek, BUKAN
 * Forum Proyek (App\Livewire\Eksekusi\Projects\Tabs\Forum, project_id
 * terisi). Threads di sini: `portal = 'execution'` DAN `project_id = null`.
 * Backend reuse App\Services\Forum\ForumService — sama persis dipakai
 * Forum Eksplorasi dan Forum Proyek, nol logic Forum baru ditulis di sini.
 *
 * RBAC lewat middleware route `mode:execution,admin` (bukan `role:`) —
 * konsisten dengan seluruh rute Eksekusi lain sejak Fase 8 Batch 2, member
 * Mode Ganda yang approved+aktif otomatis ikut bisa akses, tidak perlu
 * pengecualian khusus di sini.
 */
#[Layout('components.layouts.app')]
#[Title('Forum General Eksekusi')]
class Index extends Component
{
    public function render()
    {
        $threads = ForumThread::with(['creator', 'replies'])
            ->where('portal', 'execution')
            ->whereNull('project_id')
            ->latest()
            ->get();

        return view('livewire.eksekusi.forum.index', [
            'threads' => $threads,
        ]);
    }
}
