<div class="overflow-x-auto rounded-xl border border-muted/25">
    <table class="w-full text-left text-sm">
        @if (! empty($data['headers']))
            <thead class="border-b border-muted/25 bg-accent-soft/20 text-muted">
                <tr>
                    @foreach ($data['headers'] as $header)
                        <th class="px-4 py-3 font-medium text-ink">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            @foreach ($data['rows'] ?? [] as $row)
                <tr class="border-b border-muted/15 last:border-0">
                    @foreach ($row as $cell)
                        <td class="px-4 py-3 text-ink">{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
