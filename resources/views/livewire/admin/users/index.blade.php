@php
    // Icons matching the outline stroke style already used in the sidebar
    // (viewBox 24, stroke-width 1.75, currentColor) — no icon package.
    $roleIcons = [
        'admin' => '<path d="M12 3 4 6v5c0 4.5 3 7.5 8 10 5-2.5 8-5.5 8-10V6Z" />',
        'exploration_member' => '<path d="M4 5a2 2 0 0 1 2-2h6v18H6a2 2 0 0 1-2-2V5Z" /><path d="M20 5a2 2 0 0 0-2-2h-6v18h6a2 2 0 0 1 2 2V5Z" />',
        'execution_member' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" />',
    ];
@endphp

@push('breadcrumb-actions')
    <a href="{{ url('/admin/users/create') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control bg-ink px-4 py-2 text-sm font-medium text-white shadow-warm-xs hover:bg-ink/90">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M12 5v14" /><path d="M5 12h14" />
        </svg>
        Buat Akun Baru
    </a>
@endpush

<div>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Manajemen Akun</h1>
    </div>

    @if (session('generated_password'))
        <div class="mb-6 rounded-xl border border-accent/50 bg-accent-soft/40 p-4">
            <p class="text-sm text-ink">
                Password awal untuk <strong>{{ session('generated_password_user') }}</strong> (tampil sekali, catat sekarang):
            </p>
            <p class="font-mono mt-1 text-lg font-medium text-ink" data-testid="generated-password">{{ session('generated_password') }}</p>
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Cari nama anggota..."
            class="w-full rounded-control border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none sm:flex-1"
        >
        <select wire:model.live="roleFilter" class="w-full rounded-control border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none sm:w-48">
            <option value="">Semua Role</option>
            <option value="admin">Admin</option>
            <option value="exploration_member">Anggota Eksplorasi</option>
            <option value="execution_member">Anggota Eksekusi</option>
        </select>
        <select wire:model.live="statusFilter" class="w-full rounded-control border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none sm:w-40">
            <option value="">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-muted/25">
        <table class="w-full table-fixed text-left text-sm">
            <thead class="border-b border-muted/25 bg-accent-soft/20 text-muted">
                <tr>
                    <th class="w-[6%] px-3 py-2.5 font-medium">No</th>
                    <th class="w-[20%] px-3 py-2.5 font-medium">Nama</th>
                    <th class="w-[31%] px-3 py-2.5 font-medium">Email</th>
                    <th class="w-[16%] px-3 py-2.5 font-medium">Role</th>
                    <th class="w-[15%] px-3 py-2.5 font-medium">Status</th>
                    <th class="w-[12%] px-3 py-2.5 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-b border-muted/15 last:border-0">
                        <td class="px-3 py-2.5 font-mono text-xs text-muted">{{ $loop->iteration }}</td>
                        <td class="px-3 py-2.5 text-ink">{{ $user->name }}</td>
                        <td class="truncate px-3 py-2.5 text-ink" title="{{ $user->email }}">{{ $user->email }}</td>
                        <td class="font-mono px-3 py-2.5 text-xs text-ink">
                            <span class="inline-flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 shrink-0 text-muted">
                                    {!! $roleIcons[$user->role] !!}
                                </svg>
                                {{ match ($user->role) {
                                    'admin' => 'Admin',
                                    'exploration_member' => 'Eksplorasi',
                                    'execution_member' => 'Eksekusi',
                                } }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs {{ $user->membership_status === 'active' ? 'bg-success-soft text-success' : 'bg-muted/15 text-muted' }}">
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $user->membership_status === 'active' ? 'bg-success' : 'bg-muted' }}"></span>
                                {{ $user->membership_status === 'active' ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5 text-right">
                            <a href="{{ url('/admin/users/'.$user->id.'/edit') }}" class="text-sm text-ink underline hover:text-accent">
                                Kelola
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-sm text-muted">Tidak ada anggota ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
