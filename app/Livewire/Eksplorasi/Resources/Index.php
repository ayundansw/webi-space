<?php

namespace App\Livewire\Eksplorasi\Resources;

use App\Models\LearningResource;
use App\Models\Module;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Bagian A3 (Fase 3): form submit referensi milik anggota.
 * `module_id` OPSIONAL (dikonfirmasi eksplisit ke Aye, migrasi
 * 2026_07_11_000002 -- kosong = referensi "General", ditampilkan di card
 * terpisah). Tidak ada level "unit" di skema ini sama sekali (tidak ada
 * unit_id di learning_resources), jadi "modul/unit mana" di rancangan
 * diselesaikan sebagai "modul saja atau General". `source_name` (kolom
 * lama, NOT NULL) tidak diminta sebagai field terpisah di form -- diturunkan
 * otomatis dari hostname URL yang diisi.
 */
#[Layout('components.layouts.app')]
#[Title('Referensi Belajar')]
class Index extends Component
{
    public bool $showSubmitForm = false;

    public string $title = '';

    public string $url = '';

    public string $description = '';

    public string $moduleId = '';

    public function openSubmitForm(): void
    {
        $this->reset(['title', 'url', 'description', 'moduleId']);
        $this->resetValidation();
        $this->showSubmitForm = true;
    }

    public function closeSubmitForm(): void
    {
        $this->showSubmitForm = false;
    }

    public function submit(): void
    {
        // Fase 8 Batch 3 (§2.2.A): submitting a community resource is a
        // write, off limits for execution_member mode baca even though
        // Referensi itself is explicitly a read-allowed page.
        abort_if(Auth::user()->isReadOnlyExploration(), 403);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'moduleId' => ['nullable', 'uuid', 'exists:modules,id'],
        ]);

        LearningResource::create([
            'module_id' => $validated['moduleId'] ?: null,
            'created_by' => Auth::id(),
            'title' => $validated['title'],
            'url' => $validated['url'],
            'description' => $validated['description'] ?: null,
            'source_name' => $this->deriveSourceName($validated['url']),
        ]);

        $this->showSubmitForm = false;
        $this->reset(['title', 'url', 'description', 'moduleId']);
        session()->flash('resource_submitted', 'Referensi kamu berhasil ditambahkan.');
    }

    public function render()
    {
        $modules = Module::orderBy('order_number')->with(['learningResources' => fn ($q) => $q->latest()])->get();
        $generalResources = LearningResource::whereNull('module_id')->latest()->get();

        return view('livewire.eksplorasi.resources.index', [
            'modules' => $modules,
            'generalResources' => $generalResources,
            'isReadOnlyExploration' => Auth::user()->isReadOnlyExploration(),
        ]);
    }

    private function deriveSourceName(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: $url;

        return preg_replace('/^www\./', '', $host);
    }
}
