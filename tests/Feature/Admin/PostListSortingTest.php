<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PostListSortingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * Three posts whose creation order and update order deliberately disagree,
     * which is the case the old default got wrong.
     *
     * @return array<string, Post>
     */
    private function threePosts(): array
    {
        $newsDesk = Category::factory()->create(['name' => 'Business']);
        $sport = Category::factory()->create(['name' => 'Athletics']);

        $oldest = Post::factory()->create([
            'title' => 'Zebra crossing rules change',
            'category_id' => $sport->id,
            'views_count' => 900,
            'created_at' => Carbon::parse('2026-01-01 10:00'),
            // Edited most recently, though it was written first.
            'updated_at' => Carbon::parse('2026-08-20 10:00'),
        ]);

        $middle = Post::factory()->create([
            'title' => 'Metro fare revision',
            'category_id' => $newsDesk->id,
            'views_count' => 100,
            'created_at' => Carbon::parse('2026-04-01 10:00'),
            'updated_at' => Carbon::parse('2026-04-01 10:00'),
        ]);

        $newest = Post::factory()->create([
            'title' => 'Airport line opens',
            'category_id' => $newsDesk->id,
            'views_count' => 500,
            'created_at' => Carbon::parse('2026-08-01 10:00'),
            'updated_at' => Carbon::parse('2026-08-02 10:00'),
        ]);

        return compact('oldest', 'middle', 'newest');
    }

    /**
     * The order the three titles appear in the rendered table.
     *
     * @return array<int, string>
     */
    private function orderOf(string $url): array
    {
        $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();

        $positions = [];

        foreach (['Zebra crossing rules change', 'Metro fare revision', 'Airport line opens'] as $title) {
            $at = strpos($html, $title);

            if ($at !== false) {
                $positions[$at] = $title;
            }
        }

        ksort($positions);

        return array_values($positions);
    }

    public function test_the_list_opens_on_newest_created_first(): void
    {
        $this->threePosts();

        $this->assertSame(
            ['Airport line opens', 'Metro fare revision', 'Zebra crossing rules change'],
            $this->orderOf(route('admin.posts.index')),
            'The list should open in creation order, newest first.'
        );
    }

    public function test_editing_an_old_post_no_longer_pushes_it_to_the_top(): void
    {
        $posts = $this->threePosts();

        // The oldest post has the most recent updated_at. Under the previous
        // default that alone put it first, which is the behaviour being fixed.
        $this->assertTrue($posts['oldest']->updated_at->greaterThan($posts['newest']->updated_at));

        $this->assertSame(
            'Airport line opens',
            $this->orderOf(route('admin.posts.index'))[0],
        );
    }

    public function test_created_can_be_flipped_to_oldest_first(): void
    {
        $this->threePosts();

        $this->assertSame(
            ['Zebra crossing rules change', 'Metro fare revision', 'Airport line opens'],
            $this->orderOf(route('admin.posts.index', ['sort' => 'created', 'direction' => 'asc'])),
        );
    }

    public function test_it_sorts_by_title(): void
    {
        $this->threePosts();

        $this->assertSame(
            ['Airport line opens', 'Metro fare revision', 'Zebra crossing rules change'],
            $this->orderOf(route('admin.posts.index', ['sort' => 'title', 'direction' => 'asc'])),
        );

        $this->assertSame(
            ['Zebra crossing rules change', 'Metro fare revision', 'Airport line opens'],
            $this->orderOf(route('admin.posts.index', ['sort' => 'title', 'direction' => 'desc'])),
        );
    }

    public function test_it_sorts_by_view_count(): void
    {
        $this->threePosts();

        $this->assertSame(
            ['Zebra crossing rules change', 'Airport line opens', 'Metro fare revision'],
            $this->orderOf(route('admin.posts.index', ['sort' => 'views'])),
            'Views should open on the most-read post.'
        );
    }

    public function test_it_sorts_by_updated(): void
    {
        $this->threePosts();

        $this->assertSame(
            ['Zebra crossing rules change', 'Airport line opens', 'Metro fare revision'],
            $this->orderOf(route('admin.posts.index', ['sort' => 'updated'])),
        );
    }

    public function test_it_sorts_by_the_categorys_name_and_not_its_id(): void
    {
        $this->threePosts();

        // Athletics before Business, though the Business category was created
        // first and so has the lower id.
        $this->assertSame(
            'Zebra crossing rules change',
            $this->orderOf(route('admin.posts.index', ['sort' => 'category', 'direction' => 'asc']))[0],
        );
    }

    public function test_a_column_starts_in_the_direction_that_column_means(): void
    {
        $this->threePosts();

        // No direction given: a date opens newest first, a name opens A to Z.
        $this->assertSame(
            'Airport line opens',
            $this->orderOf(route('admin.posts.index', ['sort' => 'created']))[0],
        );

        $this->assertSame(
            'Airport line opens',
            $this->orderOf(route('admin.posts.index', ['sort' => 'title']))[0],
        );

        $this->assertSame(
            'Zebra crossing rules change',
            $this->orderOf(route('admin.posts.index', ['sort' => 'views']))[0],
        );
    }

    public function test_an_unknown_sort_column_falls_back_instead_of_reaching_the_query(): void
    {
        $this->threePosts();

        // The value lands in an ORDER BY, so anything not whitelisted has to be
        // ignored rather than passed through.
        foreach (['id', 'password', 'title); drop table posts;--'] as $bad) {
            $this->assertSame(
                'Airport line opens',
                $this->orderOf(route('admin.posts.index', ['sort' => $bad]))[0],
                "An unknown sort ({$bad}) should fall back to the default."
            );
        }

        $this->assertSame(3, Post::count(), 'The table should still be there.');
    }

    public function test_an_unknown_direction_falls_back_to_the_columns_own_default(): void
    {
        $this->threePosts();

        $this->assertSame(
            'Airport line opens',
            $this->orderOf(route('admin.posts.index', ['sort' => 'created', 'direction' => 'sideways']))[0],
        );
    }

    public function test_the_active_column_is_announced_and_offers_the_opposite_direction(): void
    {
        $this->threePosts();

        $html = $this->actingAs($this->admin)
            ->get(route('admin.posts.index', ['sort' => 'created', 'direction' => 'desc']))
            ->assertOk()
            ->getContent();

        // A screen reader is told which column the table is ordered by.
        $this->assertStringContainsString('aria-sort="descending"', $html);

        // And clicking the active header flips it rather than re-sorting the
        // same way.
        $this->assertStringContainsString('direction=asc', $html);
    }

    public function test_sorting_keeps_the_filters_and_drops_the_page(): void
    {
        $this->threePosts();

        $html = $this->actingAs($this->admin)
            ->get(route('admin.posts.index', ['search' => 'metro', 'page' => 2]))
            ->assertOk()
            ->getContent();

        // The sort links carry the search along...
        $this->assertStringContainsString('search=metro', $html);

        // ...and reset the page, because page 2 of a differently ordered list
        // is not where the reader was.
        $this->assertStringNotContainsString('page=2', $html);
    }

    public function test_filtering_keeps_the_chosen_sort(): void
    {
        $this->threePosts();

        $html = $this->actingAs($this->admin)
            ->get(route('admin.posts.index', ['sort' => 'title', 'direction' => 'asc']))
            ->assertOk()
            ->getContent();

        // The filter form carries the sort in hidden inputs, so narrowing the
        // list does not silently throw the ordering away.
        $this->assertStringContainsString('name="sort" value="title"', $html);
        $this->assertStringContainsString('name="direction" value="asc"', $html);
    }
}
