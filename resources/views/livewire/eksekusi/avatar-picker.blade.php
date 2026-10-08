<div class="max-w-2xl">
    <h1 class="font-display text-2xl font-bold text-ink">Pilih Avatar</h1>
    <p class="mt-1 text-sm text-muted">Pilih salah satu hewan sebagai avatar kamu. Bebas ganti kapan saja, tidak ada syarat.</p>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-muted/25 bg-success-soft p-4 text-sm text-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($options as $jenis)
            <button
                type="button"
                wire:click="choose('{{ $jenis }}')"
                class="flex flex-col items-center gap-3 rounded-xl border p-5 transition-all duration-200 hover:-translate-y-0.5 {{ $selected === $jenis ? 'border-accent bg-accent-soft/40 shadow-warm-md' : 'border-muted/25 hover:shadow-warm-xs' }}"
            >
                <x-avatar.eksekusi :jenis="$jenis" size="lg" />
                <span class="flex items-center gap-1.5 font-display text-sm font-semibold text-ink capitalize">
                    {{ $jenis }}
                    @if ($selected === $jenis)
                        <span class="rounded-full bg-accent px-2 py-0.5 font-mono text-[10px] font-bold text-ink">AKTIF</span>
                    @endif
                </span>
            </button>
        @endforeach
    </div>
</div>
