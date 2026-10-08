<?php

namespace App\Livewire\Admin\Curriculum\Modules;

use App\Models\Module;
use App\Services\Content\CurriculumReorderService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Buat Modul Baru')]
class Create extends Component
{
    public string $order_number = '';

    public string $title = '';

    public string $description = '';

    public string $level_number = '';

    public function save(CurriculumReorderService $reorder): void
    {
        $validated = $this->validate([
            'order_number' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'level_number' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated, $reorder) {
            $reorder->makeRoomForNewPosition(Module::class, [], (int) $validated['order_number']);
            Module::create($validated);
        });

        session()->flash('status', 'Modul baru berhasil dibuat.');

        $this->redirect('/admin/curriculum/modules', navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.curriculum.modules.create');
    }
}
