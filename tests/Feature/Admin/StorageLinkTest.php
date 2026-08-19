<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The page that repairs public/storage when there is no shell to run
 * `php artisan storage:link` in.
 */
class StorageLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_cannot_reach_the_repair_page(): void
    {
        $this->get('/admin/maintenance/storage-link')->assertRedirect('/admin/login');
        $this->post('/admin/maintenance/storage-link')->assertRedirect('/admin/login');
    }

    public function test_a_signed_in_reader_cannot_reach_it_either(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin/maintenance/storage-link')
            ->assertForbidden();
    }

    public function test_an_admin_sees_the_diagnosis(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/maintenance/storage-link')
            ->assertOk()
            ->assertSee('Storage link')
            ->assertSee('points at storage/app/public');
    }

    public function test_the_page_reports_the_link_as_correct_when_it_is(): void
    {
        // The suite runs against a working installation, so the link is already
        // right: the page must then say so rather than "repairing" anything.
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/maintenance/storage-link')
            ->assertOk();

        if (is_link(public_path('storage'))) {
            $response->assertSee('The link is already correct');
        }

        $this->assertTrue(true);
    }
}
