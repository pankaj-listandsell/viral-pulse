<?php

namespace App\Services;

use Mews\Purifier\Facades\Purifier;

/**
 * Every piece of rich text - written by hand or produced by a model - passes
 * through here before it is stored. AI output in particular must never be
 * trusted: a generated article can contain script tags, event handlers,
 * javascript: URLs or iframes, and none of that should reach a reader.
 */
class HtmlSanitizer
{
    public function clean(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        return (string) Purifier::clean($html, 'article');
    }

    /**
     * For fields that must never contain markup at all - excerpts, comment
     * bodies, SEO descriptions.
     */
    public function plain(?string $text, ?int $limit = null): string
    {
        $clean = trim(strip_tags((string) $text));
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? '';

        return $limit ? mb_substr($clean, 0, $limit) : $clean;
    }

    public function wordCount(?string $html): int
    {
        $text = $this->plain($html);

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);
    }
}
