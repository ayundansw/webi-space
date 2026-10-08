{{--
    Diekstrak verbatim dari chat.blade.php (Redesign WEBI Chat halaman
    penuh) -- isi PERSIS sama, cuma dipindah ke partial supaya dipakai
    ULANG oleh dua tata letak berbeda (panel kontekstual di halaman
    Materi & halaman chat penuh yang baru) tanpa duplikasi ~110 baris.
    Variabel ($messages) otomatis tersedia lewat scope @include Blade,
    tidak perlu di-pass eksplisit.
--}}
@forelse ($messages as $item)
    @php($message = $item->model)
    <div class="space-y-1">
        <div class="flex items-end gap-2 {{ $message->sender === 'user' ? 'justify-end' : 'justify-start' }}">
            @unless ($message->sender === 'user')
                {{--
                    Putaran perbaikan Aye: badge "profil" WEBI di bubble
                    chat diganti maskot resmi (Boxy Blocky, komponen yang
                    sama dipakai di seluruh aplikasi -- Dashboard, WEBI AI
                    card, dst) menggantikan ikon sparkle generik lama.
                    Berlaku universal (panel kontekstual & halaman penuh,
                    partial ini dipakai keduanya) supaya identitas WEBI
                    konsisten di manapun dia muncul.
                --}}
                <x-brand.mascot variant="chat-icon" size="sm" class="shrink-0" />
            @endunless

            <div
                class="max-w-[75%] rounded-xl px-4 py-2.5 text-sm {{ $message->sender === 'user' ? 'bg-ink text-white' : 'bg-accent-soft/40 text-ink' }}
                    [&_p]:mb-2 [&_p:last-child]:mb-0 [&_strong]:font-semibold [&_em]:italic
                    [&_code]:rounded [&_code]:bg-black/10 [&_code]:px-1 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-xs
                    [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-black/10 [&_pre]:p-2 [&_pre_code]:bg-transparent [&_pre_code]:p-0
                    [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5
                    [&_a]:underline [&_a]:underline-offset-2"
            >
                {!! $item->safeHtml !!}
            </div>
        </div>

        <p class="mt-1 font-mono text-[10px] text-muted {{ $message->sender === 'user' ? 'text-right' : 'ml-10' }}">
            {{ $message->created_at->format('d M Y H:i') }}
        </p>
    </div>

    @if ($item->recommendedUnit)
        <div class="flex justify-start pl-10">
            <a
                href="{{ url('/eksplorasi/unit/'.$item->recommendedUnit->id) }}"
                wire:navigate
                class="block max-w-[75%] rounded-xl border border-muted/25 p-3 text-sm hover:bg-accent-soft/20"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-accent">Rekomendasi Unit</p>
                <p class="mt-1 font-medium text-ink">{{ $item->recommendedUnit->title }}</p>
                <p class="mt-1 text-xs text-muted">Modul {{ $item->recommendedUnit->module->order_number }}: {{ $item->recommendedUnit->module->title }} &rarr;</p>
            </a>
        </div>
    @elseif ($item->recommendedModule)
        <div class="flex justify-start pl-10">
            <a
                href="{{ url('/eksplorasi/kurikulum') }}"
                wire:navigate
                class="block max-w-[75%] rounded-xl border border-muted/25 p-3 text-sm hover:bg-accent-soft/20"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-accent">Rekomendasi Modul</p>
                <p class="mt-1 font-medium text-ink">Modul {{ $item->recommendedModule->order_number }}: {{ $item->recommendedModule->title }}</p>
                <p class="mt-1 text-xs text-muted">Lihat di Peta Kurikulum &rarr;</p>
            </a>
        </div>
    @endif
@empty
    <div class="flex h-full flex-col items-center justify-center gap-4 py-6 text-center">
        <x-brand.mascot variant="chat-icon" size="md" />
        <div class="space-y-1">
            <p class="text-sm font-medium text-ink">Halo! Aku WEBI, teman belajarmu.</p>
            <p class="mt-1 text-sm text-muted">Tanya apa aja soal materi kurikulum atau cara pakai WEBI-SPACE.</p>
        </div>
        <div class="flex flex-wrap justify-center gap-2">
            @foreach (['Apa itu Software Development?', 'Gimana cara kerja HTML dan CSS?', 'Aku harus mulai dari mana?'] as $example)
                <button
                    type="button"
                    @click="$wire.set('messageText', @js($example)); $nextTick(() => $refs.chatInput.focus())"
                    class="rounded-full border border-muted/40 px-3 py-1.5 text-xs text-ink hover:border-accent hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                >
                    {{ $example }}
                </button>
            @endforeach
        </div>
    </div>
@endforelse

{{--
    Optimistic bubble: shows the just-typed text INSTANTLY, before the
    server round-trip (which includes the slow Gemini call) finishes.
    Pure client-side state (`optimisticMessage` in webiVoice(), not a
    Livewire property) — the real message list above is still the only
    source of truth. Cleared the moment the request resolves WITHOUT
    an error, at which point the real (server-rendered) bubble has
    already taken its place in the list above, so there's no
    duplicate-then-gone flash. Kept visible if the request fails (rate
    limit / Gemini error) so what the user typed doesn't just vanish —
    see submitMessage() in the script below.
--}}
<div x-show="optimisticMessage" x-cloak class="flex items-end gap-2 justify-end">
    <div class="max-w-[75%] rounded-xl bg-ink px-4 py-2.5 text-sm text-white">
        <p x-text="optimisticMessage" class="whitespace-pre-wrap"></p>
    </div>
</div>

{{-- Typing indicator: purely Livewire's built-in wire:loading, scoped
     to the sendMessage action — no new loading-state mechanism. --}}
<div wire:loading wire:target="sendMessage" class="flex items-end gap-2 justify-start">
    <x-brand.mascot variant="chat-icon" size="sm" class="shrink-0" />
    <div class="flex items-center gap-1 rounded-xl bg-accent-soft/40 px-4 py-3">
        <span class="text-xs text-muted mr-1">WEBI sedang mengetik</span>
        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-ink/50"></span>
        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-ink/50" style="animation-delay: 0.15s"></span>
        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-ink/50" style="animation-delay: 0.3s"></span>
    </div>
</div>
