<?php

namespace Tests\Feature\Security;

use App\Mail\NewsLetterMail;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The newsletter may only be sent by an admin, via POST + CSRF, after an
 * explicit confirmation, and at most 3 attempts per hour. Mail is always
 * faked here (phpunit.xml also forces MAIL_MAILER=array).
 */
class NewsletterAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    private function seedSubscribers(int $count = 2): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Subscriber::create(['email' => "reader{$i}@example.test"]);
        }
    }

    public function test_the_old_public_get_trigger_is_gone(): void
    {
        $this->seedSubscribers();

        $this->get('/send-newsletter')->assertNotFound();

        Mail::assertNothingSent();
    }

    public function test_guests_are_sent_to_login_and_nothing_is_sent(): void
    {
        $this->seedSubscribers();

        $this->get(route('admin.newsletter.confirm'))->assertRedirect(route('login'));
        $this->post(route('admin.newsletter.send'), ['confirm' => '1'])->assertRedirect(route('login'));

        Mail::assertNothingSent();
    }

    public function test_logged_in_non_admins_are_forbidden(): void
    {
        $this->seedSubscribers();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.newsletter.confirm'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.newsletter.send'), ['confirm' => '1'])->assertForbidden();

        Mail::assertNothingSent();
    }

    public function test_the_confirmation_page_sends_nothing(): void
    {
        $this->seedSubscribers(3);

        $this->actingAs($this->admin())
            ->get(route('admin.newsletter.confirm'))
            ->assertOk()
            ->assertSee('3 subscribers');

        Mail::assertNothingSent();
    }

    public function test_sending_requires_the_confirmation_checkbox(): void
    {
        $this->seedSubscribers();

        $this->actingAs($this->admin())
            ->post(route('admin.newsletter.send'))
            ->assertSessionHasErrors('confirm');

        Mail::assertNothingSent();
    }

    public function test_an_admin_can_send_after_confirming(): void
    {
        $this->seedSubscribers(2);

        $this->actingAs($this->admin())
            ->post(route('admin.newsletter.send'), ['confirm' => '1'])
            ->assertRedirect(route('admin.newsletter.confirm'))
            ->assertSessionHas('success', 'Newsletter sent to 2 subscribers.');

        Mail::assertSent(NewsLetterMail::class, 2);
        Mail::assertSent(NewsLetterMail::class, fn ($mail) => $mail->hasTo('reader1@example.test'));
    }

    public function test_the_send_route_only_accepts_post(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/newsletter/send')
            ->assertStatus(405);

        Mail::assertNothingSent();
    }

    public function test_the_send_route_is_csrf_protected_and_rate_limited(): void
    {
        $route = Route::getRoutes()->getByName('admin.newsletter.send');
        $middleware = $route->gatherMiddleware();

        // CSRF verification is part of the "web" group (Laravel skips it while running tests).
        $this->assertContains('web', $middleware);
        $this->assertContains('throttle:3,60', $middleware);
        $this->assertSame(['POST'], $route->methods());
    }

    public function test_more_than_three_attempts_per_hour_are_rejected(): void
    {
        $admin = $this->admin();

        foreach (range(1, 3) as $_) {
            $this->actingAs($admin)->post(route('admin.newsletter.send'))->assertSessionHasErrors('confirm');
        }

        $this->actingAs($admin)
            ->post(route('admin.newsletter.send'), ['confirm' => '1'])
            ->assertStatus(429);

        Mail::assertNothingSent();
    }
}
