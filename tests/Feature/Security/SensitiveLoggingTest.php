<?php

namespace Tests\Feature\Security;

use App\Models\NewsCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Request bodies and personal data must not be written to the log.
 */
class SensitiveLoggingTest extends TestCase
{
    use RefreshDatabase;

    private function loggedAnything(string $needle): bool
    {
        foreach (['debug', 'info', 'notice', 'warning', 'error'] as $level) {
            try {
                Log::shouldHaveReceived($level)->withArgs(function (...$args) use ($needle) {
                    return str_contains(json_encode($args, JSON_UNESCAPED_UNICODE), $needle);
                });

                return true;
            } catch (\Throwable) {
                // not received at this level with that content
            }
        }

        return false;
    }

    public function test_creating_news_does_not_log_the_request(): void
    {
        Log::spy();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $category = NewsCategory::create(['name' => 'News']);

        // The form carries a one-time token (Phase 2).
        $this->actingAs($admin)->get(route('admin.news.create'))->assertOk();
        $token = collect(session('form_tokens.admin-news'))->last();

        $this->actingAs($admin)->post(route('admin.news.store'), [
            'title' => 'Log probe title',
            'language' => 'en',
            'news_category_id' => $category->id,
            'content' => '<p>SECRET-BODY-MARKER</p>',
            'status' => 'published',
            'form_token' => $token,
        ])->assertSessionHasNoErrors();

        $this->assertFalse($this->loggedAnything('SECRET-BODY-MARKER'));
    }

    public function test_subscriptions_do_not_log_personal_data(): void
    {
        Log::spy();

        // Plan subscriptions now go through the shared /inquiry endpoint.
        $guard = [
            \App\Http\Middleware\ProtectPublicForm::HONEYPOT => '',
            \App\Http\Middleware\ProtectPublicForm::TIMESTAMP => \Illuminate\Support\Facades\Crypt::encryptString((string) (now()->timestamp - 10)),
        ];
        $this->post(route('inquiry.store'), [
            'type' => 'subscription',
            'plan' => 'starter',
            'name' => 'Jane Probe',
            'email' => 'jane.probe@example.test',
            'phone' => '+251900000000',
        ] + $guard);
        $this->post(route('subscribe'), ['email' => 'newsletter.probe@example.test'] + $guard);

        $this->assertFalse($this->loggedAnything('jane.probe@example.test'));
        $this->assertFalse($this->loggedAnything('+251900000000'));
        $this->assertFalse($this->loggedAnything('newsletter.probe@example.test'));
    }
}
