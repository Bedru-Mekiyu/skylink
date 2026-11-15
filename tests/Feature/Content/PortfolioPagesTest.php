<?php

namespace Tests\Feature\Content;

use App\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioPagesTest extends TestCase
{
    use RefreshDatabase;

    private function project(string $title, ?string $url = 'https://example.com'): Portfolio
    {
        return Portfolio::create([
            'title' => $title,
            'description' => "About {$title}.",
            'image' => 'portfolio/'.\Illuminate\Support\Str::slug($title).'.jpg',
            'project_url' => $url,
        ]);
    }

    public function test_portfolio_page_shows_project_cards_with_image_and_safe_external_link(): void
    {
        $this->project('Afar Website');
        $this->project('Kirb Gebeya', null);

        $response = $this->get('/portfolio')->assertOk();

        $response->assertSee('Afar Website')
            ->assertSee('Kirb Gebeya')
            ->assertSee('storage/portfolio/afar-website.jpg', false)
            ->assertSee('href="https://example.com" target="_blank" rel="noopener noreferrer"', false);
        // The newest project (Kirb Gebeya) is in the banner and not repeated as a card.
        $this->assertSame('Kirb Gebeya', $response->viewData('heroProject')->title);
        $this->assertSame(1, substr_count($response->getContent(), 'class="project-card"'));
        $this->assertSame(1, substr_count($response->getContent(), 'class="project-card__link"'));
        // A project without a URL gets no "Visit project" button (here: the banner).
        $response->assertDontSee('class="pf-hero__cta"', false);
    }

    public function test_portfolio_page_has_an_empty_state(): void
    {
        $this->get('/portfolio')->assertOk()->assertSee('No projects have been added yet.');
    }

    public function test_homepage_featured_portfolio_uses_the_project_cards(): void
    {
        $this->project('Bilalul Habeshi Virtual Museum');

        $response = $this->get('/')->assertOk();

        $response->assertSee('Bilalul Habeshi Virtual Museum');
        $this->assertSame(1, substr_count($response->getContent(), 'class="project-card"'));
    }
}
