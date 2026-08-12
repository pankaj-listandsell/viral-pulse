<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    private function log(array $attributes = []): ActivityLog
    {
        return ActivityLog::create(array_merge([
            'user_id' => $this->admin->id,
            'action' => 'post.created',
            'description' => 'Created "A headline"',
            'ip_hash' => str_repeat('a', 64),
        ], $attributes));
    }

    public function test_the_log_renders(): void
    {
        $this->log();

        $this->actingAs($this->admin)
            ->get(route('admin.activity.index'))
            ->assertOk()
            ->assertSee('Created &quot;A headline&quot;', false)
            ->assertSee('post.created');
    }

    public function test_entries_can_be_filtered_by_action(): void
    {
        $this->log(['action' => 'post.created', 'description' => 'A post was created']);
        $this->log(['action' => 'settings.updated', 'description' => 'Settings were changed']);

        $this->actingAs($this->admin)
            ->get(route('admin.activity.index', ['action' => 'settings.updated']))
            ->assertOk()
            ->assertSee('Settings were changed')
            ->assertDontSee('A post was created');
    }

    public function test_entries_can_be_filtered_by_date_range(): void
    {
        $this->log(['description' => 'Happened last week'])->forceFill(['created_at' => now()->subWeek()])->save();
        $this->log(['description' => 'Happened today']);

        $this->actingAs($this->admin)
            ->get(route('admin.activity.index', ['from' => now()->subDay()->toDateString()]))
            ->assertOk()
            ->assertSee('Happened today')
            ->assertDontSee('Happened last week');
    }

    public function test_an_unparseable_date_is_ignored_rather_than_fatal(): void
    {
        $this->log(['description' => 'Still visible']);

        // A hand-edited query string should not take the page down.
        $this->actingAs($this->admin)
            ->get(route('admin.activity.index', ['from' => 'not-a-date']))
            ->assertOk()
            ->assertSee('Still visible');
    }

    public function test_scheduled_work_is_attributed_to_the_system(): void
    {
        // Console commands run with nobody signed in.
        $this->log(['user_id' => null, 'description' => 'Published on schedule']);

        $this->actingAs($this->admin)
            ->get(route('admin.activity.index'))
            ->assertOk()
            ->assertSee('System');
    }

    public function test_the_log_is_closed_to_everyone_but_the_admin(): void
    {
        $this->get(route('admin.activity.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.activity.index'))
            ->assertForbidden();
    }
}
