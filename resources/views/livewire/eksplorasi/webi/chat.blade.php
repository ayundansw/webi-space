@if ($contextUnit)
    {{--
        Panel kontekstual (embedded di halaman Materi, App\Livewire\Eksplorasi\UnitShow)
        -- TIDAK DIUBAH sama sekali oleh redesign halaman chat penuh di
        bawah (task terpisah, sudah selesai sebelumnya). Isi loop
        pesan+form input diekstrak verbatim ke _messages-content.blade.php
        / _input-form.blade.php supaya dipakai ulang tanpa duplikasi, TAPI
        rendered output-nya identik dengan sebelum diekstrak.
    --}}
    <div
        x-data="webiVoice(true)"
        x-init="init()"
        @webi-reply-ready.window="speak($event.detail.text)"
        @webi-message-sent.window="scrollToBottom()"
        class="flex h-full flex-col"
    >
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h1 class="font-display text-2xl font-bold text-ink">Chat WEBI</h1>

            <div class="flex items-center gap-4">
                <template x-if="voiceSupported">
                    <label class="flex items-center gap-2 text-xs text-muted">
                        <input type="checkbox" x-model="voiceMode" @change="$wire.set('voiceMode', voiceMode)">
                        Mode suara
                    </label>
                </template>

                {{-- 2.2.3 (riwayat percakapan): collapsible list of the user's own
                     past sessions, mirroring the x-data/x-show/x-transition/x-cloak
                     pattern already used in notifications/bell.blade.php. --}}
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" class="flex items-center gap-1.5 text-xs text-muted hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent rounded-lg px-1 py-0.5">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                            <path d="M12 8v4l3 2" /><circle cx="12" cy="12" r="9" />
                        </svg>
                        Riwayat ({{ $pastConversations->count() }})
                    </button>

                    <div
                        x-show="open"
                        x-cloak
                        x-transition
                        @click.outside="open = false"
                        class="absolute right-0 z-20 mt-2 max-h-72 w-72 space-y-1.5 overflow-y-auto rounded-xl border border-muted/25 bg-white p-2 shadow-lg"
                    >
                        @forelse ($pastConversations as $item)
                            <a
                                href="{{ url('/eksplorasi/webi/'.$item['conversation']->id) }}"
                                wire:navigate
                                class="block rounded-lg px-3 py-2 text-sm hover:bg-accent-soft/20"
                            >
                                <p class="font-mono text-xs text-muted">{{ $item['conversation']->started_at->format('d M Y, H:i') }}</p>
                                <p class="mt-0.5 truncate text-ink">{{ $item['preview'] }}</p>
                                <p class="mt-0.5 font-mono text-xs text-muted">{{ $item['message_count'] }} pesan</p>
                            </a>
                        @empty
                            <p class="px-3 py-2 text-sm text-muted">Belum ada percakapan lain.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{--
            docs/v_2.0/archive/sumber-konsolidasi/PRD.md 5.1: PENYIMPANGAN SADAR -- notice ini DISEMBUNYIKAN
            di panel kontekstual (dikonfirmasi eksplisit ke Aye, lihat
            CLAUDE.md "Known gaps / backlog dari Fase 3"), cuma tampil di
            halaman chat penuh (cabang @else di bawah).
        --}}

        @if ($isHistoryView)
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-muted/40 bg-muted/5 p-3 text-xs text-ink">
                <span>Kamu sedang melihat percakapan lama ({{ $conversation->started_at->format('d M Y, H:i') }}).</span>
                <a href="{{ url('/eksplorasi/webi') }}" wire:navigate class="font-medium text-ink underline hover:text-accent">
                    Kembali ke percakapan aktif
                </a>
            </div>
        @endif

        <div
            x-ref="messagesBox"
            x-init="scrollToBottom()"
            class="mt-4 flex flex-1 min-h-0 flex-col gap-4 overflow-y-auto rounded-xl border border-muted/25 bg-white p-4"
        >
            @include('livewire.eksplorasi.webi._messages-content')
        </div>

        @if ($errorMessage)
            <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ $errorMessage }}
            </div>
        @endif

        <div class="mt-4">
            @include('livewire.eksplorasi.webi._input-form')
        </div>
    </div>
