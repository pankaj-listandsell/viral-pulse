<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ContactRequest;
use App\Models\ContactMessage;
use App\Services\SeoService;
use App\Support\Fingerprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * The static pages AdSense and most ad networks expect a publisher to
     * have. Their copy lives in views rather than the database because it is
     * legal boilerplate, not content the site produces.
     */
    private const PAGES = [
        'about' => 'About',
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms of Service',
        'disclaimer' => 'Disclaimer',
    ];

    public function __construct(private readonly SeoService $seo) {}

    public function show(string $page): View
    {
        abort_unless(array_key_exists($page, self::PAGES), 404);

        return view("public.pages.{$page}", [
            'seo' => $this->seo->forPage(self::PAGES[$page], null, route('pages.show', $page)),
        ]);
    }

    public function contact(): View
    {
        return view('public.pages.contact', [
            'seo' => $this->seo->forPage('Contact', 'Get in touch with us.', route('contact')),
        ]);
    }

    public function submitContact(ContactRequest $request): RedirectResponse
    {
        ContactMessage::create([
            ...$request->safe()->only('name', 'email', 'subject', 'message'),
            'ip_hash' => Fingerprint::ip($request->ip()),
        ]);

        return back()->with('success', 'Thanks — your message is on its way. We usually reply within a couple of days.');
    }

    /**
     * Kept out of PAGES because it is generated, not written.
     */
    public function sitemapPlaceholder(Request $request): View
    {
        return view('public.pages.sitemap', [
            'seo' => $this->seo->forPage('Sitemap', 'Every page on the site.', route('sitemap.page')),
        ]);
    }
}
