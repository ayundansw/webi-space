@props(['project', 'active'])

{{--
    Fase 7 Batch 1a: tab bar dipakai di SEMUA halaman tab proyek (Kanban
    sungguhan sekarang; Roadmap/Gantt/Kalender/Forum Proyek placeholder;
    Anggota sungguhan). Murni presentational (wire:navigate, tanpa state
    sendiri) — makanya Blade component biasa, bukan Livewire, beda dari
    <livewire:eksekusi.projects.project-header /> yang punya form/aksi.
--}}
@php
    // Task 4 (Tahap B, Isi Proyek revisi): urutan render mengikuti urutan
    // key di array ini — Anggota, Kalender, Roadmap, Kanban, Gantt, Forum
    // Proyek.
    $tabs = [
        'anggota' => ['label' => 'Anggota', 'route' => 'eksekusi.projects.anggota'],
        'kalender' => ['label' => 'Kalender', 'route' => 'eksekusi.projects.kalender'],
        'roadmap' => ['label' => 'Roadmap', 'route' => 'eksekusi.projects.roadmap'],
        'kanban' => ['label' => 'Kanban', 'route' => 'eksekusi.projects.kanban'],
        'gantt' => ['label' => 'Gantt', 'route' => 'eksekusi.projects.gantt'],
        'forum' => ['label' => 'Forum Proyek', 'route' => 'eksekusi.projects.forum'],
    ];
@endphp

<div class="mt-6 flex gap-1 overflow-x-auto border-b border-muted/25">
    @foreach ($tabs as $slug => $tab)
        <a
            href="{{ route($tab['route'], $project) }}"
            wire:navigate
            class="shrink-0 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors {{ $active === $slug ? 'border-ink text-ink' : 'border-transparent text-muted hover:text-ink' }}"
        >
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
