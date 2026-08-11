<?php

use App\Http\Controllers\Public\ArchiveController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LikeController;
use App\Http\Controllers\Public\NewsletterController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PostController;
use App\Http\Controllers\Public\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
|
| Every page here is server-rendered Blade, so a crawler receives the complete
| article without running any JavaScript. Vue is mounted only onto interactive
| islands.
|
| Authentication is not in this file on purpose - it lives under /admin, and
| the public site never links to it.
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('latest', [ArchiveController::class, 'latest'])->name('latest');
Route::get('trending', [ArchiveController::class, 'trending'])->name('trending');

Route::get('categories', [ArchiveController::class, 'categories'])->name('categories.index');
Route::get('category/{category}', [ArchiveController::class, 'category'])->name('categories.show');
Route::get('tag/{tag}', [ArchiveController::class, 'tag'])->name('tags.show');

Route::get('search', SearchController::class)->middleware('throttle:search')->name('search');

Route::get('contact', [PageController::class, 'contact'])->name('contact');
Route::post('contact', [PageController::class, 'submitContact'])
    ->middleware('throttle:contact')
    ->name('contact.submit');

Route::get('sitemap', [PageController::class, 'sitemapPlaceholder'])->name('sitemap.page');

Route::post('newsletter', [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:newsletter')
    ->name('newsletter.subscribe');
Route::get('newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

Route::post('post/{post}/like', [LikeController::class, 'toggle'])
    ->middleware('throttle:60,1')
    ->name('posts.like');

// Static pages are matched last so they cannot shadow a real route.
Route::get('page/{page}', [PageController::class, 'show'])->name('pages.show');

// The article URL sits at the root of its own segment: short, stable and
// readable, which is what both people and crawlers prefer.
Route::get('post/{post}', [PostController::class, 'show'])->name('posts.show');
