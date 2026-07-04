<div>
    <div class="flex items-start justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink">{{ $project->title }}</h1>
            <p class="mt-1 text-sm text-muted">{{ ucfirst($project->project_type) }} &middot; {{ $project->start_date->format('d M Y') }} &ndash; {{ $project->target_end_date->format('d M Y') }}</p>
        </div>
        <span class="rounded-lg border border-muted/40 px-3 py-1.5 text-sm font-mono uppercase text-ink">{{ $project->status }}</span>
    </div>

    <div class="mt-4 rounded-xl border border-muted/25 p-4 text-sm text-ink">
        <p>{{ $project->description }}</p>
        <p class="mt-2 text-muted">Tujuan: {{ $project->objective }}</p>
        <p class="mt-2 font-mono">{{ $project->progressPercentage() }}% task selesai</p>
    </div>

    <div class="mt-4 flex flex-wrap gap-3">
        <a href="{{ url('/eksekusi/projects/'.$project->id.'/board') }}" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
            Kanban Board
        </a>

        @if (auth()->user()->role === 'admin')
            @if ($project->status === 'active')
                <button wire:click="changeStatus('on_hold')" class="rounded-lg border border-muted/40 px-4 py-2 text-sm text-ink hover:border-ink">Pause (On Hold)</button>
                <button wire:click="changeStatus('completed')" class="rounded-lg border border-muted/40 px-4 py-2 text-sm text-ink hover:border-ink">Tandai Completed</button>
            @elseif ($project->status === 'on_hold')
                <button wire:click="changeStatus('active')" class="rounded-lg border border-muted/40 px-4 py-2 text-sm text-ink hover:border-ink">Resume (Active)</button>
            @elseif ($project->status === 'completed')
                <button wire:click="changeStatus('archived')" class="rounded-lg border border-muted/40 px-4 py-2 text-sm text-ink hover:border-ink">Archive</button>
            @endif
        @endif
    </div>
    @error('status') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

    <div class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-2">
        <div>
            <h2 class="font-display text-lg font-bold text-ink">Milestone</h2>
            <div class="mt-3 space-y-3">
                @forelse ($milestones as $milestone)
                    <div class="rounded-lg border border-muted/25 p-3">
                        <div class="flex items-center justify-between">
                            <p class="font-medium text-ink">{{ $milestone->title }}</p>
                            <span class="font-mono text-xs text-muted">{{ $milestone->progressPercentage() }}%</span>
                        </div>
                        @if ($milestone->description)
                            <p class="mt-1 text-sm text-muted">{{ $milestone->description }}</p>
                        @endif
                        <p class="mt-1 text-xs text-muted">Target: {{ $milestone->target_date->format('d M Y') }}</p>
                        <div class="mt-2 h-1.5 rounded-full bg-muted/20">
                            <div class="h-1.5 rounded-full bg-accent" style="width: {{ $milestone->progressPercentage() }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-muted">Belum ada milestone.</p>
                @endforelse
            </div>

            @if (auth()->user()->role === 'admin' && $project->status !== 'archived')
                <form wire:submit="addMilestone" class="mt-4 space-y-2 rounded-lg border border-muted/25 p-3">
                    <input type="text" wire:model="milestoneTitle" placeholder="Judul milestone" class="w-full rounded-lg border border-muted/40 px-3 py-1.5 text-sm focus:border-accent focus:outline-none">
                    @error('milestoneTitle') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <textarea wire:model="milestoneDescription" placeholder="Deskripsi (opsional)" rows="2" class="w-full rounded-lg border border-muted/40 px-3 py-1.5 text-sm focus:border-accent focus:outline-none"></textarea>
                    <input type="date" wire:model="milestoneTargetDate" class="w-full rounded-lg border border-muted/40 px-3 py-1.5 text-sm focus:border-accent focus:outline-none">
                    @error('milestoneTargetDate') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <button type="submit" class="rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">+ Tambah Milestone</button>
                </form>
            @endif
        </div>

        <div>
            <h2 class="font-display text-lg font-bold text-ink">Anggota Tim</h2>
            <div class="mt-3 space-y-2">
                @forelse ($members as $member)
                    <div class="flex items-center justify-between rounded-lg border border-muted/25 p-3">
                        <span class="text-sm text-ink">{{ $member->user->name }}</span>
                        @if (auth()->user()->role === 'admin')
                            <button wire:click="removeMember('{{ $member->user_id }}')" wire:confirm="Keluarkan {{ $member->user->name }} dari proyek?" class="text-xs text-red-600 hover:underline">Keluarkan</button>
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
                @error('newMemberId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @endif
        </div>
    </div>
</div>
