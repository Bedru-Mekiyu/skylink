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

    public function test_ceo_sits_alone_on_top_and_the_other_three_are_below(): void
    {
        $html = $this->get(route('about'))->assertOk()->getContent();
        $team = substr($html, strpos($html, 'Our Management Team'));
        $team = substr($team, 0, strpos($team, 'Teams Grid'));
        $rows = explode('class="row g-4', $team);
        $this->assertStringContainsString('Mohammedkerem Ahmed', $rows[1]);
        $this->assertSame(1, substr_count($rows[1], 'col-lg-3'));
        $this->assertSame(3, substr_count($rows[2], 'col-lg-3'));
        $this->assertStringNotContainsString('Mohammedkerem Ahmed', $rows[2]);
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
