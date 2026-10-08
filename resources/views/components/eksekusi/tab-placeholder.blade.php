@props(['title', 'description'])

{{-- Fase 7 Batch 1a: pola sama persis dengan slot "Segera Hadir" lain yang
     sudah ada di app ini (mis. livewire/eksekusi/dashboard.blade.php). --}}
<div class="mt-6 flex flex-col items-center gap-2 rounded-xl border border-dashed border-muted/40 bg-white/60 p-10 text-center">
    <span class="rounded-full bg-muted/15 px-3 py-1 font-mono text-[10px] font-medium uppercase tracking-wide text-caption">Segera Hadir</span>
    <h2 class="font-display text-base font-semibold text-heading">{{ $title }}</h2>
    <p class="max-w-md text-sm text-caption">{{ $description }}</p>
</div>
