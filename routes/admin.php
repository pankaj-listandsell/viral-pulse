<?php

use App\Http\Controllers\Admin\AiGeneratorController;
use App\Http\Controllers\Admin\AiSettingsController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ScheduledPostController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TrendingTopicController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
|
| Registered from bootstrap/app.php with the "web" middleware, an /admin
| prefix and an "admin." name prefix.
|
*/

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    /*
     * Posts. The state-changing actions are POST rather than GET so none of
     * them can be triggered by a prefetch or an image tag.
     */
    Route::controller(PostController::class)->prefix('posts')->name('posts.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::post('bulk', 'bulk')->name('bulk');
        Route::get('{post}/edit', 'edit')->name('edit');
        Route::put('{post}', 'update')->name('update');
        Route::delete('{post}', 'destroy')->name('destroy');
        Route::post('{post}/duplicate', 'duplicate')->name('duplicate');
        Route::post('{post}/publish', 'publish')->name('publish');
        Route::post('{post}/unpublish', 'unpublish')->name('unpublish');
        Route::post('{post}/archive', 'archive')->name('archive');
        Route::post('{post}/schedule', 'schedule')->name('schedule');
        Route::post('{post}/restore', 'restore')->withTrashed()->name('restore');
        Route::delete('{post}/force', 'forceDelete')->withTrashed()->name('force-delete');
    });

    Route::resource('categories', CategoryController::class)->except('show');

    Route::controller(TagController::class)->prefix('tags')->name('tags.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('{tag}', 'update')->name('update');
        Route::delete('{tag}', 'destroy')->name('destroy');
    });

    Route::controller(MediaController::class)->prefix('media')->name('media.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('{media}', 'update')->name('update');
        Route::delete('{media}', 'destroy')->name('destroy');
    });

    Route::controller(AiGeneratorController::class)->prefix('ai')->name('ai.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware('throttle:ai-generation')->name('store');
        Route::post('settings', [AiSettingsController::class, 'update'])->name('settings');
        Route::get('{generation}', 'show')->name('show');
        Route::get('{generation}/status', 'status')->name('status');
        Route::post('{generation}/approve', 'approve')->name('approve');
        Route::post('{generation}/retry', 'retry')->middleware('throttle:ai-generation')->name('retry');
        Route::delete('{generation}', 'destroy')->name('destroy');
    });

    /*
     * Trending pipeline. Everything that spends money or changes state is a
     * POST, and the fetch button is throttled so it cannot be leaned on.
     */
    Route::controller(TrendingTopicController::class)->prefix('trending')->name('trending.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('fetch', 'fetch')->middleware('throttle:6,1')->name('fetch');
        Route::post('run', 'runBatch')->middleware('throttle:ai-generation')->name('run');
        Route::post('{topic}/generate', 'generate')->middleware('throttle:ai-generation')->name('generate');
        Route::post('{topic}/ignore', 'ignore')->name('ignore');
        Route::post('{topic}/restore', 'restore')->name('restore');
        Route::delete('{topic}', 'destroy')->name('destroy');
    });

    Route::controller(ScheduledPostController::class)->prefix('scheduled')->name('scheduled.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('{scheduled}/publish', 'publishNow')->name('publish');
        Route::post('{scheduled}/cancel', 'cancel')->name('cancel');
    });

    Route::controller(UserController::class)->prefix('users')->name('users.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('{user}/toggle-active', 'toggleActive')->name('toggle-active');
        Route::delete('{user}', 'destroy')->name('destroy');
    });
});
