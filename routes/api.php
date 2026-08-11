<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| Session-authenticated JSON endpoints for the Vue frontend. There is no token
| auth here on purpose: the admin panel is an Inertia SPA on the same origin,
| so Sanctum would add a dependency and an attack surface for nothing.
|
*/

Route::middleware('throttle:api')->group(function () {
    Route::get('health', fn (Request $request) => response()->json([
        'status' => 'ok',
        'time' => now()->toIso8601String(),
    ]))->name('api.health');
});
