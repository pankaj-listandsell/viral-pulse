<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostDailyStat;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_the_dashboard_counts_posts_by_status(): void
    {
        Post::factory()->count(3)->create();
        Post::factory()->count(2)->draft()->create();
        Post::factory()->scheduled()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $stats = $response->viewData('stats');

        $this->assertSame(6, $stats['total_posts']);
        $this->assertSame(3, $stats['published_posts']);
        $this->assertSame(2, $stats['draft_posts']);
        $this->assertSame(1, $stats['scheduled_posts']);
    }

    public function test_charts_include_a_point_for_every_day_including_quiet_ones(): void
    {
        Post::factory()->create(['published_at' => now()->subDays(3)]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $points = $response->viewData('postsPerDay');

        $this->assertCount(30, $points, 'A 30 day window must produce 30 points.');
        $this->assertSame(1, collect($points)->sum('total'));
        $this->assertSame(now()->toDateString(), collect($points)->last()['date']);
    }

    public function test_views_per_day_reads_the_rollup_table(): void
    {
        $post = Post::factory()->create();
        PostDailyStat::create([
            'post_id' => $post->id,
            'date' => now()->subDay()->toDateString(),
            'views' => 42,
            'unique_views' => 30,
        ]);

        $points = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->viewData('viewsPerDay');

        $this->assertSame(42, collect($points)->sum('total'));
    }

    public function test_the_pending_comment_banner_only_appears_when_there_is_something_to_moderate(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertDontSee('waiting for moderation');

        Comment::factory()->count(2)->create();

        $stats = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->viewData('stats');

        $this->assertSame(2, $stats['pending_comments']);
    }

    public function test_top_categories_only_count_published_posts(): void
    {
        $category = Category::factory()->create();
        Post::factory()->count(2)->for($category)->create();
        Post::factory()->for($category)->draft()->create();

        $categories = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->viewData('topCategories');

        $this->assertSame(2, $categories->firstWhere('id', $category->id)->posts_count);
    }

    public function test_the_dashboard_loads_relations_eagerly(): void
    {
        Post::factory()->count(5)->create();

        // Model::shouldBeStrict() is on outside production, so any lazy load in
        // the dashboard queries would throw here rather than pass silently.
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
    }
}
