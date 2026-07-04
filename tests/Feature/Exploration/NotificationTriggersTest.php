<?php

namespace Tests\Feature\Exploration;

use App\Models\Checkpoint;
use App\Models\ForumThread;
use App\Models\Module;
use App\Models\Notification;
use App\Models\Unit;
use App\Models\User;
use App\Services\Exploration\ProgressService;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Task 2.6 Batch 3: Eksplorasi triggers from PRD 3.1.8 now write to the SAME
 * `notifications` table (App\Models\Notification, morphTo context) that
 * Eksekusi has used since 2.4 — previously this table only ever received
 * Eksekusi rows, even though the schema/enum from 2.0 already reserved the
 * Eksplorasi-side types and context kinds.
 */
class NotificationTriggersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
    }

    private function member(string $name = 'Member'): User
    {
        return User::create([
            'name' => $name, 'email' => strtolower($name).'@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
    }

    public function test_completing_a_unit_notifies_about_the_newly_unlocked_next_unit(): void
    {
        $user = $this->member();
        $service = $this->app->make(ProgressService::class);
        $moduleA = Module::where('order_number', 1)->first();
        $firstUnit = $moduleA->units()->where('order_number', 1)->first();
        $secondUnit = $moduleA->units()->where('order_number', 2)->first();

        $service->completeUnit($user, $firstUnit);

        $notification = Notification::where('recipient_id', $user->id)->where('type', 'new_unit_unlocked')->first();

        $this->assertNotNull($notification);
        $this->assertSame('unit', $notification->context_type);
        $this->assertSame($secondUnit->id, $notification->context_id);
        $this->assertStringContainsString($secondUnit->title, $notification->message);
    }

    public function test_completing_the_modules_last_unit_does_not_send_a_stray_notification_beyond_the_real_chain(): void
    {
        $user = $this->member();
        $service = $this->app->make(ProgressService::class);
        $moduleA = Module::where('order_number', 1)->first();
        $units = $moduleA->units()->orderBy('order_number')->get();

        // no seeded unit declares module A's last unit as its prerequisite, so
        // completing it must add zero further new_unit_unlocked notifications
        // beyond the 3 real a1->a2, a2->a3, a3->a4 transitions above it.
        foreach ($units as $unit) {
            $service->completeUnit($user, $unit);
        }

        $this->assertSame(
            $units->count() - 1,
            Notification::where('recipient_id', $user->id)->where('type', 'new_unit_unlocked')->count(),
        );
    }

    public function test_reaching_a_new_level_sends_a_level_up_notification(): void
    {
        $user = $this->member();
        $service = $this->app->make(ProgressService::class);

        $this->assertSame(1, $user->fresh()->explorationProgress?->current_level ?? 1);

        // config('exploration.level_thresholds')[2] === 150 — award enough points to cross it in one call
        $service->awardPoints($user, 150);

        $notification = Notification::where('recipient_id', $user->id)->where('type', 'level_up')->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Level 2', $notification->message);

        // a second, smaller award that does NOT cross another threshold must not notify again
        $service->awardPoints($user, 5);
        $this->assertSame(1, Notification::where('recipient_id', $user->id)->where('type', 'level_up')->count());
    }

    public function test_completing_a_checkpoint_sends_a_checkpoint_completed_notification(): void
    {
        $user = $this->member();
        $service = $this->app->make(ProgressService::class);
        $moduleA = Module::where('order_number', 1)->first();
        $checkpoint = Checkpoint::where('module_id', $moduleA->id)->first();

        foreach ($moduleA->units()->get() as $unit) {
            $service->completeUnit($user, $unit);
        }

        $service->completeCheckpoint($user, $checkpoint, [
            'checklist_answers' => [], 'intermezo_answers' => ['Jawaban'], 'form_tanggapan' => 'Tanggapan',
        ]);

        $notification = Notification::where('recipient_id', $user->id)->where('type', 'checkpoint_completed')->first();
        $this->assertNotNull($notification);
        $this->assertSame('checkpoint', $notification->context_type);
        $this->assertSame($checkpoint->id, $notification->context_id);
    }

    public function test_forum_reply_notifies_the_thread_creator_but_not_the_replier_themselves(): void
    {
        $creator = $this->member('Agitsa');
        $replier = $this->member('Bilal');
        $module = Module::where('order_number', 1)->first();

        $thread = ForumThread::create([
            'module_id' => $module->id, 'created_by' => $creator->id,
            'title' => 'Pertanyaan', 'content' => 'Isi pertanyaan', 'target' => 'peer',
        ]);

        Livewire::actingAs($replier)->test(\App\Livewire\Eksplorasi\Forum\Show::class, ['thread' => $thread])
            ->set('replyContent', 'Coba dilihat lagi ya')
            ->call('reply');

        $notification = Notification::where('recipient_id', $creator->id)->where('type', 'forum_reply_received')->first();
        $this->assertNotNull($notification);
        $this->assertSame('forum_thread', $notification->context_type);
        $this->assertSame($thread->id, $notification->context_id);

        // creator replying to their own thread must not self-notify
        Notification::query()->delete();
        Livewire::actingAs($creator)->test(\App\Livewire\Eksplorasi\Forum\Show::class, ['thread' => $thread])
            ->set('replyContent', 'Menambahkan konteks')
            ->call('reply');

        $this->assertSame(0, Notification::where('recipient_id', $creator->id)->where('type', 'forum_reply_received')->count());
    }
}
