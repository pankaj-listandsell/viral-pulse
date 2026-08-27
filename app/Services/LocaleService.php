<?php

namespace App\Services;

use Illuminate\Support\Arr;

/**
 * One place that knows which languages the public site speaks, what each one's
 * URLs look like, and how to name the same page in every other language.
 *
 * The last of those is the part that matters for search. A translated page is
 * worth nothing to Google until every version of it points at every other one
 * with rel="alternate" hreflang - without that the two versions compete as
 * near-duplicates and Google keeps whichever it likes, usually not the one you
 * wanted. Building those links from the same table the router reads is what
 * stops the two drifting apart.
 */
class LocaleService
{
    public function default(): string
    {
        return config('locales.default', 'en');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function supported(): array
    {
        return config('locales.supported', []);
    }

    /**
     * @return array<int, string>
     */
    public function codes(): array
    {
        return array_keys($this->supported());
    }

    public function isSupported(?string $code): bool
    {
        return $code !== null && array_key_exists($code, $this->supported());
    }

    public function current(): string
    {
        $locale = app()->getLocale();

        return $this->isSupported($locale) ? $locale : $this->default();
    }

    public function isDefault(?string $locale = null): bool
    {
        return ($locale ?? $this->current()) === $this->default();
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(?string $locale = null): array
    {
        $locale = $locale ?? $this->current();

        return $this->supported()[$locale] ?? $this->supported()[$this->default()] ?? [];
    }

    public function hreflang(?string $locale = null): string
    {
        return Arr::get($this->meta($locale), 'hreflang', $locale ?? $this->current());
    }

    /**
     * The locale a request belongs to, read from the URL prefix rather than
     * from a cookie or an Accept-Language header.
     *
     * A crawler sends neither, so anything driven by them would serve Googlebot
     * one language and the reader another - the classic way a translated site
     * gets its Hindi pages indexed under English URLs.
     */
    public function fromPath(string $path): string
    {
        $first = strtok(ltrim($path, '/'), '/') ?: '';

        foreach ($this->supported() as $code => $meta) {
            if (($meta['prefix'] ?? null) !== null && $meta['prefix'] === $first) {
                return $code;
            }
        }

        return $this->default();
    }

    /*
    |--------------------------------------------------------------------------
    | Horoscope URLs
    |--------------------------------------------------------------------------
    */

    /**
     * The route name for one horoscope page in one language.
     *
     * English keeps the bare names it was already registered under, because
     * route('horoscope') is called from the header, the footer and the sitemap
     * and renaming it would be a refactor with no reader-facing gain.
     */
    public function routeName(string $key, ?string $locale = null): string
    {
        $locale = $locale ?? $this->current();
        $base = $key === 'hub' ? 'horoscope' : "horoscope.{$key}";

        return $this->isDefault($locale) ? $base : "{$locale}.{$base}";
    }

    /**
     * Translate an internal sign key into the slug that locale puts in its URL.
     */
    public function signSlug(string $sign, ?string $locale = null): string
    {
        $locale = $locale ?? $this->current();

        return config("horoscope.slugs.{$locale}.{$sign}", $sign);
    }

    /**
     * The reverse: a slug out of the URL back to the internal sign key.
     *
     * Both languages' slugs are accepted in either locale so that a Hindi slug
     * typed against an English URL resolves instead of 404ing - the controller
     * then redirects it to the right language's canonical path rather than
     * leaving the reader on a dead end.
     */
    public function signFromSlug(string $slug, ?string $locale = null): ?string
    {
        $slug = strtolower($slug);
        $locale = $locale ?? $this->current();

        $ordered = array_unique([$locale, ...$this->codes()]);

        foreach ($ordered as $code) {
            $map = config("horoscope.slugs.{$code}", []);
            $sign = array_search($slug, $map, true);

            if ($sign !== false) {
                return $sign;
            }
        }

        return null;
    }

    /**
     * Absolute URL for a horoscope page in a given language.
     *
     * `$query` carries the compatibility page's sign1/sign2 pair, so a pair's
     * alternates point at the same pair in the other language rather than at
     * the calculator's default view - an hreflang pointing somewhere that is
     * not a translation of the page is worse than no hreflang at all.
     *
     * @param  array<string, string>  $query
     */
    public function horoscopeUrl(string $key, ?string $sign = null, ?string $locale = null, array $query = []): string
    {
        $locale = $locale ?? $this->current();
        $name = $this->routeName($key, $locale);

        $parameters = $query;

        if ($sign !== null) {
            $parameters['sign'] = $this->signSlug($sign, $locale);
        }

        return route($name, $parameters);
    }

    /**
     * Every language's version of one page, ready to be rendered as hreflang.
     *
     * x-default names the version served to a reader whose language the site
     * does not publish. That is English here, and saying so explicitly stops
     * Google choosing the Hindi page for, say, a reader in Germany.
     *
     * @param  array<string, string>  $query
     * @return array<int, array{hreflang: string, href: string}>
     */
    public function alternates(string $key, ?string $sign = null, array $query = []): array
    {
        $links = [];

        foreach ($this->codes() as $code) {
            $links[] = [
                'hreflang' => $this->hreflang($code),
                'href' => $this->horoscopeUrl($key, $sign, $code, $query),
            ];
        }

        $links[] = [
            'hreflang' => 'x-default',
            'href' => $this->horoscopeUrl($key, $sign, $this->default(), $query),
        ];

        return $links;
    }

    /**
     * The switcher's own links: this page in each of the other languages.
     *
     * @return array<int, array<string, mixed>>
     */
    public function switcher(string $key, ?string $sign = null, array $query = []): array
    {
        $current = $this->current();

        return collect($this->supported())
            ->map(fn (array $meta, string $code): array => [
                'code' => $code,
                'native' => $meta['native'] ?? $code,
                'name' => $meta['name'] ?? $code,
                'url' => $this->horoscopeUrl($key, $sign, $code, $query),
                'current' => $code === $current,
            ])
            ->values()
            ->all();
    }
}
