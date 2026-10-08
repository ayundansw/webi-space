# Recon — WEBI v1.0

Laporan ini murni observasi kode NYATA saat ini. Dibuat sebagai bekal
WEBI kontekstual + riwayat percakapan (2.2.3). **Tidak ada kode yang
diubah.**

## A. Struktur komponen chat sekarang

**1. Path lengkap:**
- Class Livewire: `app/Livewire/Eksplorasi/Webi/Chat.php`
- View: `resources/views/livewire/eksplorasi/webi/chat.blade.php`
- Route: `/eksplorasi/webi` (`routes/web.php`, dalam group
  `middleware(['auth', 'role:exploration_member'])->prefix('eksplorasi')`,
  nama route `eksplorasi.webi`) — **cuma exploration_member**, dikonfirmasi
  juga oleh `tests/Feature/Webi/ChatTest.php::test_execution_member_and_admin_cannot_access_webi_chat`.

**2. Alur submit → proses → tampil** (`Chat::sendMessage()` →
`ChatService::sendMessage()`):
1. Validasi `messageText` (required, max 4000).
2. `ChatService::sendMessage()`: cek rate limit harian dulu (lempar
   `RateLimitExceededException` kalau sudah 50 pesan hari itu — TIDAK
   memanggil Gemini sama sekali kalau limit tercapai).
3. Ambil histori (`historyFor()`, 20 pesan terakhir dari conversation
   AKTIF), ambil `currentUnit` user (`$user->explorationProgress?->currentUnit`),
   bangun evaluation bank untuk unit itu.
4. Simpan `Message` (sender `user`) — `unit_context` diisi dari
   `currentUnit` DI SINI.
5. Cek guardrail Layer 2 input (`checkEvalDetectionInput`) — log flag
   kalau mirip soal evaluasi (logging only, tidak memblokir).
6. Bangun `[USER_CONTEXT]` (personalisasi) + `[RELEVANT_CURRICULUM_CONTENT]`
   (konten kurikulum) + system prompt lengkap → panggil Gemini
   (`GeminiClient::generate()`).
7. Cek guardrail Layer 2 output (`checkOutputAgainstAnswers`) — kalau
   balasan mengandung jawaban evaluasi, retry SEKALI dengan instruksi
   koreksi; kalau retry masih bocor, ganti total dengan generic refusal.
8. Simpan `Message` balasan (sender `webi`), cek `checkDomainRejection`
   (log flag kalau balasan cocok salah satu template penolakan domain).
9. Livewire component (`Chat::sendMessage()`) terima `Message` balasan,
   kalau `voiceMode` aktif: bersihkan tag rekomendasi + markdown, dispatch
   event browser `webi-reply-ready` untuk TTS.
10. `render()`: ambil SEMUA pesan di conversation AKTIF, parse tiap pesan
    lewat `RecommendationParser` (ekstrak+validasi tag rekomendasi) dan
    `MessageRenderer` (markdown → HTML aman) sebelum dikirim ke view.

**3. Service pendukung dan perannya:**
| Service | Peran |
|---|---|
| `GeminiClient` | Wrapper HTTP tipis ke Gemini REST API. Timeout 20 detik, retry 2x, error diterjemahkan jadi pesan ramah per status code. |
| `SystemPromptBuilder` | Merakit system prompt: persona+domain (statis), proteksi evaluasi (statis), instruksi voice mode (kondisional), instruksi personalisasi (kondisional), instruksi tag rekomendasi (kondisional), lalu blok data `[EVALUATION_BANK]`/`[USER_CONTEXT]`/`[RELEVANT_CURRICULUM_CONTENT]`. |
| `EvaluationBankBuilder` | Membangun `[EVALUATION_BANK]` (kunci jawaban unit aktif) untuk dijaga guardrail. |
| `GuardrailService` | Layer 2 (backend, di belakang instruksi prompt/Layer 1): validasi output vs kunci jawaban (bisa retry/blokir), deteksi input mirip soal (logging saja), deteksi domain-rejection (logging saja). |
| `PersonalizationContextBuilder` | Membangun `[USER_CONTEXT]` — read-only, tidak pernah menulis progres. |
| `CurriculumContextBuilder` | Membangun `[RELEVANT_CURRICULUM_CONTENT]` — konten unit aktif + unit terkait (keyword match) + ID navigasi unit/modul berikutnya. |
| `MessageRenderer` | Markdown → HTML aman (CommonMark, `html_input: escape`) untuk tampilan; markdown → plain text untuk TTS. |
| `RecommendationParser` | Ekstrak+validasi tag `[REKOMENDASI_UNIT:id]`/`[REKOMENDASI_MODUL:id]`, selalu di-strip dari teks tampil, ID hasil parse divalidasi ulang setiap kali pesan DITAMPILKAN (bukan sekali saat ditulis). |
| `ProactiveService` | Mode B (sapaan proaktif) — dijalankan sinkron dari `Chat::mount()`, bukan scheduler terpisah. |

