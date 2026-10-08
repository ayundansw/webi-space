<div>
    <h1 class="mb-6 font-display text-2xl font-bold text-ink">Profil Saya</h1>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-muted/25 bg-green-50 p-4 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    {{--
        Fase 3 Batch 7b, poin 1: ringkasan avatar aktif + level/poin (kalau
        ada), gaya kartu final Dashboard (rounded-3xl bg-surface + aksen
        sirkuit sudut) — bukan sekadar teks polos seperti ringkasan status
        keanggotaan lama di bawah form (yang tetap dipertahankan apa adanya).
    --}}
    <div class="relative mb-8 overflow-hidden rounded-3xl bg-surface p-6 sm:p-8">
        <svg aria-hidden="true" viewBox="0 0 240 120" fill="none" stroke="currentColor" stroke-width="1.5" class="pointer-events-none absolute -top-6 -right-6 h-32 w-56 text-accent/20 sm:h-40 sm:w-72">
            <path d="M0 90 H70 L90 60 H150 L170 30 H240" />
            <circle cx="70" cy="90" r="4" fill="currentColor" stroke="none" />
            <circle cx="150" cy="60" r="4" fill="currentColor" stroke="none" />
            <circle cx="240" cy="30" r="4" fill="currentColor" stroke="none" />
        </svg>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                @if ($user->role === 'exploration_member')
                    <x-avatar.fox :tingkat="$foxTier" size="lg" />
                @elseif ($user->role === 'execution_member')
                    <x-avatar.eksekusi :jenis="$user->avatar_url ?: 'elang'" size="lg" />
                @else
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-ink font-display text-2xl font-bold text-white">
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    </span>
                @endif
                <div>
                    <p class="font-display text-xl font-bold text-heading">{{ $user->name }}</p>
                    <p class="text-xs text-caption">
                        {{ match ($user->role) {
                            'admin' => 'Admin',
                            'exploration_member' => 'Anggota Eksplorasi',
                            'execution_member' => 'Anggota Eksekusi',
                        } }}
                    </p>
                </div>
            </div>

            @if ($explorationProgress)
                <div class="flex items-center gap-3 rounded-xl border border-muted/20 bg-white px-4 py-3 shadow-warm-xs">
                    <div>
                        <p class="text-xs text-caption">Level {{ $explorationProgress->current_level }}</p>
                        <p class="font-display text-lg font-bold text-heading">{{ $explorationProgress->level_name }}</p>
                    </div>
                    <p class="font-pixel text-3xl text-accent">{{ $explorationProgress->total_points }}</p>
                </div>
            @endif
        </div>
    </div>

    {{--
        Perbaikan proporsi (setelah Aye cek di layar lebar): dulu 1:1
        (lg:grid-cols-2) di dalam wrapper halaman ber-`max-w-5xl` SENDIRI
        (tanpa mx-auto) — dua masalah sekaligus: wrapper itu lebih sempit
        dari `max-w-7xl` yang sudah disediakan <main> di layout, DAN karena
        tidak di-center, sisa lebar antara max-w-5xl dan max-w-7xl selalu
        jatuh di kanan. Halaman Dashboard (final) tidak pernah menambah
        max-w sendiri di atas milik <main> -- pola yang sama diikuti di sini
        (lihat penghapusan `max-w-5xl` di root div atas). Rasio kolom juga
        diubah dari 1:1 ke 2:3 (lg:grid-cols-5, kiri col-span-2/kanan
        col-span-3) -- form di kiri secukupnya, galeri avatar + heatmap di
        kanan yang butuh ruang lebih lebar dapat porsi lebih besar.
        `items-stretch` (bukan `items-start` sebelumnya) menyamakan tinggi
        kedua kolom, pola yang sama dengan grid besar Dashboard Eksplorasi.
    --}}
    <div class="grid grid-cols-1 items-stretch gap-6 lg:grid-cols-5">
        {{-- Kolom kiri: form CRUD dasar, TIDAK diubah dari sebelumnya. --}}
        <div class="space-y-6 lg:col-span-2">
            <form wire:submit="saveProfile" class="space-y-4 rounded-xl border border-muted/25 p-6">
                <div>
                    <label for="email" class="mb-1 block text-sm text-ink">Email</label>
                    <input
                        type="text"
                        id="email"
                        value="{{ $user->email }}"
                        disabled
                        class="w-full rounded-lg border border-muted/25 bg-muted/5 px-3 py-2 text-sm text-muted"
                    >
                    <p class="mt-1 text-xs text-muted">Email tidak bisa diubah sendiri. Hubungi admin kalau perlu diganti.</p>
                </div>

                <div>
                    <label for="name" class="mb-1 block text-sm text-ink">Nama</label>
                    <input
                        type="text"
                        id="name"
                        wire:model="name"
                        class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm text-ink">Minat Bidang</label>
                    <p class="mb-2 text-xs text-muted">
                        Opsional, tapi kalau diisi, WEBI bisa kasih saran unit/materi yang lebih relevan buat kamu. Boleh pilih lebih dari satu.
                    </p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach (['frontend' => 'Frontend', 'backend' => 'Backend', 'ui_ux' => 'UI/UX', 'analyst' => 'Analis', 'pm' => 'PM', 'fullstack' => 'Fullstack'] as $value => $label)
                            <label class="flex items-center gap-2 rounded-lg border border-muted/25 px-3 py-2 text-sm text-ink">
                                <input
                                    type="checkbox"
                                    wire:model="interestField"
                                    value="{{ $value }}"
                                    class="rounded border-muted/40 text-accent focus:ring-accent"
                                >
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('interestField.*')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between rounded-lg bg-muted/5 px-3 py-2 text-xs text-muted">
                    <span>Status Keanggotaan: <span class="font-medium text-ink">{{ $user->membership_status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></span>
                    @if ($explorationProgress)
                        <span class="font-mono">{{ $explorationProgress->total_points }} poin</span>
                    @endif
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60"
                >
                    Simpan Perubahan
                </button>
            </form>

            {{--
                Perbaikan: Ganti Password sekarang modal, bukan form yang
                selalu terbuka di bawah halaman utama. Logic changePassword()
                di komponen TIDAK diubah sama sekali -- form di dalam modal
                ini wire:submit ke method yang persis sama, cuma dibungkus
                x-show/x-transition (pola identik modal detail Project Ideas,
                _idea-card.blade.php). Modal menutup otomatis lewat event
                `password-changed` yang di-dispatch komponen HANYA di jalur
                sukses (bukan di jalur validasi/password lama salah) --
                supaya modal tetap terbuka menampilkan error kalau gagal.
            --}}
            <div x-data="{ passwordModalOpen: false }" x-on:password-changed.window="passwordModalOpen = false" class="rounded-xl border border-muted/25 p-6">
                @if (session('password_status'))
                    <div class="mb-4 rounded-lg border border-muted/25 bg-green-50 p-3 text-sm text-green-700">
                        {{ session('password_status') }}
                    </div>
                @endif

                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-medium text-ink">Password</h2>
                        <p class="mt-1 text-xs text-muted">Ganti password kapan saja lewat dialog singkat.</p>
                    </div>
                    <button
                        type="button"
                        @click="passwordModalOpen = true"
                        class="shrink-0 rounded-lg border border-muted/40 px-4 py-2 text-sm text-ink hover:border-ink"
                    >
                        Ganti Password
                    </button>
                </div>

                <div
                    x-show="passwordModalOpen"
                    x-cloak
                    x-transition:enter="transition-opacity duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition-opacity duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="passwordModalOpen = false"
                    @keydown.escape.window="passwordModalOpen = false"
                    class="fixed inset-0 z-40 flex items-center justify-center bg-ink/40 p-4"
                >
                    <div
                        @click.stop
                        x-show="passwordModalOpen"
                        x-transition:enter="transition duration-200 ease-out"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition duration-150 ease-in"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="w-full max-w-md rounded-modal border border-muted/20 bg-white p-6 shadow-warm-lg"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <h2 class="font-display text-lg font-bold text-ink">Ganti Password</h2>
                            <button type="button" @click="passwordModalOpen = false" class="text-muted hover:text-ink" aria-label="Tutup">&times;</button>
                        </div>

                        <form wire:submit="changePassword" class="mt-4 space-y-4">
                            <div>
                                <label for="current_password" class="mb-1 block text-sm text-ink">Password Lama</label>
                                <input
                                    type="password"
                                    id="current_password"
                                    wire:model="current_password"
                                    class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                                >
                                @error('current_password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="mb-1 block text-sm text-ink">Password Baru</label>
                                <input
                                    type="password"
                                    id="password"
                                    wire:model="password"
                                    class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                                >
                                <p class="mt-1 text-xs text-muted">Minimal 8 karakter.</p>
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="mb-1 block text-sm text-ink">Konfirmasi Password Baru</label>
                                <input
                                    type="password"
                                    id="password_confirmation"
                                    wire:model="password_confirmation"
                                    class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                                >
                            </div>

                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                class="w-full rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60"
                            >
                                Simpan Password Baru
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom kanan: galeri avatar, heatmap/kontribusi (role-dependent), slot placeholder. --}}
        <div class="space-y-6 lg:col-span-3">
            @if ($user->role === 'exploration_member')
                {{-- Fase 3 Batch 7b poin 2: 5 tingkat Fox, terbuka/terkunci
                     murni derived dari FoxAvatarService::tierFor() — tidak
                     ada mekanisme pilih, sama seperti aslinya. --}}
                <div class="relative overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs">
                    <div class="card-pixel-accent-top" aria-hidden="true"></div>
                    <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

                    <h2 class="font-display text-base font-semibold text-heading">Galeri Avatar Fox</h2>
                    <p class="mt-1 text-xs text-caption">Tingkat naik otomatis sesuai total poin kamu: tidak bisa dipilih manual.</p>

                    <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-5">
                        @foreach ($foxTiers as $tier => $minPoints)
                            @php $unlocked = $foxTier >= $tier; @endphp
                            <div class="flex flex-col items-center gap-1.5 rounded-lg border p-3 text-center {{ $unlocked ? 'border-accent bg-accent-soft/30' : 'border-muted/20 bg-muted/5' }}">
                                {{--
                                    Perbaikan: opacity-30 + grayscale penuh
                                    (100%) sebelumnya menghancurkan detail
                                    pembeda antar tingkat (aksen kerah cyan,
                                    aksen pelipis/bahu, sparkle) sampai nyaris
                                    tidak terlihat sama sekali di ukuran kecil
                                    ini (10x10, viewBox 12x12 -- aksen 1-2
                                    unit jadi cuma ~3-7px layar). Diganti
                                    opacity-60 + grayscale-50 (desaturasi
                                    SEBAGIAN, bukan penuh) -- tetap terasa
                                    "terkunci"/redup (efek dim TIDAK dihapus,
                                    cuma dikurangi intensitasnya), tapi beda
                                    warna asli (cyan aksen vs oranye badan vs
                                    abu kerah) masih cukup tersisa untuk
                                    samar-samar menunjukkan siluet tiap
                                    tingkat itu berbeda.
                                --}}
                                <x-avatar.fox :tingkat="$tier" size="sm" class="{{ $unlocked ? '' : 'opacity-60 grayscale-50' }}" />
                                <span class="text-[11px] font-medium {{ $unlocked ? 'text-ink' : 'text-muted' }}">Tingkat {{ $tier }}</span>
                                @if ($unlocked)
                                    <span class="rounded-full bg-accent px-1.5 py-0.5 font-mono text-[9px] font-bold text-ink">TERBUKA</span>
                                @else
                                    <span class="text-[10px] text-muted">{{ $minPoints }}+ poin</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{--
                    Perbaikan: grid heatmap sebelumnya pakai ukuran kotak
                    tetap (h-3 w-3) di dalam flex w-fit -- lebar totalnya
                    cuma (jumlah kolom x 12px), jauh lebih sempit dari lebar
                    card, jadi menggantung di kiri dengan ruang kosong besar
                    di kanan. Diganti CSS Grid dengan grid-template-columns
                    dinamis `repeat(N, minmax(0, 1fr))` (N = jumlah minggu,
                    beda tiap request tergantung tanggal hari ini -- HARUS
                    inline style, bukan class Tailwind, karena Tailwind's
                    build-time scanner tidak bisa menghasilkan class untuk
                    angka yang baru diketahui saat request) + `grid-auto-flow:
                    column` supaya urutan flat $heatmapWeeks (minggu demi
                    minggu, 7 hari tiap minggu) otomatis mengisi kolom demi
                    kolom tanpa perlu nested grid per minggu. Tiap kotak
                    `aspect-square w-full` supaya lebar mengikuti lebar
                    kolom (1fr, otomatis proporsional) dan tinggi mengikuti
                    lebar itu juga -- selalu persegi apa pun lebar card-nya.
                    `min-width` (10px/kolom) + overflow-x-auto pada
                    pembungkus adalah fallback KHUSUS layar sangat sempit --
                    di layar biasa/lebar, lebar grid = 100% lebar card
                    (tidak pernah overflow), scroll cuma aktif kalau
                    N x 10px genuinely melebihi lebar card yang tersedia.
                --}}
                <div class="relative overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs">
                    <div class="card-pixel-accent-top" aria-hidden="true"></div>
                    <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

                    <div class="flex items-center justify-between">
                        <h2 class="font-display text-base font-semibold text-heading">Kalender Aktivitas</h2>
                        <span class="text-xs text-caption">6 bulan terakhir</span>
                    </div>

                    {{--
                        Perbaikan: tooltip native `title` diganti tooltip
                        Alpine kustom (hover DAN tap, `title` browser tidak
                        tap-friendly di mobile dan tidak bisa diberi
                        styling). SATU elemen tooltip dipindah-pindah lewat
                        state Alpine (`tooltip.top`/`left`/`text`), bukan
                        satu elemen per kotak (182 elemen tooltip sekaligus
                        di DOM terlalu berat, cukup satu). Posisi pakai
                        `position: fixed` + koordinat mentah dari
                        `getBoundingClientRect()` (relatif viewport) --
                        otomatis lolos dari `overflow-hidden` card ini tanpa
                        perhitungan offset tambahan. Clamp horizontal
                        (`Math.max`/`Math.min` terhadap `window.innerWidth`)
                        dan flip vertikal (di bawah kotak kalau kotak terlalu
                        dekat ke tepi atas viewport) adalah penyesuaian tepi
                        yang diminta. Teks tooltip (termasuk format tanggal
                        Indonesia lengkap) dihitung SEKALI di server lewat
                        `data-tooltip`, dibaca Alpine dari
                        `event.currentTarget.dataset.tooltip` -- tidak perlu
                        request server tiap hover/tap, dan Blade `{{ }}`
                        otomatis meng-escape teksnya jadi tidak perlu
                        escaping manual di sisi JS.
                    --}}
                    @php $heatmapWeekCount = count($heatmapWeeks); @endphp
                    <div
                        class="mt-4"
                        x-data="{
                            tooltip: { open: false, text: '', top: 0, left: 0, above: true },
                            showTooltip(event) {
                                const text = event.currentTarget.dataset.tooltip;
                                if (! text) return;
                                const rect = event.currentTarget.getBoundingClientRect();
                                const estimatedWidth = 220;
                                const margin = 8;
                                let left = rect.left + rect.width / 2;
                                left = Math.max(estimatedWidth / 2 + margin, Math.min(left, window.innerWidth - estimatedWidth / 2 - margin));
                                const above = rect.top > 60;
                                this.tooltip = { open: true, text, left, top: above ? rect.top - margin : rect.bottom + margin, above };
                            },
                            hideTooltip() { this.tooltip.open = false; },
                        }"
                        @click.outside="hideTooltip()"
                    >
                        <div class="overflow-x-auto pb-2">
                            <div
                                class="grid w-full gap-1"
                                style="grid-auto-flow: column; grid-template-rows: repeat(7, minmax(0, 1fr)); grid-template-columns: repeat({{ $heatmapWeekCount }}, minmax(0, 1fr)); min-width: {{ $heatmapWeekCount * 10 }}px;"
                            >
                                @foreach ($heatmapWeeks as $week)
                                    @foreach ($week as $day)
                                        @php
                                            $cellClass = match (true) {
                                                $day['count'] === null => 'bg-transparent',
                                                $day['count'] === 0 => 'bg-muted/10',
                                                $day['count'] === 1 => 'bg-accent-soft',
                                                $day['count'] === 2 => 'bg-accent/60',
                                                default => 'bg-accent',
                                            };
                                            $tooltipText = $day['count'] === null
                                                ? null
                                                : ($day['count'] === 0
                                                    ? 'Tidak ada aktivitas pada '.$day['date']->locale('id')->translatedFormat('d F Y')
                                                    : $day['count'].' aktivitas pada '.$day['date']->locale('id')->translatedFormat('d F Y'));
                                        @endphp
                                        @if ($tooltipText === null)
                                            <span class="aspect-square w-full rounded-sm {{ $cellClass }}"></span>
                                        @else
                                            <span
                                                class="aspect-square w-full cursor-pointer rounded-sm {{ $cellClass }}"
                                                data-date="{{ $day['date']->format('Y-m-d') }}"
                                                data-tooltip="{{ $tooltipText }}"
                                                @mouseenter="showTooltip($event)"
                                                @mouseleave="hideTooltip()"
                                                @click="showTooltip($event)"
                                            ></span>
                                        @endif
                                    @endforeach
                                @endforeach
                            </div>
                        </div>

                        <div
                            x-show="tooltip.open"
                            x-cloak
                            x-transition.opacity.duration.100ms
                            class="pointer-events-none fixed z-50 max-w-[220px] -translate-x-1/2 rounded-lg bg-ink px-2.5 py-1.5 text-center text-xs leading-snug text-white shadow-warm-md"
                            :class="tooltip.above ? '-translate-y-full' : ''"
                            :style="`top: ${tooltip.top}px; left: ${tooltip.left}px;`"
                            x-text="tooltip.text"
                        ></div>
                    </div>

                    <div class="mt-3 flex items-center justify-end gap-1.5 text-[10px] text-caption">
                        <span>Kurang</span>
                        <span class="h-3 w-3 rounded-sm bg-muted/10"></span>
                        <span class="h-3 w-3 rounded-sm bg-accent-soft"></span>
                        <span class="h-3 w-3 rounded-sm bg-accent/60"></span>
                        <span class="h-3 w-3 rounded-sm bg-accent"></span>
                        <span>Banyak</span>
                    </div>
                </div>
            @elseif ($user->role === 'execution_member')
                {{-- Fase 3 Batch 7b poin 2: REUSE App\Livewire\Eksekusi\AvatarPicker
                     apa adanya (nested Livewire component, pola yang sama
                     dipakai <livewire:notifications.bell /> dkk) — komponen
                     ini cuma menampilkannya di konteks Profil, tidak
                     membangun ulang logic pilih avatar. --}}
                <div class="relative overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs">
                    <div class="card-pixel-accent-top" aria-hidden="true"></div>
                    <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

                    <livewire:eksekusi.avatar-picker />
                </div>
            @endif

            {{--
                Fase 3 Batch 7b poin 4: kontribusi per-proyek, TANPA kolom
                peran. Fase 8 Batch 6 (gap #2 dari Batch 2's audit): dulu
                cuma tampil untuk role==='execution_member' literal, jadi
                anggota Mode Ganda (exploration_member yang sungguhan sudah
                jadi ProjectMember/TaskAssignment lewat akses Eksekusi
                mereka) tidak pernah melihat kontribusi MEREKA SENDIRI di
                sini. Sekarang di LUAR blok role exploration/execution di
                atas — tampil untuk role apa pun yang PERNAH punya
                kontribusi (data asli, bukan role), PLUS selalu untuk
                execution_member native (perilaku lama dipertahankan persis:
                mereka tetap lihat kartu ini walau masih kosong, karena
                kartu ini inheren relevan untuk identitas role mereka).
                exploration_member biasa yang tidak pernah menyentuh
                Eksekusi TIDAK melihat kartu kosong ini sama sekali —
                menghindari section yang tidak relevan buat mayoritas
                anggota Eksplorasi.
            --}}
            @if ($user->role === 'execution_member' || $projectContributions->isNotEmpty())
                <div class="relative overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs">
                    <div class="card-pixel-accent-top" aria-hidden="true"></div>
                    <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

                    <h2 class="font-display text-base font-semibold text-heading">Kontribusi Proyek</h2>

                    <div class="mt-3 space-y-2">
                        @forelse ($projectContributions as $row)
                            <a
                                href="{{ url('/eksekusi/projects/'.$row['project']->id) }}"
                                wire:navigate
                                class="flex items-center justify-between rounded-lg border border-muted/20 bg-white px-3 py-2 text-sm shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md"
                            >
                                <span class="text-ink">{{ $row['project']->title }}</span>
                                <span class="font-mono text-xs text-caption">{{ $row['task_count'] }} task</span>
                            </a>
                        @empty
                            <p class="text-sm text-muted">Kamu belum tergabung di proyek apa pun.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{--
                Fase 8 Batch 4: slot "Info Mode Ganda" (Fase 3 Batch 7b)
                fungsional penuh untuk sisi pengajuan member
                (docs/v_2.0/archive/sumber-konsolidasi/RANCANGAN_FINAL_WEBI-SPACE_v2.md §2.2.B) —
                submit/status/riwayat tolakan semua lewat DualModeService.
                Panel admin approve/reject (Batch 5) dan UI ganti mode aktif
                lewat menu akun navbar (Batch 6) sekarang sudah ada juga.
            --}}
            @if ($user->role === 'exploration_member')
                <div class="relative flex flex-col gap-2 overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs">
                    <div class="card-pixel-accent-top" aria-hidden="true"></div>
                    <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

                    <h2 class="font-display text-sm font-semibold text-heading">Info Mode Ganda</h2>

                    @error('dual_mode') <p class="text-xs text-danger">{{ $message }}</p> @enderror

                    @if ($user->dual_mode_status === 'none')
                        @if ($latestDualModeRequest?->status === 'rejected')
                            <span class="w-fit rounded-full bg-danger-soft px-3 py-1 font-mono text-[10px] font-medium uppercase tracking-wide text-danger">Permintaan Sebelumnya Ditolak</span>
                            @if ($latestDualModeRequest->note)
                                <p class="text-xs text-caption">Alasan admin: {{ $latestDualModeRequest->note }}</p>
                            @endif
                        @elseif ($latestDualModeRequest?->status === 'revoked')
                            <span class="w-fit rounded-full bg-danger-soft px-3 py-1 font-mono text-[10px] font-medium uppercase tracking-wide text-danger">Akses Dicabut</span>
                            <p class="text-xs text-caption">Akses Eksekusi kamu pernah dicabut admin.</p>
                        @endif
                        <p class="text-xs text-caption">
                            Mau bantu di sisi Eksekusi juga? Ajukan akses Mode Eksekusi ke admin — kalau disetujui,
                            data Eksplorasi kamu (poin, level, progres) TETAP ADA seperti biasa, progres Eksekusi
                            (task, proyek) berjalan terpisah sejak disetujui. Admin bisa mencabut akses ini kapan saja.
                        </p>
                        <button type="button" wire:click="requestDualModeAccess" class="mt-1 w-fit rounded-control bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">
                            Ajukan Akses Eksekusi
                        </button>
                    @elseif ($user->dual_mode_status === 'pending')
                        <span class="w-fit rounded-full bg-warning-soft px-3 py-1 font-mono text-[10px] font-medium uppercase tracking-wide text-warning">Menunggu Keputusan Admin</span>
                        <p class="text-xs text-caption">Permintaan akses Eksekusi kamu sedang ditinjau admin.</p>
                    @elseif ($user->dual_mode_status === 'approved')
                        <span class="w-fit rounded-full bg-success-soft px-3 py-1 font-mono text-[10px] font-medium uppercase tracking-wide text-success">Akses Eksekusi Disetujui</span>
                        <p class="text-xs text-caption">Akses Eksekusi kamu aktif{{ $user->active_mode === 'execution' ? ' dan sedang kamu pakai sekarang' : '' }}. Ganti mode lewat menu Akun (navbar).</p>
                    @endif
                </div>
            @elseif ($user->role === 'execution_member')
                {{-- Fase 8 Batch 6: bukan placeholder lagi -- akses baca
                     Eksplorasi SUDAH aktif sejak Batch 3 (tanpa perlu
                     approval), dan sekarang ada jalur UI sungguhan ke sana
                     (menu Akun navbar + nav "Jelajahi Eksplorasi"). --}}
                <div class="relative flex flex-col gap-2 overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs">
                    <div class="card-pixel-accent-top" aria-hidden="true"></div>
                    <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

                    <h2 class="font-display text-sm font-semibold text-heading">Info Mode Ganda</h2>
                    <p class="text-xs text-caption">
                        Kamu sudah bisa baca-saja Peta Kurikulum, materi Unit, dan Referensi Eksplorasi kapan saja,
                        tanpa perlu persetujuan admin. Progres/poin Eksplorasi TIDAK tercatat dari mode baca ini.
                        Buka lewat menu Akun (navbar) atau menu "Jelajahi Eksplorasi" di navigasi.
                    </p>
                    <a href="{{ route('eksplorasi.kurikulum') }}" wire:navigate class="mt-1 w-fit rounded-control bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">
                        Buka Peta Kurikulum
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
