<?php

namespace Tests\Feature\Public;

use App\Enums\SubscriberStatus;
use App\Mail\NewsletterConfirmation;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\Post;
use App\Models\PostView;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class EngagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        Cache::flush();
        RateLimiter::clear('newsletter');
        RateLimiter::clear('contact');
    }

    public function test_subscribing_creates_a_pending_record_and_sends_a_confirmation(): void
    {
        Mail::fake();

        $this->postJson(route('newsletter.subscribe'), ['email' => 'reader@example.test'])
            ->assertOk()
            ->assertJsonStructure(['message']);

        $subscriber = NewsletterSubscriber::firstWhere('email', 'reader@example.test');

        // Double opt-in: nothing is sent to this address until it is confirmed.
        $this->assertSame(SubscriberStatus::Pending, $subscriber->status);
        Mail::assertSent(NewsletterConfirmation::class);
    }

    public function test_confirming_activates_the_subscription(): void
    {
        Mail::fake();
        $this->postJson(route('newsletter.subscribe'), ['email' => 'reader@example.test']);

        $subscriber = NewsletterSubscriber::first();

        $this->get(route('newsletter.confirm', $subscriber->token))->assertRedirect(route('home'));

        $subscriber->refresh();

        $this->assertSame(SubscriberStatus::Subscribed, $subscriber->status);
        $this->assertNotNull($subscriber->confirmed_at);
    }

    public function test_unsubscribing_works_in_one_click_and_never_reveals_membership(): void
    {
        $subscriber = NewsletterSubscriber::factory()->subscribed()->create();

        $this->get(route('newsletter.unsubscribe', $subscriber->token))
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $this->assertSame(SubscriberStatus::Unsubscribed, $subscriber->fresh()->status);

        // An unknown token gets the identical response, so the endpoint cannot
        // be used to test whether an address is on the list.
        $this->get(route('newsletter.unsubscribe', 'not-a-real-token'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');
    }

    public function test_subscribing_twice_does_not_create_a_duplicate(): void
    {
        Mail::fake();

        $this->postJson(route('newsletter.subscribe'), ['email' => 'reader@example.test']);
        $this->postJson(route('newsletter.subscribe'), ['email' => 'reader@example.test']);

        $this->assertDatabaseCount('newsletter_subscribers', 1);
    }

    public function test_the_newsletter_honeypot_rejects_bots(): void
    {
        $this->postJson(route('newsletter.subscribe'), [
            'email' => 'bot@example.test',
            'website' => 'http://spam.test',
        ])->assertStatus(422);

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_a_contact_message_is_stored_without_the_senders_ip(): void
    {
        $this->post(route('contact.submit'), [
            'name' => 'Asha',
            'email' => 'asha@example.test',
            'subject' => 'A correction',
            'message' => 'The date in your metro article looks wrong to me.',
        ])->assertSessionHasNoErrors();

        $message = ContactMessage::first();

        $this->assertSame('Asha', $message->name);
        // Stored as a salted hash, never as a raw address.
        $this->assertSame(64, strlen($message->ip_hash));
        $this->assertStringNotContainsString('127.0.0.1', $message->ip_hash);
    }

    public function test_the_contact_honeypot_rejects_bots(): void
    {
        $this->post(route('contact.submit'), [
            'name' => 'Bot',
            'email' => 'bot@example.test',
            'subject' => 'Cheap stuff',
            'message' => 'Buy things at this link right now please.',
            'website' => 'http://spam.test',
        ])->assertSessionHasErrors('website');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_a_message_stuffed_with_links_is_rejected(): void
    {
        $this->post(route('contact.submit'), [
            'name' => 'Spammer',
            'email' => 'spam@example.test',
            'subject' => 'Great offers',
            'message' => 'http://a.test http://b.test http://c.test http://d.test http://e.test',
        ])->assertSessionHasErrors('message');
    }

    public function test_liking_a_post_toggles_and_recounts(): void
    {
        $post = Post::factory()->create();

        $this->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertJson(['liked' => true, 'count' => 1]);

        $this->assertSame(1, $post->fresh()->likes_count);

        $this->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertJson(['liked' => false, 'count' => 0]);

        $this->assertSame(0, $post->fresh()->likes_count);
    }

    public function test_a_draft_cannot_be_liked(): void
    {
        $this->postJson(route('posts.like', Post::factory()->draft()->create()))->assertNotFound();
    }

    public function test_reading_a_post_records_one_view_per_visitor_window(): void
    {
        $post = Post::factory()->create(['views_count' => 0]);

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0)')->get(route('posts.show', $post));
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0)')->get(route('posts.show', $post));

        // The second read falls inside the dedupe window and is not counted.
        $this->assertSame(1, PostView::where('post_id', $post->id)->count());
        $this->assertSame(1, $post->fresh()->views_count);
    }

    public function test_a_view_never_stores_a_raw_ip(): void
    {
        $post = Post::factory()->create();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0)')->get(route('posts.show', $post));

        $view = PostView::first();

        $this->assertSame(64, strlen($view->ip_hash));
        $this->assertStringNotContainsString('127.0.0.1', $view->ip_hash);
    }

    public function test_crawlers_and_admins_do_not_inflate_the_view_count(): void
    {
        $post = Post::factory()->create(['views_count' => 0]);

        $this->withHeader('User-Agent', 'Googlebot/2.1 (+http://www.google.com/bot.html)')
            ->get(route('posts.show', $post));

        $this->actingAs(User::factory()->admin()->create())
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0)')
            ->get(route('posts.show', $post));

        $this->assertSame(0, PostView::count());
        $this->assertSame(0, $post->fresh()->views_count);
    }
}