## B. Data Conversation & Message

**4. Field aktual** (dikonfirmasi migrasi, persis sesuai dokumen
arsitektur):
- `conversations`: `id`, `user_id`, `started_at` (nullable), `last_message_at` (nullable). Tidak ada `updated_at`/`created_at` bawaan Eloquent.
- `messages`: `id`, `conversation_id`, `sender` (enum `user`/`webi`), `content` (text), `unit_context` (FK nullable ke `units`, `nullOnDelete`), `voice_mode` (boolean default false), `created_at` (nullable, tanpa `updated_at`).
- `guardrail_flags`: `id`, `message_id`, `flag_type` (enum `eval_detection`/`domain_rejection`/`output_validation`), `unit_id` (FK nullable), `details` (json), `created_at`.

**5. Pembuatan Conversation: BERBASIS SESI, bukan satu selamanya.**
`ChatService::activeConversationFor()` — kalau ada conversation TERAKHIR
milik user dan jeda dari `last_message_at`-nya masih di bawah
`config('webi.session_timeout_minutes')` (default **30 menit**), pakai
itu lagi. Kalau tidak (belum pernah chat, atau jeda sudah lewat), buat
`Conversation` BARU. **Artinya satu user SUDAH BISA punya banyak baris
`Conversation`** (satu per "sesi"), dikonfirmasi test
`test_message_after_session_timeout_starts_a_new_conversation`. Ini
kabar baik untuk riwayat — struktur data multi-conversation-per-user
SUDAH ADA secara alami, bukan perlu dibangun dari nol.

**6. `Message.unit_context`: SUDAH DIPAKAI, bukan kolom kosong.** Diisi
di `ChatService::sendMessage()` (baris pembuatan `Message` sender
`user` DAN `webi`, keduanya) dari `$user->explorationProgress?->currentUnit`
— yaitu unit TERAKHIR yang tercatat sebagai "sedang dikerjakan" user
lewat `UserExplorationProgress.current_unit_id` (diperbarui
`ProgressService::recordUnitOpened()`/`refreshCurrentUnit()` saat user
membuka/menyelesaikan unit di Peta Kurikulum/halaman unit). **PENTING:**
ini BUKAN "unit yang sedang dibuka di tab/halaman yang sama tempat chat
dipakai" — chat sekarang adalah HALAMAN TERPISAH (`/eksplorasi/webi`),
jadi `unit_context` merefleksikan histori progres global user, bukan
konteks visual real-time dari halaman spesifik yang sedang dilihat saat
mengirim pesan itu. Field ini SUDAH dipakai untuk 2 hal: (a) di-inject
sebagai bagian dari `[RELEVANT_CURRICULUM_CONTENT]` (WEBI tahu unit
"aktif" user), dan (b) di `ProactiveService::stuckUnitByRepeatedTopic()`
— deteksi topik yang sama ditanyakan berulang kali lintas SESI, dikelompokkan
per `unit_context`.

**7. Riwayat percakapan sekarang: HANYA sesi AKTIF, tidak ada browse
percakapan LAMA.** `Chat::mount()` selalu memanggil `activeConversationFor()`
— yang mengembalikan conversation aktif (dalam window 30 menit) ATAU
membuat yang baru. **Tidak ada mekanisme untuk memilih/membuka
conversation LAMA yang sudah lewat window sesi** dari sisi member. Dites:
`test_reopening_chat_shows_conversation_history` HANYA membuktikan
"dalam window sesi yang sama, riwayat pesan conversation itu masih
tampil saat halaman dibuka ulang" — bukan "browse semua sesi masa lalu".
**Satu-satunya tempat SEMUA pesan lintas SEMUA conversation seorang user
bisa dilihat sekarang adalah sisi ADMIN** (`Admin\Webi\Show`, lihat
bagian F) — member sendiri tidak punya UI setara.

