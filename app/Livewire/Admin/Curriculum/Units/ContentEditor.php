<?php

namespace App\Livewire\Admin\Curriculum\Units;

use App\Models\Challenge;
use App\Models\ChallengeStep;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Editor Blok Konten — admin CRUD for content_blocks (9 types: heading,
 * text, callout, code, image, video, list, table, custom_html). JSON shape
 * per type follows resources/views/components/content-block/*.blade.php.
 *
 * Accepts EITHER `Unit` (Materi) OR `ChallengeStep` (Praktik track map);
 * both route params are optional, blockable() picks whichever is set.
 *
 * Reorder buttons swap $orderedBlockIds in-memory; persisted only via a
 * separate "Simpan Urutan" action, resyncing from DB on any add/edit/delete.
 */
#[Layout('components.layouts.app')]
#[Title('Editor Blok Konten')]
class ContentEditor extends Component
{
    public ?Unit $unit = null;

    public ?ChallengeStep $step = null;

    public bool $showTypePicker = false;

    public ?string $formType = null;

    public ?string $editingBlockId = null;

    // Heading
    public string $level = '2';

    public string $text = '';

    // Text
    public string $markdown = '';

    // Callout
    public string $variant = 'info';

    public string $title = '';

    public string $body = '';

    // Code
    public string $language = '';

    public string $code = '';

    // Image + Video share `url`/`caption`; Image adds `alt`.
    public string $url = '';

    public string $alt = '';

    public string $caption = '';

    // List
    public string $style = 'unordered';

    public array $items = [''];

    // Table
    public array $headers = [];

    public array $rows = [['']];

    // Custom HTML
    public string $html = '';

    // Reorder (2b) — see class docblock.
    public array $orderedBlockIds = [];

    public bool $orderDirty = false;

    public const ALL_TYPES = [
        'heading' => 'Heading',
        'text' => 'Teks',
        'callout' => 'Callout',
        'code' => 'Kode',
        'image' => 'Gambar',
        'video' => 'Video',
        'list' => 'List',
        'table' => 'Tabel',
        'custom_html' => 'HTML Kustom',
    ];

    public function mount(?Unit $unit = null, ?ChallengeStep $step = null, ?Challenge $challenge = null): void
    {
        if ($step && $challenge) {
            abort_if($step->challenge_id !== $challenge->id, 404);
        }

        $this->unit = $unit;
        $this->step = $step;
        $this->resyncOrder();
    }

    /**
     * The one thing this whole component is generalized over. `??` rather
     * than a stored "which mode" flag — exactly one of $unit/$step is ever
     * non-null (routes only ever supply one), so this is unambiguous.
     */
    private function blockable(): Unit|ChallengeStep
    {
        return $this->unit ?? $this->step;
    }

    private function contextTitle(): string
    {
        return $this->blockable()->title;
    }

    private function contextSubtitle(): string
    {
        return $this->unit
            ? $this->unit->module->title
            : $this->step->challenge->title.' · Step '.$this->step->order_number;
    }

    private function backUrl(): string
    {
        return $this->unit
            ? url('/admin/curriculum/units/'.$this->unit->id.'/edit')
            : url('/admin/curriculum/challenges/'.$this->step->challenge_id.'/steps/'.$this->step->id.'/edit');
    }

    private function previewUrl(): string
    {
        return $this->unit
            ? url('/admin/curriculum/units/'.$this->unit->id.'/content/preview')
            : url('/admin/curriculum/challenges/'.$this->step->challenge_id.'/steps/'.$this->step->id.'/content/preview');
    }

    public function openTypePicker(): void
    {
        $this->showTypePicker = true;
    }

    public function closeTypePicker(): void
    {
        $this->showTypePicker = false;
    }

    public function selectType(string $type): void
    {
        if (! array_key_exists($type, self::ALL_TYPES)) {
            return;
        }

        $this->resetForm();
        $this->formType = $type;
        $this->editingBlockId = null;
        $this->showTypePicker = false;
    }

