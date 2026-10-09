<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use App\Services\Images\AiIllustrationGenerator;
use App\Services\Images\CloudflareImageGenerator;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloudflareImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        Storage::fake(config('site.media.disk'));

        config([
            'site.media.cloudflare.account_id' => 'acc123',
            'site.media.cloudflare.token' => 'secret',
        ]);
    }

    private function article(): Post
    {
        return Post::factory()->create([
            'title' => 'LPG Aadhaar rule from October 1',
            'status' => PostStatus::Published,
            'published_at' => now()->subHour(),
            'featured_image' => null,
        ]);
    }

    private function jpeg(): string
    {
        $im = imagecreatetruecolor(1024, 1024);
        ob_start();
        imagejpeg($im);

        return (string) ob_get_clean();
    }

    public function test_a_generated_image_is_stored_and_labelled_as_ai(): void
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response(['success' => true, 'result' => ['image' => base64_encode($this->jpeg())]]),
        ]);

        $media = app(CloudflareImageGenerator::class)->generate($this->article());

        $this->assertNotNull($media);
        Storage::disk(config('site.media.disk'))->assertExists($media->path);
        $this->assertSame(AiIllustrationGenerator::CREDIT, $media->caption);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/accounts/acc123/ai/run/@cf/black-forest-labs/flux-1-schnell')
            && $request->hasHeader('Authorization', 'Bearer secret')
            && str_contains($request['prompt'], 'lpg aadhaar rule from october 1')
            && ! str_contains($request['prompt'], 'October'));
    }

    public function test_without_credentials_the_next_strategy_runs(): void
    {
        config(['site.media.cloudflare.token' => null, 'ai.providers.cloudflare.key' => null]);
        Http::fake();

        $this->assertNull(app(CloudflareImageGenerator::class)->generate($this->article()));
        Http::assertNothingSent();
    }

    public function test_the_editor_button_falls_back_to_cloudflare_when_gemini_fails(): void
    {
        config(['ai.providers.gemini.key' => 'test-key', 'site.media.illustration.key' => 'test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota']], 429),
            'api.cloudflare.com/*' => Http::response(['success' => true, 'result' => ['image' => base64_encode($this->jpeg())]]),
        ]);

        $post = $this->article();

        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.posts.generate-image', $post))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($post->refresh()->featured_image);
    }

    public function test_an_api_error_returns_nothing_rather_than_failing(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response(['success' => false, 'errors' => [['message' => 'quota']]], 429)]);

        $this->assertNull(app(CloudflareImageGenerator::class)->generate($this->article()));
    }
}
