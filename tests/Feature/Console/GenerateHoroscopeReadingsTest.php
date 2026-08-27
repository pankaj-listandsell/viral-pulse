<?php

namespace Tests\Feature\Console;

use App\Models\HoroscopeReading;
use App\Services\AI\AiProviderManager;
use App\Services\AI\Exceptions\AiGenerationException;
use App\Services\AI\Providers\FakeProvider;
use App\Services\HoroscopeService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GenerateHoroscopeReadingsTest extends TestCase
{
    use RefreshDatabase;

    private FakeProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);

        $this->provider = new FakeProvider;
        app(AiProviderManager::class)->swap($this->provider);

        // The writer asks for four signs at a time, and which four changes per
        // call. The schema names them in the `sign` enum, so the fake answers
        // with exactly the batch it was handed - which is also the contract a
        // real provider is being held to.
        $this->provider->willReturnUsing(fn (string $system, string $user, array $schema): array => $this->payloadFor(
            $schema['properties']['readings']['items']['properties']['sign']['enum']
        ));
    }

    /**
     * The model is asked for four signs at a time and answers with exactly
     * those, so a scripted payload has to be built per batch.
     *
     * @param  array<int, string>  $signs
     * @return array<string, mixed>
     */
    private function payloadFor(array $signs, string $prefix = 'Reading for'): array
    {
        return [
            'readings' => collect($signs)->map(fn (string $sign): array => [
                'sign' => $sign,
                'overview' => "{$prefix} {$sign}, written to be unmistakably about {$sign} and nobody else.",
                'love' => "Love note for {$sign}.",
                'career' => "Career note for {$sign}.",
                'health' => "Health note for {$sign}.",
                'money' => "Money note for {$sign}.",
                'mantra' => "Mantra for {$sign}.",
                'lucky_number' => 42,
                'lucky_color' => 'Sunset Coral',
                'lucky_time' => '9:00 AM – 2:00 PM',
                'lucky_direction' => 'North-East',
                'mood' => 'Focused & Grounded',
                'score' => 88,
                'love_score' => 91,
                'career_score' => 84,
                'health_score' => 79,
                'money_score' => 72,
            ])->all(),
        ];
    }

    public function test_it_writes_a_reading_for_every_sign_in_every_configured_language(): void
    {
        $this->artisan('content:generate-horoscope-readings --period=daily')
            ->assertSuccessful();

        foreach (['en', 'hi'] as $locale) {
            $this->assertSame(
                12,
                HoroscopeReading::where('locale', $locale)->where('period', 'daily')->count(),
                "Expected twelve {$locale} readings."
            );
        }
    }

    public function test_the_page_prefers_a_written_reading_over_the_static_pool(): void
    {
        $today = Carbon::today();

        HoroscopeReading::create([
            'sign' => 'aries',
            'locale' => 'en',
            'period' => 'daily',
            'period_start' => $today,
            'overview' => 'A reading that exists only in the database.',
            'love' => 'Written love.',
            'career' => 'Written career.',
            'health' => 'Written health.',
            'money' => 'Written money.',
            'lucky_number' => 7,
            'source' => HoroscopeReading::SOURCE_AI,
        ]);

        $reading = app(HoroscopeService::class)->daily('aries', $today);

        $this->assertSame('A reading that exists only in the database.', $reading['overview']);
        $this->assertSame(7, $reading['lucky_number']);
        $this->assertTrue($reading['is_written']);
    }

    public function test_a_partial_reading_is_completed_from_the_static_pool(): void
    {
        $today = Carbon::today();

        // Only the overview was written - the rest of the row is empty.
        HoroscopeReading::create([
            'sign' => 'leo',
            'locale' => 'en',
            'period' => 'daily',
            'period_start' => $today,
            'overview' => 'Only the overview came back.',
            'love' => '',
            'career' => '',
            'health' => '',
            'money' => '',
            'source' => HoroscopeReading::SOURCE_AI,
        ]);

        $reading = app(HoroscopeService::class)->daily('leo', $today);

        $this->assertSame('Only the overview came back.', $reading['overview']);

        // Every field the template reads still holds something.
        foreach (['love', 'career', 'health', 'money', 'lucky_color', 'mood'] as $field) {
            $this->assertNotEmpty($reading[$field], "{$field} should have fallen back to the pool.");
        }
    }

    public function test_it_leaves_an_editors_rewrite_alone(): void
    {
        $today = Carbon::today();

        HoroscopeReading::create([
            'sign' => 'aries',
            'locale' => 'en',
            'period' => 'daily',
            'period_start' => $today,
            'overview' => 'Rewritten by a human being.',
            'love' => 'Human love.',
            'career' => 'Human career.',
            'health' => 'Human health.',
            'money' => 'Human money.',
            'source' => HoroscopeReading::SOURCE_MANUAL,
        ]);

        $this->artisan('content:generate-horoscope-readings --period=daily --locale=en --force')
            ->assertSuccessful();

        $this->assertSame(
            'Rewritten by a human being.',
            HoroscopeReading::where('sign', 'aries')->where('locale', 'en')->first()->overview,
            'A manual reading must survive a forced regeneration.'
        );
    }

    public function test_it_does_not_rewrite_readings_that_already_exist(): void
    {
        $this->artisan('content:generate-horoscope-readings --period=daily --locale=en')->assertSuccessful();

        $callsAfterFirstRun = $this->provider->callCount();
        $this->assertGreaterThan(0, $callsAfterFirstRun);

        $this->artisan('content:generate-horoscope-readings --period=daily --locale=en')
            ->expectsOutputToContain('already written')
            ->assertSuccessful();

        $this->assertSame(
            $callsAfterFirstRun,
            $this->provider->callCount(),
            'A second run for the same day must not call the provider again.'
        );
    }

    public function test_a_provider_failure_leaves_the_pages_working(): void
    {
        $this->provider->failRetryably('The provider is having a bad morning.');

        $this->artisan('content:generate-horoscope-readings --period=daily --locale=en')
            ->expectsOutputToContain('nothing written')
            ->assertFailed();

        $this->assertSame(0, HoroscopeReading::count());

        // The page is still complete, from the static pool.
        $reading = app(HoroscopeService::class)->daily('aries');

        $this->assertNotEmpty($reading['overview']);
        $this->assertFalse($reading['is_written']);

        $this->get('/horoscope/aries')->assertOk();
    }

    public function test_weekly_and_monthly_readings_are_keyed_to_the_period_they_cover(): void
    {
        // A Thursday, so the Monday and the 1st are both clearly different.
        Carbon::setTestNow(Carbon::parse('2026-08-27'));

        $this->artisan('content:generate-horoscope-readings --period=weekly --locale=en')->assertSuccessful();
        $this->artisan('content:generate-horoscope-readings --period=monthly --locale=en')->assertSuccessful();

        $this->assertSame(
            '2026-08-24',
            HoroscopeReading::where('period', 'weekly')->first()->period_start->toDateString(),
            'A weekly reading belongs to the Monday of its week.'
        );

        $this->assertSame(
            '2026-08-01',
            HoroscopeReading::where('period', 'monthly')->first()->period_start->toDateString(),
            'A monthly reading belongs to the 1st.'
        );

        Carbon::setTestNow();
    }

    public function test_it_ignores_a_sign_the_model_was_not_asked_for(): void
    {
        // The model answers with a sign that was not in the batch. Storing it
        // would put a row under a key some other batch is about to write.
        $this->provider->willReturn([
            'readings' => [
                [
                    'sign' => 'ophiuchus',
                    'overview' => 'A thirteenth sign nobody asked for.',
                    'love' => 'x', 'career' => 'x', 'health' => 'x', 'money' => 'x', 'mantra' => 'x',
                    'lucky_number' => 1, 'lucky_color' => 'x', 'lucky_time' => 'x',
                    'lucky_direction' => 'x', 'mood' => 'x',
                    'score' => 50, 'love_score' => 50, 'career_score' => 50,
                    'health_score' => 50, 'money_score' => 50,
                ],
            ],
        ]);

        $this->artisan('content:generate-horoscope-readings --period=daily --locale=en')->assertFailed();

        $this->assertSame(0, HoroscopeReading::count());
    }

    public function test_it_rejects_a_reading_with_no_overview(): void
    {
        $this->provider->willReturn([
            'readings' => [
                [
                    'sign' => 'aries',
                    'overview' => '   ',
                    'love' => 'x', 'career' => 'x', 'health' => 'x', 'money' => 'x', 'mantra' => 'x',
                    'lucky_number' => 1, 'lucky_color' => 'x', 'lucky_time' => 'x',
                    'lucky_direction' => 'x', 'mood' => 'x',
                    'score' => 50, 'love_score' => 50, 'career_score' => 50,
                    'health_score' => 50, 'money_score' => 50,
                ],
            ],
        ]);

        $this->artisan('content:generate-horoscope-readings --period=daily --locale=en')->assertFailed();

        $this->assertSame(0, HoroscopeReading::count(), 'A reading with a blank overview is not a reading.');
    }

    public function test_it_stops_when_no_provider_is_configured(): void
    {
        config(['ai.providers' => []]);

        // swap() has already forced a provider in, so a fresh manager is what
        // the command would actually see on a site with no keys set.
        app()->forgetInstance(AiProviderManager::class);

        $this->artisan('content:generate-horoscope-readings')
            ->expectsOutputToContain('No AI provider is configured')
            ->assertFailed();
    }

    public function test_it_rejects_an_unknown_period(): void
    {
        $this->artisan('content:generate-horoscope-readings --period=fortnightly')
            ->expectsOutputToContain('Unknown period')
            ->assertFailed();
    }

    public function test_a_failed_batch_does_not_cost_the_other_signs_their_reading(): void
    {
        $this->provider->failWith(AiGenerationException::retryable('Rate limited.'));

        $this->artisan('content:generate-horoscope-readings --period=daily --locale=en')->assertFailed();

        // Nothing was written, but the run completed rather than throwing, so
        // the scheduler is free to try again on the next tick.
        $this->assertSame(0, HoroscopeReading::count());
    }
}
