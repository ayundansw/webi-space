<?php

namespace App\Livewire\Admin\Curriculum\Units;

use App\Models\Challenge;
use App\Models\ChallengeStep;
use App\Models\Unit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Fase 4 Batch 2b: read-only preview of a Unit's content_blocks, rendered
 * with the exact same <x-content-blocks> component and materi-card styling
 * as the member-facing resources/views/livewire/eksplorasi/unit-show.blade.php,
 * so what admin sees here is what a member actually sees — no separate
 * render path to drift out of sync.
 *
 * Praktik 1 (Fase 5): generalized the same way as ContentEditor (see its
 * docblock) — accepts EITHER `Unit` OR `ChallengeStep` via optional route
 * params, `$unit` unchanged for backward compatibility.
 */
#[Layout('components.layouts.app')]
#[Title('Preview Konten')]
class ContentPreview extends Component
{
    public ?Unit $unit = null;

    public ?ChallengeStep $step = null;

    public function mount(?Unit $unit = null, ?ChallengeStep $step = null, ?Challenge $challenge = null): void
    {
        if ($step && $challenge) {
            abort_if($step->challenge_id !== $challenge->id, 404);
        }

        $this->unit = $unit;
        $this->step = $step;
    }

    private function blockable(): Unit|ChallengeStep
    {
        return $this->unit ?? $this->step;
    }

    public function render()
    {
        $blockable = $this->blockable();

        return view('livewire.admin.curriculum.units.content-preview', [
            'blocks' => $blockable->contentBlocks,
            'title' => $blockable->title,
            'subtitle' => $this->unit
                ? $this->unit->module->title.' · tampilan ini persis seperti yang dilihat anggota Eksplorasi.'
                : $this->step->challenge->title.' · tampilan ini persis seperti yang dilihat anggota Eksplorasi.',
            'metaLine' => $this->unit
                ? $this->unit->estimated_minutes.' menit · '.$this->unit->point_value.' poin'
                : null,
            'backUrl' => $this->unit
                ? url('/admin/curriculum/units/'.$this->unit->id.'/content')
                : url('/admin/curriculum/challenges/'.$this->step->challenge_id.'/steps/'.$this->step->id.'/content'),
        ]);
    }
}
