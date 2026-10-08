# Recon — Skema Referensi/Resources (Persiapan Submission Anggota + Label Sumber)

Laporan ini murni observasi kode & data NYATA saat ini. **Tidak ada kode/skema
yang diubah.**

## 1. Skema tabel Referensi/Resources sekarang

Tabel **`learning_resources`** (`database/migrations/2026_07_02_235331_create_learning_resources_table.php`):

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | uuid, primary | - |
| `module_id` | foreignUuid &rarr; `modules`, `cascadeOnDelete()` | NOT NULL — referensi SELALU terikat ke satu Modul, tidak ada opsi lepas/general (beda dari Forum yang sudah nullable, lihat `RECON_forum.md`) |
| `title` | string | NOT NULL |
| `url` | string | NOT NULL |
| `source_name` | string | NOT NULL |
| `timestamps` | - | - |

**Tidak ada kolom `created_by`/`user_id`/`source` (penanda pembuat) sama sekali.** Cuma 4 kolom data non-timestamp: modul, judul, URL, dan `source_name`.

**Temuan penting soal `source_name`:** nama kolomnya menyiratkan "nama sumber" singkat (mis. "Mozilla", "roadmap.sh"), TAPI dari data nyata (`CurriculumSeeder.php`), kolom ini justru dipakai untuk **kalimat deskripsi panjang**, bukan label singkat:
```php
LearningResource::create([
    'module_id' => $module->id,
    'title' => 'MDN Web Docs (Mozilla)',
    'url' => 'https://developer.mozilla.org/en-US/docs/Learn_web_development/Getting_started/Web_standards/How_the_web_works',
    'source_name' => 'Dokumentasi resmi dari Mozilla yang menjelaskan cara kerja web, termasuk client, server, dan cara file website dikirim. Ini rujukan tepercaya yang dipakai developer di seluruh dunia.',
]);
```
Jadi **kolom `source_name` sekarang secara de facto berfungsi sebagai field deskripsi**, bukan atribusi sumber singkat. Ini penting untuk rencana v2.0 — TIDAK ADA kolom "deskripsi" terpisah yang bisa dipakai member untuk menjelaskan referensinya; `source_name` sudah "dipakai" untuk itu di data existing.

Model `App\Models\LearningResource` (`app/Models/LearningResource.php`): cuma relasi `module()` (`belongsTo`), tidak ada atribut/accessor tambahan. `$fillable`: `['module_id', 'title', 'url', 'source_name']`.

## 2. Class Livewire + view

| Fungsi | Class | View | Route |
|---|---|---|---|
| Daftar referensi | `App\Livewire\Eksplorasi\Resources\Index` | `resources/views/livewire/eksplorasi/resources/index.blade.php` | `GET /eksplorasi/resources` (nama `eksplorasi.resources`) |

**Bagaimana ditampilkan:** dikelompokkan PER MODUL (`Module::orderBy('order_number')->with('learningResources')->get()`) — satu section per modul, daftar referensi di dalamnya (atau pesan "Belum ada referensi tambahan untuk modul ini" kalau kosong). Setiap item: link `title` (buka tab baru), lalu `source_name` ditampilkan sebagai teks kecil di sebelah judul (`<span class="font-mono text-xs text-muted"> &middot; {{ $resource->source_name }}</span>`) — cocok untuk source-name PENDEK, tapi karena isinya kalimat panjang (lihat poin 1), tampilannya jadi baris deskripsi panjang nempel di ujung judul, bukan label singkat yang rapi.

Tidak ada halaman terpisah untuk resource per-modul — semua modul ditampilkan sekaligus di satu halaman `/eksplorasi/resources` (bukan halaman detail modul).

## 3. Bagaimana referensi dibuat sekarang

**HANYA lewat seeder — tidak ada UI sama sekali** (admin maupun member). Digrep `LearningResource::create` di seluruh kode — cuma muncul di 2 file: `database/seeders/CurriculumSeeder.php` (data kurikulum produksi) dan `database/seeders/ExplorationSampleSeeder.php` (data sample untuk test). Tidak ada satu pun Livewire component (admin atau member) yang menulis ke tabel ini — `Resources\Index` cuma `render()`, murni read-only.

## 4. Migrasi yang dibutuhkan (tidak ada kolom pembeda sumber)

