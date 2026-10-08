<div>
    <h1 class="font-display text-2xl font-bold text-ink">Permintaan Mode Ganda</h1>
    <p class="mt-1 text-sm text-muted">Anggota Eksplorasi yang mengajukan akses Mode Eksekusi (docs §2.2.B).</p>

    @if (session('status'))
        <div class="mt-4 rounded-xl border border-success/40 bg-success-soft p-4 text-sm text-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-6 space-y-3">
        @forelse ($requests as $request)
            <div class="rounded-card border border-muted/20 bg-white p-4 shadow-warm-xs">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-ink">{{ $request->user->name }}</p>
                        <p class="text-xs text-muted">{{ $request->user->email }} &middot; mengajukan {{ $request->created_at->diffForHumans() }}</p>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            wire:click="approve('{{ $request->id }}')"
                            wire:confirm="Yakin approve akses Mode Eksekusi untuk {{ $request->user->name }}?"
                            class="rounded-control bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90"
                        >
                            Approve
                        </button>
                        <button
                            type="button"
                            wire:click="openReject('{{ $request->id }}')"
                            class="rounded-control border border-danger/40 px-3 py-1.5 text-xs text-danger hover:bg-danger-soft"
                        >
                            Reject
                        </button>
                    </div>
                </div>

                @if ($openRejectForm === $request->id)
                    <div class="mt-3 rounded-lg border border-muted/20 bg-surface/60 p-3">
                        <label for="reject-note-{{ $request->id }}" class="text-xs font-medium text-ink">Alasan penolakan <span class="font-normal text-muted">(opsional, tapi disarankan)</span></label>
                        <textarea
                            id="reject-note-{{ $request->id }}"
                            wire:model="rejectNotes.{{ $request->id }}"
                            rows="2"
                            class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                            placeholder="Contoh: belum cukup aktif di Eksplorasi, coba lagi bulan depan."
                        ></textarea>
                        <div class="mt-2 flex gap-2">
                            <button
                                type="button"
                                wire:click="reject('{{ $request->id }}')"
                                wire:confirm="Yakin tolak permintaan {{ $request->user->name }}?"
                                class="rounded-control bg-danger px-3 py-1.5 text-xs font-medium text-white hover:bg-danger/90"
                            >
                                Tolak Permintaan
                            </button>
                            <button type="button" wire:click="cancelReject" class="rounded-control border border-muted/40 px-3 py-1.5 text-xs text-ink hover:border-ink">
                                Batal
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <p class="rounded-card border border-muted/20 bg-white p-4 text-sm text-muted shadow-warm-xs">Tidak ada permintaan yang menunggu keputusan.</p>
        @endforelse
    </div>
</div>
