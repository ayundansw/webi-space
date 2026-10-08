# Pemilihan Tech Stack dan Tools — WEBI-SPACE

**Tahapan:** 1.9 dari Fase 1: Inisiasi dan Setup
**Status:** Final

---

## 1. Framework dan Bahasa

**Backend dan Frontend: Laravel (PHP), dengan Livewire + Alpine.js untuk interaktivitas.**

Laravel menangani seluruh logic backend (autentikasi, RBAC, logic Eksplorasi dan Eksekusi, sesuai arsitektur di 1.7). Untuk bagian yang butuh interaktivitas (progress tracker real-time, update status task, dst), pakai Livewire, yang memungkinkan komponen reaktif tanpa perlu membangun API terpisah dan framework JS penuh seperti React/Vue. Alpine.js dipakai untuk interaksi kecil di sisi browser (dropdown, modal, toggle) yang tidak butuh round-trip ke server.

**Alasan:**
- Jalan native di hosting rumahweb (PHP shared hosting), tidak perlu setup tambahan apa pun untuk deploy.
- Sesuai persis dengan environment lokal yang sudah kamu pakai (Laragon, MySQL, phpMyAdmin).
- Claude Code sangat familiar dengan Laravel, minim risiko hasil kode yang aneh atau tidak stabil.
- Livewire menghindari kompleksitas mengelola dua codebase terpisah (backend API + frontend SPA), cocok untuk solo dev.

**Catatan:** kalau nanti fitur Kanban board terasa kaku untuk drag-and-drop pakai Livewire murni, tinggal tambah library kecil seperti SortableJS di sisi Alpine, tidak perlu ganti stack.

---

## 2. Database

**MySQL.**

**Alasan:** bawaan default Laragon dan rumahweb, tidak perlu instalasi tambahan. Notasi tipe data generik di skema ERD (1.7) langsung bisa dipetakan ke tipe MySQL tanpa penyesuaian besar.

---

## 3. Integrasi API WEBI

**Model AI: mulai dari Google Gemini API (free tier).**

**Alasan:** free tier Gemini cukup untuk skala pemakaian WEBI-SPACE (12 anggota, bukan aplikasi publik besar), jadi bisa validasi dulu apakah konsep WEBI ini efektif tanpa keluar biaya di awal. Arsitektur WEBI Service yang sudah dirancang di 1.7 (layer terpisah untuk context assembly dan pemanggilan API) membuat provider ini bisa diganti ke Anthropic atau OpenAI nanti kalau ternyata kualitas atau kebutuhan berubah, tanpa perlu rombak struktur sistem, cukup ganti bagian pemanggilan API-nya saja.

**Mekanisme teknis:** pemanggilan API dilakukan dari backend Laravel (pakai HTTP Client/Guzzle bawaan Laravel), bukan langsung dari browser. Ini penting supaya API key tidak pernah terekspos ke publik lewat kode di sisi client.

**STT/TTS: Web Speech API bawaan browser (gratis).**

**Alasan:** cukup untuk versi awal, tidak perlu biaya tambahan di luar API chat. Kualitas standar tapi fungsional. Bisa upgrade ke layanan STT/TTS berbayar (Google Cloud Speech, dsb) nanti kalau kualitas suara jadi masalah nyata di pemakaian riil.

---

## 4. Tools Pendukung Development

- **Version control:** Git, repository **public** di GitHub. Awalnya direncanakan privat, tapi diubah jadi public saat troubleshooting deployment di tahap 1.10: SSH key untuk clone repo privat gagal berkali-kali (`Permission denied (publickey)`) walau Deploy Key sudah didaftarkan di GitHub, akar masalahnya tidak berhasil dipastikan tanpa akses debug langsung ke server. Repo dipindah ke public supaya clone tidak butuh autentikasi SSH sama sekali, menghilangkan blocker itu — risikonya dianggap kecil karena `.env` tidak pernah ikut ter-push (lihat docs/Catatan_Troubleshooting_Deployment_WEBI-SPACE.md).
- **Deployment:** fitur Git Version Control di cPanel rumahweb (deploy lewat pull dari repo, di-clone langsung ke `public_html/webi-space` karena repo public tidak butuh autentikasi), dengan upload manual via File Manager sebagai fallback kalau fitur itu bermasalah. Domain sudah tersedia di rit-base.online.
- **Local development:** Laragon, sudah mencakup web server, PHP, MySQL, dan phpMyAdmin dalam satu paket, tidak perlu instalasi tambahan.

---

## Catatan Penting

1. **API key wajib disimpan di file `.env`**, jangan pernah di-commit ke GitHub. Repo ini **public**, jadi risiko kebocoran kalau ada kredensial ter-commit jauh lebih besar dan permanen (riwayat git tetap menyimpannya walau commit berikutnya menghapusnya) — jangan pernah taruh key atau kredensial apa pun langsung di kode.
2. Kalau nanti kamu upgrade dari Gemini ke Anthropic/OpenAI API, biaya per pemakaian jadi pertimbangan baru yang perlu dicek langsung ke halaman pricing resmi masing-masing provider saat itu terjadi, karena harga bisa berubah dari waktu ke waktu.
3. Kalau di kemudian hari WEBI-SPACE ternyata melebihi kapasitas shared hosting Unlimited M (traffic tinggi, aplikasi terasa lambat), opsi upgrade ke paket hosting rumahweb yang lebih tinggi tetap tersedia tanpa perlu pindah provider atau migrasi besar, karena stack-nya (PHP/MySQL) didukung di semua tingkatan paket mereka.