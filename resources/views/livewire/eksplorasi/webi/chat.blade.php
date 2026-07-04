<div
    x-data="webiVoice()"
    x-init="init()"
    @webi-reply-ready.window="speak($event.detail.text)"
>
    <div class="flex items-center justify-between">
        <h1 class="font-display text-2xl font-bold text-ink">Chat WEBI</h1>

        <template x-if="voiceSupported">
            <label class="flex items-center gap-2 text-xs text-muted">
                <input type="checkbox" x-model="voiceMode" @change="$wire.set('voiceMode', voiceMode)">
                Mode suara
            </label>
        </template>
    </div>

    {{--
        docs/PRD.md 5.1: persistent, cannot be dismissed — deliberately no close
        button or JS toggle here. Shown every time the chat is opened, not just
        the first time, since the doc frames it as a standing notice rather than
        a one-time acknowledgment.
    --}}
    <div class="mt-3 rounded-lg border border-muted/30 bg-accent-soft/20 p-3 text-xs text-ink">
        Percakapanmu dengan WEBI bisa diakses PIC untuk membantu memahami kesulitan belajar yang umum dialami anggota.
    </div>

    <div class="mt-6 space-y-3 rounded-xl border border-muted/25 p-4" style="max-height: 28rem; overflow-y: auto;">
        @forelse ($messages as $item)
            @php($message = $item->model)
            <div class="flex {{ $message->sender === 'user' ? 'justify-end' : 'justify-start' }}">
                <div
                    class="max-w-[80%] rounded-xl px-4 py-2 text-sm {{ $message->sender === 'user' ? 'bg-ink text-white' : 'bg-accent-soft/40 text-ink' }}
                        [&_p]:mb-2 [&_p:last-child]:mb-0 [&_strong]:font-semibold [&_em]:italic
                        [&_code]:rounded [&_code]:bg-black/10 [&_code]:px-1 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-xs
                        [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-black/10 [&_pre]:p-2 [&_pre_code]:bg-transparent [&_pre_code]:p-0
                        [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5
                        [&_a]:underline [&_a]:underline-offset-2"
                >
                    {!! $item->safeHtml !!}
                    <p class="mt-1 text-xs {{ $message->sender === 'user' ? 'text-white/60' : 'text-muted' }}">{{ $message->created_at->format('d M Y H:i') }}</p>
                </div>
            </div>

            @if ($item->recommendedUnit)
                <div class="flex justify-start">
                    <a
                        href="{{ url('/eksplorasi/unit/'.$item->recommendedUnit->id) }}"
                        class="block max-w-[80%] rounded-xl border border-muted/25 p-3 text-sm hover:bg-accent-soft/20"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-accent">Rekomendasi Unit</p>
                        <p class="mt-1 font-medium text-ink">{{ $item->recommendedUnit->title }}</p>
                        <p class="mt-1 text-xs text-muted">Modul {{ $item->recommendedUnit->module->order_number }}: {{ $item->recommendedUnit->module->title }} &rarr;</p>
                    </a>
                </div>
            @elseif ($item->recommendedModule)
                <div class="flex justify-start">
                    <a
                        href="{{ url('/eksplorasi/kurikulum') }}"
                        class="block max-w-[80%] rounded-xl border border-muted/25 p-3 text-sm hover:bg-accent-soft/20"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-accent">Rekomendasi Modul</p>
                        <p class="mt-1 font-medium text-ink">Modul {{ $item->recommendedModule->order_number }}: {{ $item->recommendedModule->title }}</p>
                        <p class="mt-1 text-xs text-muted">Lihat di Peta Kurikulum &rarr;</p>
                    </a>
                </div>
            @endif
        @empty
            <p class="text-sm text-muted">Belum ada percakapan. Tanya apa aja soal materi kurikulum atau cara pakai WEBI-SPACE.</p>
        @endforelse
    </div>

    @if ($errorMessage)
        <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ $errorMessage }}
        </div>
    @endif

    <form wire:submit="sendMessage" class="mt-4 flex gap-2">
        <input
            type="text"
            wire:model="messageText"
            @webi-message-sent.window="$el.value = ''"
            placeholder="Tanya WEBI di sini..."
            class="flex-1 rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            autocomplete="off"
        >

        <template x-if="voiceSupported">
            <button
                type="button"
                @click="toggleListening()"
                :class="listening ? 'border-accent text-accent' : 'border-muted/40 text-ink'"
                class="rounded-lg border px-3 py-2 text-sm hover:border-ink"
                title="Ngomong ke WEBI"
            >
                <span x-show="!listening">&#127908;</span>
                <span x-show="listening">&#9679;</span>
            </button>
        </template>

        <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60">
            Kirim
        </button>
    </form>
    @error('messageText') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

    <p class="mt-2 text-xs text-muted" x-show="voiceSupported && voiceMode">
        Mode suara aktif. Bicara lewat tombol mikrofon, transkrip akan muncul di kotak teks untuk kamu cek dulu sebelum dikirim.
    </p>
    <p class="mt-2 text-xs text-muted" x-show="!voiceSupported">
        Browser ini belum mendukung fitur suara, tapi tenang, chat teks tetap berfungsi penuh seperti biasa.
    </p>
