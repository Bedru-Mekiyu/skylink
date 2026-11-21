<?php

namespace Tests\Feature\Contact;

use App\Http\Middleware\ProtectPublicForm;
use App\Mail\InquiryConfirmation;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** One inbox for every website form: save first, queued emails, admin inbox. */
class InquiryTest extends TestCase
{
    use RefreshDatabase;

    private const INBOX = 'info@skylinkict.com';

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-forms');
        config([
            'contact.inbox' => self::INBOX,
            'contact.inboxes' => array_fill_keys(array_keys(Inquiry::TYPES), self::INBOX),
            'contact.confirm_visitor' => true,
        ]);
    }

    private function guard(): array
    {
        return [
            ProtectPublicForm::HONEYPOT => '',
            ProtectPublicForm::TIMESTAMP => Crypt::encryptString((string) (now()->timestamp - 10)),
        ];
    }

    private function send(string $from, array $fields)
    {
        return $this->from($from)->post(route('inquiry.store'), $fields + $this->guard());
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    // ----- the single setting -------------------------------------------------

    /** Re-evaluates config/contact.php with the given environment. */
    private function contactConfigWith(array $env): array
    {
        $keys = ['CONTACT_INBOX', 'QUOTE_INBOX', 'SUPPORT_INBOX', 'MAIL_FROM_ADDRESS', 'COMPANY_EMAIL'];
        $saved = [];
        foreach ($keys as $k) {
            $saved[$k] = [$_ENV[$k] ?? null, $_SERVER[$k] ?? null, getenv($k)];
            $v = $env[$k] ?? null;
            if ($v === null) {
                unset($_ENV[$k], $_SERVER[$k]);
                putenv($k);
            } else {
                $_ENV[$k] = $_SERVER[$k] = $v;
                putenv("{$k}={$v}");
            }
        }
        try {
            return require config_path('contact.php');
        } finally {
            foreach ($saved as $k => [$e, $s, $g]) {
                $e === null ? \Illuminate\Support\Arr::forget($_ENV, $k) : $_ENV[$k] = $e;
                $s === null ? \Illuminate\Support\Arr::forget($_SERVER, $k) : $_SERVER[$k] = $s;
                $g === false ? putenv($k) : putenv("{$k}={$g}");
            }
        }
    }

    public function test_one_env_line_sets_the_inbox_for_every_form_type(): void
    {
        $c = $this->contactConfigWith(['CONTACT_INBOX' => 'team@example.test', 'QUOTE_INBOX' => '', 'SUPPORT_INBOX' => '']);
        $this->assertSame('team@example.test', $c['inbox']);
        foreach (array_keys(Inquiry::TYPES) as $type) {
            $this->assertSame('team@example.test', $c['inboxes'][$type] ?? $c['inbox'], "type {$type}");
        }

        // Optional per-type inboxes win when set.
        $c = $this->contactConfigWith(['CONTACT_INBOX' => 'team@example.test', 'QUOTE_INBOX' => 'sales@example.test', 'SUPPORT_INBOX' => 'help@example.test']);
        $this->assertSame('sales@example.test', $c['inboxes']['quote']);
        $this->assertSame('sales@example.test', $c['inboxes']['service']);
        $this->assertSame('help@example.test', $c['inboxes']['support']);
        $this->assertSame('team@example.test', $c['inboxes']['contact']);

        // No CONTACT_INBOX at all: the company address (COMPANY_EMAIL), never nothing.
        $c = $this->contactConfigWith(['COMPANY_EMAIL' => 'company@example.test']);
        $this->assertSame('company@example.test', $c['inboxes']['subscription']);
        $c = $this->contactConfigWith([]);
        $this->assertSame('info@skylinkict.com', $c['inboxes']['subscription']);
    }

    // ----- every form type ----------------------------------------------------

    public static function formTypes(): array
    {
        return [
            'contact page, general' => ['/contact', 'contact-form', ['type' => 'contact']],
            'contact page, support' => ['/contact', 'contact-form', ['type' => 'support']],
            'contact page, quote' => ['/contact', 'contact-form', ['type' => 'quote', 'service' => 'Network', 'phone' => '+251 911 000 000']],
            'services quote form' => ['/services', 'quote-form', ['type' => 'quote', 'service' => 'Web Development', 'phone' => '0911000000']],
            'service detail form' => ['/services/detail/web-apps', 'service-form', ['type' => 'service', 'service' => 'Web Applications', 'phone' => '0911000000']],
            'plan subscription' => ['/subscribe/starter', 'subscribe-form', ['type' => 'subscription', 'plan' => 'starter', 'phone' => '0911000000']],
        ];
    }

    /** @dataProvider formTypes */
    public function test_each_form_saves_first_then_queues_team_and_visitor_emails(string $page, string $anchor, array $extra): void
    {
        Mail::fake();
        $this->seed(\Database\Seeders\ServiceSeeder::class); // service detail pages need their service

        $response = $this->send($page, $extra + [
            'form_anchor' => $anchor,
            'name' => 'ሄኖክ ታደሰ (Henok Tadesse)',
            'email' => 'henok@example.test',
            'message' => 'ሰላም፣ ስለ ዋጋ መረጃ እፈልጋለሁ። Price please.',
        ]);

        $inquiry = Inquiry::sole();
        $response->assertRedirect($page.'#'.$anchor)->assertSessionHas('inquiry_success.reference', $inquiry->reference);
        $this->assertSame($extra['type'], $inquiry->type);
        $this->assertSame('am', $inquiry->language);
        $this->assertStringContainsString('ሰላም፣ ስለ ዋጋ', $inquiry->message);
        $this->assertMatchesRegularExpression('/^SK-\d{6}-[A-Z2-9]{5}$/', $inquiry->reference);
        $this->assertNotNull($inquiry->ip_hash);

        Mail::assertQueued(InquiryReceived::class, fn ($m) => $m->hasTo(self::INBOX) && $m->hasReplyTo('henok@example.test'));
        Mail::assertQueued(InquiryConfirmation::class, fn ($m) => $m->hasTo('henok@example.test') && $m->hasReplyTo(self::INBOX));

        // The visitor sees the thank-you message with the reference, at the form.
        $this->get($page)->assertSee('Your reference number is', false)->assertSee($inquiry->reference);
    }

    public function test_validation_errors_are_shown_at_the_form_and_input_is_kept(): void
    {
        Mail::fake();

        $this->send('/services', ['type' => 'quote', 'form_anchor' => 'quote-form', 'name' => 'Abebe', 'email' => 'not-an-email', 'message' => 'Hi there'])
            ->assertRedirect('/services#quote-form')
            ->assertSessionHasErrors(['email', 'phone', 'service']);
        $this->assertSame(0, Inquiry::count());
        Mail::assertNothingQueued();

        $this->get('/services')
            ->assertSee('Your message was not sent yet.')
            ->assertSee('value="Abebe"', false);
    }

    public function test_a_mail_server_failure_never_reaches_the_visitor(): void
    {
        // Sent immediately (no queue worker) to a closed port: the send fails.
        config([
            'queue.default' => 'sync',
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.scheme' => 'smtp',
            'mail.mailers.smtp.timeout' => 2,
        ]);

        $this->send('/contact', ['type' => 'contact', 'form_anchor' => 'contact-form', 'name' => 'Abebe', 'email' => 'abebe@example.test', 'message' => 'Hello there'])
            ->assertRedirect('/contact#contact-form')
            ->assertSessionHas('inquiry_success');

        $inquiry = Inquiry::sole(); // saved anyway
        $this->assertNotNull($inquiry->team_notify_error);
        $this->assertNull($inquiry->team_notified_at);
    }

    public function test_sent_emails_are_recorded_and_complete(): void
    {
        config(['queue.default' => 'sync', 'mail.default' => 'array']);

        $this->send('/contact', ['type' => 'support', 'form_anchor' => 'contact-form', 'name' => 'Selam & Co', 'email' => 'selam@example.test', 'phone' => '+251 911 234 567', 'company' => 'Addis PLC', 'message' => "ሰላም!\nLine two <b>not html</b>"]);

        $inquiry = Inquiry::sole();
        $this->assertNotNull($inquiry->team_notified_at);
        $this->assertNotNull($inquiry->visitor_confirmed_at);

        $sent = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(2, $sent);
        $team = $sent[0]->getOriginalMessage();
        $this->assertSame(self::INBOX, $team->getTo()[0]->getAddress());
        $this->assertSame('selam@example.test', $team->getReplyTo()[0]->getAddress());
        $this->assertStringContainsString($inquiry->reference, $team->getSubject());
        $html = $team->getHtmlBody();
        $text = $team->getTextBody();
        foreach (['Selam &amp; Co', 'Addis PLC', '+251 911 234 567', 'ሰላም!', '&lt;b&gt;not html&lt;/b&gt;'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringContainsString('Selam & Co', $text);
        $this->assertStringNotContainsString('<p>', $text, 'the plain-text part must be plain text');

        $visitor = $sent[1]->getOriginalMessage();
        $this->assertSame('selam@example.test', $visitor->getTo()[0]->getAddress());
        $this->assertSame(self::INBOX, $visitor->getReplyTo()[0]->getAddress());
        $this->assertStringContainsString('መልዕክትዎ ደርሶናል', $visitor->getSubject());
        $this->assertStringNotContainsString('Line two', $visitor->getHtmlBody(), 'the confirmation must not echo visitor text');
        $this->assertStringNotContainsString('Selam', $visitor->getHtmlBody());
    }

    public function test_bots_are_still_blocked_and_nothing_is_saved(): void
    {
        Mail::fake();
        $this->from('/contact')->post(route('inquiry.store'), [
            'type' => 'contact', 'form_anchor' => 'contact-form', 'name' => 'Bot', 'email' => 'bot@example.test', 'message' => 'spam spam',
            ProtectPublicForm::HONEYPOT => 'http://spam', ProtectPublicForm::TIMESTAMP => Crypt::encryptString((string) now()->timestamp),
        ])->assertRedirect('/contact#contact-form');

        $this->assertSame(0, Inquiry::count());
        Mail::assertNothingQueued();
        $this->get('/contact')->assertSee(ProtectPublicForm::MESSAGE);
    }

    public function test_the_old_duplicate_endpoints_are_gone(): void
    {
        foreach (['/contact-submit', '/contact-message', '/request-a-quote', '/quote/request', '/chat-submit', '/subscribe/process', '/admin/contact-message'] as $uri) {
            $status = $this->post($uri, ['name' => 'x'])->getStatusCode();
            $this->assertContains($status, [404, 405], "POST {$uri} should no longer exist (got {$status})");
        }
    }

    public function test_the_pages_post_to_the_single_endpoint(): void
    {
        $this->seed(\Database\Seeders\ServiceSeeder::class);
        $action = 'action="'.route('inquiry.store').'"';
        $this->get('/contact')->assertSee($action, false)->assertSee('name="company"', false);
        $this->get('/services')->assertSee($action, false)->assertDontSee('Thank you for your inquiry! We will get back to you within 24 hours.');
        $this->get('/services/detail/web-apps')->assertSee($action, false)->assertSee('value="service"', false);
        $this->get('/subscribe/starter')->assertSee($action, false)->assertSee('value="starter"', false);
        // An odd plan in the address is not echoed into the form.
        $this->get('/subscribe/'.rawurlencode('x"><script>'))->assertOk()->assertDontSee('x"><script>', false)->assertSee('value="premium"', false);
    }

    // ----- admin inbox -------------------------------------------------------

    public function test_the_admin_inbox_lists_filters_and_manages_messages(): void
    {
        Mail::fake();
        $this->send('/contact', ['type' => 'contact', 'name' => 'Abebe Kebede', 'email' => 'abebe@example.test', 'message' => 'General question here']);
        $this->send('/services', ['type' => 'quote', 'name' => 'Tigist Haile', 'email' => 'tigist@example.test', 'phone' => '0911000000', 'service' => 'CCTV', 'message' => 'Quote please']);
        [$contact, $quote] = Inquiry::orderBy('id')->get()->all();

        $this->get(route('admin.inbox.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.inbox.index'))->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.inbox.index'))->assertOk()
            ->assertSee('Abebe Kebede')->assertSee('Tigist Haile')->assertSee(self::INBOX)->assertSee('Unread only (2)');
        $this->actingAs($admin)->get(route('admin.inbox.index', ['type' => 'quote']))->assertSee('Tigist Haile')->assertDontSee('Abebe Kebede');
        $this->actingAs($admin)->get(route('admin.inbox.index', ['q' => $contact->reference]))->assertSee('Abebe Kebede')->assertDontSee('Tigist Haile');

        $this->actingAs($admin)->get(route('admin.inbox.show', $quote))->assertOk()
            ->assertSee('Quote please')->assertSee('mailto:tigist@example.test', false);
        $this->assertNotNull($quote->fresh()->read_at);

        $this->actingAs($admin)->patch(route('admin.inbox.update', $quote), ['status' => 'done'])->assertSessionHas('success');
        $this->assertSame('done', $quote->fresh()->status);

        Mail::fake();
        $this->actingAs($admin)->post(route('admin.inbox.resend', $quote))->assertSessionHas('success');
        Mail::assertQueued(InquiryReceived::class, fn ($m) => $m->hasTo(self::INBOX));

        $this->actingAs($admin)->delete(route('admin.inbox.destroy', $contact))->assertRedirect(route('admin.inbox.index'));
        $this->assertModelMissing($contact); // deleted for good (export the CSV first)

        // Sidebar badge on every admin page.
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('Inbox');
    }

    public function test_existing_messages_are_copied_into_the_inbox_by_the_migration(): void
    {
        Schema::drop('inquiries');
        DB::table('contact_messages')->insert(['first_name' => 'Old', 'last_name' => 'Contact', 'email' => 'old@example.test', 'phone' => '1', 'company' => 'Co', 'message' => 'old message', 'created_at' => '2025-01-01', 'updated_at' => '2025-01-01']);
        DB::table('service_requests')->insert(['name' => 'Old Quote', 'email' => 'q@example.test', 'phone' => '2', 'service' => 'smart-city', 'message' => 'ዋጋ', 'created_at' => '2025-02-01', 'updated_at' => '2025-02-01']);
        DB::table('subscriptions')->insert(['name' => 'Old Plan', 'email' => 'p@example.test', 'phone' => '3', 'plan' => 'starter', 'notes' => null, 'created_at' => '2025-03-01', 'updated_at' => '2025-03-01']);

        (require database_path('migrations/2026_11_18_100000_create_inquiries_table.php'))->up();

        $this->assertSame(3, Inquiry::count());
        $this->assertSame(1, DB::table('contact_messages')->count(), 'originals stay');
        $old = Inquiry::where('legacy_table', 'contact_messages')->sole();
        $this->assertSame(['Old Contact', 'contact', 'done', 'Co'], [$old->name, $old->type, $old->status, $old->company]);
        $this->assertSame('am', Inquiry::where('legacy_table', 'service_requests')->sole()->language);
        $this->assertSame('starter', Inquiry::where('legacy_table', 'subscriptions')->sole()->plan);
    }

    public function test_old_messages_are_anonymised_after_the_retention_period(): void
    {
        Mail::fake();
        $this->send('/contact', ['type' => 'contact', 'name' => 'Old Person', 'email' => 'old@example.test', 'message' => 'old text here']);
        $old = Inquiry::sole();
        $old->forceFill(['created_at' => now()->subMonths(25)])->save();
        $this->send('/contact', ['type' => 'contact', 'name' => 'New Person', 'email' => 'new@example.test', 'message' => 'new text here']);

        $this->artisan('inquiries:anonymize', ['--dry-run' => true])->expectsOutputToContain('1 message(s)')->assertSuccessful();
        $this->assertSame('Old Person', $old->fresh()->name);

        $this->artisan('inquiries:anonymize')->assertSuccessful();
        $old->refresh();
        $this->assertSame(['(removed)', null, null], [$old->name, $old->message, $old->phone]);
        $this->assertSame('contact', $old->type);
        $this->assertSame('New Person', Inquiry::where('email', 'new@example.test')->sole()->name);
    }

    // ----- newsletter ---------------------------------------------------------

    public function test_newsletter_answers_the_same_for_new_and_existing_addresses(): void
    {
        $this->from('/')->post(route('subscribe'), ['email' => 'Reader@Example.test', 'form_anchor' => 'newsletter'] + $this->guard())
            ->assertRedirect('/#newsletter')->assertSessionHas('newsletter_status');
        RateLimiter::clear('public-forms');
        $this->from('/')->post(route('subscribe'), ['email' => 'reader@example.test', 'form_anchor' => 'newsletter'] + $this->guard())
            ->assertRedirect('/#newsletter')->assertSessionHas('newsletter_status')->assertSessionHasNoErrors();

        $this->assertSame(1, Subscriber::count());
        $this->get('/')->assertSee('You are on our newsletter list.');
    }

    public function test_newsletter_unsubscribe_needs_a_signed_link_and_a_confirmation(): void
    {
        $s = Subscriber::create(['email' => 'leave@example.test']);

        $this->get('/newsletter/unsubscribe/'.$s->id)->assertForbidden();

        $url = URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $s->id]);
        $this->get($url)->assertOk()->assertSee('Yes, unsubscribe me');
        $this->assertSame(1, Subscriber::count(), 'opening the link alone changes nothing');

        $confirm = URL::signedRoute('newsletter.unsubscribe.confirm', ['subscriber' => $s->id]);
        $this->post($confirm)->assertOk()->assertSee('You are unsubscribed');
        $this->assertSame(0, Subscriber::count());

        $mail = new \App\Mail\NewsLetterMail('News', Subscriber::create(['email' => 'x@example.test']));
        $this->assertStringContainsString('newsletter/unsubscribe/', $mail->render());
    }
}
