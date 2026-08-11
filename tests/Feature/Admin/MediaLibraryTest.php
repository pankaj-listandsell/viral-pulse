<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();
    }

    public function test_an_image_can_be_uploaded(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.media.store'), [
                'files' => [UploadedFile::fake()->image('holiday.jpg', 1200, 800)],
            ])
            ->assertSessionHasNoErrors();

        $media = Media::first();

        $this->assertNotNull($media);
        $this->assertSame('holiday.jpg', $media->original_name);
        $this->assertSame($this->admin->id, $media->user_id);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_uploads_are_re_encoded_rather_than_stored_as_received(): void
    {
        $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'files' => [UploadedFile::fake()->image('original.jpg', 800, 600)],
        ]);

        $media = Media::first();

        // Re-encoding is what neutralises a payload hidden in image metadata.
        $this->assertSame('webp', $media->extension);
        $this->assertStringEndsWith('.webp', $media->path);
        $this->assertNotSame('original.jpg', $media->filename);
    }

    public function test_responsive_variants_are_generated(): void
    {
        $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'files' => [UploadedFile::fake()->image('wide.jpg', 2000, 1200)],
        ]);

        $media = Media::first();

        $this->assertArrayHasKey('thumbnail', $media->conversions);
        $this->assertArrayHasKey('medium', $media->conversions);
        $this->assertLessThanOrEqual(320, $media->conversions['thumbnail']['width']);
        Storage::disk('public')->assertExists($media->conversions['thumbnail']['path']);
    }

    public function test_the_real_dimensions_are_recorded(): void
    {
        $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'files' => [UploadedFile::fake()->image('exact.jpg', 640, 480)],
        ]);

        $media = Media::first();

        $this->assertSame(640, $media->width);
        $this->assertSame(480, $media->height);
    }

    public function test_a_non_image_file_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.media.store'), [
                'files' => [UploadedFile::fake()->create('script.php', 10, 'application/x-php')],
            ])
            ->assertSessionHasErrors('files.0');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_a_php_payload_disguised_with_an_image_extension_is_rejected(): void
    {
        // Real content type is text, only the filename claims to be a JPEG.
        $this->actingAs($this->admin)
            ->post(route('admin.media.store'), [
                'files' => [UploadedFile::fake()->createWithContent('payload.jpg', '<?php echo "pwned";')],
            ])
            ->assertSessionHasErrors('files.0');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_an_oversized_image_is_rejected(): void
    {
        $limit = (int) config('site.media.max_upload_kb');

        $this->actingAs($this->admin)
            ->post(route('admin.media.store'), [
                'files' => [UploadedFile::fake()->image('huge.jpg')->size($limit + 512)],
            ])
            ->assertSessionHasErrors('files.0');
    }

    public function test_alt_text_can_be_saved(): void
    {
        $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'files' => [UploadedFile::fake()->image('a.jpg')],
        ]);

        $media = Media::first();

        $this->actingAs($this->admin)
            ->put(route('admin.media.update', $media), ['alt_text' => 'A busy market street'])
            ->assertSessionHasNoErrors();

        $this->assertSame('A busy market street', $media->fresh()->alt_text);
    }

    public function test_deleting_removes_the_file_and_all_its_variants(): void
    {
        $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'files' => [UploadedFile::fake()->image('gone.jpg', 1200, 900)],
        ]);

        $media = Media::first();
        $original = $media->path;
        $thumbnail = $media->conversions['thumbnail']['path'];

        $this->actingAs($this->admin)->delete(route('admin.media.destroy', $media));

        Storage::disk('public')->assertMissing($original);
        Storage::disk('public')->assertMissing($thumbnail);
        $this->assertDatabaseCount('media', 0);
    }

    public function test_the_picker_returns_json_when_asked(): void
    {
        $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'files' => [UploadedFile::fake()->image('a.jpg')],
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.media.index'))
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'url', 'thumbnail', 'name']], 'next_page']);
    }

    public function test_readers_cannot_upload(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('a.jpg')]])
            ->assertForbidden();
    }
}
