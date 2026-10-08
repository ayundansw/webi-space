<?php

namespace App\Livewire\Admin\Curriculum\Units;

use App\Models\Module;
use App\Models\Unit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kelola Kurikulum: Unit')]
class Index extends Component
{
    #[Url]
    public string $moduleFilter = '';

    public function render()
    {
        $units = Unit::with('module')
            ->when($this->moduleFilter !== '', fn ($query) => $query->where('module_id', $this->moduleFilter))
            ->orderBy('module_id')
            ->orderBy('order_number')
            ->get();

        return view('livewire.admin.curriculum.units.index', [
            'units' => $units,
            'modules' => Module::orderBy('order_number')->get(),
        ]);
    }
}
