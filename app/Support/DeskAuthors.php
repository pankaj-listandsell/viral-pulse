<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The desks that byline AI-drafted articles.
 *
 * Desks rather than invented people: a named journalist who does not exist
 * is a fake byline, which Google's spam policies treat as deception and
 * readers rightly would too. A desk is honest about who stands behind the
 * story - the publication - and still gives every section its own author
 * page, bio and schema entity.
 *
 * The accounts cannot sign in: they are not admins and their password is
 * random and never stored anywhere.
 */
class DeskAuthors
{
    public const DESKS = [
        'news-desk' => [
            'name' => 'ViralPulse News Desk',
            'categories' => ['news', 'trending'],
            'bio' => 'The News Desk follows the stories India is searching for - national affairs, government schemes, policy changes and the day\'s biggest developments - and explains what they mean for ordinary readers.',
        ],
        'business-desk' => [
            'name' => 'ViralPulse Business Desk',
            'categories' => ['business'],
            'bio' => 'The Business Desk covers markets, gold and fuel prices, personal finance and the economy, with a focus on how each change reaches your wallet.',
        ],
        'tech-desk' => [
            'name' => 'ViralPulse Tech Desk',
            'categories' => ['technology'],
            'bio' => 'The Tech Desk covers smartphones, apps, AI, digital payments and the internet services millions of Indians use every day.',
        ],
        'sports-desk' => [
            'name' => 'ViralPulse Sports Desk',
            'categories' => ['sports'],
            'bio' => 'The Sports Desk covers cricket, football and the wider sporting calendar - fixtures, results, records and the context behind them.',
        ],
        'entertainment-desk' => [
            'name' => 'ViralPulse Entertainment Desk',
            'categories' => ['entertainment'],
            'bio' => 'The Entertainment Desk covers films, box office, OTT releases, music and television across Bollywood and regional cinema.',
        ],
        'lifestyle-desk' => [
            'name' => 'ViralPulse Lifestyle & Health Desk',
            'categories' => ['lifestyle', 'health', 'travel', 'quiz-fun'],
            'bio' => 'The Lifestyle & Health Desk writes practical guides on wellness, food, travel and everyday living. Health articles are general information, not medical advice.',
        ],
        'astrology-desk' => [
            'name' => 'ViralPulse Astrology Desk',
            'categories' => ['astrology', 'devotional'],
            'bio' => 'The Astrology Desk publishes the daily rashifal for all twelve signs, festival dates and devotional explainers. Astrology is shared for cultural interest and entertainment.',
        ],
        'education-desk' => [
            'name' => 'ViralPulse Education & Careers Desk',
            'categories' => ['education'],
            'bio' => 'The Education & Careers Desk covers exam results, admit cards, recruitment notices and step-by-step guides for students and job seekers.',
        ],
    ];

    private const DISCLOSURE = ' Stories from this desk are written with AI assistance and published by the ViralPulse editorial team.';

    /**
     * Create any desk that is missing. Idempotent: an existing desk - even
     * one renamed or re-described by hand since - is left as it is.
     */
    public static function sync(): void
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'viralpulse.in';

        foreach (self::DESKS as $username => $desk) {
            if (DB::table('users')->where('username', $username)->exists()) {
                continue;
            }

            DB::table('users')->insert([
                'name' => $desk['name'],
                'username' => $username,
                'email' => "{$username}@{$host}",
                'password' => Hash::make(Str::random(64)),
                'bio' => $desk['bio'].self::DISCLOSURE,
                'author_categories' => json_encode($desk['categories']),
                'is_admin' => false,
                'is_author' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
