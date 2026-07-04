<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Eksekusi\AttachmentDownloadController;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Users\Create as UsersCreate;
use App\Livewire\Admin\Users\Edit as UsersEdit;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Admin\Webi\Index as AdminWebiIndex;
use App\Livewire\Admin\Webi\Show as AdminWebiShow;
use App\Livewire\Auth\Login;
use App\Livewire\Eksekusi\Ideas\Approve as IdeasApprove;
use App\Livewire\Eksekusi\Ideas\Create as IdeasCreate;
use App\Livewire\Eksekusi\Ideas\Index as IdeasIndex;
use App\Livewire\Eksekusi\Projects\Board;
use App\Livewire\Eksekusi\Projects\Create as ProjectsCreate;
use App\Livewire\Eksekusi\Projects\Index as ProjectsIndex;
use App\Livewire\Eksekusi\Projects\Show as ProjectsShow;
use App\Livewire\Eksekusi\Tasks\Create as TasksCreate;
use App\Livewire\Eksekusi\Tasks\Show as TasksShow;
use App\Livewire\Eksplorasi\CheckpointShow;
use App\Livewire\Eksplorasi\Dashboard as EksplorasiDashboard;
use App\Livewire\Eksplorasi\Forum\Create as ForumCreate;
use App\Livewire\Eksplorasi\Forum\Index as ForumIndex;
use App\Livewire\Eksplorasi\Forum\Show as ForumShow;
use App\Livewire\Eksplorasi\PetaKurikulum;
use App\Livewire\Eksplorasi\Resources\Index as ResourcesIndex;
use App\Livewire\Eksplorasi\UnitShow;
use App\Livewire\Eksplorasi\Webi\Chat as WebiChat;
use App\Livewire\Notifications\Index as NotificationsIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', Login::class)->middleware('guest')->name('login');
Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::get('/dashboard', function () {
    return redirect(auth()->user()->dashboardPath());
})->middleware('auth')->name('dashboard');

Route::get('/notifications', NotificationsIndex::class)
    ->middleware('auth')
    ->name('notifications.index');

Route::get('/admin/dashboard', AdminDashboard::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin.dashboard');

Route::get('/eksplorasi/dashboard', EksplorasiDashboard::class)
    ->middleware(['auth', 'role:exploration_member'])
    ->name('eksplorasi.dashboard');

Route::middleware(['auth', 'role:exploration_member'])->prefix('eksplorasi')->name('eksplorasi.')->group(function () {
    Route::get('/kurikulum', PetaKurikulum::class)->name('kurikulum');
    Route::get('/unit/{unit}', UnitShow::class)->name('unit.show');
    Route::get('/checkpoint/{checkpoint}', CheckpointShow::class)->name('checkpoint.show');
    Route::get('/resources', ResourcesIndex::class)->name('resources');
    Route::get('/webi', WebiChat::class)->name('webi');
});

Route::middleware(['auth', 'role:exploration_member,admin'])->prefix('eksplorasi/forum')->name('eksplorasi.forum.')->group(function () {
    Route::get('/', ForumIndex::class)->name('index');
    Route::get('/create', ForumCreate::class)->name('create');
    Route::get('/{thread}', ForumShow::class)->name('show');
});

Route::get('/eksekusi/dashboard', function () {
    return view('eksekusi.dashboard');
})->middleware(['auth', 'role:execution_member'])->name('eksekusi.dashboard');

Route::middleware(['auth', 'role:execution_member,admin'])->prefix('eksekusi/ideas')->name('eksekusi.ideas.')->group(function () {
    Route::get('/', IdeasIndex::class)->name('index');
    Route::get('/create', IdeasCreate::class)->name('create');
    Route::get('/{idea}/approve', IdeasApprove::class)->name('approve');
});

Route::middleware(['auth', 'role:execution_member,admin'])->prefix('eksekusi/projects')->name('eksekusi.projects.')->group(function () {
    Route::get('/', ProjectsIndex::class)->name('index');
    Route::get('/create', ProjectsCreate::class)->name('create');
    Route::get('/{project}', ProjectsShow::class)->name('show');
    Route::get('/{project}/board', Board::class)->name('board');
    Route::get('/{project}/tasks/create', TasksCreate::class)->name('tasks.create');
});

Route::middleware(['auth', 'role:execution_member,admin'])->prefix('eksekusi/tasks')->name('eksekusi.tasks.')->group(function () {
    Route::get('/{task}', TasksShow::class)->name('show');
});

Route::get('/attachments/{attachment}/download', AttachmentDownloadController::class)
    ->middleware(['auth', 'role:execution_member,admin'])
    ->name('attachments.download');

Route::middleware(['auth', 'role:admin'])->prefix('admin/users')->name('admin.users.')->group(function () {
    Route::get('/', UsersIndex::class)->name('index');
    Route::get('/create', UsersCreate::class)->name('create');
    Route::get('/{user}/edit', UsersEdit::class)->name('edit');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/webi')->name('admin.webi.')->group(function () {
    Route::get('/', AdminWebiIndex::class)->name('index');
    Route::get('/{user}', AdminWebiShow::class)->name('show');
});
