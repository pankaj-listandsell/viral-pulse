<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxonomyCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_a_category_can_be_created_with_a_generated_slug(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Space & Science',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Space & Science',
            'slug' => 'space-science',
        ]);
    }

    public function test_duplicate_category_slugs_are_rejected(): void
    {
        Category::factory()->create(['slug' => 'technology']);

        $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), ['name' => 'Tech', 'slug' => 'technology'])
            ->assertSessionHasErrors('slug');
    }

    public function test_a_category_can_be_updated(): void
    {
        $category = Category::factory()->create(['name' => 'Old name']);

        $this->actingAs($this->admin)
            ->put(route('admin.categories.update', $category), [
                'name' => 'New name',
                'slug' => $category->slug,
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('New name', $category->fresh()->name);
    }

    public function test_a_category_cannot_be_its_own_parent(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.categories.update', $category), [
                'name' => $category->name,
                'parent_id' => $category->id,
            ])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_a_category_holding_posts_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Post::factory()->for($category)->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($category);
    }

    public function test_a_category_with_subcategories_cannot_be_deleted(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $parent))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($parent);
    }

    public function test_an_empty_category_can_be_deleted(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($category);
    }

    public function test_a_tag_can_be_created_and_updated(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tags.store'), ['name' => 'World Cup'])
            ->assertSessionHasNoErrors();

        $tag = Tag::firstWhere('slug', 'world-cup');
        $this->assertNotNull($tag);

        $this->actingAs($this->admin)
            ->put(route('admin.tags.update', $tag), ['name' => 'World Cup 2026', 'is_trending' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($tag->fresh()->is_trending);
    }

    public function test_deleting_a_tag_leaves_its_posts_intact(): void
    {
        $post = Post::factory()->create();
        $tag = Tag::factory()->create();
        $post->tags()->attach($tag);

        $this->actingAs($this->admin)->delete(route('admin.tags.destroy', $tag));

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        $this->assertDatabaseCount('post_tag', 0);
        $this->assertNotSoftDeleted($post);
    }

    public function test_taxonomy_routes_are_closed_to_readers(): void
    {
        $reader = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($reader)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($reader)->post(route('admin.categories.store'), ['name' => 'X'])->assertForbidden();
        $this->actingAs($reader)->delete(route('admin.categories.destroy', $category))->assertForbidden();
        $this->actingAs($reader)->get(route('admin.tags.index'))->assertForbidden();
    }
}
