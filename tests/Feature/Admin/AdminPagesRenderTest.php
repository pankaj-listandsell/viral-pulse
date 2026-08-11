<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every admin screen rendered with real data. Blade compiles at render time,
 * so a broken directive or a missing variable only surfaces when the page is
 * actually built - these keep that from reaching the browser.
 */
class AdminPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_every_admin_page_renders(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->for($category)->create();
        $post->tags()->attach(Tag::factory()->count(2)->create());

        Post::factory()->draft()->for($category)->create();
        Post::factory()->scheduled()->for($category)->create();
        User::factory()->count(2)->create();

        $pages = [
            'dashboard' => route('admin.dashboard'),
            'posts index' => route('admin.posts.index'),
            'posts trash' => route('admin.posts.index', ['trashed' => 1]),
            'post create' => route('admin.posts.create'),
            'post edit' => route('admin.posts.edit', $post),
            'categories index' => route('admin.categories.index'),
            'category create' => route('admin.categories.create'),
            'category edit' => route('admin.categories.edit', $category),
            'tags index' => route('admin.tags.index'),
            'media index' => route('admin.media.index'),
            'users index' => route('admin.users.index'),
            'profile' => route('admin.profile.edit'),
        ];

        foreach ($pages as $label => $url) {
            $this->actingAs($this->admin)
                ->get($url)
                ->assertOk("The {$label} page failed to render.");
        }
    }

    public function test_the_editor_page_carries_its_islands(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.posts.create'))
            ->assertSee('data-island="PostEditor"', false)
            ->assertSee('data-island="TagInput"', false)
            ->assertSee('data-island="ImagePicker"', false)
            // The textarea fallback means the form still works without JS.
            ->assertSee('<textarea', false);
    }

    public function test_the_sidebar_exposes_the_sections_built_so_far(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        foreach (['Posts', 'Create Post', 'Categories', 'Tags', 'Media Library', 'Users'] as $item) {
            $response->assertSee($item);
        }
    }

    public function test_empty_states_appear_instead_of_bare_tables(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.posts.index'))
            ->assertOk()
            ->assertSee('No posts yet');

        $this->actingAs($this->admin)
            ->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('No images yet');
    }
}
