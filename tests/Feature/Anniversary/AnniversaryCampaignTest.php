<?php

namespace Tests\Feature\Anniversary;

use App\Support\Anniversary;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Once an admin saved Automatic, the 5-year campaign switches itself on and off by its dates (config/anniversary.php),
 * leaves no trace outside its window and can only be previewed outside production.
 */
class AnniversaryCampaignTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Africa/Addis_Ababa';

    protected function setUp(): void
    {
        parent::setUp();
        config(['anniversary.force' => false]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function at(string $localTime): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse($localTime, self::TZ));
    }

    /** An admin has saved Automatic with the campaign dates (nothing saved = Off, see AnniversaryAdminTest). */
    private function automatic(): void
    {
        DB::table('anniversary_settings')->insert([
            'mode' => 'auto', 'starts_at' => '2026-11-22 21:00:00', 'ends_at' => '2026-12-06 21:00:00',
            'admin_preview' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        Anniversary::forgetCache();
    }
    public static function publicPages(): array
    {
        return [
            'home' => ['/'], 'about' => ['/about'], 'services' => ['/services'],
            'products' => ['/products'], 'portfolio' => ['/portfolio'],
            'news' => ['/news'], 'contact' => ['/contact'],
        ];
    }

    public function test_window_starts_inclusive_and_ends_exclusive_in_addis_ababa_time(): void
    {
        $this->automatic();
        $this->at('2026-11-22 23:59:59');
        $this->assertFalse(Anniversary::isActive());

        $this->at('2026-11-23 00:00:00');
        $this->assertTrue(Anniversary::isActive());

        $this->at('2026-12-06 23:59:59');
        $this->assertTrue(Anniversary::isActive());

        $this->at('2026-12-07 00:00:00');
        $this->assertFalse(Anniversary::isActive());
    }

    public function test_window_uses_addis_ababa_not_utc(): void
    {
        $this->automatic();
        // 2026-11-22 21:00 UTC is already 2026-11-23 00:00 in Addis Ababa (UTC+3).
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-22 21:00:00', 'UTC'));
        $this->assertTrue(Anniversary::isActive());
    }

    public function test_preview_override_works_outside_production(): void
    {
        $this->at('2026-01-01 12:00:00');
        config(['anniversary.force' => true]);

        $this->assertTrue(Anniversary::isActive());
    }

    public function test_preview_override_can_never_activate_in_production(): void
    {
        $this->at('2026-01-01 12:00:00');
        config(['anniversary.force' => true, 'anniversary.preview_environments' => ['local', 'staging', 'testing', 'production']]);
        $this->app['env'] = 'production';

        $this->assertFalse(Anniversary::isActive());
    }

    /** @dataProvider publicPages */
    public function test_inactive_pages_carry_no_anniversary_markup_styles_or_scripts(string $uri): void
    {
        $this->at('2026-11-22 23:59:59');

        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringNotContainsString('anniv5', $html, "$uri still renders anniversary output while inactive.");
    }

    /** @dataProvider publicPages */
    public function test_active_pages_show_the_ceremony_seal_and_the_intro_once(string $uri): void
    {
        $this->automatic();
        $this->at('2026-11-23 00:00:00');

        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringContainsString('data-anniv5-seal', $html, "$uri is missing the ceremony seal.");
        $this->assertSame(1, substr_count($html, 'id="anniv5-intro"'), "$uri should render the intro overlay exactly once.");
        $this->assertSame(1, preg_match_all('/<h1[\s>]/i', $html), "$uri should still have one <h1>.");
        $this->assertStringNotContainsString('2026-11-23', $html, 'Campaign dates must not leak into the page.');
    }

    public function test_the_intro_has_an_icon_only_close_button_and_no_skip_word_in_english_and_amharic(): void
    {
        $this->automatic();
        $this->at('2026-11-23 00:00:00');
        foreach (['en' => 'Close', 'am' => 'ዝጋ'] as $locale => $label) {
            app()->setLocale($locale);
            $html = $this->get('/')->assertOk()->getContent();
            $intro = substr($html, strpos($html, 'id="anniv5-intro"'), 1200);
            $this->assertStringContainsString('class="anniv5-intro__skip" aria-label="'.__('anniversary.close', [], $locale).'"', $intro);
            $this->assertStringContainsString('<svg', $intro);
            $this->assertDoesNotMatchRegularExpression('/skip/i', preg_replace('/anniv5-intro__skip/', '', strip_tags(preg_replace('#<(script|style).*?</>#is', '', substr($html, strpos($html, 'id="anniv5-intro"'), 9000)))), 'no visible "skip" word');
            $this->assertSame($label, __('anniversary.close', [], $locale));
        }
        $this->assertStringNotContainsString('Skip intro', file_get_contents(lang_path('en/anniversary.php')));
        $this->assertStringContainsString('width: 44px; height: 44px', file_get_contents(resource_path('views/partials/anniversary/head.blade.php')), 'tap target');
    }

    public function test_homepage_carries_the_two_line_message_and_other_pages_a_short_line(): void
    {
        $this->automatic();
        $this->at('2026-11-23 00:00:00');

        $this->get('/')->assertOk()
            ->assertSee(__('anniversary.kicker'))
            ->assertSee(__('anniversary.headline'))
            ->assertDontSee(__('anniversary.message'));

        $this->get('/services')->assertOk()
            ->assertSee(__('anniversary.context.services'))
            ->assertDontSee(__('anniversary.message'));
    }

    public function test_auth_pages_never_show_the_campaign(): void
    {
        $this->automatic();
        $this->at('2026-11-23 00:00:00');

        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringNotContainsString('anniv5', $html);
    }

    public function test_amharic_locale_uses_the_amharic_copy(): void
    {
        $this->automatic();
        $this->at('2026-11-23 00:00:00');
        app()->setLocale('am');

        $this->get('/')->assertOk()->assertSee(__('anniversary.headline', [], 'am'));
    }
}
