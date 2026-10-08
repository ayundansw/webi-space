<?php

namespace Tests\Feature\Webi;

use App\Livewire\Eksplorasi\Webi\Chat;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Module;
use App\Models\ProactiveLog;
use App\Models\User;
use App\Models\UserExplorationProgress;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Onboarding proactive greeting (task 2.5 Batch 5) fires on every fresh
     * user's first Chat::mount() — pre-marking it sent here keeps these
     * chat-mechanics tests focused; the greeting itself is covered by
     * tests/Feature/Webi/ProactiveTest.php.
     */
    private function member(): User
    {
        $user = User::create([
            'name' => 'Member',
            'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);

        ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'onboarding']);

        return $user;
    }

    private function fakeGeminiReply(string $text): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => $text]]]]],
            ], 200),
        ]);
    }

    /**
     * Fase 8 Batch 3 (docs/v_2.0/archive/sumber-konsolidasi/RANCANGAN_FINAL_WEBI-SPACE_v2.md §2.2.A):
     * execution_member reading Eksplorasi in read-only mode is EXPLICITLY
     * allowed to keep using WEBI chat ("bantuan baca kontekstual") — the
     * ONE write action left unguarded on purpose among all the Eksplorasi
     * routes opened this batch. Previously (before Fase 8) this asserted
     * execution_member got 403 here too; that assertion is now UPDATED
     * (not deleted) to match the intentional new behavior — admin's own
     * exclusion is unrelated to this batch and stays unchanged (webi was
     * always `exploration_member`-only, never admin, and still is).
     */
    public function test_execution_member_can_now_access_webi_chat_in_read_only_mode_but_admin_still_cannot(): void
    {
        $executionMember = User::create([
            'name' => 'Executor', 'email' => 'exec@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'execution_member', 'membership_status' => 'active',
        ]);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'admin', 'membership_status' => 'active',
        ]);

        $this->actingAs($executionMember)->get('/eksplorasi/webi')->assertOk();
        $this->actingAs($admin)->get('/eksplorasi/webi')->assertForbidden();
    }

    public function test_member_can_send_a_message_through_the_real_form_and_gets_a_persisted_reply(): void
    {
        $user = $this->member();
        $this->fakeGeminiReply('Halo! Ada yang bisa aku bantu soal materi kurikulum?');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Halo WEBI, HTML itu apa?')
            ->call('sendMessage')
            ->assertSet('messageText', '')
            ->assertSet('errorMessage', null);

        $conversation = Conversation::where('user_id', $user->id)->first();
        $this->assertNotNull($conversation);

        $messages = Message::where('conversation_id', $conversation->id)->orderBy('created_at')->get();
        $this->assertCount(2, $messages);
        $this->assertSame('user', $messages[0]->sender);
        $this->assertSame('Halo WEBI, HTML itu apa?', $messages[0]->content);
        $this->assertSame('webi', $messages[1]->sender);
        $this->assertSame('Halo! Ada yang bisa aku bantu soal materi kurikulum?', $messages[1]->content);
    }

    public function test_reopening_chat_shows_conversation_history(): void
    {
        $user = $this->member();
        $this->fakeGeminiReply('Jawaban pertama.');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Pertanyaan pertama')
            ->call('sendMessage');

        // simulate reopening the page (fresh component mount, not the same instance)
        $reopened = Livewire::actingAs($user)->test(Chat::class);
        $reopened->assertSee('Pertanyaan pertama');
        $reopened->assertSee('Jawaban pertama.');
    }

    public function test_message_within_session_timeout_reuses_the_same_conversation(): void
    {
        $user = $this->member();
        $this->fakeGeminiReply('balasan');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'pesan pertama')
            ->call('sendMessage');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'pesan kedua')
            ->call('sendMessage');

        $this->assertSame(1, Conversation::where('user_id', $user->id)->count());
    }

    public function test_message_after_session_timeout_starts_a_new_conversation(): void
    {
        $user = $this->member();

        $oldConversation = Conversation::create([
            'user_id' => $user->id,
            'started_at' => now()->subHours(2),
            'last_message_at' => now()->subHours(2),
        ]);

        $this->fakeGeminiReply('balasan baru');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'pesan setelah jeda lama')
            ->call('sendMessage');

        $this->assertSame(2, Conversation::where('user_id', $user->id)->count());
        $this->assertSame(0, Message::where('conversation_id', $oldConversation->id)->count());

        $newConversation = Conversation::where('user_id', $user->id)->where('id', '!=', $oldConversation->id)->first();
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $newConversation->id,
            'content' => 'pesan setelah jeda lama',
        ]);
    }

    public function test_gemini_failure_shows_friendly_error_without_crashing_the_page(): void
    {
        $user = $this->member();
        Http::fake([
            '*' => Http::response(['error' => ['code' => 503, 'message' => 'high demand']], 503),
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Pertanyaan saat API down')
            ->call('sendMessage')
            ->assertOk()
            ->assertSet('errorMessage', fn ($value) => ! empty($value))
            // the input must clear even on failure — otherwise the user might
            // not notice the error banner and resubmit the same text again
            ->assertSet('messageText', '');

        // the user's own message is still saved even though WEBI couldn't reply
        $this->assertDatabaseHas('messages', ['sender' => 'user', 'content' => 'Pertanyaan saat API down']);
        $this->assertDatabaseMissing('messages', ['sender' => 'webi']);
    }

    public function test_empty_message_is_rejected(): void
    {
        $user = $this->member();

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', '')
            ->call('sendMessage')
            ->assertHasErrors('messageText');

        $this->assertDatabaseCount('messages', 0);
    }

    /**
     * 2.2.3 (riwayat percakapan) — SECURITY, most important test in this
     * batch: a member must never be able to read another member's chat
     * history by guessing/changing a conversation ID in the URL. This is a
     * worse class of bug than a guardrail leak (personal data, not just a
     * quiz answer), per docs/v_2.0/archive/sumber-konsolidasi/RECON_webi.md's own risk assessment.
     */
    public function test_member_cannot_open_another_members_conversation(): void
    {
        $owner = $this->member();
        $intruder = User::create([
            'name' => 'Intruder', 'email' => 'intruder@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member', 'membership_status' => 'active',
        ]);

        $ownerConversation = Conversation::create([
            'user_id' => $owner->id,
            'started_at' => now()->subDays(2),
            'last_message_at' => now()->subDays(2),
        ]);
        Message::create([
            'conversation_id' => $ownerConversation->id,
            'sender' => 'user',
            'content' => 'Rahasia milik owner',
        ]);

        $this->actingAs($intruder)->get('/eksplorasi/webi/'.$ownerConversation->id)->assertForbidden();
    }

    public function test_member_can_see_their_own_past_conversations_and_not_other_members(): void
    {
        $user = $this->member();
        $otherMember = User::create([
            'name' => 'Lain', 'email' => 'lain@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member', 'membership_status' => 'active',
        ]);

        $ownOld = Conversation::create(['user_id' => $user->id, 'started_at' => now()->subDays(3), 'last_message_at' => now()->subDays(3)]);
        Message::create(['conversation_id' => $ownOld->id, 'sender' => 'user', 'content' => 'Pertanyaan lama soal HTML']);

        $othersConversation = Conversation::create(['user_id' => $otherMember->id, 'started_at' => now()->subDays(1), 'last_message_at' => now()->subDays(1)]);
        Message::create(['conversation_id' => $othersConversation->id, 'sender' => 'user', 'content' => 'Punya anggota lain']);

        $component = Livewire::actingAs($user)->test(Chat::class);

        $component->assertSee('Pertanyaan lama soal HTML');
        $component->assertDontSee('Punya anggota lain');
    }

    /**
     * Redesign halaman WEBI Chat penuh (sidebar riwayat permanen + kolom
     * chat viewport-fixed) -- membuktikan sidebar baru merender data yang
     * sama ($pastConversations) yang sebelumnya dipakai dropdown, dan
     * item riwayat yang SEDANG DIBUKA ditandai beda (border-accent/30
     * bg-accent-soft/40) dari item lain.
     */
    public function test_full_page_sidebar_highlights_the_currently_open_conversation(): void
    {
        $user = $this->member();
        $current = Conversation::create(['user_id' => $user->id, 'started_at' => now()->subHours(2), 'last_message_at' => now()->subHours(2)]);
        Message::create(['conversation_id' => $current->id, 'sender' => 'user', 'content' => 'Ini percakapan yang sedang dibuka']);

        $other = Conversation::create(['user_id' => $user->id, 'started_at' => now()->subDays(2), 'last_message_at' => now()->subDays(2)]);
        Message::create(['conversation_id' => $other->id, 'sender' => 'user', 'content' => 'Percakapan lain yang tidak sedang dibuka']);

        $html = Livewire::actingAs($user)->test(Chat::class, ['conversation' => $current])->html();

        $this->assertStringContainsString('Ini percakapan yang sedang dibuka', $html);
        $this->assertStringContainsString('Percakapan lain yang tidak sedang dibuka', $html);
        $this->assertStringContainsString('border-accent/30 bg-accent-soft/40', $html);
    }

    /**
     * Sidebar baru menggantikan dropdown "Riwayat" di halaman penuh --
     * tombol "Percakapan Baru" & pixel-art accent kolom chat (card-pixel-accent-*,
     * REUSE class yang sama dengan card Dashboard Eksplorasi) harus ada.
     */
    public function test_full_page_shows_new_conversation_button_and_pixel_accent_frame(): void
    {
        $user = $this->member();

        Livewire::actingAs($user)->test(Chat::class)
            ->assertSee('Percakapan Baru')
            ->assertSeeHtml('card-pixel-accent-top')
            ->assertSeeHtml('card-pixel-accent-left');
    }

    /**
     * Putaran perbaikan Aye: "Percakapan Baru" harus SELALU mengarah ke
     * sesi baru & bersih -- sebelumnya cuma link ke rute default yang bisa
     * reuse sesi aktif kalau masih dalam window waktu (activeConversationFor()).
     * newConversation() harus SELALU membuat Conversation baru, bahkan
     * kalau sesi sebelumnya masih dalam window waktu aktif dan punya pesan.
     */
    public function test_new_conversation_button_always_creates_a_fresh_empty_conversation(): void
    {
        $user = $this->member();
        // Backdated by a full minute (not now()) so ordering by started_at
        // is unambiguous -- MySQL datetime columns here don't carry
        // sub-second precision, so two rows both stamped "now()" in the
        // same test can tie on ORDER BY started_at DESC.
        $stillActive = Conversation::create(['user_id' => $user->id, 'started_at' => now()->subMinute(), 'last_message_at' => now()->subMinute()]);
        Message::create(['conversation_id' => $stillActive->id, 'sender' => 'user', 'content' => 'Pesan di sesi yang masih aktif']);

        $countBefore = Conversation::where('user_id', $user->id)->count();

        Livewire::actingAs($user)->test(Chat::class)->call('newConversation');

        $countAfter = Conversation::where('user_id', $user->id)->count();
        $this->assertSame($countBefore + 1, $countAfter, 'newConversation() must always create a new row, never reuse the still-active session.');

        $newest = Conversation::where('user_id', $user->id)->orderByDesc('started_at')->first();
        $this->assertNotSame($stillActive->id, $newest->id);
        $this->assertCount(0, $newest->messages, 'A freshly created conversation must start with zero messages.');
    }

    public function test_opening_an_old_conversation_shows_its_messages_and_flags_history_view(): void
    {
        $user = $this->member();
        $old = Conversation::create(['user_id' => $user->id, 'started_at' => now()->subDays(1), 'last_message_at' => now()->subDays(1)]);
        Message::create(['conversation_id' => $old->id, 'sender' => 'user', 'content' => 'Pertanyaan di sesi lama']);
        Message::create(['conversation_id' => $old->id, 'sender' => 'webi', 'content' => 'Jawaban di sesi lama']);

        $component = Livewire::actingAs($user)->test(Chat::class, ['conversation' => $old]);

        $component->assertSet('isHistoryView', true);
        $component->assertSee('Pertanyaan di sesi lama');
        $component->assertSee('Jawaban di sesi lama');
        $component->assertSee('Kamu sedang melihat percakapan lama');
    }

    public function test_continuing_an_old_conversation_appends_to_it_and_still_counts_toward_rate_limit(): void
    {
        $user = $this->member();
        $old = Conversation::create(['user_id' => $user->id, 'started_at' => now()->subDays(1), 'last_message_at' => now()->subDays(1)]);
        Message::create(['conversation_id' => $old->id, 'sender' => 'user', 'content' => 'Pertanyaan lama']);

        $this->fakeGeminiReply('Lanjutan jawaban.');

        Livewire::actingAs($user)->test(Chat::class, ['conversation' => $old])
            ->set('messageText', 'Lanjut nanya di sini')
            ->call('sendMessage')
            ->assertSet('errorMessage', null);

        // still just ONE conversation — the message was appended to the old
        // one, not routed into a brand new conversation.
        $this->assertSame(1, Conversation::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $old->id,
            'content' => 'Lanjut nanya di sini',
        ]);

        // sendMessage() was genuinely used (not a new send path bypassing it)
        // — messagesSentToday() must reflect BOTH the pre-existing "Pertanyaan
        // lama" message (created directly above, sender=user, today) AND the
        // new one just sent through the real flow.
        $service = app(\App\Services\Webi\ChatService::class);
        $this->assertSame(2, $service->messagesSentToday($user));
    }

    public function test_default_chat_url_without_conversation_id_behaves_exactly_as_before(): void
    {
        $user = $this->member();

        $this->actingAs($user)->get('/eksplorasi/webi')->assertOk();

        Livewire::actingAs($user)->test(Chat::class)
            ->assertSet('isHistoryView', false);
    }

    /**
     * 2.2.3 (WEBI kontekstual) tests below. These need real seeded Unit rows
     * (unlike the rest of this file), so each test seeds explicitly rather
     * than adding a class-wide setUp() that could shift existing tests'
     * assumptions.
     */
    private function memberWithGlobalCurrentUnit(\App\Models\Unit $globalUnit): User
    {
        $user = $this->member();

        UserExplorationProgress::create([
            'user_id' => $user->id,
            'current_level' => 1,
            'level_name' => 'Pengenal',
            'total_points' => 0,
            'current_unit_id' => $globalUnit->id,
        ]);

        return $user;
    }

    /**
     * PENYIMPANGAN SADAR dari PRD 5.1 (lihat CLAUDE.md "Known gaps / backlog
     * dari Fase 3"), dikonfirmasi eksplisit oleh Aye: notice transparansi
     * monitoring PIC TETAP tampil di halaman chat penuh, tapi SENGAJA
     * disembunyikan di panel kontekstual (embedded di halaman Materi). Test
     * ini mengunci perilaku itu supaya perubahan berikutnya tidak diam-diam
     * menghapusnya dari halaman penuh juga (yang justru akan jadi gap
     * compliance sungguhan).
     */
    public function test_pic_monitoring_notice_shown_on_full_page_but_hidden_in_contextual_panel(): void
    {
        $this->seed(ExplorationSampleSeeder::class);
        $unit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->first();
        $user = $this->member();

        Livewire::actingAs($user)->test(Chat::class)
            ->assertSee('Percakapanmu dengan WEBI bisa diakses PIC');

        Livewire::actingAs($user)->test(Chat::class, ['contextUnit' => $unit])
            ->assertDontSee('Percakapanmu dengan WEBI bisa diakses PIC');
    }

    public function test_message_from_contextual_panel_uses_the_page_unit_not_the_global_current_unit(): void
    {
        $this->seed(ExplorationSampleSeeder::class);
        $units = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->get();
        $globalUnit = $units[0];
        $pageUnit = $units[1];

        $user = $this->memberWithGlobalCurrentUnit($globalUnit);
        $this->fakeGeminiReply('Balasan soal unit halaman.');

        Livewire::actingAs($user)->test(Chat::class, ['contextUnit' => $pageUnit])
            ->set('messageText', 'Apa maksud bagian ini?')
            ->call('sendMessage');

        $this->assertDatabaseHas('messages', [
            'sender' => 'user',
            'content' => 'Apa maksud bagian ini?',
            'unit_context' => $pageUnit->id,
        ]);
        $this->assertDatabaseMissing('messages', [
            'sender' => 'user',
            'content' => 'Apa maksud bagian ini?',
            'unit_context' => $globalUnit->id,
        ]);
    }

    public function test_full_chat_page_without_context_unit_still_uses_global_current_unit(): void
    {
        $this->seed(ExplorationSampleSeeder::class);
        $globalUnit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->first();

        $user = $this->memberWithGlobalCurrentUnit($globalUnit);
        $this->fakeGeminiReply('Balasan biasa.');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Pertanyaan bebas')
            ->call('sendMessage');

        $this->assertDatabaseHas('messages', [
            'sender' => 'user',
            'content' => 'Pertanyaan bebas',
            'unit_context' => $globalUnit->id,
        ]);
    }

    public function test_contextual_panel_reuses_the_same_active_conversation_as_the_full_chat_page(): void
    {
        $this->seed(ExplorationSampleSeeder::class);
        $pageUnit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->first();

        $user = $this->member();
        $this->fakeGeminiReply('balasan');

        // start a conversation via the full chat page first
        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'pesan pertama di chat penuh')
            ->call('sendMessage');

        $conversationCountAfterFullChat = Conversation::where('user_id', $user->id)->count();

        // now open the contextual panel (fresh mount) and send from there
        Livewire::actingAs($user)->test(Chat::class, ['contextUnit' => $pageUnit])
            ->set('messageText', 'pesan dari panel unit')
            ->call('sendMessage');

        // still the SAME conversation — no fragmented, unit-specific thread
        $this->assertSame($conversationCountAfterFullChat, Conversation::where('user_id', $user->id)->count());

        $conversation = Conversation::where('user_id', $user->id)->first();
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'content' => 'pesan pertama di chat penuh',
        ]);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'content' => 'pesan dari panel unit',
        ]);
    }

    public function test_rate_limit_still_applies_to_messages_sent_from_the_contextual_panel(): void
    {
        $this->seed(ExplorationSampleSeeder::class);
        $pageUnit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->first();

        config(['webi.daily_message_limit' => 1]);

        $user = $this->member();
        $this->fakeGeminiReply('balasan');

        // first message consumes the (artificially low) daily limit
        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'pesan pertama')
            ->call('sendMessage');

        // second message, sent from the contextual panel, must still be blocked
        Livewire::actingAs($user)->test(Chat::class, ['contextUnit' => $pageUnit])
            ->set('messageText', 'pesan kedua dari panel')
            ->call('sendMessage')
            ->assertSet('errorMessage', fn ($value) => ! empty($value));

        $this->assertDatabaseMissing('messages', ['content' => 'pesan kedua dari panel']);
    }

    public function test_contextual_panel_skips_proactive_greeting(): void
    {
        $this->seed(ExplorationSampleSeeder::class);
        $pageUnit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->first();

        // deliberately a fresh user WITHOUT the onboarding ProactiveLog
        // pre-marked, so the greeting would fire on a normal full-chat mount.
        $user = User::create([
            'name' => 'Baru', 'email' => 'baru@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member', 'membership_status' => 'active',
        ]);

        Livewire::actingAs($user)->test(Chat::class, ['contextUnit' => $pageUnit]);

        // checkAndDeliver() never ran at all for the contextual mount — more
        // precise than checking rendered text, since it directly proves the
        // proactive service was skipped, not just that its message string
        // happens not to appear.
        $this->assertDatabaseCount('proactive_logs', 0);
    }
}
