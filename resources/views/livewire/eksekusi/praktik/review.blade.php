<div>
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ url('/eksekusi/dashboard') }}" wire:navigate class="text-sm text-muted hover:text-ink">&larr; Kembali ke Dashboard</a>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-muted/25 bg-green-50 p-4 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="relative overflow-hidden rounded-2xl border border-muted/20 bg-white p-6 shadow-warm-md sm:p-8">
        <div class="card-pixel-accent-left" aria-hidden="true"></div>
        <div class="card-pixel-accent-right" aria-hidden="true"></div>

        <p class="text-xs font-medium text-muted">{{ $submission->challenge->title }}</p>
        <h1 class="font-display mt-1 text-xl font-bold text-heading">
            Submission {{ $submission->user->name }}: Percobaan ke-{{ $submission->attempt_number }}
        </h1>
        <p class="mt-1 text-xs text-caption">Dikirim {{ $submission->created_at->diffForHumans() }}</p>

        <div class="mt-4 rounded-lg border border-muted/20 bg-surface/60 p-4 text-sm">
            @include('livewire.eksplorasi.praktik._submission-content')
        </div>
    </div>

    @include('livewire.eksplorasi.praktik._track-map')

    <div class="mt-6 max-w-2xl rounded-xl border border-muted/25 p-6">
        @if ($submission->reviewed_at)
            @php
                $statusBadge = match ($submission->status) {
                    'disetujui' => ['label' => 'Disetujui', 'class' => 'bg-green-100 text-green-700'],
                    'perlu_revisi' => ['label' => 'Perlu Revisi', 'class' => 'bg-red-100 text-red-700'],
                    default => ['label' => $submission->status, 'class' => 'bg-muted/15 text-muted'],
                };
            @endphp
            <div class="flex items-center justify-between">
                <h2 class="font-display text-sm font-bold text-ink">Keputusan Sudah Dibuat</h2>
                <span class="rounded-full px-2 py-0.5 font-mono text-[10px] font-medium uppercase tracking-wide {{ $statusBadge['class'] }}">
                    {{ $statusBadge['label'] }}
                </span>
            </div>
            <p class="mt-2 text-xs text-caption">Direview {{ $submission->reviewed_at->diffForHumans() }}</p>

            @if ($submission->points_awarded !== null)
                <p class="mt-2 text-sm text-ink">Poin diberikan: <span class="font-semibold text-accent">{{ $submission->points_awarded }}</span></p>
            @endif

            @if ($submission->feedback)
                <div class="mt-3 rounded-lg border border-accent/40 bg-accent-soft/30 p-3">
                    <p class="text-xs font-medium text-ink">Feedback:</p>
                    <p class="mt-1 text-xs text-body">{{ $submission->feedback }}</p>
                </div>
            @endif
        @else
            <h2 class="font-display text-sm font-bold text-ink">Beri Keputusan</h2>

            <div class="mt-4">
                <label for="feedback" class="mb-1 block text-sm text-ink">Feedback (wajib untuk kedua keputusan)</label>
                <textarea
                    id="feedback"
                    wire:model="feedback"
                    rows="4"
                    class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                ></textarea>
                @error('feedback')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    wire:click="approve"
                    wire:confirm="Yakin setujui submission ini? Poin akan langsung diberikan ke member."
                    class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90"
                >
                    Setujui
                </button>
                <button
                    type="button"
                    wire:click="requestRevision"
                    wire:confirm="Yakin minta revisi untuk submission ini?"
                    class="rounded-lg border border-muted/40 px-4 py-2 text-sm text-ink hover:border-accent"
                >
                    Minta Revisi
                </button>
            </div>
        @endif
    </div>
</div>
