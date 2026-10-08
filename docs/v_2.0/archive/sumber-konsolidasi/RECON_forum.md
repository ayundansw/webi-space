# Recon — Forum Eksplorasi v1.0 (Persiapan Thread Opsional-Modul)

Laporan ini murni observasi kode & skema NYATA saat ini. **Tidak ada kode/skema
yang diubah.**

## 1. Skema tabel Forum/Thread sekarang

**`forum_threads`** (`database/migrations/2026_07_02_235330_create_forum_threads_table.php`):

| Kolom | Tipe | Wajib? |
|---|---|---|
| `id` | uuid, primary | - |
| `module_id` | foreignUuid &rarr; `modules`, `nullOnDelete()` | **NULLABLE** |
| `unit_id` | foreignUuid &rarr; `units`, `nullOnDelete()` | **NULLABLE** |
| `created_by` | foreignUuid &rarr; `users`, `restrictOnDelete()` | NOT NULL |
| `title` | string | NOT NULL |
| `content` | text | NOT NULL |
| `target` | enum(`peer`, `pic`) | NOT NULL |
| `timestamps` | - | - |

**Temuan kunci: `module_id` dan `unit_id` SUDAH NULLABLE di skema sejak migrasi awal (v1.0)**, bukan NOT NULL seperti yang mungkin diasumsikan. Skema database TIDAK PERNAH memaksa thread terikat ke modul/unit — pembatasan itu murni ada di lapisan aplikasi (lihat poin 3).

**`forum_replies`** (`database/migrations/2026_07_02_235331_create_forum_replies_table.php`): `id`, `thread_id` (NOT NULL, `cascadeOnDelete`), `user_id` (NOT NULL, `restrictOnDelete`), `content` (NOT NULL), timestamps. Tidak relevan untuk perubahan ini — reply tidak punya relasi modul/unit sama sekali.

Model `App\Models\ForumThread` (`app/Models/ForumThread.php`): relasi `module()` dan `unit()` keduanya `belongsTo`, tidak ada validasi/aturan tambahan di level model. `App\Models\ForumReply`: relasi `thread()` dan `user()`.

## 2. Class Livewire + view

| Fungsi | Class | View | Route |
|---|---|---|---|
| Daftar thread | `App\Livewire\Eksplorasi\Forum\Index` | `resources/views/livewire/eksplorasi/forum/index.blade.php` | `GET /eksplorasi/forum` (nama `eksplorasi.forum.index`) |
| Buat thread | `App\Livewire\Eksplorasi\Forum\Create` | `resources/views/livewire/eksplorasi/forum/create.blade.php` | `GET /eksplorasi/forum/create` (nama `eksplorasi.forum.create`) |
| Detail + balas | `App\Livewire\Eksplorasi\Forum\Show` | `resources/views/livewire/eksplorasi/forum/show.blade.php` | `GET /eksplorasi/forum/{thread}` (nama `eksplorasi.forum.show`) |

Ketiga route ada dalam satu grup middleware `['auth', 'role:exploration_member,admin']` (`routes/web.php` baris 69-73) — `execution_member` ditolak (dikonfirmasi test `test_member_and_admin_can_reply_to_thread_but_execution_member_cannot_access`).

**Bagaimana thread ditampilkan:** `Index::render()` mengambil **SEMUA thread digabung jadi satu daftar** (`ForumThread::with([...])->latest()->get()`), TIDAK dikelompokkan per-unit atau per-modul. Modul/unit yang terkait ditampilkan sebagai metadata baris (`@if ($thread->module) ... Modul {{ $thread->module->order_number }} ... @endif`, `@if ($thread->unit) ... Unit: {{ $thread->unit->title }} @endif`) — kedua kondisi ini **sudah** ditulis dengan `@if`, artinya blade sudah siap menampilkan thread tanpa modul/unit sama sekali (baris metadata itu cuma tidak muncul), tanpa perlu perubahan tampilan tambahan untuk kasus "general".

## 3. Bagaimana thread baru dibuat sekarang

Form `Create` (`app/Livewire/Eksplorasi/Forum/Create.php`) minta: `title`, `content`, `target` (peer/pic), `moduleId` (opsional secara validasi), `unitId` (opsional secara validasi).

**Validasi Livewire murni:**
```php
'moduleId' => ['nullable', 'uuid'],
'unitId' => ['nullable', 'uuid'],
```
— keduanya memang `nullable` di level rule Laravel.