</div>

<script>
    function webiVoice() {
        return {
            voiceSupported: false,
            voiceMode: false,
            listening: false,
            recognition: null,

            init() {
                // Mandatory fallback rule (docs/spesifikasi-webi.md 6, task 2.5
                // instructions): feature-detect on the client, hide/disable the
                // mic entirely when unsupported so text chat stays the only
                // path — never let a broken mic button block the user.
                const SpeechRecognitionApi = window.SpeechRecognition || window.webkitSpeechRecognition;
                const hasTts = 'speechSynthesis' in window;
                this.voiceSupported = Boolean(SpeechRecognitionApi) && hasTts;

                if (!this.voiceSupported) {
                    return;
                }

                this.recognition = new SpeechRecognitionApi();
                this.recognition.lang = 'id-ID';
                this.recognition.interimResults = false;
                this.recognition.maxAlternatives = 1;

                this.recognition.addEventListener('result', (event) => {
                    const transcript = event.results[0][0].transcript;
                    // Shown in the same text input for user verification before
                    // sending, per docs/spesifikasi-webi.md 6.4 — never auto-sent.
                    this.$wire.set('messageText', transcript);
                });

                this.recognition.addEventListener('end', () => {
                    this.listening = false;
                });
            },

            toggleListening() {
                if (!this.voiceSupported) {
                    return;
                }

                if (this.listening) {
                    this.recognition.stop();
                    this.listening = false;
                    return;
                }

                this.listening = true;
                this.recognition.start();
            },

            speak(text) {
                if (!this.voiceSupported || !this.voiceMode) {
                    return;
                }

                // The server already sends plain text with markdown stripped
                // (App\Services\Webi\MessageRenderer::toPlainText()) — this is
                // a defensive second layer in case that's ever bypassed, same
                // two-layer pattern as the guardrail. Bug fixed 2026-07-04:
                // TTS used to read out "asterisk asterisk" etc. for **bold**
                // and similar markdown symbols verbatim.
                const cleaned = text
                    .replace(/```[a-zA-Z0-9]*\n?([\s\S]*?)```/g, '$1')
                    .replace(/`([^`]*)`/g, '$1')
                    .replace(/(\*\*|__)(.*?)\1/g, '$2')
                    .replace(/(\*|_)(.*?)\1/g, '$2')
                    .replace(/^#{1,6}\s+/gm, '')
                    .replace(/^>\s?/gm, '')
                    .replace(/^[-*+]\s+/gm, '')
                    .replace(/^\d+\.\s+/gm, '')
                    .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1');

                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(cleaned);
                utterance.lang = 'id-ID';
                window.speechSynthesis.speak(utterance);
            },
        };
    }
</script>
