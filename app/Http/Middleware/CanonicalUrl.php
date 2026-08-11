<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Collapses the URL variants that would otherwise serve identical content.
 *
 * /about/ and /about, www and bare host, all return the same page, and a
 * crawler treats each as a separate URL that splits the ranking between them.
 * The canonical tag handles most of it; a 301 handles it properly.
 */
class CanonicalUrl
{
    public function __invoke(Request $request, Closure $next): Response
    {
        return $this->handle($request, $next);
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Only safe, idempotent requests are redirected. Redirecting a POST
        // would drop the body and break every form on the site.
        if (! $request->isMethodSafe()) {
            return $next($request);
        }

        // canonical() returns null when the URL is already canonical. It is
        // deliberately not compared against fullUrl(), which normalises the
        // trailing slash away and would hide the very difference being fixed.
        $target = $this->canonical($request);

        return $target === null
            ? $next($request)
            : redirect()->away($target, 301);
    }

    private function canonical(Request $request): ?string
    {
        $path = $request->getPathInfo();
        $host = $request->getHost();

        $wantedHost = $this->preferredHost($host);
        $wantedPath = rtrim($path, '/');

        if ($wantedPath === '') {
            $wantedPath = '/';
        }

        if ($wantedHost === $host && $wantedPath === $path) {
            return null;
        }

        $query = $request->getQueryString();

        return $request->getScheme().'://'.$wantedHost
            .($request->getPort() && ! in_array($request->getPort(), [80, 443], true) ? ':'.$request->getPort() : '')
            .$wantedPath
            .($query ? '?'.$query : '');
    }

    /**
     * Only normalises between the configured host and its www variant. Any
     * other host is left alone: silently rewriting an unknown host would break
     * local development, previews and health checks.
     */
    private function preferredHost(string $host): string
    {
        $configured = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($configured) || $configured === '') {
            return $host;
        }

        $bare = fn (string $value) => preg_replace('/^www\./i', '', $value) ?? $value;

        return $bare($host) === $bare($configured) ? $configured : $host;
    }
}
