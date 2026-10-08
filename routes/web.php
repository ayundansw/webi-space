<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DualMode\SwitchModeController;
use App\Http\Controllers\Eksekusi\AttachmentDownloadController;
use App\Http\Controllers\Eksplorasi\ChallengeAttachmentDownloadController;
use App\Http\Controllers\Eksplorasi\ChallengeSubmissionDownloadController;
use App\Livewire\Admin\Curriculum\Challenges\Create as CurriculumChallengesCreate;
use App\Livewire\Admin\Curriculum\Challenges\Edit as CurriculumChallengesEdit;
use App\Livewire\Admin\Curriculum\Challenges\Index as CurriculumChallengesIndex;
use App\Livewire\Admin\Curriculum\Challenges\Steps\Create as CurriculumChallengeStepsCreate;
use App\Livewire\Admin\Curriculum\Challenges\Steps\Edit as CurriculumChallengeStepsEdit;
use App\Livewire\Admin\Curriculum\Challenges\Steps\Index as CurriculumChallengeStepsIndex;
use App\Livewire\Admin\Curriculum\Modules\Create as CurriculumModulesCreate;
use App\Livewire\Admin\Curriculum\Modules\Edit as CurriculumModulesEdit;
use App\Livewire\Admin\Curriculum\Modules\Index as CurriculumModulesIndex;
use App\Livewire\Admin\Curriculum\Submissions\Index as CurriculumSubmissionsIndex;
use App\Livewire\Admin\Curriculum\Units\ContentEditor as CurriculumUnitsContentEditor;
use App\Livewire\Admin\Curriculum\Units\ContentPreview as CurriculumUnitsContentPreview;
use App\Livewire\Admin\Curriculum\Units\Create as CurriculumUnitsCreate;
use App\Livewire\Admin\Curriculum\Units\Edit as CurriculumUnitsEdit;
use App\Livewire\Admin\Curriculum\Units\Evaluations\Create as CurriculumUnitsEvaluationsCreate;
use App\Livewire\Admin\Curriculum\Units\Evaluations\Edit as CurriculumUnitsEvaluationsEdit;
use App\Livewire\Admin\Curriculum\Units\Evaluations\Index as CurriculumUnitsEvaluationsIndex;
use App\Livewire\Admin\Curriculum\Units\Index as CurriculumUnitsIndex;
use App\Livewire\Admin\DualMode\Index as AdminDualModeIndex;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Users\Create as UsersCreate;
use App\Livewire\Admin\Users\Edit as UsersEdit;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Admin\Webi\Index as AdminWebiIndex;
use App\Livewire\Admin\Webi\Show as AdminWebiShow;
use App\Livewire\Auth\Login;
use App\Livewire\Eksekusi\AvatarPicker;
use App\Livewire\Eksekusi\Dashboard as EksekusiDashboard;
use App\Livewire\Eksekusi\Forum\Create as EksekusiForumCreate;
use App\Livewire\Eksekusi\Forum\Index as EksekusiForumIndex;
use App\Livewire\Eksekusi\Forum\Show as EksekusiForumShow;
use App\Livewire\Eksekusi\Kalender as EksekusiKalender;
use App\Livewire\Eksekusi\Ideas\Create as IdeasCreate;
use App\Livewire\Eksekusi\Ideas\Index as IdeasIndex;
use App\Livewire\Eksekusi\Praktik\Review as EksekusiPraktikReview;
use App\Livewire\Eksekusi\Projects\Board;
use App\Livewire\Eksekusi\Projects\Create as ProjectsCreate;
use App\Livewire\Eksekusi\Projects\Index as ProjectsIndex;
use App\Livewire\Eksekusi\Projects\Tabs\Anggota as ProjectsAnggota;
use App\Livewire\Eksekusi\Projects\Tabs\Forum as ProjectsForum;
use App\Livewire\Eksekusi\Projects\Tabs\Gantt as ProjectsGantt;
use App\Livewire\Eksekusi\Projects\Tabs\Kalender as ProjectsKalender;
use App\Livewire\Eksekusi\Projects\Tabs\Roadmap as ProjectsRoadmap;
use App\Livewire\Eksekusi\Tasks\Create as TasksCreate;
use App\Livewire\Eksplorasi\CheckpointShow;
use App\Livewire\Eksplorasi\Dashboard as EksplorasiDashboard;
use App\Livewire\Eksplorasi\Forum\Create as ForumCreate;
use App\Livewire\Eksplorasi\Forum\Index as ForumIndex;
use App\Livewire\Eksplorasi\Forum\Show as ForumShow;
use App\Livewire\Eksplorasi\PetaKurikulum;
use App\Livewire\Eksplorasi\Praktik\Index as PraktikIndex;
use App\Livewire\Eksplorasi\Praktik\Show as PraktikShow;
use App\Livewire\Eksplorasi\Resources\Index as ResourcesIndex;
use App\Livewire\Eksplorasi\UnitShow;
use App\Livewire\Eksplorasi\Webi\Chat as WebiChat;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Profile\Edit as ProfileEdit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', Login::class)->middleware('guest')->name('login');
Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::get('/dashboard', function () {
    return redirect(auth()->user()->dashboardPath());
})->middleware('auth')->name('dashboard');

