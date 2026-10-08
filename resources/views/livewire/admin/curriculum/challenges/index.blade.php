@push('breadcrumb-actions')
    <a href="{{ url('/admin/curriculum/challenges/create') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control bg-ink px-4 py-2 text-sm font-medium text-white shadow-warm-xs hover:bg-ink/90">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M12 5v14" /><path d="M5 12h14" />
        </svg>
        Buat Challenge Baru
    </a>
@endpush

<div>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Kelola Praktik: Challenge</h1>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-muted/25 bg-green-50 p-4 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Cari judul challenge..."
            class="w-full rounded-control border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none sm:flex-1"
        >
        <select wire:model.live="levelFilter" class="w-full rounded-control border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none sm:w-40">
            <option value="">Semua Level</option>
            <option value="low">Low</option>
            <option value="mid">Mid</option>
            <option value="high">High</option>
        </select>
        <select wire:model.live="statusFilter" class="w-full rounded-control border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none sm:w-40">
            <option value="">Semua Status</option>
            <option value="published">Published</option>
            <option value="draft">Draft</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-muted/25">
        <table class="w-full table-fixed text-left text-sm">
            <thead class="border-b border-muted/25 bg-accent-soft/20 text-muted">
                <tr>
                    <th class="w-[6%] px-3 py-2.5 font-medium">No</th>
                    <th class="w-[32%] px-3 py-2.5 font-medium">Judul</th>
                    <th class="w-[12%] px-3 py-2.5 font-medium">Level</th>
                    <th class="w-[10%] px-3 py-2.5 font-medium">Poin</th>
                    <th class="w-[15%] px-3 py-2.5 font-medium">Status</th>
                    <th class="w-[13%] px-3 py-2.5 font-medium">Jumlah Step</th>
                    <th class="w-[12%] px-3 py-2.5 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($challenges as $challenge)
                    <tr class="border-b border-muted/15 last:border-0">
                        <td class="px-3 py-2.5 font-mono text-xs text-muted">{{ $loop->iteration }}</td>
                        <td class="truncate px-3 py-2.5 text-ink" title="{{ $challenge->title }}">{{ $challenge->title }}</td>
                        <td class="px-3 py-2.5 text-ink">
                            {{ match ($challenge->level) { 'low' => 'Low', 'mid' => 'Mid', 'high' => 'High' } }}
                        </td>
                        <td class="px-3 py-2.5 text-ink">{{ $challenge->points_reward }}</td>
                        <td class="px-3 py-2.5">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs {{ $challenge->status === 'published' ? 'bg-success-soft text-success' : 'bg-muted/15 text-muted' }}">
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $challenge->status === 'published' ? 'bg-success' : 'bg-muted' }}"></span>
                                {{ $challenge->status === 'published' ? 'Published' : 'Draft' }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5 text-ink">{{ $challenge->challenge_steps_count }}</td>
                        <td class="px-3 py-2.5 text-right">
                            <a href="{{ url('/admin/curriculum/challenges/'.$challenge->id.'/edit') }}" class="text-sm text-ink underline hover:text-accent">
                                Kelola
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-center text-sm text-muted">Tidak ada challenge ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
