<?php

namespace App\Console\Commands;

use App\Enums\AiGenerationStatus;
use App\Enums\ContentTone;
use App\Enums\ContentType;
use App\Jobs\GenerateAiContentJob;
use App\Models\AiGeneration;
use App\Models\Category;
use App\Models\User;
use App\Services\AI\AiContentService;
use App\Services\AI\GenerationRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateDailyHoroscope extends Command
{
    protected $signature = 'content:generate-daily-horoscope
                            {--date= : The date to generate for, format YYYY-MM-DD}
                            {--force : Force generation even if disabled in config}';

    protected $description = 'Generate a comprehensive daily horoscope/rashifal article covering all 12 zodiac signs';

    public function handle(AiContentService $contentService): int
    {
        $enabled = config('trending.automation.enabled', true);
        if (!$enabled && !$this->option('force')) {
            $this->warn('Daily horoscope auto-generation is disabled. Use --force to execute.');
            return self::SUCCESS;
        }

        $author = User::admins()->where('is_active', true)->first();
        if (!$author) {
            $this->error('No active admin user found.');
            return self::FAILURE;
        }

        // Get date
        $dateStr = $this->option('date') ?: now()->toDateString();
        $date = Carbon::parse($dateStr);
        $formattedDate = $date->format('j F Y'); // e.g. "19 August 2026"

        $this->info("Starting Daily Horoscope generation for {$formattedDate}...");

        // Ensure "Astrology" category exists
        $category = Category::where('slug', 'astrology')
            ->orWhere('slug', 'rashifal')
            ->first();

        if (!$category) {
            $category = Category::create([
                'name' => 'Astrology',
                'slug' => 'astrology',
                'color' => '#8b5cf6', // purple-500
                'is_active' => true,
                'sort_order' => 10,
            ]);
        }

        $topic = "Daily Horoscope Predictions for All 12 Zodiac Signs on {$formattedDate}";

        /*
         * Once a day, however often this is called.
         *
         * The schedule cannot rely on the scheduler waking during one exact
         * minute: shared hosting throttles cron, and a task pinned to 05:00
         * is simply skipped for the day when nothing runs at 05:00. So this is
         * scheduled across a window and asked to run repeatedly, and this
         * guard is what makes that safe - the first run of the day creates the
         * article, the rest see it and stop.
         *
         * Failed and rejected attempts do not count, so a bad morning is
         * retried rather than blocking the day.
         */
        $existing = AiGeneration::where('topic', $topic)
            ->whereIn('status', [
                AiGenerationStatus::Pending,
                AiGenerationStatus::Processing,
                AiGenerationStatus::Completed,
            ])
            ->first();

        if ($existing && ! $this->option('force')) {
            $this->info("Already generated for {$formattedDate} (generation #{$existing->id}, {$existing->status->value}).");

            return self::SUCCESS;
        }

        $extraContext = <<<CONTEXT
Generate a comprehensive, highly engaging, and structured daily horoscope forecast for all 12 zodiac signs: Aries, Taurus, Gemini, Cancer, Leo, Virgo, Libra, Scorpio, Sagittarius, Capricorn, Aquarius, and Pisces.

For each of the 12 signs, write a beautifully styled section starting with an <h2> heading for that sign.
Inside each sign's section, you must include:
1. Astrological overview of today's cosmic energy for the sign.
2. 💖 Love & Relationship prediction.
3. 💼 Career & Finance forecast.
4. 🔢 Lucky Number (Shubh Ank) and 🎨 Lucky Color (Shubh Rang).

Make sure the article has a high-quality introduction explaining today's overall planetary transits and a final summary advice. Keep the content friendly, informative, and engaging.
CONTEXT;

        // Queue generation
        $request = new GenerationRequest(
            topic: $topic,
            contentType: ContentType::News, // Standard article
            tone: ContentTone::Informative,
            category: $category,
            language: 'en',
            targetWords: 1500, // Make it detailed for all 12 signs
            extraContext: $extraContext
        );

        $generation = $contentService->queue($request, $author);

        // Dispatch background generation job
        GenerateAiContentJob::dispatch(
            generationId: $generation->id,
            requestData: [
                'topic' => $request->topic,
                'content_type' => $request->contentType->value,
                'tone' => $request->tone->value,
                'category_id' => $category->id,
                'language' => $request->language,
                'target_words' => $request->targetWords,
                'extra_context' => $request->extraContext,
            ],
            userId: $author->id,
            categoryId: $category->id,
            createPost: true,
            publishAt: null, // Publish immediately
        );

        $this->info("Daily Horoscope generation job successfully queued under ID: {$generation->id}");

        return self::SUCCESS;
    }
}
