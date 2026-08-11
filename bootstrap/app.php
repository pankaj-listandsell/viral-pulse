<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Error views live in resources/views/errors and are picked up by
        // Laravel automatically. Nothing to wire here beyond keeping session
        // expiry friendly rather than a bare 419 page.
        $exceptions->respond(function ($response, Throwable $exception, $request) {
            if ($response->getStatusCode() === 419 && ! $request->expectsJson()) {
                return back()->withInput($request->except('password', 'password_confirmation'))
                    ->with('error', 'Your session expired. Please try again.');
            }

            return $response;
        });
    })->create();
