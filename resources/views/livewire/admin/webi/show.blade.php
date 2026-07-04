<div>
    <a href="{{ url('/admin/webi') }}" class="text-sm text-muted hover:text-ink">&larr; Kembali</a>
    <h1 class="mt-2 font-display text-2xl font-bold text-ink">Log Percakapan: {{ $user->name }}</h1>

    <div class="mt-6 space-y-3">
        @forelse ($messages as $message)
            <div class="rounded-lg border {{ $message->guardrailFlags->isNotEmpty() ? 'border-red-200 bg-red-50' : 'border-muted/25' }} p-3 text-sm">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-ink">{{ $message->sender === 'user' ? $user->name : 'WEBI' }}</span>
                    <span class="font-mono text-xs text-muted">
                        {{ $message->created_at->format('d M Y H:i') }}
                        @if ($message->unitContext) &middot; {{ $message->unitContext->title }} @endif
                        @if ($message->voice_mode) &middot; suara @endif
                    </span>
                </div>
                <p class="mt-1 whitespace-pre-line text-ink">{{ $message->content }}</p>

                @if ($message->guardrailFlags->isNotEmpty())
                    <div class="mt-2 space-y-1">
                        @foreach ($message->guardrailFlags as $flag)
                            <p class="text-xs font-medium text-red-700">
                                Flag: {{ $flag->flag_type }}
                                @if ($flag->unit)
                                    &middot; unit {{ $flag->unit->title }}
                                @endif
                            </p>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <p class="text-sm text-muted">Belum ada percakapan.</p>
        @endforelse
    </div>
</div>
