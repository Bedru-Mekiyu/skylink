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
