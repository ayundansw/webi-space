<div>
    <livewire:eksekusi.projects.project-header :project="$project" />

    <x-eksekusi.project-tabs :project="$project" active="anggota" />

    <div class="mt-6 max-w-lg">
        <h2 class="font-display text-lg font-bold text-ink">Anggota Tim</h2>
        <div class="mt-3 space-y-2">
            @forelse ($members as $member)
                <div class="flex items-center justify-between rounded-lg border border-muted/25 p-3">
                    <span class="text-sm text-ink">{{ $member->user->name }}</span>
                    @if (auth()->user()->role === 'admin')
                        <button wire:click="removeMember('{{ $member->user_id }}')" wire:confirm="Keluarkan {{ $member->user->name }} dari proyek?" class="text-xs text-danger hover:underline">Keluarkan</button>
                    @endif
                </div>
            @empty
                <p class="text-sm text-muted">Belum ada anggota.</p>
            @endforelse
        </div>

        @if (auth()->user()->role === 'admin' && $project->status !== 'archived')
            <form wire:submit="addMember" class="mt-4 flex gap-2">
                <select wire:model="newMemberId" class="flex-1 rounded-lg border border-muted/40 px-3 py-1.5 text-sm focus:border-accent focus:outline-none">
                    <option value="">Pilih anggota eksekusi...</option>
                    @foreach ($availableMembers as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">Tambah</button>
            </form>
            @error('newMemberId') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        @endif
    </div>
</div>
