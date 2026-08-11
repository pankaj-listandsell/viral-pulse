<?php

namespace Tests\Feature\Database;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guard rail. RefreshDatabase drops every table it touches, so if the test
     * suite is ever pointed back at the live database this fails loudly before
     * any data is lost.
     */
    public function test_tests_never_run_against_the_live_database(): void
    {
        $database = DB::connection()->getDatabaseName();

        $this->assertSame('viral_plush_test', $database);
        $this->assertNotSame('viral_plush', $database);
    }

    public function test_every_expected_table_exists(): void
    {
        $tables = [
            'roles', 'users', 'categories', 'tags', 'media', 'posts', 'post_tag',
            'trending_topics', 'ai_generations', 'scheduled_posts', 'post_views',
            'post_daily_stats', 'post_likes', 'comments', 'settings', 'seo_meta',
            'contact_messages', 'newsletter_subscribers', 'activity_logs',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_posts_carry_the_indexes_the_public_queries_depend_on(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM posts'))->pluck('Key_name')->unique();

        foreach ([
            'posts_slug_unique',
            'posts_status_published_at_index',
            'posts_category_id_status_published_at_index',
            'posts_status_scheduled_at_index',
            'posts_search_fulltext',
        ] as $index) {
            $this->assertContains($index, $indexes->all(), "Missing index: {$index}");
        }
    }

    public function test_post_slugs_are_unique(): void
    {
        Post::factory()->create(['slug' => 'a-viral-story']);

        $this->expectException(UniqueConstraintViolationException::class);

        Post::factory()->create(['slug' => 'a-viral-story']);
    }

    public function test_deleting_a_post_cascades_to_its_tag_pivot_rows(): void
    {
        $post = Post::factory()->create();
        $post->tags()->attach(Tag::factory()->create());

        $this->assertDatabaseCount('post_tag', 1);

        $post->forceDelete();

        $this->assertDatabaseCount('post_tag', 0);
    }

    public function test_an_author_cannot_be_deleted_while_they_still_have_posts(): void
    {
        $post = Post::factory()->create();

        $this->expectException(QueryException::class);

        // Soft deleting is fine; a hard delete must be blocked so no post is
        // ever left without a real byline.
        $post->author->forceDelete();
    }
}
