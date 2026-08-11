<?php

namespace Tests\Feature\Trending;

use App\Models\Category;
use App\Services\Trending\CategoryGuesser;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryRoutingTest extends TestCase
{
    use RefreshDatabase;

    private CategoryGuesser $guesser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);

        $this->guesser = app(CategoryGuesser::class);
    }

    private function slugFor(string $topic, ?string $description = null): ?string
    {
        $id = $this->guesser->guess($topic, $description);

        return $id ? Category::find($id)?->slug : null;
    }

    public function test_the_headline_decides_the_category_not_the_feed_summary(): void
    {
        // Real regression: a Pixel launch was filed under Sports because the
        // feed summary happened to contain a sports keyword.
        $slug = $this->slugFor(
            'Google Pixel 11 Pro launched with a new processor',
            'Coverage continues after the match, with a full score card and league table.',
        );

        $this->assertSame('technology', $slug);
    }

    public function test_the_summary_is_only_consulted_when_the_headline_says_nothing(): void
    {
        $slug = $this->slugFor(
            'Everything you need to know before tomorrow',
            'The Sensex and Nifty both closed lower as the rupee weakened.',
        );

        $this->assertSame('business', $slug);
    }

    public function test_the_category_with_the_most_keyword_hits_wins(): void
    {
        // "wedding" is a lifestyle keyword and appears first in config, but the
        // headline is plainly about the election.
        $slug = $this->slugFor('Election result and the new minister sworn in after a wedding season delay');

        $this->assertSame('news', $slug);
    }

    public function test_an_unrecognised_topic_falls_back_rather_than_being_dropped(): void
    {
        $this->assertSame(
            config('trending.fallback_category'),
            $this->slugFor('Something entirely without a keyword in it'),
        );
    }

    public function test_a_keyword_only_matches_on_a_word_boundary(): void
    {
        // "ai" must not fire on "said", "ram" must not fire on "program".
        $this->assertSame(
            config('trending.fallback_category'),
            $this->slugFor('He said the program would continue as before'),
        );
    }
}
