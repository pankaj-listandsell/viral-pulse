<?php

use App\Http\Controllers\Public\AdsTxtController;
use App\Http\Controllers\Public\ArchiveController;
use App\Http\Controllers\Public\FeedController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LikeController;
use App\Http\Controllers\Public\NewsletterController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PostController;
use App\Http\Controllers\Public\RobotsController;
use App\Http\Controllers\Public\SearchController;
use App\Http\Controllers\Public\SitemapController;
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

Route::get('search/live', [SearchController::class, 'live'])->name('search.live');
Route::get('search', SearchController::class)->middleware('throttle:search')->name('search');

Route::get('contact', [PageController::class, 'contact'])->name('contact');
Route::post('contact', [PageController::class, 'submitContact'])
    ->middleware('throttle:contact')
    ->name('contact.submit');

Route::get('sitemap', [PageController::class, 'sitemapPlaceholder'])->name('sitemap.page');

/*
 * Machine-readable endpoints. These are the files a crawler and an ad network
 * look for by exact path, so none of them may move.
 */
Route::controller(SitemapController::class)->group(function () {
    Route::get('sitemap.xml', 'index')->name('sitemap.index');
    Route::get('sitemap-posts-{page}.xml', 'posts')->whereNumber('page')->name('sitemap.posts');
    Route::get('sitemap-categories.xml', 'categories')->name('sitemap.categories');
    Route::get('sitemap-tags.xml', 'tags')->name('sitemap.tags');
    Route::get('sitemap-pages.xml', 'pages')->name('sitemap.pages');
});

Route::get('robots.txt', RobotsController::class)->name('robots');
Route::get('ads.txt', AdsTxtController::class)->name('ads');

Route::get('feed.xml', [FeedController::class, 'index'])->name('feed.index');
Route::get('feed/{category}.xml', [FeedController::class, 'category'])->name('feed.category');

Route::post('newsletter', [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:newsletter')
    ->name('newsletter.subscribe');
Route::get('newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

Route::post('post/{post}/like', [LikeController::class, 'toggle'])
    ->middleware('throttle:60,1')
    ->name('posts.like');

Route::get('post/{post}/reactions', [\App\Http\Controllers\Public\ReactionController::class, 'index'])->name('posts.reactions.index');
Route::post('post/{post}/react', [\App\Http\Controllers\Public\ReactionController::class, 'toggle'])
    ->middleware('throttle:60,1')
    ->name('posts.react');

Route::get('post/{post}/poll', [\App\Http\Controllers\Public\PollController::class, 'index'])->name('posts.poll.index');
Route::post('post/{post}/poll/vote', [\App\Http\Controllers\Public\PollController::class, 'vote'])
    ->middleware('throttle:30,1')
    ->name('posts.poll.vote');

// Static pages are matched last so they cannot shadow a real route.
Route::get('page/{page}', [PageController::class, 'show'])->name('pages.show');

// The article URL sits at the root of its own segment: short, stable and
// readable, which is what both people and crawlers prefer.
//
// Bound as a string rather than a model so a slug that no longer exists can be
// checked against post_slug_history and 301'd instead of 404'd.
Route::get('post/{slug}', [PostController::class, 'show'])->name('posts.show');
