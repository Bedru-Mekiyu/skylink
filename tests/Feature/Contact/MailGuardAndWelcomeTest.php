<?php

namespace Tests\Feature\Contact;

use App\Mail\InquiryConfirmation;
use App\Mail\NewsletterWelcome;
use App\Models\Inquiry;
use App\Models\Subscriber;
use App\Models\User;
use App\Http\Middleware\ProtectPublicForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Visitor confirmation language, newsletter welcome mail, and the test-site-only mail guard. */
class MailGuardAndWelcomeTest extends TestCase
{
    use RefreshDatabase;

    private function sentMessages(): array
    {
        return array_map(fn ($m) => $m->getOriginalMessage(), app('mailer')->getSymfonyTransport()->messages()->all());
    }

    private function useRealMailer(): void
    {
        config(['queue.default' => 'sync', 'mail.default' => 'array', 'contact.inbox' => 'company@example.test', 'contact.inboxes' => ['contact' => 'company@example.test']]);
    }

    private function guardFields(): array
    {
        return [ProtectPublicForm::HONEYPOT => '', ProtectPublicForm::TIMESTAMP => Crypt::encryptString((string) now()->subMinute()->timestamp)];
    }

    private function send(array $o): void
    {
        $this->from('/contact')->post(route('inquiry.store'), $o + [
            'type' => 'contact', 'form_anchor' => 'contact-form', 'name' => 'Visitor', 'email' => 'visitor@example.test', 'message' => 'Hello there',
            ProtectPublicForm::HONEYPOT => '', ProtectPublicForm::TIMESTAMP => Crypt::encryptString((string) now()->subMinute()->timestamp),
        ]);
    }