    public function editBlock(string $blockId): void
    {
        $block = $this->blockable()->contentBlocks()->findOrFail($blockId);
        $data = $block->content;

        $this->resetForm();
        $this->formType = $block->type;
        $this->editingBlockId = $block->id;
        $this->showTypePicker = false;

        match ($block->type) {
            'heading' => $this->fill(['level' => (string) ($data['level'] ?? 2), 'text' => $data['text'] ?? '']),
            'text' => $this->fill(['markdown' => $data['markdown'] ?? '']),
            'callout' => $this->fill(['variant' => $data['variant'] ?? 'info', 'title' => $data['title'] ?? '', 'body' => $data['body'] ?? '']),
            'code' => $this->fill(['language' => $data['language'] ?? '', 'code' => $data['code'] ?? '']),
            'image' => $this->fill(['url' => $data['url'] ?? '', 'alt' => $data['alt'] ?? '', 'caption' => $data['caption'] ?? '']),
            'video' => $this->fill(['url' => $data['url'] ?? '', 'caption' => $data['caption'] ?? '']),
            'list' => $this->fill(['style' => $data['style'] ?? 'unordered', 'items' => ! empty($data['items']) ? array_values($data['items']) : ['']]),
            'table' => $this->fill(['headers' => $data['headers'] ?? [], 'rows' => ! empty($data['rows']) ? array_values(array_map('array_values', $data['rows'])) : [['']]]),
            'custom_html' => $this->fill(['html' => $data['html'] ?? '']),
            default => null,
        };
    }

    public function cancelForm(): void
    {
        $this->formType = null;
        $this->editingBlockId = null;
        $this->resetForm();
    }

    public function saveBlock(): void
    {
        $content = match ($this->formType) {
            'heading' => $this->collectHeadingContent(),
            'text' => $this->collectTextContent(),
            'callout' => $this->collectCalloutContent(),
            'code' => $this->collectCodeContent(),
            'image' => $this->collectImageContent(),
            'video' => $this->collectVideoContent(),
            'list' => $this->collectListContent(),
            'table' => $this->collectTableContent(),
            'custom_html' => $this->collectCustomHtmlContent(),
            default => null,
        };

        if ($content === null) {
            return;
        }

        if ($this->editingBlockId) {
            $this->blockable()->contentBlocks()->where('id', $this->editingBlockId)->update(['content' => $content]);
        } else {
            $nextOrder = ($this->blockable()->contentBlocks()->max('order') ?? 0) + 1;
            $this->blockable()->contentBlocks()->create([
                'type' => $this->formType,
                'content' => $content,
                'order' => $nextOrder,
            ]);
        }

        $this->formType = null;
        $this->editingBlockId = null;
        $this->resetForm();
        $this->resyncOrder();

        session()->flash('status', 'Blok berhasil disimpan.');
    }

    public function deleteBlock(string $blockId): void
    {
        $this->blockable()->contentBlocks()->where('id', $blockId)->delete();
        $this->resyncOrder();

        session()->flash('status', 'Blok berhasil dihapus.');
    }

    // --- List: dynamic item add/remove ---

    public function addListItem(): void
    {
        $this->items[] = '';
    }

