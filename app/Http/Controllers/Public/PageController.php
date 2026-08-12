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
            'seo' => $this->seo->forPage('Sitemap', 'Every page on the site.', route('sitemap.page')),
        ]);
    }
}
