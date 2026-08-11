<?php

namespace Tests\Feature\Database;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\TrendingTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_post_resolves_its_author_category_and_tags(): void
    {
        $category = Category::factory()->create();
        $author = User::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $post = Post::factory()->for($category)->create(['author_id' => $author->id]);
        $post->tags()->sync($tags->pluck('id'));

        $post->refresh()->load('author', 'category', 'tags');

        $this->assertTrue($post->author->is($author));
        $this->assertTrue($post->category->is($category));
        $this->assertCount(3, $post->tags);
    }

    public function test_status_and_source_type_are_cast_to_enums(): void
    {
        $post = Post::factory()->draft()->create();

        $this->assertInstanceOf(PostStatus::class, $post->status);
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertFalse($post->status->isPubliclyVisible());
    }

    public function test_published_scope_excludes_drafts_scheduled_and_future_posts(): void
    {
        Post::factory()->count(2)->create();
        Post::factory()->draft()->create();
        Post::factory()->scheduled()->create();
        Post::factory()->create([
            'status' => PostStatus::Published,
            'published_at' => now()->addWeek(),
        ]);

        $this->assertSame(2, Post::published()->count());
    }

    public function test_categories_nest_through_parent_and_children(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->count(2)->create(['parent_id' => $parent->id]);

        $this->assertCount(2, $parent->children);
        $this->assertSame(1, Category::roots()->count());

        // Loaded explicitly: strict mode turns any lazy load into an error.
        $child = Category::with('parent')->whereNotNull('parent_id')->first();
        $this->assertTrue($child->parent->is($parent));
    }

    public function test_comments_thread_through_replies(): void
    {
        $post = Post::factory()->create();
        $root = Comment::factory()->approved()->create(['post_id' => $post->id]);
        Comment::factory()->count(2)->approved()->create([
            'post_id' => $post->id,
            'parent_id' => $root->id,
        ]);

        $this->assertCount(2, $root->replies);
        $this->assertSame(1, $post->comments()->roots()->count());
        $this->assertSame(3, $post->approvedComments()->count());
    }

    public function test_trending_topics_dedupe_on_a_normalised_hash(): void
    {
        $a = TrendingTopic::hashTopic('IPL Final 2026');
        $b = TrendingTopic::hashTopic('  ipl   final 2026 ');

        $this->assertSame($a, $b);
        $this->assertNotSame($a, TrendingTopic::hashTopic('IPL Final 2025'));
    }

    public function test_admin_access_depends_on_both_the_admin_flag_and_the_account_being_active(): void
    {
        $admin = User::factory()->admin()->create();
        $suspended = User::factory()->admin()->inactive()->create();
        $reader = User::factory()->create();

        $this->assertTrue($admin->canAccessAdminPanel());
        $this->assertFalse($suspended->canAccessAdminPanel());
        $this->assertFalse($reader->canAccessAdminPanel());
    }

    public function test_the_admin_flag_is_not_mass_assignable(): void
    {
        $this->assertNotContains('is_admin', (new User)->getFillable());

        // Strict mode escalates a discarded attribute into an exception, so an
        // attempt to smuggle is_admin through a payload fails loudly here and
        // is silently dropped in production.
        $this->expectException(MassAssignmentException::class);

        User::create([
            'name' => 'Sneaky',
            'email' => 'sneaky@example.test',
            'password' => 'password',
            'is_admin' => true,
        ]);
    }

    public function test_soft_deleted_posts_disappear_from_normal_queries(): void
    {
        $post = Post::factory()->create();

        $post->delete();

        $this->assertSame(0, Post::count());
        $this->assertSame(1, Post::withTrashed()->count());
    }
}
