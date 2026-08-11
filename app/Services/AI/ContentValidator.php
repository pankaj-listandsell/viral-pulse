<?php

namespace App\Services\AI;

use App\Models\Post;
use App\Services\HtmlSanitizer;
use Illuminate\Support\Str;

/**
 * The quality gate between the model and the site.
 *
 * This decides whether generated content is fit to auto-publish. It is not a
 * substitute for a human read - it catches the mechanical failures (too short,
 * truncated, duplicate, hedging placeholders) so a reviewer's attention goes to
 * the things only a person can judge.
 */
class ContentValidator
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    /**
     * Phrases that mean the model gave up or left a placeholder behind.
     *
     * @var array<int, string>
     */
    private const PLACEHOLDER_PHRASES = [
        'lorem ipsum',
        'as an ai language model',
        'as an ai model',
        'i cannot browse',
        'i do not have access to real-time',
        'insert ',
        '[your ',
        'todo:',
        'xxx',
        'placeholder',
    ];

    /**
     * @param  array<string, mixed>  $payload
     * @return array{score: int, issues: array<int, string>, publishable: bool}
     */
    public function check(array $payload): array
    {
        $issues = [];
        $score = 100;

        $content = (string) ($payload['content'] ?? '');
        $title = trim((string) ($payload['title'] ?? ''));
        $words = $this->sanitizer->wordCount($content);
        $minWords = (int) config('site.content.min_words', 400);

        if ($words < $minWords) {
            $issues[] = "Only {$words} words; the minimum is {$minWords}.";
            $score -= 40;
        }

        // A body that ends without terminal punctuation is usually a response
        // that hit the token ceiling mid-sentence.
        $plain = rtrim($this->sanitizer->plain($content));

        if ($plain !== '' && ! Str::endsWith($plain, ['.', '!', '?', '"', '”', '।'])) {
            $issues[] = 'The article appears to end mid-sentence.';
            $score -= 25;
        }

        if (mb_strlen($title) < 15) {
            $issues[] = 'The title is too short to work as a headline.';
            $score -= 15;
        }

        if (mb_strlen($title) > 110) {
            $issues[] = 'The title is too long and will be truncated in search results.';
            $score -= 5;
        }

        if (! Str::contains($content, '<h2')) {
            $issues[] = 'The article has no section headings.';
            $score -= 10;
        }

        $lower = Str::lower($this->sanitizer->plain($content).' '.$title);

        foreach (self::PLACEHOLDER_PHRASES as $phrase) {
            if (Str::contains($lower, $phrase)) {
                $issues[] = "Contains placeholder or filler text: \"{$phrase}\".";
                $score -= 30;
                break;
            }
        }

        if (blank($payload['excerpt'] ?? null)) {
            $issues[] = 'No excerpt was produced.';
            $score -= 5;
        }

        $seoDescription = (string) ($payload['seo_description'] ?? '');

        if (mb_strlen($seoDescription) > 160) {
            $issues[] = 'The meta description is over 160 characters and will be cut off.';
            $score -= 5;
        }

        if ($title !== '' && $this->titleAlreadyUsed($title)) {
            $issues[] = 'A post with this title already exists.';
            $score -= 35;
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'issues' => $issues,
            'publishable' => $score >= (int) config('site.content.min_quality_score', 70),
        ];
    }

    /**
     * Duplicate titles are the clearest signal of a scheduler generating the
     * same topic twice - and near-duplicate pages are exactly what search
     * engines penalise.
     */
    private function titleAlreadyUsed(string $title): bool
    {
        return Post::withTrashed()->where('title', $title)->exists();
    }
}
