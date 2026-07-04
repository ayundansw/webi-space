<?php

namespace App\Livewire\Eksekusi\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Proyek')]
class Index extends Component
{
    public function render()
    {
        $user = Auth::user();

        $query = Project::query()->orderByDesc('created_at');

        if ($user->role !== 'admin') {
            $query->whereHas('members', fn ($q) => $q->where('user_id', $user->id));
        }

        return view('livewire.eksekusi.projects.index', ['projects' => $query->get()]);
    }
}
