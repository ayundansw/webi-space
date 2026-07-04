<div>
    <h1 class="font-display text-2xl font-bold text-ink">Referensi Belajar</h1>
    <p class="mt-1 text-sm text-muted">Kumpulan sumber tambahan per modul. Bisa kamu akses kapan saja, tidak harus menunggu sampai di modulnya.</p>

    <div class="mt-6 space-y-6">
        @foreach ($modules as $module)
            <div class="rounded-xl border border-muted/25 p-5">
                <h2 class="font-display text-base font-semibold text-ink">Modul {{ $module->order_number }}: {{ $module->title }}</h2>

                @if ($module->learningResources->isEmpty())
                    <p class="mt-2 text-sm text-muted">Belum ada referensi tambahan untuk modul ini.</p>
                @else
                    <ul class="mt-3 space-y-2">
                        @foreach ($module->learningResources as $resource)
                            <li>
                                <a href="{{ $resource->url }}" target="_blank" rel="noopener" class="text-sm text-ink underline hover:text-accent">
                                    {{ $resource->title }}
                                </a>
                                <span class="font-mono text-xs text-muted"> &middot; {{ $resource->source_name }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
</div>
