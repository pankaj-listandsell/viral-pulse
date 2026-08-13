<?php

namespace Tests\Feature\Admin;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsConfigBridge;
use App\Services\SettingsService;
use App\Services\Trending\PublishWindow;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The publishing times an admin sets are only real if two things hold: the
 * setting overrides the environment, and the scheduler uses those exact times.
 */
class PublishingSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->admin = User::factory()->admin()->create();

        // These tests are about which slot is picked. The lookahead guard is a
        // separate rule with its own test, so it is switched off here.
        //
        // Stored rather than set with config(): the bridge reapplies the stored
        // settings on every store() call, so a bare config() would be undone by
        // the next line of the test.
        $this->store('publish_lookahead_hours', '0');
    }

    private function store(string $key, string $value): void
    {
        Setting::where('key', $key)->update(['value' => $value]);
        app(SettingsService::class)->flush();
        app(SettingsConfigBridge::class)->apply();
    }

    public function test_a_stored_setting_overrides_the_environment(): void
    {
        config(['trending.automation.enabled' => false]);

        // The switch used to write a row nobody read, so the admin toggled it
        // and nothing whatsoever changed.
        $this->store('ai_auto_generate', '1');

        $this->assertTrue((bool) config('trending.automation.enabled'));
    }

    public function test_an_empty_setting_leaves_the_environment_in_charge(): void
    {
        config(['ai.daily_limit' => 50]);

        $this->store('ai_daily_limit', '');

        // Clearing a field means "no opinion", not "zero" - a zeroed limit
        // would quietly stop all generation.
        $this->assertSame(50, config('ai.daily_limit'));
    }

    public function test_articles_are_scheduled_at_the_configured_times(): void
    {
        Carbon::setTestNow(today()->setTime(9, 0));

        $this->store('publish_slots', '08:00, 13:00, 19:00');

        $slots = app(PublishWindow::class)->nextSlots(3);

        // 08:00 has passed, so the run starts at the next listed time.
        $this->assertSame('13:00', $slots[0]->format('H:i'));
        $this->assertSame('19:00', $slots[1]->format('H:i'));
        // The day's times are used up, so it rolls to tomorrow's first.
        $this->assertSame('08:00', $slots[2]->format('H:i'));
        $this->assertTrue($slots[2]->isTomorrow());

        Carbon::setTestNow();
    }

    public function test_times_are_accepted_in_any_order_or_spacing(): void
    {
        Carbon::setTestNow(today()->setTime(6, 0));

        $this->store('publish_slots', '19:00,8:00 ,  13:00');

        $slots = app(PublishWindow::class)->nextSlots(3);

        $this->assertSame(['08:00', '13:00', '19:00'], array_map(fn ($s) => $s->format('H:i'), $slots));

        Carbon::setTestNow();
    }

    public function test_a_time_already_taken_that_day_is_not_reused(): void
    {
        Carbon::setTestNow(today()->setTime(6, 0));

        Post::factory()->create([
            'status' => PostStatus::Scheduled,
            'scheduled_at' => today()->setTime(13, 0),
            'published_at' => null,
        ]);

        $this->store('publish_slots', '08:00, 13:00, 19:00');

        $slots = app(PublishWindow::class)->nextSlots(2);

        // 13:00 is spoken for, so it is skipped rather than doubled up.
        $this->assertSame(['08:00', '19:00'], array_map(fn ($s) => $s->format('H:i'), $slots));

        Carbon::setTestNow();
    }

    public function test_the_daily_maximum_still_applies(): void
    {
        Carbon::setTestNow(today()->setTime(6, 0));

        $this->store('publish_slots', '08:00, 13:00, 19:00');
        $this->store('publish_max_per_day', '2');

        $slots = app(PublishWindow::class)->nextSlots(3);

        $this->assertSame('08:00', $slots[0]->format('H:i'));
        $this->assertSame('13:00', $slots[1]->format('H:i'));
        // Two a day is the cap, so the third waits for tomorrow.
        $this->assertTrue($slots[2]->isTomorrow());

        Carbon::setTestNow();
    }

    public function test_no_times_falls_back_to_even_spacing(): void
    {
        Carbon::setTestNow(today()->setTime(9, 0));

        $this->store('publish_slots', '');
        config([
            'trending.publishing.window_start' => '07:00',
            'trending.publishing.window_end' => '22:00',
            'trending.publishing.gap_minutes' => 90,
            'trending.publishing.lead_minutes' => 0,
        ]);

        $slots = app(PublishWindow::class)->nextSlots(2);

        $this->assertSame('09:00', $slots[0]->format('H:i'));
        $this->assertSame(90, (int) $slots[0]->diffInMinutes($slots[1]));

        Carbon::setTestNow();
    }

    public function test_the_screen_saves_a_list_of_times(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.update'), [
                'group' => 'publishing',
                'publish_mode' => 'scheduled',
                'publish_lookahead_hours' => 3,
                'publish_slots' => '07:30, 12:00, 18:45',
                'publish_max_per_day' => 6,
                'publish_lead_minutes' => 15,
                'trending_generate_per_run' => 2,
                'trending_min_score' => 45,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'publishing']));

        $this->assertSame('07:30, 12:00, 18:45', app(SettingsService::class)->get('publish_slots'));
    }

    public function test_a_typo_in_the_times_is_rejected(): void
    {
        // A silent failure here would stop the site publishing with nothing to
        // explain why, so it is caught at the form.
        foreach (['08:00, lunchtime', '25:00', '8am, 1pm', '08:60'] as $bad) {
            $this->actingAs($this->admin)
                ->post(route('admin.settings.update'), [
                    'group' => 'publishing',
                    'publish_mode' => 'scheduled',
                    'publish_lookahead_hours' => 3,
                    'publish_slots' => $bad,
                    'publish_max_per_day' => 6,
                    'publish_lead_minutes' => 15,
                    'trending_generate_per_run' => 2,
                    'trending_min_score' => 45,
                ])
                ->assertSessionHasErrors('publish_slots');
        }
    }

    public function test_the_publishing_tab_renders(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.edit', ['tab' => 'publishing']))
            ->assertOk()
            ->assertSee('Publishing times')
            ->assertSee('Maximum posts per day');
    }
}
