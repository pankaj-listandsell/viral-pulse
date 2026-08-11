<?php

namespace Tests\Feature\Trending;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostDailyStat;
use App\Models\User;
use App\Support\Fingerprint;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MaintenanceCommandsTest extends TestCase
{
    use RefreshDatabase;

    private function recordView(Post $post, string $ip, \DateTimeInterface $at): void
    {
        DB::table('post_views')->insert([
            'post_id' => $post->id,
            'ip_hash' => Fingerprint::ip($ip),
            'viewed_at' => $at,
        ]);
    }

    public function test_views_are_rolled_up_into_daily_stats(): void
    {
        $post = Post::factory()->create();
        $yesterday = today()->subDay()->setTime(12, 0);

        $this->recordView($post, '10.0.0.1', $yesterday);
        $this->recordView($post, '10.0.0.1', $yesterday->copy()->addHour());
        $this->recordView($post, '10.0.0.2', $yesterday->copy()->addHours(2));
        // A view from a different day must not leak into yesterday's row.
        $this->recordView($post, '10.0.0.3', today()->subDays(4));

        $this->artisan('stats:aggregate')->assertSuccessful();

        $stat = PostDailyStat::where('post_id', $post->id)
            ->whereDate('date', $yesterday->toDateString())
            ->sole();

        $this->assertSame(3, $stat->views);
        $this->assertSame(2, $stat->unique_views);
    }

    public function test_rolling_up_the_same_day_twice_does_not_double_count(): void
    {
        $post = Post::factory()->create();
        $this->recordView($post, '10.0.0.1', today()->subDay()->setTime(9, 0));

        $this->artisan('stats:aggregate')->assertSuccessful();
        $this->artisan('stats:aggregate')->assertSuccessful();

        $this->assertSame(1, PostDailyStat::where('post_id', $post->id)->count());
        $this->assertSame(1, PostDailyStat::where('post_id', $post->id)->sole()->views);
    }

    public function test_cleanup_prunes_old_analytics_but_keeps_recent_ones(): void
    {
        $post = Post::factory()->create();

        $this->recordView($post, '10.0.0.1', now()->subDays(400));
        $this->recordView($post, '10.0.0.2', now()->subDay());

        $this->artisan('data:cleanup')->assertSuccessful();

        $this->assertSame(1, DB::table('post_views')->where('post_id', $post->id)->count());
    }

    public function test_a_dry_run_deletes_nothing(): void
    {
        $post = Post::factory()->create();
        $this->recordView($post, '10.0.0.1', now()->subDays(400));

        $this->artisan('data:cleanup --dry-run')->assertSuccessful();

        $this->assertSame(1, DB::table('post_views')->where('post_id', $post->id)->count());
    }

    public function test_counters_are_recalculated_from_the_source_tables(): void
    {
        $post = Post::factory()->create(['status' => PostStatus::Published, 'views_count' => 999]);

        $this->recordView($post, '10.0.0.1', now());
        $this->recordView($post, '10.0.0.2', now());

        $this->artisan('content:reconcile-counters')->assertSuccessful();

        $this->assertSame(2, $post->refresh()->views_count);
        $this->assertSame(1, $post->category->refresh()->posts_count);
    }

    public function test_a_soft_deleted_post_is_not_counted_towards_its_category(): void
    {
        $post = Post::factory()->create(['status' => PostStatus::Published]);
        $category = $post->category;

        $post->delete();

        $this->artisan('content:reconcile-counters')->assertSuccessful();

        $this->assertSame(0, $category->refresh()->posts_count);
    }

    public function test_the_maintenance_commands_are_scheduled(): void
    {
        $commands = collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->command)
            ->implode(' ');

        // The whole automation story depends on schedule:run actually calling
        // these, so a silent rename would be a real outage.
        foreach (['posts:publish-scheduled', 'trending:fetch', 'content:generate-trending', 'stats:aggregate', 'data:cleanup'] as $command) {
            $this->assertStringContainsString($command, $commands);
        }
    }

    public function test_only_an_admin_can_trigger_a_feed_fetch(): void
    {
        $this->post(route('admin.trending.fetch'))->assertRedirect(route('login'));

        $reader = User::factory()->create(['is_admin' => false]);

        $this->actingAs($reader)->post(route('admin.trending.fetch'))->assertForbidden();
    }
}
