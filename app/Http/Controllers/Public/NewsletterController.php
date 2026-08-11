<?php

namespace App\Http\Controllers\Public;

use App\Enums\SubscriberStatus;
use App\Http\Controllers\Controller;
use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterSubscriber;
use App\Services\SettingsService;
use App\Support\Fingerprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Double opt-in: subscribing only creates a pending record, and nothing is
     * ever sent until the address is confirmed from the inbox that owns it.
     */
    public function subscribe(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->settings->bool('newsletter_enabled', true), 404);

        $validated = $request->validate([
            // No DNS check: double opt-in already proves the address is real,
            // and a live MX lookup would block the request on the network.
            'email' => ['required', 'email:rfc', 'max:255'],
            'name' => ['nullable', 'string', 'max:100'],
            'website' => ['prohibited'],
        ], [
            'website.prohibited' => 'That submission looked automated.',
        ]);

        $subscriber = NewsletterSubscriber::firstOrNew(['email' => Str::lower($validated['email'])]);

        if ($subscriber->exists && $subscriber->status === SubscriberStatus::Subscribed) {
            return $this->respond($request, 'You are already on the list.');
        }

        $subscriber->fill([
            'name' => $validated['name'] ?? $subscriber->name,
            'status' => SubscriberStatus::Pending,
            'ip_hash' => Fingerprint::ip($request->ip()),
            'source' => $subscriber->source ?? 'site',
            'unsubscribed_at' => null,
        ]);

        // A fresh token invalidates any link from a previous attempt.
        $subscriber->token = Str::random(64);
        $subscriber->save();

        try {
            Mail::to($subscriber->email)->send(new NewsletterConfirmation($subscriber));
        } catch (\Throwable $e) {
            // A mail outage must not look like a failed subscription: the
            // record exists and can be confirmed once mail recovers.
            Log::error('Newsletter confirmation failed to send', [
                'subscriber' => $subscriber->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->respond($request, 'Almost there — check your inbox to confirm.');
    }

    public function confirm(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->first();

        if (! $subscriber) {
            return redirect()->route('home')->with('error', 'That confirmation link is no longer valid.');
        }

        if ($subscriber->status !== SubscriberStatus::Subscribed) {
            $subscriber->forceFill([
                'status' => SubscriberStatus::Subscribed,
                'confirmed_at' => now(),
            ])->save();
        }

        return redirect()->route('home')->with('success', 'You are subscribed. Welcome aboard.');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->first();

        // Unsubscribing always reports success. Telling someone their address
        // was not found would leak who is on the list.
        $subscriber?->forceFill([
            'status' => SubscriberStatus::Unsubscribed,
            'unsubscribed_at' => now(),
        ])->save();

        return redirect()->route('home')->with('success', 'You have been unsubscribed. Sorry to see you go.');
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }
}
