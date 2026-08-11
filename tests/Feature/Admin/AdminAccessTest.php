<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_a_registered_reader_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_a_deactivated_admin_is_refused(): void
    {
        $this->actingAs(User::factory()->admin()->inactive()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_the_admin_reaches_the_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');
    }

    public function test_the_admin_panel_is_never_indexable(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    public function test_the_access_admin_gate_matches_the_middleware(): void
    {
        $admin = User::factory()->admin()->create();
        $reader = User::factory()->create();
        $suspended = User::factory()->admin()->inactive()->create();

        $this->assertTrue($admin->can('access-admin'));
        $this->assertFalse($reader->can('access-admin'));
        $this->assertFalse($suspended->can('access-admin'));
    }

    public function test_the_profile_page_is_admin_only(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.profile.edit'))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.profile.edit'))
            ->assertOk();
    }

    public function test_signing_in_lives_under_admin_and_not_at_the_site_root(): void
    {
        $this->get('/admin/login')->assertOk();

        // A bare /login must not exist: the public site never exposes one.
        $this->get('/login')->assertNotFound();
        $this->get('/register')->assertNotFound();
    }
}
