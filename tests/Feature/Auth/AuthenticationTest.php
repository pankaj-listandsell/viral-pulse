<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_screen_renders(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in');
    }

    public function test_a_reader_can_sign_in_and_lands_on_the_public_site(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_admin_lands_on_the_dashboard_after_signing_in(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_signing_in_records_the_last_login_time_and_an_activity_log(): void
    {
        $user = User::factory()->create(['last_login_at' => null]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login',
        ]);
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))
            ->post(route('login'), ['email' => $user->email, 'password' => 'nope'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_deactivated_account_cannot_sign_in_even_with_the_right_password(): void
    {
        $user = User::factory()->inactive()->create();

        $this->from(route('login'))
            ->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_repeated_failures_are_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $ignored) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'seconds',
            session('errors')->first('email'),
            'The sixth attempt should be throttled, not merely rejected.'
        );
    }

    public function test_a_user_can_sign_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertTrue(ActivityLog::where('action', 'auth.logout')->exists());
    }

    public function test_there_is_no_public_registration(): void
    {
        // Accounts are created by the seeder or by an administrator, never by
        // a visitor, so no self-service signup endpoint exists at all.
        $this->assertFalse(app('router')->has('register'));
        $this->post('/register', [
            'name' => 'Mallory',
            'email' => 'mallory@example.test',
            'password' => 'correct-horse-battery',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'mallory@example.test']);
    }

    public function test_the_forgot_password_response_does_not_reveal_whether_an_account_exists(): void
    {
        User::factory()->create(['email' => 'known@example.test']);

        $this->post(route('password.email'), ['email' => 'known@example.test'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        RateLimiter::clear('password-resets');

        $this->post(route('password.email'), ['email' => 'nobody@example.test'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();
    }
}