// Fase 8 Batch 6: member-facing toggle for active_mode (account-menu
// dropdown) — see SwitchModeController docblock for scope (exploration_member
// only; execution_member's side is pure navigation, no route needed here).
Route::post('/mode/switch', SwitchModeController::class)->middleware('auth')->name('mode.switch');

Route::get('/notifications', NotificationsIndex::class)
    ->middleware('auth')
    ->name('notifications.index');

Route::get('/profile', ProfileEdit::class)
    ->middleware('auth')
    ->name('profile.edit');

Route::get('/admin/dashboard', AdminDashboard::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin.dashboard');

Route::get('/eksplorasi/dashboard', EksplorasiDashboard::class)
    ->middleware(['auth', 'role:exploration_member'])
    ->name('eksplorasi.dashboard');

// Fase 8 Batch 3: `role:exploration_member` swapped to `mode:exploration`
// on the 5 read-safe routes only (RECON_fase8_mode_ganda.md poin 3) —
// admits both real exploration_member AND any execution_member (free
// read-only access, no approval, §2.2.A). Write actions nested inside
// these same routes (UnitEvaluation's submit*/markAsRead, CheckpointShow::submit(),
// Resources\Index::submit()) are separately guarded by
// User::isReadOnlyExploration() inside each method itself — this
// middleware only ever decides "can this user open the page".
Route::middleware(['auth', 'mode:exploration'])->prefix('eksplorasi')->name('eksplorasi.')->group(function () {
    Route::get('/kurikulum', PetaKurikulum::class)->name('kurikulum');
    Route::get('/unit/{unit}', UnitShow::class)->name('unit.show');
    Route::get('/checkpoint/{checkpoint}', CheckpointShow::class)->name('checkpoint.show');
    Route::get('/resources', ResourcesIndex::class)->name('resources');
    Route::get('/webi/{conversation?}', WebiChat::class)->name('webi');
});

// Praktik is deliberately NOT opened to read-only mode — recon flagged it
// as "tidak disebut dokumen", and this batch's prompt doesn't list it
// among the routes to extend either. Stays real exploration_member only.
Route::middleware(['auth', 'role:exploration_member'])->prefix('eksplorasi')->name('eksplorasi.')->group(function () {
    Route::get('/praktik', PraktikIndex::class)->name('praktik.index');
    Route::get('/praktik/{challenge}', PraktikShow::class)->name('praktik.show');
});

// /create deliberately stays role:exploration_member,admin ONLY (not
// opened) — it's a pure-write page (no readable content of its own), so
// there's nothing for read-only mode to gain from reaching it directly.
// It's still reachable INDIRECTLY though, embedded as a modal inside
// Forum\Index (forum/index.blade.php) — which IS now open to read-only
// users — so Forum\Create::save() itself is ALSO guarded (see that
// class), not just this route. Registered BEFORE the `/{thread}` wildcard
// group below — Laravel matches routes in registration order, so
// `/create` must come first or the literal string "create" gets bound as
// a {thread} id instead (404, since it's not a valid uuid).
Route::middleware(['auth', 'role:exploration_member,admin'])->prefix('eksplorasi/forum')->name('eksplorasi.forum.')->group(function () {
    Route::get('/create', ForumCreate::class)->name('create');
});

Route::middleware(['auth', 'mode:exploration,admin'])->prefix('eksplorasi/forum')->name('eksplorasi.forum.')->group(function () {
    Route::get('/', ForumIndex::class)->name('index');
    Route::get('/{thread}', ForumShow::class)->name('show');
});

// Fase 8 Batch 2: `role:execution_member` swapped to `mode:execution` —
// admits both native execution_member origin AND an approved, active-mode
// exploration_member (User::canAccessExecution()). Still deliberately NOT
// admin-inclusive (unchanged from before this batch).
Route::get('/eksekusi/dashboard', EksekusiDashboard::class)
    ->middleware(['auth', 'mode:execution'])
    ->name('eksekusi.dashboard');

Route::get('/eksekusi/avatar', AvatarPicker::class)
    ->middleware(['auth', 'mode:execution'])
    ->name('eksekusi.avatar');

// Fase 7 Batch 2b: execution_member only (not admin), same restriction as
// eksekusi.dashboard above -- "proyek yang diikuti user" (ProjectMember)
// doesn't map onto admin's app-wide access the same way.
Route::get('/eksekusi/kalender', EksekusiKalender::class)
    ->middleware(['auth', 'mode:execution'])
    ->name('eksekusi.kalender');

// Forum General Eksekusi ("utang Fase 7 Batch 4") — lintas-proyek
// (portal='execution', project_id null), BEDA dari Forum Proyek
// (eksekusi.projects.forum tab, di atas project group). `mode:execution,admin`
// konsisten dengan rute Eksekusi lain sejak Fase 8 Batch 2 (bukan
// `role:execution_member,admin` literal) — member Mode Ganda otomatis ikut.
// `/create` didaftarkan SEBELUM grup `/{thread}` di bawahnya -- trap
// urutan route wildcard-vs-literal yang sama seperti Fase 8 Batch 3's
// `/eksplorasi/forum/create`, kalau dibalik "create" akan ketangkap
// sebagai {thread} id (404, bukan uuid valid).
Route::middleware(['auth', 'mode:execution,admin'])->prefix('eksekusi/forum')->name('eksekusi.forum.')->group(function () {
    Route::get('/create', EksekusiForumCreate::class)->name('create');
});

Route::middleware(['auth', 'mode:execution,admin'])->prefix('eksekusi/forum')->name('eksekusi.forum.')->group(function () {
    Route::get('/', EksekusiForumIndex::class)->name('index');
    Route::get('/{thread}', EksekusiForumShow::class)->name('show');
});

// Fase 8 Batch 2: `role:execution_member,admin` swapped to `mode:execution,admin`
// — admin access preserved exactly as before (separate literal check
// inside the middleware, not folded into canAccessExecution()).
Route::middleware(['auth', 'mode:execution,admin'])->prefix('eksekusi/ideas')->name('eksekusi.ideas.')->group(function () {
    Route::get('/', IdeasIndex::class)->name('index');
    Route::get('/create', IdeasCreate::class)->name('create');
});

Route::middleware(['auth', 'mode:execution,admin'])->prefix('eksekusi/projects')->name('eksekusi.projects.')->group(function () {
    Route::get('/', ProjectsIndex::class)->name('index');
    Route::get('/create', ProjectsCreate::class)->name('create');

    // Fase 7 Batch 1a: every project-scoped tab lives behind ONE
    // `project.member` middleware application (see EnsureProjectMembership)
    // instead of each component repeating the same abort_if() in mount().
    // Kanban is the default tab — reachable at both the bare project URL
    // (kept for every existing link across the app that already points at
    // it, e.g. Admin\Dashboard, Profile, Project Ideas cards — none of them
    // needed to change) AND the explicit /kanban suffix used by the tab bar.
    Route::middleware('project.member')->prefix('/{project}')->group(function () {
        Route::get('/', Board::class)->name('show');
        Route::get('/kanban', Board::class)->name('kanban');
        Route::get('/roadmap', ProjectsRoadmap::class)->name('roadmap');
        Route::get('/gantt', ProjectsGantt::class)->name('gantt');
        Route::get('/kalender', ProjectsKalender::class)->name('kalender');
        Route::get('/forum', ProjectsForum::class)->name('forum');
        Route::get('/anggota', ProjectsAnggota::class)->name('anggota');
        Route::get('/tasks/create', TasksCreate::class)->name('tasks.create');
    });
});

// Fase 7 Batch 1b: Detail Task is a slide-over panel on the project's
// Kanban tab now, not its own page — but this exact URL is still built by
// Notification::linkUrl() and Tasks\Create's post-save redirect (both use
// the raw path, not route()), so it keeps working, redirecting to the
// project's Kanban tab with ?task= to auto-open the panel there (Board::mount()
// reads that query param). `project.member` still guards this route BEFORE
// the redirect fires, so a non-member gets 403 here rather than being
// bounced toward a project they can't see anyway (that destination would
// 403 them too, but failing fast here is more direct).
Route::middleware(['auth', 'mode:execution,admin', 'project.member'])->prefix('eksekusi/tasks')->name('eksekusi.tasks.')->group(function () {
    Route::get('/{task}', function (\App\Models\Task $task) {
        // Bypasses the redirect()/Redirect:: helpers on purpose — during an
        // actual HTTP request (not console/tinker), Livewire's
        // SupportRedirects feature rebinds the container's 'redirect'
        // singleton to its own Redirector, which isn't a valid Response
        // when returned directly from a plain route closure (only from
        // inside a Livewire component action, where Livewire's own
        // pipeline knows how to unwrap it). Constructing the response
        // class directly sidesteps that entirely.
        return new \Illuminate\Http\RedirectResponse(route('eksekusi.projects.kanban', $task->project).'?task='.$task->id);
    })->name('show');
});

// Shared by an assigned execution_member reviewer AND admin (PIC
// self-review) — the component's own mount() owns the fine-grained "is this
// YOUR assignment" check, this middleware only narrows the role.
Route::middleware(['auth', 'mode:execution,admin'])->prefix('eksekusi/praktik')->name('eksekusi.praktik.')->group(function () {
    Route::get('/submissions/{submission}', EksekusiPraktikReview::class)->name('submissions.show');
});

Route::get('/attachments/{attachment}/download', AttachmentDownloadController::class)
    ->middleware(['auth', 'mode:execution,admin'])
    ->name('attachments.download');

// No role: restriction here (unlike attachments.download above) — a
// legitimate accessor can be admin, an execution_member reviewer, OR the
// exploration_member owner, i.e. potentially any of the 3 roles. The
// controller itself owns the full RBAC decision.
Route::get('/challenge-submissions/{submission}/download', ChallengeSubmissionDownloadController::class)
    ->middleware(['auth'])
    ->name('challenge-submissions.download');

// Not role-restricted either — reference material, visible to any
// authenticated user once its challenge is published (controller enforces
// the draft-stays-admin-only rule).
Route::get('/challenge-attachments/{attachment}/download', ChallengeAttachmentDownloadController::class)
    ->middleware(['auth'])
    ->name('challenge-attachments.download');

Route::middleware(['auth', 'role:admin'])->prefix('admin/users')->name('admin.users.')->group(function () {
    Route::get('/', UsersIndex::class)->name('index');
    Route::get('/create', UsersCreate::class)->name('create');
    Route::get('/{user}/edit', UsersEdit::class)->name('edit');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/webi')->name('admin.webi.')->group(function () {
    Route::get('/', AdminWebiIndex::class)->name('index');
    Route::get('/{user}', AdminWebiShow::class)->name('show');
});

// Fase 8 Batch 5 (§5.3 "Antrian Permintaan Mode Eksekusi").
Route::middleware(['auth', 'role:admin'])->prefix('admin/dual-mode')->name('admin.dual-mode.')->group(function () {
    Route::get('/', AdminDualModeIndex::class)->name('index');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/curriculum')->name('admin.curriculum.')->group(function () {
    Route::get('/modules', CurriculumModulesIndex::class)->name('modules.index');
    Route::get('/modules/create', CurriculumModulesCreate::class)->name('modules.create');
    Route::get('/modules/{module}/edit', CurriculumModulesEdit::class)->name('modules.edit');

    Route::get('/units', CurriculumUnitsIndex::class)->name('units.index');
    Route::get('/units/create', CurriculumUnitsCreate::class)->name('units.create');
    Route::get('/units/{unit}/edit', CurriculumUnitsEdit::class)->name('units.edit');
    Route::get('/units/{unit}/content', CurriculumUnitsContentEditor::class)->name('units.content');
    Route::get('/units/{unit}/content/preview', CurriculumUnitsContentPreview::class)->name('units.content-preview');

    Route::get('/units/{unit}/evaluations', CurriculumUnitsEvaluationsIndex::class)->name('units.evaluations.index');
    Route::get('/units/{unit}/evaluations/create', CurriculumUnitsEvaluationsCreate::class)->name('units.evaluations.create');
    Route::get('/units/{unit}/evaluations/{evaluation}/edit', CurriculumUnitsEvaluationsEdit::class)->name('units.evaluations.edit');

    Route::get('/challenges', CurriculumChallengesIndex::class)->name('challenges.index');
    Route::get('/challenges/create', CurriculumChallengesCreate::class)->name('challenges.create');
    Route::get('/challenges/{challenge}/edit', CurriculumChallengesEdit::class)->name('challenges.edit');

    Route::get('/challenges/{challenge}/steps', CurriculumChallengeStepsIndex::class)->name('challenges.steps.index');
    Route::get('/challenges/{challenge}/steps/create', CurriculumChallengeStepsCreate::class)->name('challenges.steps.create');
    Route::get('/challenges/{challenge}/steps/{step}/edit', CurriculumChallengeStepsEdit::class)->name('challenges.steps.edit');
    // Praktik 1: same generalized components as Unit's content editor/preview
    // (App\Livewire\Admin\Curriculum\Units\ContentEditor/ContentPreview) —
    // not a separate implementation, see those classes' docblocks.
    Route::get('/challenges/{challenge}/steps/{step}/content', CurriculumUnitsContentEditor::class)->name('challenges.steps.content');
    Route::get('/challenges/{challenge}/steps/{step}/content/preview', CurriculumUnitsContentPreview::class)->name('challenges.steps.content-preview');

    Route::get('/submissions', CurriculumSubmissionsIndex::class)->name('submissions.index');
});

// 2.2.4a (Konten Dinamis, fondasi): dev-only visual check for all 9 content
// block types (docs/v_2.0/content-blocks-spec.md). Wrapped in an environment
// check rather than just a role check so it never exists at all in
// production, per the task's "jangan masuk nav produksi" constraint — not
// linked from any sidebar/nav, and blocks are built in-memory (never
// persisted) so visiting this page can't pollute the database.
if (app()->environment('local')) {
    Route::get('/dev/content-blocks-preview', function () {
        $blocks = collect([
            new \App\Models\ContentBlock(['type' => 'heading', 'content' => ['level' => 1, 'text' => 'Contoh Heading Level 1']]),
            new \App\Models\ContentBlock(['type' => 'heading', 'content' => ['level' => 2, 'text' => 'Contoh Heading Level 2']]),
            new \App\Models\ContentBlock(['type' => 'heading', 'content' => ['level' => 3, 'text' => 'Contoh Heading Level 3']]),
            new \App\Models\ContentBlock(['type' => 'text', 'content' => ['markdown' => "Ini contoh blok **teks** biasa dengan *italic* dan [link](https://example.com). Paragraf kedua untuk contoh jarak antar paragraf."]]),
            new \App\Models\ContentBlock(['type' => 'callout', 'content' => ['variant' => 'info', 'title' => 'Info', 'body' => 'Ini contoh callout **info**.']]),
            new \App\Models\ContentBlock(['type' => 'callout', 'content' => ['variant' => 'tip', 'title' => 'Tips', 'body' => 'Ini contoh callout tip.']]),
            new \App\Models\ContentBlock(['type' => 'callout', 'content' => ['variant' => 'warning', 'title' => 'Peringatan', 'body' => 'Ini contoh callout warning.']]),
            new \App\Models\ContentBlock(['type' => 'code', 'content' => ['language' => 'bash', 'code' => "mkdir latihan_pertama\ncd latihan_pertama\npwd"]]),
            new \App\Models\ContentBlock(['type' => 'image', 'content' => ['url' => 'https://placehold.co/640x320', 'alt' => 'Contoh gambar placeholder', 'caption' => 'Contoh caption gambar']]),
            new \App\Models\ContentBlock(['type' => 'video', 'content' => ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'caption' => 'Contoh video YouTube (auto-embed dari url)']]),
            new \App\Models\ContentBlock(['type' => 'video', 'content' => ['url' => 'https://example.com/video-tidak-dikenal', 'caption' => 'Provider tidak dikenal -> jatuh ke tautan biasa']]),
            new \App\Models\ContentBlock(['type' => 'list', 'content' => ['style' => 'unordered', 'items' => ['Item pertama', 'Item **kedua** dengan bold', 'Item ketiga dengan [link](https://example.com)']]]),
            new \App\Models\ContentBlock(['type' => 'list', 'content' => ['style' => 'ordered', 'items' => ['Langkah pertama', 'Langkah kedua', 'Langkah ketiga']]]),
            new \App\Models\ContentBlock(['type' => 'table', 'content' => ['headers' => ['Perintah', 'Fungsi'], 'rows' => [['pwd', 'Menampilkan lokasi folder'], ['ls', 'Menampilkan isi folder']]]]),
            new \App\Models\ContentBlock(['type' => 'custom_html', 'content' => ['html' => '<div class="rounded-lg border border-muted/25 p-3 text-sm">Custom HTML aman. <script>alert(1)</script><img src=x onerror="alert(2)"> — script &amp; onerror di atas SENGAJA disisipkan, seharusnya hilang total dari HTML akhir (bukti sanitasi).</div>']]),
        ])->values();

        return view('dev.content-blocks-preview', ['blocks' => $blocks]);
    })->middleware('auth')->name('dev.content-blocks-preview');

    // Fase 2 Langkah 3 (Maskot Boxy Blocky): halaman sementara buat Aye
    // verifikasi visual proporsi/warna SVG sebelum langkah ini dianggap
    // selesai. SENGAJA disposable -- dihapus lagi begitu verifikasi kelar,
    // sama seperti dev/content-blocks-preview di atas. Referensi PNG ada di
    // resources/images/ (bukan public/), jadi di-inline sebagai data URI
    // langsung dari closure ini alih-alih bikin route/copy file baru cuma
    // buat satu gambar sekali-lihat.
    Route::get('/dev/mascot-preview', function () {
        $path = resource_path('images/mascot/Webi Maskot Ikon.png');
        $referenceImage = 'data:image/png;base64,'.base64_encode(file_get_contents($path));

        return view('dev.mascot-preview', ['referenceImage' => $referenceImage]);
    })->middleware('auth')->name('dev.mascot-preview');

    // Fase 2 Langkah 4 (Avatar Fox + Hewan Eksekusi): sama polanya persis
    // dengan dev/mascot-preview di atas -- disposable, referensi PNG
    // di-inline sebagai data URI dari resources/images/avatars/.
    Route::get('/dev/avatar-preview', function () {
        $toDataUri = fn (string $path) => 'data:image/png;base64,'.base64_encode(file_get_contents($path));

        $foxReferences = collect(range(1, 5))->mapWithKeys(
            fn (int $tingkat) => [$tingkat => $toDataUri(resource_path("images/avatars/eksplorasi/Eksplor_Level {$tingkat}.png"))]
        );

        $eksekusiFiles = [
            'elang' => 'Eksekusi_Elang.png',
            'serigala' => 'Eksekusi_Serigala.png',
            'singa' => 'Eksekusi_Singa.png',
            'harimau' => 'Eksekusi_Harimau.png',
            'cheetah' => 'Eksekusi_Cheetah.png',
        ];
        $eksekusiReferences = collect($eksekusiFiles)->map(
            fn (string $file) => $toDataUri(resource_path("images/avatars/eksekusi/{$file}"))
        );

        return view('dev.avatar-preview', [
            'foxReferences' => $foxReferences,
            'eksekusiReferences' => $eksekusiReferences,
        ]);
    })->middleware('auth')->name('dev.avatar-preview');

    // Fase 2 Langkah 5: navbar + popup menu + breadcrumb baru, untuk 3 role
    // sekaligus supaya Aye bisa bandingkan berdampingan. Beda dari 3 dev
    // preview di atas (yang murni in-memory) -- ini SATU-SATUNYA yang perlu
    // 3 akun dev nyata (bukan in-memory), sengaja idempotent (firstOrCreate
    // keyed by email tetap, bukan create baru tiap kunjungan) supaya
    // <x-shell.account-menu> bisa dites lewat jalur data ASLI (avatar Fox
    // butuh baris UserExplorationProgress sungguhan lewat ProgressService,
    // bukan user palsu yang tidak pernah tersimpan -- akan gagal foreign
    // key kalau dipaksa in-memory). Auth session pengunjung SELALU
    // dikembalikan persis semula di akhir (try/finally), termasuk kalau
    // ada exception di tengah render -- halaman ini tidak boleh
    // "membajak" sesi Aye sendiri jadi salah satu akun dev.
    Route::get('/dev/shell-preview', function () {
        $originalUser = Auth::user();
        $originalRoute = request()->route();

        $roles = [
            'admin' => ['name' => 'Admin (Preview)', 'route' => 'admin.users.index'],
            'exploration_member' => ['name' => 'Anggota Eksplorasi (Preview)', 'route' => 'eksplorasi.kurikulum'],
            'execution_member' => ['name' => 'Anggota Eksekusi (Preview)', 'route' => 'eksekusi.projects.index'],
        ];

        $panels = [];

        try {
            foreach ($roles as $role => $config) {
                $user = User::firstOrCreate(
                    ['email' => "dev-preview-{$role}@example.test"],
                    [
                        'name' => $config['name'],
                        'password_hash' => bcrypt(\Illuminate\Support\Str::random(32)),
                        'role' => $role,
                        'membership_status' => 'active',
                    ]
                );

                Auth::login($user);
                request()->setRouteResolver(fn () => app('router')->getRoutes()->getByName($config['route']));

                $panels[$role] = [
                    'user' => $user,
                    'navbarHtml' => (string) view('components.shell.navbar'),
                    'breadcrumbHtml' => (string) view('components.shell.breadcrumb'),
                ];
            }
        } finally {
            // NOT setRouteResolver(null) -- that method type-hints \Closure
            // (non-nullable), passing null throws a TypeError that would
            // skip the Auth::login() restore right below it. Restore the
            // REAL bound route this request actually matched instead.
            request()->setRouteResolver(fn () => $originalRoute);

            if ($originalUser) {
                Auth::login($originalUser);
            } else {
                Auth::logout();
            }
        }

        return view('dev.shell-preview', ['panels' => $panels]);
    })->middleware('auth')->name('dev.shell-preview');
}
