<?php

namespace App\Livewire\Eksplorasi\Forum;

use App\Models\ForumThread;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Forum Diskusi Eksplorasi')]
class Index extends Component
{
    public function render()
    {
        $threads = ForumThread::with(['creator', 'module', 'unit', 'replies'])
            ->latest()
            ->get();

        return view('livewire.eksplorasi.forum.index', ['threads' => $threads]);
    }
}