    // ---------------------------------------------------------------- visitor confirmation language
    public function test_the_visitor_confirmation_follows_the_visitors_language(): void
    {
        $this->useRealMailer();

        $this->send(['name' => 'John', 'message' => 'I need a quote for a website']);
        $this->send(['name' => 'ሰላም', 'email' => 'selam@example.test', 'message' => 'ድረ ገጽ እፈልጋለሁ']);

        [$teamEn, $visitorEn, $teamAm, $visitorAm] = $this->sentMessages();

        $this->assertStringContainsString('We received your message', $visitorEn->getSubject());
        $this->assertStringNotContainsString('መልዕክትዎ', $visitorEn->getSubject());
        $this->assertStringContainsString('Thank you', $visitorEn->getHtmlBody());
        $this->assertStringNotContainsString('ደርሶናል', $visitorEn->getHtmlBody().$visitorEn->getTextBody());

        $this->assertStringContainsString('መልዕክትዎ ደርሶናል', $visitorAm->getSubject());
        $this->assertStringNotContainsString('We received', $visitorAm->getSubject());
        $this->assertStringContainsString('ደርሶናል', $visitorAm->getHtmlBody());
        $this->assertStringNotContainsString('Our team will get back', $visitorAm->getHtmlBody().$visitorAm->getTextBody());

        // the company notification carries the form type, name, email, phone, message, page and time
        $html = $teamEn->getHtmlBody();
        foreach (['John', 'visitor@example.test', 'I need a quote for a website'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertSame('company@example.test', $teamEn->getTo()[0]->getAddress());
    }

    // ---------------------------------------------------------------- newsletter
    public function test_a_new_subscriber_gets_one_welcome_email_with_a_working_unsubscribe_link_and_a_duplicate_gets_none(): void
    {
        Mail::fake();

        $this->from('/')->post(route('subscribe'), ['email' => 'New.Person@Example.test'] + $this->guardFields())->assertSessionHas('newsletter_status');
        $this->assertDatabaseHas('subscribers', ['email' => 'new.person@example.test']);
        Mail::assertQueued(NewsletterWelcome::class, 1);
        Mail::assertQueued(NewsletterWelcome::class, fn ($m) => $m->hasTo('new.person@example.test') && $m->language === 'en');

        // the same address again: same friendly answer, no second email, no second row
        $this->from('/')->post(route('subscribe'), ['email' => 'new.person@example.test'] + $this->guardFields())->assertSessionHas('newsletter_status', 'Thank you! You are on our newsletter list.');
        Mail::assertQueued(NewsletterWelcome::class, 1);
        $this->assertSame(1, Subscriber::count());

        // invalid address: an error, nothing saved or mailed
        $this->from('/')->post(route('subscribe'), ['email' => 'nope'] + $this->guardFields())->assertSessionHasErrors('email', null, 'newsletter');
        Mail::assertQueued(NewsletterWelcome::class, 1);
    }

    public function test_the_welcome_email_is_amharic_for_an_amharic_browser_and_its_unsubscribe_link_works(): void
    {
        Mail::fake();
        $this->withHeaders(['Accept-Language' => 'am-ET,am;q=0.9,en;q=0.5'])->from('/')->post(route('subscribe'), ['email' => 'selam@example.test'] + $this->guardFields());
        $welcome = null;
        Mail::assertQueued(NewsletterWelcome::class, function ($m) use (&$welcome) { $welcome = $m; return $m->language === 'am'; });

        $html = $welcome->render();
        $this->assertStringContainsString('ተመዝግበዋል', $html);
        $this->assertStringNotContainsString('you are now on', $html);
        $this->assertStringContainsString('ተመዝግበዋል', $welcome->envelope()->subject);

        // the link in the email: opening it asks, confirming removes the address
        $url = $welcome->unsubscribeUrl;
        $this->assertStringContainsString('signature=', $url);
        $this->get($url)->assertOk();
        $this->assertSame(1, Subscriber::count());
        preg_match('~/newsletter/unsubscribe/(\d+)~', $url, $m);
        $confirm = \Illuminate\Support\Facades\URL::signedRoute('newsletter.unsubscribe.confirm', ['subscriber' => $m[1]]);
        $this->post($confirm)->assertOk();
        $this->assertSame(0, Subscriber::count());
    }

    // ---------------------------------------------------------------- staging guard
    public function test_with_the_guard_off_mail_goes_to_its_recipients(): void
    {
        $this->useRealMailer();
        config(['mail.staging_guard.enabled' => false]);

        Mail::raw('hi', fn ($m) => $m->to('customer@example.test')->subject('Hello'));

        [$msg] = $this->sentMessages();
        $this->assertSame('customer@example.test', $msg->getTo()[0]->getAddress());
        $this->assertSame('Hello', $msg->getSubject());
    }

    public function test_with_the_guard_on_only_company_and_allowlisted_addresses_get_mail_everyone_else_is_redirected(): void
    {
        $this->useRealMailer();
        config(['mail.staging_guard.enabled' => true, 'mail.staging_guard.allowlist' => ['tester@example.test'], 'mail.staging_guard.redirect' => 'tester@example.test']);

        Mail::raw('hi', fn ($m) => $m->to('customer@example.test', 'Customer')->cc('another@example.test')->bcc('company@example.test')->subject('Hello'));
        Mail::raw('hi', fn ($m) => $m->to('company@example.test')->subject('For the company'));
        Mail::raw('hi', fn ($m) => $m->to('Tester@Example.test')->subject('For the tester'));

        [$a, $b, $c] = $this->sentMessages();

        $everyone = fn ($msg) => array_map(fn ($x) => $x->getAddress(), array_merge($msg->getTo(), $msg->getCc(), $msg->getBcc()));
        $this->assertSame(['tester@example.test', 'company@example.test'], array_values(array_unique($everyone($a))), 'the customer and the other cc went to the tester; the company bcc stayed');
        $this->assertNotContains('customer@example.test', $everyone($a));
        $this->assertNotContains('another@example.test', $everyone($a));
        $this->assertStringStartsWith('[TEST SITE, meant for customer@example.test, another@example.test] Hello', $a->getSubject());
        $this->assertSame('on', $a->getHeaders()->get('X-Staging-Guard')->getBodyAsString());

        $this->assertSame(['company@example.test'], $everyone($b));
        $this->assertSame('For the company', $b->getSubject(), 'allowed recipients: subject untouched');
        $this->assertSame(['tester@example.test'], array_map('strtolower', $everyone($c)));
    }

    public function test_with_the_guard_on_and_no_redirect_address_other_recipients_get_nothing(): void
    {
        $this->useRealMailer();
        config(['mail.staging_guard.enabled' => true, 'mail.staging_guard.allowlist' => [], 'mail.staging_guard.redirect' => null]);

        Mail::raw('hi', fn ($m) => $m->to('customer@example.test')->subject('Hello'));
        $this->assertCount(0, $this->sentMessages(), 'cancelled, not sent');

        Mail::raw('hi', fn ($m) => $m->to('company@example.test')->subject('For the company'));
        $this->assertCount(1, $this->sentMessages());
    }

    public function test_real_form_emails_on_the_test_site_never_reach_the_visitor_address(): void
    {
        $this->useRealMailer();
        config(['mail.staging_guard.enabled' => true, 'mail.staging_guard.allowlist' => ['tester@example.test'], 'mail.staging_guard.redirect' => 'tester@example.test']);

        $this->send(['email' => 'real.customer@example.test']);

        $to = [];
        foreach ($this->sentMessages() as $m) {
            foreach (array_merge($m->getTo(), $m->getCc(), $m->getBcc()) as $a) {
                $to[] = $a->getAddress();
            }
        }
        $this->assertNotContains('real.customer@example.test', $to);
        $this->assertEqualsCanonicalizing(['company@example.test', 'tester@example.test'], array_unique($to));
        $this->assertSame(1, Inquiry::count(), 'the message itself is still saved in the inbox');
    }

    public function test_the_admin_newsletter_campaign_is_blocked_on_the_test_site(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        Subscriber::create(['email' => 'real.subscriber@example.test']);

        config(['mail.staging_guard.enabled' => true]);
        $this->actingAs($admin)->post(route('admin.newsletter.send'), ['confirm' => '1'])->assertRedirect(route('admin.newsletter.confirm'))->assertSessionHas('error');
        Mail::assertNothingSent();
        Mail::assertNothingQueued();

        config(['mail.staging_guard.enabled' => false]);
        $this->actingAs($admin)->post(route('admin.newsletter.send'), ['confirm' => '1'])->assertSessionHas('success');
        Mail::assertSent(\App\Mail\NewsLetterMail::class, 1);
    }

    public function test_the_guard_is_off_unless_switched_on(): void
    {
        $this->assertFalse((bool) config('mail.staging_guard.enabled'));
        $this->assertNull(config('mail.staging_guard.redirect'));
    }
}
