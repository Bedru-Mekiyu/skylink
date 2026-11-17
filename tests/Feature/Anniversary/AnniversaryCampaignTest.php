<?php

namespace Tests\Feature\Anniversary;

use App\Support\Anniversary;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AnniversaryCampaignTest extends TestCase
{
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

    public function test_campaign_is_inactive_before_start_date(): void
    {
        // 1 second before start
        $before = Carbon::parse('2026-11-22 23:59:59', 'Africa/Addis_Ababa');
        $this->assertFalse(Anniversary::isActive($before));

        // Well before start
        $early = Carbon::parse('2026-10-15 12:00:00', 'Africa/Addis_Ababa');
        $this->assertFalse(Anniversary::isActive($early));
    }

    public function test_campaign_is_active_from_first_minute_of_start_date(): void
    {
        // Exact start instant
        $start = Carbon::parse('2026-11-23 00:00:00', 'Africa/Addis_Ababa');
        $this->assertTrue(Anniversary::isActive($start));

        // One minute in
        $firstMinute = Carbon::parse('2026-11-23 00:01:00', 'Africa/Addis_Ababa');
        $this->assertTrue(Anniversary::isActive($firstMinute));
    }

    public function test_campaign_is_active_up_to_last_minute_before_end_date(): void
    {
        // Middle of campaign
        $mid = Carbon::parse('2026-11-30 15:30:00', 'Africa/Addis_Ababa');
        $this->assertTrue(Anniversary::isActive($mid));

        // 6 December 23:59:59 (the last second shown)
        $lastMinute = Carbon::parse('2026-12-06 23:59:59', 'Africa/Addis_Ababa');
        $this->assertTrue(Anniversary::isActive($lastMinute));
    }

    public function test_campaign_is_inactive_from_end_instant(): void
    {
        // 2026-12-07 00:00:00 is exclusive
        $end = Carbon::parse('2026-12-07 00:00:00', 'Africa/Addis_Ababa');
        $this->assertFalse(Anniversary::isActive($end));

        // Well after end
        $after = Carbon::parse('2026-12-10 10:00:00', 'Africa/Addis_Ababa');
        $this->assertFalse(Anniversary::isActive($after));
    }

    public function test_preview_flag_force_is_honored_in_local_debug(): void
    {
        // Outside campaign window
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

        // Temporarily override environment detection
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
        $this->assertSame('5 Years of Sky Link', Anniversary::headline('en'));
        $this->assertSame('አምስት ዓመታት በስካይሊንክ', Anniversary::headline('am'));

        $this->assertSame('Five years of innovation, trust and growth.', Anniversary::tagline('en'));
        $this->assertSame('አምስት ዓመታት የፈጠራ፣ የአመኔታና የዕድገት ጉዞ።', Anniversary::tagline('am'));

        $this->assertSame('Thank you for five years of trust.', Anniversary::thanks('en'));
        $this->assertSame('ለአምስት ዓመታት ላሳያችሁን አመኔታ እናመሰግናለን።', Anniversary::thanks('am'));
    }

    public function test_milestone_and_counter_filtering_skips_invalid_entries(): void
    {
        Config::set('anniversary.milestones', [
            ['year' => 2021, 'title' => ['en' => 'Founded', 'am' => 'መሥራች']],
            ['year' => null, 'title' => ['en' => 'Invalid no year']], // skip
            ['year' => 2022, 'title' => ['en' => '']], // skip empty title
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
            ['label' => ['en' => 'Missing value'], 'value' => null], // skip
            ['label' => ['en' => ''], 'value' => 10], // skip empty label
        ]);

        $counters = Anniversary::counters('en');
        $this->assertCount(1, $counters);
        $this->assertSame('Enterprise Clients', $counters[0]['label']);
        $this->assertSame(50, $counters[0]['value']);
        $this->assertSame('+', $counters[0]['suffix']);
    }
}
