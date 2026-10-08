# Spec — Struktur `content_blocks` (2.2.4a)

Turunan konkret dari `docs/v_2.0/Rancangan_Arsitektur_Konten_Dinamis_v2.md`.
Skema tabel final ada di migrasi
`database/migrations/2026_07_09_000001_create_content_blocks_table.php`, model
di `app/Models/ContentBlock.php`. Dokumen ini mendefinisikan bentuk kolom
`content` (json) per `type`, dan bagaimana tiap tipe dirender dengan aman.

**Status:** fondasi sistem (2.2.4a) — belum ada satu pun unit produksi yang
memakai ini. `units.content` (teks polos lama) tetap jadi sumber unit-show
sampai migrasi manual per unit (batch terpisah) selesai.

**Perubahan dari rancangan asli:** rancangan awal menyebut 8 tipe blok tanpa
Tabel. Recon (`RECON_konten_dinamis.md` §B.6) menemukan 28 kemunculan tabel
markdown/`[SAJIKAN: tabel perbandingan]` di 67 unit lama — cukup signifikan
untuk dapat tipe blok sendiri alih-alih dipaksa masuk Custom HTML. Final: 9
tipe. Heading juga dipersempit dari rancangan awal (1-6) jadi **level 1-3**
(keputusan eksplisit batch ini) — materi unit tidak butuh heading sedalam
dokumen H4-H6, dan renderer meng-clamp nilai di luar rentang itu ke batas
terdekat sebagai jaga-jaga, bukan menolak/error.

## Tipe & struktur `content`

| Type (enum) | Struktur `content` (json) | Catatan |
|---|---|---|
| `heading` | `{level: 1-3, text: string}` | `level` di-clamp ke 1-3 saat render kalau data di luar rentang. |
| `text` | `{markdown: string}` | Disimpan sebagai markdown SUMBER, bukan HTML — dirender aman saat tampil (lihat "Sanitasi" di bawah), sama seperti pesan WEBI disimpan sebagai teks lalu dirender lewat CommonMark saat tampil, bukan HTML pre-rendered yang disimpan di DB. |
| `image` | `{url: string, alt: string, caption?: string}` | `url` EKSTERNAL (Cloudinary/imgur/dst) — tidak ada upload file, sesuai rancangan §2. |
| `callout` | `{variant: 'info'\|'tip'\|'warning', title?: string, body: string}` | `body` markdown, aturan sanitasi sama seperti `text`. `variant` di luar 3 nilai ini jatuh ke tampilan `info` sebagai default aman. |
| `code` | `{language?: string, code: string}` | `code` SELALU dirender sebagai teks literal (escape penuh), tidak pernah lewat parser markdown/HTML — lihat "Kenapa tidak ada syntax highlighting" di bawah. |
| `video` | `{url: string, caption?: string}` | Tidak ada field `provider` terpisah — provider (YouTube/Vimeo) dideteksi otomatis dari `url` saat render (lihat detail di bawah), supaya data yang disimpan tidak bisa "bohong" soal provider-nya sendiri. |
| `list` | `{style: 'ordered'\|'unordered', items: string[]}` | Tiap item markdown, sama aturan sanitasi seperti `text`. |
| `table` | `{headers: string[], rows: string[][]}` | Sel TIDAK mendukung markdown (teks polos, di-escape) — keputusan simplifikasi batch ini, lihat "Keputusan kecil" di laporan chat. |
| `custom_html` | `{html: string}` | Escape hatch. HTML mentah tapi WAJIB disanitasi saat render (lihat bawah) — INI PERUBAHAN dari rancangan asli §5 yang bilang "dirender apa adanya"; batch ini secara eksplisit mengetatkannya jadi selalu disanitasi, sebagai keputusan keamanan tambahan yang diminta terpisah dari rancangan awal. |

## Sanitasi (anti-XSS)

Dua mekanisme berbeda, dipakai sesuai jenis field:

### 1. Markdown (`text.markdown`, `callout.body`, tiap item `list.items`)

