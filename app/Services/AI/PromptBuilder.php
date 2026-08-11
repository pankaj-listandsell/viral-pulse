<?php

namespace App\Services\AI;

use App\Services\SettingsService;

class PromptBuilder
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Stable across every generation so providers that support prompt caching
     * can reuse it. Nothing request-specific belongs here.
     */
    public function system(): string
    {
        $siteName = $this->settings->get('site_name') ?: config('app.name');

        return <<<PROMPT
        You write articles for {$siteName}, a general-interest publication.

        Write for a reader who came from a search result or a shared link and
        wants the substance quickly. Lead with what happened or what the answer
        is; put background afterwards for the readers who want it.

        Accuracy over fluency. If you are not confident a specific fact, name,
        date, figure or quote is correct, leave it out rather than approximating
        it — a plausible invented detail is worse than an omission. Never
        fabricate quotes, statistics, studies, prices, or events. Do not claim
        to have tested, visited, or verified anything. Write nothing that reads
        as professional medical, legal, or financial advice.

        Formatting rules for the content field:
        - HTML only, using p, h2, h3, ul, ol, li, strong, em, blockquote and a.
        - No h1 — the page renders the title separately.
        - No inline styles, no classes, no script, no iframe, no images.
        - Open with a paragraph, not a heading.
        - Break the article into sections with h2 headings a reader can scan.

        Write the way a careful human editor would: vary sentence length, skip
        filler transitions, and cut any sentence that only restates the previous
        one. Do not pad to reach a word count.
        PROMPT;
    }

    /**
     * The request-specific half. Everything that varies per article lives here
     * so the cached system prefix stays byte-identical.
     */
    public function user(GenerationRequest $request): string
    {
        $lines = [
            "Write a {$request->contentType->label()} in {$request->languageName()}.",
            '',
            "Topic: {$request->topic}",
        ];

        if ($request->category) {
            $lines[] = "Section: {$request->category->name}";
        }

        if ($request->audience) {
            $lines[] = "Audience: {$request->audience}";
        }

        $lines[] = "Target length: about {$request->targetWords} words.";
        $lines[] = '';
        $lines[] = "Shape: {$request->contentType->guidance()}";
        $lines[] = "Tone: {$request->tone->guidance()}";

        if ($request->extraContext) {
            $lines[] = '';
            $lines[] = 'Additional context to work from:';
            $lines[] = $request->extraContext;
        }

        $lines[] = '';
        $lines[] = <<<'TAIL'
        Also produce:
        - An SEO title under 60 characters that reads naturally, not stuffed.
        - A meta description under 160 characters that says what the reader gets.
        - 5 to 8 comma-separated keywords someone would actually search for.
        - 3 to 6 short topic tags.
        - A prompt for generating a featured image: describe the scene plainly,
          with no text or logos in the image.
        TAIL;

        return implode("\n", $lines);
    }
}
