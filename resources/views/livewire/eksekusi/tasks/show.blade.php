<div>
    <div class="flex items-start justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink">{{ $task->title }}</h1>
            <p class="mt-1 text-sm text-muted">Proyek: {{ $task->project->title }} &middot; Milestone: {{ $task->milestone->title }}</p>
        </div>
        <span class="rounded-lg border border-muted/40 px-3 py-1.5 text-sm font-mono uppercase text-ink">{{ $task->status }}</span>
    </div>

    <div class="mt-4 rounded-xl border border-muted/25 p-4 text-sm text-ink">
        <p>{{ $task->description ?: 'Tidak ada deskripsi.' }}</p>
        <div class="mt-3 flex flex-wrap gap-4 text-xs text-muted">
            <span>Prioritas: <span class="font-mono uppercase text-ink">{{ $task->priority }}</span></span>
            <span>Deadline: <span class="font-mono text-ink">{{ $task->deadline->format('d M Y') }}</span>{{ $task->deadline->isPast() && $task->status !== 'done' ? ' (OVERDUE)' : '' }}</span>
            <span>Assignee: {{ $assignments->pluck('user.name')->join(', ') ?: '-' }}</span>
        </div>
    </div>

    @error('status') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

    <div class="mt-4 flex flex-wrap gap-3">
        @if ($task->status === 'todo')
            <button wire:click="changeStatus('in_progress')" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">Mulai Kerjakan</button>
        @elseif ($task->status === 'in_progress')
            <button wire:click="changeStatus('in_review')" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">Submit untuk Review</button>
        @elseif ($task->status === 'in_review' && auth()->user()->role === 'admin')
            <button wire:click="changeStatus('done')" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">Lolos (Done)</button>
            <button wire:click="changeStatus('in_progress')" class="rounded-lg border border-muted/40 px-4 py-2 text-sm text-ink hover:border-ink">Perlu Revisi</button>
        @endif
    </div>

    @if (auth()->user()->role === 'admin')
        <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-3">
            <form wire:submit="changeDeadline" class="rounded-lg border border-muted/25 p-3">
                <label class="block text-xs font-medium text-ink">Ubah Deadline</label>
                <input type="date" wire:model="newDeadline" class="mt-1 w-full rounded-lg border border-muted/40 px-2 py-1.5 text-sm focus:border-accent focus:outline-none">
                <button type="submit" class="mt-2 rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">Simpan</button>
            </form>

            <form wire:submit="changePriority" class="rounded-lg border border-muted/25 p-3">
                <label class="block text-xs font-medium text-ink">Ubah Prioritas</label>
                <select wire:model="newPriority" class="mt-1 w-full rounded-lg border border-muted/40 px-2 py-1.5 text-sm focus:border-accent focus:outline-none">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
                <button type="submit" class="mt-2 rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">Simpan</button>
            </form>

            <form wire:submit="reassign" class="rounded-lg border border-muted/25 p-3">
                <label class="block text-xs font-medium text-ink">Re-assign</label>
                <select wire:model="reassignFromId" class="mt-1 w-full rounded-lg border border-muted/40 px-2 py-1.5 text-sm focus:border-accent focus:outline-none">
                    <option value="">Dari...</option>
                    @foreach ($assignments as $assignment)
                        <option value="{{ $assignment->user_id }}">{{ $assignment->user->name }}</option>
                    @endforeach
                </select>
                <select wire:model="reassignToId" class="mt-1 w-full rounded-lg border border-muted/40 px-2 py-1.5 text-sm focus:border-accent focus:outline-none">
                    <option value="">Ke...</option>
                    @foreach ($projectMembers as $member)
                        <option value="{{ $member->user_id }}">{{ $member->user->name }}</option>
                    @endforeach
                </select>
                @error('reassignToId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <button type="submit" class="mt-2 rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">Re-assign</button>
            </form>
        </div>

        <button wire:click="delete" wire:confirm="Hapus task ini? Tidak bisa dibatalkan." class="mt-6 text-sm text-red-600 hover:underline">
            Hapus Task
        </button>
    @endif

    <div class="mt-10 grid grid-cols-1 gap-8 md:grid-cols-2">
        <div>
            <h2 class="font-display text-lg font-bold text-ink">Komentar</h2>
            <div class="mt-3 space-y-3">
                @forelse ($comments as $comment)
                    <div class="rounded-lg border border-muted/25 p-3">
                        <p class="text-xs font-medium text-ink">{{ $comment->user->name }}</p>
                        <p class="mt-1 text-sm text-ink">{{ $comment->content }}</p>
                        <p class="mt-1 text-xs text-muted">{{ $comment->created_at->format('d M Y H:i') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-muted">Belum ada komentar.</p>
                @endforelse
            </div>

            <form wire:submit="addComment" class="mt-3 space-y-2">
                <textarea wire:model="commentContent" rows="2" placeholder="Tulis komentar..." class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
                @error('commentContent') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <button type="submit" class="rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">Kirim Komentar</button>
            </form>
        </div>

        <div>
            <h2 class="font-display text-lg font-bold text-ink">Attachment</h2>
            <div class="mt-3 space-y-2">
                @forelse ($attachments as $attachment)
                    <div class="rounded-lg border border-muted/25 p-3 text-sm">
                        @if ($attachment->file_type === 'text')
                            <p class="font-medium text-ink">{{ $attachment->file_name }}</p>
                            <p class="mt-1 text-sm text-ink">{{ $attachment->file_url }}</p>
                        @elseif ($attachment->file_type === 'link')
                            <a href="{{ $attachment->file_url }}" target="_blank" class="font-medium text-ink hover:text-accent">{{ $attachment->file_name }}</a>
                        @else
                            {{-- Task 2.9: real uploaded files are never linked directly to a
                                 public disk URL — always through the access-checked download route. --}}
                            <a href="{{ url('/attachments/'.$attachment->id.'/download') }}" class="font-medium text-ink hover:text-accent">{{ $attachment->file_name }}</a>
                        @endif
                        <p class="mt-1 text-xs text-muted">{{ $attachment->file_type }} &middot; diupload oleh {{ $attachment->uploader->name }}</p>
                    </div>
                @empty
                    <p class="text-sm text-muted">Belum ada attachment.</p>
                @endforelse
            </div>

            @if ($task->status !== 'done')
                <div class="mt-3 rounded-lg border border-muted/25 p-3">
                    <div class="flex gap-2 text-xs">
                        <button type="button" wire:click="$set('attachmentMode', 'file')" class="rounded-lg border px-2 py-1 {{ $attachmentMode === 'file' ? 'border-ink bg-ink text-white' : 'border-muted/40 text-ink' }}">Upload File</button>
                        <button type="button" wire:click="$set('attachmentMode', 'link')" class="rounded-lg border px-2 py-1 {{ $attachmentMode === 'link' ? 'border-ink bg-ink text-white' : 'border-muted/40 text-ink' }}">Tambah Link</button>
                        <button type="button" wire:click="$set('attachmentMode', 'text')" class="rounded-lg border px-2 py-1 {{ $attachmentMode === 'text' ? 'border-ink bg-ink text-white' : 'border-muted/40 text-ink' }}">Tulis Catatan</button>
                    </div>

                    <form wire:submit="addAttachment" class="mt-2 space-y-2">
                        @if ($attachmentMode === 'file')
                            <input type="file" wire:model="attachmentFile" class="w-full text-xs">
                            @error('attachmentFile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        @elseif ($attachmentMode === 'link')
                            <input type="text" wire:model="attachmentLinkLabel" placeholder="Label link (contoh: Link deploy preview)" class="w-full rounded-lg border border-muted/40 px-3 py-1.5 text-sm focus:border-accent focus:outline-none">
                            @error('attachmentLinkLabel') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <input type="url" wire:model="attachmentLinkUrl" placeholder="https://..." class="w-full rounded-lg border border-muted/40 px-3 py-1.5 text-sm focus:border-accent focus:outline-none">
                            @error('attachmentLinkUrl') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        @else
                            <input type="text" wire:model="attachmentTextLabel" placeholder="Label catatan (contoh: Catatan desain)" class="w-full rounded-lg border border-muted/40 px-3 py-1.5 text-sm focus:border-accent focus:outline-none">
                            @error('attachmentTextLabel') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <textarea wire:model="attachmentTextContent" rows="3" placeholder="Tulis catatan di sini..." class="w-full rounded-lg border border-muted/40 px-3 py-1.5 text-sm focus:border-accent focus:outline-none"></textarea>
                            @error('attachmentTextContent') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        @endif
                        <button type="submit" class="rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">Tambah Attachment</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-8">
        <h2 class="font-display text-lg font-bold text-ink">Progress Update</h2>
        <p class="text-xs text-muted">Laporan kerja formal, terpisah dari komentar. Tidak bisa diedit setelah dikirim.</p>

        <div class="mt-3 space-y-3">
            @forelse ($progressUpdates as $update)
                <div class="rounded-lg border border-muted/25 p-3">
                    <p class="text-xs font-medium text-ink">{{ $update->user->name }} &middot; {{ $update->created_at->format('d M Y H:i') }}</p>
                    <p class="mt-1 text-sm text-ink">{{ $update->content }}</p>
                    @if ($update->attachment_url)
                        <a href="{{ $update->attachment_url }}" target="_blank" class="mt-1 block text-xs text-accent hover:underline">{{ $update->attachment_url }}</a>
                    @endif
                </div>
            @empty
                <p class="text-sm text-muted">Belum ada progress update.</p>
            @endforelse
        </div>

        <form wire:submit="addProgressUpdate" class="mt-3 space-y-2">
            <textarea wire:model="progressContent" rows="3" placeholder="Apa yang sudah dikerjakan, kendala yang dihadapi..." class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
            @error('progressContent') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <input type="url" wire:model="progressAttachmentUrl" placeholder="Link pendukung (opsional)" class="w-full rounded-lg border border-muted/40 px-3 py-1.5 text-sm focus:border-accent focus:outline-none">
            @error('progressAttachmentUrl') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <button type="submit" class="rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">Kirim Progress Update</button>
        </form>
    </div>
</div>
