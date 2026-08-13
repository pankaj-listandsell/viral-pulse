<?php

namespace App\Providers;

use App\Models\ContactMessage;
use App\Models\User;
use App\Services\AI\AiProviderManager;
use App\Services\Images\BrandCardGenerator;
use App\Services\Images\Contracts\FeaturedImageGenerator;
use App\Services\MediaResolver;
use App\Services\SettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Both hold a per-request memo, so they must be the same instance for
        // every caller within a request.
        $this->app->scoped(SettingsService::class);
        $this->app->scoped(MediaResolver::class);

        // Singleton so tests can swap in the fake provider once and have every
        // resolution downstream honour it.
        $this->app->singleton(AiProviderManager::class);

        // The one place that decides how featured images are made. Swapping in
        // generated photography or a stock-photo service later is a change to
        // this line and nothing else.
        $this->app->bind(FeaturedImageGenerator::class, BrandCardGenerator::class);
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureUrls();
        $this->configureRateLimiting();
        $this->configureGates();
        $this->configureViews();

        Vite::prefetch(concurrency: 3);
    }

    /**
     * Layouts need the site name, logo and social links on every page. Only
     * settings flagged is_public are shared, and the lookup is served from a
     * forever-cache, so this costs nothing per request.
     */
    private function configureViews(): void
    {
        View::composer('layouts.*', function ($view): void {
            $view->with('siteSettings', app(SettingsService::class)->public());
        });

        // The inbox badge. Cached for a minute so it costs one query per
        // minute rather than one on every admin page load, and flushed the
        // moment a message changes state so the count is never visibly wrong.
        View::composer('admin.partials.sidebar', function ($view): void {
            $view->with('unreadMessages', Cache::remember(
                'admin.unread-messages',
                now()->addMinute(),
                fn (): int => ContactMessage::unread()->count()
            ));
        });
    }

    private function configureModels(): void
    {
        // Outside production, surface lazy loading, missing attributes and
        // silently discarded mass-assignment as errors rather than letting
        // them ship as N+1 queries and quietly dropped fields.
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::unguard(false);
    }

    private function configureUrls(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('registrations', fn (Request $request) => Limit::perHour(5)->by($request->ip()));

        RateLimiter::for('password-resets', fn (Request $request) => Limit::perHour(5)
            ->by($request->input('email').'|'.$request->ip()));

        RateLimiter::for('comments', fn (Request $request) => Limit::perMinute(3)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('newsletter', fn (Request $request) => Limit::perHour(5)->by($request->ip()));

        RateLimiter::for('contact', fn (Request $request) => Limit::perHour(3)->by($request->ip()));

        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        // AI calls cost money, so the limit is per user rather than per IP.
        RateLimiter::for('ai-generation', fn (Request $request) => Limit::perMinute(5)->by($request->user()?->id));
    }

    /**
     * The site has a single administrator, so authorization is one question:
     * is this an active admin? Per-model policies would add ceremony without
     * expressing anything the gate below does not already say.
     */
    private function configureGates(): void
    {
        Gate::before(fn (User $user) => $user->canAccessAdminPanel() ? true : null);

        Gate::define('access-admin', fn (User $user): bool => $user->canAccessAdminPanel());
    }
}
