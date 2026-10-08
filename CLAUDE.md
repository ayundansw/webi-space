# WEBI-SPACE

Web app ekosistem Divisi Web Development RIT. Tiga modul: Eksplorasi (LMS), 
Eksekusi (manajemen proyek), WEBI (AI companion).

## Tech stack
Laravel + Livewire + Alpine.js, MySQL. Detail lengkap: docs/tech-stack.md

## Dokumentasi wajib dibaca sebelum kerjakan fitur
- Requirement dan business rules: docs/PRD.md
- Skema database dan relasi: docs/arsitektur-database.md
- Konteks modul spesifik: docs/kurikulum-eksplorasi.md, docs/struktur-eksekusi.md, 
  atau docs/spesifikasi-webi.md, sesuai modul yang sedang dikerjakan
- Aturan desain dan visual: docs/design-tokens.md (WAJIB dibaca sebelum generate UI apa pun)

## Aturan wajib
- Jangan ubah struktur skema database yang sudah ada di docs/arsitektur-database.md 
  tanpa konfirmasi eksplisit ke aku dulu.
- Jangan generate logic yang bertentangan dengan business rules di docs/PRD.md 
  (khususnya soal RBAC per role, dan guardrail evaluasi WEBI).
- Ikuti konvensi penamaan dan struktur yang sudah ada di kode, jangan bikin pola baru 
  tanpa alasan jelas.


## Known gaps / backlog (dari 2.3, belum ada nomor task)
- **Modul 6, intermezo**: dokumen meminta submission tugas lewat upload file sungguhan
  ("Tugas praktik dengan pengumpulan file", tombol "Kumpulkan"). Saat ini disimpan
  sebagai teks deskripsi di `Checkpoint.intermezo_questions`, belum ada mekanisme
  upload file di manapun di aplikasi. Perlu masuk task tersendiri kalau mau dibangun.
- **Unit 5.8** (tipe evaluasi campuran kuis+esai): sudah bisa dituntaskan dengan benar
  (esai wajib diisi lewat textarea, tidak ikut dinilai benar/salah, tidak bisa
  diselesaikan hanya dengan menjawab 2 soal pilihan ganda) — dibetulkan di
  `App\Livewire\Eksplorasi\UnitEvaluation` setelah 2.3. Yang masih jadi utang: belum
  ada UI evaluasi gabungan yang lebih rapi untuk tipe campuran ini, masih pakai
  tampilan quiz biasa + textarea tambahan.

## Known gaps / backlog (dari 2.4)
- **Tidak ada mekanisme "wewenang buat task" untuk anggota eksekusi.** docs/struktur-eksekusi.md
  Tahap 4 bilang task boleh dibuat "Admin, atau anggota eksekusi yang diberi wewenang
  oleh admin", tapi tidak ada field di ProjectMember (atau tabel manapun) yang
  menyimpan wewenang itu. Sementara: SEMUA anggota yang jadi ProjectMember di proyek
  itu boleh buat task (plus admin). Kalau butuh granularitas per-anggota, perlu field
  baru di ProjectMember (skema, butuh konfirmasi eksplisit).
  Lihat `App\Livewire\Eksekusi\Tasks\Create` docblock.
- **Notifikasi comment_from_admin vs comment_on_my_task tumpang tindih.** Lampiran B
  docs/struktur-eksekusi.md mendefinisikan dua notifikasi untuk kejadian yang sama
  (komentar admin ter-cover di keduanya). Diselesaikan dengan mengirim SALAH SATU saja
  (comment_from_admin kalau penulisnya admin, comment_on_my_task kalau bukan) supaya
  tidak dobel notifikasi untuk satu komentar. Lihat `TaskService::addComment()`.
- **Utang teknis: dedup notifikasi INACTIVE MEMBER pakai pencocokan teks pesan
  (LIKE %nama%), bukan context_id.** Skema `notifications.context_type` tidak punya
  varian "user" (cuma project/task/unit/checkpoint/module/forum_thread/none), jadi
  tidak ada cara resmi menandai notifikasi ini milik anggota tertentu selain
  mencocokkan nama di teks pesan. Cukup jalan untuk tim kecil (3 anggota, nama
  beda-beda), tapi rapuh kalau ada nama anggota yang tumpang tindih sebagai
  substring nama lain, dan tidak scalable kalau tim membesar. Kalau mau
  diperbaiki, perlu tambah varian "user" ke `context_type` enum (skema, butuh
  konfirmasi eksplisit). Lihat `AlertService::notifyMemberAlertOnce()`.
- **Alert/notifikasi "pertama kali muncul" hanya dicek sekali seumur hidup per
  task/anggota** (bukan per episode kemunculan ulang) — karena tidak ada tabel status
  flag terpisah untuk tahu kapan sebuah flag "sembuh" lalu muncul lagi. Kalau task
  yang sempat STALLED lalu aktif lagi lalu STALLED lagi, notifikasi kedua tidak akan
  terkirim. Lihat `AlertService::notifyOnceForContext()`.
- **RESOLVED (2.6, 2026-07-04): dashboard admin sekarang satu panel terpadu.**
  Sisi Eksplorasi (progres anggota + leaderboard admin-only, baru dibangun di
  2.6 karena belum pernah ada di task manapun sebelumnya) dan sisi Eksekusi
  (dari 2.4) sekarang satu halaman `/admin/dashboard`, plus kartu ringkasan
  dengan tautan cepat ke `/admin/webi` (halaman log lengkapnya tetap terpisah,
  tidak di-inline seluruhnya). Lihat `App\Livewire\Admin\Dashboard`.

## Known gaps / backlog (dari 2.5)
- **Validasi Layer 2 (output vs kunci jawaban) dan deteksi domain_rejection pakai
  `similar_text()` PHP, bukan cosine similarity embeddings.** docs/spesifikasi-webi.md
  5.2 bilang "cosine similarity ATAU exact match" — stack ini tidak punya
  vector/embedding store (docs/tech-stack.md sengaja menunda keputusan itu ke 1.9,
  tidak pernah diselesaikan). `similar_text()` dipakai sebagai pendekatan teks
  murni. Cukup jalan untuk mendeteksi jawaban yang bocor verbatim/hampir verbatim,
  tapi tidak akan menangkap parafrase makna yang beda kata. Kalau butuh akurasi
  lebih tinggi, perlu API embedding terpisah (biaya tambahan, di luar scope 2.5).
  **Ditambah (2026-07-04):** `similar_text()` sendiri ternyata bisa melewatkan
  jawaban singkat yang disisipkan di kalimat panjang (skor persentase turun
  karena selisih panjang teks) — ditambal dengan cek substring verbatim untuk
  kunci jawaban multi-kata (2+ kata, 6+ karakter) saja, supaya kata pendek umum
  seperti "Benar"/"Salah" tidak false-positive di kalimat biasa. Kunci jawaban
  satu-kata pendek tetap cuma diamankan oleh similarity persentase, bukan
  substring check. Lihat `App\Services\Webi\GuardrailService` docblock + test
  `GuardrailServiceTest`.
- **[RELEVANT_CURRICULUM_CONTENT] pakai pencarian keyword LIKE, bukan semantic/vector
  search sungguhan.** Sama alasannya seperti di atas — tidak ada infrastruktur
  vector store. Konten unit yang sedang dikerjakan user selalu ikut disertakan;
  unit "relevan" lain ditemukan lewat overlap kata kunci sederhana. Lihat
  `App\Services\Webi\CurriculumContextBuilder`.
- **ProactiveLog tidak punya field untuk mencatat level/checkpoint spesifik yang
  sudah dirayakan.** Skema (dari 2.0) cuma simpan trigger_type + sent_at/responded,
  tidak ada payload/level_number/checkpoint_id. "Sudah dirayakan atau belum"
  disimpulkan dari COUNT baris log dibanding current_level/jumlah checkpoint
  selesai (asumsi level/checkpoint biasanya naik satu-satu). Kalau user melompat
  banyak level sekaligus (poin besar dari menyelesaikan banyak unit + checkpoint
  bersamaan), trigger bisa terkirim beberapa kali sebelum "count" mengejar level
  final — dibatasi otomatis oleh aturan max 1 sapaan per hari. Sama kategori
  masalah dengan gap AlertService di Eksekusi (2.4). Lihat `ProactiveService` docblock.
- **Trigger 4 & 5 (achievement) diasumsikan bypass SEMUA pembatasan nudge**
  (termasuk "maksimal 1 sapaan per hari"), bukan cuma cooldown yang disebutkan
  eksplisit di dokumen. docs/spesifikasi-webi.md 3.2 cuma bilang "tetap dikirim
  terlepas dari cooldown", tidak eksplisit soal batas harian. Butuh konfirmasi.
  Lihat `ProactiveService::determineTrigger()`.
- **Notice transparansi monitoring baru ada di halaman chat WEBI, belum di
  halaman "Pendahuluan Kurikulum".** docs/PRD.md 5.1 minta notice muncul di DUA
  tempat: halaman Pendahuluan Kurikulum (saat onboarding) dan di antarmuka chat.
  Halaman "Pendahuluan Kurikulum" sendiri belum pernah dibangun sebagai halaman
  terpisah di 2.2 (tidak ada di scope 2.5 untuk membuatnya) — jadi baru satu dari
  dua touchpoint yang terpasang. Notice di chat sudah persistent dan tidak bisa
  di-dismiss sesuai aturan.
- **RESOLVED (2026-07-04): retry gagal kedua kali pada guardrail output_validation
  sekarang diganti generic-refusal, tidak lagi dikirim apa adanya.**
  docs/spesifikasi-webi.md 5.2 sendiri cuma bilang retry sekali dengan instruksi
  tambahan, tidak menjelaskan retry KEDUA yang masih bocor — awalnya (Batch 3)
  hasil retry tetap dikirim ke user "best effort". User secara eksplisit minta
  ini diperbaiki: kalau retry masih terdeteksi bocor, `GuardrailService::genericRefusalMessage()`
  menggantikan isi balasan sebelum disimpan/dikirim ke user, dan GuardrailFlag
  tetap mencatat `still_flagged_after_retry` + `generic_refusal_override_applied`
  untuk admin. Lihat `ChatService::sendMessage()` dan test
  `GuardrailTest::test_reply_still_leaking_after_retry_is_replaced_with_generic_refusal`.
- **Voice mode (STT/TTS) hanya diverifikasi lewat code review untuk deteksi
  dukungan browser**, karena PHPUnit/Livewire test tidak punya browser engine
  untuk menjalankan Web Speech API sungguhan. Yang tercakup di test otomatis:
  efek server-side dari flag voice_mode (system prompt berubah, Message tercatat
  voice_mode=true/false). Deteksi dukungan browser dan tombol mic hide/show butuh
  verifikasi manual di browser asli sebelum deploy. Lihat
  `resources/views/livewire/eksplorasi/webi/chat.blade.php` (fungsi `webiVoice()`).
- **RESOLVED (2026-07-04): kuota 20 request/hari yang sempat jadi blocker sudah
  tidak berlaku lagi** — key sekarang pakai project Google Cloud baru yang
  didedikasikan khusus untuk WEBI-SPACE (bukan lagi "Default Gemini Project"
  yang kepakai bareng aplikasi lain). Koneksi + kuota sudah diverifikasi live
  ulang, tidak ada lagi 429 RESOURCE_EXHAUSTED. Kalau ini muncul lagi di masa
  depan, cek dulu apakah project Google Cloud yang dipakai key itu didedikasikan
  khusus atau dipakai bareng aplikasi lain — kuota free tier per-project, bukan
  per-API-key.
