<div class="max-w-xl">
    <h1 class="font-display text-2xl font-bold text-ink">Buat Task Baru</h1>
    <p class="mt-1 text-sm text-muted">Proyek: {{ $project->title }}</p>

    <form wire:submit="save" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-ink">Milestone</label>
            <select wire:model="milestoneId" class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                <option value="">Pilih milestone...</option>
                @foreach ($milestones as $milestone)
                    <option value="{{ $milestone->id }}">{{ $milestone->title }}</option>
                @endforeach
            </select>
            @error('milestoneId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink">Judul task</label>
            <input type="text" wire:model="title" placeholder="Buat halaman login" class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink">Deskripsi</label>
            <textarea wire:model="description" rows="3" class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-ink">Prioritas</label>
                <select wire:model="priority" class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-ink">Deadline</label>
                <input type="date" wire:model="deadline" class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                @error('deadline') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-ink">Assignee</label>
            <div class="mt-1 space-y-1">
                @foreach ($members as $member)
                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input type="checkbox" wire:model="assigneeIds" value="{{ $member->user_id }}">
                        <span>{{ $member->user->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
            Buat Task
        </button>
    </form>
</div>