    public function removeListItem(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    // --- Table: dynamic row/column add/remove ---

    public function addTableRow(): void
    {
        $columnCount = max(count($this->headers), count($this->rows[0] ?? []), 1);
        $this->rows[] = array_fill(0, $columnCount, '');
    }

    public function removeTableRow(int $index): void
    {
        if (count($this->rows) <= 1) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function addTableColumn(): void
    {
        $this->headers[] = '';

        foreach ($this->rows as $i => $row) {
            $this->rows[$i][] = '';
        }
    }

    public function removeTableColumn(int $columnIndex): void
    {
        $columnCount = max(count($this->headers), count($this->rows[0] ?? []));

        if ($columnCount <= 1) {
            return;
        }

        if (array_key_exists($columnIndex, $this->headers)) {
            unset($this->headers[$columnIndex]);
            $this->headers = array_values($this->headers);
        }

        foreach ($this->rows as $i => $row) {
            unset($this->rows[$i][$columnIndex]);
            $this->rows[$i] = array_values($this->rows[$i]);
        }
    }

    // --- Reorder ---

    public function moveBlockUp(int $index): void
    {
        $this->swapOrder($index, $index - 1);
    }

    public function moveBlockDown(int $index): void
    {
        $this->swapOrder($index, $index + 1);
    }

    private function swapOrder(int $index, int $targetIndex): void
    {
        if ($targetIndex < 0 || $targetIndex >= count($this->orderedBlockIds)) {
            return;
        }

        [$this->orderedBlockIds[$index], $this->orderedBlockIds[$targetIndex]]
            = [$this->orderedBlockIds[$targetIndex], $this->orderedBlockIds[$index]];

        $this->orderDirty = true;
    }

    public function saveOrder(): void
    {
        DB::transaction(function () {
            foreach ($this->orderedBlockIds as $position => $blockId) {
                $this->blockable()->contentBlocks()->where('id', $blockId)->update(['order' => $position + 1]);
            }
        });

        $this->orderDirty = false;

        session()->flash('status', 'Urutan blok disimpan.');
    }

    private function resyncOrder(): void
    {
        $this->orderedBlockIds = $this->blockable()->contentBlocks()->orderBy('order')->pluck('id')->all();
        $this->orderDirty = false;
    }

    // --- Per-type validation ---

    private function collectHeadingContent(): array
    {
        $validated = $this->validate([
            'level' => ['required', 'integer', 'in:1,2,3'],
            'text' => ['required', 'string', 'max:255'],
        ]);

        return ['level' => (int) $validated['level'], 'text' => $validated['text']];
    }

    private function collectTextContent(): array
    {
        $validated = $this->validate([
            'markdown' => ['required', 'string'],
        ]);

        return ['markdown' => $validated['markdown']];
    }

    private function collectCalloutContent(): array
    {
        $validated = $this->validate([
            'variant' => ['required', 'in:info,tip,warning'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        return [
            'variant' => $validated['variant'],
            'title' => $validated['title'] ?: null,
            'body' => $validated['body'],
        ];
    }

    private function collectCodeContent(): array
    {
        $validated = $this->validate([
            'language' => ['nullable', 'string', 'max:50'],
            'code' => ['required', 'string'],
        ]);

        return ['language' => $validated['language'] ?: null, 'code' => $validated['code']];
    }

    private function collectImageContent(): array
    {
        $validated = $this->validate([
            'url' => ['required', 'url', 'max:2000'],
            'alt' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        return [
            'url' => $validated['url'],
            'alt' => $validated['alt'] ?: null,
            'caption' => $validated['caption'] ?: null,
        ];
    }

    private function collectVideoContent(): array
    {
        $validated = $this->validate([
            'url' => ['required', 'url', 'max:2000'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        return ['url' => $validated['url'], 'caption' => $validated['caption'] ?: null];
    }

    private function collectListContent(): array
    {
        $validated = $this->validate([
            'style' => ['required', 'in:unordered,ordered'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'string', 'max:1000'],
        ]);

        return ['style' => $validated['style'], 'items' => array_values($validated['items'])];
    }

    private function collectTableContent(): array
    {
        $validated = $this->validate([
            'headers' => ['nullable', 'array'],
            'headers.*' => ['nullable', 'string', 'max:255'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*' => ['required', 'array', 'min:1'],
            'rows.*.*' => ['nullable', 'string', 'max:1000'],
        ]);

        // Headers is genuinely optional (renderer skips <thead> entirely when
        // empty) — an all-blank header row is treated the same as "no
        // headers" rather than persisting a row of empty strings.
        $headers = array_values($validated['headers'] ?? []);
        if (count(array_filter($headers, fn ($h) => trim((string) $h) !== '')) === 0) {
            $headers = [];
        }

        return ['headers' => $headers, 'rows' => array_values(array_map('array_values', $validated['rows']))];
    }

    private function collectCustomHtmlContent(): array
    {
        $validated = $this->validate([
            'html' => ['required', 'string'],
        ]);

        return ['html' => $validated['html']];
    }

    private function resetForm(): void
    {
        $this->reset([
            'level', 'text', 'markdown', 'variant', 'title', 'body', 'language', 'code',
            'url', 'alt', 'caption', 'style', 'items', 'headers', 'rows', 'html',
        ]);
        $this->resetValidation();
    }

    public function render()
    {
        $blocksById = $this->blockable()->contentBlocks->keyBy('id');

        return view('livewire.admin.curriculum.units.content-editor', [
            // Rendered in $orderedBlockIds order (not the DB's own `order`
            // column) so an unsaved pending reorder is reflected immediately.
            'blocks' => collect($this->orderedBlockIds)->map(fn ($id) => $blocksById->get($id))->filter()->values(),
            'contextTitle' => $this->contextTitle(),
            'contextSubtitle' => $this->contextSubtitle(),
            'backUrl' => $this->backUrl(),
            'previewUrl' => $this->previewUrl(),
        ]);
    }
}
