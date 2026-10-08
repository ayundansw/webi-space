<div>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Antrian Review Praktik</h1>
        <p class="mt-1 text-sm text-muted">Submission yang menunggu direview. Tugaskan ke anggota eksekusi, atau review sendiri.</p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-muted/25 bg-green-50 p-4 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($submissions as $submission)
            <div class="rounded-xl border border-muted/20 bg-white p-4 shadow-warm-xs">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-sm font-medium text-ink">{{ $submission->challenge->title }}</p>
                        <p class="text-xs text-caption">
                            {{ $submission->user->name }} &middot; Percobaan ke-{{ $submission->attempt_number }}
                            &middot; {{ $submission->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <a
                        href="{{ url('/eksekusi/praktik/submissions/'.$submission->id) }}"
                        class="rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90"
                    >
                        Review Sendiri
                    </a>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-muted/15 pt-3">
                    @if ($submission->assignedReviewer)
                        <span class="text-xs text-caption">Ditugaskan ke <span class="font-medium text-ink">{{ $submission->assignedReviewer->name }}</span></span>
                    @else
                        <div class="flex items-center gap-2">
                            <select wire:model="reviewerSelections.{{ $submission->id }}" class="rounded-lg border border-muted/40 px-2 py-1 text-xs focus:border-accent focus:outline-none">
                                <option value="">Pilih reviewer...</option>
                                @foreach ($reviewers as $reviewer)
                                    <option value="{{ $reviewer->id }}">{{ $reviewer->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="assignReviewer('{{ $submission->id }}')" class="rounded-lg border border-muted/40 px-3 py-1 text-xs text-ink hover:border-accent">
                                Tugaskan
                            </button>
                            @error('reviewerSelections.'.$submission->id)
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-muted/40 p-6 text-center text-sm text-muted">
                Tidak ada submission yang menunggu review saat ini.
            </div>
        @endforelse
    </div>
</div>
