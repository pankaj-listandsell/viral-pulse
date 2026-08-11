<?php

namespace Tests\Feature\Database;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Role;
use App\Models\Tag;
use App\Models\TrendingTopic;
use App\Models\User;
use Database\Seeders\RoleSeeder;
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
        $this->assertTrue($parent->children->first()->parent->is($parent));
        $this->assertSame(1, Category::roots()->count());
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

    public function test_a_user_reports_its_role_correctly(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $reader = User::factory()->withRole(Role::USER)->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->canAccessAdminPanel());
        $this->assertFalse($reader->isAdmin());
        $this->assertFalse($reader->canAccessAdminPanel());
    }

    public function test_soft_deleted_posts_disappear_from_normal_queries(): void
    {
        $post = Post::factory()->create();

        $post->delete();

        $this->assertSame(0, Post::count());
        $this->assertSame(1, Post::withTrashed()->count());
    }
}
