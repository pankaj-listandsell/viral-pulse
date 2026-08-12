<?php

namespace Tests\Feature\Admin;

use App\Enums\ContactMessageStatus;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactInboxTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Priya Sharma',
            'email' => 'priya@example.com',
            'subject' => 'A question about advertising',
            'message' => 'I would like to know more about sponsorship options on the site.',
        ], $overrides);
    }

    public function test_a_submission_notifies_the_admin(): void
    {
        Mail::fake();
        Setting::where('key', 'contact_email')->update(['value' => 'inbox@example.test']);
        app(SettingsService::class)->flush();

        $this->post(route('contact.submit'), $this->payload())->assertSessionHasNoErrors();

        Mail::assertSent(ContactMessageReceived::class, fn ($mail) => $mail->hasTo('inbox@example.test'));
    }

    public function test_the_notification_falls_back_to_the_admin_account(): void
    {
        Mail::fake();

        $this->post(route('contact.submit'), $this->payload())->assertSessionHasNoErrors();

        Mail::assertSent(ContactMessageReceived::class, fn ($mail) => $mail->hasTo($this->admin->email));
    }

    public function test_the_reply_to_is_the_sender_not_the_from(): void
    {
        Mail::fake();

        $this->post(route('contact.submit'), $this->payload());

        // Sending as the visitor's address would fail SPF and land the whole
        // site's mail in spam folders.
        Mail::assertSent(ContactMessageReceived::class, fn ($mail) => $mail->hasReplyTo('priya@example.com'));
    }

    public function test_a_mail_failure_does_not_lose_the_message(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP is down'));

        $this->post(route('contact.submit'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        // The message is saved before the mail is attempted, so an SMTP outage
        // costs a notification, not the enquiry itself.
        $this->assertDatabaseHas('contact_messages', ['email' => 'priya@example.com']);
    }

    public function test_the_inbox_lists_messages(): void
    {
        ContactMessage::factory()->create(['subject' => 'Sponsorship enquiry']);

        $this->actingAs($this->admin)
            ->get(route('admin.messages.index'))
            ->assertOk()
            ->assertSee('Sponsorship enquiry');
    }

    public function test_the_inbox_can_be_filtered_by_status_and_text(): void
    {
        ContactMessage::factory()->create(['subject' => 'Wanted subject', 'status' => ContactMessageStatus::New]);
        ContactMessage::factory()->create(['subject' => 'Filed as spam', 'status' => ContactMessageStatus::Spam]);

        $this->actingAs($this->admin)
            ->get(route('admin.messages.index', ['status' => 'new']))
            ->assertOk()
            ->assertSee('Wanted subject')
            ->assertDontSee('Filed as spam');

        $this->actingAs($this->admin)
            ->get(route('admin.messages.index', ['q' => 'Filed as']))
            ->assertOk()
            ->assertSee('Filed as spam')
            ->assertDontSee('Wanted subject');
    }

    public function test_opening_a_message_marks_it_read(): void
    {
        $message = ContactMessage::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.messages.show', $message))->assertOk();

        $message->refresh();

        // A separate "mark read" button is one more thing to forget, and then
        // the unread count lies.
        $this->assertSame(ContactMessageStatus::Read, $message->status);
        $this->assertNotNull($message->read_at);
    }

    public function test_a_message_body_is_escaped_not_rendered(): void
    {
        $message = ContactMessage::factory()->create([
            'message' => 'Hello <script>alert(1)</script> and <b>bold</b>',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.messages.show', $message))
            ->assertOk()
            // This is text a stranger typed into a public form.
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_the_status_can_be_changed(): void
    {
        $message = ContactMessage::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.messages.status', $message), ['status' => 'replied'])
            ->assertRedirect();

        $this->assertSame(ContactMessageStatus::Replied, $message->refresh()->status);
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $message = ContactMessage::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.messages.status', $message), ['status' => 'archived'])
            ->assertSessionHasErrors('status');
    }

    public function test_messages_can_be_marked_in_bulk(): void
    {
        $messages = ContactMessage::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->post(route('admin.messages.bulk'), [
                'action' => 'spam',
                'ids' => $messages->pluck('id')->all(),
            ])
            ->assertRedirect();

        $this->assertSame(3, ContactMessage::where('status', ContactMessageStatus::Spam)->count());
    }

    public function test_messages_can_be_deleted_in_bulk(): void
    {
        $messages = ContactMessage::factory()->count(2)->create();

        $this->actingAs($this->admin)
            ->post(route('admin.messages.bulk'), [
                'action' => 'delete',
                'ids' => $messages->pluck('id')->all(),
            ])
            ->assertRedirect();

        $this->assertSame(0, ContactMessage::count());
        // Soft deleted, so an accidental bulk delete is recoverable.
        $this->assertSame(2, ContactMessage::withTrashed()->count());
    }

    public function test_the_unread_badge_updates_when_a_message_is_read(): void
    {
        $message = ContactMessage::factory()->create();

        // Stand in for a count cached a moment before the message was opened.
        Cache::put('admin.unread-messages', 99, now()->addMinute());

        $this->actingAs($this->admin)->get(route('admin.messages.show', $message))->assertOk();

        // A stale unread count is the sort of small wrongness that makes an
        // admin distrust the whole screen, so the cached value must not survive
        // a state change.
        $this->assertNotSame(99, Cache::get('admin.unread-messages'));
        $this->assertSame(0, ContactMessage::unread()->count());
    }

    public function test_the_inbox_is_closed_to_visitors(): void
    {
        $message = ContactMessage::factory()->create();

        $this->get(route('admin.messages.index'))->assertRedirect(route('login'));
        $this->get(route('admin.messages.show', $message))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.messages.index'))
            ->assertForbidden();
    }
}
