<?php

namespace App\Http\Middleware;

use App\Services\LocaleService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the application into the language the URL asked for.
 *
 * The URL is the only signal used. Session, cookie and Accept-Language are all
 * deliberately ignored: a crawler carries none of them, so a site that switched
 * language on any of those would show Googlebot one version of a URL and a
 * returning reader another. Google calls that cloaking even when it is an
 * accident, and the visible symptom is Hindi content indexed under the English
 * URL. One URL, one language, always.
 */
class SetLocale
{
    public function __construct(private readonly LocaleService $locales) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->locales->fromPath($request->path());

        app()->setLocale($locale);

        // Dates are content on a horoscope page - the reading is only credible
        // if it is dated in the reader's own language, so "25 August 2026"
        // becomes "25 अगस्त 2026" rather than staying English on a Hindi page.
        Carbon::setLocale($locale);

        return $next($request);
    }
}
