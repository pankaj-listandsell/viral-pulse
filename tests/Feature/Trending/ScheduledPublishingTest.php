<?php

namespace Tests\Feature\Trending;

use App\Enums\PostStatus;
use App\Enums\ScheduledPostStatus;
use App\Models\Post;
use App\Models\ScheduledPost;
use App\Models\User;
use App\Services\Trending\PublishWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduledPublishingTest extends TestCase
{
    use RefreshDatabase;

    private function scheduled(Carbon $at): ScheduledPost
    {
        $post = Post::factory()->create([
            'status' => PostStatus::Scheduled,
            'scheduled_at' => $at,
            'published_at' => null,
        ]);

        return ScheduledPost::factory()->create([
            'post_id' => $post->id,
            'scheduled_at' => $at,
        ]);
    }

    public function test_a_due_post_is_published_and_a_future_one_is_left_alone(): void
    {
        $due = $this->scheduled(now()->subMinutes(5));
        $later = $this->scheduled(now()->addHours(3));

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $due->refresh();
        $this->assertSame(ScheduledPostStatus::Published, $due->status);
        $this->assertSame(PostStatus::Published, $due->post->refresh()->status);
        $this->assertNotNull($due->post->published_at);
        // scheduled_at is cleared, so the post does not look scheduled and
        // published at the same time.
        $this->assertNull($due->post->scheduled_at);

        $this->assertSame(ScheduledPostStatus::Pending, $later->refresh()->status);
        $this->assertSame(PostStatus::Scheduled, $later->post->refresh()->status);
    }

    public function test_a_second_run_does_not_publish_the_same_post_twice(): void
    {
        $due = $this->scheduled(now()->subMinute());

        $this->artisan('posts:publish-scheduled')->assertSuccessful();
        $publishedAt = $due->refresh()->post->published_at;

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertSame(1, $due->refresh()->attempts);
        $this->assertEquals($publishedAt, $due->post->refresh()->published_at);
    }

    public function test_a_trashed_post_cancels_its_schedule_instead_of_failing_forever(): void
    {
        $row = $this->scheduled(now()->subMinute());

        // Soft delete, not force: a hard delete cascades the schedule row away,
        // so this branch only ever fires for a trashed post.
        $row->post->delete();

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertSame(ScheduledPostStatus::Cancelled, $row->refresh()->status);
    }

    public function test_the_admin_can_publish_early(): void
    {
        $admin = User::factory()->admin()->create();
        $row = $this->scheduled(now()->addDay());

        $this->actingAs($admin)
            ->post(route('admin.scheduled.publish', $row))
            ->assertSessionHasNoErrors();

        $this->assertSame(ScheduledPostStatus::Published, $row->refresh()->status);
        $this->assertSame(PostStatus::Published, $row->post->refresh()->status);
    }

    public function test_cancelling_returns_the_post_to_drafts(): void
    {
        $admin = User::factory()->admin()->create();
        $row = $this->scheduled(now()->addDay());

        $this->actingAs($admin)
            ->post(route('admin.scheduled.cancel', $row))
            ->assertSessionHasNoErrors();

        $this->assertSame(ScheduledPostStatus::Cancelled, $row->refresh()->status);
        $this->assertSame(PostStatus::Draft, $row->post->refresh()->status);
    }

    public function test_the_scheduled_screen_renders(): void
    {
        $this->scheduled(now()->addHour());

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.scheduled.index'))
            ->assertOk();
    }

    public function test_slots_stay_inside_the_publishing_window(): void
    {
        config([
            'trending.publishing.window_start' => '08:00',
            'trending.publishing.window_end' => '20:00',
            'trending.publishing.gap_minutes' => 60,
            'trending.publishing.max_per_day' => 8,
            'trending.publishing.lead_minutes' => 0,
        ]);

        // 03:00 is outside the window, so the first slot is the moment it opens.
        Carbon::setTestNow(today()->setTime(3, 0));

        $slot = app(PublishWindow::class)->nextSlot();

        $this->assertSame('08:00', $slot?->format('H:i'));

        Carbon::setTestNow();
    }

    public function test_a_slot_is_never_handed_out_in_the_past(): void
    {
        config([
            'trending.publishing.window_start' => '07:00',
            'trending.publishing.window_end' => '22:00',
            'trending.publishing.lead_minutes' => 15,
        ]);

        // Mid-morning, well after the window opens. This came out as 07:00 in
        // real use because the app was running in UTC while the window times
        // were written for IST, so "now" looked like 04:47 and the window had
        // not opened yet.
        Carbon::setTestNow(today()->setTime(10, 20));

        $slot = app(PublishWindow::class)->nextSlot();

        $this->assertNotNull($slot);
        $this->assertTrue($slot->isFuture(), "The slot {$slot} is in the past.");
        $this->assertSame('10:35', $slot->format('H:i'));

        Carbon::setTestNow();
    }

    public function test_the_application_timezone_comes_from_the_environment(): void
    {
        // config/app.php ships with 'timezone' => 'UTC' hardcoded, so
        // APP_TIMEZONE in .env is read by nothing until that is changed. For an
        // India-only audience that silently moved the whole publishing window
        // five and a half hours, into the middle of the night.
        $this->assertSame(
            env('APP_TIMEZONE', 'UTC'),
            config('app.timezone'),
            'config/app.php is not reading APP_TIMEZONE.'
        );
    }

    public function test_slots_in_one_run_are_spaced_by_the_configured_gap(): void
    {
        config([
            'trending.publishing.window_start' => '08:00',
            'trending.publishing.window_end' => '20:00',
            'trending.publishing.gap_minutes' => 90,
            'trending.publishing.max_per_day' => 8,
            'trending.publishing.lead_minutes' => 0,
        ]);

        Carbon::setTestNow(today()->setTime(9, 0));

        $slots = app(PublishWindow::class)->nextSlots(3);

        $this->assertCount(3, $slots);
        $this->assertSame(90, (int) $slots[0]->diffInMinutes($slots[1]));
        $this->assertSame(90, (int) $slots[1]->diffInMinutes($slots[2]));

        Carbon::setTestNow();
    }

    public function test_a_full_day_pushes_the_next_slot_to_tomorrow(): void
    {
        config([
            'trending.publishing.window_start' => '08:00',
            'trending.publishing.window_end' => '20:00',
            'trending.publishing.gap_minutes' => 60,
            'trending.publishing.max_per_day' => 2,
            'trending.publishing.lead_minutes' => 0,
        ]);

        Carbon::setTestNow(today()->setTime(9, 0));

        Post::factory()->count(2)->create([
            'status' => PostStatus::Published,
            'published_at' => today()->setTime(10, 0),
        ]);

        $slot = app(PublishWindow::class)->nextSlot();

        $this->assertTrue($slot?->isTomorrow());
        $this->assertSame('08:00', $slot->format('H:i'));

        Carbon::setTestNow();
    }
}
