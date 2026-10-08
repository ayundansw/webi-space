{{--
    Diekstrak verbatim dari chat.blade.php (Redesign WEBI Chat halaman
    penuh) -- isi PERSIS sama, dipakai ULANG oleh panel kontekstual &
    halaman chat penuh. Conditional `$contextUnit` di dalamnya (kotak
    ketik h-10 tetap vs min-h-10 boleh melebar) TETAP jalan seperti
    semula lewat scope @include Blade -- tidak ada perbedaan perilaku
    antara ditulis inline vs di-include.
--}}
<form @submit.prevent="submitMessage()" class="flex items-end gap-2">
    <textarea
        x-ref="chatInput"
        wire:model="messageText"
        x-init="resizeChatInput()"
        @input="resizeChatInput()"
        @webi-message-sent.window="$el.value = ''; resizeChatInput()"
        @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); submitMessage() }"
        rows="1"
        placeholder="Tanya WEBI di sini... (Enter untuk kirim, Shift+Enter untuk baris baru)"
        {{--
            $contextUnit ? h-10 tetap (panel kecil di halaman Materi,
            tidak boleh membesar sama sekali -- lihat resizeChatInput()
            compact no-op) : min-h-10 boleh melebar sampai 160px (halaman
            chat penuh, baik versi lama maupun redesign baru ini).
        --}}
        class="{{ $contextUnit ? 'h-10 overflow-y-auto' : 'min-h-10' }} flex-1 resize-none rounded-lg border border-muted/40 px-3 py-2 text-sm leading-relaxed focus:border-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
    ></textarea>

    <template x-if="voiceSupported">
        <button
            type="button"
            @click="toggleListening()"
            :class="listening ? 'border-accent text-accent' : 'border-muted/40 text-ink'"
            class="flex h-10 shrink-0 items-center justify-center rounded-lg border px-3 text-sm hover:border-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent"
            title="Ngomong ke WEBI"
        >
            <span x-show="!listening">&#127908;</span>
            <span x-show="listening">&#9679;</span>
        </button>
    </template>

    <button
        type="submit"
        wire:loading.attr="disabled"
        wire:target="sendMessage"
        class="flex h-10 shrink-0 items-center justify-center rounded-lg bg-ink px-4 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent"
    >
        Kirim
    </button>
</form>
@error('messageText') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

{{--
    Putaran perbaikan Aye: di halaman chat PENUH, hint mode-suara ini
    dipindah ke LUAR/ATAS card (lihat chat.blade.php cabang @else) supaya
    card tetap bersih -- jadi di sini cuma dirender untuk panel
    KONTEKSTUAL ($contextUnit set), TIDAK diduplikasi di halaman penuh.
--}}
@if ($contextUnit)
    <p class="mt-2 text-xs text-muted" x-show="voiceSupported && voiceMode">
        Mode suara aktif. Bicara lewat tombol mikrofon, transkrip akan muncul di kotak teks untuk kamu cek dulu sebelum dikirim.
    </p>
    <p class="mt-2 text-xs text-muted" x-show="!voiceSupported">
        Browser ini belum mendukung fitur suara, tapi tenang, chat teks tetap berfungsi penuh seperti biasa.
    </p>
@endif
