<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    private function settings(): SettingsService
    {
        return app(SettingsService::class);
    }

    private function generalPayload(array $overrides = []): array
    {
        return array_merge([
            'group' => 'general',
            'site_name' => 'ViralPulse',
            'site_tagline' => 'Fast takes on what is trending',
            'site_description' => 'Daily explainers.',
            'contact_email' => 'hello@example.test',
            'posts_per_page' => 12,
            'timezone' => 'Asia/Kolkata',
        ], $overrides);
    }

    public function test_the_settings_screen_renders_every_tab(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.edit'))->assertOk();

        foreach (['General', 'Social', 'AdSense', 'Analytics', 'Features', 'AI'] as $tab) {
            $response->assertSee($tab);
        }
    }

    public function test_a_group_can_be_saved(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.update'), $this->generalPayload(['site_name' => 'Renamed Site']))
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'general']));

        $this->assertSame('Renamed Site', $this->settings()->get('site_name'));
        $this->assertSame(12, $this->settings()->get('posts_per_page'));
    }

    public function test_validation_rejects_bad_values(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.update'), $this->generalPayload([
                'site_name' => '',
                'posts_per_page' => 500,
                'contact_email' => 'not-an-address',
            ]))
            ->assertSessionHasErrors(['site_name', 'posts_per_page', 'contact_email']);
    }

    public function test_a_publisher_id_must_look_like_one(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.update'), [
                'group' => 'adsense',
                'adsense_client_id' => 'pub-123',
            ])
            ->assertSessionHasErrors('adsense_client_id');

        $this->actingAs($this->admin)
            ->post(route('admin.settings.update'), [
                'group' => 'adsense',
                'adsense_client_id' => 'ca-pub-1234567890123456',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_a_toggle_can_actually_be_switched_off(): void
    {
        Setting::where('key', 'likes_enabled')->update(['value' => '1']);
        $this->settings()->flush();

        // An unchecked box is absent from the request rather than false. Read
        // from the validated set, a toggle could be turned on but never off.
        $this->actingAs($this->admin)
            ->post(route('admin.settings.update'), ['group' => 'features'])
            ->assertSessionHasNoErrors();

        $this->assertFalse($this->settings()->bool('likes_enabled'));
    }

    public function test_an_unknown_group_is_a_404(): void
    {
        // The group arrives in a hidden field; an unknown one would otherwise
        // validate against no rules at all.
        $this->actingAs($this->admin)
            ->post(route('admin.settings.update'), ['group' => 'made-up'])
            ->assertNotFound();
    }

    public function test_a_logo_upload_is_stored_and_replaced_cleanly(): void
    {
        Storage::fake(config('site.media.disk'));

        $this->actingAs($this->admin)
            ->post(route('admin.settings.update'), $this->generalPayload([
                'site_logo' => UploadedFile::fake()->image('logo.png', 200, 60),
            ]))
            ->assertSessionHasNoErrors();

        $first = $this->settings()->get('site_logo');
        $this->assertNotNull($first);
        Storage::disk(config('site.media.disk'))->assertExists($first);

        $this->actingAs($this->admin)
            ->post(route('admin.settings.update'), $this->generalPayload([
                'site_logo' => UploadedFile::fake()->image('new-logo.png', 200, 60),
            ]));

        // Replacing a logo should not leave the old file on disk forever.
        Storage::disk(config('site.media.disk'))->assertMissing($first);
        Storage::disk(config('site.media.disk'))->assertExists($this->settings()->get('site_logo'));
    }

    public function test_an_absent_file_leaves_the_existing_one_alone(): void
    {
        Storage::fake(config('site.media.disk'));

        $this->actingAs($this->admin)->post(route('admin.settings.update'), $this->generalPayload([
            'site_logo' => UploadedFile::fake()->image('logo.png'),
        ]));

        $stored = $this->settings()->get('site_logo');

        $this->actingAs($this->admin)->post(route('admin.settings.update'), $this->generalPayload());

        // No file in the request means "leave it alone", not "clear it".
        $this->assertSame($stored, $this->settings()->get('site_logo'));
    }

    public function test_an_image_can_be_removed(): void
    {
        Storage::fake(config('site.media.disk'));

        $this->actingAs($this->admin)->post(route('admin.settings.update'), $this->generalPayload([
            'site_logo' => UploadedFile::fake()->image('logo.png'),
        ]));

        $this->actingAs($this->admin)->post(route('admin.settings.update'), $this->generalPayload([
            'remove_site_logo' => '1',
        ]));

        $this->assertNull($this->settings()->get('site_logo'));
    }

    public function test_saving_flushes_the_caches_that_depend_on_settings(): void
    {
        $this->get(route('feed.index'))->assertOk();

        $this->actingAs($this->admin)->post(route('admin.settings.update'), $this->generalPayload([
            'site_name' => 'A Brand New Name',
        ]));

        // The RSS channel title comes from the site name and is cached for
        // half an hour.
        $this->get(route('feed.index'))->assertOk()->assertSee('A Brand New Name');
    }

    public function test_the_seo_screen_saves_its_own_group(): void
    {
        $this->actingAs($this->admin)->get(route('admin.seo.edit'))->assertOk()->assertSee('robots.txt');

        $this->actingAs($this->admin)
            ->post(route('admin.seo.update'), [
                'seo_default_title' => 'ViralPulse — trending, explained',
                'seo_default_description' => 'The stories people are searching for, written plainly.',
                'seo_robots_default' => 'noindex, follow',
                'ai_default_tone' => 'ignored',
            ])
            ->assertRedirect(route('admin.seo.edit'));

        $this->assertSame('noindex, follow', $this->settings()->get('seo_robots_default'));
        // A key outside the group is not part of its schema, so it is ignored
        // rather than written.
        $this->assertNotSame('ignored', $this->settings()->get('ai_default_tone'));
    }

    public function test_a_robots_directive_outside_the_list_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.seo.update'), ['seo_robots_default' => 'index'])
            ->assertSessionHasErrors('seo_robots_default');
    }

    public function test_settings_never_accept_an_api_key(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.update'), [
                'group' => 'ai',
                'ai_daily_limit' => 20,
                'ai_default_language' => 'en',
                'ai_default_tone' => 'informative',
                'gemini_api_key' => 'a-key-that-should-never-be-stored',
            ]);

        // Keys live in .env. One stored here would appear in every database
        // dump, every backup, and on this screen.
        $this->assertDatabaseMissing('settings', ['key' => 'gemini_api_key']);
    }

    public function test_caches_can_be_cleared_by_hand(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.flush'))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', ['action' => 'settings.cache_flushed']);
    }

    public function test_settings_are_closed_to_everyone_but_the_admin(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.settings.edit'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.settings.update'), $this->generalPayload())
            ->assertForbidden();
    }
}
