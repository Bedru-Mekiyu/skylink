<?php

namespace Tests\Feature\Anniversary;

use App\Support\Anniversary;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoLiveAnniversaryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_the_campaign_runs_from_now_to_2026_12_21_and_the_command_enables_it_like_the_admin_page(): void
    {
        $file = file_get_contents(config_path('anniversary.php'));
        $this->assertStringContainsString("env('ANNIVERSARY_END', '2026-12-21 00:00:00')", $file);
        $this->assertStringContainsString("env('ANNIVERSARY_START', '2026-11-21 00:00:00')", $file);
        config(['anniversary.start' => '2026-11-21 00:00:00', 'anniversary.end' => '2026-12-21 00:00:00']); // whatever the local .env says
        $this->artisan('anniversary:enable')->assertSuccessful();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-12-20 23:00:00', 'Africa/Addis_Ababa'));
        Anniversary::forgetCache();
        $this->assertTrue(Anniversary::isActive());
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-12-21 00:00:01', 'Africa/Addis_Ababa'));
        Anniversary::forgetCache();
        $this->assertFalse(Anniversary::isActive(), 'it ends by itself');
    }

    public function test_the_footer_year_is_the_current_year_in_addis_ababa_never_hardcoded(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2027-01-01 00:30:00', 'Africa/Addis_Ababa'));
        $this->assertStringContainsString('Copyright © 2027 |', $this->get('/')->assertOk()->getContent());
        $this->assertStringNotContainsString('Copyright © 2025', file_get_contents(resource_path('views/layouts/master.blade.php')));
    }
}
