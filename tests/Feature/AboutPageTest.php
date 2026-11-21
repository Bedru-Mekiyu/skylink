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
        $this->assertSame(1, substr_count($rows[1], 'team-card--lead'), 'the CEO card is the large one');
        $this->assertSame(0, substr_count($rows[2], 'team-card--lead'));
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

    public function test_about_intro_has_no_phone_item_keeps_all_words_and_reads_years_from_config(): void
    {
        $html = $this->get(route('about'))->assertOk()->getContent();
        $intro = substr($html, strpos($html, '<section class="about-intro"'));
        $intro = substr($intro, 0, strpos($intro, '</section>'));
        $this->assertStringNotContainsString('Call to ask any question', $html);
        $this->assertDoesNotMatchRegularExpression('/\+?251[\s-]?9\d|fa-phone|tel:/', $intro);
        foreach (['About Us', 'The Best IT Solution With Many Years of Experience', 'At Sky Link Technologies, we turn visionary concepts into cutting-edge digital solutions.', 'Award Winning', 'Dedicated Support', 'Professional Staff', 'Fair Prices', 'MORE INFORMATION', 'YEARS', 'EXPERIENCE', 'INDUSTRY.'] as $words) {
            $this->assertStringContainsString($words, $intro, $words);
        }
        $this->assertStringContainsString('data-ai-count="5"', $intro);
        config(['company.years_experience' => 7]);
        $this->assertStringContainsString('data-ai-count="7"', $this->get(route('about'))->getContent());
        $this->assertStringContainsString('id="journey"', $html, 'the MORE INFORMATION link has a target');
        $this->assertStringContainsString('href="#journey"', $intro);
        // the intro is the plain white section; reduced motion / Save-Data switch the motion off
        $this->assertStringContainsString('prefers-reduced-motion', $intro);
        $this->assertStringContainsString('saveData', $intro);
    }

    public function test_company_numbers_stay_in_navbar_footer_and_contact_page(): void
    {
        foreach (['/', '/about', '/contact'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('+251-974-967171', $html, $url);
            $this->assertStringContainsString('+251-936-177777', $html, $url);
        }
    }

    public function test_ceo_card_is_larger_than_the_other_cards_at_every_breakpoint(): void
    {
        $html = $this->get(route('about'))->getContent();
        $this->assertSame(1, preg_match_all('/class="[^"]*team-card--lead/', $html), 'exactly one lead card');
        // desktop/tablet/phone: the lead photo is up to 410 px (or 86% of the width), the others 240 px or less
        $this->assertMatchesRegularExpression('/\.team-card--lead \.team-card__photo \{ width: min\(86%, 410px\)/', $html);
        $this->assertMatchesRegularExpression('/\.team-card__photo \{[^}]*width: min\(100%, 240px\)/', $html);
        $this->assertGreaterThanOrEqual(1.6, 410 / 240);
        $this->assertLessThanOrEqual(1.8, 410 / 240);
        $this->assertStringContainsString('width="820"', $html);
    }

    public function test_homepage_years_counter_uses_the_same_config_figure_as_the_about_intro(): void
    {
        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('data-target="5+"', $home);
        $this->assertStringNotContainsString('data-target="4+"', $home);
        config(['company.years_experience' => 6]);
        $this->assertStringContainsString('data-target="6+"', $this->get('/')->getContent());
        $this->assertStringContainsString('data-ai-count="6"', $this->get(route('about'))->getContent());
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
