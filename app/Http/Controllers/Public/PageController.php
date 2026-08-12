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

    public function __construct(private readonly SeoService $seo) {}

    public function show(string $page): View
    {
        abort_unless(array_key_exists($page, self::PAGES), 404);

        return view("public.pages.{$page}", [
            'seo' => $this->seo->forPage(
                self::PAGES[$page],
                self::DESCRIPTIONS[$page],
                route('pages.show', $page)
            ),
        ]);
    }

    public function contact(): View
    {
        return view('public.pages.contact', [
            'seo' => $this->seo->forPage(
                'Contact',
                'Get in touch about a correction, a partnership, an advertising enquiry or anything else. Messages are read by a person and usually answered within a couple of days.',
                route('contact')
            ),
        ]);
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
        return view('public.pages.sitemap', [
            'seo' => $this->seo->forPage(
                'Sitemap',
                'Every section, category and recent article on the site in one place — the readable version of the XML sitemap search engines use.',
                route('sitemap.page')
            ),
        ]);
    }
}