@else
    {{--
        Redesign halaman WEBI Chat PENUH (task terpisah dari panel
        kontekstual di atas): pola sidebar+chat modern (Claude/Gemini/GPT)
        -- kolom kiri riwayat permanen (bisa minimize jadi strip ikon),
        kolom kanan chat viewport-fixed (cuma bubble pesan yang scroll,
        bukan halaman). Reuse variabel yang SAMA persis
        ($pastConversations, $conversation, $isHistoryView, $messages,
        $errorMessage) -- tidak ada logic baru, murni restrukturisasi
        tampilan + kotak ketik & bubble pesan dipakai ulang lewat
        @include yang sama dengan panel kontekstual.

        Putaran perbaikan Aye (mobile drawer): x-data/x-init dipindah ke
        WRAPPER PALING LUAR (bukan di div flex-row lagi) supaya notice
        footer PIC dan notice mode-suara di ATAS card juga bisa akses
        state Alpine yang sama, sambil tetap SATU root element Livewire
        (wrapper polos ini, flex-row + footer jadi children-nya, bukan
        dua root terpisah -- pola sama dengan fix MultipleRootElementsDetectedException
        di Dashboard Eksplorasi sebelumnya).
    --}}
    <div
        x-data="webiVoice(false)"
        x-init="init()"
        @webi-reply-ready.window="speak($event.detail.text)"
        @webi-message-sent.window="scrollToBottom()"
    >
        {{--
            Putaran perbaikan Aye: notice mode-suara sekarang DI LUAR/DI
            ATAS card chat (bukan di dalam form lagi) -- card tetap
            bersih. _input-form.blade.php TIDAK dihapus paragraf-nya
            (masih dipakai apa adanya oleh panel kontekstual, lihat
            @if($contextUnit) di sana), cuma di halaman penuh ini
            versinya dipindah ke sini.
        --}}
        <template x-if="voiceSupported && voiceMode">
            <p class="mb-3 text-center text-xs text-muted">
                Mode suara aktif. Bicara lewat tombol mikrofon, transkrip akan muncul di kotak teks untuk kamu cek dulu sebelum dikirim.
            </p>
        </template>
        <template x-if="!voiceSupported">
            <p class="mb-3 text-center text-xs text-muted">
                Browser ini belum mendukung fitur suara, tapi tenang, chat teks tetap berfungsi penuh seperti biasa.
            </p>
        </template>

        {{--
            Tinggi viewport-fixed: lg:h-[calc(100vh-11rem)] -- angka 11rem
            SAMA dengan yang sudah diverifikasi aman untuk panel
            kontekstual (lihat unit-show.blade.php). overflow-y-auto
            tetap dipasang di sidebar & kolom chat sebagai jaring
            pengaman, BUKAN di level halaman.
        --}}
        <div class="flex h-auto gap-6 lg:h-[calc(100vh-11rem)]">
            {{--
                Backdrop drawer riwayat, MOBILE SAJA (mobileDrawerOpen,
                state TERPISAH dari sidebarOpen milik desktop -- lihat
                penjelasan lengkap di webiVoice()). Root cause bug
                sebelumnya: dulu backdrop & drawer sama-sama pakai
                `sidebarOpen` yang default TRUE (benar untuk desktop,
                salah untuk mobile -- drawer otomatis "kebuka" tiap
                halaman dimuat di layar sempit).
            --}}
            <div
                x-show="mobileDrawerOpen"
                x-cloak
                x-transition:enter="transition-opacity duration-150"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="mobileDrawerOpen = false"
                class="fixed inset-0 z-30 bg-ink/40 lg:hidden"
            ></div>

            {{--
                SIDEBAR RIWAYAT. Desktop (lg: ke atas): kolom inline
                normal, bisa menyusut jadi strip ikon (w-16) lewat
                sidebarOpen, TIDAK PERNAH hilang total. Mobile (<lg):
                SEPENUHNYA independen dari sidebarOpen -- tersembunyi
                total secara default (max-lg:hidden), muncul sebagai
                drawer overlay slide-dari-kiri (max-lg:fixed ... 80% lebar
                layar) HANYA saat mobileDrawerOpen true. Root cause bug
                lama: dulu satu state (sidebarOpen, default true) dipakai
                untuk KEDUANYA -- di mobile itu bikin sidebar "collapsed"
                (bukan hidden, w-16 max-lg:hidden -> max-lg:hidden menang
                jadi sebenarnya sudah hidden) TAPI trigger button-nya
                sendiri jadi flex SIBLING sejajar card chat (bukan
                overlay), makanya terlihat "kotak kecil terpisah".
            --}}
            {{--
                CATATAN TEKNIS: elemen ini SENGAJA tidak pakai x-show/
                x-transition Alpine untuk state mobile -- x-show men-toggle
                `display:none` lewat inline style yang akan MENIMPA aturan
                CSS breakpoint apa pun (termasuk "tetap tampil di lg:"),
                jadi tidak bisa dipakai untuk elemen yang perilakunya beda
                per breakpoint. Sebagai gantinya: elemen SELALU ada di DOM
                dengan `max-lg:fixed` (posisi tetap, tidak masuk normal
                flow, tidak pernah memicu scroll horizontal halaman), dan
                mobileDrawerOpen murni menggeser lewat CSS transform
                (max-lg:translate-x-0 vs max-lg:-translate-x-full) +
                transition-transform bawaan Tailwind -- pola drawer standar
                yang robust lintas breakpoint.
            --}}
            <div
                :class="{
                    'lg:w-72': sidebarOpen,
                    'lg:w-16': ! sidebarOpen,
                    'max-lg:translate-x-0': mobileDrawerOpen,
                    'max-lg:-translate-x-full': ! mobileDrawerOpen,
                }"
                class="flex shrink-0 flex-col overflow-hidden rounded-2xl border border-muted/20 bg-white shadow-warm-xs transition-all duration-200 max-lg:fixed max-lg:inset-y-0 max-lg:left-0 max-lg:z-40 max-lg:w-4/5 max-lg:max-w-sm max-lg:rounded-l-none max-lg:border-l-0 max-lg:shadow-warm-lg max-lg:transition-transform max-lg:duration-200"
            >
                <div class="flex items-center justify-between gap-2 p-3" :class="! sidebarOpen && 'lg:justify-center'">
                    <p x-show="sidebarOpen" class="truncate text-xs font-semibold uppercase tracking-wide text-caption">Riwayat</p>

                    {{-- Desktop: chevron minimize/maximize kolom inline. --}}
                    <button type="button" @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen.toString()" aria-label="Minimize/expand riwayat" class="hidden shrink-0 rounded-control p-1 text-muted hover:bg-surface-alt hover:text-ink lg:flex">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 transition-transform" :class="! sidebarOpen && 'rotate-180'">
                            <path d="m15 6-6 6 6 6" />
                        </svg>
                    </button>

                    {{-- Mobile: tutup drawer (X), konsep beda dari minimize desktop. --}}
                    <button type="button" @click="mobileDrawerOpen = false" aria-label="Tutup riwayat" class="flex shrink-0 rounded-control p-1 text-muted hover:bg-surface-alt hover:text-ink lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-3 pb-3">
                {{--
                    Putaran perbaikan Aye: "Percakapan Baru" sekarang
                    SELALU mengarah ke sesi baru & bersih -- wire:click
                    memanggil Chat::newConversation() (ChatService::startNewConversationFor(),
                    SELALU create, tidak pernah reuse sesi lama), baru
                    redirect. Bukan <a href> lagi karena butuh memanggil
                    aksi server dulu SEBELUM redirect, bukan cuma navigasi.
                --}}
                <button
                    type="button"
                    wire:click="newConversation"
                    class="flex h-10 w-full items-center justify-center gap-1.5 rounded-control bg-ink text-sm font-medium text-white hover:bg-ink/90"
                    :class="sidebarOpen ? 'px-3' : 'px-0'"
                    title="Percakapan Baru"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0">
                        <path d="M12 5v14" /><path d="M5 12h14" />
                    </svg>
                    <span x-show="sidebarOpen">Percakapan Baru</span>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-1.5 overflow-y-auto px-3 pb-3">
                @forelse ($pastConversations as $item)
                    {{--
                        Bukan cuma saat $isHistoryView (buka riwayat lewat
                        URL eksplisit) -- percakapan default/aktif
                        (isHistoryView=false) juga harus tetap ditandai di
                        sidebar, karena sekarang IKUT muncul di daftar ini
                        (lihat App\Livewire\Eksplorasi\Webi\Chat::render(),
                        exclude cuma berlaku di panel kontekstual).
                    --}}
                    @php($isActiveHistoryItem = $conversation->id === $item['conversation']->id)
                    <a
                        href="{{ url('/eksplorasi/webi/'.$item['conversation']->id) }}"
                        wire:navigate
                        class="block rounded-lg border px-3 py-2 text-sm transition-colors duration-150 {{ $isActiveHistoryItem ? 'border-accent/30 bg-accent-soft/40' : 'border-transparent hover:bg-surface-alt' }}"
                        :class="! sidebarOpen && 'lg:flex lg:justify-center lg:px-0'"
                        title="{{ $item['preview'] }}"
                    >
                        <span x-show="! sidebarOpen" class="hidden h-6 w-6 items-center justify-center rounded-full bg-muted/15 font-mono text-[10px] text-caption lg:flex">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                                <path d="M12 8v4l3 2" /><circle cx="12" cy="12" r="9" />
                            </svg>
                        </span>
                        <span x-show="sidebarOpen">
                            <p class="font-mono text-xs text-caption">{{ $item['conversation']->started_at->format('d M Y, H:i') }}</p>
                            <p class="mt-0.5 truncate text-ink">{{ $item['preview'] }}</p>
                            <p class="mt-0.5 font-mono text-xs text-caption">{{ $item['message_count'] }} pesan</p>
                        </span>
                    </a>
                @empty
                    <p x-show="sidebarOpen" class="px-3 py-2 text-xs text-muted">Belum ada percakapan lain.</p>
                @endforelse
            </div>
        </div>

        {{--
            KOLOM CHAT. Aksen pixel-art di 4 sisi (card-pixel-accent-top/
            bottom/left/right) -- REUSE class yang SAMA sudah dipakai
            card-card Dashboard Eksplorasi (app.css), bukan teknik baru.
        --}}
        <div class="relative flex min-w-0 flex-1 flex-col overflow-hidden rounded-2xl border border-muted/20 bg-white shadow-warm-md">
            <div class="card-pixel-accent-top" aria-hidden="true"></div>
            <div class="card-pixel-accent-bottom" aria-hidden="true"></div>
            <div class="card-pixel-accent-left" aria-hidden="true"></div>
            <div class="card-pixel-accent-right" aria-hidden="true"></div>

            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-muted/10 p-4">
                <div class="flex items-center gap-2">
                    {{--
                        Putaran perbaikan Aye: trigger drawer riwayat mobile
                        SEKARANG ikon kecil di DALAM header card ini (bukan
                        kotak terpisah di luar/di atas card lagi) -- rujukan
                        visual Claude.ai: ikon toggle kecil, bukan tombol
                        besar bertuliskan "Riwayat".
                    --}}
                    <button type="button" @click="mobileDrawerOpen = true" aria-label="Buka riwayat percakapan" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-control border border-muted/40 text-ink hover:border-ink lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M4 6h16" /><path d="M4 12h16" /><path d="M4 18h7" />
                        </svg>
                    </button>
                    <h1 class="font-display text-2xl font-bold text-heading">Chat WEBI</h1>
                </div>
                {{--
                    Putaran perbaikan Aye: toggle Mode Suara sebelumnya
                    checkbox polos, gampang terlewat. Diganti tombol
                    pil kontras + ikon mic, pola SAMA dengan tombol mic di
                    _input-form.blade.php (state aktif = border+bg accent).
                --}}
                <template x-if="voiceSupported">
                    <button
                        type="button"
                        @click="voiceMode = ! voiceMode; $wire.set('voiceMode', voiceMode)"
                        :class="voiceMode ? 'border-accent bg-accent-soft text-ink' : 'border-muted/40 text-muted hover:border-ink hover:text-ink'"
                        class="flex items-center gap-1.5 rounded-control border px-3 py-1.5 text-xs font-medium transition-colors duration-150"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                            <rect x="9" y="2" width="6" height="11" rx="3" /><path d="M5 10a7 7 0 0 0 14 0" /><path d="M12 17v4" /><path d="M9 21h6" />
                        </svg>
                        Mode Suara
                    </button>
                </template>
            </div>

            @if ($isHistoryView)
                <div class="px-4 pt-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-muted/40 bg-muted/5 p-3 text-xs text-ink">
                        <span>Kamu sedang melihat percakapan lama ({{ $conversation->started_at->format('d M Y, H:i') }}).</span>
                        <a href="{{ url('/eksplorasi/webi') }}" wire:navigate class="font-medium text-ink underline hover:text-accent">
                            Kembali ke percakapan aktif
                        </a>
                    </div>
                </div>
            @endif

            <div
                x-ref="messagesBox"
                x-init="scrollToBottom()"
                class="mx-4 mt-3 flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto rounded-xl border border-muted/25 bg-white p-4"
            >
                @include('livewire.eksplorasi.webi._messages-content')
            </div>

            @if ($errorMessage)
                <div class="mx-4 mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ $errorMessage }}
                </div>
            @endif

            <div class="border-t border-muted/10 p-4">
                @include('livewire.eksplorasi.webi._input-form')
            </div>
        </div>
    </div>

    {{--
        Putaran perbaikan Aye: notice PRD 5.1 dipindah BENAR-BENAR keluar
        dari card (footer halaman, sibling dari flex-row di atas, bukan
        anak card lagi) + font dikecilkan, supaya card chat-nya sendiri
        bersih -- tetap persistent/tidak bisa di-dismiss (docs/v_2.0/archive/sumber-konsolidasi/PRD.md 5.1),
        cuma posisi & ukurannya yang berubah. Tetap di dalam wrapper
        x-data yang sama (satu root Livewire, dijelaskan di komentar atas).
    --}}
    <div class="mt-3 text-center">
        <p class="text-[10px] text-caption">
            Percakapanmu dengan WEBI bisa diakses PIC untuk membantu memahami kesulitan belajar yang umum dialami anggota.
        </p>
    </div>
    </div>
