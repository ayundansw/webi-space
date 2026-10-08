# Design Token v2 — "Hangat, Playful, Profesional"

Turunan konkret dari `docs/design-brief-v2.md`. Ini MENAMBAH `docs/design-tokens.md`
(token lama), bukan menggantikannya — token lama tetap valid dan masih dipakai
apa adanya di semua halaman yang belum diperbarui. Implementasi CSS ada di
`resources/css/app.css` (`@theme`), sebagai Tailwind v4 theme variable (bukan
hex hardcode tersebar di Blade).

**Status penerapan (2026-07-09):** token ini didefinisikan penuh, tapi BARU
diterapkan ke satu halaman sebagai bukti/template: dashboard Eksplorasi
(`resources/views/livewire/eksplorasi/dashboard.blade.php`) +
`<x-stat-card>` (lewat prop opsional baru, default lama tidak berubah).
Halaman lain (Admin, Eksekusi, Peta Kurikulum, dst) BELUM disentuh — token
lama masih berlaku 100% di situ sampai batch penyebaran berikutnya.

---

## 1. Warna — dipertahankan dari token lama

| Variable | Hex | Peran |
|---|---|---|
| `--color-ink` | `#1C1515` | Teks utama, elemen gelap kecil. |
| `--color-muted` | `#979393` | Teks sekunder, border, disabled. |
| `--color-accent` | `#05D9E7` | CTA, progres, signature (cyan). |
| `--color-accent-soft` | `#D1F8FF` | Section alternatif, highlight lembut. |

Tidak berubah nilainya — brief eksplisit minta pertahankan ini sebagai identitas.

## 2. Warna baru — hangat & kedalaman

| Variable | Hex | Peran & alasan |
|---|---|---|
| `--color-warm` | `#FF7F50` (coral) | Aksen hangat pendukung utama. Coral+cyan/teal adalah pasangan warna yang sudah terbukti harmonis di banyak sistem desain (saling melengkapi di roda warna, sama-sama cerah jadi tidak kalah saing, tapi beda hue jauh jadi tidak bentrok). Dipakai di badge/highlight positif/ilustrasi/CTA sekunder — TIDAK untuk teks body panjang (kontras & prinsip lama tetap berlaku). |
| `--color-warm-soft` | `#FFE8DD` | Tint lembut dari `--color-warm`, pola identik dengan `accent`/`accent-soft` yang sudah ada — background section/badge hangat. |
| `--color-surface` | `#FAF8F6` | Tingkat 1: background HALAMAN (bukan kartu). Off-white sangat halus dengan sedikit kehangatan (bukan abu netral) — ini yang memberi kedalaman: halaman tidak lagi putih polos sama seperti kartu di atasnya. |
| `--color-surface-alt` | `#F3F0EC` | Tingkat 2: lebih dalam dari `--color-surface` — untuk elemen sunken/nested di DALAM kartu (mis. baris berselang, area kode/kutipan). |

**Kenapa TIDAK menyentuh variable `--color-*` yang sudah ada:** supaya halaman yang belum diperbarui (semuanya kecuali dashboard Eksplorasi) tetap terlihat identik — token v2 murni tambahan, tidak ada breaking change di CSS global.

## 3. Warna semantik (didefinisikan, desaturasi ringan)

| Variable | Hex | Peran |
|---|---|---|
| `--color-success` | `#2FA872` | Teks/ikon sukses. |
| `--color-success-soft` | `#E4F5EC` | Background badge/section sukses. |
| `--color-warning` | `#E8A23C` | Teks/ikon warning. |
| `--color-warning-soft` | `#FCF0DC` | Background badge/section warning. |
| `--color-danger` | `#E1594B` | Teks/ikon error. |
| `--color-danger-soft` | `#FBEAE7` | Background badge/section error. |

Sengaja dijaga jarak hue dari `--color-warm` (coral `#FF7F50` vs danger `#E1594B`)
supaya "aksen hangat playful" tidak pernah tertukar makna dengan "ada masalah/error"
di layar yang sama — beda saturasi & kecondongan hue (warm lebih ke oranye cerah,
danger lebih ke merah). Belum diterapkan ke halaman manapun di batch ini (dashboard
Eksplorasi tidak punya UI error/warning) — didefinisikan sekarang supaya batch
berikutnya (mis. Alert Panel Eksekusi/Admin) tinggal pakai token ini, bukan
`red-600`/`amber-50` Tailwind default yang tersebar ad-hoc seperti sekarang.

## 4. Shadow bertingkat

Dinamai `shadow-warm-*` (BUKAN menimpa `shadow-sm`/`shadow-md`/`shadow-lg`
bawaan Tailwind) — supaya halaman lain yang sudah memakai `shadow-sm`/`shadow-lg`
bawaan (unit-show, chat WEBI, ideas index, notifications bell, welcome) tidak
ikut berubah tampilannya. Warna shadow pakai rgb dari `--color-ink`
(`28 21 21`) alih-alih abu netral — ini yang membuat shadow terasa "hangat",
bukan abu dingin generic.

| Variable | Value | Pemakaian |
|---|---|---|
| `--shadow-warm-xs` | `0 1px 2px 0 rgb(28 21 21 / 0.05), 0 1px 1px 0 rgb(28 21 21 / 0.03)` | Kartu biasa saat diam (resting state) — halus, bukan nol. |
| `--shadow-warm-md` | `0 6px 16px -4px rgb(28 21 21 / 0.10), 0 3px 6px -2px rgb(28 21 21 / 0.06)` | Kartu saat hover, atau kartu utama/CTA saat diam. |
| `--shadow-warm-lg` | `0 16px 32px -8px rgb(28 21 21 / 0.16), 0 6px 12px -4px rgb(28 21 21 / 0.08)` | Elemen benar-benar mengambang (modal, dropdown, panel slide-over) — belum dipakai di batch ini. |

## 5. Tipografi — dipertahankan, hierarki dipertegas di pemakaian

Sora/Plus Jakarta Sans/JetBrains Mono TETAP, tidak ada font baru. Yang berubah
cuma DISIPLIN ukuran saat dipakai (mis. judul dashboard lebih besar dari
sebelumnya, angka stat-card besar & tegas) — diterapkan di level Blade/utility
class, bukan token baru.

## 6. Radius & spacing — tidak perlu token baru

Dicek: `rounded-lg` Tailwind v4 default = 8px, `rounded-xl` = 12px — SUDAH
persis sama dengan aturan `docs/design-tokens.md` §5 ("radius ~8px
button/input, ~12px kartu"). Tidak ada token radius baru yang perlu
ditambahkan, konvensi lama sudah benar dan dipakai terus. Spacing juga pakai
skala Tailwind default (sudah lapang secara konvensi) — kedalaman didapat dari
warna/shadow, bukan dari mengubah angka spacing.

## 7. Micro-interaction (pola, bukan token CSS)

Bukan `@theme` variable, tapi pola class yang disepakati untuk kartu
interaktif ke depannya: `transition-all duration-200 hover:-translate-y-0.5
hover:shadow-warm-md`. Dipakai konsisten di kartu manapun yang ingin terasa
"hidup" saat di-hover (diterapkan penuh di dashboard Eksplorasi batch ini).

---

## 8. Rencana penyebaran

Belum diputuskan urutannya — menunggu validasi visual dashboard Eksplorasi
oleh Aye di browser dulu (bisa iterasi kalau arah belum pas). Setelah
disetujui, disebar halaman per halaman ke Eksekusi lalu Admin (paling padat,
playful paling halus, sesuai brief §6), TIDAK sekaligus.
