<?php

namespace Tests\Unit;

use App\Http\Middleware\CanonicalUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * Driven through the middleware directly rather than the HTTP test client.
 *
 * Laravel's test client runs every URI through prepareUrlForRequest(), which
 * trims trailing slashes before the request is built - so a feature test
 * physically cannot send "/contact/" and this behaviour would look covered
 * while never being exercised.
 */
class CanonicalUrlTest extends TestCase
{
    private function pass(string $url, string $method = 'GET'): \Symfony\Component\HttpFoundation\Response
    {
        return (new CanonicalUrl)->handle(
            Request::create($url, $method),
            fn () => new Response('ok')
        );
    }

    public function test_a_trailing_slash_is_redirected_away(): void
    {
        config(['app.url' => 'http://localhost']);

        $response = $this->pass('http://localhost/contact/');

        // /contact and /contact/ are two URLs serving one page, which splits
        // whatever ranking the page earns.
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('http://localhost/contact', $response->headers->get('Location'));
    }

    public function test_the_query_string_survives_the_redirect(): void
    {
        config(['app.url' => 'http://localhost']);

        $response = $this->pass('http://localhost/latest/?page=2');

        $this->assertSame('http://localhost/latest?page=2', $response->headers->get('Location'));
    }

    public function test_a_canonical_url_passes_straight_through(): void
    {
        config(['app.url' => 'http://localhost']);

        $this->assertSame(200, $this->pass('http://localhost/contact')->getStatusCode());
        $this->assertSame(200, $this->pass('http://localhost/')->getStatusCode());
    }

    public function test_www_is_folded_into_the_configured_host(): void
    {
        config(['app.url' => 'https://viralpulse.test']);

        $response = $this->pass('https://www.viralpulse.test/latest');

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('https://viralpulse.test/latest', $response->headers->get('Location'));
    }

    public function test_a_www_configured_host_wins_in_the_other_direction(): void
    {
        config(['app.url' => 'https://www.viralpulse.test']);

        $response = $this->pass('https://viralpulse.test/latest');

        $this->assertSame('https://www.viralpulse.test/latest', $response->headers->get('Location'));
    }

    public function test_an_unrelated_host_is_left_alone(): void
    {
        config(['app.url' => 'https://viralpulse.test']);

        // Rewriting an unknown host would break local development, preview
        // deployments and health checks.
        $this->assertSame(200, $this->pass('http://192.168.1.10/contact')->getStatusCode());
    }

    public function test_unsafe_methods_are_never_redirected(): void
    {
        config(['app.url' => 'http://localhost']);

        // Redirecting a POST drops the body and breaks every form on the site.
        $this->assertSame(200, $this->pass('http://localhost/contact/', 'POST')->getStatusCode());
    }
}
