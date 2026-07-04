<div>
    <h1 class="font-display text-2xl font-bold text-ink">Log Percakapan WEBI</h1>
    <p class="mt-1 text-sm text-muted">Ringkasan per anggota eksplorasi untuk monitoring pola pertanyaan dan guardrail.</p>

    <div class="mt-6 overflow-x-auto rounded-xl border border-muted/25">
        <table class="w-full text-sm">
            <thead class="border-b border-muted/25 bg-muted/5 text-left text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-2">Nama</th>
                    <th class="px-4 py-2">Jumlah Pesan</th>
                    <th class="px-4 py-2">Pesan Terakhir</th>
                    <th class="px-4 py-2">Guardrail Flag</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($summaries as $summary)
                    <tr class="border-b border-muted/10">
                        <td class="px-4 py-2 text-ink">{{ $summary['user']->name }}</td>
                        <td class="px-4 py-2 font-mono text-ink">{{ $summary['message_count'] }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-muted">{{ $summary['last_message_at']?->format('d M Y H:i') ?? '-' }}</td>
                        <td class="px-4 py-2 font-mono {{ $summary['flag_count'] > 0 ? 'text-red-600' : 'text-muted' }}">{{ $summary['flag_count'] }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ url('/admin/webi/'.$summary['user']->id) }}" class="text-ink hover:text-accent">Lihat log &rarr;</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-muted">Belum ada anggota eksplorasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
