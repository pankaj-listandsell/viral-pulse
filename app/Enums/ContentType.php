<?php

namespace App\Enums;

enum ContentType: string
{
    case News = 'news';
    case Trending = 'trending';
    case Listicle = 'listicle';
    case HowTo = 'how_to';
    case Quiz = 'quiz';
    case Fact = 'fact';
    case Story = 'story';
    case Entertainment = 'entertainment';
    case Technology = 'technology';
    case Travel = 'travel';
    case Devotional = 'devotional';
    case Education = 'education';

    public function label(): string
    {
        return match ($this) {
            self::News => 'News article',
            self::Trending => 'Trending topic',
            self::Listicle => 'Listicle',
            self::HowTo => 'How-to guide',
            self::Quiz => 'Quiz',
            self::Fact => 'Facts',
            self::Story => 'Story',
            self::Entertainment => 'Entertainment',
            self::Technology => 'Technology',
            self::Travel => 'Travel',
            self::Devotional => 'Devotional',
            self::Education => 'Education',
        };
    }

    /**
     * Shape guidance appended to the prompt. Kept short on purpose: current
     * models write better from a stated goal than from a step-by-step script.
     */
    public function guidance(): string
    {
        return match ($this) {
            self::News => 'Report what happened, who is involved, and why it matters. Lead with the news itself, not with background.',
            self::Trending => 'Explain what people are talking about and why it took off right now. Assume the reader saw a headline and wants the substance.',
            self::Listicle => 'Use a numbered list of 7 to 12 entries. Each entry gets an h3 heading and a short paragraph that says something specific, not filler.',
            self::HowTo => 'Give ordered, followable steps. State what the reader needs before starting and what success looks like at the end.',
            self::Quiz => 'Write 8 to 10 questions, each with its answer and a one-line explanation immediately after it.',
            self::Fact => 'Give a set of genuinely surprising, verifiable facts. Skip anything a reader would already know.',
            self::Story => 'Tell it as a narrative with a beginning, a turn, and an ending. Keep the pace moving.',
            self::Entertainment => 'Write for someone following the subject casually. Be specific about names, dates and what actually happened.',
            self::Technology => 'Explain the technology in plain language, then what changes for the reader in practice.',
            self::Travel => 'Cover what to see, when to go, and what it realistically costs. Be concrete about places.',
            self::Devotional => 'Write respectfully and accurately about the tradition. Do not invent scripture, rituals, or attributions.',
            self::Education => 'Teach the concept from first principles with a worked example the reader can follow.',
        };
    }
}
