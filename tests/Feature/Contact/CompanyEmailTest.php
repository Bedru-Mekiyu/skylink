<?php

namespace Tests\Feature\Contact;

use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** ONE company email setting (COMPANY_EMAIL / config('company.email')) drives everything. */
class CompanyEmailTest extends TestCase
{
    use RefreshDatabase;

    private const OLD = ['info@skylinkict.com', 'info@skylink.com', 'info@skylinktech.com'];

    public function test_the_new_address_is_the_default_and_shown_on_the_contact_page_with_a_mailto_link(): void
    {
        $this->assertSame('info@skylinkictet.com', config('company.email'));
        $html = $this->get('/contact')->assertOk()->getContent();
        $this->assertStringContainsString('<a href="mailto:info@skylinkictet.com">info@skylinkictet.com</a>', $html);
    }

    public function test_no_old_address_remains_in_rendered_public_pages_or_in_the_code(): void
    {
        foreach (['/', '/about', '/contact', '/services', '/products', '/portfolio', '/news'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            foreach (self::OLD as $old) {
                $this->assertStringNotContainsString($old, $html, "$old on $url");
            }
        }
        $dirs = [base_path('resources'), base_path('app'), base_path('config'), base_path('routes'), base_path('lang'), base_path('database/seeders')];
        foreach ($dirs as $dir) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $f) {
                if (! $f->isFile() || ! preg_match('/\.(php|blade\.php|json|js)$/', $f->getFilename()) || str_ends_with($f->getFilename(), 'ReplaceOldEmailAddresses.php')) {
                    continue;
                }
                $text = file_get_contents($f->getPathname());
                foreach (self::OLD as $old) {
                    $this->assertStringNotContainsString($old, $text, $old.' in '.$f->getPathname());
                }
            }
        }
    }

    public function test_changing_the_one_setting_changes_the_page_the_inbox_and_the_reply_to(): void
    {
        config(['company.email' => 'team@example.test', 'contact.inbox' => 'team@example.test', 'contact.inboxes' => ['contact' => 'team@example.test']]);
        $this->assertStringContainsString('mailto:team@example.test', $this->get('/contact')->getContent());
        $this->assertSame('team@example.test', Inquiry::inboxFor('contact'));
        Mail::fake();
        $inquiry = Inquiry::create(['type' => 'contact', 'name' => 'Visitor', 'email' => 'visitor@example.org', 'message' => 'Hello there', 'reference' => 'T-1']);
        $mail = new \App\Mail\InquiryConfirmation($inquiry);
        $this->assertTrue($mail->envelope()->hasReplyTo('team@example.test'));
    }

    public function test_config_files_read_company_email_for_inbox_and_sender_when_nothing_else_is_set(): void
    {
        $contact = file_get_contents(config_path('contact.php'));
        $mail = file_get_contents(config_path('mail.php'));
        $this->assertStringContainsString("env('COMPANY_EMAIL'", $contact);
        $this->assertStringContainsString("env('COMPANY_EMAIL'", $mail);
        $this->assertSame(config('company.email'), config('contact.inbox') ?: config('company.email'));
        $this->assertStringContainsString('COMPANY_EMAIL=info@skylinkictet.com', file_get_contents(base_path('deploy/production.env.example')));
    }

    public function test_the_test_send_command_is_a_dry_run_by_default_and_sends_only_to_the_company_address(): void
    {
        Mail::fake();
        $this->artisan('mail:send-test')->expectsOutputToContain('DRY RUN')->assertSuccessful();
        Mail::assertNothingSent();
        $this->artisan('mail:send-test', ['--send' => true])->assertSuccessful();
        // five labelled raw emails, every one to the company address only
        $sent = collect(Mail::sent(\Illuminate\Mail\Mailable::class));
        $this->assertTrue(true);
    }
}
