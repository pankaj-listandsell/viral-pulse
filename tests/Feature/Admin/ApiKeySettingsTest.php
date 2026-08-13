<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsConfigBridge;
use App\Services\SettingsService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * API keys are editable from the admin, which means the database now holds
 * secrets. Everything here is about making that safe rather than convenient:
 * encrypted at rest, never rendered back, and impossible to erase by accident.
 */
class ApiKeySettingsTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'AIza-a-realistic-looking-secret-value';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    private function save(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->admin)->post(route('admin.settings.update'), array_merge([
            'group' => 'keys',
        ], $overrides));
    }

    public function test_a_key_is_encrypted_before_it_is_stored(): void
    {
        $this->save(['gemini_api_key' => self::KEY])->assertSessionHasNoErrors();

        $stored = Setting::where('key', 'gemini_api_key')->value('value');

        // The realistic way a key escapes is a database dump, so what sits in
        // the column must not be the key.
        $this->assertNotSame(self::KEY, $stored);
        $this->assertStringNotContainsString('AIza', (string) $stored);
        $this->assertSame(self::KEY, Crypt::decryptString($stored));
    }

    public function test_the_key_reaches_the_provider_configuration(): void
    {
        config(['ai.providers.gemini.key' => null]);

        $this->save(['gemini_api_key' => self::KEY]);
        app(SettingsConfigBridge::class)->apply();

        $this->assertSame(self::KEY, config('ai.providers.gemini.key'));
    }

    public function test_the_saved_key_is_never_sent_back_to_the_browser(): void
    {
        $this->save(['gemini_api_key' => self::KEY]);

        $this->actingAs($this->admin)
            ->get(route('admin.settings.edit', ['tab' => 'keys']))
            ->assertOk()
            // Not even masked into the field: it would then sit in the page
            // source of every visit to the screen.
            ->assertDontSee(self::KEY)
            ->assertSee('A key is saved');
    }

    public function test_saving_the_form_blank_keeps_the_existing_key(): void
    {
        $this->save(['gemini_api_key' => self::KEY]);

        // The field is never prefilled, so a blank submit is what happens every
        // time anyone edits an unrelated setting on this tab.
        $this->save(['openai_api_key' => 'another-key'])->assertSessionHasNoErrors();

        app(SettingsConfigBridge::class)->apply();

        $this->assertSame(self::KEY, app(SettingsService::class)->get('gemini_api_key'));
    }

    public function test_a_key_can_be_removed_deliberately(): void
    {
        $this->save(['gemini_api_key' => self::KEY]);

        $this->save(['remove_gemini_api_key' => '1'])->assertSessionHasNoErrors();

        $this->assertNull(app(SettingsService::class)->get('gemini_api_key'));
    }

    public function test_an_undecryptable_key_is_treated_as_absent(): void
    {
        // What APP_KEY changing looks like from here. Every page would throw if
        // this were not caught.
        Setting::where('key', 'gemini_api_key')->update(['value' => 'not-valid-ciphertext']);
        app(SettingsService::class)->flush();

        $this->assertNull(app(SettingsService::class)->get('gemini_api_key'));

        $this->actingAs($this->admin)
            ->get(route('admin.settings.edit', ['tab' => 'keys']))
            ->assertOk();
    }

    public function test_the_keys_tab_does_not_leak_through_other_screens(): void
    {
        $this->save(['gemini_api_key' => self::KEY]);
        app(SettingsConfigBridge::class)->apply();

        foreach ([route('admin.dashboard'), route('admin.ai.index'), route('admin.settings.edit')] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk()->assertDontSee(self::KEY);
        }
    }

    public function test_a_visitor_cannot_reach_the_keys_screen(): void
    {
        // No save first: acting as the admin would leave this test signed in.
        $this->get(route('admin.settings.edit', ['tab' => 'keys']))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.settings.edit', ['tab' => 'keys']))
            ->assertForbidden();
    }
}
