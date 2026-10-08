<?php

namespace App\Livewire\Admin\DualMode;

use App\Models\DualModeRequest;
use App\Services\DualMode\DualModeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Fase 8 Batch 5 (docs/v_2.0/archive/sumber-konsolidasi/RANCANGAN_FINAL_WEBI-SPACE_v2.md §5.3 —
 * "Antrian Permintaan Mode Eksekusi"). Same pattern as
 * App\Livewire\Admin\Curriculum\Submissions\Index — full-page Livewire,
 * relies entirely on the `role:admin` route middleware for RBAC (no
 * abort_if in mount(), consistent with every other Admin\* panel).
 *
 * All status-mutation logic stays in DualModeService (Batch 4) — this
 * component only resolves the target DualModeRequest and calls approve()/
 * reject(), same division of responsibility as
 * Eksekusi\Ideas\Approve/Index do for ProjectIdeaService.
 */
#[Layout('components.layouts.app')]
#[Title('Permintaan Mode Ganda')]
class Index extends Component
{
    /** @var array<string, string> keyed by request id, holds the reject note being typed */
    public array $rejectNotes = [];

    /** id of the request whose reject form is currently open, or '' */
    public string $openRejectForm = '';

    public function approve(string $requestId, DualModeService $service): void
    {
        $request = DualModeRequest::where('status', 'pending')->findOrFail($requestId);

        $service->approve($request, Auth::user());

        session()->flash('status', "Akses Eksekusi untuk {$request->user->name} disetujui.");
    }

    public function openReject(string $requestId): void
    {
        $this->openRejectForm = $requestId;
    }

    public function cancelReject(): void
    {
        $this->openRejectForm = '';
    }

    public function reject(string $requestId, DualModeService $service): void
    {
        $request = DualModeRequest::where('status', 'pending')->findOrFail($requestId);

        $note = trim($this->rejectNotes[$requestId] ?? '');

        $service->reject($request, Auth::user(), $note !== '' ? $note : null);

        unset($this->rejectNotes[$requestId]);
        $this->openRejectForm = '';

        session()->flash('status', "Permintaan {$request->user->name} ditolak.");
    }

    public function render()
    {
        return view('livewire.admin.dual-mode.index', [
            'requests' => DualModeRequest::with('user')
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->get(),
        ]);
    }
}