`App\Services\Content\SafeMarkdown::toHtml()` — pembungkus tipis di atas
`Illuminate\Support\Str::markdown()` dengan opsi PERSIS sama dengan
`App\Services\Webi\MessageRenderer::toSafeHtml()` (dipakai untuk balasan
WEBI sejak 2.5):
```php
Str::markdown($markdown, [
    'html_input' => 'escape',       // literal HTML di sumber -> entity, tidak pernah dieksekusi
    'allow_unsafe_links' => false,  // menolak javascript:/data: sebagai href
]);
```
Konten blok ditulis admin (dipercaya lebih dari balasan AI), tapi aturan
yang sama tetap dipakai — satu standar keamanan konsisten di seluruh
aplikasi untuk "teks yang berasal dari markdown", bukan aturan berbeda
tergantung siapa penulisnya.

### 2. Custom HTML (`custom_html.html`)

`App\Services\Content\HtmlSanitizer::sanitize()` — sanitizer allowlist
custom (bukan library pihak ketiga — lihat "Kenapa tidak pakai library" di
bawah), jalan lewat `DOMDocument`:
- Tag berbahaya **dihapus total beserta isinya**: `script`, `style`,
  `iframe`, `object`, `embed`, `form`, `input`, `button`, `textarea`,
  `select`, `link`, `meta`, `base`.
- Semua atribut event handler (`on*` — `onclick`, `onerror`, `onload`, dst)
  **dihapus** dari tag manapun yang tersisa.
- Atribut `href`/`src`/`action`/`formaction` dengan skema `javascript:`
  atau `vbscript:` **dihapus**. Skema `data:` dihapus KECUALI
  `data:image/...` (gambar inline kecil tetap diizinkan, `data:text/html`
  dkk tidak).
- Tag/atribut lain (termasuk `class`, `style` presentasional, `div`,
  `span`, tabel manual, dst) dibiarkan apa adanya — escape hatch ini tetap
  berguna untuk visual custom, cuma jalur eksekusi kode (script + event
  handler + skema URL berbahaya) yang ditutup.

**Kenapa tidak pakai library sanitizer pihak ketiga (mis. HTMLPurifier):**
`composer.json` proyek ini sengaja minim dependency (lihat pola yang sama
di keputusan-keputusan sebelumnya — DB_TIMEZONE manual, tanpa vector-store
package, dst). Allowlist tag+atribut berbahaya di atas cukup untuk model
ancaman escape-hatch ini (satu-satunya penulis adalah admin yang sudah
punya akses penuh ke kode aplikasi lewat Claude Code — risiko yang ingin
ditutup di sini adalah SALAH KETIK/COPY-PASTE snippet dari sumber tidak
tepercaya, bukan admin yang sengaja jahat ke aplikasinya sendiri), dan
diuji eksplisit lewat test XSS (lihat laporan chat). Kalau nanti kebutuhan
custom_html berkembang jauh lebih kompleks, `HtmlSanitizer` ini satu titik
yang bisa diganti isinya ke library sungguhan tanpa mengubah pemanggilnya.

## Video: deteksi provider dari `url`

Bukan field `provider` terpisah yang dipercaya dari data — saat render,
`url` dicocokkan ke pola YouTube (`youtube.com/watch?v=`, `youtu.be/`) atau
Vimeo (`vimeo.com/<id>`). Kalau cocok, ID diekstrak dan iframe embed
DIBANGUN SENDIRI oleh renderer (`https://www.youtube.com/embed/<id>`,
`https://player.vimeo.com/video/<id>`) — bukan `url` mentah yang langsung
dipasang sebagai `iframe src`. Ini mencegah admin (sengaja atau tidak
sengaja) menaruh iframe ke domain sembarang. Kalau `url` tidak cocok pola
manapun, renderer jatuh ke tautan biasa ("Tonton video →") alih-alih iframe.

## Kenapa tidak ada syntax highlighting sungguhan di blok Kode

Rancangan menyebut "syntax highlighting sesuai `language`". Batch ini
merender kode sebagai `<pre><code>` monospace polos (escape penuh) TANPA
highlighting warna sungguhan — highlighting butuh library JS baru
(Prism.js/highlight.js) yang belum ada di `package.json`, dan menambahkannya
di luar scope "fondasi sistem" batch ini. `language` tetap disimpan dan
ditampilkan sebagai label kecil di atas blok kode, jadi begitu highlighting
ditambahkan nanti, datanya sudah siap tanpa perlu migrasi ulang.
