<div>
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ url('/eksplorasi/praktik') }}" wire:navigate class="text-sm text-muted hover:text-ink">&larr; Kembali ke Praktik</a>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-muted/25 bg-green-50 p-4 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    @php
        $levelBadge = match ($challenge->level) {
            'low' => 'bg-green-100 text-green-700',
            'mid' => 'bg-amber-100 text-amber-700',
            'high' => 'bg-red-100 text-red-700',
        };
    @endphp

    <div class="relative overflow-hidden rounded-2xl border border-muted/20 bg-white p-6 shadow-warm-md sm:p-8">
        <div class="card-pixel-accent-left" aria-hidden="true"></div>
        <div class="card-pixel-accent-right" aria-hidden="true"></div>

        <div class="flex items-center gap-2">
            <span class="rounded-full px-2 py-0.5 font-mono text-[10px] font-medium uppercase tracking-wide {{ $levelBadge }}">
                {{ strtoupper($challenge->level) }}
            </span>
            <span class="font-mono text-xs text-caption">{{ $challenge->points_reward }} poin</span>
        </div>

        <h1 class="font-display mt-2 text-2xl font-bold text-heading">{{ $challenge->title }}</h1>
        <p class="mt-3 text-sm leading-relaxed text-body">{{ $challenge->description }}</p>

        @if ($attachments->isNotEmpty())
            <div class="mt-4 border-t border-muted/20 pt-4">
                <p class="text-xs font-medium text-muted">Lampiran</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($attachments as $attachment)
                        <a
                            href="{{ url('/challenge-attachments/'.$attachment->id.'/download') }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-muted/40 px-3 py-1.5 text-xs text-ink hover:border-accent hover:text-accent"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 shrink-0">
                                <path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" />
                            </svg>
                            {{ $attachment->file_name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @include('livewire.eksplorasi.praktik._track-map')

    {{-- Submission history --}}
    @if ($submissions->isNotEmpty())
        <div class="mt-6">
            <h2 class="font-display text-lg font-bold text-ink">Riwayat Submission Kamu</h2>
            <div class="mt-3 space-y-3">
                @foreach ($submissions as $submission)
                    @php
                        $statusBadge = match ($submission->status) {
                            'disetujui' => ['label' => 'Disetujui', 'class' => 'bg-green-100 text-green-700'],
                            'perlu_revisi' => ['label' => 'Perlu Revisi', 'class' => 'bg-red-100 text-red-700'],
                            default => ['label' => 'Menunggu Review', 'class' => 'bg-amber-100 text-amber-700'],
                        };
                    @endphp
                    <div class="rounded-xl border border-muted/20 bg-white p-4 shadow-warm-xs">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium text-ink">Percobaan ke-{{ $submission->attempt_number }}</span>
                            <span class="rounded-full px-2 py-0.5 font-mono text-[10px] font-medium uppercase tracking-wide {{ $statusBadge['class'] }}">
                                {{ $statusBadge['label'] }}
                            </span>
                        </div>

                        <div class="mt-2 text-xs text-caption">
                            @include('livewire.eksplorasi.praktik._submission-content')
                        </div>

                        @if ($submission->feedback)
                            <div class="mt-3 rounded-lg border border-accent/40 bg-accent-soft/30 p-3">
                                <p class="text-xs font-medium text-ink">Feedback:</p>
                                <p class="mt-1 text-xs text-body">{{ $submission->feedback }}</p>
                            </div>
                        @endif

                        <p class="font-mono mt-2 text-[10px] text-caption">{{ $submission->created_at->diffForHumans() }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Submit form --}}
    <div class="mt-6 max-w-xl rounded-xl border border-muted/25 p-6">
        <h2 class="font-display text-sm font-bold text-ink">
            {{ $submissions->isNotEmpty() ? 'Submit Ulang' : 'Submit Hasil Kamu' }}
        </h2>
        <p class="mt-1 text-xs text-muted">
            Kerjakan challenge ini di luar sistem (mis. bikin project di device sendiri), lalu kirim link/tulisan hasilnya di sini. Boleh submit ulang kapan saja, tanpa batas percobaan.
        </p>

        <form wire:submit="submit" class="mt-4 space-y-4">
            <div>
                <label for="submissionType" class="mb-1 block text-sm text-ink">Tipe Submission</label>
                <select
                    id="submissionType"
                    wire:model.live="submissionType"
                    class="w-full max-w-xs rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                >
                    <option value="link">Link (repo/demo/dokumen)</option>
                    <option value="text">Teks</option>
                    <option value="file">Upload File</option>
                </select>
                @error('submissionType')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if ($submissionType === 'file')
                <div>
                    <label for="submissionFile" class="mb-1 block text-sm text-ink">File</label>
                    <input
                        type="file"
                        id="submissionFile"
                        wire:model="submissionFile"
                        class="w-full text-sm"
                    >
                    <p class="mt-1 text-xs text-muted">Maks. 10MB. Tipe yang diterima: gambar (jpg/png/gif/webp), PDF, video (mp4/mov/webm), atau zip.</p>
                    <div wire:loading wire:target="submissionFile" class="mt-1 text-xs text-muted">Mengunggah...</div>
                    @error('submissionFile')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @else
                <div>
                    <label for="content" class="mb-1 block text-sm text-ink">
                        {{ $submissionType === 'link' ? 'URL' : 'Isi' }}
                    </label>
                    @if ($submissionType === 'link')
                        <input
                            type="text"
                            id="content"
                            wire:model="content"
                            placeholder="https://..."
                            class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                        >
                    @else
                        <textarea
                            id="content"
                            wire:model="content"
                            rows="5"
                            class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                        ></textarea>
                    @endif
                    @error('content')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60"
            >
                Kirim Submission
            </button>
        </form>
    </div>
</div>
