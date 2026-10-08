@push('breadcrumb-actions')
    <a href="{{ url('/admin/curriculum/units') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control border border-muted/40 px-4 py-2 text-sm font-medium text-ink shadow-warm-xs hover:border-ink hover:bg-surface-alt">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" />
        </svg>
        Lihat Unit
    </a>
    <a href="{{ url('/admin/curriculum/modules/create') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control bg-ink px-4 py-2 text-sm font-medium text-white shadow-warm-xs hover:bg-ink/90">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M12 5v14" /><path d="M5 12h14" />
        </svg>
        Buat Modul Baru
    </a>
@endpush

<div>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Kelola Kurikulum: Modul</h1>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-muted/25 bg-green-50 p-4 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-muted/25">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-muted/25 bg-accent-soft/20 text-muted">
                <tr>
                    <th class="px-4 py-3 font-medium">Urutan</th>
                    <th class="px-4 py-3 font-medium">Judul</th>
                    <th class="px-4 py-3 font-medium">Level</th>
                    <th class="px-4 py-3 font-medium">Jumlah Unit</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($modules as $module)
                    <tr class="border-b border-muted/15 last:border-0">
                        <td class="px-4 py-3 text-ink">{{ $module->order_number }}</td>
                        <td class="px-4 py-3 text-ink">{{ $module->title }}</td>
                        <td class="px-4 py-3 text-ink">{{ $module->level_number }}</td>
                        <td class="px-4 py-3 text-ink">{{ $module->units_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ url('/admin/curriculum/modules/'.$module->id.'/edit') }}" class="text-sm text-ink underline hover:text-accent">
                                Kelola
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-muted">Belum ada modul.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