@endif

<script>
    function webiVoice(compact = false) {
        return {
            // Putaran perbaikan Aye (ketiga): panel kontekstual (embedded
            // di halaman Materi) memanggil webiVoice(true) -- kotak ketik
            // di sana TIDAK BOLEH tumbuh sama sekali (tetap h-10 lewat
            // CSS), jadi resizeChatInput() di bawah jadi no-op saat compact
            // true. Halaman chat penuh memanggil webiVoice(false),
            // resize-tumbuh-sampai-160px tetap jalan persis seperti semula.
            compact,
            // Redesign halaman chat penuh: state minimize/maximize sidebar
            // riwayat DESKTOP (kolom inline w-72 <-> w-16 strip), murni
            // Alpine lokal, default true. Properti ini tidak dipakai sama
            // sekali di panel kontekstual (compact=true).
            sidebarOpen: true,
            // Putaran perbaikan Aye: state TERPISAH khusus drawer overlay
            // MOBILE, default FALSE (tertutup). Root cause bug sebelumnya:
            // dulu cuma ada satu state (sidebarOpen, default true) dipakai
            // untuk desktop DAN mobile sekaligus -- di mobile itu bikin
            // drawer "otomatis kebuka" tiap halaman dimuat (default true
            // itu benar untuk konsep "kolom desktop selalu ada", tapi
            // salah untuk konsep "overlay mobile harus tertutup sampai
            // eksplisit dibuka"). Dua konsep berbeda butuh dua state
            // berbeda, bukan satu state dipakai ganda.
            mobileDrawerOpen: false,
            voiceSupported: false,
            voiceMode: false,
            listening: false,
            recognition: null,

            // Optimistic UI (4b-1 fix): purely client-side, never a Livewire
            // property. See submitMessage() below for the full flow.
            optimisticMessage: null,
            sending: false,

            init() {
                // Mandatory fallback rule (docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md 6, task 2.5
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
                    // sending, per docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md 6.4 — never auto-sent.
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

            scrollToBottom() {
                this.$nextTick(() => {
                    if (this.$refs.messagesBox) {
                        this.$refs.messagesBox.scrollTop = this.$refs.messagesBox.scrollHeight;
                    }
                });
            },

            // Optimistic UI (4b-1 fix): wire:model="messageText" on the
            // textarea still syncs the typed text into the server-side
            // property exactly as before — this method doesn't replace that,
            // it just ALSO reads the textarea's own DOM value to show an
            // instant local bubble while the real (slow, Gemini-backed)
            // request is in flight. $wire.sendMessage() is the exact same
            // server method the form used to call via wire:submit; calling
            // it directly here just lets this function await its completion.
            async submitMessage() {
                if (this.sending) {
                    return;
                }

                const text = this.$refs.chatInput.value;

                if (text.trim() !== '') {
                    this.optimisticMessage = text;
                    this.$refs.chatInput.value = '';
                    this.resizeChatInput();
                    this.scrollToBottom();
                }
                // Empty text: don't fabricate an optimistic bubble, but still
                // call sendMessage() below so the existing server-side
                // "required" validation error shows exactly as it did before
                // this change (test_empty_message_is_rejected).

                this.sending = true;
                await this.$wire.sendMessage();
                this.sending = false;

                // Only clear the optimistic bubble on SUCCESS — the real,
                // server-rendered message list already contains it by now,
                // so there's no gap or duplicate. On failure (rate limit /
                // Gemini error, surfaced via $errorMessage), deliberately
                // leave it showing: for a rate-limit rejection specifically,
                // ChatService never even saves the message, so this is the
                // ONLY remaining trace of what the user tried to send.
                if (!this.$wire.errorMessage) {
                    this.optimisticMessage = null;
                }

                this.scrollToBottom();
            },

            resizeChatInput() {
                if (this.compact) {
                    return;
                }

                const el = this.$refs.chatInput;
                el.style.height = 'auto';
                el.style.height = Math.min(el.scrollHeight, 160) + 'px';
            },
        };
    }
</script>
