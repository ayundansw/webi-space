@php
    $typeLabels = [
        'multiple_choice' => 'Pilihan Ganda',
        'matching' => 'Mencocokkan',
        'ordering' => 'Mengurutkan',
        'essay' => 'Esai',
        'practice' => 'Praktik',
    ];
@endphp

@push('breadcrumb-actions')
    <a href="{{ url('/admin/curriculum/units/'.$unit->id.'/edit') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control border border-muted/40 px-4 py-2 text-sm font-medium text-ink shadow-warm-xs hover:border-ink hover:bg-surface-alt">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
        </svg>
        Kembali ke Unit
    </a>
    <a href="{{ url('/admin/curriculum/units/'.$unit->id.'/evaluations/create') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control bg-ink px-4 py-2 text-sm font-medium text-white shadow-warm-xs hover:bg-ink/90">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M12 5v14" /><path d="M5 12h14" />
        </svg>
        Tambah Soal
    </a>
@endpush

<div>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Kelola Evaluasi</h1>
        <p class="mt-1 text-sm text-muted">{{ $unit->title }}</p>
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
                    <th class="px-4 py-3 font-medium">Tipe</th>
                    <th class="px-4 py-3 font-medium">Pertanyaan</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($evaluations as $evaluation)
                    <tr class="border-b border-muted/15 last:border-0">
                        <td class="px-4 py-3 text-ink">{{ $evaluation->sort_order }}</td>
                        <td class="px-4 py-3 text-ink">{{ $typeLabels[$evaluation->question_type] ?? $evaluation->question_type }}</td>
                        <td class="px-4 py-3 text-ink">{{ \Illuminate\Support\Str::limit($evaluation->question_text, 80) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ url('/admin/curriculum/units/'.$unit->id.'/evaluations/'.$evaluation->id.'/edit') }}" class="text-sm text-ink underline hover:text-accent">Kelola</a>
                            <button
                                type="button"
                                wire:click="delete('{{ $evaluation->id }}')"
                                wire:confirm="Yakin ingin menghapus soal ini?"
                                class="ml-3 text-sm text-red-600 underline hover:text-red-700"
                            >Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-muted">Belum ada soal evaluasi untuk unit ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
