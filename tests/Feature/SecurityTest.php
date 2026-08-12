<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
    }

    /**
     * Every GET route under /admin, excluding the sign-in flow that has to stay
     * reachable to a guest.
     *
     * @return array<int, \Illuminate\Routing\Route>
     */
    private function protectedAdminRoutes(): array
    {
        $public = ['login', 'logout', 'register', 'password.request', 'password.reset', 'password.email',
            'password.store', 'password.confirm', 'verification.notice', 'verification.send', 'verification.verify'];

        return array_values(array_filter(
            Route::getRoutes()->getRoutes(),
            fn ($route) => str_starts_with($route->uri(), 'admin')
                && in_array('GET', $route->methods(), true)
                && ! in_array($route->getName(), $public, true)
        ));
    }

    public function test_every_admin_route_carries_the_admin_middleware(): void
    {
        // A static assertion on purpose: a route added later without the
        // middleware fails here rather than quietly shipping open.
        foreach ($this->protectedAdminRoutes() as $route) {
            $this->assertContains(
                'admin',
                $route->gatherMiddleware(),
                "The route {$route->uri()} is missing the admin middleware."
            );
            $this->assertContains(
                'auth',
                $route->gatherMiddleware(),
                "The route {$route->uri()} is missing the auth middleware."
            );
        }
    }

    public function test_a_guest_is_sent_to_the_login_page_from_every_admin_screen(): void
    {
        $category = Category::factory()->create();
        Post::factory()->create(['category_id' => $category->id]);

        foreach ($this->protectedAdminRoutes() as $route) {
            if ($route->parameterNames() !== []) {
                continue; // Covered individually in the per-feature suites.
            }

            $this->get('/'.$route->uri())->assertRedirect(route('login'));
        }
    }

    public function test_a_signed_in_non_admin_is_refused(): void
    {
        $reader = User::factory()->create();

        foreach ($this->protectedAdminRoutes() as $route) {
            if ($route->parameterNames() !== []) {
                continue;
            }

            $this->actingAs($reader)->get('/'.$route->uri())->assertForbidden();
        }
    }

    public function test_a_deactivated_admin_loses_access(): void
    {
        $admin = User::factory()->admin()->create(['is_active' => false]);

        // Deactivating an account has to take effect immediately, not at the
        // next sign-in.
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_rights_cannot_be_granted_through_mass_assignment(): void
    {
        $this->assertNotContains('is_admin', (new User)->getFillable());

        // Outside production strict mode turns a discarded attribute into an
        // exception. Production silently drops it instead, so the behaviour is
        // checked with strict mode off - that is the path that actually ships.
        Model::preventSilentlyDiscardingAttributes(false);

        $user = new User;
        $user->fill(['name' => 'Someone', 'email' => 'someone@example.test', 'is_admin' => true]);

        $this->assertNotTrue($user->is_admin);
    }

    public function test_the_public_site_never_links_to_the_admin(): void
    {
        Post::factory()->create(['status' => PostStatus::Published, 'published_at' => now()->subHour()]);

        foreach (['/', '/latest', '/contact'] as $uri) {
            $body = $this->get($uri)->assertOk()->getContent();

            $this->assertStringNotContainsString('/admin', $body, "{$uri} exposes an admin link.");
        }
    }

    public function test_security_headers_are_present_on_every_response(): void
    {
        $expected = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ];

        foreach (['/', '/feed.xml', '/robots.txt'] as $uri) {
            $response = $this->get($uri)->assertOk();

            foreach ($expected as $header => $value) {
                $response->assertHeader($header, $value);
            }
        }
    }

    public function test_hsts_is_not_sent_over_plain_http(): void
    {
        // Sent over http it is ignored, and in development it would pin
        // localhost to https in the developer's browser for a year.
        $this->get('/')->assertOk()->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_state_changing_public_routes_are_rate_limited(): void
    {
        $limited = [
            'contact.submit' => 'throttle:contact',
            'newsletter.subscribe' => 'throttle:newsletter',
            'search' => 'throttle:search',
        ];

        foreach ($limited as $name => $middleware) {
            $this->assertContains(
                $middleware,
                Route::getRoutes()->getByName($name)->gatherMiddleware(),
                "The route {$name} is not rate limited."
            );
        }
    }

    /**
     * Asserted statically rather than by sending a request.
     *
     * ValidateCsrfToken short-circuits whenever the app is running tests, so a
     * POST without a token succeeds in the harness no matter what. A test that
     * looked like it exercised CSRF would prove nothing; checking that the
     * middleware is actually on every state-changing route does.
     */
    public function test_csrf_protection_covers_every_state_changing_route(): void
    {
        // From the HTTP kernel, not the router: in Laravel 12 the groups are
        // assembled by bootstrap/app.php and the router's own copy is empty.
        $groups = app(Kernel::class)->getMiddlewareGroups();
        $unprotected = [];

        $this->assertArrayHasKey('web', $groups);

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $changesState = array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $route->methods()) !== [];

            // The API group is stateless and token-authenticated, and Laravel's
            // own signed storage route authenticates by signature.
            if (! $changesState || str_starts_with($route->uri(), 'api/') || str_starts_with($route->uri(), 'storage/')) {
                continue;
            }

            // gatherMiddleware() returns group names rather than their
            // contents, so the groups have to be expanded before looking for
            // the middleware itself.
            $middleware = [];

            foreach ($route->gatherMiddleware() as $entry) {
                $middleware = array_merge($middleware, $groups[$entry] ?? [$entry]);
            }

            if (! in_array(ValidateCsrfToken::class, $middleware, true)) {
                $unprotected[] = implode('|', $route->methods()).' /'.$route->uri();
            }
        }

        $this->assertSame([], $unprotected, 'These routes accept writes without CSRF protection.');
    }

    public function test_an_api_key_never_reaches_a_response(): void
    {
        config(['ai.providers.gemini.key' => 'a-key-that-must-never-be-rendered']);

        $admin = User::factory()->admin()->create();

        foreach ([route('admin.dashboard'), route('admin.ai.index'), route('admin.settings.edit')] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()->assertDontSee('a-key-that-must-never-be-rendered');
        }
    }

    public function test_raw_ip_addresses_are_never_stored(): void
    {
        $this->post(route('contact.submit'), [
            'name' => 'Someone',
            'email' => 'someone@example.test',
            'subject' => 'A subject line',
            'message' => 'A message body long enough to pass validation.',
        ]);

        $stored = ContactMessage::first();

        $this->assertNotNull($stored);
        // A salted hash, so submissions can be matched against each other but
        // the address can never be read back.
        $this->assertSame(64, strlen($stored->ip_hash));
        $this->assertStringNotContainsString('127.0.0.1', $stored->ip_hash);
    }

    public function test_debug_mode_is_off_in_production(): void
    {
        // A stack trace on a production error page leaks paths, queries and
        // environment values.
        $this->assertFalse(
            (bool) env('APP_DEBUG') && app()->isProduction(),
            'APP_DEBUG must be false when APP_ENV is production.'
        );
    }
}
