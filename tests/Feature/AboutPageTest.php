<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** About page: team replacement, square team photos, shorter banner. */
class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_head_of_hr_is_listed_and_old_name_is_gone(): void
    {
        $html = $this->get(route('about'))->assertOk()->getContent();
        $this->assertStringContainsString('Hanan Nesru', $html);
        $this->assertStringContainsString('Head of Human Resource &amp; Administration', $html);
        foreach (['Jemil', 'Gragn', 'Networking Administration', 'jemil.png'] as $old) {
            $this->assertStringNotContainsString($old, $html);
        }
        $this->assertFileDoesNotExist(public_path('assets/images/about/jemil.png'));
        $this->assertStringContainsString('alt="Hanan Nesru, Head of Human Resource &amp; Administration"', $html);
        $this->assertStringContainsString('assets/images/about/hanan.jpg', $html);
        $this->assertFileExists(public_path('assets/images/about/hanan.jpg'));
    }

    public function test_ceo_sits_alone_on_top_and_four_cards_are_below(): void
    {
        $html = $this->get(route('about'))->assertOk()->getContent();
        $team = substr($html, strpos($html, 'Our Management Team'));
        $team = substr($team, 0, strpos($team, 'Teams Grid'));
        $rows = explode('class="row g-4', $team);
        $this->assertStringContainsString('Mohammedkerem Ahmed', $rows[1]);
        $this->assertSame(1, substr_count($rows[1], 'col-lg-3'));
        $this->assertSame(4, substr_count($rows[2], 'col-lg-3'), 'bottom row: three core members plus Head of Marketing');
        $this->assertStringContainsString('Head of Marketing', $rows[2]);
        $this->assertLessThan(strpos($rows[2], 'Head of Marketing'), strpos($rows[2], 'Hanan Nesru'));
        $this->assertStringNotContainsString('Mohammedkerem Ahmed', $rows[2]);
    }

    public function test_ceo_section_has_no_personal_number_but_company_numbers_stay(): void
    {
        $html = $this->get(route('about'))->assertOk()->getContent();
        $ceo = substr($html, strpos($html, 'CEO MESSAGE') - 1500, 4000);
        $this->assertDoesNotMatchRegularExpression('/\+?251[\s-]?\d|09\d{8}|fa-phone|tel:/', $ceo);
        $this->assertStringContainsString('974', $html, 'company number stays on the page (navbar / about box)');
        $this->get('/contact')->assertOk()->assertSee('974', false);
    }

    public function test_the_ceo_name_comes_from_one_place_and_is_spelled_the_same_everywhere(): void
    {
        $name = config('team.ceo.name');
        $this->assertSame('Mohammedkerem Ahmed', $name);
        $about = $this->get(route('about'))->getContent();
        $this->assertGreaterThanOrEqual(3, substr_count($about, $name));
        $this->assertStringContainsString('alt="'.$name.', CEO of Sky Link Technologies"', $this->get('/')->getContent());
    }

    public function test_head_of_marketing_placeholder_is_a_neutral_4_5_image_and_marked_todo(): void
    {
        $html = $this->get(route('about'))->getContent();
        $this->assertStringContainsString('Full Name', $html);
        $this->assertStringContainsString('assets/images/about/head-of-marketing.jpg', $html);
        [$w, $h] = getimagesize(public_path('assets/images/about/head-of-marketing.jpg'));
        $this->assertSame(4 / 5, $w / $h);
        $this->assertStringContainsString('TODO', file_get_contents(config_path('team.php')));
    }

    public function test_placeholder_cannot_reach_the_real_live_site(): void
    {
        $this->assertTrue(\App\Support\TeamPlaceholder::inUse(), 'placeholder is still in use');
        // test site (not production), or production with the test-site guard on: allowed
        $this->artisan('team:check-placeholder')->assertSuccessful();
        // real live site = production without MAIL_STAGING_GUARD: must fail
        $this->app['env'] = 'production';
        config(['mail.staging_guard.enabled' => false]);
        $this->artisan('team:check-placeholder')->assertFailed();
        config(['mail.staging_guard.enabled' => true]);
        $this->artisan('team:check-placeholder')->assertSuccessful();
        // real name and photo: production is fine
        config(['mail.staging_guard.enabled' => false, 'team.marketing.name' => 'Real Person', 'team.marketing.image' => 'assets/images/about/hanan.jpg']);
        $this->artisan('team:check-placeholder')->assertSuccessful();
    }

    public function test_team_photos_are_one_rounded_square_ratio_not_circles(): void
    {
        $html = $this->get(route('about'))->getContent();
        $this->assertStringContainsString('team-card__photo', $html);
        $this->assertMatchesRegularExpression('/\.team-card__photo\s*\{[^}]*aspect-ratio:\s*4 \/ 5[^}]*border-radius:\s*12px/', $html);
        $card = file_get_contents(resource_path('views/components/team-member-card.blade.php'));
        $this->assertStringNotContainsString('rounded-circle', $card);
    }

    public function test_banner_is_about_half_a_screen_not_full(): void
    {
        $html = $this->get(route('about'))->getContent();
        $this->assertStringContainsString('height: clamp(340px, 50svh, 560px)', $html);
        $this->assertStringNotContainsString('max-height: calc(100svh - 56px', $html);
    }
}
