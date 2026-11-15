<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\ProtectPublicForm;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Public forms (every message form via /inquiry, and the newsletter): rate limit 5/minute and
 * 20/hour per visitor, honeypot, minimum time-to-submit, friendly messages.
 */
class PublicFormProtectionTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTES = [
        'inquiry',
        'subscribe',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        RateLimiter::clear('public-forms');
    }

    /** Guard fields as a real browser would send them (page rendered $secondsAgo ago). */
    private function guard(int $secondsAgo = 10, string $honeypot = ''): array
    {
        return [
            ProtectPublicForm::HONEYPOT => $honeypot,
            ProtectPublicForm::TIMESTAMP => Crypt::encryptString((string) (now()->timestamp - $secondsAgo)),
        ];
    }

    private function subscribe(string $email, array $guard): \Illuminate\Testing\TestResponse
    {
        return $this->from('/')->post(route('subscribe'), ['email' => $email] + $guard);
    }

    private function formError(): ?string
    {
        return session('errors')?->getBag(ProtectPublicForm::ERROR_BAG)->first('form');
    }

    public function test_all_public_form_routes_are_rate_limited_and_guarded(): void
    {
        foreach (self::ROUTES as $uri) {
            $route = collect(Route::getRoutes()->getRoutes())
                ->first(fn ($r) => $r->uri() === $uri && in_array('POST', $r->methods(), true));
            $this->assertNotNull($route, "POST {$uri} must exist");
            $middleware = $route->gatherMiddleware();
            $this->assertContains('throttle:public-forms', $middleware, "{$uri} must be rate limited");
            $this->assertContains('form.guard', $middleware, "{$uri} must be guarded");
        }
    }

    public function test_a_normal_visitor_can_subscribe(): void
    {
        $this->subscribe('reader@example.test', $this->guard())
            ->assertRedirect('/#newsletter')
            ->assertSessionHas('newsletter_status');

        $this->assertDatabaseHas('subscribers', ['email' => 'reader@example.test']);
    }

    public function test_a_filled_honeypot_is_rejected_with_a_friendly_message(): void
    {
        $this->subscribe('bot@example.test', $this->guard(10, 'http://spam.example'))
            ->assertRedirect('/');

        $this->assertSame(ProtectPublicForm::MESSAGE, $this->formError());
        $this->assertSame(0, Subscriber::count());
    }

    public function test_a_submission_faster_than_three_seconds_is_rejected(): void
    {
        $this->subscribe('fast@example.test', $this->guard(1))->assertRedirect('/');

        $this->assertSame(ProtectPublicForm::MESSAGE, $this->formError());
        $this->assertSame(0, Subscriber::count());
    }

    public function test_a_missing_or_forged_timestamp_is_rejected(): void
    {
        $this->from('/')->post(route('subscribe'), ['email' => 'a@example.test', ProtectPublicForm::HONEYPOT => '']);
        $this->assertSame(ProtectPublicForm::MESSAGE, $this->formError());

        $this->from('/')->post(route('subscribe'), [
            'email' => 'b@example.test',
            ProtectPublicForm::HONEYPOT => '',
            ProtectPublicForm::TIMESTAMP => (string) (now()->timestamp - 60), // plain number, not encrypted
        ]);
        $this->assertSame(ProtectPublicForm::MESSAGE, $this->formError());

        $this->assertSame(0, Subscriber::count());
    }

    public function test_rejected_input_is_kept_for_the_visitor_but_guard_fields_are_not(): void
    {
        $this->subscribe('keep.me@example.test', $this->guard(1));

        $this->assertSame('keep.me@example.test', session()->getOldInput('email'));
        $this->assertNull(session()->getOldInput(ProtectPublicForm::TIMESTAMP));
        $this->assertNull(session()->getOldInput(ProtectPublicForm::HONEYPOT));
    }

    public function test_the_sixth_submission_in_a_minute_is_rate_limited(): void
    {
        foreach (range(1, 5) as $i) {
            $this->subscribe("user{$i}@example.test", $this->guard())->assertSessionHas('newsletter_status');
        }

        $this->subscribe('user6@example.test', $this->guard())->assertRedirect('/');

        $this->assertStringContainsString('several requests in a short time', $this->formError());
        $this->assertSame(5, Subscriber::count());
    }

    public function test_the_twenty_first_submission_in_an_hour_is_rate_limited(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            if ($i % 5 === 1 && $i > 1) {
                $this->travel(61)->seconds(); // stay under the per-minute limit
            }
            $this->subscribe("hour{$i}@example.test", $this->guard())->assertSessionHas('newsletter_status');
        }

        $this->travel(61)->seconds();
        $this->subscribe('hour21@example.test', $this->guard())->assertRedirect('/');

        $this->assertStringContainsString('several requests in a short time', $this->formError());
        $this->assertSame(20, Subscriber::count());
    }

    public function test_blocked_attempts_count_towards_the_rate_limit(): void
    {
        foreach (range(1, 5) as $_) {
            $this->subscribe('bot@example.test', $this->guard(10, 'filled')); // honeypot hits
        }

        $this->subscribe('human@example.test', $this->guard());

        $this->assertStringContainsString('several requests in a short time', $this->formError());
        $this->assertSame(0, Subscriber::count());
    }

    public function test_json_clients_get_proper_status_codes(): void
    {
        $this->postJson(route('subscribe'), ['email' => 'x@example.test'] + $this->guard(1))
            ->assertStatus(422)
            ->assertJson(['message' => ProtectPublicForm::MESSAGE]);

        RateLimiter::clear('public-forms');
        foreach (range(1, 5) as $i) {
            $this->postJson(route('subscribe'), ['email' => "j{$i}@example.test"] + $this->guard());
        }
        $this->postJson(route('subscribe'), ['email' => 'j6@example.test'] + $this->guard())->assertStatus(429);
    }

    public function test_the_contact_form_works_for_people_and_blocks_bots(): void
    {
        $fields = [
            'type' => 'contact', 'form_anchor' => 'contact-form', 'name' => 'Abebe Kebede',
            'email' => 'abebe@example.test', 'phone' => '+251900000001', 'message' => 'Hello there',
        ];

        $this->from('/contact')->post(route('inquiry.store'), $fields + $this->guard(10, 'spam'))
            ->assertRedirect('/contact#contact-form');
        $this->assertSame(0, Inquiry::count());
        Mail::assertNothingQueued();

        $this->from('/contact')->post(route('inquiry.store'), $fields + $this->guard())
            ->assertRedirect('/contact#contact-form')
            ->assertSessionHas('inquiry_success');
        $this->assertSame(1, Inquiry::count());
        Mail::assertQueued(InquiryReceived::class);
    }

    public function test_the_subscription_plan_form_blocks_bots(): void
    {
        $this->from('/subscribe/basic')->post(route('inquiry.store'), [
            'type' => 'subscription', 'plan' => 'basic', 'form_anchor' => 'subscribe-form',
            'name' => 'Bot', 'email' => 'bot@example.test', 'phone' => '0911000000',
        ] + $this->guard(0))->assertRedirect('/subscribe/basic#subscribe-form');

        $this->assertSame(0, Inquiry::count());
        $this->assertSame(ProtectPublicForm::MESSAGE, $this->formError());
    }

    public function test_the_forms_render_the_guard_fields_and_the_message(): void
    {
        foreach (['/', '/contact', '/subscribe/basic', '/services'] as $page) {
            $html = $this->get($page)->assertOk()->getContent();
            $this->assertStringContainsString('name="'.ProtectPublicForm::HONEYPOT.'"', $html, "{$page} needs the honeypot");
            $this->assertStringContainsString('name="'.ProtectPublicForm::TIMESTAMP.'"', $html, "{$page} needs the timestamp");
        }

        $this->from('/contact')->post(route('inquiry.store'), ['form_anchor' => 'contact-form'] + $this->guard(0));
        $this->followingRedirects()->get('/contact')->assertSee(ProtectPublicForm::MESSAGE);
    }
}
