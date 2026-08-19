<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ContactRequest;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\User;
use App\Services\SeoService;
use App\Services\SettingsService;
use App\Support\Fingerprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

    /**
     * Each page gets its own description.
     *
     * Falling back to the site default gave all four of these the same
     * sentence, and four pages sharing one description is a duplicate-content
     * signal on the exact pages an ad network reads before approving a site.
     */
    private const DESCRIPTIONS = [
        'about' => 'Who publishes this site, how the stories are chosen and written, and where AI is used in that process. Written plainly, with nothing hidden.',
        'privacy' => 'What data this site collects, why, how long it is kept and how to have it removed. Covers cookies, analytics and advertising partners.',
        'terms' => 'The terms you agree to by using this site: acceptable use, intellectual property, liability, and how these terms may change over time.',
        'disclaimer' => 'The limits of what you should rely on here. Articles are for general information and are not professional, legal, medical or financial advice.',
    ];

    /**
     * The schema.org type each page actually is. About and Contact have their
     * own types, and using them lets a crawler tie the site to a publisher
     * and a reachable address rather than guessing from four generic pages.
     */
    private const SCHEMA_TYPES = [
        'about' => 'AboutPage',
        'privacy' => 'WebPage',
        'terms' => 'WebPage',
        'disclaimer' => 'WebPage',
    ];

    public function __construct(private readonly SeoService $seo) {}

    public function show(string $page): View
    {
        abort_unless(array_key_exists($page, self::PAGES), 404);

        return view("public.pages.{$page}", [
            'crumbs' => [['name' => self::PAGES[$page], 'url' => route('pages.show', $page)]],
            'seo' => [
                ...$this->seo->forPage(
                    self::PAGES[$page],
                    self::DESCRIPTIONS[$page],
                    route('pages.show', $page)
                ),
                'schemas' => [
                    $this->pageSchema(
                        self::SCHEMA_TYPES[$page],
                        self::PAGES[$page],
                        self::DESCRIPTIONS[$page],
                        route('pages.show', $page),
                    ),
                    $this->seo->breadcrumbSchema([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => self::PAGES[$page], 'url' => route('pages.show', $page)],
                    ]),
                ],
            ],
        ]);
    }

    public function contact(): View
    {
        $description = 'Get in touch about a correction, a partnership, an advertising enquiry or anything else. Messages are read by a person and usually answered within a couple of days.';

        return view('public.pages.contact', [
            'crumbs' => [['name' => 'Contact', 'url' => route('contact')]],
            'seo' => [
                ...$this->seo->forPage('Contact', $description, route('contact')),
                'schemas' => [
                    $this->pageSchema('ContactPage', 'Contact', $description, route('contact')),
                    $this->seo->breadcrumbSchema([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Contact', 'url' => route('contact')],
                    ]),
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pageSchema(string $type, string $name, string $description, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
            'isPartOf' => ['@type' => 'WebSite', 'name' => $this->seo->siteName(), 'url' => url('/')],
            'publisher' => $this->seo->organizationSchema(),
        ];
    }

    public function submitContact(ContactRequest $request): RedirectResponse
    {
        $message = ContactMessage::create([
            ...$request->safe()->only('name', 'email', 'subject', 'message'),
            'ip_hash' => Fingerprint::ip($request->ip()),
        ]);

        $this->notifyAdmin($message);

        return back()->with('success', 'Thanks — your message is on its way. We usually reply within a couple of days.');
    }

    /**
     * The message is already saved by this point, so a mail failure is logged
     * rather than thrown: an SMTP outage must not lose the visitor's message or
     * show them an error for something that worked.
     */
    private function notifyAdmin(ContactMessage $message): void
    {
        $to = app(SettingsService::class)->get('contact_email')
            ?: User::admins()->value('email');

        if (blank($to)) {
            return;
        }

        try {
            Mail::to($to)->send(new ContactMessageReceived($message));
        } catch (\Throwable $e) {
            Log::warning('Contact notification could not be sent', [
                'message' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Kept out of PAGES because it is generated, not written.
     */
    public function sitemapPlaceholder(Request $request): View
    {
        $description = 'Every section, category and recent article on the site in one place — the readable version of the XML sitemap search engines use.';

        return view('public.pages.sitemap', [
            'crumbs' => [['name' => 'Sitemap', 'url' => route('sitemap.page')]],
            'seo' => [
                ...$this->seo->forPage('Sitemap', $description, route('sitemap.page')),
                'schemas' => [
                    $this->pageSchema('WebPage', 'Sitemap', $description, route('sitemap.page')),
                    $this->seo->breadcrumbSchema([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Sitemap', 'url' => route('sitemap.page')],
                    ]),
                ],
            ],
        ]);
    }
}
