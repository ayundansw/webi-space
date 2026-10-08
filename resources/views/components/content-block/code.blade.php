<div class="overflow-x-auto rounded-xl border border-muted/25 bg-ink p-4">
    @if (! empty($data['language']))
        <p class="font-mono mb-2 text-xs uppercase tracking-wide text-white/50">{{ $data['language'] }}</p>
    @endif
    <pre class="font-mono text-sm text-white"><code>{{ $data['code'] ?? '' }}</code></pre>
</div>
