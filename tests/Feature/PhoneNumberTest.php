<?php

namespace Tests\Feature;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    use RefreshDatabase;

    private function plain(string $s): string
    {
        return str_replace("\u{00A0}", ' ', $s);
    }

    public function test_mobile_numbers(): void
    {
        foreach (['0911234567', '+251911234567', '251911234567', '911234567', '00251911234567', '+251 (911) 23-45-67', '+251-911-234567', '+2510911234567'] as $in) {
            $this->assertSame('+251 911 234 567', $this->plain(PhoneNumber::international($in)), $in);
            $this->assertSame('0911 234 567', $this->plain(PhoneNumber::local($in)), $in);
            $this->assertSame('+251911234567', PhoneNumber::e164($in), $in);
            $this->assertSame('tel:+251911234567', PhoneNumber::tel($in));
            $this->assertSame('https://wa.me/251911234567', PhoneNumber::whatsapp($in));
        }
    }

    public function test_the_company_numbers_keep_their_digits(): void
    {
        $this->assertSame('+251 974 967 171', $this->plain(PhoneNumber::international('+251-974-967171')));
        $this->assertSame('+251 936 177 777', $this->plain(PhoneNumber::international('+251-936-177777')));
        $this->assertSame('+251974967171', PhoneNumber::e164('+251-974-967171'));
    }

    public function test_addis_ababa_and_other_landlines(): void
    {
        foreach (['0115551234', '+251115551234', '011 555 1234', '+251 (11) 555-1234'] as $in) {
            $this->assertSame('+251 11 555 1234', $this->plain(PhoneNumber::international($in)), $in);
            $this->assertSame('011 555 1234', $this->plain(PhoneNumber::local($in)), $in);
            $this->assertSame('+251115551234', PhoneNumber::e164($in), $in);
        }
        $this->assertSame('+251 22 111 2345', $this->plain(PhoneNumber::international('0221112345')));   // Adama-style 2-digit area code
        $this->assertSame('022 111 2345', $this->plain(PhoneNumber::local('+251221112345')));
    }

    public function test_display_uses_non_breaking_spaces_only(): void
    {
        $out = PhoneNumber::international('0911234567');
        $this->assertStringNotContainsString(' ', $out);
        $this->assertStringContainsString("\u{00A0}", $out);
    }

    public function test_invalid_numbers_are_returned_unchanged_and_logged(): void
    {
        Log::spy();
        foreach (['12345', 'abc', '+251 123 456 789 0', '0811234567', ''] as $in) {
            $this->assertSame($in, PhoneNumber::international($in), $in);
        }
        Log::shouldHaveReceived('warning')->atLeast()->once();
        $this->assertSame('', PhoneNumber::tel(null) === 'tel:' ? '' : 'x');
    }

    public function test_no_public_page_shows_an_unformatted_company_number(): void
    {
        foreach (['/', '/about', '/services', '/products', '/portfolio', '/news', '/contact'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertDoesNotMatchRegularExpression('/\+251-\d|\d{3}-\d{6}|\+251 \d{3} \d{6}|974967171|936177777/', preg_replace(['/(href="tel:|data-copy=")[^"]*"/', '#<script type="application/ld\+json">.*?</script>#s'], '', $html), $path);
            $this->assertStringNotContainsString('tel:+251-', $html, $path);
            $this->assertStringNotContainsString('tel: ', $html, $path);
        }
        $home = $this->get('/')->getContent();
        $this->assertStringContainsString('href="tel:+251974967171"', $home);
        $this->assertStringContainsString("+251\u{00A0}974\u{00A0}967\u{00A0}171", $home);
        $this->assertStringContainsString('href="tel:+251936177777"', $home);
    }
}