**Migrasi BARU wajib** — tidak ada nullable/opsional apa pun untuk pembeda sumber di skema sekarang. Kolom yang perlu ditambahkan (additive, aman untuk data existing):
- `created_by` (foreignUuid &rarr; `users`, nullable) — **NULLABLE wajib**, karena semua baris `learning_resources` existing (dari `CurriculumSeeder`) tidak punya pembuat member manapun; kalau `created_by` NULL diinterpretasikan sebagai "Dari Admin"/konten kurikulum resmi, itu otomatis benar untuk seluruh data lama tanpa backfill.
- Kemungkinan besar juga butuh kolom **deskripsi terpisah** (mis. `description`, text, nullable) kalau mau memisahkan "nama sumber singkat" dari "penjelasan panjang" — supaya `source_name` bisa dikembalikan ke makna aslinya (label pendek) sementara deskripsi panjang existing dipindah ke kolom baru ini (perlu keputusan produk: apakah data `source_name` LAMA dimigrasi otomatis jadi `description` baru, atau dibiarkan apa adanya dan cuma berlaku untuk entri baru).
- Kalau member bisa submit sendiri: kemungkinan perlu kolom status moderasi (mis. `status` enum `pending`/`approved`, ATAU flag boolean sederhana) TERGANTUNG apakah "tayang otomatis" (sesuai konteks task ini: "tayang otomatis") berarti tanpa moderasi sama sekali — kalau memang tanpa moderasi, kolom status TIDAK diperlukan, cukup `created_by` saja untuk label sumber.

**Risiko terhadap data existing: RENDAH** — semua penambahan kolom bersifat ADDITIVE + NULLABLE, tidak mengubah/menghapus kolom yang sudah ada. Baris lama otomatis dapat `created_by = NULL` (berarti "Dari Admin") tanpa backfill manual.

## 5. Test yang menyentuh Referensi/Resources

Cuma **1 test**, di file yang sama dengan Forum (`tests/Feature/Exploration/ResourcesAndForumTest.php`):

```php
public function test_resources_page_lists_resources_per_module_regardless_of_lock_status(): void
{
    $user = $this->member();
    // module B is locked for a brand-new user, but its resource should still be visible
    $this->actingAs($user)->get('/eksplorasi/resources')
        ->assertOk()
        ->assertSee('roadmap.sh')
        ->assertSee('MDN Web Docs');
}
```
Cuma menguji halaman tampil `assertSee` judul-judul resource seed — TIDAK ada assersi terkait kolom pembeda sumber atau data lain (wajar, karena kolomnya memang belum ada). Test ini AMAN, tidak akan pecah oleh migrasi kolom baru (murni `assertSee` teks yang sudah ada, tidak bergantung struktur baru).

## 6. Penilaian

**(a) Member submit sendiri** — pekerjaan SEDANG, bukan kecil:
- Perlu migrasi kolom baru (poin 4).
- Perlu Livewire component BARU untuk form submit (belum ada component tulis sama sekali untuk resource — beda dari Forum yang formnya sudah ada, di sini semuanya dari nol).
- Perlu keputusan: submit per-modul (pilih modul dari dropdown, sama seperti pola Forum) atau di halaman modul tertentu langsung?
- Perlu validasi URL (format `url` Laravel rule minimal) — sekarang tidak ada validasi sama sekali karena tidak ada form.

**(b) Label sumber "Dari Anggota"/"Dari Admin"** — pekerjaan KECIL setelah (a) selesai:
- Cuma butuh `@if ($resource->created_by) Dari Anggota @else Dari Admin @endif` (atau badge serupa) di `resources/index.blade.php` — pola identik dengan badge target Forum (`peer`/`pic`) yang sudah ada sebagai referensi visual.
- Tidak butuh logic tambahan di luar kolom `created_by` itu sendiri.

**(c) Admin bisa hapus** — pekerjaan KECIL:
- Perlu tombol/aksi hapus + otorisasi (cek `Auth::user()->role === 'admin'`, pola yang sudah konsisten dipakai di halaman lain seperti `Projects\Index`).
- Tidak ada kerumitan tambahan — `LearningResource::delete()` biasa, tidak ada relasi anak yang cascade-sensitive (tidak ada tabel lain yang `belongsTo` ke `learning_resources`).

**Ringkasan skala keseluruhan:** modul ini paling "dari nol" dibanding Forum (yang sudah punya form + Livewire component, cuma perlu penyesuaian nullable) — Resources butuh migrasi BARU + Livewire component BARU + form BARU dari nol, karena sekarang cuma halaman baca statis dari data seeder.