- **Thinking level Gemini diset ke "minimal" (final, 2026-07-04).** Ditambahkan
  setelah log produksi menunjukkan timeout cURL murni ~20 detik ("0 bytes
  received") saat model masih "thinking". Sempat default ke "low" lalu diuji
  head-to-head live (project baru, kuota fresh): minimal ~2.1s vs low ~4.72s
  vs medium ~4.37s untuk pertanyaan yang sama — user pilih "minimal" karena
  kecepatan lebih penting dari kedalaman jawaban untuk kasus chat companion
  ini. Cuma didukung model keluarga Gemini 3.x (`thinkingLevel`); kalau
  `GEMINI_MODEL` diganti ke seri 2.5 (`thinkingBudget`), field ini otomatis
  di-skip (lihat `config/services.php` gemini.thinking_level). Catatan: test
  perbandingan pakai system prompt sederhana, bukan system prompt produksi
  penuh (persona+guardrail+evaluation bank+user context+curriculum) yang jauh
  lebih besar — latency asli di aplikasi kemungkinan lebih tinggi dari angka di
  atas, tapi pola relatifnya (minimal jelas lebih cepat) seharusnya tetap berlaku.

## Known gaps / backlog (dari 2.5 follow-up, bug fixes 2026-07-04)
- **RESOLVED: `[VOICE_MODE=true]` bocor ke tampilan dan TTS.** Akar masalah:
  `SystemPromptBuilder::voiceModeInstructions()` dulu menulis literal string
  `"[VOICE_MODE=true]"` ke dalam prompt — ini persis meniru pola marker
  struktural lain yang dipakai di kelas yang sama (`[EVALUATION_BANK]`,
  `[USER_CONTEXT]`), jadi model kadang mengira itu konten yang perlu
  direproduksi, bukan deskripsi state. Prompt sekarang tidak pernah menulis
  bracket/flag apapun soal voice mode — cukup instruksi teks biasa yang
  disertakan SECARA KONDISIONAL (bukan lewat flag literal). Backstop
  pertahanan kedua juga ditambahkan di `ChatService::stripInternalArtifacts()`
  untuk berjaga-jaga kalau model tetap membocorkannya.
- **RESOLVED: simbol markdown tidak ter-render dan ikut terbaca TTS.** Bubble
  chat sekarang render markdown asli via CommonMark (`App\Services\Webi\MessageRenderer::toSafeHtml()`,
  dengan `html_input: escape` supaya HTML mentah dari model tidak pernah
  dieksekusi sebagai HTML sungguhan — perlindungan XSS wajib karena teks
  sumbernya dari AI, bukan konten yang sudah dipercaya). TTS dapat versi
  plain-text yang sudah di-strip simbol markdown-nya
  (`MessageRenderer::toPlainText()`), dengan lapis kedua di sisi client
  (`webiVoice().speak()` di chat.blade.php) sebagai jaga-jaga.
- **BARU: Card Rekomendasi Modul/Unit di chat.** Model diinstruksikan menulis
  baris terpisah `[REKOMENDASI_UNIT:<id>]` atau `[REKOMENDASI_MODUL:<id>]` di
  akhir respons kalau merekomendasikan unit/modul spesifik.
  `App\Services\Webi\RecommendationParser` mengekstrak + memvalidasi ID itu ke
  database SEBELUM card ditampilkan — ID yang tidak valid/halusinasi tidak
  pernah menghasilkan card rusak, cukup teks biasa tanpa card. Tag SELALU
  di-strip dari teks yang ditampilkan (valid maupun tidak), tidak pernah bocor
  sebagai teks mentah. `Message.content` di database TETAP menyimpan tag
  mentahnya (tidak di-strip saat ditulis) supaya card bisa muncul lagi kalau
  percakapan lama dibuka ulang — parsing dilakukan ulang di setiap titik
  tampil (chat member), bukan sekali saat ditulis. **Keterbatasan yang
  disengaja:** rekomendasi cuma bisa merujuk unit/modul yang IDnya memang ada
  di `[RELEVANT_CURRICULUM_CONTENT]` request itu (unit saat ini, unit terkait
  hasil pencarian keyword, unit/modul berikutnya) — bukan seluruh ~67 unit
  kurikulum, supaya prompt tidak membengkak dan mengorbankan latency yang baru
  saja dituning. **Observasi (bukan bug):** kalau respons menyebut dua unit
  berurutan (unit saat ini + unit berikutnya), tag kadang mengarah ke unit
  KEDUA yang disebutkan, bukan yang pertama dijelaskan — card tetap selalu
  valid (tidak pernah rusak), cuma kadang bukan unit "utama" dari paragraf
  pembuka. Bisa disempurnakan lewat pengaturan ulang wording prompt di
  iterasi berikutnya kalau jadi masalah nyata di pemakaian.
- **RESOLVED (ditemukan saat live-testing fitur card di atas): guardrail Layer 2
  false-positive pada respons rekomendasi yang panjang dan wajar.** Cek
  substring verbatim yang ditambahkan sebelumnya (untuk menangkap jawaban
  pendek yang disisipkan di kalimat panjang) ternyata salah tangkap respons
  rekomendasi yang menyebut judul/topik unit (misal "Software Development")
  yang KEBETULAN juga jadi kunci jawaban kuis unit itu sendiri — memicu retry
  yang tidak perlu (dan kadang timeout beneran, karena retry adalah panggilan
  Gemini penuh lagi). Ditambal dengan membatasi cek substring HANYA untuk
  respons pendek (<=150 karakter) — kebocoran jawaban asli biasanya berupa
  balasan pendek yang isinya cuma jawaban itu sendiri, bukan beberapa kalimat
  penjelasan yang kebetulan menyebut topiknya. Lihat
  `GuardrailService::MAX_LENGTH_FOR_SUBSTRING_LEAK_CHECK` dan test
  `test_long_explanatory_reply_mentioning_the_answer_as_a_topic_is_not_flagged`.

## Known gaps / backlog (dari 2.6, 2026-07-04)
- **RESOLVED (keputusan user, 2026-07-04): Forum Diskusi Eksekusi TIDAK akan
  dibangun — bukan gap, PRD-nya yang salah tulis.** Sempat dicurigai sebagai
  fitur yang belum dibangun (PRD 2.2 dan 5.16 sebelumnya menyebut
  execution_member berhak mengakses "Forum diskusi Eksekusi"), tapi
  dikonfirmasi user: forum diskusi cuma pernah diputuskan untuk Eksplorasi
  di tahap 1.2 (Fiksasi Fitur) — referensi Eksekusi itu salah tulis saat
  konsolidasi PRD, bukan keputusan tahap 1.2 yang nyata (beda dengan gap
  Attachment 3-form dari 2.4, yang memang terlacak sebagai keputusan
  1.2 asli). `docs/PRD.md` bagian 2.2 dan 5.16 sudah dikoreksi untuk
  menghapus hak akses forum dari execution_member; section 5.16 diganti
  namanya jadi "Forum Diskusi Hanya untuk Eksplorasi" dengan catatan koreksi
  eksplisit. Tidak ada perubahan kode (fitur ini memang tidak pernah
  dibangun, jadi tidak ada yang perlu dihapus).
- **RESOLVED (dikerjakan sebagai task terpisah "2.6b: UI Notifikasi Terpadu",
  2026-07-04): UI notifikasi sekarang ada di seluruh aplikasi.** Lihat
  section "Known gaps / backlog (dari 2.6b)" di bawah untuk detail lengkap.
- **RESOLVED: Eksplorasi tidak pernah menulis ke tabel `notifications`.**
  Skema dan morph map (`unit`, `checkpoint`, `module`, `forum_thread`) serta
  enum `type` (`checkpoint_completed`, `level_up`, `new_unit_unlocked`,
  `evaluation_reminder`, `forum_reply_received`) sudah disiapkan sejak 2.0,
  tapi baru Eksekusi (2.4) yang benar-benar memakainya. Ditambal dengan
  `App\Services\Exploration\Notifier` (mengikuti pola persis
  `App\Services\Execution\Notifier`), dipanggil dari
  `ProgressService::completeCheckpoint()` (checkpoint_completed),
  `ProgressService::awardPoints()` (level_up, hanya saat level benar-benar
  naik dibanding sebelum poin ditambahkan), `ProgressService::completeUnit()`
  via `notifyNewlyUnlockedUnits()` (new_unit_unlocked, dibatasi ke unit yang
  prerequisite_unit_id-nya persis unit yang baru diselesaikan — sesuai bunyi
  literal PRD 3.1.8 "setelah menyelesaikan unit sebelumnya", TIDAK menangani
  unit pertama modul berikutnya yang baru terbuka lewat checkpoint), dan
  `Forum\Show::reply()` (forum_reply_received ke thread creator, mengikuti
  pola `TaskService::addComment()` karena tidak ada mekanisme "follow thread"
  terpisah di skema). **Trigger `evaluation_reminder` ("Pengingat
  tugas/evaluasi belum selesai") SENGAJA TIDAK dibangun** — event ini butuh
  scheduled command + threshold hari (mirip `ProactiveService` di WEBI atau
  `execution:check-alerts` di Eksekusi), dan tidak ada angka threshold yang
  dikonfirmasi di PRD/kurikulum manapun untuk ini. Butuh keputusan angka
  threshold eksplisit sebelum dibangun, sama seperti kehati-hatian yang sama
  dipakai untuk `ProactiveService::stagnation_days` di 2.5.
- **RESOLVED: sesi login yang sudah aktif tidak langsung ter-cut saat admin
  menonaktifkan akun.** `App\Livewire\Auth\Login` sudah menolak login BARU
  dari akun nonaktif sejak 2.1, tapi tidak ada pengecekan ulang
  `membership_status` untuk sesi yang SUDAH berjalan — anggota yang
  dinonaktifkan admin di tengah sesi aktif tetap punya akses penuh sampai
  sesi itu kedaluwarsa sendiri. Ditambal dengan
  `App\Http\Middleware\EnsureMembershipIsActive`, didaftarkan global di
  grup middleware `web` (`bootstrap/app.php`) — bukan cuma di route yang
  sudah pakai `role:`, supaya berlaku di request manapun. Begitu admin
  menonaktifkan akun, request BERIKUTNYA dari akun itu langsung di-logout
  paksa dan diarahkan ke `/login`.

## Known gaps / backlog (dari 2.6b, 2026-07-04)
- **UI Notifikasi Terpadu dibangun lengkap.** Tiga bagian, dibangun dan
  ditest berurutan: (A) ikon bell + badge unread di `resources/views/components/layouts/app.blade.php`,
  tampil untuk ketiga role (`App\Livewire\Notifications\Bell`); (B) dropdown
  ringkas (6 item terbaru), klik item langsung menandai dibaca dan mengarah
  ke halaman terkait; (C) halaman penuh `/notifications`
  (`App\Livewire\Notifications\Index`) dengan pagination (15/halaman) dan
  tombol "Tandai semua sudah dibaca" (cuma tampil kalau ada yang belum
  dibaca). Link tujuan tiap notifikasi diresolusi lewat `Notification::linkUrl()`
  berdasarkan `context_type`/`context` (morphTo) yang sudah ada sejak 2.0 —
  project/task ke halaman Eksekusi, unit/checkpoint/forum_thread ke halaman
  Eksplorasi, module ke Peta Kurikulum (belum ada halaman detail modul
  tersendiri). Scoping per-recipient dan penanganan context yang sudah
  dihapus dites eksplisit (`tests/Feature/Notifications/NotificationScopingAndSafetyTest.php`).
- **Bug ditemukan dan diperbaiki saat membangun ini: mengakses relasi
  `context()` pada notifikasi ber-`context_type = 'none'` akan crash
  ("Class \"none\" not found"), bukan mengembalikan null seperti asumsi
  awal.** `context_type = 'none'` (dipakai untuk notifikasi tanpa
  konteks spesifik) tidak terdaftar di morph map (`AppServiceProvider`),
  jadi Eloquent mencoba resolve string `"none"` sebagai nama class
  sungguhan saat relasi `morphTo` diakses — fatal error, bukan graceful
  null. Bug ini laten sejak 2.4 (notifikasi Eksekusi sudah lama memakai
  `context_type = 'none'` untuk beberapa jenis, misal alert tanpa task
  spesifik) tapi tidak pernah ketahuan karena tidak ada kode manapun yang
  pernah mengakses `->context` sebelum UI ini dibangun. Ditambal di
  `Notification::linkUrl()` dengan short-circuit eksplisit untuk
  `context_type === 'none'` SEBELUM relasi disentuh sama sekali.
- **Tidak ada infrastruktur websocket/broadcast** (dikonfirmasi tidak pernah
  di-setup di `docs/tech-stack.md` maupun `config/`), jadi "real-time" badge
  count dilakukan lewat `wire:poll.30s` di komponen Bell, bukan push
  sungguhan. Cukup untuk tim 12 orang; kalau butuh push instan sungguhan
  nanti, perlu infrastruktur broadcast baru (Reverb/Pusher), di luar scope
  batch ini.
- **View pagination default Laravel (`tailwind.blade.php`) di-override**
  di `resources/views/vendor/pagination/tailwind.blade.php` karena versi
  bawaan framework pakai warna gray/blue-300 generic yang tidak sesuai
  palet di docs/design-tokens.md. Override ini otomatis berlaku untuk
  pagination manapun di aplikasi ke depannya (bukan cuma halaman
  notifikasi), konsisten dengan aturan "ikuti pola desain yang sudah ada".
- **Verifikasi visual di browser sungguhan BELUM dilakukan** — lingkungan
  kerja ini tidak punya tool browser (Playwright dkk). Sudah divalidasi
  lewat: assertion konten HTML pada `Livewire::test(...)->html()` (termasuk
  cek class CSS badge/dropdown benar-benar muncul di markup), route
  `assertOk()` untuk tiap role, dan `npm run build` sukses tanpa error.
  Interaksi Alpine (buka/tutup dropdown, klik-di-luar-buat-nutup) belum
  diverifikasi manual di browser asli — sama seperti gap voice mode WEBI di
  2.5, perlu dicek manual sebelum deploy.

## Known gaps / backlog (dari 2.7, 2026-07-04)
- **RESOLVED: alur seeding produksi difinalisasi.** `database/seeders/DatabaseSeeder.php`
  (yang jalan lewat `php artisan migrate:fresh --seed`) sekarang HANYA memanggil
  `CurriculumSeeder` — tidak lagi memanggil `ExplorationSampleSeeder` (test-only,
  sekarang cuma dipanggil eksplisit lewat `$this->seed(...)` di test) dan tidak
  membuat user apa pun. Diverifikasi langsung dari kondisi bersih: 10 modul, 67
  unit, 0 user. Command interaktif baru `php artisan app:create-admin`
  (`App\Console\Commands\CreateAdmin`) menanyakan nama/email/password lewat
  prompt terminal (password pakai `secret()`, tidak tampil di layar), validasi
  password min. 8 karakter + konfirmasi + email unik, tidak ada kredensial
  di-hardcode di kode manapun — ini pengganti permanen cara manual-tinker yang
  ditandai sementara sejak 2.1. **Catatan lingkungan kerja:** verifikasi
  otomatis (`tests/Feature/Console/CreateAdminTest.php`, 4 test lewat
  `$this->artisan(...)->expectsQuestion(...)`, cara resmi Laravel test command
  interaktif) semua lolos, tapi percobaan menjalankan command ini secara manual
  lewat pipe stdin di shell sandbox ini tidak berhasil terbaca (keterbatasan
  non-TTY tool, bukan bug command-nya) — command ini perlu dicoba sekali secara
  manual di terminal asli sebelum deploy pertama kali.
- **BUG SIGNIFIKAN ditemukan dan diperbaiki: unit dengan evaluation_type
  `quiz_matching` atau `quiz_ordering` TIDAK BISA DISELESAIKAN SAMA SEKALI
  oleh user manapun.** `resources/views/livewire/eksplorasi/unit-evaluation.blade.php`
  cuma render form untuk `quiz_multiple_choice`; kedua tipe ini jatuh ke
  pesan "Tipe evaluasi ini belum didukung di versi ini". Parah karena Unit 1.2
  (matching) adalah unit KEDUA di seluruh kurikulum, dan progres harus
  berurutan lewat rantai prerequisite — artinya sebelum ini diperbaiki, TIDAK
  ADA anggota yang bisa maju melewati Unit 1.1 sama sekali. Total 13 unit
  terdampak (10 matching + 3 ordering) dari 67. Server-side juga ikut menolak:
  `submitQuiz()`'s validation rule lama mensyaratkan `string` padahal jawaban
  matching/ordering berbentuk array. **Diperbaiki penuh:** UI dropdown
  per-pasangan untuk matching, UI naik/turun urutan untuk ordering
  (`moveOrderItem()` — alternatif drag-and-drop yang testable tanpa browser),
  validasi rule disesuaikan per question_type, dan grading matching dibuat
  order-independent (`ksort` kedua sisi sebelum dibandingkan) supaya urutan
  pasangan yang dipilih user tidak memengaruhi benar/salah. Lihat
  `App\Livewire\Eksplorasi\UnitEvaluation` dan test
  `tests/Feature/Exploration/MatchingAndOrderingEvaluationTest.php`.
- **Smoke test lintas sistem dibangun** (`tests/Feature/Integration/FullSystemSmokeTest.php`):
  bootstrap admin lewat `app:create-admin` → admin buat 3 akun (satu tiap
  role) → anggota eksplorasi kerjakan seluruh Modul 1 (6 unit, semua tipe
  evaluasi asli) sampai checkpoint → WEBI kasih rekomendasi berdasar unit
  lanjutan yang benar-benar terbuka dari progres asli → anggota eksekusi usul
  ide → admin approve → task dikerjakan sampai done → dashboard admin
  terpadu menunjukkan angka poin/persentase/task yang cocok persis dengan
  yang dilihat anggota di dashboard masing-masing. Semua lewat komponen
  Livewire/route asli, bukan service layer langsung.
- **APP_DEBUG: tidak bisa diverifikasi untuk production dari sini.** `.env`
  lokal (`APP_ENV=local`) memang `APP_DEBUG=true` — ini benar untuk dev, BUKAN
  indikasi masalah. Tidak ada `.env` production di repo ini (konfigurasi live
  server terpisah, sesuai cara deploy cPanel/git pull yang sudah didokumentasikan).
  **WAJIB dicek manual oleh user langsung di server production**: pastikan
  `APP_DEBUG=false` di `.env` server sebelum live. Ditambahkan test
  `tests/Feature/Security/ErrorHandlingTest.php` yang membuktikan: kalau
  `app.debug` false, error apa pun tidak pernah menampilkan stack
  trace/file path/query mentah; kalau true, baru bocor (test kedua ini
  cuma pembuktian bahwa assertion pertama benar-benar mendeteksi kebocoran,
  bukan lolos secara kebetulan).
- **Guardrail WEBI diuji ulang dengan 7 percobaan jailbreak live** (bukan
  mocked, lewat Gemini API sungguhan dalam transaksi DB yang di-rollback):
  fake "system override"/"mode developer", roleplay jadi AI lain, eja
  huruf-demi-huruf, main tebak-tebakan "panas-dingin", minta nomor opsi
  bukan teks, dan klaim sebagai "admin yang sedang audit". **Semua tertahan**
  — model konsisten menolak memberi jawaban langsung, mengarahkan balik ke
  penjelasan konsep (Socratic), dan explicitly menyebut tidak bisa membocorkan
  isi `[EVALUATION_BANK]` bahkan untuk klaim "keperluan audit admin". Nol
  kebocoran jawaban asli di ke-7 percobaan. Verifikasi ini one-off (live,
  tidak dikomit sebagai automated test karena bergantung pada perilaku model
  real yang bisa berubah), melengkapi test otomatis yang sudah ada
  (`GuardrailTest::test_reply_leaking_the_answer_key_is_flagged_and_retried`
  dan `test_reply_still_leaking_after_retry_is_replaced_with_generic_refusal`
  yang membuktikan Layer 2 tetap menahan kebocoran SEANDAINYA Layer 1 gagal).
- **Grep kredensial hardcoded: bersih.** Tidak ada API key/password/token
  ter-hardcode di `app/`, `config/`, `routes/`, `database/`, `resources/`.
  `.env` dikonfirmasi ter-gitignore dan tidak pernah masuk riwayat git.
  `config/services.php` gemini.key murni dari `env('GEMINI_API_KEY')`
  tanpa fallback default yang berbahaya.
- **Tidak ada `dd()`/`dump()`/`var_dump()` tertinggal** di kode aplikasi
  maupun view manapun.
- **BUG responsive ditemukan dan diperbaiki: grid dashboard admin, Kanban
  board, dan 3 halaman Eksekusi lainnya kehilangan base `grid-cols-1`
  sebelum breakpoint prefix (`md:`/`lg:`/`sm:grid-cols-N`).** Tanpa base
  eksplisit, `grid-template-columns` browser default ke `none`, artinya di
  bawah breakpoint (semua HP dan sebagian besar tablet) item-item grid TIDAK
  stack vertikal, malah dipaksa jadi kolom-kolom sempit dalam satu baris
  horizontal — bug CSS yang perilakunya pasti/well-established, bukan
  sekadar dugaan. Diperbaiki di `livewire/admin/dashboard.blade.php` (3
  lokasi), `livewire/eksekusi/projects/board.blade.php` (Kanban),
  `livewire/eksekusi/projects/show.blade.php`, dan
  `livewire/eksekusi/tasks/show.blade.php` (2 lokasi) — semua ditambah
  `grid-cols-1` sebagai base sebelum override breakpoint-nya. Juga
  diperbaiki: `livewire/admin/users/index.blade.php` pakai `overflow-hidden`
  pada wrapper tabel (memotong konten di layar sempit) alih-alih
  `overflow-x-auto` yang sudah jadi pola konsisten di tabel lain
  (`admin/dashboard`, `admin/webi/index`) — disamakan.
- **DITUNDA (keputusan user, 2026-07-04): navbar admin di HP tidak
  dikerjakan sekarang.** `flex-wrap` yang sudah ditambal dianggap cukup
  untuk saat ini; menu hamburger/mobile-drawer yang lebih rapi ditunda,
  bukan dibatalkan — bisa diangkat lagi kalau dirasa perlu nanti.
- **RESOLVED (2026-07-04): form "Bahas modul atau unit mana?" di
  `eksplorasi/forum/create.blade.php` sekarang stack vertikal di layar
  kecil.** `grid-cols-2` tanpa breakpoint diganti `grid-cols-1 gap-3
  sm:grid-cols-2` — kedua `<select>` (judul modul/unit yang bisa panjang)
  jadi satu kolom penuh lebar di bawah breakpoint `sm`, dua kolom baru
  muncul di layar yang cukup lebar.
- **DITERIMA (keputusan user, 2026-07-04): Peta Kurikulum tetap vertikal,
  tidak diubah jadi horizontal.** Bukan bug — perbedaan dari "jalur node
  horizontal" yang disebut literal di design-tokens.md 4 diterima sebagai
  adaptasi mobile-friendly yang sudah tepat, tidak perlu redesign.
- **APP_DEBUG production dan test manual `app:create-admin` di terminal
  asli: dikerjakan sendiri oleh user, tidak perlu tindak lanjut dari sisi
  Claude Code.** Chat WEBI dan dashboard Eksplorasi sudah dicek sebelumnya
  dan levelnya rendah risiko (layout vertikal/fluid natural, tidak butuh
  breakpoint tambahan).

## Known gaps / backlog (dari 2.8 persiapan, 2026-07-04)
- **`.env.example` diperbaiki, sebelumnya tidak lengkap.** Hilang total:
  `GEMINI_API_KEY`, `GEMINI_MODEL`, `GEMINI_THINKING_LEVEL` (tiga variabel
  wajib untuk WEBI). `DB_*` juga masih default sqlite dengan
  host/database/username/password di-comment — diganti ke `mysql` (satu-satunya
  driver yang benar-benar dipakai project ini, lihat docs/tech-stack.md) dengan
  kelima variabelnya diaktifkan (nilai kosong sebagai placeholder, bukan
  kredensial asli). Ditambah komentar eksplisit di atas `APP_ENV`/`APP_DEBUG`
  mengingatkan wajib `production`/`false` di server.
- **Ditemukan: aplikasi ini tidak pernah memakai job queue sama sekali**
  (`QUEUE_CONNECTION=database` di `.env` cuma warisan scaffold default, tidak
  ada satu pun `ShouldQueue`/`Job` class di kodenya — semua `dispatch(...)`
  yang ada adalah event browser Livewire, bukan queued job). Berarti server
  production TIDAK butuh `queue:work` atau supervisor apa pun — disederhanakan
  di `DEPLOYMENT_CHECKLIST.md`.
- **Ditemukan: `/public/build` (hasil `npm run build`) dan `/vendor` (hasil
  Composer) sama-sama di-gitignore** — tidak ikut ter-pull lewat cPanel Git
  Version Control, harus diisi terpisah di server. Tidak diketahui apakah
  server rumahweb ini punya Node.js — mengingat `proc_open` saja sudah
  dimatikan (memengaruhi Composer scripts), kemungkinan Node juga tidak ada.
  Direkomendasikan build asset di lokal lalu upload folder `public/build`
  manual, bukan mengandalkan `npm run build` jalan di server — keputusan akhir
  ada di user setelah cek ketersediaan Node lewat Terminal cPanel.
- **Scheduler dikonfirmasi benar, tidak ada yang kurang.** Satu-satunya
  scheduled command adalah `execution:check-alerts` (`routes/console.php`,
  `->daily()`, terverifikasi lewat `php artisan schedule:list`). Sapaan
  proaktif WEBI (`ProactiveService::checkAndDeliver()`) BUKAN scheduled
  command dan memang tidak butuh jadi satu — itu dihitung real-time saat
  `Chat::mount()` (halaman chat dibuka), bukan gap yang lupa dijadwalkan.
- **RESOLVED: `docs/Catatan_Troubleshooting_Deployment_WEBI-SPACE.md` sempat
  tidak ada di repo ini saat pertama dicek, sudah ditambahkan user setelahnya.**
  Ini log insiden nyata deploy pertama di tahap 1.10 (akun cPanel `rits8313`).
  `DEPLOYMENT_CHECKLIST.md` sudah direkonsiliasi dengan isi dokumen ini —
  ditambahkan 2 langkah yang sebelumnya belum tercakup: (1) Composer TIDAK
  preinstalled di server rumahweb, perlu instalasi manual sekali per server
  lewat `curl` installer; (2) versi PHP CLI server bisa mismatch dengan
  requirement `composer.json` (pernah kejadian 8.2 vs butuh ^8.3), perlu
  dicek dulu lewat `php -v` sebelum lanjut.
- **Repo GitHub WEBI-SPACE dikonfirmasi PUBLIC** (bukan private seperti
  sempat salah tercatat sesaat). `docs/tech-stack.md` awalnya bilang "privat"
  — itu dokumen yang sudah usang (ditulis sebelum keputusan 1.10), bukan
  memory yang salah. Repo dipindah ke public saat troubleshooting 1.10:
  SSH key untuk clone repo privat gagal berkali-kali (`Permission denied
  (publickey)`) walau Deploy Key GitHub sudah didaftarkan, akar masalahnya
  tidak berhasil dipastikan tanpa akses debug server langsung — jadi
  diputuskan pindah ke public supaya clone tidak butuh autentikasi SSH sama
  sekali. `docs/tech-stack.md` sudah dikoreksi menyebutkan ini. Aturan jangan
  pernah hardcode kredensial tetap berlaku sama ketatnya terlepas dari
  visibilitas repo — itu tidak berubah.
- **`DEPLOYMENT_CHECKLIST.md` dibuat di root project** — urutan lengkap
  command yang harus dijalankan manual di server, mengantisipasi seluruh
  masalah yang pernah kejadian di `docs/Catatan_Troubleshooting_Deployment_WEBI-SPACE.md`
  (SSH key gagal → repo public, Composer tidak preinstalled, versi PHP
  mismatch, `proc_open` mati → `--no-scripts` + `package:discover` manual,
  format `.env` tanpa `#` di baris aktif + kutip dua untuk password karakter
  spesial, urutan `config:cache`/`route:cache`/`view:cache` PALING TERAKHIR
  setelah `.env` final, urutan `migrate:fresh --seed` lalu `app:create-admin`
  terpisah sesuai hasil 2.7). Eksekusi di server tetap manual oleh user.
- **RESOLVED (2026-07-04): `storage:link` tidak bisa dipakai sama sekali di
  server production — `symlink()` dimatikan total lewat `disable_functions`
  (dikonfirmasi `ini_get('disable_functions')`), bukan cuma error sesaat.**
  Solusi sementara (copy manual `storage/app/public` ke `public/storage` lewat
  `cp -r`) sudah jalan tapi tidak scalable (file upload baru tidak otomatis
  muncul, butuh copy ulang manual tiap kali). **Solusi permanen dipilih:** disk
  baru `storage_files` (`config/filesystems.php`) dengan root langsung di
  `public_path('storage_files')` — file attachment ditulis LANGSUNG ke dalam
  `public/`, disajikan langsung oleh web server, tidak butuh symlink sama
  sekali selamanya. `App\Services\Execution\TaskService::addAttachmentFile()`
  diubah menulis ke disk ini (dari disk `public` bawaan Laravel). Dipilih dari
  3 opsi yang dipertimbangkan (ubah disk vs scheduled-sync vs serve lewat
  route ber-auth) — alasan utama: tidak ada proses terpisah yang bisa gagal
  senyap (beda dari opsi scheduled-sync yang bergantung `schedule:run` tiap
  menit, delay minimal 1 menit, dan kalau cron berhenti jalan, upload baru
  diam-diam tidak pernah muncul tanpa ada indikasi error). Attachment lama
  yang sempat di-`cp` manual tetap bisa diakses (foldernya masih ada fisik),
  tidak butuh migrasi data. `DEPLOYMENT_CHECKLIST.md` diperbarui: langkah
  `storage:link` dihapus total, diganti catatan eksplisit "jangan dijalankan".
- **RESOLVED (dikerjakan sebagai task terpisah "2.9: Kontrol Akses Attachment",
  2026-07-04): attachment task Eksekusi sekarang wajib login + cek keanggotaan
  proyek.** Lihat section "Known gaps / backlog (dari 2.9)" di bawah untuk
  detail lengkap.

## Known gaps / backlog (dari 2.9, 2026-07-04)
- **RESOLVED: attachment file task Eksekusi sebelumnya bisa diakses SIAPA PUN
  yang punya URL-nya, tanpa cek login atau keanggotaan proyek sama sekali.**
  Ditemukan saat menganalisis solusi symlink di 2.8 (opsi serve-lewat-route
  yang saat itu sengaja tidak dipilih) — karakteristik yang sudah ada sejak
  2.4, bukan regresi dari perubahan disk `storage_files` di 2.8. Diperbaiki
  penuh:
  - File attachment sekarang ditulis ke disk `attachments` (privat, disk BARU
    khusus, `storage/app/private`, tidak pernah bisa diakses langsung lewat
    URL web) alih-alih disk `storage_files` yang disajikan publik apa
    adanya. `TaskService::addAttachmentFile()` diubah; `file_url` untuk tipe
    file sekarang menyimpan PATH RELATIF di disk, bukan URL yang bisa
    diakses langsung.
  - Route baru `/attachments/{attachment}/download` (`App\Http\Controllers\Eksekusi\AttachmentDownloadController`,
    middleware `auth` + `role:execution_member,admin`) — cek akses PERSIS
    sama seperti `Tasks\Show::mount()` (admin, atau anggota proyek yang sama
    dengan task tempat attachment itu berada), lalu stream file lewat
    `Storage::disk(...)->download()`. Gagal cek akses → 403 (bukan 404, sesuai
    instruksi user — 403 lebih jujur secara keamanan tapi tanpa membocorkan
    detail alasan penolakan di pesannya). Attachment tipe `link`/`text` (bukan
    file sungguhan) sengaja 404 di route ini — `file_url`-nya bukan path disk.
  - `tasks/show.blade.php` diperbarui: link attachment tipe file sekarang
    mengarah ke route download ini, bukan lagi langsung ke `file_url`. Tipe
    `link` (URL eksternal yang di-paste user) TETAP tampil sebagai link
    langsung apa adanya — itu bukan file yang kita simpan, tidak relevan
    untuk dilewatkan lewat cek akses ini.
  - **Attachment lama dari disk `storage_files` (jendela singkat 2.8→2.9):**
    dicek live, 0 baris ada di database manapun yang saya akses. Dipilih
    pendekatan FALLBACK di `AttachmentDownloadController` (bukan migrasi data
    terpisah) — kalau `file_url` masih berbentuk URL lama yang mengandung
    `/storage_files/`, controller otomatis baca dari disk `storage_files`
    alih-alih `attachments`. Alasan pilih fallback dibanding migrasi: cuma
    beberapa baris kode, tidak perlu command/test terpisah untuk memigrasi
    data yang saat ini kosong, dan tetap benar kalau ternyata ada baris yang
    terlewat di lingkungan lain.
  - Disk `storage_files` (`config/filesystems.php`) TIDAK dihapus — masih
    perlu ada untuk fallback di atas, cuma tidak lagi ditulisi upload baru.
    Dikomentari eksplisit sebagai "LEGACY — no longer written to."
- **BUG PRODUKSI ditemukan dan diperbaiki di hari yang sama (2026-07-04):
  percobaan pertama perbaikan di atas salah menimpa disk `local` BAWAAN
  Laravel (bukan bikin disk baru), bikin SELURUH upload file lewat Livewire
  di aplikasi ini gagal di production dengan error "Unable to retrieve the
  file_size" — bukan cuma soal attachment Eksekusi.** Akar masalah: Livewire
  sendiri memakai disk default aplikasi (`config/livewire.php`
  `temporary_file_upload.disk`, kalau kosong jatuh ke `filesystems.default`,
  yaitu disk `local`) untuk SEMUA upload sementara di halaman manapun,
  sebelum file itu dipindah permanen oleh kode aplikasi. Analisis awal
  "blast radius sempit, cuma TaskService yang pakai" tidak menghitung
  dependency tersembunyi Livewire ke disk `local` sebagai disk default.
  **Diperbaiki:** disk `local` dikembalikan PERSIS ke kondisi bawaan
  (`storage_path('app')`, tidak disentuh sama sekali, komentar 2.9 yang
  sempat ditambahkan di situ juga dihapus). Attachment sekarang pakai disk
  BARU bernama `attachments` (root `storage_path('app/private')`, terpisah
  total dari `local`). **Pelajaran yang dicatat eksplisit di kode
  (`config/filesystems.php`):** jangan pernah menimpa/merepurpose disk yang
  mungkin dipakai Livewire atau infrastruktur framework lain untuk kebutuhan
  satu fitur spesifik — selalu buat disk baru dengan nama sendiri. Test
  regresi ditambahkan yang secara eksplisit TIDAK fake disk `attachments`
  saat menguji langkah upload sementara Livewire, membuktikan langkah itu
  tidak lagi bergantung pada disk `attachments` sama sekali.

## Known gaps / backlog (dari Fase 3, restrukturisasi Materi+WEBI, 2026-07-11)
- **PENYIMPANGAN SADAR dari PRD 5.1 (Transparansi Monitoring Chat WEBI),
  dikonfirmasi eksplisit oleh Aye.** PRD 5.1 minta notice "Percakapanmu
  dengan WEBI bisa diakses PIC..." muncul persistent (tidak bisa
  di-dismiss) di ANTARMUKA CHAT MANAPUN. Panel chat WEBI kontekstual yang
  baru (embedded di halaman Materi/`unit-show.blade.php`, kolom kanan
  bisa expand/minimize) SENGAJA TIDAK menampilkan notice ini —
  `App\Livewire\Eksplorasi\Webi\Chat` blade menyembunyikannya lewat
  `@unless ($contextUnit)`, cuma tampil di halaman chat penuh
  (`/eksplorasi/webi`). Sudah diflag eksplisit sebelum dieksekusi
  (percakapannya SAMA, tetap bisa diakses PIC; anggota yang cuma pakai
  panel kontekstual ini — tidak pernah buka halaman chat penuh — tidak
  akan pernah lihat notice ini sama sekali) — Aye tetap pilih menghapus.
  Kalau ini jadi masalah compliance nyata nanti, opsi paling minim
  perubahan: kembalikan notice versi ringkas (1 baris, bukan card) di
  panel kontekstual, bukan hapus total.
- **Mekanisme slide-over lama halaman Materi (webiPanelOpen, FAB fixed
  bottom-right, backdrop, translate-x) dibongkar total**, diganti layout
  3-kolom (Daftar Isi Modul | Materi | WEBI) dengan state Alpine lokal
  (`tocOpen`/`webiOpen`, reset tiap unit dibuka). Tombol toggle WEBI
  dipindah ke dalam card Daftar Isi Modul (bukan strip terpisah), default
  ikon saja, melebar menampilkan teks "Chat dengan WEBI" saat di-hover
  (CSS `group-hover`, cuma aktif kalau TOC juga sedang tidak diminimize).
  Panel WEBI kontekstual dibuat tinggi TETAP (`lg:h-[calc(100vh-7rem)]`,
  bukan `max-h`+`overflow-y-auto`) supaya card-nya sendiri tidak pernah
  scroll — cuma bubble chat di dalamnya yang scroll (messages-box diberi
  `flex-1 min-h-0` KHUSUS saat `contextUnit` di-set; halaman chat penuh
  tetap `height: 28rem` seperti semula, tidak disentuh). Mobile (`<lg`):
  TOC & WEBI jadi overlay/drawer (`max-lg:fixed`) lewat Tailwind v4
  `max-lg:` variant, bukan kolom sejajar. Lihat
  `App\Livewire\Eksplorasi\UnitShow` dan
  `resources/views/livewire/eksplorasi/unit-show.blade.php`.
- **"Stack poin progres" (istilah dari wireframe Aye) diinterpretasikan**
  sebagai progres modul yang sedang dibuka (persentase) + total poin user,
  ditampilkan sebagai card kecil sejajar breadcrumb (posisi `top-2`/`top-3`
  sama seperti `<x-shell.breadcrumb>`, disembunyikan di layar paling
  sempit). Ini ASUMSI, belum dikonfirmasi eksplisit — kalau maksud Aye
  beda, tinggal sebutkan interpretasi yang benar.

## Known gaps / backlog (dari Fase 5, Praktik 2, 2026-07-12)
- **`submission_type = 'file'` sengaja TIDAK ditawarkan di form submit member**,
  walau tetap nilai enum valid di skema `challenge_submissions` (dari Praktik 1).
  Alasan: satu-satunya infrastruktur upload file yang ada di app ini (disk
  `attachments`, 2.9) dibangun khusus untuk attachment task Eksekusi lengkap
  dengan RBAC download controller sendiri — menggunakannya ulang untuk upload
  bebas dari anggota eksplorasi butuh alur/kontrol akses baru (siapa boleh
  download hasil submission siapa), bukan penambahan kecil, dan tidak diminta
  di batch ini. Kategori gap yang sama dengan Modul 6 intermezo (lihat entri
  2.3 di atas) — dicatat, bukan dikerjakan diam-diam. Form Praktik member
  (`App\Livewire\Eksplorasi\Praktik\Show`) cuma punya opsi `link`/`text`.
- **Notifikasi "submission baru masuk" pakai type baru `submission_received_alert`**
  (migrasi additif ke `notifications.type`), BUKAN salah satu dari 3 type
  review-lifecycle yang sudah ada dari Praktik 1
  (`submission_assigned_to_reviewer`/`submission_approved`/`submission_needs_revision`)
  — ketiganya baru berlaku SETELAH admin bertindak, sedangkan event ini
  terjadi SEBELUM admin sempat menugaskan siapa pun. Dikirim ke SEMUA admin
  (broadcast, pola yang sama dengan `AlertService::notifyNewAlerts()` di
  Eksekusi — loop tiap admin, bukan satu recipient), karena assignment
  reviewer per-submission itu sendiri tindakan admin terpisah (task setelah
  ini). `context_type` pakai `challenge_submission` yang sudah terdaftar di
  morph map sejak Praktik 1.
- **`config/navigation.php['exploration_member']` slot "Praktik" sekarang
  `enabled: true`** mengarah ke `eksplorasi.praktik.index` — tidak ada lagi
  slot placeholder untuk role ini. Dashboard Eksplorasi juga sudah diisi
  data asli (3 challenge published terbaru), placeholder "Segera Hadir"-nya
  dihapus.

## Known gaps / backlog (dari Fase 5, Praktik 3, 2026-07-12)
- **`submission_type = 'file'` gap dari Praktik 2 SUDAH DITUTUP.** Upload
  file member sekarang aktif (`WithFileUploads`, disk `attachments` yang
  sudah ada, whitelist MIME baru: gambar jpg/png/gif/webp, PDF, video
  mp4/mov/webm, zip; maks 50MB — beda dari batas attachment Task 10MB,
  sengaja tidak disamakan). Kolom `file_name`/`file_path`/`file_size` di
  `challenge_submissions` (migrasi additif), BUKAN reuse tabel `Attachment`
  Task — beda domain RBAC total (owner/reviewer/admin vs project membership).
  Download lewat `ChallengeSubmissionDownloadController` baru (bukan reuse
  `AttachmentDownloadController`).
- **Lampiran Challenge (`challenge_attachments`, tabel baru)** — referensi
  dari admin (brief, starter asset), sengaja RBAC LONGGAR (siapa saja yang
  login boleh unduh, kecuali challenge masih draft baru dibatasi admin-only)
  — beda total dari RBAC ketat file submission member. Upload multi-file
  sekaligus di halaman Edit Challenge.
- **Antrian admin "Antrian Review Praktik"** (`config/navigation.php['admin']`,
  disabled sejak Praktik 1) sekarang aktif —
  `App\Livewire\Admin\Curriculum\Submissions\Index`, satu halaman global
  lintas-challenge (bukan per-challenge) karena assign-reviewer memang
  workflow lintas-challenge admin, konsisten pola Ideas/Tasks queue Eksekusi.
  Widget Dashboard Eksekusi "Antrian Review Praktik" (placeholder sejak
  §4.1) juga sudah data asli — submission yang `assigned_reviewer_id` =
  anggota eksekusi yang login, status pending.
- **Halaman review (`App\Livewire\Eksekusi\Praktik\Review`) DIPAKAI BERSAMA
  admin dan execution_member** — bukan dua halaman terpisah. RBAC di
  `mount()`: admin selalu lolos (PIC self-review, sesuai
  Rancangan_Modul_Praktik_v2.md §5), execution_member cuma lolos kalau
  `assigned_reviewer_id` PERSIS dirinya. Track map + isi submission
  ditampilkan lewat partial yang di-`@include` dari
  `resources/views/livewire/eksplorasi/praktik/` (`_track-map.blade.php`,
  `_submission-content.blade.php`) — sama persis dengan yang dipakai
  halaman member, bukan render terpisah.
- **Bug presisi floating-point ditemukan dan diperbaiki saat testing**:
  formula diminishing-return `points_reward * (0.7 ** (attempt-1))` untuk
  attempt ke-3 sempat menghasilkan 48 alih-alih 49 (0.7 tidak punya
  representasi biner eksak — `0.7 ** 2` = `0.48999999999999994` di PHP,
  `floor()` membulatkan ke bawah sebelum sempat menyentuh 49). Ditambal
  dengan epsilon kecil (`+ 1e-9`) sebelum `floor()` — perbaikan standar
  untuk kelas bug ini, BUKAN mengubah rumus atau timing pembulatannya.
- **Poin submission HANYA pernah diberikan di titik approve** (task ini),
  TIDAK PERNAH di titik submit (Praktik 2) — `PointService::award()` cuma
  dipanggil dari `Review::approve()`. Lock `reviewed_at` (cuma bisa diisi
  sekali, dicek eksplisit di `approve()`/`requestRevision()` sebelum
  mutasi apa pun) mencegah admin/reviewer meng-approve ulang submission
  yang sudah punya keputusan — teruji eksplisit (lihat
  `tests/Feature/Execution/PraktikReviewTest.php`).

## Known gaps / backlog (dari Fase 5 recon follow-up, 2026-07-12)
- **RESOLVED: `CurriculumContextBuilder` sekarang baca `content_blocks` untuk
  unit yang sudah dimigrasi lewat Editor Blok Konten**, bukan cuma kolom
  lama `units.content` (gap yang ditemukan `RECON_fase5_praktik.md` Area
  1.4). **Dicek live sebelum ditambal: NOL unit yang punya `content_blocks`
  saat itu (satu-satunya baris `content_blocks` yang ada saat itu milik
  ChallengeStep, bukan Unit)** — jadi bukan bug aktif yang sudah membocorkan
  konteks salah ke member manapun, tapi unit MIGRASI PERTAMA lewat editor
  pasti akan langsung kena kalau tidak ditambal duluan. `unitContextText()`
  baru: cek `content_blocks` dulu (ekstrak teks per tipe blok — heading/text/
  callout/code/list/table/custom_html jadi teks polos, image/video sengaja
  dilewati karena tidak ada teks bermakna), fallback ke kolom `content` lama
  kalau unit belum dimigrasi (backward compatible, nol regresi ke unit
  existing). `relatedUnits()` (pencarian kata kunci relevansi) juga
  diperluas mencakup `content_blocks` (JSON di-cast ke teks untuk `LIKE`),
  supaya unit yang sudah dimigrasi tidak "hilang" dari pencarian relevansi
  WEBI. `[SAJIKAN: ...]` directive stripping tetap diterapkan sebagai satu
  lapis defensif di teks akhir (dari sumber manapun) meski polanya memang
  cuma pernah dipakai di format lama.

## Known gaps / backlog (dari Fase 3 Batch 6, 2026-07-12)
- **Kanban board (`resources/views/livewire/eksekusi/projects/board.blade.php`)
  di-re-skin warna SAJA, mengikuti token final di docs/design-tokens.md**
  (`rounded-card`, `shadow-warm-xs`/`shadow-warm-md`, `bg-danger-soft`/
  `text-danger`, dll., menggantikan Tailwind mentah `bg-red-100`/`rounded-lg`).
  Mekanisme drag-and-drop native HTML5 (`draggable`, keempat `x-on:drag*`,
  kedua `dataTransfer` key `text/task-id`/`text/from-status`) dan
  `wire:click="changeStatus(...)"` di keempat tombol manual TIDAK disentuh
  sama sekali — dikonfirmasi lewat pembacaan baris-per-baris sebelum dan
  sesudah, satu-satunya yang berubah adalah nilai `class="..."`/`:class="..."`.
  Test suite tetap 486/486 (tidak ada file test yang diubah, membuktikan nol
  regresi fungsional). **Perlu verifikasi manual browser** (drag antar kolom +
  tombol manual) sebelum dianggap selesai — di luar kemampuan test otomatis
  untuk interaksi drag-and-drop sungguhan.

## Known gaps / backlog (dari Fase 3 Batch 5b, 2026-07-12)
- **Kondisi awal `Ideas\Index`/`index.blade.php` yang ditemukan sebelum
  direstrukturisasi**: satu daftar dengan tiga tombol filter status
  (Draft/Approved/Rejected) yang saling menggantikan (`statusFilter`,
  default `draft`) — bukan dua bagian sekaligus seperti yang diminta di
  batch ini.
- **Field `description` dan `purpose` di form usul ide (`Ideas\Create`)
  sekarang opsional** (validasi `nullable`, cuma `title` yang `required`).
  Tidak perlu migrasi skema — kolom `project_ideas.description`/`.purpose`
  di DB memang `text` NOT NULL tanpa `nullable()`, tapi string kosong `''`
  (bukan `null`) tetap sah untuk constraint itu, dan `ProjectIdeaService::propose()`
  selalu mengirim kunci itu (Livewire default property `''`, bukan hilang
  dari array tervalidasi). Label form ditambah keterangan "(opsional)"
  karena versi asli tidak punya penanda wajib/opsional sama sekali.
- **Halaman `Ideas\Index` sekarang menampilkan "Menunggu Keputusan" (status
  `draft`) dan "Riwayat" (status `approved`/`rejected`) SEKALIGUS** di satu
  halaman, bukan filter gonta-ganti. Markup kartu-per-ide (termasuk modal
  detail) diekstrak ke partial `_idea-card.blade.php` (pola `@include` yang
  sama dengan `_track-map.blade.php`/`_submission-content.blade.php` di
  Praktik 3C) supaya tidak duplikasi ~100 baris antara dua bagian. Badge
  status pakai token warna final (`text-success bg-success-soft` untuk
  disetujui, `text-danger bg-danger-soft` untuk ditolak), kartu pakai
  `rounded-card`/`border-muted/20`/`shadow-warm-xs` konsisten dengan bahasa
  visual Dashboard/Kanban yang sudah final. Logic `reject()`/`Ideas\Approve`
  tidak disentuh sama sekali.

## Known gaps / backlog (dari Fase 3 Batch 7b, 2026-07-12)
- **BATCH TERAKHIR Fase 3 — seluruh Fase 3 tuntas setelah batch ini.**
- **Halaman Profil (`App\Livewire\Profile\Edit`) sekarang punya kartu
  ringkasan avatar+level/poin, galeri avatar, kalender aktivitas (heatmap),
  dan kontribusi proyek** — nol migrasi, semua data dikonfirmasi sudah
  tersedia oleh `RECON_fase3_menyeluruh.md` §9. Layout diubah dari
  `max-w-lg` satu kolom jadi `max-w-5xl` dua kolom (`lg:grid-cols-2`, stack
  jadi satu kolom di bawah `lg`) — form email/nama/minat/password ASLI
  TIDAK diubah sama sekali (kolom kiri), sisi kanan berisi seluruh section
  baru batch ini.
- **Cakupan waktu heatmap: 4 bulan (120 hari), keputusan sendiri sesuai
  instruksi ("putuskan yang paling wajar").** Alasan: 3 bulan berisiko
  terlihat kosong untuk anggota yang baru mulai; grid gaya GitHub penuh
  setahun (52 kolom minggu) tidak muat di card sempit halaman Profil ini
  bahkan dengan `overflow-x-auto`. 4 bulan (~17 kolom minggu) jadi titik
  tengah yang tetap scroll-friendly di HP. Biaya query bukan faktor
  pembatas (satu user, `GROUP BY DATE(completed_at)`, ukuran tabel skala
  klub kecil) — keputusan murni soal tampilan/kegunaan, bukan performa.
- **Heatmap Eksekusi: TIDAK dibangun di batch ini, keputusan sendiri.**
  Eksplorasi punya `completed_at` yang SUDAH jadi sumber data
  aktif 2 fitur produksi lain (Log Aktivitas, tie-break leaderboard) —
  Eksekusi tidak punya kolom timestamp completion yang setara sekuat itu
  (task completion lewat `changeStatus()`, tidak ada `completed_at`
  tersendiri per task yang mencerminkan "kapan tuntas" secara bersih tanpa
  ikut kena update status lain). Membangunnya butuh keputusan sumber data
  baru yang tidak diminta eksplisit di prompt ini ("pertimbangkan... atau
  cukup Eksplorasi saja") — dipilih cukup Eksplorasi untuk sekarang, bisa
  diangkat lagi sebagai task terpisah kalau dibutuhkan.
- **Galeri avatar Eksekusi REUSE penuh, bukan bangun ulang** —
  `<livewire:eksekusi.avatar-picker />` (nested Livewire component)
  meng-embed `App\Livewire\Eksekusi\AvatarPicker` apa adanya (mount/choose/
  render sama persis dengan halaman `/eksekusi/avatar` yang sudah ada sejak
  Fase 2). **Temuan sampingan:** halaman itu ternyata TIDAK PERNAH ditautkan
  dari UI mana pun sebelum batch ini (dicek: nol referensi link di seluruh
  `resources/views`) — cuma bisa diakses dengan tahu URL persis. Sekarang
  akhirnya punya jalur UI nyata lewat Profil.
- **Kontribusi Per-Proyek Eksekusi TETAP TANPA kolom peran**, sesuai
  keputusan lama yang dicatat `RECON_project_member_peran.md` — diturunkan
  murni dari `ProjectMember` (keanggotaan) + `TaskAssignment` (hitung task
  per proyek), pola query identik dengan yang sudah dipakai
  `App\Livewire\Eksekusi\Dashboard`.
- **Slot "Info Mode Ganda" ditampilkan untuk `exploration_member`/
  `execution_member`, TIDAK untuk admin** — dasarnya `User::hasDualModeCapability()`
  (Fase 8, selalu `false` untuk sekarang) yang secara docblock menyebut
  mode ganda sebagai konsep lintas Eksplorasi/Eksekusi untuk anggota biasa;
  admin sudah punya akses penuh ke semua modul jadi placeholder ini tidak
  relevan untuknya. Pola dashed-border+badge "Segera Hadir" identik dengan
  slot placeholder lain (`livewire/eksekusi/dashboard.blade.php`).
- **RESOLVED (2026-07-12, ditemukan Aye di layar lebar): ruang kosong di
  sisi kanan halaman Profil.** Akar masalah GANDA: (1) root div halaman
  ini ber-`max-w-5xl` SENDIRI di atas `max-w-7xl` yang sudah disediakan
  `<main>` di layout — beda dari halaman Dashboard (final) yang tidak
  pernah menambah max-w sendiri; (2) wrapper `max-w-5xl` itu TANPA
  `mx-auto`, jadi nempel ke kiri dan sisa lebar antara 5xl dan 7xl selalu
  jatuh di kanan sebagai ruang kosong. Diperbaiki: `max-w-5xl` dihapus
  total (ikut pola Dashboard, biarkan `<main>` yang menentukan lebar), dan
  rasio grid kolom diubah dari 1:1 (`lg:grid-cols-2`) ke 2:3
  (`lg:grid-cols-5`, kiri `col-span-2` form/kanan `col-span-3` galeri+
  heatmap) supaya kolom kanan yang kontennya lebih butuh ruang (grid 5
  avatar, heatmap mingguan) dapat porsi lebih besar. `items-stretch`
  (bukan `items-start`) disamakan dengan pola grid besar Dashboard
  Eksplorasi.
- **RESOLVED (2026-07-12): Ganti Password sekarang modal, bukan form yang
  selalu terbuka di halaman utama.** Tombol "Ganti Password" (di dalam
  card ringkas menggantikan form lama) membuka modal bergaya identik
  modal detail Project Ideas (`_idea-card.blade.php`: x-cloak, x-transition,
  backdrop click-to-close, Escape-to-close). `Edit::changePassword()`
  TIDAK diubah logic-nya sama sekali — satu-satunya tambahan adalah
  `$this->dispatch('password-changed')` di baris terakhir jalur SUKSES
  (setelah flash message diset), dikonsumsi Alpine
  (`x-on:password-changed.window="passwordModalOpen = false"`) supaya
  modal menutup otomatis. Dispatch itu TIDAK pernah terpanggil di jalur
  validasi gagal atau password lama salah (keduanya `return` sebelum baris
  itu), jadi modal tetap terbuka menampilkan error kalau gagal — perilaku
  ini tidak butuh test baru karena murni derivasi dari urutan kode yang
  sudah ada, bukan logic baru.
- **RESOLVED (2026-07-12): grid Kalender Aktivitas diganti dari kotak
  ukuran tetap (`h-3 w-3` di dalam `flex w-fit`) jadi CSS Grid yang
  melebar proporsional mengisi lebar card** (`grid-template-columns:
  repeat(N, minmax(0, 1fr))`, N = jumlah minggu — WAJIB inline style,
  bukan class Tailwind, karena N baru diketahui saat request, tidak bisa
  di-scan Tailwind saat build) + `grid-auto-flow: column` supaya urutan
  flat `$heatmapWeeks` (minggu demi minggu) otomatis mengisi kolom demi
  kolom tanpa nested grid per minggu. Tiap kotak `aspect-square w-full`
  supaya selalu persegi mengikuti lebar kolom yang dihitung otomatis.
  `min-width` (10px/kolom) + `overflow-x-auto` pada pembungkus jadi
  fallback KHUSUS layar sangat sempit (banyak kolom di layar kecil) —
  bukan mekanisme utama lagi seperti sebelumnya. **Cakupan waktu
  diperpanjang dari 4 ke 6 bulan (182 hari = 26 minggu, `HEATMAP_DAYS`
  di `App\Livewire\Profile\Edit`)**: di bawah layout lama (kotak tetap +
  scroll), makin banyak minggu = makin lebar total grid = makin perlu
  scroll — jadi cakupan pendek sengaja dipilih supaya tidak overflow.
  Layout proporsional baru justru terbalik: makin banyak minggu = makin
  RAPAT/kecil tiap kotak di lebar card yang sama, bukan overflow — jadi
  batasan lama tidak berlaku lagi. 6 bulan dipilih (bukan setahun penuh
  ala GitHub) supaya kepadatan grid terasa proporsional di lebar card
  biasa tanpa membuat riwayat anggota baru mayoritas kotak kosong (data
  aktivitas nyata cuma ada sejak anggota itu mulai, bukan sejak setahun
  lalu).
- **RESOLVED (2026-07-12): tooltip interaktif per kotak ditambahkan ke
  Kalender Aktivitas** — hover (desktop) atau tap (mobile) satu kotak
  menampilkan "N aktivitas pada [tanggal lengkap Indonesia]" atau "Tidak
  ada aktivitas pada [tanggal]" untuk hari tanpa aktivitas (tetap
  informatif, bukan diam). **SATU elemen tooltip** dipindah-pindah lewat
  state Alpine (`tooltip.top`/`left`/`text`/`above`), bukan satu elemen
  per kotak (182 elemen sekaligus terlalu berat) — posisi pakai
  `position: fixed` + `getBoundingClientRect()` (lolos dari
  `overflow-hidden` card tanpa hitung offset tambahan), dengan clamp
  horizontal (`Math.max`/`Math.min` terhadap `window.innerWidth`) dan flip
  vertikal (di bawah kotak kalau kotak terlalu dekat ke tepi atas viewport)
  supaya tooltip tidak pernah terpotong di luar layar. Teks tooltip
  (termasuk format tanggal Indonesia via `Carbon::locale('id')`, BUKAN
  `app.locale` yang default `en` di project ini) dihitung sekali di server
  per kotak lewat atribut `data-date`/`data-tooltip`, dibaca Alpine dari
  `event.currentTarget.dataset` saat hover/tap — nol request server
  tambahan. Test baru
  (`test_each_heatmap_cell_maps_to_its_correct_calendar_date_and_activity_count`)
  membuktikan mapping tanggal-ke-kotak benar (bukan cuma jumlah kotak)
  lewat regex `\s+` antara atribut, karena atribut multi-baris di Blade
  menghasilkan newline+indentasi di HTML asli, bukan satu spasi.
- **RESOLVED (2026-07-12): kelima tingkat Galeri Avatar Fox di Profil
  kelihatan identik saat terkunci/dim, root cause DIKONFIRMASI di efek
  filter, bukan bug pemanggilan atau regresi komponen.** Diverifikasi
  dulu sebelum menyentuh kode: `:tingkat="$tier"` di loop galeri mengikat
  angka yang benar per card (1-5, bukan hardcode), dan
  `resources/views/components/avatar/fox.blade.php` sendiri memang sudah
  render detail berbeda per tingkat (aksen kerah cyan tingkat 3+, aksen
  pelipis tingkat 4+, aksen bahu+sparkle+helm tingkat 5) — tidak ada
  regresi di komponen itu. Akar masalah murni `opacity-30 grayscale`
  (grayscale 100% + opacity sangat rendah) yang terlalu agresif untuk SVG
  sekecil itu (`size="sm"`, 40x40px, viewBox 12x12 -- aksen 1-2 unit cuma
  ~3-7px layar), menghancurkan beda warna asli (cyan vs oranye vs abu)
  jadi nyaris tidak kelihatan. Diperbaiki jadi `opacity-60 grayscale-50`
  (desaturasi SEBAGIAN, bukan penuh) di
  `resources/views/livewire/profile/edit.blade.php` — efek "terkunci/dim"
  secara konsep TIDAK dihapus (tetap jelas beda dari tingkat yang
  terbuka), cuma dikurangi intensitasnya supaya siluet pembeda tiap
  tingkat tetap samar-samar terlihat.

## Known gaps / backlog (dari Fase 4 Batch 3, 2026-07-12)
- **BATCH TERAKHIR Fase 4 — seluruh Fase 4 tuntas setelah batch ini.**
- **Admin CRUD Kelola Evaluasi dibangun lengkap** —
  `App\Livewire\Admin\Curriculum\Units\Evaluations\{Index,Create,Edit}`,
  diakses lewat tombol "Kelola Evaluasi" di halaman Edit Unit. Satu unit
  bisa punya banyak soal campuran tipe (mis. `multiple_choice` + `essay`
  dalam satu unit, sesuai kasus Unit 5.8). Form per tipe TIDAK generik —
  `multiple_choice` (repeater opsi + radio kunci jawaban), `matching`
  (repeater pasangan kiri-kanan, `correct_answer` diturunkan OTOMATIS
  lewat `array_column($pairs, 'right', 'left')`, tidak ada input kunci
  terpisah), `ordering` (lihat poin di bawah), `essay`/`practice` (cuma
  `question_text`). Logic repeater (~150 baris, dipakai Create DAN Edit)
  diekstrak ke trait `Evaluations\Concerns\HandlesQuestionForm` — beda
  dari pola Modules/Units/ChallengeSteps yang boleh duplikasi (field
  sedikit), kompleksitas di sini sepadan diekstrak (prinsip sama dengan
  alasan ContentEditor digeneralisasi di Praktik 1). Reorder pakai
  `CurriculumReorderService` yang sudah ada, digeneralisasi menerima
  parameter `$column` (default `order_number`, backward compatible ke
  semua caller lama) karena `unit_evaluations` pakai nama kolom
  `sort_order`, bukan `order_number` seperti Modules/Units/ChallengeSteps.
- **GOTCHA PENAMAAN (diperingatkan eksplisit di rancangan, dicek dua kali
  sebelum coding): `unit_evaluations.question_type` TIDAK PAKAI prefix
  `quiz_` (`multiple_choice` dst) — beda dari `units.evaluation_type` yang
  PAKAI prefix (`quiz_multiple_choice` dst), kolom berbeda di tabel
  berbeda.** Batch ini HANYA menyentuh `question_type`, tidak pernah
  menulis ke `evaluation_type`. Dicatat eksplisit di docblock
  `HandlesQuestionForm` untuk maintainer berikutnya.
- **RESOLVED (2026-07-12): BUG DATA LAMA yang ditemukan (SEMUA 3 baris
  `ordering` dari `CurriculumSeeder` kena bug options===correct_answer —
  soal SDLC, soal proses membuka website, soal workflow kolaborasi tim)
  sudah diperbaiki, atas konfirmasi eksplisit Aye.** Dikerjakan LEWAT form
  admin "Kelola Evaluasi" yang sesungguhnya (bukan edit seeder atau
  `UPDATE` manual) — didorong via `Livewire::test(Edit::class, ...)`
  (mekanisme yang sama dipakai test otomatis, dijalankan di sini lewat
  `tinker` terhadap database asli, bukan database test) supaya validasi/
  `save()`/`CurriculumReorderService` yang sungguhan tetap dilalui persis
  seperti submit browser. `correct_answer` TIDAK disentuh (dipertahankan
  sesuai instruksi — sudah benar secara konten); `options` diacak lewat
  `shuffleDisplayOrder()` (fitur "Acak Otomatis" yang sudah ada di form),
  dengan pengecekan tambahan "well-scrambled" (bukan cuma beda, tapi
  &le;50% posisi yang sama dengan urutan kunci) sebelum disimpan, supaya
  hasilnya benar-benar menguji, bukan cuma tukar 1-2 posisi. Diverifikasi:
  `question_text`/`sort_order`/`correct_answer` ketiganya identik sebelum-
  sesudah; query ulang seluruh tabel `unit_evaluations` mengonfirmasi 0
  baris `ordering` tersisa dengan `options === correct_answer`.
- **Perbaikan bug `ordering` (keputusan sudah dikunci di rancangan):**
  form baru pakai representasi INDEKS (bukan salinan teks) untuk
  memisahkan "Urutan Kunci Jawaban" (`orderingItems`, urutan penulisan =
  correct_answer) dari "Urutan Tampil ke User" (`orderingDisplayIndexes`,
  array indeks ke `orderingItems`, disusun terpisah lewat
  `moveDisplayOrder()`/`shuffleDisplayOrder()`). Sengaja BUKAN dua kolom
  teks bebas independen — kalau admin mengetik ulang item yang sama dua
  kali (sekali untuk "kunci", sekali untuk "tampil"), risiko typo/beda
  kata antara keduanya nyata. Representasi indeks menjamin kedua array
  SELALU berisi teks yang identik, cuma urutannya beda — mengedit teks
  sebuah item otomatis konsisten di kedua sisi, tidak pernah "basi".
  Tombol "Acak Otomatis" (`shuffleDisplayOrder()`) ditambahkan sebagai
  titik awal cepat sesuai anjuran rancangan ("idealnya diacak"), admin
  tetap bisa menyempurnakan manual lewat tombol naik/turun sesudahnya.
- **TEMUAN KRITIS (bukan gap, tapi bug keamanan AKTIF yang sudah lama
  ada): `[EVALUATION_BANK]` TERNYATA SUDAH terintegrasi ke konteks WEBI
  sejak task 2.5 Batch 3 — TAPI lewat jalur terpisah
  (`App\Services\Webi\EvaluationBankBuilder` + `SystemPromptBuilder`),
  BUKAN lewat `CurriculumContextBuilder` seperti dugaan awal prompt ini.**
  Dicek dulu sebelum asumsi ini gap baru (sesuai instruksi) — ternyata
  bukan gap sama sekali, infrastrukturnya utuh. **TAPI ditemukan
  `EvaluationBankBuilder::toPromptText()` MEMANG menyertakan
  `kunci_jawaban` (correct_answer) literal ke teks prompt yang dikirim ke
  Gemini** — dikonfirmasi lewat test yang sudah ada
  (`GuardrailTest::test_evaluation_bank_for_current_unit_is_injected_into_the_system_prompt`
  sebelumnya bahkan MENGASSERT string "Mobile App", jawaban asli soal
  WhatsApp, ADA di prompt — bukti bug ini bukan cuma teori). Ini bertentangan
  langsung dengan syarat keamanan eksplisit prompt ini ("JANGAN sertakan
  correct_answer ke konteks WEBI sama sekali"), jadi diperbaiki sebagai
  bagian dari batch ini meski secara teknis infrastrukturnya dibangun di
  task lampau. `toPromptText()` sekarang cuma kirim `unit_id`/`tipe`/`soal`
  — `correct_answer` TETAP ada di Collection `$bank` untuk dipakai
  `GuardrailService::checkOutputAgainstAnswers()`/`checkEvalDetectionInput()`
  (perbandingan SERVER-SIDE terhadap balasan Gemini SETELAH fakta, jalur
  yang sama sekali terpisah dari apa yang dikirim KE Gemini). Test lama
  diperbaiki (hapus assertion "Mobile App" yang menguji perilaku yang
  salah), test baru
  `GuardrailTest::test_correct_answer_never_appears_in_the_prompt_sent_to_gemini`
  ditambahkan pakai kunci jawaban unik buatan (bukan istilah nyata seperti
  "Mobile App" yang kebetulan juga muncul wajar di materi kurikulum) supaya
  pembuktiannya tidak ambigu.

## Known gaps / backlog (dari Fase 6, 2026-07-12)
- **TEMUAN & RESOLVED (dikonfirmasi Aye sebelum dieksekusi): database dev
  (`webi_space`) ternyata punya 19 baris Modul sampah/duplikat sebelum
  perhitungan threshold bisa dipercaya.** Modul 1 ("Dunia Software
  Development") dan Modul 2 ("Bagaimana Website Bekerja") masing-masing
  punya 10 baris (1 asli dari `CurriculumSeeder` 2026-07-04 + 9 duplikat
  berisi konten placeholder mirip-tapi-beda, dibuat berulang 2026-07-11
  malam — pola timestamp-nya sangat menunjukkan hasil script/tinker yang
  jalan berulang kali di sesi debugging sebelumnya, bukan klik manual satu
  per satu), plus 1 modul "tes" di order_number 13. **Dicek dulu sebelum
  hapus apa pun**: 0 referensi dari `UserUnitProgress`/`EvaluationSubmission`/
  `CheckpointCompletion`/`ForumThread`/`UserExplorationProgress.current_unit_id`
  ke 73 unit dan 18 checkpoint turunan modul sampah itu — aman dihapus,
  tidak ada progres anggota nyata yang hilang. Setelah dibersihkan: tepat
  10 modul, 67 unit, 9 checkpoint (cocok dengan dokumentasi lama). 37 user
  dan 26 `UserExplorationProgress` dev yang sudah ada TIDAK disentuh sama
  sekali (bukan `migrate:fresh`, query terarah saja).
- **Total poin maksimal realistis (dihitung ULANG dari DB yang sudah
  bersih, bukan asumsi): Materi 770 + Checkpoint 225 (25×9, Modul 4 tidak
  punya checkpoint) + Praktik 25 (estimasi) = 1020.** Catatan eksplisit
  soal Praktik: satu-satunya Challenge `published` yang ada saat dihitung
  literally berjudul "tes 1" (25 poin) — bukan konten Praktik sungguhan,
  cuma placeholder QA lain. Angka Praktik ini SENGAJA dipakai apa adanya
  sesuai instruksi ("estimasi dasar" dari kondisi SEKARANG), tapi
  faktanya saat ini kontribusinya nyaris nol karena belum ada Challenge
  asli — akan naik signifikan begitu admin publish Challenge sungguhan,
  itulah kenapa mekanisme recalculate (di bawah) dibangun sebagai command
  yang bisa dijalankan ulang, bukan angka sekali hitung.
- **Threshold 6 level baru disimpan di `config/exploration.php`
  (`level_thresholds`), TERPISAH dari `fox_avatar_tiers` yang tidak
  disentuh sama sekali**: 0 / 204 / 306 / 510 / 714 / 918 — proporsional
  terhadap jumlah modul per level (2/1/2/2/2/1 dari 10 modul total),
  BUKAN pembagian rata 6 bagian sama besar. Beda metodologi dari draft
  2.3 lama (yang pakai jumlah EXACT per-modul karena waktu itu cuma
  Materi+Checkpoint yang unit/modul-scoped) — Praktik tidak terikat ke
  modul/level tertentu (Challenge bisa dikerjakan anggota kapan saja),
  jadi tidak ada "jumlah exact saat modul N selesai" yang bisa dihitung
  untuk porsi itu; pembobotan proporsional adalah padanan paling
  bermakna yang diminta untuk kasus ini.
- **RESOLVED (2026-07-12): dampak ke anggota existing dikonfirmasi 0 dari
  26 `UserExplorationProgress`, dan `exploration:recalculate-levels`
  SUDAH dieksekusi sungguhan (bukan cuma `--dry-run`) atas persetujuan
  eksplisit Aye.** Sebelum eksekusi, `--dry-run` dijalankan sekali lagi
  sebagai konfirmasi akhir — hasilnya identik dengan laporan sebelumnya
  (0 dampak, tidak ada perubahan kondisi data di antara laporan dan
  eksekusi), jadi lanjut ke eksekusi sungguhan sesuai rencana. Verifikasi
  pasca-eksekusi: query ulang seluruh 26 baris `UserExplorationProgress`
  mengonfirmasi `current_level`/`level_name` tiap baris cocok persis
  dengan hasil `PointService::resolveLevel()` di bawah `level_thresholds`
  baru — 0 mismatch. Karena dampaknya nihil (poin semua akun dev jauh di
  bawah threshold level 2 manapun), keputusan cara komunikasi ke anggota
  terdampak (disebut di bawah) TETAP belum relevan diuji di dev DB ini —
  masih perlu diputuskan Aye sebelum command ini dijalankan lagi di
  lingkungan manapun yang punya user nyata dengan progres berarti
  (khususnya production, kalau/ketika threshold perlu disesuaikan lagi).
- **Command baru `php artisan exploration:recalculate-levels`
  (`App\Console\Commands\RecalculateExplorationLevels`), idempotent, bisa
  dijalankan ulang kapan saja** (cuma menyentuh baris yang level
  hasil-hitungnya benar-benar beda dari yang tersimpan) — dirancang untuk
  dijalankan ULANG setiap kali `level_thresholds` disesuaikan lagi di
  masa depan (paling mungkin karena Praktik terus bertambah). Opsi
  `--dry-run` untuk preview tanpa menyimpan; menampilkan tabel
  anggota+poin+level lama/baru, dan warning eksplisit kalau ada
  penurunan level di antara perubahannya. **SENGAJA TIDAK mengirim
  notifikasi apa pun** — cara mengomunikasikan perubahan level akibat
  threshold (beda dari notifikasi "naik level" biasa karena baru dapat
  poin) adalah keputusan terbuka yang perlu dikonfirmasi Aye dulu,
  bukan diasumsikan/dibangun diam-diam di sini. `PointService::resolveLevel()`
  diubah dari `protected` ke `public` supaya command ini bisa reuse
  logic threshold yang sama persis, bukan reimplementasi terpisah.

## Known gaps / backlog (dari Fase 7 Batch 1a, 2026-07-12)
- **Halaman proyek sekarang berstruktur tab** (Kanban default, Roadmap/
  Gantt/Kalender/Forum Proyek placeholder "Segera Hadir", Anggota
  sungguhan) menggantikan `Projects\Show` lama (DIHAPUS, logicnya dipecah
  ke `ProjectHeader` [nested, metadata+status+Milestone CRUD] dan
  `Projects\Tabs\Anggota` [full page, member CRUD]). Tab bar
  (`<x-eksekusi.project-tabs>`) pakai `wire:navigate`, bukan reload penuh —
  konsisten pola SPA yang sudah dipakai di seluruh app. Header proyek
  (`<livewire:eksekusi.projects.project-header>`) dirender di ATAS setiap
  tab, bukan digabung ke satu tab spesifik (satu dari dua opsi yang
  ditawarkan task, dipilih supaya info proyek+milestone selalu terlihat
  apa pun tab yang sedang dibuka).
- **RBAC "member proyek" dikonsolidasi jadi middleware `project.member`
  (`App\Http\Middleware\EnsureProjectMembership`), bukan trait/service.**
  Alasan pilih middleware: cek ini murni soal ROUTE MANA yang boleh
  diakses (bukan logic bisnis di dalam komponen), jadi middleware level
  route lebih cocok daripada trait yang masih bisa "lupa di-`use`" di
  komponen baru — middleware otomatis melindungi SEMUA route dalam grup
  `eksekusi/projects/{project}/*` tanpa perlu diingat ulang tiap tab baru
  ditambah batch-batch berikutnya. Middleware resolve `Project` dari
  parameter route `{project}` ATAU (untuk `/eksekusi/tasks/{task}` yang
  tidak nested di bawah project) dari `{task}->project`. **Ditemukan 4
  tempat duplikasi, bukan 3 seperti estimasi recon** — `Tasks\Create`
  ternyata punya snippet abort_if identik juga, baru ketahuan saat
  membangun batch ini.
- **Route direstrukturisasi**: `eksekusi.projects.show` (URL bare
  `/eksekusi/projects/{project}`) TETAP ADA dan sekarang me-render Kanban
  langsung (BUKAN redirect) — sengaja begini supaya SEMUA link existing di
  seluruh app (Admin\Dashboard, Profile, Project Ideas card, dst.) yang
  sudah memakai URL bare ini tidak perlu diubah satu pun. `eksekusi.projects.board`
  di-rename jadi `eksekusi.projects.kanban` (URL `/kanban`, konsisten
  dengan 5 tab lain: `/roadmap`, `/gantt`, `/kalender`, `/forum`,
  `/anggota`) — kedua route (`show` dan `kanban`) memetakan ke komponen
  `Board::class` yang SAMA persis.
- **Kanban (`Board.php` + `board.blade.php`) — mekanisme drag-drop
  dikonfirmasi 100% utuh, dibuktikan lewat diff baris-per-baris**: satu-
  satunya perubahan di `board.blade.php` adalah penambahan `mt-6` (class
  spacing) pada div pembungkus header lama + blok header/tab BARU
  ditambahkan DI ATASNYA — nol karakter berubah di blok drag-drop (`x-data`,
  `x-on:drag*`, `dataTransfer` key, `wire:click="changeStatus(...)"`).
  Satu-satunya perubahan di `Board.php` adalah abort_if() di `mount()`
  dihapus (pindah ke middleware) — method `changeStatus()` itu sendiri
  tidak disentuh. Test `TaskManagementTest.php` yang sudah ada (memanggil
  `Board::changeStatus()` langsung, jalur yang SAMA dipanggil handler
  drop) tetap 100% lolos tanpa diubah — bukti regresi fungsional nol.
  **Verifikasi drag-and-drop sungguhan di browser TETAP wajib dicek manual
  oleh Aye** — sama seperti keterbatasan lingkungan kerja ini di Fase 3
  Batch 6, tidak berubah.

## Known gaps / backlog (dari Fase 7 Batch 1b, 2026-07-12)
- **Detail Task sekarang panel slide-over dari kanan** di tab Kanban,
  BUKAN halaman penuh lagi — dikonfirmasi lewat diff eksplisit bahwa
  `App\Livewire\Eksekusi\Tasks\Show` (komponen lama) dipakai APA ADANYA
  sebagai nested component (`<livewire:eksekusi.tasks.show>`), TIDAK
  dibangun ulang — satu-satunya perubahan file itu sendiri adalah
  menghapus abort_if() di `mount()` (sudah terjadi di Batch 1a). Panel
  dikendalikan `Board::$openTaskId` (state Livewire) di-entangle dua arah
  (`@entangle(...)->live`) ke `panelOpen` Alpine di root `board.blade.php`
  — animasi slide (`translate-x-full` ke `translate-x-0`) murni Alpine,
  sinkron balik ke server saat backdrop/tombol tutup diklik. Pola transisi
  dicontek dari drawer mobile sidebar riwayat WEBI Chat (`chat.blade.php`),
  diterapkan di SEMUA breakpoint di sini (bukan cuma mobile) karena task
  memang dibuka-tutup berulang dalam satu sesi Kanban.
- **Keamanan**: karena `Tasks\Show::mount()` tidak lagi punya cek membership
  sendiri (dipindah ke middleware route di Batch 1a, dan panel ini TIDAK
  punya route sendiri), `Board.php` sekarang jadi SATU-SATUNYA penjaga supaya
  panel tidak bisa dibuka untuk task dari proyek lain — divalidasi di DUA
  entry point (`openTask()` action dan `?task=` query param di `mount()`),
  keduanya cek `$this->project->tasks()->where('id', $taskId)->exists()`
  sebelum mengizinkan `$openTaskId` di-set. Diuji eksplisit
  (`TaskPanelTest::test_open_task_refuses_a_task_from_a_different_project`
  dan `test_query_param_is_ignored_when_the_task_does_not_belong_to_the_project`).
- **URL lama `/eksekusi/tasks/{task}` tetap berfungsi, sekarang redirect**
  (bukan halaman) ke tab Kanban proyek terkait dengan `?task={id}` —
  `Board::mount()` baca query param itu untuk auto-buka panel begitu
  halaman dimuat, jadi link lama (notifikasi, bookmark) TIDAK rusak.
  Dipilih dari opsi yang sama-sama ditawarkan prompt task ini (redirect +
  query param, bukan strategi lain) karena paling langsung reuse mekanisme
  buka-panel yang sama dipakai jalur klik.
- **BUG FRAMEWORK ditemukan dan diakali saat membangun redirect di atas**:
  helper global `redirect()` (dan resolusi container `'redirect'`) TERNYATA
  dikembalikan sebagai `Livewire\Features\SupportRedirects\Redirector`
  (bukan `Illuminate\Routing\Redirector`/`RedirectResponse` biasa) saat
  dipanggil dari closure ROUTE POLOS di tengah request HTTP sungguhan
  (TIDAK terjadi di tinker/console) — melempar `TypeError` kalau
  hasilnya langsung dikembalikan sebagai response route. Diakali dengan
  `new \Illuminate\Http\RedirectResponse(...)` langsung (bypass helper/
  container sepenuhnya) di closure route `eksekusi.tasks.show`. Cuma
  ditemukan karena route ini KEBETULAN satu-satunya closure route "polos"
  (bukan Livewire component) di app yang melakukan redirect — worth
  diingat kalau ada closure route lain yang butuh redirect ke depannya.
- **Reused, bukan dibangun ulang**: komentar/attachment/progress
  update/reassign/ubah deadline-prioritas/hapus task — SEMUA logic ini
  tetap 100% di `Tasks\Show.php` yang sudah ada, nol baris diubah di file
  itu pada batch ini. Test coverage lama (`TaskManagementTest`,
  `TaskCollaborationTest`, `AttachmentDownloadTest`) tetap 100% lolos
  tanpa diubah — bukti langsung fungsi-fungsi itu tidak beregresi.

## Known gaps / backlog (dari Fase 7 Batch 2a, 2026-07-13)

- **Subtask (`tasks.parent_task_id`) dibangun lengkap, DENGAN audit wajib
  ke-18 titik query Task lama (bukan cuma 12 seperti estimasi awal recon —
  "minimal 12" ternyata 18 setelah dihitung baris-per-baris, termasuk 1
  titik di `App\Livewire\Profile\Edit` yang ketemu lewat grep sweep, di
  luar 5 file yang dipetakan recon).** Migrasi backup database dijalankan
  dulu (`mysqldump` ke scratchpad) sebelum migrasi dijalankan, sesuai
  instruksi wajib. Subtask = row `Task` biasa dengan `parent_task_id`
  terisi (bukan tabel terpisah, self-referencing `nullOnDelete()`) —
  mewarisi `project_id`/`milestone_id` induknya otomatis (tidak ada
  picker milestone terpisah di form subtask, `milestone_id` di skema
  TETAP NOT NULL, tidak ada migrasi tambahan untuk itu).
- **Keputusan cakupan subtask DIKONFIRMASI via `AskUserQuestion`
  (2026-07-12, genuinely ambigu, bukan diasumsikan sendiri):** subtask
  IKUT dihitung di alert (overdue/due soon/stalled/inactive member) dan
  hitungan personal "task saya" (Dashboard Eksekusi anggota, ringkasan
  per-anggota Dashboard Admin, kontribusi per-proyek di Profil) — karena
  subtask punya deadline/assignee nyata dan biar tidak pernah "hilang"
  dari radar siapa pun. TAPI DIKECUALIKAN TOTAL dari: Kanban board
  (`Projects\Board`, di ke-4 titik query-nya sekaligus tempat subtask
  TIDAK BISA dibuka sebagai panel sendiri — cuma pernah muncul nested di
  mini-list panel task induknya), `Project`/`Milestone::progressPercentage()`
  (rollup %), dan hitungan ringkasan proyek Dashboard Admin
  (`active_members`)/Dashboard Eksekusi (`task_count` per kartu proyek).
  **Perpanjangan keputusan yang SAMA (bukan pertanyaan baru, tapi turunan
  logis darinya) ke satu titik lagi yang recon temukan**: gate penyelesaian
  proyek di `ProjectService::changeStatus()` (tidak bisa mark `completed`
  kalau ada task belum `done`) SENGAJA TIDAK dikecualikan dari subtask —
  subtask yang masih outstanding tetap memblokir proyek ditandai selesai,
  supaya proyek tidak bisa "selesai" sambil ada pekerjaan asli yang
  ditinggalkan. Semua 18 titik didokumentasikan lewat komentar inline di
  kode masing-masing (bukan cuma di sini), supaya keputusan per titik
  tetap terlihat di tempat kodenya sendiri.
- **UI subtask ada di dalam panel slide-over task induk (dari Batch 1b)**:
  mini-list (title, assignee, dropdown status todo/in_progress/in_review/
  done — BUKAN mini-Kanban penuh, sesuai dokumen rancangan) + form "+
  Tambah Subtask" (title wajib, assignee opsional, deadline opsional).
  "Deadline opsional" secara UI diwujudkan dengan default ke deadline task
  induk kalau dikosongkan (bukan dengan menjadikan kolom `deadline`
  nullable — tidak ada migrasi tambahan di luar `parent_task_id` yang
  diotorisasi task ini). Status subtask (`Tasks\Show::changeSubtaskStatus()`)
  memakai `TaskService::changeStatus()` yang SAMA (validasi transisi/RBAC
  sama persis), sepenuhnya independen dari status task induk — mengubah
  status subtask TIDAK PERNAH menyentuh status task induknya, dibuktikan
  test eksplisit. Subtask TIDAK BISA melahirkan subtask lagi (dicegah
  `abort_if` di `addSubtask()`, walau secara UI normal tidak pernah bisa
  terjadi karena subtask tidak pernah dapat panel sendiri).
- **Drag-drop Kanban dikonfirmasi TIDAK tersentuh sama sekali di batch
  ini** — `resources/views/livewire/eksekusi/projects/board.blade.php`
  nol kali dibuka lewat Edit tool sepanjang batch ini (cuma dibaca sekali
  di awal untuk konteks); satu-satunya perubahan terkait Kanban adalah di
  `Projects\Board.php` (PHP, bukan Blade) — menambah `whereNull('parent_task_id')`
  ke 4 query yang sudah ada (`mount()`, `openTask()`, `changeStatus()`,
  `render()`), tidak ada baris `x-data`/`x-on:drag*`/`draggable`/`dataTransfer`
  yang disentuh.
- **Test baru `tests/Feature/Execution/SubtaskTest.php` (20 test) ditulis
  KHUSUS untuk membuktikan hasil audit** (bukan sekadar CRUD subtask
  generik) — subtask tidak muncul di Kanban, tidak bisa dibuka sebagai
  panel sendiri (baik lewat action maupun query param), tidak bisa
  diubah status lewat aksi Kanban (`ModelNotFoundException` dibuktikan
  eksplisit), tidak mendilusi progress %, tidak masuk hitungan
  `active_members`/`task_count` rollup — SEKALIGUS subtask overdue/due-soon
  terdeteksi alert, subtask masuk hitungan personal 3 tempat (Dashboard
  Eksekusi, ringkasan admin per-anggota, kontribusi Profil), dan subtask
  outstanding memblokir penyelesaian proyek. Full suite naik dari 529 ke
  **549/549 lolos** (1862 assertion), `npm run build` sukses.

## Known gaps / backlog (dari Fase 7 Batch 2b, 2026-07-13)

- **Kalender dibangun lengkap dari nol** (tabel `calendar_events`, migrasi
  baru — backup database dijalankan lebih dulu). Dua kategori sesuai
  `docs/v_2.0/Rancangan_Modul_Manajemen_Proyek_v2.md` §Kalender: **Kegiatan**
  (warna ink/gelap — deadline task besar + target milestone, SELALU
  derived on-the-fly lewat `App\Services\Execution\CalendarService`, TIDAK
  PERNAH ditulis ke `calendar_events`) dan **Acara** (warna accent/cyan —
  baris nyata di `calendar_events`, diinput manual). Kegiatan otomatis
  memfilter `parent_task_id IS NULL` — konsisten hasil audit Fase 7 Batch
  2a, subtask tidak pernah muncul di kalender manapun.
- **Keputusan enum `type` pada `calendar_events` SENGAJA MENYIMPANG dari
  daftar yang diajukan prompt (`meeting`/`deadline`/`personal`/`milestone`)
  setelah dicek dulu ke dokumen rancangan (sesuai instruksi eksplisit
  "cek dulu apakah dokumen sudah spesifik").** Dokumen cuma mengenal 2
  kategori TAMPILAN (Kegiatan vs Acara, ditentukan sumber data, bukan
  kolom `type`) — jadi 4 nilai yang diajukan salah kategori kalau
  diterapkan mentah: `deadline`/`milestone` adalah Kegiatan yang TIDAK
  PERNAH jadi baris di tabel ini (jelas bertentangan dengan larangan
  "JANGAN duplikasi data" di batasan task ini), dan `personal` mencampur
  dua dimensi berbeda yang sebenarnya ortogonal (isi acara vs cakupan
  pemilik acara — cakupan personal-vs-proyek SUDAH direpresentasikan oleh
  `project_id` nullable, menambah `type=personal` akan membuka state
  ambigu "type=personal tapi project_id juga terisi"). Diputuskan sendiri:
  `type` cuma sub-kategori ISI dari Acara — `meeting`/`competition`/`other`
  (default), diturunkan dari contoh literal dokumen sendiri ("kompetisi,
  meeting, kumpulan rutin, hal organisasi"). Dijelaskan lengkap di
  docblock migrasi `2026_07_13_000000_create_calendar_events_table.php`
  dan `App\Models\CalendarEvent`.
- **Query dua tampilan (tab Kalender proyek vs Kalender Personal) benar-benar
  satu sumber, cuma beda filter cakupan** — `CalendarService::projectMonth()`
  vs `::personalMonth()`, keduanya memanggil method `merge()` privat yang
  sama; `::upcomingForUser()` (dipakai Kalender Personal DAN ringkasan
  Dashboard Eksekusi) sumbernya juga sama, cuma windownya "N ke depan dari
  hari ini" bukan satu bulan kalender. Grid bulanan (Senin-mulai, 7×N
  minggu, termasuk hari bulan sebelum/sesudah supaya tiap baris genap 7
  kolom) diekstrak ke trait `App\Livewire\Eksekusi\Concerns\BuildsCalendarWeeks`
  dipakai kedua komponen Livewire, dan markup grid-nya sendiri ke komponen
  Blade `<x-eksekusi.calendar-month-grid>` — dipakai identik oleh tab
  proyek dan halaman personal, cuma beda nama method Livewire yang dipanggil
  saat klik tanggal (`onDayClick` prop).
- **Kalender Personal (`App\Livewire\Eksekusi\Kalender`, route
  `eksekusi.kalender`) sengaja `role:execution_member` SAJA, BUKAN
  `execution_member,admin`** — mengikuti pembatasan `eksekusi.dashboard`
  yang sudah ada (bukan pola umum lain di modul Eksekusi yang biasanya
  admin+member). Alasan: "proyek yang diikuti user" adalah konsep
  `ProjectMember` yang tidak punya padanan bermakna untuk admin (admin
  punya akses semua proyek, bukan "proyek yang diikuti"). Slot nav
  "Kalender Personal" (`config/navigation.php`, `execution_member`) di-flip
  `enabled: true` — `tests/Feature/Shell/NavPopupTest.php`'s
  disabled-slot-example test dipindah subjeknya ke "Forum General" (slot
  `enabled: false` terakhir yang tersisa di situ), pola yang sama seperti
  saat 'Praktik' dulu di-swap ke 'Kalender Personal' waktu Praktik 2.
- **Keputusan TAMBAHAN yang tidak diminta eksplisit di prompt, tapi
  diperlukan supaya `project_id` nullable di skema benar-benar bisa
  dipakai**: halaman Kalender Personal dikasih form "+ Tambah Acara
  Personal" sendiri (SELALU `project_id = null`) — tanpa ini, tidak ada
  jalur UI mana pun yang pernah bisa membuat baris Acara personal
  (tab proyek cuma bisa membuat Acara TERIKAT proyek itu). Acara
  ber-`project_id` tetap HANYA bisa ditambah dari tab Kalender proyek
  terkait, bukan dari halaman personal.
- **RBAC event proyek reuse penuh middleware `project.member` dari Batch
  1a** — tidak ada pengecekan baru ditulis, tab Kalender proyek (baik
  lihat maupun tambah Acara) otomatis terlindungi sama seperti tab lain.
- **"Ringkasan Kalender" di Dashboard Eksekusi (placeholder sejak Fase 3
  Batch 1) sekarang data asli** — `Eksekusi\Dashboard::render()` memanggil
  `CalendarService::upcomingForUser()` yang sama dipakai halaman Kalender
  Personal, 5 item terdekat. `tests/Feature/Execution/MemberDashboardTest.php`
  diperbarui: test placeholder lama displit — "Antrian Review Praktik"
  TETAP diuji sebagai placeholder murni (belum dikerjakan batch ini),
  "Ringkasan Kalender" dapat cakupan test baru tersendiri di
  `CalendarTest.php` yang membuktikan ini BUKAN lagi placeholder.
- **Test baru `tests/Feature/Execution/CalendarTest.php` (14 test)**:
  gabungan Kegiatan+Acara di tab proyek, Acara ter-scope proyek yang benar
  (tidak bocor ke proyek lain), navigasi bulan (item di luar rentang bulan
  yang ditampilkan tidak muncul sampai pindah bulan), subtask TIDAK PERNAH
  muncul sebagai Kegiatan (mengonfirmasi ulang audit Batch 2a dari sisi
  Kalender), RBAC non-member ditolak dari tab Kalender proyek, Kalender
  Personal menggabungkan lintas-proyek dengan benar TAPI mengecualikan
  proyek yang bukan keanggotaan user dan Acara personal milik user lain,
  admin ditolak dari halaman Kalender Personal, dan Ringkasan Kalender
  Dashboard Eksekusi menampilkan data asli bukan placeholder. Full suite
  naik dari 549 ke **563/563 lolos** (1894 assertion), `npm run build`
  sukses.

## Known gaps / backlog (dari Fase 7 Batch 3a, 2026-07-13)

- **Gantt Chart + Task Dependency dibangun dari nol** (tabel baru
  `task_dependencies`, backup database dijalankan lebih dulu). `task_id`
  BERGANTUNG PADA `depends_on_task_id` (harus selesai duluan) — cuma task
  besar yang boleh (`parent_task_id IS NULL` di kedua sisi, konsisten hasil
  audit Fase 7 Batch 2a); subtask ditolak eksplisit sebagai salah satu
  sisi dependency manapun.
- **Pencegahan siklik: level aplikasi, bukan constraint DB** (sesuai
  rekomendasi prompt task ini) — `App\Services\Execution\TaskDependencyService::wouldCreateCycle()`
  menyusuri graf dependency yang SUDAH ADA lewat DFS/BFS iteratif sebelum
  baris baru diizinkan disimpan. Algoritma: menambah edge (task &rarr;
  dependsOn) akan menutup siklus PERSIS kalau `dependsOn` SUDAH (secara
  transitif) bergantung balik ke `task` — jadi ditelusuri maju dari
  `dependsOn` mengikuti rantai dependency yang sudah ada, kalau `task`
  ketemu di rantai itu, ditolak. Diuji eksplisit untuk siklus langsung
  2-node (A&rarr;B lalu coba B&rarr;A) MAUPUN siklus transitif 3-node
  (A&rarr;B&rarr;C lalu coba C&rarr;A) — keduanya WAJIB ditolak dengan
  pesan jelas, tidak ada baris tersimpan.
- **`activity_logs.action_type` SENGAJA TIDAK ditambah nilai baru untuk
  event "dependency ditambahkan"** — kolom itu enum DB tetap (18 nilai
  existing, tidak ada yang cocok), menambah nilai baru berarti mengubah
  skema tabel yang SUDAH ADA, butuh konfirmasi eksplisit ke Aye dulu
  (`CLAUDE.md`: "Jangan ubah struktur skema database yang sudah ada...
  tanpa konfirmasi eksplisit"). Dilewati secara sengaja dan didokumentasikan
  di docblock `TaskDependencyService`, bukan diam-diam terlupa — sama
  kehati-hatian dengan gap `notifications.context_type` yang sudah tercatat
  lebih dulu.
- **Pendekatan teknis Gantt: murni CSS/HTML + satu overlay `<svg>` inline,
  TIDAK ADA library chart.** Dicek dulu `package.json` — nol dependency
  chart/graphing apa pun di project ini — konsisten preferensi app ini
  menghindari library JS berat kalau primitif native cukup (persis alasan
  Kanban pakai native HTML5 drag-drop, Fase 3 Batch 6). Posisi & lebar
  batang dihitung server-side (persentase dari rentang tanggal proyek,
  `Gantt.php::render()`), garis koneksi dependency digambar sebagai
  `<line>` SVG dengan koordinat dari perhitungan BARIS/TANGGAL YANG SAMA
  PERSIS dengan batangnya (satu sumber angka, dikonversi ke skala unit
  viewBox 0-1000 supaya endpoint garis dan tepi batang tidak pernah
  "meleset" sedikit pun akibat pembulatan ganda).
- **"Tanggal mulai" per task memakai `created_at`, BUKAN kolom baru
  `start_date`.** Dicek dulu (sesuai instruksi prompt "cek field yang
  tersedia") — `tasks` cuma punya `deadline`, tidak ada kolom start
  sendiri (dan recon lama juga sudah mencatat `Milestone` tidak punya
  `start_date`, gap yang sama). Menambah kolom baru butuh migrasi skema
  tambahan yang tidak diminta eksplisit di batch ini, jadi dipakai
  timestamp yang SUDAH ADA di setiap task (`created_at`, tanggal task itu
  benar-benar dibuat) sebagai proksi titik mulai batang Gantt — di-clamp
  supaya tidak pernah menghasilkan lebar batang negatif kalau deadline
  sempat diedit admin jadi lebih awal dari tanggal task dibuat.
- **UI "Bergantung pada" di panel task (Batch 1b) — dropdown-nya SENGAJA
  cuma menawarkan task besar dari proyek yang sama**, tapi
  `TaskDependencyService::addDependency()` tetap re-validasi SEMUA aturan
  (proyek sama, bukan subtask di kedua sisi, bukan diri sendiri, bukan
  duplikat, bukan siklik) di level server terlepas dari apa yang
  ditawarkan dropdown — permintaan langsung yang di-craft ke action
  Livewire ini tidak bisa melewati aturan manapun cuma karena dropdown
  tidak pernah menawarkan opsi itu. **Tidak ada UI hapus dependency di
  batch ini** — mengikuti pola section lain di panel yang sama (Subtask,
  Komentar, Attachment semuanya cuma tambah, tidak ada hapus), bukan
  celah yang terlewat.
- **Test baru `tests/Feature/Execution/TaskDependencyTest.php` (18 test)**:
  CRUD dasar, dependency lintas-proyek ditolak, subtask ditolak di KEDUA
  sisi (dependent maupun depends-on) + tidak pernah muncul di dropdown,
  **anti-siklik 2-node dan 3-node (WAJIB, paling penting di file ini)**
  plus unit test langsung ke `wouldCreateCycle()` yang membuktikan
  penambahan AMAN vs TIDAK AMAN dibedakan dengan benar, render Gantt
  (urutan baris, lebar batang proporsional ke rentang tanggal, subtask
  tidak pernah tampil, garis koneksi cuma muncul kalau dependency memang
  ada), dan RBAC non-member ditolak dari tab Gantt (reuse penuh middleware
  `project.member` dari Batch 1a, tidak ada pengecekan baru ditulis).
  Full suite naik dari 563 ke **581/581 lolos** (1925 assertion),
  `npm run build` sukses.

## Known gaps / backlog (dari Fase 7 Batch 3b, 2026-07-13)

- **Roadmap dibangun murni dari data yang sudah ada — NOL migrasi, NOL
  tabel baru**, sesuai batasan task ini ("batch istirahat" di antara
  batch berat lainnya per rekomendasi recon). `Milestone::progressPercentage()`
  (sudah ada, sudah exclude subtask sejak Fase 7 Batch 2a) dipakai APA
  ADANYA, tidak dihitung ulang. CRUD Milestone (masih di `ProjectHeader`
  dari Batch 1a) tidak disentuh sama sekali.
- **Klasifikasi status visual (selesai/sedang berjalan/belum mulai) BARU
  di batch ini** — bukan kolom tersimpan, dihitung on-the-fly di
  `Roadmap::milestoneEntry()` tiap render: **selesai** kalau milestone
  punya &ge;1 task besar dan SEMUANYA `done`; **sedang berjalan** kalau
  ada task besar berstatus `in_progress` ATAU `in_review` (diperluas
  sedikit dari kalimat literal prompt "ada task in_progress" — `in_review`
  ikut dihitung karena secara konsep juga "sedang dikerjakan", bukan diam,
  cuma lagi ditinjau); **belum mulai** untuk sisanya (termasuk milestone
  yang belum punya task sama sekali — sengaja TIDAK dianggap "selesai"
  meski secara teknis 0/0, supaya milestone kosong tidak salah tampil
  seolah sudah tuntas). Query task difilter `whereNull('parent_task_id')`
  — audit Batch 2a yang sama, subtask tidak pernah mempengaruhi status
  ATAU persentase milestone induknya.
- **Orientasi HORIZONTAL dipilih (bukan vertikal ala Peta Kurikulum),
  keputusan sendiri sesuai instruksi ("putuskan sendiri kalau ada
  pertimbangan lain").** Dua alasan: (1) jumlah Milestone per proyek
  biasanya jauh lebih sedikit dari jumlah Modul Eksplorasi, jadi tidak
  butuh pola scroll panjang yang membuat vertikal masuk akal di Peta
  Kurikulum; (2) Roadmap secara konsep adalah garis WAKTU proyek —
  horizontal kiri-ke-kanan lebih cocok dengan kebiasaan membaca timeline
  dibanding "urutan langkah" vertikal ala kurikulum. Bahasa visual node+
  garis penghubung TETAP dicontek dari Peta Kurikulum untuk konsistensi
  lintas-portal (warna completed/active/locked -> ink/accent/muted,
  pola yang sama), cuma arahnya diputar. `overflow-x-auto` sebagai
  fallback kalau Milestone banyak di layar sempit.
- **Interaksi klik-untuk-detail murni Alpine, TIDAK ADA Livewire
  round-trip** — satu `x-data="{ openId: null }"` dibagi semua node
  (persis pola per-modul di Peta Kurikulum, cuma di sini state-nya
  dibagi lintas-node alih-alih per-node, supaya cuma satu detail
  terbuka dalam satu waktu). Semua data task per milestone SUDAH
  dikirim di render awal (tidak lazy-load saat diklik) karena jumlah
  Milestone dan task per proyek kecil — sengaja tidak dioptimasi lebih
  jauh, konsisten pola query app ini di skala tim kecil.
- **Test baru `tests/Feature/Execution/RoadmapTest.php` (10 test)**:
  urutan `sort_order` (dibuktikan lewat urutan MUNCUL di HTML, bukan cuma
  urutan Collection), keempat kombinasi status (completed/active via
  in_progress/active via in_review/not_started termasuk 0-task), subtask
  tidak pernah mempengaruhi status/persentase, persentase dibuktikan
  IDENTIK dengan hasil `$milestone->progressPercentage()` langsung (bukti
  reuse, bukan hitung ulang terpisah), detail task muncul di markup
  (Alpine `x-show` cuma toggle CSS, konten server-side sudah ada), dan
  RBAC non-member ditolak (reuse `project.member`, tidak ada pengecekan
  baru). Full suite naik dari 581 ke **591/591 lolos** (1936 assertion),
  `npm run build` sukses.

## Known gaps / backlog (dari Fase 7 Batch 4, TERAKHIR di Fase 7, 2026-07-13)

- **Migrasi ke `forum_threads` (tabel LAMA, sudah dipakai Forum Eksplorasi
  sejak v1.0) — dikonfirmasi eksplisit ke Aye dulu via `AskUserQuestion`
  sebelum dieksekusi**, sesuai instruksi wajib task ini (kelas risiko beda
  dari batch lain yang murni `CREATE TABLE` baru). Backup database
  dijalankan lebih dulu. Dua perubahan: (1) `project_id` nullable, FK ke
  `projects`, `nullOnDelete()` — null berarti thread Eksplorasi (SEMUA
  baris lama otomatis dapat makna ini, tidak ada migrasi data manual); (2)
  `target` (enum peer/pic) diperlebar jadi nullable — dikonfirmasi terpisah
  ke Aye (bukan diasumsikan sendiri) karena murni konsep Eksplorasi, tidak
  relevan untuk thread Eksekusi (dipaksa isi nilai dummy dianggap lebih
  menyesatkan daripada NULL yang jujur "tidak relevan").
- **Forum Proyek (project_id terisi) DIBANGUN di batch ini. Forum General
  (project_id null TAPI konteks Eksekusi, bukan Eksplorasi) TIDAK dibangun
  — di luar scope literal prompt task ini** (Section 2 cuma minta "Tab
  Forum Proyek"). Hubungan keduanya dikonfirmasi LANGSUNG dari
  `docs/v_2.0/Rancangan_Modul_Manajemen_Proyek_v2.md` §5 ("Forum, dua
  kategori"), bukan diasumsikan: **konsep BERBEDA, tabel SAMA** —
  Forum Proyek = `project_id` terisi (thread khusus 1 proyek), Forum
  General = `project_id` null tapi tetap `execution_member`/`admin`-only
  (bukan thread Eksplorasi). **Ditemukan gap desain yang perlu diselesaikan
  SEBELUM Forum General benar-benar dibangun**: kalau Forum General dibuat
  apa adanya (`module_id`/`unit_id`/`project_id` semuanya null), baris itu
  TIDAK BISA dibedakan dari thread "General" Eksplorasi (yang juga
  `module_id`/`unit_id` null sejak Fase 3) HANYA dari kolom FK kosong
  semua — perlu kolom pembeda portal eksplisit (atau linked context lain)
  sebelum Forum General diimplementasikan, dicatat di sini supaya tidak
  terlupa. Slot nav "Forum General" (`config/navigation.php`, execution_member)
  TETAP `enabled: false` di batch ini. **RESOLVED 2026-07-13** (task
  terpisah "Forum General Eksekusi", setelah Fase 8 tuntas) — kolom
  `forum_threads.portal` ditambahkan, gap ditutup. Lihat section "Known
  gaps / backlog (Forum General Eksekusi)" di bawah.
- **Logic backend digeneralisasi ke `App\Services\Forum\ForumService`
  (namespace BARU, bukan di bawah `Execution\`/`Exploration\` — kasus
  pertama logic yang genuinely dipakai KEDUA portal), dipakai LANGSUNG
  oleh KEDUA sisi** — `Eksplorasi\Forum\Create::save()` dan
  `Eksplorasi\Forum\Show::reply()` DIREFAKTOR untuk memanggil
  `ForumService::createThread()`/`addReply()` (bukan `ForumThread::create()`/
  `ForumReply::create()` langsung lagi), Eksekusi\Projects\Tabs\Forum
  (baru) memanggil service yang SAMA — sesuai instruksi eksplisit "JANGAN
  duplikasi logic Forum jadi dua sistem terpisah". Pengiriman notifikasi
  TETAP terpisah per portal (`Exploration\Notifier` vs `Execution\Notifier`,
  recipient/wording beda) — service ini sengaja tetap tipis, cuma menulis
  baris DB, bukan orkestrasi penuh.
- **`Execution\Notifier::send()` diperluas menerima `ForumThread` sebagai
  context** (union type `Project|Task|ForumThread|null`, match arm baru
  `'forum_thread'`) — morph map `forum_thread` sudah terdaftar sejak 2.0,
  tinggal dipakai dari sisi Eksekusi juga. `Notification::linkUrl()` untuk
  `context_type = 'forum_thread'` digeneralisasi: `project_id` terisi ->
  arah ke tab Forum proyek itu dengan `?thread=` (pola query-param
  auto-open yang sama dengan `Board`'s `?task=`, Fase 7 Batch 1b);
  `project_id` null -> URL Eksplorasi lama PERSIS tidak berubah (dibuktikan
  tidak beregresi lewat `NotificationScopingAndSafetyTest.php` yang
  dijalankan ulang tanpa modifikasi).
- **BUG ISOLASI ditemukan dan diperbaiki SEBELUM sempat jadi regresi
  nyata (ketahuan lewat test baru sendiri, bukan laporan user)**:
  `Eksplorasi\Forum\Index::render()` sebelumnya query `ForumThread::with(...)->latest()->get()`
  TANPA filter apa pun — begitu `project_id` ditambahkan, thread Forum
  Proyek langsung IKUT MUNCUL di daftar Forum Eksplorasi DAN bikin
  halamannya CRASH (`$targetIcon($thread->target)` mengharapkan string,
  dapat `null` dari thread Eksekusi). Ditambal dengan `whereNull('project_id')`
  eksplisit di query itu. **Ditambal juga (defense-in-depth, ditemukan
  sekalian saat audit)**: `Eksplorasi\Forum\Show::mount()` sebelumnya bisa
  diakses untuk THREAD ID APAPUN termasuk thread Forum Proyek kalau
  ID-nya ditebak/bocor (tidak crash, tapi salah tampil dan melanggar
  batasan "exploration_member dikecualikan sepenuhnya" dari forum
  Eksekusi) — ditambal `abort_if($thread->project_id !== null, 404)`.
- **Interaksi Forum Proyek (list, buat thread, buka detail+balas) SEMUA
  inline di dalam tab** (state Livewire `$openThreadId`, pola sama persis
  `Board::$openTaskId` dari Batch 1b) — BUKAN halaman terpisah ala
  `Eksplorasi\Forum\Show`, konsisten pola tab-mandiri yang sudah dipakai
  tab lain di Fase 7 (slide-over Kanban, expand inline Roadmap). Satu
  pengecualian: `?thread=` query param tetap dibaca di `mount()` (pola
  sama `Board`'s `?task=`) supaya notifikasi tetap bisa deep-link ke
  thread yang benar.
- **Verifikasi non-regresi Forum Eksplorasi WAJIB, dijalankan DUA KALI
  (sebelum dan sesudah bug-fix isolasi di atas), keduanya 100% lolos TANPA
  modifikasi assertion satu pun**: `NotificationTriggersTest.php`,
  `ResourcesAndForumTest.php`, `RouteAccessMatrixTest.php`,
  `NavPopupTest.php`, `NotificationScopingAndSafetyTest.php` — 32 test,
  242 assertion, nol file test itu disentuh Edit tool sepanjang batch ini.
- **Test baru `tests/Feature/Execution/ProjectForumTest.php` (13 test)**:
  CRUD dasar (target selalu null, module/unit selalu null untuk thread
  Eksekusi), notifikasi balas terkirim ke pembuat thread lewat
  `Execution\Notifier` dengan `linkUrl()` yang benar, balas ke thread
  sendiri tidak menotifikasi diri sendiri, isolasi lintas-proyek (thread
  proyek lain tidak muncul, balas thread proyek lain ditolak
  `ModelNotFoundException`), isolasi DUA ARAH dari Forum Eksplorasi
  (thread proyek tidak muncul di Forum Eksplorasi DAN sebaliknya, plus
  404 eksplisit saat thread proyek diakses lewat route Eksplorasi), dan
  RBAC (non-member ditolak, exploration_member ditolak, member proyek
  bisa akses penuh). Full suite naik dari 591 ke **603/603 lolos**
  (1961 assertion), `npm run build` sukses.
- **FASE 7 TUNTAS setelah batch ini** — seluruh 6 tab halaman proyek
  (Kanban, Roadmap, Gantt, Kalender, Forum Proyek, Anggota) sekarang
  berisi data/fungsi asli, nol placeholder tersisa.

## Progress Fase 7
- [x] Batch 1a — Tab Kanban + konsolidasi RBAC (`project.member` middleware)
- [x] Batch 1b — Detail Task jadi slide-over panel
- [x] Batch 2a — Subtask (`parent_task_id`) + audit 18 titik query lama
- [x] Batch 2b — Kalender (Kegiatan derived + Acara manual, proyek & personal)
- [x] Batch 3a — Gantt Chart + Task Dependency (anti-siklik level aplikasi)
- [x] Batch 3b — Roadmap (timeline Milestone horizontal)
- [x] Batch 4 — Forum Proyek (`forum_threads.project_id`, generalisasi ForumService)
      — Forum General (project_id null, konteks Eksekusi) TIDAK dibangun,
      di luar scope; gap desain pembeda-portal dicatat di atas untuk
      dituntaskan sebelum Forum General diimplementasikan.

## Progress Fase 8 (Mode Ganda — rencana 7 batch kecil per `docs/v_2.0/archive/RECON_fase8_mode_ganda.md`, diarsipkan 2026-07-13 setelah Fase 8 tuntas)
- [x] Batch 1 — Skema (`users.dual_mode_status`/`active_mode`, tabel
      `dual_mode_requests`, model dasar) — NOL logic Gate/akses
- [x] Batch 2 — Gate terpusat + swap middleware Eksekusi (arah Eksplorasi ->
      akses Eksekusi setelah disetujui)
- [x] Batch 3 — Akses baca Eksplorasi (arah Eksekusi -> Eksplorasi, bebas)
- [x] Batch 4 — Alur pengajuan member lengkap (`DualModeService::submitRequest()`
      + notifikasi broadcast admin + status/alasan tolak terlihat member);
      versi minimal dari Batch 2 dirombak jadi service penuh
      (`approve()`/`reject()`/`revoke()` juga sudah siap dipakai Batch 5)
- [x] Batch 5 — Panel admin approve/reject + cabut akses (nav+dashboard
      slot terisi juga)
- [x] Batch 6 — UI switching + badge + slot Profil jadi aktif
- [x] Batch 7 — Sweep regresi penuh + edge case (revoke di tengah sesi, dst.)
      — **FASE 8 TUNTAS.**

## Known gaps / backlog (dari Fase 8 Batch 1, 2026-07-13)

- **Fondasi skema Mode Ganda dibangun — MURNI skema+model, NOL logic
  Gate/akses.** Backup database dulu (menyentuh tabel `users` yang paling
  sensitif di app ini). Dua migrasi: (1) `users.dual_mode_status` (enum
  `none`/`pending`/`approved`/`revoked`, default `none`) dan
  `users.active_mode` (enum `exploration`/`execution`, nullable, default
  null); (2) tabel baru `dual_mode_requests` (`user_id`, `status`
  pending/approved/rejected, `reviewed_by`/`reviewed_at`/`note`) — state
  machine dicontek dari `ProjectIdea` (single-step, admin langsung
  putuskan, tanpa delegasi — sesuai temuan `RECON_fase8_mode_ganda.md`
  poin 5, bukan pola Praktik yang punya assign-reviewer terpisah).
- **Keputusan desain `active_mode` — nullable+default-null DIPILIH, bukan
  kolom yang selalu terisi.** Alasan: (1) mayoritas user (single-mode,
  tanpa akses ganda) tidak PERNAH punya "mode aktif" yang bermakna beda
  dari `role` asalnya — memaksa kolom selalu terisi berarti setiap user
  baru butuh nilai default yang harus SELALU disinkronkan manual dengan
  `role` saat pembuatan akun (titik gagal tambahan, dua sumber kebenaran
  untuk hal yang sama); (2) `null` = "ikuti `role` asal" adalah representasi
  yang jujur secara semantik untuk 99% user (mereka memang tidak pernah
  switch), dan cuma user yang BENAR-BENAR sedang mode selain asalnya yang
  butuh baris ini terisi eksplisit — pola yang sama dengan `Task.parent_task_id`
  (Fase 7 Batch 2a: null = kasus umum/default, terisi = pengecualian
  spesifik) dan `ForumThread.project_id` (Fase 7 Batch 4: null = kasus
  lama/default). Konsisten dengan konvensi nullable-untuk-default yang
  sudah dipakai berulang kali di app ini.
- **`DualModeRequest` = audit trail SETIAP pengajuan (bisa lebih dari
  satu baris per user seiring waktu — ditolak lalu mengajukan ulang lalu
  disetujui lalu suatu saat dicabut), TERPISAH dari `users.dual_mode_status`
  yang cuma menyimpan status EFEKTIF TERKINI** — dipisah sengaja supaya
  Gate/pengecekan akses nanti (batch berikutnya) cukup baca SATU kolom
  terindeks di `users`, tidak perlu join/agregasi ke riwayat pengajuan
  tiap kali. Perhatikan asimetri enum yang disengaja: `dual_mode_requests.status`
  cuma `pending`/`approved`/`rejected` (hasil SATU pengajuan spesifik),
  sedangkan `users.dual_mode_status` py nilai tambahan `revoked` — "dicabut"
  bukan hasil sebuah pengajuan, itu TINDAKAN admin BELAKANGAN atas akses
  yang SUDAH disetujui, jadi cuma pernah muncul di `users.dual_mode_status`,
  tidak pernah jadi status baris `dual_mode_requests` itu sendiri.
- **`User::hasDualModeCapability()` (placeholder Fase 2, dipakai
  `<x-shell.mode-badge>` dan slot "Info Mode Ganda" di Profil) SENGAJA
  TIDAK disentuh — tetap hardcode `return false;`.** Getter baru
  (`hasApprovedDualModeAccess()`, `hasPendingDualModeRequest()`,
  `dualModeRequests()`) ditambahkan TERPISAH, belum dipanggil dari
  middleware/Gate/UI manapun — dibuktikan eksplisit lewat test
  `test_hasDualModeCapability_is_still_always_false_regardless_of_the_new_columns`
  (user dengan `dual_mode_status=approved` DAN `active_mode=execution`
  sekalipun, badge/slot tetap berperilaku identik seperti sebelum batch
  ini). Menyambungkan getter baru ke Gate sungguhan adalah kerja Batch 2+.
- **Verifikasi eksplisit nol middleware/RBAC tersentuh** (pola sama
  seperti bukti diff drag-drop Kanban di Fase 7): dicek lewat timestamp
  modifikasi file — `routes/web.php`/`bootstrap/app.php`/ketiga file
  `app/Http/Middleware/*.php` terakhir diubah SEBELUM batch ini dimulai
  (kemarin/pagi), sedangkan seluruh file batch ini (migrasi, model, test)
  bertimestamp dalam rentang beberapa menit yang sama, jauh setelahnya —
  bukti konkret bukan cuma klaim.
- **Test baru `tests/Feature/DualMode/DualModeSchemaTest.php` (10 test,
  direktori test baru)**: default kolom pada user baru, kolom bisa
  diubah ke seluruh nilai enum yang valid, `DualModeRequest` CRUD dasar +
  default `status=pending`, relasi `user()`/`reviewer()`/`User::dualModeRequests()`
  resolve dengan benar, kedua getter baru merefleksikan kolom dengan
  benar, DAN pembuktian non-regresi `hasDualModeCapability()` di atas.
  Full suite naik dari 603 ke **613/613 lolos** (1986 assertion),
  `npm run build` sukses (hash CSS/JS identik — batch ini tidak
  menyentuh Blade/asset apa pun).

## Known gaps / backlog (dari Fase 8 Batch 2, 2026-07-13)

- **Gate terpusat: `User::canAccessExecution()`, SATU-SATUNYA sumber
  kebenaran** (docs/v_2.0/RANCANGAN_FINAL_WEBI-SPACE_v2.md §2.2.D,
  RECON_fase8_mode_ganda.md — dijadikan dasar langsung, bukan mulai dari
  nol). `role === 'execution_member'` (origin asli) ATAU
  (`role === 'exploration_member' && dual_mode_status === 'approved' &&
  active_mode === 'execution'`) — SENGAJA TIDAK termasuk `role === 'admin'`
  (§2.2.C: "Admin tidak ikut sistem mode ganda ini"), admin dicek terpisah
  di titik pemanggilan, persis seperti `role:execution_member,admin` dulu
  memuat dua kondisi terpisah, bukan digabung jadi satu method.
- **Middleware baru `App\Http\Middleware\EnsureCanAccessMode` (alias
  `mode`), diparameterisasi PERSIS pola `EnsureUserHasRole` (`role:`) yang
  sudah ada** — `mode:execution` atau `mode:execution,admin`, dipilih
  dibanding 2 middleware class terpisah supaya konsisten konvensi yang
  sudah ada di app ini, bukan pola baru. `role:` middleware LAMA
  **tidak disentuh sama sekali**, tetap dipakai apa adanya untuk semua
  route non-Eksekusi (admin panel, Eksplorasi).
- **8 deklarasi middleware diganti persis** (`routes/web.php`, SEMUA yang
  dipetakan recon poin 4, nol lebih nol kurang):
  | Route/grup | Sebelum | Sesudah |
  |---|---|---|
  | `/eksekusi/dashboard` | `role:execution_member` | `mode:execution` |
  | `/eksekusi/avatar` | `role:execution_member` | `mode:execution` |
  | `/eksekusi/kalender` | `role:execution_member` | `mode:execution` |
  | `/eksekusi/ideas/*` | `role:execution_member,admin` | `mode:execution,admin` |
  | `/eksekusi/projects/*` (+ tab proyek nested) | `role:execution_member,admin` | `mode:execution,admin` |
  | `/eksekusi/tasks/{task}` (redirect) | `role:execution_member,admin` | `mode:execution,admin` |
  | `/eksekusi/praktik/submissions/{submission}` | `role:execution_member,admin` | `mode:execution,admin` |
  | `/attachments/{attachment}/download` | `role:execution_member,admin` | `mode:execution,admin` |

  `project.member` (`EnsureProjectMembership`) TIDAK disentuh — orthogonal,
  otomatis tetap benar begitu admin (Batch 5+) benar-benar menambahkan
  anggota yang disetujui ke `ProjectMember` seperti anggota asli.
- **Hasil audit guard level-aksi (recon poin 1c, 20 titik) — KESIMPULAN:
  NOL titik butuh guard tambahan untuk kekhawatiran "boleh akses Eksekusi
  atau tidak".** Semua titik manual yang benar-benar access-control
  (bukan business-rule/display-conditional) membandingkan `role === 'admin'`
  atau mengecek baris `ProjectMember`/`assigned_reviewer_id` SUNGGUHAN —
  TIDAK PERNAH membandingkan `role === 'execution_member'` secara literal.
  Karena grant Mode Ganda TIDAK PERNAH mengubah `role` (tetap
  `exploration_member` selamanya, §2.2.C) dan TIDAK PERNAH memberi admin,
  seluruh 8 titik admin-only (`Tasks\Show` 4 aksi, `ProjectHeader` 2 aksi,
  `Anggota` 2 aksi, `Projects\Create`, `Ideas\Approve`, `Ideas\Index::reject()`)
  dan titik assignment-specific (`Praktik\Review`, `AttachmentDownloadController`,
  `ChallengeSubmissionDownloadController`) otomatis tetap benar TANPA
  modifikasi apa pun — dibuktikan eksplisit lewat test (member yang
  disetujui+aktif tetap 403 di `ideas.approve`/`projects.create`, cuma bisa
  akses `praktik.submissions.show`/`attachments.download` setelah BENAR-BENAR
  di-assign/jadi anggota proyek, persis seperti anggota Eksekusi asli).
- **Ditemukan 2 gap TERPISAH (feature-completeness, BUKAN celah keamanan)
  saat audit — sengaja TIDAK diperbaiki di batch ini, didokumentasikan
  supaya tidak terlupa:**
  1. `Admin\Curriculum\Submissions\Index::assignReviewer()` membatasi
     kandidat reviewer dengan `User::where('role', 'execution_member')` —
     anggota yang disetujui+aktif Mode Ganda (role tetap `exploration_member`)
     TIDAK PERNAH muncul di dropdown penugasan reviewer. Bukan celah
     keamanan (cuma under-include, tidak ada yang bocor), tapi anggota
     tersebut secara praktik tidak akan pernah ditugaskan sebagai reviewer
     Praktik sampai query ini diperluas.
  2. `Profile\Edit.php:132` (`projectContributions`) menampilkan section
     kontribusi proyek berdasar `$user->role === 'execution_member'` literal
     — anggota Mode Ganda yang disetujui+aktif TIDAK akan melihat section
     ini di profil MEREKA SENDIRI meski sudah punya proyek/task Eksekusi
     sungguhan. Kandidat perbaikan wajar: kemungkinan besar dituntaskan di
     Batch 6 (UI switching) bersamaan dengan polish tampilan Profil
     lainnya, bukan batch keamanan ini.
- **Alur pengajuan minimal (`App\Services\DualMode\DualModeService::requestAccess()`)
  dibangun HANYA untuk keperluan test end-to-end batch ini** — dipanggil
  dari tombol baru di slot "Info Mode Ganda" Profil (Fase 3 Batch 7b,
  sekarang fungsional untuk `exploration_member`: tombol "Ajukan Akses
  Eksekusi" kalau `dual_mode_status='none'`, badge status kalau
  pending/approved/revoked). Slot untuk `execution_member` TETAP "Segera
  Hadir" (arah baca-Eksplorasi itu Batch 3, belum dibangun). **TIDAK ADA
  UI approval admin** (Batch 5) — approve/set `active_mode` untuk testing
  batch ini dilakukan LANGSUNG lewat tinker, sesuai instruksi eksplisit
  prompt task ini.
- **Test baru `tests/Feature/DualMode/ExecutionAccessGateTest.php` (12
  test, 115 assertion) — SEMUA 18 URL konkret di balik 8 middleware yang
  diganti dites, bukan sampel.** 3 skenario penuh (tanpa grant sama
  sekali, disetujui-tapi-`active_mode` masih null, disetujui-tapi-masih-mode-eksplorasi)
  masing-masing di-loop ke SELURUH 18 URL dan wajib 403 di semuanya;
  skenario disetujui+aktif di-loop ke 14 URL (14 dari 18, mengecualikan 4
  yang punya aturan orthogonal terpisah) dan wajib TIDAK 403, PLUS 4 test
  khusus membuktikan 4 URL yang dikecualikan tadi tetap berperilaku benar
  (2 tetap 403 karena admin-only, 2 baru sukses setelah benar-benar
  di-assign/jadi anggota proyek). Regresi eksplisit: native execution_member
  dan admin dites ulang di seluruh URL yang relevan, tetap tidak berubah.
  Full suite naik dari 613 ke **625/625 lolos** (2101 assertion),
  `npm run build` sukses.

## Known gaps / backlog (dari Fase 8 Batch 3, 2026-07-13)

- **Arah sebaliknya dari Batch 2: `User::canAccessExploration()`
  (`role==='exploration_member' || role==='execution_member'`, TANPA
  syarat approval/active_mode — persis §2.2.A "BEBAS tanpa persetujuan")
  + `User::isReadOnlyExploration()` (`role==='execution_member'`, dipakai
  di SETIAP guard level-aksi).** Middleware `EnsureCanAccessMode` dari
  Batch 2 diperluas (bukan bikin class baru) dengan match-arm `'exploration'`,
  konsisten pola yang sama. **Keputusan desain: akses baca TIDAK bergantung
  `active_mode`** (beda dari `canAccessExecution()` yang mensyaratkan
  `active_mode==='execution'`) — dicek ulang ke kalimat literal prompt
  batch ini ("TANPA syarat approval apa pun", kondisi boolean yang
  diberikan tidak menyebut `active_mode` sama sekali) dan ke §2.2.A sendiri
  ("beralih ke Mode Eksplorasi... TANPA perlu pengajuan/persetujuan") —
  ditafsirkan `active_mode` cuma relevan untuk badge/switching UI (Batch 6),
  BUKAN gate akses baca itu sendiri. Flag eksplisit kalau maksud Aye beda.
- **5 route dibuka** (`role:exploration_member` → `mode:exploration`):
  Peta Kurikulum, Unit-show, Checkpoint-show, Referensi index, WEBI.
  Forum index+show dibuka terpisah (`mode:exploration,admin`, admin tetap
  TIDAK termasuk — sama seperti sebelumnya). **TIDAK dibuka** (sengaja,
  sesuai literal prompt yang tidak menyebutnya + rekomendasi recon
  "default tertutup"): Dashboard Eksplorasi, Praktik (index+show), Forum
  Create (murni-tulis, tidak ada nilai baca dari route itu sendiri —
  TETAP reachable via modal nested di Forum Index, makanya `save()`-nya
  sendiri tetap di-guard, lihat di bawah).
- **BUG ditemukan saat implementasi (bukan recon): urutan registrasi
  route `/eksplorasi/forum/{thread}` (wildcard) vs `/eksplorasi/forum/create`
  (literal) — Laravel mencocokkan route sesuai urutan DIDAFTARKAN, wildcard
  yang didaftar lebih dulu menangkap "create" sebagai `{thread}` id
  (404, bukan UUID valid). Ditambal dengan mendaftarkan grup `/create`
  SEBELUM grup `mode:exploration,admin` yang berisi `/{thread}` — ketahuan
  lewat test yang gagal, bukan lolos kebetulan.
- **GUARD LEVEL-AKSI — SEMUA titik tulis yang sekarang reachable dari
  route baca, satu-satu (poin paling kritis batch ini):**
  1. `UnitEvaluation::submitQuiz()` — `abort_if(isReadOnlyExploration(), 403)`
  2. `UnitEvaluation::submitFreeText()` — sama
  3. `UnitEvaluation::markAsRead()` — sama
  4. `CheckpointShow::submit()` — sama
  5. `Resources\Index::submit()` — sama
  6. `Forum\Create::save()` — sama, **guard ini WAJIB tetap ada meski
     route `/eksplorasi/forum/create` sendiri TIDAK dibuka** — komponen
     ini di-embed sebagai modal DI DALAM `Forum\Index` (`<livewire:eksplorasi.forum.create />`,
     lihat blade Index) yang JUSTRU dibuka — tanpa guard ini, read-only
     user bisa bikin thread lewat modal walau tidak pernah bisa buka
     `/create` langsung. Ini persis "trap" yang diperingatkan recon,
     dibuktikan eksplisit lewat test terpisah.
  7. `Forum\Show::reply()` — `abort_if(isReadOnlyExploration(), 403)`

  **Method non-DB-write yang DIPERIKSA tapi SENGAJA TIDAK diguard**
  (`UnitEvaluation::moveOrderItem()`, `::retry()`, `Resources\Index::openSubmitForm()`/`closeSubmitForm()`)
  — semuanya cuma memanipulasi state Livewire in-memory (reorder array,
  reset mode tampilan, toggle boolean form), TIDAK PERNAH menyentuh
  database, jadi tidak ada apa pun untuk "diamankan" dari sisi data —
  disembunyikan lewat UI (tombol/form pemicu tidak dirender untuk
  read-only user) supaya tidak ada jalan buntu UX, bukan lewat abort_if
  yang tidak perlu. `Webi\Chat::sendMessage()`/`newConversation()`
  SENGAJA TIDAK diguard sama sekali — pengecualian eksplisit §2.2.A,
  dibuktikan tetap berfungsi lewat test.
- **BUG RECON DITUTUP, dibuktikan langsung (bukan diklaim)**: `ensureProgress()`
  yang dulu dipanggil tanpa syarat di `PetaKurikulum`/`UnitShow` sekarang
  DILEWATI untuk read-only user, diganti `ProgressService::blankProgress()`
  (baru — instance `UserExplorationProgress` TIDAK PERNAH di-`save()`,
  cuma bentuk objek default supaya blade tetap punya properti yang sama
  untuk dibaca). **Bug KEDUA yang ditemukan sendiri saat implementasi,
  DI LUAR yang sudah diflag recon**: `UnitShow::mount()` juga memanggil
  `ProgressService::recordUnitOpened()` tanpa syarat — method ini menulis
  `UserUnitProgress` (DAN memanggil `ensureProgress()` sendiri di
  dalamnya) HANYA DARI MEMBUKA HALAMAN UNIT, sebelum sempat submit apa
  pun. Kalau tidak ditambal, sekadar MELIHAT satu Unit sudah cukup bikin
  baris progress untuk read-only user. Ditambal dengan skip total
  (`recordUnitOpened()` tidak pernah dipanggil, `$this->locked` dipaksa
  `false`) untuk `isReadOnlyExploration()`.
- **Sistem lock/unlock DILEWATI TOTAL untuk mode baca** (§2.2.A) — bukan
  cuma dilewati SEBAGIAN (biarkan logic asli jalan dengan progress
  kosong) karena itu justru akan membuat HAMPIR SEMUA unit tampak
  TERKUNCI (progress kosong = prasyarat modul manapun belum terpenuhi) —
  kebalikan dari yang diinginkan. `PetaKurikulum`/`UnitShow` fork
  eksplisit: `status`/`locked` dipaksa `'active'`/`false` untuk
  read-only user, TIDAK PERNAH memanggil `moduleStatus()`/`unitLocked()`
  sama sekali untuk mereka. Dibuktikan test dengan skenario 2-modul
  (Modul 2 yang NORMALNYA terkunci untuk anggota Eksplorasi baru,
  terbukti terbuka untuk read-only user, plus sanity-check eksplisit
  membuktikan fixture-nya memang benar-benar akan terkunci kalau bukan
  read-only — bukan kebetulan selalu terbuka).
- **Banner keterangan** (`<x-eksplorasi.read-only-banner>`, baru) dipasang
  di 2 halaman yang eksplisit disebut prompt (Peta Kurikulum, Unit-show)
  — teks persis kutipan dokumen. Halaman lain yang dibuka (Referensi,
  Forum, Checkpoint) TIDAK dapat banner (di luar literal "halaman-halaman
  terkait" yang disebutkan), tapi tetap dapat notice kecil pengganti
  form/tombol yang disembunyikan di titik masing-masing.
- **6 test lama diperbarui (BUKAN dihapus)** karena asersinya memang jadi
  usang akibat perubahan yang DIMINTA batch ini (pola sama seperti
  update test placeholder Fase 7): `ChatTest::test_execution_member_and_admin_cannot_access_webi_chat`
  (execution_member dulu 403 di WEBI, sekarang sengaja 200 — admin tetap
  403, tidak berubah), `ResourcesAndForumTest`'s forum-index test (dulu
  403, sekarang 200), dan `RouteAccessMatrixTest`'s 2 test besar dipecah
  jadi 3 (dashboard tetap tertutup vs 5 route lain yang dibuka, forum
  index+show dibuka vs forum/create tetap tertutup).
- **Test baru `tests/Feature/DualMode/ReadOnlyExplorationTest.php` (16
  test)**: akses baca ke SEMUA 6 route yang dibuka, banner muncul/tidak
  muncul sesuai role, lock-bypass dengan sanity-check 2 arah, **test
  paling penting**: nol `UserExplorationProgress`/`UserUnitProgress`
  baru setelah read-only user membuka Peta Kurikulum + 3 Unit berbeda +
  Checkpoint (dengan regresi eksplisit membuktikan exploration_member asli
  TETAP dapat tracking normal), ketujuh titik guard level-aksi ditolak
  satu-satu (termasuk pembuktian jalur modal Forum\Create yang jadi
  perhatian khusus recon), 3 route yang sengaja tidak dibuka tetap
  tertutup, dan WEBI tetap terima pesan (satu-satunya pengecualian tulis).
  Full suite naik dari 625 ke **643/643 lolos** (2155 assertion),
  `npm run build` sukses.

## Known gaps / backlog (dari Fase 8 Batch 4, 2026-07-13)

- **`App\Services\DualMode\DualModeService` lengkap: `submitRequest()`,
  `approve()`, `reject()`, `revoke()`** — SEMUA logic perubahan status
  terpusat di sini (pola sama `ProjectIdeaService`/`TaskService`), nol
  logic status tersebar di Livewire component. `requestAccess()` dari
  Batch 2 di-rename `submitRequest()` (sesuai penamaan literal prompt
  batch ini) — satu pemanggil (`Profile\Edit`) + satu test lama
  disesuaikan, bukan breaking change publik.
- **Keputusan `reject()`: `users.dual_mode_status` kembali ke `none`,
  BUKAN status permanen "rejected".** Dicek dulu: enum kolom itu sendiri
  (dikunci sejak Fase 8 Batch 1) cuma `none`/`pending`/`approved`/`revoked`
  — **TIDAK ADA nilai `rejected` sama sekali** di enum-nya, jadi `none`
  bukan sekadar pilihan desain, itu satu-satunya representasi yang valid
  secara skema. Ini otomatis juga berarti member BISA mengajukan ulang
  setelah ditolak (bukan terkunci permanen) — penolakannya sendiri TIDAK
  hilang, tetap permanen sebagai riwayat di baris `DualModeRequest`
  spesifik itu (`status='rejected'` + `note`), cuma status EFEKTIF
  terkini user yang reset. Profil member baca baris `DualModeRequest`
  TERBARU (bukan cuma `dual_mode_status`) supaya bisa menampilkan
  "Ditolak" + alasan sekalipun `dual_mode_status` sudah balik ke `none`.
- **`approve()` SENGAJA TIDAK menyentuh `active_mode`** (persis instruksi
  eksplisit prompt) — member tetap harus switch manual (Batch 6). `revoke()`
  reset `active_mode` ke `null` ("mode kembali ke Eksplorasi", kutipan
  langsung §2.2.B) meski `canAccessExecution()` sudah otomatis berhenti
  bekerja begitu `dual_mode_status` bukan `approved` lagi (jadi resetnya
  soal kebersihan data, bukan celah keamanan kalau terlewat).
- **BUG NYATA ditemukan & diperbaiki saat menulis test (bukan cuma di
  test, di SERVICE-nya sendiri)**: `DualModeRequest::create(['user_id' => ...])`
  tanpa eksplisit set `status` membuat instance in-memory yang
  dikembalikan `submitRequest()` punya `status` bernilai `null` (Eloquent
  tidak tahu default DB `'pending'` tanpa fetch ulang) — kalau caller
  LANGSUNG memakai return value itu ke `approve()`/`reject()`,
  `guardUnreviewed()` salah membaca `null !== 'pending'` sebagai "sudah
  pernah diputuskan" dan menolak permintaan yang justru masih baru.
  Ditambal dengan set `'status' => 'pending'` eksplisit di `create()`,
  bukan mengandalkan default kolom.
- **Notifikasi: `Notification::create()` dipanggil LANGSUNG di
  `DualModeService`, BUKAN lewat `Exploration\Notifier`/`Execution\Notifier`**
  — dua Notifier portal yang sudah ada py union type context yang tidak
  cocok untuk `DualModeRequest` (dan menambah morph map baru untuk
  halaman detail yang belum ada — panel admin baru Batch 5 — dianggap
  spekulatif). `context_type` tetap `'none'`. 4 `type` baru ditambah lewat
  migrasi ADDITIF ke enum `notifications.type` (pola persis 2 migrasi
  Praktik sebelumnya, tidak minta konfirmasi terpisah karena preseden
  sudah mapan): `dual_mode_request_alert` (broadcast ke SEMUA admin, pola
  sama `AlertService::notifyNewAlerts()`/submission Praktik —
  di-loop, bukan satu recipient), `dual_mode_approved`,
  `dual_mode_rejected` (menyertakan `note` di pesan kalau diisi),
  `dual_mode_revoked` (ekstra, tidak diminta eksplisit di poin 3 prompt
  tapi ditambahkan untuk konsistensi — setiap aksi admin lain yang
  mengubah status di sini mengirim notifikasi, revoke juga signifikan
  buat member, jadi diberi perlakuan sama).
- **TEMUAN sampingan (dicatat, tidak diperbaiki — di luar scope batch
  ini)**: `config/navigation.php['execution_member']` TIDAK PUNYA entri
  apa pun ke rute Eksplorasi manapun — akses baca-saja yang dibangun Fase
  8 Batch 3 cuma bisa dicapai lewat URL langsung, tidak ada jalur navigasi
  UI sama sekali. Teks slot Profil execution_member diperbaiki supaya
  jujur soal ini ("lewat URL langsung untuk sekarang", bukan mengklaim
  "menu Eksplorasi" yang sebenarnya tidak ada). Kandidat wajar buat
  ditutup di Batch 6 (UI switching) yang memang akan menyentuh navigasi
  untuk kedua arah sekalian.
- **Slot "Info Mode Ganda" di Profil sekarang tampilkan penjelasan
  konsekuensi eksplisit** (dua riwayat data terpisah, admin bisa cabut
  kapan saja) sebelum tombol "Ajukan Akses Eksekusi" — bahasa jujur
  sesuai prinsip WEBI-SPACE, bukan cuma tombol polos tanpa konteks.
- **Test baru `tests/Feature/DualMode/DualModeRequestFlowTest.php` (21
  test)**: `submitRequest()` (buat request+notifikasi ke SEMUA admin
  dibuktikan bukan cuma 1, cegah pengajuan ganda saat pending/approved,
  tolak non-exploration_member), `approve()` (status berubah TAPI
  `active_mode` TIDAK tersentuh, notifikasi member, cegah approve dobel
  di request yang sama), `reject()` (`dual_mode_status` balik `none`
  TAPI riwayat penolakan permanen di baris request, notifikasi dengan/tanpa
  `note`, PEMBUKTIAN eksplisit member bisa mengajukan ulang setelah
  ditolak), `revoke()` (reset status+`active_mode`, tolak revoke user yang
  belum `approved`), dan integrasi UI Profil penuh (tombol muncul/hilang
  sesuai status, error jelas saat dobel klik, alasan penolakan tampil).
  Full suite naik dari 643 ke **664/664 lolos** (2198 assertion),
  `npm run build` sukses.

## Known gaps / backlog (dari Fase 8 Batch 5, 2026-07-13)

- **`App\Livewire\Admin\DualMode\Index` (§5.3 "Antrian Permintaan Mode
  Eksekusi"), pola PERSIS `Admin\Curriculum\Submissions\Index`** — full-page
  Livewire, RBAC murni lewat middleware route `role:admin` (TANPA
  `abort_if` di `mount()`, konsisten SEMUA panel Admin\* lain yang sudah
  ada, dicek dulu sebelum menulis). Approve pakai `wire:confirm` native
  (pola sama tombol "Reset Password" `Admin\Users\Edit` yang sudah ada);
  Reject butuh form kecil (textarea `note`, opsional) makanya TIDAK bisa
  pakai `wire:confirm` polos — buka form dulu (langkah friksi pertama)
  lalu tombol submit di dalam form JUGA dikasih `wire:confirm` (langkah
  kedua) supaya reject tetap sesulit approve untuk ter-klik tidak sengaja.
- **`DualModeService` TIDAK diubah sama sekali dari Batch 4** — `revoke()`
  SUDAH mereset `active_mode` ke `null` TANPA SYARAT sejak ditulis di
  Batch 4 (baris `$user->update(['dual_mode_status' => 'revoked', 'active_mode' => null])`),
  jadi syarat "kalau user sedang aktif di mode Eksekusi, active_mode HARUS
  di-reset" SUDAH terpenuhi dari awal, berlaku untuk SEMUA kasus (baik
  `active_mode` sebelumnya `'execution'` maupun sudah `null`) — bukan
  perilaku baru batch ini, cuma sekarang ada UI sungguhan yang
  memanggilnya. Dibuktikan eksplisit lewat test (bukan diasumsikan dari
  baca kode Batch 4 saja) karena prompt secara eksplisit minta pembuktian
  ulang.
- **Section "Akses Mode Ganda" di `Admin\Users\Edit` cuma tampil untuk
  `dual_mode_status === 'approved'`** — begitu dicabut, section hilang total
  (bukan berubah jadi "sudah dicabut, aktifkan lagi?", karena Batch ini
  tidak diminta membangun ulang mekanisme re-grant, cuma cabut satu arah).
  **RESOLVED (perbaikan pasca-Batch 5, 2026-07-13): gap "member yang
  dicabut TIDAK otomatis bisa mengajukan ulang" yang tadinya dicatat di
  sini sudah ditutup** — lihat section "Known gaps / backlog (perbaikan
  pasca-Batch 5)" di bawah untuk detail lengkap.
- **Nav admin + Dashboard Admin**: entry "Permintaan Mode Ganda"
  ditambahkan ke `config/navigation.php['admin']` (`enabled: true`,
  langsung aktif, bukan slot disabled — beda dari kebanyakan slot v2.0
  lain yang biasanya didaftarkan dulu sebagai placeholder). Card baru di
  Dashboard Admin (§5.6) — jumlah pending + link — ditaruh sejajar card
  "Status Kurikulum" yang sudah ada, pola visual identik (border-muted,
  judul+angka font-mono, tombol link kanan).
- **Test baru `tests/Feature/DualMode/AdminDualModePanelTest.php` (11
  test)**: RBAC (cuma admin lolos, exploration_member DAN execution_member
  ditolak), antrian cuma tampilkan `pending` (bukan `approved`/`rejected`),
  approve dari UI mengubah status + hilang dari antrian, reject dari UI
  DENGAN dan TANPA note (keduanya sah), section Cabut Akses cuma tampil
  untuk user `approved` sungguhan (hilang lagi begitu `plainUser`/`revoked`),
  revoke dari UI berfungsi, DAN **test paling ditekankan prompt ini**:
  revoke terhadap user yang `active_mode='execution'` SUNGGUHAN terbukti
  reset ke `null`, dengan pembuktian tambahan `canAccessExecution()`
  langsung ikut berhenti mengizinkan (bukan cuma cek kolom mentah). Full
  suite naik dari 664 ke **675/675 lolos** (2225 assertion), `npm run build`
  sukses.

## Known gaps / backlog (perbaikan pasca-Batch 5, 2026-07-13)

- **RESOLVED: user yang aksesnya dicabut (revoked) admin sekarang BISA
  mengajukan ulang, sama seperti user yang ditolak (rejected).** Gap ini
  ditemukan tepat setelah Batch 5 selesai — beda dari `reject()` (kembali
  ke `dual_mode_status='none'`, bisa ajukan lagi), `revoke()` sebelumnya
  menyetel status ke `'revoked'` yang tidak pernah diperiksa `submitRequest()`
  sebagai kondisi boleh-mengajukan (`!== 'none'` menolaknya permanen).
  Aye putuskan `revoke()` harus berperilaku SAMA dengan `reject()` — kembali
  ke `none`, bukan status yang mengunci permanen.
- **Migrasi additif `dual_mode_requests.status` diperlebar menambah nilai
  `'revoked'`** (`2026_07_13_050000_add_revoked_to_dual_mode_requests_status.php`,
  pola sama widening `notifications.type` sebelumnya). `users.dual_mode_status`nya
  SENDIRI (enum dari Batch 1) TIDAK disentuh — nilai `'revoked'` di situ
  masih ada secara skema tapi sekarang tidak pernah ditulis lagi oleh
  service manapun, sengaja dibiarkan (bukan dipersempit) supaya tidak
  keluar dari scope "JANGAN ubah logic Batch 1-5 lain" milik fix ini.
- **`DualModeService::revoke()` sekarang membuat baris `DualModeRequest`
  BARU (`status='revoked'`, `reviewed_by`+`reviewed_at` terisi) alih-alih
  memutasi `users.dual_mode_status` ke `'revoked'`** — persis pola
  `reject()`: status EFEKTIF user (`users.dual_mode_status`) reset ke
  `none`, TAPI peristiwa pencabutannya sendiri permanen sebagai baris
  riwayat tersendiri, TERPISAH dari (dan tanpa mengubah) baris `'approved'`
  asli yang tetap utuh sebagai bukti siapa yang menyetujui akses itu
  pertama kali dan kapan. **Riwayat pencabutan bisa dilihat kapan pun
  lewat**: `DualModeRequest::where('user_id', $user->id)->orderBy('created_at')->get()`
  (seluruh riwayat lengkap: diajukan → disetujui → dicabut → [mengajukan
  ulang] → ...) atau `->where('status', 'revoked')` untuk cuma catatan
  pencabutannya. `active_mode` TETAP direset ke `null` tanpa syarat seperti
  sebelumnya (tidak diubah, sudah benar dari Batch 5).
- **BUG tie-break ditemukan saat menulis test untuk fix ini**:
  `Profile\Edit::render()`'s `latestDualModeRequest` (dipakai slot Info Mode
  Ganda untuk membedakan "belum pernah mengajukan" dari "pernah
  ditolak/dicabut, status balik `none`") sebelumnya cuma `->latest()`
  (order by `created_at` SAJA, tanpa tie-breaker). Urutan submit→approve→revoke
  di test (dan berpotensi juga permintaan asli yang cepat berurutan) bisa
  menghasilkan `created_at` yang identik ke detik yang sama, membuat baris
  mana yang "terbaru" jadi tidak pasti — pernah kejadian baris `'approved'`
  (lebih lama) yang terpilih alih-alih baris `'revoked'` (lebih baru).
  Ditambal dengan tie-breaker eksplisit: `->latest()->latest('id')->first()`.
- **Blade Profil**: sub-kondisi baru `$latestDualModeRequest?->status === 'revoked'`
  ditambahkan PARALEL ke sub-kondisi `rejected` yang sudah ada, di dalam
  cabang `@if ($user->dual_mode_status === 'none')` — cabang lama
  `@elseif ($user->dual_mode_status === 'revoked')` (sudah tidak pernah
  ter-trigger lagi sejak `dual_mode_status` tidak pernah bernilai `revoked`
  lagi) dihapus.
- **Test baru** (`DualModeRequestFlowTest.php`): pembuktian eksplisit riwayat
  tetap ada + baris `'approved'` asli tidak tersentuh
  (`test_revoke_records_a_permanent_history_row_without_touching_the_original_approval`),
  dan member yang dicabut bisa mengajukan ulang
  (`test_a_revoked_member_can_submit_a_fresh_request_afterward`). Test
  lama yang berasumsi `revoked` permanen diperbarui assertion-nya ke
  `none` (`DualModeRequestFlowTest.php` dan `AdminDualModePanelTest.php`,
  termasuk test kritis Batch 5 soal `active_mode` yang diverifikasi ulang
  tetap benar). Test baru tambahan di sisi admin
  (`test_a_revoked_member_can_be_granted_access_again_afterward`)
  membuktikan alur ulang lewat UI penuh (submit → panel admin approve).
  Full suite naik dari 675 ke **678/678 lolos** (2236 assertion),
  `npm run build` sukses.

## Known gaps / backlog (dari Fase 8 Batch 6, 2026-07-13)

- **`User::hasDualModeCapability()` akhirnya disambungkan** (placeholder
  `false` permanen sejak Fase 2) — `true` untuk `execution_member` SELALU
  (akses baca Eksplorasi mereka tanpa syarat, §2.2.A), atau
  `exploration_member` dengan `dual_mode_status === 'approved'` (TIDAK
  mensyaratkan `active_mode === 'execution'` seperti `canAccessExecution()`
  — kapabilitas berarti "bisa switch", bukan "sedang switch", supaya badge
  dan menu tetap tampil untuk member yang disetujui tapi belum pernah
  pindah mode). Gate `canAccessExecution()`/`canAccessExploration()` dari
  Batch 2-3 TIDAK disentuh sama sekali, sesuai batasan task ini.
- **`User::isInSwitchedMode()` (baru) — dua sumber kebenaran yang SENGAJA
  beda per arah**, dijelaskan lengkap ke Aye di poin 2 output di bawah:
  - `exploration_member`: baca kolom `active_mode` (persisten, diubah
    lewat `SwitchModeController`).
  - `execution_member`: diturunkan dari route request SAAT INI
    (`request()->routeIs('eksplorasi.*')`), BUKAN kolom database — akses
    baca mereka tidak pernah butuh state DB sama sekali (Batch 3), jadi
    tidak ada state "sungguhan" untuk disimpan; kolom baru di sini justru
    berisiko jadi sumber kebenaran kedua yang bisa basi (dua tab browser
    di dua portal berbeda akan rebutan satu nilai tersimpan).
- **`User::currentModeBadgeLabel()` (baru)** — null kalau
  `!hasDualModeCapability()` (badge tidak render sama sekali, termasuk
  admin dan exploration_member biasa); kalau capable: "Mode: Eksplorasi"
  / "Mode: Eksekusi" (exploration_member, tergantung `isInSwitchedMode()`),
  atau "Mode: Eksekusi" / "Mode: Eksplorasi (Baca)" (execution_member).
  `<x-shell.mode-badge>` (slot navbar sejak Fase 2) sekarang murni memanggil
  method ini, nol logic tersisa di blade.
- **`SwitchModeController` (baru, `POST /mode/switch`, named `mode.switch`)
  — SATU-SATUNYA jalur mutasi `active_mode` selain tinker sejak sekarang.**
  Ditolak 403 (bukan no-op senyap) untuk siapa pun selain
  `exploration_member` yang `dual_mode_status === 'approved'` — termasuk
  member yang baru saja di-revoke (status balik `none` per perbaikan
  pasca-Batch-5 di atas, TIDAK otomatis boleh switch, harus lewat
  `submitRequest()` → `approve()` lagi dulu). Redirect ke dashboard mode
  tujuan (`eksekusi.dashboard` saat pindah ke Eksekusi, `eksplorasi.dashboard`
  saat kembali) supaya user langsung mendarat di tempat yang relevan.
  `execution_member` TIDAK PERNAH POST ke sini sama sekali — sisi mereka
  murni link `<a>` biasa ke `eksplorasi.kurikulum`/`eksekusi.dashboard`
  (lihat account-menu.blade.php), tidak ada state untuk dimutasi.
- **Menu akun navbar (`account-menu.blade.php`)** — opsi switch mode
  disisipkan di atas tombol "Keluar", cuma tampil kalau
  `hasDualModeCapability()`. Teks PERSIS 3 variasi yang diminta ("Beralih
  ke Mode Eksekusi" / "Beralih ke Mode Eksplorasi" / "Kembali ke Mode
  Asal"), dipilih lewat `isInSwitchedMode()` yang sama dengan badge — satu
  method, dua tempat pakai, tidak ada logic switch-state yang terduplikasi.
- **Nav `execution_member` dapat entry baru "Jelajahi Eksplorasi"**
  (`config/navigation.php`, mengarah ke `eksplorasi.kurikulum`) — menutup
  gap yang dicatat sejak Batch 4 (akses baca sudah aktif dari Batch 3,
  tapi tidak ada jalur UI ke sana sama sekali, cuma URL langsung).
- **Card "Info Mode Ganda" untuk `execution_member` di Profil** (dulu
  placeholder dashed "Segera Hadir" sejak Fase 3 Batch 7b) sekarang kartu
  fungsional biasa — teks penjelasan akses baca + tombol "Buka Peta
  Kurikulum". Card exploration_member (Batch 4) juga diperbarui satu baris
  ("ganti mode lewat menu Akun begitu tersedia" → "Ganti mode lewat menu
  Akun (navbar)", karena sekarang sungguhan tersedia).
- **RESOLVED — gap #1 (dari Batch 2's audit): dropdown reviewer Praktik
  sekarang mencakup Mode Ganda member.** `Admin\Curriculum\Submissions\Index`
  dulu `User::where('role', 'execution_member')` literal di DUA tempat
  (sumber dropdown DAN guard di `assignReviewer()`) — diganti keduanya
  memakai `User::canAccessExecution()` (Gate Batch 2 yang sama, satu
  sumber kebenaran), lewat filter in-memory (`->filter->canAccessExecution()`)
  bukan query kondisi terpisah yang bisa basi dari method aslinya — ukuran
  tim kecil di app ini membuat load-semua-lalu-filter di PHP bukan masalah
  performa. **Konsekuensi yang disengaja**: pool reviewer berubah dinamis
  mengikuti `active_mode` member SAAT INI — Mode Ganda member yang approved
  tapi sedang di mode Eksplorasi (belum switch) TIDAK muncul sampai mereka
  switch sendiri, persis definisi `canAccessExecution()` — bukan bug,
  konsisten dengan literal instruksi task ini.
- **RESOLVED — gap #2 (dari Batch 2's audit): "Kontribusi Proyek" di Profil
  sekarang tampil untuk siapa pun yang PERNAH jadi ProjectMember/TaskAssignment,
  bukan role literal.** Dipindah keluar dari blok `@if role===exploration_member
  @elseif role===execution_member` (yang tetap mengatur galeri avatar,
  TIDAK diubah) jadi blok tersendiri, kondisi tampil:
  `role==='execution_member' || $projectContributions->isNotEmpty()` —
  native execution_member tetap selalu lihat (termasuk state kosong,
  perilaku lama dipertahankan persis), Mode Ganda member cuma lihat kalau
  memang punya kontribusi asli (menghindari card kosong yang tidak relevan
  untuk mayoritas exploration_member biasa yang tidak pernah menyentuh
  Eksekusi). Query `projectContributions()` sendiri TIDAK diubah sama
  sekali (murni `whereHas('members', ...)`, sudah agnostik role dari
  awal) — cuma method `render()` yang dulu menjaganya di belakang role
  check dihapus.
- **Test baru (29 test lintas 4 file)**: `tests/Feature/DualMode/ModeSwitchingTest.php`
  (19 test — kapabilitas per kombinasi role/status, badge per kombinasi,
  teks dropdown per state, `SwitchModeController` toggle sungguhan +
  seluruh jalur penolakan termasuk member revoked), tambahan di
  `SubmissionAssignmentTest.php` (4 test — dropdown include/exclude Mode
  Ganda member berdasar `active_mode` SAAT INI, assign end-to-end untuk
  Mode Ganda member, refusal untuk exploration_member biasa),
  `EditTest.php` (2 test — Kontribusi Proyek tampil untuk Mode Ganda
  member WALAU `active_mode` sudah balik ke origin lagi karena datanya
  historis, dan TIDAK tampil kosong untuk exploration_member biasa),
  `NavPopupTest.php` (1 test — link "Jelajahi Eksplorasi" muncul).
  `DualModeSchemaTest.php`'s test lama yang membuktikan
  `hasDualModeCapability()` "selalu false" diperbarui (bukan dihapus) —
  sekarang jadi bukti sempit "user tanpa kapabilitas tetap false", fixture
  lama yang harusnya jadi `true` (approved+active) dipindah cakupannya ke
  `ModeSwitchingTest.php`. Full suite naik dari 678 ke **707/707 lolos**
  (2305 assertion), `npm run build` sukses.

## Known gaps / backlog (dari Fase 8 Batch 7, TERAKHIR, 2026-07-13)

- **Pertanyaan kritis dikonfirmasi ke Aye via `AskUserQuestion`, BUKAN
  diasumsikan (sesuai instruksi eksplisit task ini):** apakah
  `exploration_member` yang `approved` DAN `active_mode='execution'`
  kehilangan akses tulis Eksplorasi selagi di mode Eksekusi, atau tetap
  akses ganda simultan? `docs/v_2.0/RANCANGAN_FINAL_WEBI-SPACE_v2.md` §2.2.B
  cuma bilang "dua riwayat data berjalan paralel & permanen... sekalipun
  sedang aktif di mode Eksekusi" — itu soal DATA tidak terhapus, bukan
  soal akses TULIS boleh atau tidak secara eksplisit, jadi genuinely
  ambigu. **Jawaban Aye: akses ganda simultan (Opsi A)** — `active_mode`
  cuma menentukan badge/tujuan navigasi default, TIDAK PERNAH mengunci
  salah satu portal. Ini PERSIS perilaku kode yang sudah ada sejak Batch
  3 (`isReadOnlyExploration()` cuma cek `role==='execution_member'`, tidak
  pernah cek `active_mode`) — jadi **nol perubahan Gate/kode dibutuhkan**,
  cuma dikunci formal lewat `DualModeFullMatrixTest.php` supaya batch masa
  depan tidak diam-diam meregresi ini.
- **`tests/Feature/DualMode/DualModeFullMatrixTest.php` (baru, 11 test)** —
  menguji SEMUA baris matriks role×mode dari prompt task ini secara
  eksplisit (bukan cuma direferensikan): `none`/`pending`/`approved-belum-
  switch` exploration_member (tulis Eksplorasi penuh, Eksekusi ditolak),
  baris paling kritis `approved`+`active_mode=execution` (tulis Eksplorasi
  PENUH + baca Eksplorasi + Eksekusi penuh, SEKALIGUS, dibuktikan dalam
  satu test yang sama — submit kuis berhasil DAN buka Kanban proyek
  berhasil DAN assign task berhasil, semua untuk user yang sama tanpa
  switch di antaranya), `execution_member` (baca-saja+Eksekusi penuh), dan
  **admin — dibuktikan BUKAN "akses penuh ke semuanya" seperti
  penyederhanaan tabel di prompt, tapi route-scoped**: rute Eksplorasi
  member-facing (Kurikulum/Unit) TETAP tertutup untuk admin (perilaku lama
  sejak Batch 3, admin punya panel `/admin/curriculum/*` sendiri, bukan
  regresi), Forum Eksplorasi tetap terbuka untuk monitoring, Eksekusi
  personal-only (dashboard/avatar/kalender) tetap tertutup untuk admin
  (dari SEBELUM Fase 8), Eksekusi shared/manageable (ideas/projects) tetap
  terbuka. Didokumentasikan presisi di sini supaya tidak disalahpahami
  sebagai bug di masa depan.
- **Kebocoran data lintas-mode: NOL, dibuktikan eksplisit (bukan cuma
  diasumsikan dari struktur tabel terpisah).** 3 test: menyelesaikan task
  Eksekusi (todo→in_progress→in_review→done) tidak pernah membuat baris
  `UserExplorationProgress`; menyelesaikan kuis Eksplorasi tidak pernah
  mengubah `TaskAssignment`/status `Task`/jumlah `ProjectMember`; DAN satu
  test interleaved (kuis→task→kuis→task bergantian untuk SATU user yang
  sama) membuktikan kedua riwayat tetap akurat independen satu sama lain
  bahkan di bawah aktivitas yang benar-benar bercampur, bukan cuma
  aksi tunggal masing-masing.
- **RESOLVED — pertanyaan terbuka dari dokumen rancangan §2.2.B ("nasib
  assignment/task yang sudah ada saat dicabut... kemungkinan tetap sebagai
  riwayat, tidak otomatis unassign") dikonfirmasi PERSIS itu perilaku kode
  saat ini, lewat test baru.** `DualModeService::revoke()` (Batch 5, tidak
  diubah batch ini) tidak pernah menyentuh `ProjectMember`/`TaskAssignment`
  sama sekali — cuma `users.dual_mode_status`/`active_mode`. Dibuktikan:
  setelah revoke, baris `ProjectMember`/`TaskAssignment` lama TETAP ADA
  (riwayat utuh), TAPI `canAccessExecution()` sudah menolak dan rute
  Eksekusi (Kanban dst.) sudah 403 — akses yang hilang, bukan datanya.
  Bukan gap baru, cuma menutup pertanyaan terbuka lama dengan bukti
  konkret alih-alih dugaan.
- **Non-regresi 4 area dikonfirmasi via full test suite masing-masing
  (59 test gabungan): Leaderboard Eksplorasi** (`Admin\DashboardTest`),
  **`FoxAvatarServiceTest`** (independen dari Mode Ganda, tidak tersentuh
  batch manapun di Fase 8), **Kanban** (`TaskManagementTest` — logic
  `changeStatus()` yang sama dipanggil drag-drop, `ProjectTabsTest` — tab
  Kanban tetap render benar setelah middleware `mode:execution,admin`
  Batch 2), **alur review Praktik** (`PraktikReviewTest` +
  `SubmissionAssignmentTest` — termasuk perbaikan dropdown reviewer Mode
  Ganda dari Batch 6, dikonfirmasi ulang tetap benar). Semua 100% lolos,
  nol assertion diubah.
- **Full regression sweep akhir: 718/718 test lolos (2355 assertion),
  `npm run build` sukses (hash CSS/JS identik dengan sebelum batch ini —
  batch ini nol perubahan Blade/asset, murni test + 1 klarifikasi
  keputusan produk).**

### RINGKASAN AKHIR FASE 8 (Mode Ganda) — SELESAI 2026-07-13

**Ketujuh batch, status akhir:**
1. **Batch 1** (Skema) — `users.dual_mode_status`/`active_mode`, tabel
   `dual_mode_requests`. Selesai, terverifikasi (10 test schema).
2. **Batch 2** (Gate Eksekusi) — `canAccessExecution()`, middleware
   `EnsureCanAccessMode`, 8 route/grup (18 URL) di-swap dari `role:` ke
   `mode:`. Selesai, terverifikasi (12 test, 115 assertion, seluruh 18 URL
   dites bukan sampel). 2 gap ditemukan saat audit (reviewer Praktik,
   Kontribusi Proyek Profil) — **keduanya RESOLVED di Batch 6.**
3. **Batch 3** (Akses baca Eksplorasi) — `canAccessExploration()`,
   `isReadOnlyExploration()`, 6 rute dibuka baca-saja untuk
   `execution_member`, 7 titik guard tulis. Selesai, terverifikasi (16
   test). 2 bug ditemukan+ditambal SAAT implementasi (bukan sesudah):
   urutan route wildcard vs literal, `recordUnitOpened()` yang lolos dari
   guard awal.
4. **Batch 4** (Alur pengajuan member) — `DualModeService::submitRequest()`/
   `approve()`/`reject()`, notifikasi broadcast admin. Selesai,
   terverifikasi (21 test). Gap dicatat: nav `execution_member` ke
   Eksplorasi kosong — **RESOLVED di Batch 6.**
5. **Batch 5** (Panel admin) — `Admin\DualMode\Index`, cabut akses dari
   `Admin\Users\Edit`. Selesai, terverifikasi (11 test). Gap dicatat:
   `revoked` status permanen, tidak bisa mengajukan ulang — **RESOLVED**
   di perbaikan pasca-Batch-5 (turn terpisah, sebelum Batch 6): `revoke()`
   dirombak mirip `reject()`, kembali ke `none` + riwayat pencabutan
   tersimpan permanen sebagai baris `DualModeRequest` baru.
6. **Batch 6** (UI switching) — badge navbar, dropdown switch mode di menu
   akun, `SwitchModeController`, nav "Jelajahi Eksplorasi", DAN kedua gap
   dari Batch 2 ditutup (reviewer Praktik pakai `canAccessExecution()`,
   Kontribusi Proyek Profil berdasar data bukan role). Selesai,
   terverifikasi (29 test baru).
7. **Batch 7** (Sweep akhir, batch ini) — matriks lengkap seluruh
   kombinasi role×mode, kebocoran data lintas-mode, non-regresi 4 fitur
   existing, 1 pertanyaan kritis dikonfirmasi eksplisit ke Aye (bukan
   diasumsikan). Selesai, terverifikasi (11 test baru + 59 test
   non-regresi + full suite 718/718).

**SEMUA gap yang pernah tercatat sepanjang Fase 8 — status akhir:**
| Gap | Ditemukan di | Status |
|---|---|---|
| Reviewer Praktik keyed ke role literal | Batch 2 audit | RESOLVED (Batch 6) |
| Kontribusi Proyek Profil keyed ke role literal | Batch 2 audit | RESOLVED (Batch 6) |
| Route wildcard vs literal `/create` salah urutan | Batch 3 (saat implementasi) | RESOLVED (Batch 3) |
| `recordUnitOpened()` lolos dari guard read-only | Batch 3 (saat implementasi) | RESOLVED (Batch 3) |
| Nav `execution_member` kosong ke Eksplorasi | Batch 4 | RESOLVED (Batch 6) |
| `revoked` permanen, tidak bisa ajukan ulang | Batch 5 | RESOLVED (pasca-Batch-5) |
| Tie-break query `latestDualModeRequest` | Ditemukan saat fix pasca-Batch-5 | RESOLVED (pasca-Batch-5) |
| Ambiguitas akses simultan approved+active=execution | Batch 7 | RESOLVED — dikonfirmasi Aye: simultan, nol perubahan kode |
| Nasib assignment saat revoke (pertanyaan terbuka dokumen) | Dokumen §2.2.B sejak awal | RESOLVED — dikonfirmasi: tetap sebagai riwayat, sesuai saran dokumen sendiri |

**Tidak ada gap terbuka tersisa dari Fase 8 itu sendiri.** Satu-satunya
item terkait yang sempat masih backlog adalah **Forum General** (dari
Fase 7 Batch 4 — `project_id` null tapi konteks Eksekusi, perlu kolom
pembeda portal sebelum dibangun) — bukan bagian scope Fase 8, dicatat di
section Fase 7 Batch 4 di atas. **RESOLVED 2026-07-13** (task terpisah
"Forum General Eksekusi (Utang Fase 7)", setelah Fase 8 tuntas) — lihat
section "Known gaps / backlog (Forum General Eksekusi)" di bawah.

**Total test Fase 8 (Batch 1-7 + perbaikan pasca-Batch-5): 105 test baru**
sepanjang fase ini (10+12+16+21+11+29+11 = 110, minus overlap kecil dari
test yang di-rename/dipindah bukan ditambah). Suite naik dari 613 (akhir
Fase 7) ke **718/718 lolos** (2355 assertion) di akhir Fase 8.

## Known gaps / backlog (Forum General Eksekusi, "utang Fase 7", 2026-07-13)

- **Gap desain dari Fase 7 Batch 4 RESOLVED**: `forum_threads.portal`
  (enum `exploration`/`execution`, NOT NULL) ditambahkan — kolom pembeda
  eksplisit yang sebelumnya tidak ada, membuat Forum General Eksekusi
  (`project_id` null, konteks Eksekusi) akhirnya bisa dibedakan dari
  thread "General" Eksplorasi (juga `project_id`/`module_id`/`unit_id`
  semuanya null). **Backup database dijalankan lebih dulu**
  (`mysqldump`, sesuai instruksi wajib — migrasi ini membackfill data,
  bukan cuma `ADD COLUMN` kosong).
- **Migrasi 3 langkah** (`2026_07_13_060000_add_portal_to_forum_threads_table.php`):
  (1) tambah kolom `portal` nullable dulu (NOT NULL langsung mustahil
  dengan baris existing), (2) backfill — `project_id IS NOT NULL` →
  `execution` (Forum Proyek dari Fase 7 Batch 4), `project_id IS NULL` →
  `exploration` (thread lama, Forum General tidak pernah ada sebelum
  migrasi ini jadi tidak ada kasus ambigu untuk baris lama), (3) perketat
  ke NOT NULL. **Verifikasi backfill (dev DB)**: 2 thread total sebelum
  migrasi, keduanya `project_id` null → keduanya jadi `portal='exploration'`
  sesudah migrasi (spot-check manual per baris, cocok 100%); 0 baris
  `portal` null tersisa; 0 Forum Proyek existing saat itu (jadi 0 baris
  `portal='execution'` dari backfill, sesuai ekspektasi).
- **`App\Services\Forum\ForumService::createThread()` sekarang WAJIB
  menerima `portal` di array data** (tidak ada default/fallback) —
  filosofi sama dengan alasan kolom ini NOT NULL: portal harus SELALU
  eksplisit dari caller, tidak pernah ditebak dari `project_id`. Kedua
  caller lama diperbarui: `Eksplorasi\Forum\Create::save()` kirim
  `'portal' => 'exploration'`, `Eksekusi\Projects\Tabs\Forum::createThread()`
  kirim `'portal' => 'execution'`.
- **Halaman baru `App\Livewire\Eksekusi\Forum\{Index,Show,Create}`**
  (route `/eksekusi/forum`, `/eksekusi/forum/create`, `/eksekusi/forum/{thread}`,
  `/create` didaftarkan SEBELUM grup `/{thread}` — trap urutan wildcard-
  vs-literal yang sama seperti `/eksplorasi/forum/create` di Fase 8 Batch
  3, dihindari dengan pola yang sama persis). RBAC `mode:execution,admin`
  (BUKAN `role:execution_member,admin` literal) — konsisten konvensi
  Gate terpusat sejak Fase 8 Batch 2, anggota Mode Ganda approved+aktif
  otomatis ikut bisa akses tanpa pengecualian khusus. Reuse penuh
  `ForumService` untuk create/reply — nol logic Forum baru ditulis.
  Lebih sederhana dari Forum Eksplorasi (tanpa picker modul/unit, tanpa
  `target` peer/pic — field itu murni konsep Eksplorasi per keputusan
  Fase 7 Batch 4, `ForumService` sudah default null kalau di-omit).
- **Slot nav "Forum General" (`config/navigation.php['execution_member']`)
  diaktifkan** — `enabled: true`, mengarah ke `eksekusi.forum.index`. Ini
  slot `enabled: false` TERAKHIR yang tersisa di seluruh
  `config/navigation.php` (dicek eksplisit, nol sisa) — konsekuensinya,
  `tests/Feature/Shell/NavPopupTest.php`'s
  `test_disabled_slot_item_is_rendered_non_clickable` (yang selama ini
  selalu memakai slot `enabled:false` TERAKHIR yang tersisa sebagai
  subjek, sudah 3 kali ganti subjek: Praktik → Kalender Personal → Forum
  General) sekarang inject slot palsu lewat `config([...])` di dalam test
  itu sendiri alih-alih bergantung pada production config selalu punya
  placeholder tersisa — perilaku yang diuji (span non-klik + teks "segera
  hadir") tidak berubah, cuma sumber datanya.
- **Audit filter `portal` ke SEMUA query `ForumThread` existing** (sesuai
  instruksi eksplisit task ini, bukan cuma di titik yang strictly perlu):
  `Eksplorasi\Forum\Index`/`Show` (tambah `where('portal','exploration')`
  eksplisit, sebelumnya cuma `whereNull('project_id')` yang sekarang TIDAK
  LAGI cukup — Forum General juga punya `project_id` null), dan
  `Eksekusi\Projects\Tabs\Forum` (4 titik query — `mount()`, `openThread()`,
  `reply()`, `render()` — ditambah `where('portal','execution')` juga,
  walau di titik-titik ini kebocoran sebenarnya SUDAH mustahil secara
  struktural lewat `$project->forumThreads()` yang selalu resolve ke satu
  proyek nyata; ditambahkan tetap untuk eksplisit, sesuai instruksi
  blanket task ini, bukan karena ditemukan celah nyata di situ).
- **`Notification::linkUrl()` untuk `context_type='forum_thread'` diperluas
  jadi 3 varian** (dari 2): `project_id` terisi → tab Forum proyek terkait
  (tidak berubah); `project_id` null + `portal='execution'` → halaman
  Forum General baru (`/eksekusi/forum/{id}`, BARU); `project_id` null +
  `portal='exploration'` (default) → URL Eksplorasi lama, PERSIS tidak
  berubah. Sebelum perbaikan ini, notifikasi balasan Forum General
  (seandainya sempat ada) akan salah arah ke Forum Eksplorasi — sekarang
  tidak mungkin lagi.
- **6 test lama yang membuat `ForumThread::create()` LANGSUNG (bukan
  lewat `ForumService`) ditambah `'portal' => 'exploration'` ke fixture-nya**
  (kolom baru NOT NULL, insert langsung tanpa itu akan gagal) — di
  `NavPopupTest.php`, `RouteAccessMatrixTest.php` (2 titik),
  `ResourcesAndForumTest.php`, `NotificationTriggersTest.php`,
  `ReadOnlyExplorationTest.php`. **NOL baris `assert*()` di file-file ini
  disentuh** — cuma baris fixture (array yang dikirim ke `::create()`)
  yang berubah, dikonfirmasi lewat histori edit langsung (tiap edit
  cuma menambah satu baris `'portal' => 'exploration'` ke array yang
  sudah ada).
- **Test baru `tests/Feature/Execution/GeneralForumTest.php` (17 test)**:
  RBAC (execution_member/admin bisa, exploration_member 403 di ketiga
  route termasuk lewat Livewire langsung), CRUD (create/reply/notifikasi
  dengan link benar, tidak notifikasi diri sendiri), isolasi 3-arah
  (thread General tidak bocor ke Index Eksplorasi ATAU tab Forum Proyek
  manapun, sebaliknya thread Eksplorasi/Proyek tidak bocor ke Index
  General), 404 3-arah lintas route Show, `linkUrl()` diuji untuk KETIGA
  varian sekaligus dalam satu test, dan pembuktian `portal` NOT NULL di
  level skema (insert eksplisit `NULL` lewat query builder mentah ditolak
  — bukan lewat "key di-omit", karena MySQL ENUM punya kuirk implisit
  fallback ke nilai enum pertama saat NOT NULL tanpa DEFAULT dan key
  di-omit sepenuhnya, bahkan di strict mode; NULL eksplisit tetap ditolak
  tanpa terpengaruh kuirk itu, jadi itu yang dibuktikan).
- **Non-regresi dikonfirmasi EKSPLISIT**: seluruh test Forum lama
  (`ResourcesAndForumTest`, `ProjectForumTest`, `NotificationTriggersTest`,
  `NotificationScopingAndSafetyTest`, `RouteAccessMatrixTest`,
  `NavPopupTest`, `ReadOnlyExplorationTest` — 64 test gabungan) 100%
  lolos. Full suite naik dari 718 ke **735/735 lolos** (2391 assertion),
  `npm run build` sukses.

## Progress Fase 2
- [x] 2.0 Fondasi Database (migration + model untuk SELURUH entitas)
- [x] 2.1 Autentikasi dan Manajemen Akun
- [x] 2.2 Modul LMS Eksplorasi
- [x] 2.3 Konten Kurikulum
- [x] 2.4 Modul Manajemen Proyek Eksekusi
- [x] 2.5 WEBI
- [x] 2.6 Integrasi Antar Modul
- [x] 2.6b UI Notifikasi Terpadu
- [x] 2.7 Testing Internal
- [ ] 2.8 Deployment Live — persiapan kode selesai (`.env.example`, build asset,
      scheduler, `DEPLOYMENT_CHECKLIST.md`); eksekusi manual di server oleh
      user masih berjalan
- [x] 2.9 Kontrol Akses Attachment