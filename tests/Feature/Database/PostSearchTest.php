<?php

namespace Tests\Feature\Database;

use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * Search tests cannot use RefreshDatabase.
 *
 * RefreshDatabase wraps each test in a transaction that is rolled back at the
 * end, and InnoDB does not update a FULLTEXT index until the writing
 * transaction commits - so MATCH ... AGAINST would never see rows created
 * inside the test and every assertion would fail for the wrong reason.
 * Truncation-based isolation commits its writes, so the index is real.
 */
class PostSearchTest extends TestCase
{
    use DatabaseTruncation;

    public function test_full_text_search_finds_a_post_by_its_title(): void
    {
        Post::factory()->create(['title' => 'Bengaluru metro expansion explained']);
        Post::factory()->create(['title' => 'Monsoon travel guide for Kerala']);

        $this->assertSame(1, Post::search('Bengaluru')->count());
    }

    public function test_full_text_search_matches_on_body_content(): void
    {
        Post::factory()->create([
            'title' => 'Weekend roundup',
            'content' => '<p>The Chandrayaan mission timeline was updated this week.</p>',
        ]);
        Post::factory()->create(['title' => 'Unrelated story', 'content' => '<p>Nothing to see.</p>']);

        $this->assertSame(1, Post::search('Chandrayaan')->count());
    }

    public function test_all_terms_must_match_rather_than_any(): void
    {
        Post::factory()->create(['title' => 'Kerala monsoon travel guide']);
        Post::factory()->create(['title' => 'Rajasthan winter travel guide']);

        $this->assertSame(2, Post::search('travel guide')->count());
        $this->assertSame(1, Post::search('Kerala travel')->count());
    }

    public function test_short_terms_fall_back_to_like_instead_of_returning_nothing(): void
    {
        Post::factory()->create(['title' => 'AI is everywhere now']);

        // "AI" is shorter than innodb_ft_min_token_size, so a FULLTEXT-only
        // implementation would silently return zero results here.
        $this->assertSame(1, Post::search('AI')->count());
    }

    public function test_boolean_mode_operators_in_user_input_are_neutralised(): void
    {
        Post::factory()->create(['title' => 'Monsoon travel guide']);

        // A raw "+-><()~*" payload must not throw a syntax error or change the
        // meaning of the query.
        $this->assertSame(1, Post::search('+monsoon -travel* (guide)')->count());
    }

    public function test_an_empty_search_term_does_not_filter_anything(): void
    {
        Post::factory()->count(3)->create();

        $this->assertSame(3, Post::search('   ')->count());
    }
}
