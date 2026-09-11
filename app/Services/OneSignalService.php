<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    private const API_URL = 'https://onesignal.com/api/v1/notifications';

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Sends a push notification to all subscribed users.
     *
     * @param  string  $title
     * @param  string  $message
     * @param  string|null  $url
     * @param  string|null  $imageUrl
     * @return bool
     */
    public function send(string $title, string $message, ?string $url = null, ?string $imageUrl = null): bool
    {
        $appId = $this->settings->get('onesignal_app_id');
        $apiKey = $this->settings->get('onesignal_rest_api_key') ?: config('services.onesignal.rest_api_key');

        if (blank($appId)) {
            Log::info('OneSignal: appId is missing, skipping notification.');
            return false;
        }

        $payload = [
            'app_id' => $appId,
            'included_segments' => ['Total Subscriptions', 'Subscribed Users'],
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
            'url' => $url ?: url('/'),
        ];

        if ($imageUrl) {
            $payload['big_picture'] = $imageUrl;
            $payload['chrome_web_image'] = $imageUrl;
        }

        try {
            $request = Http::asJson()->timeout(10);
            if ($apiKey) {
                $request = $request->withHeader('Authorization', 'Basic '.$apiKey);
            }

            $response = $request->post(self::API_URL, $payload);

            if ($response->successful()) {
                Log::info('OneSignal: Push sent successfully', ['title' => $title, 'response' => $response->json()]);
                return true;
            }

            Log::warning('OneSignal: Push send response', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('OneSignal: Push failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
