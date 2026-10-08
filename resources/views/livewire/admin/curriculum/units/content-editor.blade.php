@push('breadcrumb-actions')
    <a href="{{ $backUrl }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control border border-muted/40 px-4 py-2 text-sm font-medium text-ink shadow-warm-xs hover:border-ink hover:bg-surface-alt">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
        </svg>
        Kembali
    </a>
    <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center gap-1.5 rounded-control border border-muted/40 px-4 py-2 text-sm font-medium text-ink shadow-warm-xs hover:border-ink hover:bg-surface-alt">
        Preview
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M5 12h14" /><path d="m13 6 6 6-6 6" />
        </svg>
    </a>
@endpush

<div>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Editor Blok Konten</h1>
        <p class="mt-1 text-sm text-muted">{{ $contextSubtitle }} &middot; {{ $contextTitle }}</p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-muted/25 bg-green-50 p-4 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    {{-- Daftar blok yang sudah ada, dengan tombol reorder --}}
    <div class="space-y-3">
        @forelse ($blocks as $index => $block)
            <div class="flex items-start gap-3 rounded-xl border border-muted/25 p-4">
                <div class="flex shrink-0 flex-col gap-1">
                    <button
                        type="button"
                        wire:click="moveBlockUp({{ $index }})"
                        @if ($index === 0) disabled @endif
                        class="rounded border border-muted/40 px-2 py-0.5 text-xs text-ink hover:border-accent disabled:cursor-not-allowed disabled:opacity-30"
                        title="Naikkan urutan"
                    >&uarr;</button>
                    <button
                        type="button"
                        wire:click="moveBlockDown({{ $index }})"
                        @if ($index === $blocks->count() - 1) disabled @endif
                        class="rounded border border-muted/40 px-2 py-0.5 text-xs text-ink hover:border-accent disabled:cursor-not-allowed disabled:opacity-30"
                        title="Turunkan urutan"
                    >&darr;</button>
                </div>

                <div class="min-w-0 flex-1">
                    <span class="font-mono inline-block rounded-full bg-accent-soft/30 px-2 py-0.5 text-xs text-ink">
                        {{ \App\Livewire\Admin\Curriculum\Units\ContentEditor::ALL_TYPES[$block->type] ?? $block->type }}
                    </span>

                    <div class="mt-2 truncate text-sm text-ink">
                        @switch($block->type)
                            @case('heading')
                                <span class="font-semibold">H{{ $block->content['level'] ?? '?' }}:</span> {{ $block->content['text'] ?? '' }}
                                @break
                            @case('text')
                                {{ \Illuminate\Support\Str::limit($block->content['markdown'] ?? '', 120) }}
                                @break
                            @case('callout')
                                <span class="font-semibold">[{{ $block->content['variant'] ?? '' }}]</span>
                                @if (! empty($block->content['title']))
                                    {{ $block->content['title'] }}:
                                @endif
                                {{ \Illuminate\Support\Str::limit($block->content['body'] ?? '', 100) }}
                                @break
                            @case('code')
                                @if (! empty($block->content['language']))
                                    <span class="font-mono text-xs text-muted">{{ $block->content['language'] }}</span>
                                @endif
                                <span class="font-mono">{{ \Illuminate\Support\Str::limit($block->content['code'] ?? '', 100) }}</span>
                                @break
                            @case('image')
                                {{ $block->content['caption'] ?? $block->content['alt'] ?? $block->content['url'] ?? '' }}
                                @break
                            @case('video')
                                {{ $block->content['caption'] ?? $block->content['url'] ?? '' }}
                                @break
                            @case('list')
                                <span class="font-semibold">{{ ($block->content['style'] ?? 'unordered') === 'ordered' ? 'Bernomor' : 'Bullet' }}:</span>
                                {{ count($block->content['items'] ?? []) }} item
                                @break
                            @case('table')
                                {{ count($block->content['rows'] ?? []) }} baris &times; {{ count($block->content['rows'][0] ?? $block->content['headers'] ?? []) }} kolom
                                @break
                            @case('custom_html')
                                <span class="font-mono">{{ \Illuminate\Support\Str::limit($block->content['html'] ?? '', 100) }}</span>
                                @break
                            @default
                                <span class="text-muted">(preview belum tersedia untuk tipe ini)</span>
                        @endswitch
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    <button type="button" wire:click="editBlock('{{ $block->id }}')" class="text-sm text-ink underline hover:text-accent">Edit</button>
                    <button
                        type="button"
                        wire:click="deleteBlock('{{ $block->id }}')"
                        wire:confirm="Yakin hapus blok ini? Tindakan ini tidak bisa dibatalkan."
                        class="text-sm text-red-600 underline hover:text-red-700"
                    >
                        Hapus
                    </button>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-muted/40 p-6 text-center text-sm text-muted">
                Belum ada blok konten untuk unit ini. Klik "Tambah Blok" untuk mulai.
            </div>
        @endforelse
    </div>

    @if ($blocks->isNotEmpty())
        <div class="mt-3 flex items-center gap-3">
            <button
                type="button"
                wire:click="saveOrder"
                @unless ($orderDirty) disabled @endunless
                class="rounded-lg px-4 py-2 text-sm font-medium disabled:cursor-not-allowed {{ $orderDirty ? 'bg-accent text-white hover:bg-accent/90' : 'bg-muted/15 text-muted' }}"
            >
                Simpan Urutan
            </button>
            @if ($orderDirty)
                <span class="text-xs text-accent">Ada perubahan urutan yang belum disimpan.</span>
            @endif
        </div>
    @endif

    {{-- Tombol tambah + pemilih tipe --}}
    @if (! $formType)
        <div class="mt-6">
            @if (! $showTypePicker)
                <button
                    type="button"
                    wire:click="openTypePicker"
                    class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90"
                >
                    Tambah Blok
                </button>
            @else
                <div class="rounded-xl border border-muted/25 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="font-display text-sm font-bold text-ink">Pilih Tipe Blok</h2>
                        <button type="button" wire:click="closeTypePicker" class="text-sm text-muted hover:text-ink">Batal</button>
                    </div>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach (\App\Livewire\Admin\Curriculum\Units\ContentEditor::ALL_TYPES as $typeKey => $typeLabel)
                            <button
                                type="button"
                                wire:click="selectType('{{ $typeKey }}')"
                                class="rounded-lg border border-muted/40 px-3 py-2 text-left text-sm text-ink hover:border-accent"
                            >
                                {{ $typeLabel }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Form tambah/edit blok --}}
    @if ($formType)
        <div class="mt-6 max-w-2xl rounded-xl border border-muted/25 p-6">
            <h2 class="font-display mb-4 text-sm font-bold text-ink">
                {{ $editingBlockId ? 'Edit Blok' : 'Tambah Blok' }}: {{ \App\Livewire\Admin\Curriculum\Units\ContentEditor::ALL_TYPES[$formType] }}
            </h2>

            <form wire:submit="saveBlock" class="space-y-4">
                @switch($formType)
                    @case('heading')
                        <div>
                            <label for="level" class="mb-1 block text-sm text-ink">Level</label>
                            <select id="level" wire:model="level" class="w-full max-w-xs rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                                <option value="1">1 (Terbesar)</option>
                                <option value="2">2</option>
                                <option value="3">3 (Terkecil)</option>
                            </select>
                            @error('level') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="text" class="mb-1 block text-sm text-ink">Teks</label>
                            <input type="text" id="text" wire:model="text" class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                            @error('text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @break

                    @case('text')
                        <div>
                            <label for="markdown" class="mb-1 block text-sm text-ink">Teks (markdown)</label>
                            <textarea id="markdown" wire:model="markdown" rows="6" class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
                            @error('markdown') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @break

                    @case('callout')
                        <div>
                            <label for="variant" class="mb-1 block text-sm text-ink">Variant</label>
                            <select id="variant" wire:model="variant" class="w-full max-w-xs rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                                <option value="info">Info</option>
                                <option value="tip">Tip</option>
                                <option value="warning">Peringatan</option>
                            </select>
                            @error('variant') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="title" class="mb-1 block text-sm text-ink">Judul (opsional)</label>
                            <input type="text" id="title" wire:model="title" class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="body" class="mb-1 block text-sm text-ink">Isi (markdown)</label>
                            <textarea id="body" wire:model="body" rows="4" class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
                            @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @break

                    @case('code')
                        <div>
                            <label for="language" class="mb-1 block text-sm text-ink">Bahasa (opsional, label saja)</label>
                            <input type="text" id="language" wire:model="language" placeholder="mis. bash, php, javascript" class="w-full max-w-xs rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                            @error('language') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="code" class="mb-1 block text-sm text-ink">Kode</label>
                            <textarea id="code" wire:model="code" rows="6" class="font-mono w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
                            @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @break

                    @case('image')
                        <div>
                            <label for="url" class="mb-1 block text-sm text-ink">URL Gambar</label>
                            <input type="text" id="url" wire:model="url" placeholder="https://..." class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                            <p class="mt-1 text-xs text-muted">Link eksternal yang sudah di-hosting, belum ada fitur upload file.</p>
                            @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="alt" class="mb-1 block text-sm text-ink">Alt text (opsional)</label>
                            <input type="text" id="alt" wire:model="alt" class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                            @error('alt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="caption" class="mb-1 block text-sm text-ink">Caption (opsional)</label>
                            <input type="text" id="caption" wire:model="caption" class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                            @error('caption') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @break

                    @case('video')
                        <div>
                            <label for="url" class="mb-1 block text-sm text-ink">URL Video</label>
                            <input type="text" id="url" wire:model="url" placeholder="https://youtube.com/watch?v=... atau https://vimeo.com/..." class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                            <p class="mt-1 text-xs text-muted">Provider (YouTube/Vimeo) dideteksi otomatis dari URL. Provider lain tetap tersimpan sebagai tautan biasa.</p>
                            @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="caption" class="mb-1 block text-sm text-ink">Caption (opsional)</label>
                            <input type="text" id="caption" wire:model="caption" class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                            @error('caption') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @break

                    @case('list')
                        <div>
                            <label for="style" class="mb-1 block text-sm text-ink">Gaya List</label>
                            <select id="style" wire:model="style" class="w-full max-w-xs rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                                <option value="unordered">Tanpa nomor (bullet)</option>
                                <option value="ordered">Bernomor</option>
                            </select>
                            @error('style') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-ink">Item</label>
                            <div class="space-y-2">
                                @foreach ($items as $index => $item)
                                    <div class="flex items-center gap-2">
                                        <input type="text" wire:model="items.{{ $index }}" class="flex-1 rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                                        <button
                                            type="button"
                                            wire:click="removeListItem({{ $index }})"
                                            @if (count($items) <= 1) disabled @endif
                                            class="shrink-0 text-sm text-red-600 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-30"
                                        >Hapus</button>
                                    </div>
                                    @error('items.'.$index) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                                @endforeach
                            </div>
                            <button type="button" wire:click="addListItem" class="mt-2 rounded-lg border border-muted/40 px-3 py-1.5 text-xs text-ink hover:border-accent">+ Tambah Item</button>
                            @error('items') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @break

                    @case('table')
                        @php $columnCount = max(count($headers), count($rows[0] ?? []), 1); @endphp
                        <div>
                            <p class="mb-2 text-xs text-muted">Header opsional, kosongkan semua sel header supaya tabel tampil tanpa judul kolom.</p>
                            <div class="overflow-x-auto rounded-lg border border-muted/25">
                                <table class="w-full border-collapse text-xs">
                                    <thead>
                                        <tr>
                                            @for ($col = 0; $col < $columnCount; $col++)
                                                <th class="border border-muted/25 p-1 align-top">
                                                    <input type="text" wire:model="headers.{{ $col }}" placeholder="Header {{ $col + 1 }}" class="w-full rounded border border-muted/40 px-2 py-1 text-xs focus:border-accent focus:outline-none">
                                                    <button
                                                        type="button"
                                                        wire:click="removeTableColumn({{ $col }})"
                                                        @if ($columnCount <= 1) disabled @endif
                                                        class="mt-1 text-xs text-red-600 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-30"
                                                    >Hapus kolom</button>
                                                </th>
                                            @endfor
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rows as $rowIndex => $row)
                                            <tr>
                                                @for ($col = 0; $col < $columnCount; $col++)
                                                    <td class="border border-muted/25 p-1">
                                                        <input type="text" wire:model="rows.{{ $rowIndex }}.{{ $col }}" class="w-full rounded border border-muted/40 px-2 py-1 text-xs focus:border-accent focus:outline-none">
                                                    </td>
                                                @endfor
                                                <td class="border border-muted/25 p-1 text-center align-top">
                                                    <button
                                                        type="button"
                                                        wire:click="removeTableRow({{ $rowIndex }})"
                                                        @if (count($rows) <= 1) disabled @endif
                                                        class="text-xs text-red-600 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-30"
                                                    >Hapus baris</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-2 flex gap-2">
                                <button type="button" wire:click="addTableRow" class="rounded-lg border border-muted/40 px-3 py-1.5 text-xs text-ink hover:border-accent">+ Tambah Baris</button>
                                <button type="button" wire:click="addTableColumn" class="rounded-lg border border-muted/40 px-3 py-1.5 text-xs text-ink hover:border-accent">+ Tambah Kolom</button>
                            </div>
                            @error('rows') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @break

                    @case('custom_html')
                        <div>
                            <label for="html" class="mb-1 block text-sm text-ink">HTML</label>
                            <textarea id="html" wire:model="html" rows="8" class="font-mono w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
                            <p class="mt-1 text-xs text-amber-700">HTML akan disanitasi otomatis saat ditampilkan ke member: elemen berbahaya seperti &lt;script&gt;, iframe, form, dan atribut event (onclick dst) akan dihapus.</p>
                            @error('html') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @break
                @endswitch

                <div class="flex items-center gap-3">
                    <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60">
                        Simpan Blok
                    </button>
                    <button type="button" wire:click="cancelForm" class="text-sm text-muted hover:text-ink">Batal</button>
                </div>
            </form>
        </div>
    @endif
</div>