## C. Konteks & personalisasi (yang TIDAK boleh rusak)

**8. `[USER_CONTEXT]`** (`PersonalizationContextBuilder::build()`):
`user_id`, `name`, `current_level` + `level_name`, `total_points`,
`current_unit` (judul), `completed_units` (daftar judul, comma-separated),
`interest_field` (comma-separated atau "(belum diisi)"), `voice_mode`
(true/false). Read-only, dikonfirmasi eksplisit di docblock ("WEBI
membaca data ini, tidak pernah menulis").

**9. WEBI SUDAH tahu "unit yang sedang dibuka"** — tapi dalam pengertian
"unit terakhir tercatat di progres", bukan "halaman spesifik yang
sedang dilihat user saat ini juga". Diisi lewat rantai yang sama seperti
poin 6: `UserExplorationProgress.current_unit_id` → `explorationProgress->currentUnit`
→ diteruskan ke `CurriculumContextBuilder::build($currentUnit, $userQuestion)`
dan `PersonalizationContextBuilder` (untuk judul unit di `[USER_CONTEXT]`).

**10. `CurriculumContextBuilder`** — baca dari `Unit::content` (kolom
konten kurikulum lama, sistem blok/markdown campuran) + `Unit::title`,
plus keyword-overlap sederhana (bukan vector search sungguhan, dikonfirmasi
di docblock file itu — `docs/tech-stack.md` menunda keputusan itu, tidak
pernah diambil). **Relevansi untuk 2.2.4 (migrasi Konten Dinamis):**
class ini membaca `Unit::content` mentah sebagai STRING (lewat
`stripDirectives()` yang cuma strip pola `[SAJIKAN: ...]`) — kalau 2.2.4
mengubah `Unit::content` jadi struktur blok terpisah (bukan satu string
markdown), method `build()`/`stripDirectives()`/`significantKeywords()`
di class ini KEMUNGKINAN BESAR perlu menyesuaikan cara mengambil teks
mentah dari struktur blok baru itu supaya WEBI tetap bisa membaca isi
unit. **Ini titik singgung nyata antara 2.2.3 (kalau menyentuh file ini)
dan 2.2.4 (migrasi konten) — perlu dikoordinasikan, bukan dikerjakan
sembarang urutan.** Sub-modul WEBI 2.2.3 sendiri (kontekstual + riwayat)
TIDAK PERLU menyentuh class ini sama sekali kalau cuma menambah cara
UI memicu/menampilkan chat, jadi risiko konkretnya rendah SELAMA 2.2.3
tidak ikut mengubah bagaimana `CurriculumContextBuilder` membaca konten.

## D. Guardrail & rate limiting (yang TIDAK boleh rusak)

**11. `GuardrailService`** — Layer 2 (backend), di belakang instruksi
system prompt (Layer 1, `SystemPromptBuilder::evaluationProtectionInstructions()`):
- `checkOutputAgainstAnswers()`: cek balasan WEBI vs kunci jawaban unit
  aktif — similarity (`similar_text()`) ATAU substring verbatim (untuk
  jawaban multi-kata pendek yang mungkin lolos dari dilusi similarity).
  **Satu-satunya yang bisa memblokir/retry balasan.**
- `checkEvalDetectionInput()`: cek pesan MASUK user vs teks soal evaluasi
  — logging saja (`GuardrailFlag` type `eval_detection`), tidak
  memblokir apa pun (Layer 1/prompt yang membuat model menolak).
- `checkDomainRejection()`: cek balasan WEBI vs 4 template penolakan
  domain tetap — logging saja (`domain_rejection`).
- Dipasang dari `ChatService::sendMessage()`, dipanggil untuk SETIAP
  pesan (bukan cuma unit dengan evaluasi — evaluation bank kosong =
  skip cek output/input, tapi domain-rejection selalu dicek).

**12. Rate limiting: `ChatService::messagesSentToday()`** — hitung
`Message` sender `user` HARI INI (semua conversation milik user, bukan
cuma yang aktif) — `whereDate('created_at', Carbon::today())`. Dicek DI
AWAL `sendMessage()`, SEBELUM Gemini dipanggil sama sekali (`config('webi.daily_message_limit')`,
default **50**). Kalau tercapai, lempar `RateLimitExceededException`
dengan pesan ramah, `Chat::sendMessage()` menangkapnya dan tampilkan di
`$errorMessage` (tidak ada balasan WEBI baru dibuat).

**13. Guardrail flag: dicatat via `GuardrailService::logFlag()`** →
`GuardrailFlag::create(['message_id', 'flag_type', 'unit_id', 'details'])`.
Admin lihat lewat `Admin\Webi\Index` (ringkasan jumlah flag per anggota,
`/admin/webi`) dan `Admin\Webi\Show` (detail lengkap per pesan,
`/admin/webi/{user}`, relasi `guardrailFlags` di-eager-load).

## E. STT/TTS (voice mode)

**14. Implementasi:** client-side murni via Web Speech API (browser),
tidak ada layanan STT/TTS eksternal. `chat.blade.php`'s inline
`<script>` (`webiVoice()` Alpine component):
- Feature-detect di `init()` (`window.SpeechRecognition`/
  `webkitSpeechRecognition` + `speechSynthesis` in window) — kalau
  browser tidak dukung, `voiceSupported = false`, tombol mic disembunyikan
  total (`x-if="voiceSupported"`), fallback penuh ke chat teks (mandatory
  fallback rule, dikonfirmasi).
- STT: `recognition.start()`/`stop()` via tombol mic, transkrip masuk
  ke `messageText` (textbox biasa) untuk DICEK USER dulu sebelum
  dikirim — tidak pernah auto-send.
- TTS: `speak(text)` dipanggil dari event `webi-reply-ready` (dipicu
  server via `$this->dispatch()` di `Chat::sendMessage()`, HANYA kalau
  `voiceMode` true). Teks yang di-dispatch SUDAH di-strip tag rekomendasi
  + markdown di SERVER (`MessageRenderer::toPlainText()`), client-side
  `speak()` strip markdown SEKALI LAGI sebagai lapis kedua (defense in
  depth, pola sama dengan guardrail 2-layer).

**`Message.voice_mode` diisi kapan:** di `ChatService::sendMessage()`,
untuk KEDUA baris `Message` (user DAN webi) dalam satu turn, dari
parameter `$voiceMode` yang diteruskan dari `Chat::sendMessage()`'s
public property `$voiceMode` — yang HANYA di-set true dari client lewat
`$wire.set('voiceMode', voiceMode)` saat browser benar-benar mendukung
DAN user mencentang toggle "Mode suara". Server tidak pernah
mengasumsikan true secara default.

## F. Entry point WEBI sekarang

**15. Cuma SATU entry point sekarang: menu sidebar Eksplorasi ("WEBI"
→ `/eksplorasi/webi`, `config/navigation.php`), mengarah ke halaman
chat PENUH.** Dikonfirmasi — tidak ada widget/floating-chat embed di
halaman lain manapun (dashboard, unit-show, dst). **RecommendationCard
BUKAN entry point terpisah** — itu kartu yang muncul DI DALAM chat yang
sudah terbuka, sebagai bagian dari satu balasan WEBI (link ke unit/modul
yang direkomendasikan), bukan cara MEMBUKA WEBI dari halaman lain.

**16. Trigger proaktif yang sudah ada** (`ProactiveService::determineTrigger()`,
semua dicek SAAT `Chat::mount()`, bukan push/scheduler terpisah, urutan
prioritas persis sesuai kode):
1. **Onboarding** — sekali seumur hidup, saat pertama kali buka chat.
2. **Level up** (`level_up`) — bypass SEMUA nudge throttling di bawah.
3. **Checkpoint** (`checkpoint`) — juga bypass throttling, beda framing
   dari feed dashboard (lihat detail poin 3 di docblock `ProactiveService`).
4. **Stagnasi** (`stagnation`) — N hari tanpa unit selesai (`config('webi.stagnation_days')`, default 5), kena batas nudge (max 1/hari, cooldown 3 hari kalau 3 nudge beruntun tidak direspons).
5. **Stuck** (`stuck`) — deteksi lewat 2 cara: `open_count_without_completion`
   >= threshold (default 3) PADA SATU unit yang belum selesai, ATAU
   topik yang sama (`unit_context`) ditanyakan >= threshold (default 3)
   kali lintas SESI (pakai `Message.unit_context`, konfirmasi lagi field
   ini genuinely dipakai). Kena batas nudge yang sama dengan stagnasi.

## G. Test terkait

11 file (`tests/Feature/Webi/`), satu baris per file:
| File | Yang diuji |
|---|---|
| `ChatTest.php` | RBAC akses chat, kirim pesan end-to-end (real form), riwayat tampil ulang dalam sesi, sesi baru vs lanjut sesi lama (timeout 30 menit), error Gemini tidak crash halaman, pesan kosong ditolak. |
| `GeminiClientTest.php` | Wrapper HTTP Gemini — response sukses, histori diteruskan, API key kosong, error 429/5xx/404 diterjemahkan, response tanpa candidates, thinking_level config. |
| `GuardrailTest.php` (integrasi via ChatService) | Evaluation bank ter-inject, input mirip soal ter-log, output bocor jawaban di-retry, retry masih bocor diganti generic refusal, domain-rejection ter-log, rate limit blokir SEBELUM panggil Gemini. |
| `GuardrailServiceTest.php` (unit, langsung ke service) | Nuansa deteksi leak: kata multi-kata dalam kalimat panjang tertangkap, kata pendek umum tidak false-positive, jawaban pendek exact tertangkap, balasan panjang yang cuma menyebut topik tidak ter-flag. |
| `PersonalizationTest.php` | Dua user beda progres dapat konteks beda untuk pertanyaan sama; konten unit aktif ter-inject dengan direktif `[SAJIKAN:]` di-strip. |
| `ProactiveTest.php` | Onboarding sekali, stagnasi, stuck, cuma 1 nudge/hari, cooldown setelah tidak direspons, resume setelah cooldown, respons user menandai log "responded", level-up tidak dobel kirim, checkpoint tidak duplikat wording dashboard. |
| `AdminMonitoringTest.php` | Notice transparansi selalu tampil, cuma admin bisa akses monitoring, index tampilkan jumlah pesan/flag per anggota, admin bisa lihat percakapan penuh + flag. |
| `VoiceModeTest.php` | Voice mode on menambah instruksi + flag pesan, voice mode off fallback teks biasa, tag VOICE_MODE yang bocor tetap di-strip. |
| `MarkdownRenderingTest.php` | Markdown jadi HTML asli di bubble, teks TTS markdown-nya di-strip, `Message.content` tersimpan RAW (markdown asli, tidak di-strip saat ditulis — untuk replay histori). |
| `MessageRendererTest.php` (unit) | Bold/italic/code jadi HTML asli, HTML mentah dari model di-escape (bukan dieksekusi), plain-text strip semua simbol markdown. |
| `RecommendationCardTest.php` | Rekomendasi unit/modul valid tampil sebagai kartu, ID halusinasi tidak error/tidak ada kartu, tanpa tag = tanpa kartu, tag di-strip dari teks TTS, **kartu muncul lagi saat membuka ulang riwayat percakapan** (paling relevan untuk 2.2.3!). |

**Paling berisiko pecah kalau UX chat diubah (kontekstual + riwayat):**
- `ChatTest.php::test_reopening_chat_shows_conversation_history` dan
  `test_message_within_session_timeout_reuses_the_same_conversation`/
  `test_message_after_session_timeout_starts_a_new_conversation` — kalau
  2.2.3 mengubah `activeConversationFor()` (mis. supaya user bisa
  membuka conversation LAMA secara eksplisit), logic timeout-based ini
  perlu dijaga tetap benar untuk kasus "belum pernah pilih conversation
  manapun secara eksplisit" (default behavior tetap harus sama).
- `RecommendationCardTest.php::test_recommendation_card_reappears_when_reopening_conversation_history`
  — SUDAH menguji "buka ulang histori tetap benar", jadi kalau UI riwayat
  berubah besar, test ini paling mungkin butuh disesuaikan caranya
  mengakses/membuka riwayat itu (bukan logic-nya).
- Test manapun yang mengasumsikan `Chat::mount()` SELALU memuat
  conversation AKTIF TUNGGAL (hampir semua test di atas) — kalau 2.2.3
  menambah parameter/route untuk "buka conversation id X", perlu
  dipastikan default (tanpa parameter) tetap identik dengan sekarang.

## H. Penilaian

**18. Seberapa besar pekerjaannya:**

**(a) WEBI kontekstual di halaman unit** — **infrastruktur data SUDAH
ADA dan SUDAH DIPAKAI** (`unit_context` per pesan, `CurriculumContextBuilder`
sudah baca "unit aktif"). **YANG BELUM ADA**: WEBI cuma bisa diakses
sebagai halaman terpisah, bukan widget/panel yang muncul DI DALAM
halaman unit. Membangun ini berarti:
- UI baru (mis. panel/slide-over/embedded chat di `unit-show.blade.php`),
  KEMUNGKINAN reuse component `Chat` yang sudah ada (mount dengan
  parameter unit eksplisit) daripada bikin komponen chat kedua dari nol.
- **Perbedaan penting yang perlu diputuskan**: sekarang `unit_context`
  = "unit terakhir tercatat di progres" (global, lewat
  `UserExplorationProgress`), BUKAN "unit yang sedang dibuka di
  komponen chat spesifik ini". Kalau WEBI kontekstual di halaman unit
  perlu tahu PERSIS unit halaman itu (bukan unit "aktif" secara global
  yang mungkin beda kalau user buka banyak tab/unit), `Chat`/`ChatService`
  perlu terima unit itu secara eksplisit dari parameter halaman, bukan
  murni dari `explorationProgress->currentUnit`. Ini bukan pekerjaan
  besar (`ChatService::sendMessage()` tinggal terima `$contextUnit`
  opsional), tapi HARUS diputuskan eksplisit sebelum implementasi,
  bukan diasumsikan otomatis benar dari infrastruktur yang ada.

**(b) UI browse + resume percakapan lama** — **data SUDAH cukup** (banyak
`Conversation` per user sudah otomatis tercipta lewat mekanisme session-timeout
yang sudah ada), **TIDAK PERLU migrasi**. Yang perlu dibangun murni UI:
daftar conversation lama (mis. per tanggal `started_at`), dan mekanisme
"buka conversation X" yang MENGGANTI cara `Chat::mount()` selalu
memanggil `activeConversationFor()` — perlu terima parameter conversation
opsional (kalau ada & milik user, pakai itu; kalau tidak, fallback ke
`activeConversationFor()` seperti sekarang). Pola akses/keamanannya
bisa MENIRU `Admin\Webi\Show::mount()` (`abort_unless` kepemilikan)
persis, cukup discope ke `Auth::id()` bukan `$user` parameter admin.

**19. Titik paling berisiko:**
- **Melanggar prinsip "riwayat cuma milik pemiliknya"** — kalau
  parameter conversation-id ditambahkan ke route/component, WAJIB
  verifikasi `$conversation->user_id === Auth::id()` (pola sama seperti
  RBAC attachment 2.9) sebelum menampilkan apa pun. Tidak melakukan ini
  = kebocoran privasi percakapan lintas-anggota, kelas bug paling serius
  di seluruh WEBI (lebih parah dari guardrail leak, karena ini kebocoran
  data personal, bukan cuma jawaban kuis).
- **Mengganggu rate limit lewat "kirim pesan dari conversation lama"** —
  `messagesSentToday()` sudah menghitung LINTAS SEMUA conversation user
  (bukan cuma yang aktif), jadi ini SEHARUSNYA aman otomatis SELAMA
  tidak ada kode baru yang menghitung ulang secara terpisah per-conversation.
  Perlu dipastikan implementasi baru tetap panggil `ChatService::sendMessage()`
  yang sama, bukan menulis jalur kirim pesan baru yang melewati cek ini.
- **`unit_context` yang diisi salah/tidak konsisten** kalau kontekstual
  di halaman unit menambahkan cara BARU mengisi `unit_context` (mis.
  langsung dari parameter route, bukan dari `explorationProgress->currentUnit`)
  — perlu pastikan `ProactiveService::stuckUnitByRepeatedTopic()`
  (yang query grup-by `unit_context`) tetap dapat data yang masuk akal,
  tidak tiba-tiba pecah jadi banyak unit_context berbeda untuk
  percakapan yang sebenarnya tentang satu unit yang sama.
- **`CurriculumContextBuilder` vs 2.2.4** — lihat poin 10, murni
  koordinasi urutan kerja, bukan risiko teknis kalau 2.2.3 tidak
  menyentuh file itu.

**20. Migrasi:** **TIDAK PERLU** untuk kedua fitur. Skema `Conversation`/
`Message` (termasuk `unit_context`) sudah lengkap dan sudah terpakai
sebagian. Murni penambahan: (a) cara UI memicu/menampilkan chat
(embedding di halaman lain), (b) parameter opsional untuk memilih
conversation yang mana yang di-mount, (c) mungkin satu query baru
("daftar conversation milik user, urut terbaru") — tidak satu pun dari
ini butuh kolom/tabel baru.
