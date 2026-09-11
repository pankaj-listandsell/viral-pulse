<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Broadcasts a post to a Telegram Channel or Group.
     */
    public function broadcast(Post $post): bool
    {
        $botToken = $this->settings->get('social_telegram_bot_token') ?: config('services.telegram.bot_token');
        $chatId = $this->settings->get('social_telegram_chat_id') ?: config('services.telegram.chat_id');

        if (blank($botToken) || blank($chatId)) {
            return false;
        }

        $url = route('posts.show', $post->slug);
        $title = $post->title;
        $excerpt = strip_tags($post->excerpt ?: '');

        $caption = "⚡ <b>{$title}</b>\n\n";
        if ($excerpt) {
            $caption .= "{$excerpt}\n\n";
        }
        $caption .= "👉 <a href=\"{$url}\">Read Full Article on ViralPulse</a>";

        $apiUrl = "https://api.telegram.org/bot{$botToken}";

        try {
            $imageUrl = $post->featured_image ? url($post->featured_image) : null;

            if ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $response = Http::timeout(10)->post("{$apiUrl}/sendPhoto", [
                    'chat_id' => $chatId,
                    'photo' => $imageUrl,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]);
            } else {
                $response = Http::timeout(10)->post("{$apiUrl}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $caption,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => false,
                ]);
            }

            if ($response->successful()) {
                Log::info('Telegram: Broadcast succeeded', ['postId' => $post->id]);
                return true;
            }

            Log::warning('Telegram: Broadcast failed', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Telegram: Broadcast error', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
