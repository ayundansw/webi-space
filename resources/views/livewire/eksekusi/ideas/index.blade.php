<div>
    <div class="flex items-center justify-between">
        <h1 class="font-display text-2xl font-bold text-ink">Project Ideas</h1>
        <a href="{{ url('/eksekusi/ideas/create') }}" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
            + Usulkan Ide
        </a>
    </div>

    <div class="mt-4 flex gap-2 text-sm">
        @foreach (['draft' => 'Draft', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
            <button
                wire:click="$set('statusFilter', '{{ $value }}')"
                class="rounded-lg border px-3 py-1.5 {{ $statusFilter === $value ? 'border-ink bg-ink text-white' : 'border-muted/40 text-ink' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="mt-6 divide-y divide-muted/25 border border-muted/25 rounded-xl">
        @forelse ($ideas as $idea)
            <div class="p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-medium text-ink">{{ $idea->title }}</p>
                        <p class="mt-1 text-sm text-muted">Diusulkan oleh {{ $idea->proposer->name }}</p>
                        <p class="mt-2 text-sm text-ink">{{ $idea->description }}</p>
                        <p class="mt-1 text-sm text-muted">Tujuan: {{ $idea->purpose }}</p>

                        @if ($idea->status === 'rejected')
                            <p class="mt-2 text-sm text-red-600">Alasan penolakan: {{ $idea->rejection_reason }}</p>
                        @endif
                    </div>

                    @if ($idea->status === 'draft' && auth()->user()->role === 'admin')
                        <div class="flex flex-col items-end gap-2" x-data="{ rejecting: false }">
                            <a href="{{ url('/eksekusi/ideas/'.$idea->id.'/approve') }}" class="rounded-lg bg-ink px-3 py-1.5 text-sm font-medium text-white hover:bg-ink/90">
                                Approve
                            </a>
                            <button type="button" @click="rejecting = !rejecting" class="rounded-lg border border-muted/40 px-3 py-1.5 text-sm text-ink hover:border-ink">
                                Tolak
                            </button>

                            <div x-show="rejecting" class="w-64">
                                <textarea wire:model="rejectReasons.{{ $idea->id }}" rows="2" placeholder="Alasan penolakan (wajib)" class="w-full rounded-lg border border-muted/40 px-2 py-1.5 text-sm focus:border-accent focus:outline-none"></textarea>
                                @error('rejectReasons.'.$idea->id) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                <button wire:click="reject('{{ $idea->id }}')" type="button" class="mt-1 rounded-lg bg-ink px-3 py-1 text-xs font-medium text-white hover:bg-ink/90">
                                    Kirim Penolakan
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="p-4 text-sm text-muted">Belum ada ide dengan status ini.</p>
        @endforelse
    </div>
</div>
