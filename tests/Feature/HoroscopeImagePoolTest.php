<?php

namespace Tests\Feature;

use App\Jobs\GenerateAiContentJob;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\AI\AiContentService;
use App\Services\AI\AiProviderManager;
use App\Services\AI\Providers\FakeProvider;
use App\Services\Images\HoroscopeImagePool;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HoroscopeImagePoolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        Storage::fake(config('site.media.disk'));
    }

    public function test_the_bundled_pictures_are_added_to_the_library_once(): void
    {
        $pool = app(HoroscopeImagePool::class);

        $first = $pool->paths();
        $bundled = count(glob(resource_path('images/daily-horoscope/*.jpg')));

        $this->assertCount($bundled, $first);
        $this->assertSame($bundled, Media::count());

        foreach ($first as $path) {
            Storage::disk(config('site.media.disk'))->assertExists($path);
        }

        // Every later morning reuses the same files rather than copying again.
        $this->assertEqualsCanonicalizing($first, $pool->paths());
        $this->assertSame($bundled, Media::count());
    }

    public function test_the_morning_article_is_given_one_of_the_fixed_pictures(): void
    {
        Queue::fake();
        User::factory()->admin()->create(['is_active' => true]);

        $this->artisan('content:generate-daily-horoscope', ['--force' => true])->assertSuccessful();

        $pool = app(HoroscopeImagePool::class)->paths();

        Queue::assertPushed(GenerateAiContentJob::class, fn (GenerateAiContentJob $job) => in_array($job->featuredImage, $pool, true));
    }

    public function test_the_published_post_carries_that_picture_instead_of_a_drawn_one(): void
    {
        Queue::fake();
        app(AiProviderManager::class)->swap(new FakeProvider);
        User::factory()->admin()->create(['is_active' => true]);

        $this->artisan('content:generate-daily-horoscope', ['--force' => true])->assertSuccessful();

        /** @var GenerateAiContentJob $job */
        $job = Queue::pushed(GenerateAiContentJob::class)->first();
        $job->handle(app(AiContentService::class));

        $post = Post::latest('id')->firstOrFail();

        $this->assertSame($job->featuredImage, $post->featured_image);
    }
}
