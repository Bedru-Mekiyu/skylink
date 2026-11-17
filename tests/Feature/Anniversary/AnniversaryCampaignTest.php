<?php

namespace Tests\Feature\Anniversary;

use App\Support\Anniversary;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AnniversaryCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Baseline standard configuration for testing
        Config::set('anniversary.start', '2026-11-23 00:00');
        Config::set('anniversary.end', '2026-12-07 00:00');
        Config::set('anniversary.timezone', 'Africa/Addis_Ababa');
        Config::set('anniversary.force', false);
        Config::set('app.debug', true);
    }

    public static function publicPages(): array
    {
        return [
            'home' => ['/'],
            'about' => ['/about'],
            'services' => ['/services'],
            'products' => ['/products'],
            'portfolio' => ['/portfolio'],
            'news' => ['/news'],
            'contact' => ['/contact'],
        ];
    }

    public function test_campaign_is_inactive_before_start_date(): void
    {
        $before = Carbon::parse('2026-11-22 23:59:59', 'Africa/Addis_Ababa');
        $this->assertFalse(Anniversary::isActive($before));

        $early = Carbon::parse('2026-10-15 12:00:00', 'Africa/Addis_Ababa');
        $this->assertFalse(Anniversary::isActive($early));
    }

    public function test_campaign_is_active_from_first_minute_of_start_date(): void
    {
        $start = Carbon::parse('2026-11-23 00:00:00', 'Africa/Addis_Ababa');
        $this->assertTrue(Anniversary::isActive($start));

        $firstMinute = Carbon::parse('2026-11-23 00:01:00', 'Africa/Addis_Ababa');
        $this->assertTrue(Anniversary::isActive($firstMinute));
    }

    public function test_campaign_is_active_up_to_last_minute_before_end_date(): void
    {
        $mid = Carbon::parse('2026-11-30 15:30:00', 'Africa/Addis_Ababa');
        $this->assertTrue(Anniversary::isActive($mid));

        $lastMinute = Carbon::parse('2026-12-06 23:59:59', 'Africa/Addis_Ababa');
        $this->assertTrue(Anniversary::isActive($lastMinute));
    }

    public function test_campaign_is_inactive_from_end_instant(): void
    {
        $end = Carbon::parse('2026-12-07 00:00:00', 'Africa/Addis_Ababa');
        $this->assertFalse(Anniversary::isActive($end));

        $after = Carbon::parse('2026-12-10 10:00:00', 'Africa/Addis_Ababa');
        $this->assertFalse(Anniversary::isActive($after));
    }

    public function test_preview_flag_force_is_honored_in_local_debug(): void
    {
        $outside = Carbon::parse('2026-11-01 12:00:00', 'Africa/Addis_Ababa');
        $this->assertFalse(Anniversary::isActive($outside));

        Config::set('anniversary.force', true);
        Config::set('app.debug', true);

        $this->assertTrue(Anniversary::isForced());
        $this->assertTrue(Anniversary::isActive($outside));
    }

    public function test_preview_flag_force_is_strictly_ignored_when_debug_is_false(): void
    {
        $outside = Carbon::parse('2026-11-01 12:00:00', 'Africa/Addis_Ababa');
        Config::set('anniversary.force', true);
        Config::set('app.debug', false);

        $this->assertFalse(Anniversary::isForced());
        $this->assertFalse(Anniversary::isActive($outside));
    }

    public function test_preview_flag_force_is_strictly_ignored_in_production(): void
    {
        $outside = Carbon::parse('2026-11-01 12:00:00', 'Africa/Addis_Ababa');
        Config::set('anniversary.force', true);
        Config::set('app.debug', true);

        $this->app['env'] = 'production';

        $this->assertFalse(Anniversary::isForced());
        $this->assertFalse(Anniversary::isActive($outside));
    }

    public function test_campaign_is_inactive_when_dates_are_missing(): void
    {
        Config::set('anniversary.start', null);
        Config::set('anniversary.end', null);

        $this->assertFalse(Anniversary::isActive());
    }

    public function test_amharic_and_english_texts_resolve_correctly(): void
    {
        $this->assertSame('Celebrating Five Years of Sky Link ICT Solutions', Anniversary::headline('en'));
        $this->assertSame('የስካይ ሊንክ አይሲቲ ሶሉሽንስ 5ኛ ዓመት የምሥረታ በዓል', Anniversary::headline('am'));

        $this->assertSame('Join us as we commemorate five years of dedication, progress, and meaningful contributions to the technology industry. We look forward to celebrating this milestone with our team, clients, partners, and community.', Anniversary::tagline('en'));
        $this->assertSame('በቴክኖሎጂው ዘርፍ ላለፉት አምስት ዓመታት ያሳየነውን ቁርጠኝነት፣ ዕድገት እና አስተዋጽኦ ስናከብር አብረውን ይሁኑ። ይህንን ትልቅ ምዕራፍ ከቡድናችን፣ ደንበኞቻችን፣ አጋሮቻችን እና ማህበረሰባችን ጋር በጋራ ለማክበር በጉጉት እንጠብቃለን።', Anniversary::tagline('am'));

        $this->assertSame('Thank you for five years of dedication, partnership, and shared progress.', Anniversary::thanks('en'));
        $this->assertSame('ላለፉት አምስት ዓመታት አብረውን ለተጓዙ ደንበኞቻችን፣ አጋሮቻችን እና መላው ቡድናችን የከበረ ምስጋና እናቀርባለን።', Anniversary::thanks('am'));
    }

    public function test_milestone_and_counter_filtering_skips_invalid_entries(): void
    {
        Config::set('anniversary.milestones', [
            ['year' => 2021, 'title' => ['en' => 'Founded', 'am' => 'መሥራች']],
            ['year' => null, 'title' => ['en' => 'Invalid no year']],
            ['year' => 2022, 'title' => ['en' => '']],
            ['year' => 2023, 'title' => ['en' => 'Expansion'], 'text' => ['en' => 'New office']],
        ]);

        $en = Anniversary::milestones('en');
        $this->assertCount(2, $en);
        $this->assertSame(2021, $en[0]['year']);
        $this->assertSame('Founded', $en[0]['title']);
        $this->assertSame(2023, $en[1]['year']);
        $this->assertSame('New office', $en[1]['text']);

        Config::set('anniversary.counters', [
            ['label' => ['en' => 'Enterprise Clients'], 'value' => 50, 'suffix' => '+'],
            ['label' => ['en' => 'Missing value'], 'value' => null],
            ['label' => ['en' => ''], 'value' => 10],
        ]);

        $counters = Anniversary::counters('en');
        $this->assertCount(1, $counters);
        $this->assertSame('Enterprise Clients', $counters[0]['label']);
        $this->assertSame(50, $counters[0]['value']);
        $this->assertSame('+', $counters[0]['suffix']);
    }

    /**
     * @dataProvider publicPages
     */
    public function test_navbar_has_no_anniversary_promotional_button_when_active(string $uri): void
    {
        Config::set('anniversary.force', true);
        Config::set('app.debug', true);

        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringNotContainsString('id="annivBadgeBtn"', $html, "The navbar on {$uri} should not contain any anniversary promotional button.");
    }

    /**
     * @dataProvider publicPages
     */
    public function test_nothing_shows_on_any_page_when_campaign_is_inactive(string $uri): void
    {
        // Explicitly deactivate
        Config::set('anniversary.force', false);
        Config::set('anniversary.start', '2026-11-23 00:00');
        Config::set('anniversary.end', '2026-12-07 00:00');

        // Travel before the start date
        Carbon::setTestNow(Carbon::parse('2026-11-20 10:00:00', 'Africa/Addis_Ababa'));

        $this->assertFalse(Anniversary::isActive());

        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringNotContainsString('id="annivBadgeBtn"', $html);
        $this->assertStringNotContainsString('id="annivCelebrationModal"', $html);
        $this->assertStringNotContainsString('id="annivIntroOverlay"', $html);
        $this->assertStringNotContainsString('id="anniversary-story"', $html);
        $this->assertStringNotContainsString('id="annivHeroCard"', $html);
        $this->assertStringNotContainsString('id="annivNewsCard"', $html);
        $this->assertStringNotContainsString('id="annivAboutCard"', $html);
        $this->assertStringNotContainsString('id="annivProductCard"', $html);
        $this->assertStringNotContainsString('anniv-circ-stage', $html);

        Carbon::setTestNow();
    }

    public function test_cinematic_intro_has_accessible_skip_control(): void
    {
        Config::set('anniversary.force', true);
        Config::set('app.debug', true);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="annivIntroOverlay"', $html);
        $this->assertStringContainsString('id="annivIntroSkip"', $html);
        $this->assertStringContainsString('aria-label="Skip introduction"', $html);
    }

    public function test_homepage_section_renders_when_active_and_has_content(): void
    {
        Config::set('anniversary.force', true);
        Config::set('app.debug', true);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="anniversary-story"', $html);
        $this->assertStringContainsString('Celebrating Five Years of Sky Link ICT Solutions', $html);
        $this->assertStringContainsString('Join us as we commemorate five years of dedication, progress, and meaningful contributions', $html);
        $this->assertStringContainsString('Start a project with us', $html);
    }

    public function test_amharic_and_english_both_render(): void
    {
        Config::set('anniversary.force', true);
        Config::set('app.debug', true);

        // English default
        $htmlEn = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('Celebrating Five Years of Sky Link ICT Solutions', $htmlEn);
        $this->assertStringContainsString('Join us as we commemorate five years of dedication, progress, and meaningful contributions', $htmlEn);

        // Amharic locale
        app()->setLocale('am');
        $htmlAm = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('የስካይ ሊንክ አይሲቲ ሶሉሽንስ 5ኛ ዓመት የምሥረታ በዓል', $htmlAm);
        $this->assertStringContainsString('በቴክኖሎጂው ዘርፍ ላለፉት አምስት ዓመታት ያሳየነውን ቁርጠኝነት፣ ዕድገት እና አስተዋጽኦ ስናከብር አብረውን ይሁኑ።', $htmlAm);
    }

    public function test_section_and_modal_remain_clean_when_milestones_and_counters_are_empty(): void
    {
        Config::set('anniversary.force', true);
        Config::set('app.debug', true);
        Config::set('anniversary.milestones', []);
        Config::set('anniversary.counters', []);

        $html = $this->get('/')->assertOk()->getContent();

        // Section is present
        $this->assertStringContainsString('id="anniversary-story"', $html);

        // But timeline and counters DOM elements are omitted
        $this->assertStringNotContainsString('class="anniv-timeline-wrap"', $html);
        $this->assertStringNotContainsString('class="anniv-counters-row"', $html);
        $this->assertStringNotContainsString('class="anniv-modal__timeline"', $html);
    }

    public function test_share_description_reflects_tagline_when_active(): void
    {
        Config::set('anniversary.force', true);
        Config::set('app.debug', true);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Celebrating 5 Years of Sky Link ICT Solutions. Join us as we commemorate five years of dedication, progress, and meaningful contributions to the technology industry. We look forward to celebrating this milestone with our team, clients, partners, and community.', $html);
    }

    public function test_homepage_hero_feature_renders_when_active(): void
    {
        Config::set('anniversary.force', true);
        Config::set('app.debug', true);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="annivHeroCard"', $html);
        $this->assertStringContainsString('5 YEARS', $html);
        $this->assertStringContainsString('anniv-circ-stage', $html);
    }

    public function test_news_page_feature_card_renders_when_active(): void
    {
        Config::set('anniversary.force', true);
        Config::set('app.debug', true);

        $html = $this->get('/news')->assertOk()->getContent();

        $this->assertStringContainsString('id="annivNewsCard"', $html);
        $this->assertStringContainsString('id="annivNewsCenterpiece"', $html);
    }

    public function test_about_page_journey_feature_renders_when_active(): void
    {
        Config::set('anniversary.force', true);
        Config::set('app.debug', true);

        $html = $this->get('/about')->assertOk()->getContent();

        $this->assertStringContainsString('id="annivAboutCard"', $html);
        $this->assertStringContainsString('id="annivAboutCenterpiece"', $html);
        $this->assertStringContainsString('anniv-circ-wrap--hero', $html);
        $this->assertStringContainsString('Celebrating Five Years of Sky Link ICT Solutions', $html);
    }

    public function test_products_page_feature_renders_when_active(): void
    {
        Config::set('anniversary.force', true);
        Config::set('app.debug', true);

        $html = $this->get('/products')->assertOk()->getContent();

        $this->assertStringContainsString('id="annivProductCard"', $html);
        $this->assertStringContainsString('id="annivProductCenterpiece"', $html);
    }
}
