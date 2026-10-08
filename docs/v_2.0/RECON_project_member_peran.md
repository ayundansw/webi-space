# Recon — Skema ProjectMember (Persiapan "Peran per Proyek" di Profil)

Laporan ini murni observasi kode & skema NYATA saat ini. **Tidak ada kode/skema
yang diubah.**

## 1. Skema tabel `project_members`

`database/migrations/2026_07_02_235640_create_project_members_table.php`:

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | uuid, primary | - |
| `project_id` | foreignUuid &rarr; `projects`, `cascadeOnDelete()` | NOT NULL |
| `user_id` | foreignUuid &rarr; `users`, `restrictOnDelete()` | NOT NULL |
| `joined_at` | timestamp, nullable | dipetakan sebagai `CREATED_AT` custom di model |

Unique constraint gabungan `['project_id', 'user_id']` — satu user cuma bisa jadi anggota satu proyek sekali (tidak ada baris duplikat/riwayat keanggotaan berulang).

**Tidak ada kolom peran/role/jabatan/position sama sekali.** Cuma 3 kolom data (di luar `id`): siapa, di proyek mana, kapan bergabung.

## 2. Bagaimana "peran" ditentukan sekarang

**Sama sekali tidak dilacak di mana pun.** Digrep seluruh `docs/v_2.0/archive/sumber-konsolidasi/struktur-eksekusi.md` untuk kata "peran"/"jabatan"/"position" — nihil, dokumen itu sendiri tidak pernah merancang konsep peran per anggota-proyek. Tidak ada tabel lain yang bisa jadi proxy:
- `task_assignments` (`database/migrations/*_create_task_assignments_table.php`) — cuma `task_id`, `user_id`, `assigned_by`, `assigned_at`. Tidak ada kolom peran di sini juga.
- `Task` sendiri tidak punya kategori/tag by-skill yang bisa disimpulkan jadi "peran" seseorang (cuma `title`, `description`, `status`, `priority`, `deadline`, `milestone_id`).
- Tidak ada tabel/field lain di seluruh skema (`docs/arsitektur-database.md`) yang menyimpan sesuatu seperti "jabatan"/"spesialisasi" anggota, baik global maupun per-proyek.

Kesimpulan: kalau sekarang ingin ditampilkan "Ahmad adalah Frontend Lead di Proyek X", jawabannya jujur **tidak bisa** — datanya tidak pernah diinput/disimpan di mana pun, bukan cuma belum ditampilkan.

## 3. Model `ProjectMember`

`app/Models/ProjectMember.php`:
```php
#[Fillable(['project_id', 'user_id'])]
class ProjectMember extends Model
{
    use HasUuids;
    const CREATED_AT = 'joined_at';
    const UPDATED_AT = null;

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
```
Cuma dua relasi (`project`, `user`), tidak ada atribut/accessor tambahan. `$fillable` cuma `project_id` dan `user_id` — bahkan kalau kolom baru ditambah ke migrasi, model ini juga perlu diupdate `$fillable`-nya supaya bisa diisi lewat `create()`.

**Cara membuatnya juga cuma satu jalur**: `App\Services\Execution\ProjectService::addMember(Project $project, User $member, User $admin)` — method inilah satu-satunya tempat baris `ProjectMember` dibuat di aplikasi (`ProjectMember::create(['project_id' => ..., 'user_id' => ...])`, baris 59-62). Kalau kolom peran ditambah, method inilah yang perlu diubah signature-nya untuk menerima input peran.

## 4. Test yang menyentuh `ProjectMember`

Digrep di seluruh `tests/` — `ProjectMember::create()` dipanggil di **10 file test** (`MemberDashboardTest`, `AttachmentDownloadTest`, `AlertAndDashboardTest`, `TaskManagementTest`, `ProjectManagementTest`, `TaskCollaborationTest`, `RouteAccessMatrixTest`, dan lainnya) — **total puluhan pemanggilan**, TAPI **semuanya, tanpa kecuali, cuma mengisi `project_id` dan `user_id`**. Tidak ada satu pun test yang mengasumsikan atau menguji field peran/role/posisi apa pun. Ini konsisten dengan temuan poin 1-3: konsepnya memang belum pernah ada, bukan cuma tidak sengaja tidak dites.

## 5. Penilaian

**Field peran BELUM ADA — perlu migrasi kolom baru.** Ini bukan cuma perubahan tampilan, ada 4 bagian:

1. **Migrasi skema** (baru, additive): tambah kolom nullable (mis. `role` atau `project_role`, string) ke `project_members`. Nullable supaya baris `ProjectMember` yang sudah ada (dan skenario "belum diisi admin") tetap valid tanpa backfill wajib.
2. **Model**: tambah kolom baru ke `$fillable` di `ProjectMember`.
3. **Service**: `ProjectService::addMember()` perlu terima parameter peran tambahan (opsional atau wajib — keputusan produk), diteruskan ke `ProjectMember::create()`.
4. **UI admin**: form assign-anggota-ke-proyek (tempat `addMember()` dipanggil dari Livewire component — belum dicek nama filenya di recon ini, tapi searchable dari pemanggil `ProjectService::addMember`) perlu field input peran (teks bebas kemungkinan paling sederhana, atau dropdown kalau ingin daftar peran baku/terstruktur — keputusan produk juga).
5. **UI Profil** (tujuan akhir fitur ini): perlu query baru yang join `ProjectMember` per user, ambil kolom peran + nama proyek, tampilkan sebagai daftar portofolio.

**Risiko terhadap data yang sudah ada: RENDAH.** Karena kolom baru ditambah sebagai `nullable` (bukan mengubah/menghapus kolom existing), migrasi murni ADDITIVE — baris `ProjectMember` lama otomatis dapat nilai `NULL` untuk peran (ditampilkan sebagai "belum diisi" di UI Profil, bukan error). Tidak ada risiko kehilangan data, tidak perlu backfill sebelum migrasi bisa jalan. Satu-satunya keputusan produk yang perlu dikonfirmasi lebih dulu: apakah peran itu **teks bebas** (fleksibel, tapi tidak konsisten ejaan antar-admin, mis. "FE Lead" vs "Frontend Lead") atau **enum/daftar tetap** (konsisten, tapi butuh daftar peran dikunci dulu) — ini keputusan skema yang butuh konfirmasi eksplisit sebelum migrasi ditulis, sesuai aturan proyek soal perubahan skema.
