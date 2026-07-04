<?php

namespace App\Livewire\Eksplorasi\Resources;

use App\Models\Module;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Referensi Belajar')]
class Index extends Component
{
    public function render()
    {
        $modules = Module::orderBy('order_number')->with('learningResources')->get();

        return view('livewire.eksplorasi.resources.index', ['modules' => $modules]);
    }
}
