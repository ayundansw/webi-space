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