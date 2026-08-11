<?php

namespace Tests\Feature\Admin;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\ScheduledPost;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->admin = User::factory()->admin()->create();
        $this->category = Category::factory()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Bengaluru metro expansion explained',
            'content' => '<p>'.str_repeat('Some genuinely useful sentence. ', 20).'</p>',
            'excerpt' => 'A short summary of the story.',
            'category_id' => $this->category->id,
            'status' => PostStatus::Draft->value,
            'language' => 'en',
            'tags' => ['Metro', 'Explained'],
        ], $overrides);
    }

    public function test_the_index_lists_posts(): void
    {
        Post::factory()->create(['title' => 'A findable headline']);

        $this->actingAs($this->admin)
            ->get(route('admin.posts.index'))
            ->assertOk()
            ->assertSee('A findable headline');
    }

    public function test_the_index_filters_by_status(): void
    {
        Post::factory()->create(['title' => 'Published story']);
        Post::factory()->draft()->create(['title' => 'Draft story']);

        $this->actingAs($this->admin)
            ->get(route('admin.posts.index', ['status' => 'draft']))
            ->assertOk()
            ->assertSee('Draft story')
            ->assertDontSee('Published story');
    }

    public function test_a_post_can_be_created(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.posts.store'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $post = Post::firstWhere('title', 'Bengaluru metro expansion explained');

        $this->assertNotNull($post);
        $this->assertSame('bengaluru-metro-expansion-explained', $post->slug);
        $this->assertSame($this->admin->id, $post->author_id);
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertCount(2, $post->tags);
    }

    public function test_the_slug_is_generated_from_the_title_and_made_unique(): void
    {
        Post::factory()->create(['slug' => 'shared-headline']);

        $this->actingAs($this->admin)
            ->post(route('admin.posts.store'), $this->validPayload(['title' => 'Shared headline']));

        $this->assertDatabaseHas('posts', ['slug' => 'shared-headline-2']);
    }

    public function test_reading_time_is_derived_from_the_body(): void
    {
        // 600 words at 200 words per minute is three minutes.
        $content = '<p>'.str_repeat('word ', 600).'</p>';

        $this->actingAs($this->admin)
            ->post(route('admin.posts.store'), $this->validPayload(['content' => $content]));

        $this->assertSame(3, Post::latest('id')->first()->reading_time);
    }

    public function test_hostile_markup_is_stripped_before_the_post_is_stored(): void
    {
        $this->actingAs($this->admin)->post(route('admin.posts.store'), $this->validPayload([
            'content' => '<p>Real content here that is long enough to pass.</p>'
                .'<script>alert(1)</script>'
                .'<img src=x onerror=alert(2)>'
                .'<a href="javascript:alert(3)">click</a>'
                .'<iframe src="//evil.test"></iframe>',
        ]));

        $content = Post::latest('id')->first()->content;

        $this->assertStringNotContainsString('<script', $content);
        $this->assertStringNotContainsString('onerror', $content);
        $this->assertStringNotContainsString('javascript:', $content);
        $this->assertStringNotContainsString('<iframe', $content);
        $this->assertStringContainsString('Real content here', $content);
    }

    public function test_a_post_can_be_updated(): void
    {
        $post = Post::factory()->create(['category_id' => $this->category->id]);

        $this->actingAs($this->admin)
            ->put(route('admin.posts.update', $post), $this->validPayload(['title' => 'A revised headline']))
            ->assertSessionHasNoErrors();

        $this->assertSame('A revised headline', $post->fresh()->title);
    }

    public function test_publishing_sets_a_published_date(): void
    {
        $post = Post::factory()->draft()->create();

        $this->actingAs($this->admin)->post(route('admin.posts.publish', $post));

        $post->refresh();

        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertNotNull($post->published_at);
    }

    public function test_unpublishing_clears_the_published_date(): void
    {
        $post = Post::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.posts.unpublish', $post));

        $post->refresh();

        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNull($post->published_at);
    }

    public function test_scheduling_creates_a_queue_row_for_the_publisher_command(): void
    {
        $post = Post::factory()->draft()->create();
        $at = now()->addDays(2);

        $this->actingAs($this->admin)->post(route('admin.posts.schedule', $post), [
            'scheduled_at' => $at->format('Y-m-d\TH:i'),
        ])->assertSessionHasNoErrors();

        $post->refresh();

        $this->assertSame(PostStatus::Scheduled, $post->status);
        $this->assertNull($post->published_at);
        $this->assertDatabaseHas('scheduled_posts', [
            'post_id' => $post->id,
            'status' => 'pending',
        ]);
    }

    public function test_rescheduling_cancels_the_previous_queue_row_instead_of_double_publishing(): void
    {
        $post = Post::factory()->draft()->create();

        $this->actingAs($this->admin)->post(route('admin.posts.schedule', $post), [
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ]);
        $this->actingAs($this->admin)->post(route('admin.posts.schedule', $post), [
            'scheduled_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
        ]);

        $this->assertSame(1, ScheduledPost::where('post_id', $post->id)->where('status', 'pending')->count());
        $this->assertSame(1, ScheduledPost::where('post_id', $post->id)->where('status', 'cancelled')->count());
    }

    public function test_a_scheduled_post_must_have_a_future_date(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.posts.store'), $this->validPayload([
                'status' => PostStatus::Scheduled->value,
                'scheduled_at' => now()->subDay()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('scheduled_at');
    }

    public function test_choosing_scheduled_without_a_date_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.posts.store'), $this->validPayload([
                'status' => PostStatus::Scheduled->value,
                'scheduled_at' => null,
            ]))
            ->assertSessionHasErrors('scheduled_at');
    }

    public function test_publishing_with_a_future_date_becomes_a_schedule_rather_than_going_live(): void
    {
        $this->actingAs($this->admin)->post(route('admin.posts.store'), $this->validPayload([
            'status' => PostStatus::Published->value,
            'published_at' => now()->addWeek()->format('Y-m-d\TH:i'),
        ]));

        $post = Post::latest('id')->first();

        $this->assertSame(PostStatus::Scheduled, $post->status);
        $this->assertNull($post->published_at);
        $this->assertNotNull($post->scheduled_at);
    }

    public function test_duplicating_produces_an_unpublished_copy_with_its_own_slug(): void
    {
        $post = Post::factory()->create(['views_count' => 500]);
        $post->tags()->attach(Tag::factory()->count(2)->create());

        $this->actingAs($this->admin)->post(route('admin.posts.duplicate', $post));

        $copy = Post::latest('id')->first();

        $this->assertNotSame($post->slug, $copy->slug);
        $this->assertSame(PostStatus::Draft, $copy->status);
        $this->assertSame(0, $copy->views_count);
        $this->assertNull($copy->published_at);
        $this->assertCount(2, $copy->tags);
    }

    public function test_deleting_moves_a_post_to_the_trash_and_it_can_be_restored(): void
    {
        $post = Post::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.posts.destroy', $post));
        $this->assertSoftDeleted($post);

        $this->actingAs($this->admin)->post(route('admin.posts.restore', $post->id));
        $this->assertNotSoftDeleted($post);
    }

    public function test_the_category_post_count_follows_publication_state(): void
    {
        $this->actingAs($this->admin)->post(route('admin.posts.store'), $this->validPayload([
            'status' => PostStatus::Published->value,
        ]));

        $this->assertSame(1, $this->category->fresh()->posts_count);

        $this->actingAs($this->admin)->post(route('admin.posts.unpublish', Post::latest('id')->first()));

        $this->assertSame(0, $this->category->fresh()->posts_count);
    }

    public function test_bulk_publishing_updates_every_selected_post(): void
    {
        $posts = Post::factory()->count(3)->draft()->create();

        $this->actingAs($this->admin)->post(route('admin.posts.bulk'), [
            'action' => 'publish',
            'ids' => $posts->pluck('id')->all(),
        ])->assertSessionHasNoErrors();

        $this->assertSame(3, Post::published()->count());
    }

    public function test_validation_rejects_an_empty_post(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.posts.store'), [])
            ->assertSessionHasErrors(['title', 'content', 'category_id', 'status']);
    }

    public function test_a_malformed_slug_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.posts.store'), $this->validPayload(['slug' => 'Not A Valid Slug!']))
            ->assertSessionHasErrors('slug');
    }

    public function test_a_reader_cannot_reach_any_post_route(): void
    {
        $reader = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($reader)->get(route('admin.posts.index'))->assertForbidden();
        $this->actingAs($reader)->get(route('admin.posts.create'))->assertForbidden();
        $this->actingAs($reader)->post(route('admin.posts.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($reader)->post(route('admin.posts.publish', $post))->assertForbidden();
        $this->actingAs($reader)->delete(route('admin.posts.destroy', $post))->assertForbidden();
    }

    public function test_guests_are_redirected_away_from_post_routes(): void
    {
        $this->get(route('admin.posts.index'))->assertRedirect(route('login'));
        $this->post(route('admin.posts.store'), $this->validPayload())->assertRedirect(route('login'));
    }
}
