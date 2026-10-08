<div x-data="{ showForm: false, selectedDate: '' }" x-on:event-added.window="showForm = false; selectedDate = ''">
    <livewire:eksekusi.projects.project-header :project="$project" />

    <x-eksekusi.project-tabs :project="$project" active="kalender" />

    {{-- Task 7 (Tahap B, Isi Proyek revisi): tombol "+ Tambah Agenda"
         independen, sejajar judul tab -- modal SAMA dengan yang dipicu klik
         tanggal, cuma tanpa tanggal pre-filled. Tidak ada pengecekan role
         tambahan di sini -- siapa pun yang bisa membuka halaman ini (route
         sudah di belakang middleware `project.member`) sudah bisa menambah
         Acara lewat klik tanggal juga, jadi tombol ini cuma jalur pintas
         kedua ke aksi yang sama, bukan aksi baru yang butuh izin baru. --}}
    <div class="mt-6 flex justify-end">
        <button type="button" @click="selectedDate = ''; showForm = true" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                <path d="M12 5v14" /><path d="M5 12h14" />
            </svg>
            Tambah Agenda
        </button>
    </div>

    <x-eksekusi.calendar-month-grid
        :weeks="$weeks"
        :month-start="$monthStart"
        on-previous="previousMonth"
        on-next="nextMonth"
        interactive
    />

    {{-- Task 1 (perf revisi): popup ini SELALU ada di DOM (bukan @if server-side
         lagi), tampil/sembunyi murni lewat Alpine x-show -- klik tanggal atau
         tombol "Tambah Agenda" tidak pernah memicu request ke server, cuma
         submit form (wire:submit) yang benar-benar memanggil Livewire. --}}
    <div x-show="showForm" x-cloak @click="showForm = false" @keydown.escape.window="showForm = false" class="fixed inset-0 z-40 flex items-center justify-center bg-ink/40 p-4">
        <div @click.stop class="w-full max-w-md rounded-modal border border-muted/20 bg-white p-6 shadow-warm-lg">
            <div class="flex items-center justify-between gap-4">
                <h3 class="font-display text-lg font-bold text-ink" x-text="selectedDate ? 'Tambah Acara: ' + new Date(selectedDate + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : 'Tambah Acara'"></h3>
                <button type="button" @click="showForm = false" class="text-muted hover:text-ink" aria-label="Tutup">&times;</button>
            </div>

            <form @submit.prevent="$wire.addEvent(selectedDate)" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <input type="text" wire:model="newEventTitle" placeholder="Judul acara" class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                    @error('newEventTitle') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <input type="date" x-model="selectedDate" class="w-full rounded-lg border border-muted/40 px-2 py-2 text-sm focus:border-accent focus:outline-none">
                    @error('newEventDate') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                <input type="time" wire:model="newEventTime" class="w-full rounded-lg border border-muted/40 px-2 py-2 text-sm focus:border-accent focus:outline-none">
                <select wire:model="newEventType" class="w-full rounded-lg border border-muted/40 px-2 py-2 text-sm focus:border-accent focus:outline-none sm:col-span-2">
                    <option value="meeting">Meeting</option>
                    <option value="competition">Kompetisi</option>
                    <option value="other">Lainnya</option>
                </select>
                <div class="sm:col-span-2">
                    <textarea wire:model="newEventDescription" rows="2" placeholder="Deskripsi (opsional)" class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
                </div>
                <button type="submit" class="rounded-lg bg-ink px-3 py-2 text-sm font-medium text-white hover:bg-ink/90 sm:col-span-2">+ Tambah Acara</button>
            </form>
        </div>
    </div>
</div>
