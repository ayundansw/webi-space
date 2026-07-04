<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-bold text-ink">Manajemen Akun</h1>
        <a
            href="{{ url('/admin/users/create') }}"
            class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90"
        >
            Buat Akun Baru
        </a>
    </div>

    @if (session('generated_password'))
        <div class="mb-6 rounded-xl border border-accent/50 bg-accent-soft/40 p-4">
            <p class="text-sm text-ink">
                Password awal untuk <strong>{{ session('generated_password_user') }}</strong> (tampil sekali, catat sekarang):
            </p>
            <p class="font-mono mt-1 text-lg font-medium text-ink" data-testid="generated-password">{{ session('generated_password') }}</p>
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-muted/25">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-muted/25 bg-accent-soft/20 text-muted">
                <tr>
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Email</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="border-b border-muted/15 last:border-0">
                        <td class="px-4 py-3 text-ink">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-ink">{{ $user->email }}</td>
                        <td class="font-mono px-4 py-3 text-xs text-ink">
                            {{ match ($user->role) {
                                'admin' => 'Admin',
                                'exploration_member' => 'Anggota Eksplorasi',
                                'execution_member' => 'Anggota Eksekusi',
                            } }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $user->membership_status === 'active' ? 'bg-green-100 text-green-700' : 'bg-muted/15 text-muted' }}">
                                {{ $user->membership_status === 'active' ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ url('/admin/users/'.$user->id.'/edit') }}" class="text-sm text-ink underline hover:text-accent">
                                Kelola
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
