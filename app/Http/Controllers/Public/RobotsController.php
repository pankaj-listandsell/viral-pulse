<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Served from a route rather than a static file so it can react to the
     * environment. The static public/robots.txt was deleted: the web server
     * serves files before routes, so it would have shadowed this permanently.
     */
    public function __invoke(): Response
    {
        return response($this->body(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function body(): string
    {
        // A staging copy indexed alongside the real site is a duplicate-content
        // problem that is painful to undo, so anything that is not production
        // is closed outright.
        if (! app()->isProduction() || $this->settings->get('seo_discourage_indexing')) {
            return "User-agent: *\nDisallow: /\n";
        }

        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# Nothing here is content a search engine should hold.',
            'Disallow: /admin',
            'Disallow: /search',
            'Disallow: /newsletter/',
            'Disallow: /*?q=',
            'Disallow: /*?page=',
            '',
            // GPTBot and friends are left alone deliberately: blocking them
            // costs referral traffic and protects nothing, since the content is
            // public either way.
            'Sitemap: '.route('sitemap.index'),
        ];

        return implode("\n", $lines)."\n";
    }
}