**TAPI ada pengecekan tambahan MANUAL setelah validasi** (baris 36-40):
```php
if (empty($validated['moduleId']) && empty($validated['unitId'])) {
    $this->addError('moduleId', 'Pilih dulu modul atau unit yang mau kamu bahas.');
    return;
}
```
Ini yang **memaksa user WAJIB memilih setidaknya salah satu** (modul ATAU unit) — bukan aturan Laravel validation, tapi kondisional eksplisit yang ditulis sendiri di method `save()`. View form (`create.blade.php`) sendiri sudah punya UI dua `<select>` terpisah dengan opsi kosong (`-- Pilih Modul --`, `-- Pilih Unit (opsional) --`) — secara visual sudah TERLIHAT seolah unit itu opsional (dan modul kelihatannya wajib), tapi realitanya backend mensyaratkan salah satu dari keduanya harus terisi, error muncul di field `moduleId` bahkan kalau yang kosong itu justru modul sementara unit terisi (errornya sedikit menyesatkan kalau usernya cuma isi unit tapi lupa isi modul — tapi karena keduanya sama-sama valid alternatif, ini tidak pernah ke-trigger kalau salah satu terisi).

## 4. Data existing & risiko migrasi

- Skema **sudah nullable sejak awal** — TIDAK ADA migrasi kolom yang perlu dijalankan untuk mengizinkan `module_id`/`unit_id` kosong.
- Grep `ForumThread` di `database/seeders/` — **nihil**. Tidak ada seeder (termasuk `ExplorationSampleSeeder` yang dipakai test) yang membuat baris `forum_threads` sama sekali.
- Baris `forum_threads` HANYA pernah dibuat lewat test (`ResourcesAndForumTest`, dibuat & dihapus tiap test run lewat `RefreshDatabase`) — tidak ada data produksi/seed yang perlu dikhawatirkan.
- **Kesimpulan: risiko migrasi/data = NOL**, karena tidak ada migrasi skema yang diperlukan sama sekali.

## 5. Test yang menyentuh Forum

File: `tests/Feature/Exploration/ResourcesAndForumTest.php` (juga menguji halaman Resources yang tidak terkait forum, di file yang sama).

| Test | Yang diuji |
|---|---|
| `test_exploration_member_can_create_thread_scoped_to_a_module` | Thread berhasil dibuat dengan `moduleId` terisi, `unit_id` tidak diisi — membuktikan unit memang sudah opsional selama modul terisi. |
| `test_thread_without_module_or_unit_is_rejected` | **Test yang PERLU DIUBAH kalau general thread mau diizinkan** — saat ini secara eksplisit menegaskan thread tanpa modul MAUPUN unit ditolak (`assertHasErrors('moduleId')`, `assertDatabaseCount('forum_threads', 0)`). Ini persis mengunci perilaku "wajib pilih salah satu" yang ditemukan di poin 3. |
| `test_member_and_admin_can_reply_to_thread_but_execution_member_cannot_access` | RBAC balas thread + akses ditolak untuk `execution_member`. Tidak terkait modul/unit, aman. |
| `test_resources_page_lists_resources_per_module_regardless_of_lock_status` | Halaman Resources, bukan Forum — tidak relevan untuk perubahan ini. |

Tidak ada test lain yang menyentuh `ForumThread`/`ForumReply` di luar file ini (dikonfirmasi lewat glob `tests/**/*Forum*` — cuma satu file ditemukan).

## 6. Penilaian

- **TIDAK BUTUH MIGRASI.** `module_id` dan `unit_id` sudah `nullable` di skema sejak v1.0 — mengizinkan thread general/bebas modul murni perubahan LOGIKA APLIKASI, bukan skema.
- **Perubahan yang dibutuhkan sangat kecil, terlokalisir di 2 tempat:**
  1. `App\Livewire\Eksplorasi\Forum\Create::save()` — hapus/ubah blok pengecekan manual baris 36-40 yang memaksa salah satu dari `moduleId`/`unitId` wajib terisi. Kemungkinan diganti jadi opsi ketiga eksplisit di UI (mis. toggle/radio "Terikat modul/unit" vs "Diskusi umum") alih-alih membiarkan user diam-diam kosongkan keduanya tanpa sadar.
  2. View `create.blade.php` — perlu penyesuaian microcopy/label supaya jelas bagi user bahwa mengosongkan modul & unit itu VALID (opsi "diskusi umum"), bukan lupa mengisi. Saat ini label "-- Pilih Modul --" tanpa keterangan "(opsional)" seperti yang sudah ada di label unit — bisa menyesatkan kalau modul dibiarkan tetap terlihat wajib padahal sekarang boleh dikosongkan juga.
- **View `index.blade.php` dan `show.blade.php` TIDAK BUTUH perubahan** — keduanya sudah pakai `@if ($thread->module)` / `@if ($thread->unit)` kondisional, otomatis menangani kasus thread tanpa modul/unit tanpa perlu disentuh.
- **1 test existing perlu diperbarui** (`test_thread_without_module_or_unit_is_rejected`) karena namanya sendiri secara eksplisit menegaskan perilaku LAMA yang akan dibalik — bukan dihapus diam-diam, tapi diganti jadi test yang membuktikan thread general BERHASIL dibuat (dan idealnya test baru untuk kombinasi: general, modul-saja, modul+unit, semuanya valid).
- Tidak ada dampak ke `ForumReply`, RBAC, atau notifikasi (`forum_reply_received`) — ketiganya tidak bergantung pada ada/tidaknya `module_id`/`unit_id`.
