<div>
    <h1 class="font-display text-2xl font-bold text-ink">Halo, {{ auth()->user()->name }}!</h1>
    <p class="mt-1 text-sm text-muted">Ini progres belajarmu sejauh ini. Pelan-pelan saja, yang penting konsisten.</p>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-muted/25 p-5">
            <p class="text-xs text-muted">Level Saat Ini</p>
            <p class="font-display mt-1 text-3xl font-bold text-ink">{{ $userProgress->current_level }}</p>
            <p class="text-sm text-ink">{{ $userProgress->level_name }}</p>
        </div>

        <div class="rounded-xl border border-muted/25 p-5">
            <p class="text-xs text-muted">Total Poin</p>
            <p class="font-display mt-1 text-3xl font-bold text-ink">{{ $userProgress->total_points }}</p>
        </div>

        <div class="rounded-xl border border-muted/25 p-5">
            <p class="text-xs text-muted">Progres Keseluruhan</p>
            <p class="font-display mt-1 text-3xl font-bold text-ink">{{ $overallPercentage }}%</p>
            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-muted/15">
                <div class="h-full bg-accent" style="width: {{ $overallPercentage }}%"></div>
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-muted/25 p-5">
        <p class="text-xs text-muted">Sedang Dikerjakan</p>
        @if ($nextUnit)
            <p class="mt-1 text-sm font-medium text-ink">{{ $nextUnit->title }}</p>
            <a href="{{ url('/eksplorasi/unit/'.$nextUnit->id) }}" class="mt-2 inline-block rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
                Lanjut Belajar
            </a>
        @else
            <p class="mt-1 text-sm text-ink">Semua unit yang tersedia sudah kamu selesaikan. Keren banget!</p>
            <a href="{{ url('/eksplorasi/kurikulum') }}" class="mt-2 inline-block text-sm text-ink underline hover:text-accent">
                Lihat Peta Kurikulum
            </a>
        @endif
    </div>

    <div class="mt-6">
        <h2 class="font-display text-base font-semibold text-ink">Log Aktivitas</h2>
        <div class="mt-3 space-y-2">
            @forelse ($feed as $entry)
                <div class="rounded-lg border border-muted/25 px-4 py-3">
                    <p class="text-sm text-ink">{{ $entry['message'] }}</p>
                    <p class="font-mono mt-1 text-xs text-muted">{{ \Illuminate\Support\Carbon::parse($entry['timestamp'])->diffForHumans() }}</p>
                </div>
            @empty
                <p class="text-sm text-muted">Belum ada aktivitas. Yuk mulai dari Modul 1 di Peta Kurikulum!</p>
            @endforelse
        </div>
    </div>
</div>
