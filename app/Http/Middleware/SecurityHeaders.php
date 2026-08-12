<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response headers that cost nothing and close off whole classes of attack.
 *
 * Deliberately no full Content-Security-Policy. The site loads AdSense and
 * Google Analytics, both of which inject further scripts from hosts they choose
 * at runtime, and the theme switcher and ad slots run inline script. A policy
 * loose enough to allow all of that stops blocking anything worth blocking,
 * and a policy tight enough to matter breaks the ads this site exists to serve.
 * frame-ancestors is the one directive that is both meaningful and safe here.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            // Stops a browser from second-guessing a declared content type,
            // which is how an uploaded image gets executed as script.
            'X-Content-Type-Options' => 'nosniff',

            // Clickjacking: nobody may frame this site.
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "frame-ancestors 'self'",

            // Send the full URL within the site, only the origin off it, and
            // nothing at all when downgrading to http.
            'Referrer-Policy' => 'strict-origin-when-cross-origin',

            // The site needs none of these; denying them means a compromised
            // third-party ad script cannot ask for them either.
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',

            // Legacy header, still honoured by some corporate proxies.
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ];

        // HSTS only over a real TLS connection: sent over http it is ignored,
        // and setting it in development would pin localhost to https in the
        // developer's browser for a year.
        if ($request->secure() && app()->isProduction()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
