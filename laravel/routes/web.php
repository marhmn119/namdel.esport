<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/vote/{match}', [HomeController::class, 'vote'])->middleware('throttle:10,1')->name('vote');
Route::post('/register', [HomeController::class, 'register'])->middleware('throttle:5,1')->name('register');
Route::post('/sponsorship', [HomeController::class, 'sponsorship'])->middleware('throttle:3,60')->name('sponsorship');
Route::get('/api/leaderboard', [HomeController::class, 'leaderboard'])->name('leaderboard.api');
Route::get('/service-worker.js', [HomeController::class, 'serviceWorker'])->name('service-worker');
Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:5,1')->name('login.authenticate');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/admin/delete/{id}', [AdminController::class, 'deleteApplicant'])->middleware(EnsureAdmin::class);

Route::middleware(EnsureAdmin::class)->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/export', [AdminController::class, 'export'])->name('export');
    Route::post('/status/{id}', [AdminController::class, 'status'])->name('status');
    Route::delete('/applicants/{id}', [AdminController::class, 'deleteApplicant'])->name('applicants.delete');
    Route::post('/match/add', [AdminController::class, 'addMatch'])->name('match.add');
    Route::post('/match/edit/{id}', [AdminController::class, 'editMatch'])->name('match.edit');
    Route::post('/match/delete/{id}', [AdminController::class, 'deleteMatch'])->name('match.delete');
    Route::post('/roster/add', [AdminController::class, 'addRoster'])->name('roster.add');
    Route::post('/roster/edit/{id}', [AdminController::class, 'editRoster'])->name('roster.edit');
    Route::post('/roster/delete/{id}', [AdminController::class, 'deleteRoster'])->name('roster.delete');
    Route::post('/hero-stats/add', [AdminController::class, 'addHeroStat'])->name('hero.add');
    Route::post('/hero-stats/delete/{type}/{id}', [AdminController::class, 'deleteHeroStat'])->name('hero.delete');
    Route::post('/recap/add', [AdminController::class, 'addRecap'])->name('recap.add');
    Route::post('/recap/delete/{id}', [AdminController::class, 'deleteRecap'])->name('recap.delete');
    Route::post('/scrim/add', [AdminController::class, 'addScrim'])->name('scrim.add');
    Route::post('/scrim/delete/{id}', [AdminController::class, 'deleteScrim'])->name('scrim.delete');
    Route::post('/achievement/add', [AdminController::class, 'addAchievement'])->name('achievement.add');
    Route::post('/achievement/delete/{id}', [AdminController::class, 'deleteAchievement'])->name('achievement.delete');
    Route::post('/gallery/add', [AdminController::class, 'addGallery'])->name('gallery.add');
    Route::post('/gallery/delete/{id}', [AdminController::class, 'deleteGallery'])->name('gallery.delete');
    Route::post('/ticker/add', [AdminController::class, 'addTicker'])->name('ticker.add');
    Route::post('/ticker/delete/{id}', [AdminController::class, 'deleteTicker'])->name('ticker.delete');
    Route::post('/settings/update', [AdminController::class, 'updateSetting'])->name('settings.update');
});
