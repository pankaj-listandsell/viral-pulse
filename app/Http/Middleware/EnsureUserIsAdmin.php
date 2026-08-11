<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Gate for the whole admin panel. Admins, editors and authors get through
     * the door; policies decide what each of them may actually do once inside.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || ! $user->canAccessAdminPanel()) {
            abort(403, 'You do not have access to the admin panel.');
        }

        return $next($request);
    }
}
