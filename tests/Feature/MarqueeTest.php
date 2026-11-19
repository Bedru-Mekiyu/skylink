<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Homepage "Our Services": a continuous marquee with no controls, every service twice for a seamless loop. */
class MarqueeTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_marquee_lists_all_seven_services_twice_and_has_no_controls(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="services-track"', $html);
        $this->assertSame(14, substr_count($html, 'class="service-card'), 'seven services, each shown twice');
        $this->assertSame(7, preg_match_all('/service-card marquee-dup"\s+aria-hidden="true"/', $html), 'the second copy is hidden from assistive tech');
        $this->assertStringContainsString('Smart City Projects', $html);

        foreach (['carousel-prev', 'carousel-next', 'services-pause', 'services-dot', 'Previous service', 'Next service'] as $removed) {
            $this->assertStringNotContainsString($removed, $html, "$removed should be gone");
        }
    }

    public function test_reduced_motion_stops_the_marquee_and_hides_the_copy(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/prefers-reduced-motion: reduce\)\s*\{\s*\.services-track \{ animation: none; \}/', $html);
        $this->assertStringContainsString('.services-marquee .marquee-dup { display: none; }', $html);
    }

    public function test_services_marquee_highlights_the_card_in_the_middle_with_readable_colours(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('.service-card.is-center {', $html);
        // The site's sky-blue button gradient, each stop darkened until white text is 4.6:1 (AA needs 4.5:1).
        $this->assertStringContainsString('--hl-from: #3775ca;', $html);
        $this->assertStringContainsString('--hl-to: #0074d9;', $html);
        $this->assertStringContainsString('background: linear-gradient(135deg, var(--hl-from), var(--hl-to));', $html);
        $this->assertStringContainsString("classList.toggle('is-center'", $html);
        // Reduced motion: the script never starts, so nothing is highlighted or animated.
        $this->assertMatchesRegularExpression("/if \(!track \|\| \(window\.matchMedia && matchMedia\('\(prefers-reduced-motion: reduce\)'\)\.matches\)\) return;/", $html);
    }
}
