<?php

namespace App\Livewire\Admin\Curriculum\Modules;

use App\Models\Module;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kelola Kurikulum: Modul')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.admin.curriculum.modules.index', [
            'modules' => Module::withCount('units')->orderBy('order_number')->get(),
        ]);
    }
}
