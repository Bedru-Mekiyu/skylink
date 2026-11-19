<?php

namespace Tests\Feature\Content;

use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The News page filter has exactly four options: All Content, News, Innovation (the "Innovations" row), Events.
 * The live database also holds service-type categories in the same table; they must never become filter buttons.
 */
class NewsCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private array $cats = [];

    protected function setUp(): void
    {
        parent::setUp();
        // the real table: 9 service-type categories first, then the three news ones (as in the live copy)
        foreach (['Software Service', 'Web & Mobile App', 'Smart City and Infrastructure', 'Safety and Security', 'Enterprise Network Solution',
                  'Consulting and Training', 'ICT Support', 'GPS Tracking System', 'Datacenter Facility and Power', 'News', 'Events', 'Innovations'] as $name) {
            $this->cats[$name] = NewsCategory::create(['name' => $name]);
        }
    }

    private function article(string $title, string $category, string $date): News
    {
        $item = new News(['title' => $title, 'news_category_id' => $this->cats[$category]->id, 'content' => 'Body text.']);
        $item->forceFill(['slug' => Str::slug($title).'-'.Carbon::parse($date)->timestamp, 'status' => News::STATUS_PUBLISHED,
            'published_at' => $date, 'created_at' => $date, 'updated_at' => $date])->save();

        return $item;
    }

    /** The labels of the filter chips (the links inside <nav aria-label="News categories">), in order. */
    private function chips(string $html): array
    {
        preg_match('#<nav aria-label="News categories">(.*?)</nav>#s', $html, $nav);
        preg_match_all('#<a class="news-chip"[^>]*>\s*(.*?)\s*</a>#s', $nav[1] ?? '', $m);

        return array_map(fn ($t) => trim(strip_tags($t)), $m[1]);
    }

    public function test_the_filter_shows_exactly_four_options_in_order(): void
    {
        $this->article('A news item', 'News', '2025-09-01');

        $this->assertSame(['All Content', 'News', 'Innovation', 'Events'], $this->chips($this->get('/news')->assertOk()->getContent()));
    }

    public function test_service_type_categories_are_never_filter_buttons_or_filters(): void
    {
        $html = $this->get('/news')->getContent();
        foreach (['Software Service', 'ICT Support', 'GPS Tracking System', 'Datacenter Facility and Power'] as $name) {
            $this->assertStringNotContainsString('>'.$name.'<', $this->chips($html) ? implode('<', array_map(fn ($c) => '>'.$c, $this->chips($html))).'<' : '');
            $this->assertNotContains($name, $this->chips($html));
        }
        // a hand-typed ?category= of such a row is ignored: the page shows All Content
        $response = $this->get('/news?category=ict-support')->assertOk();
        $this->assertNull($response->viewData('activeCategory'));
    }

    public function test_each_filter_returns_its_own_articles_and_all_content_returns_everything(): void
    {
        // The page-wide top banner shows the single newest item and nowhere else; keep that one out of the comparison.
        $this->article('Banner story', 'News', '2025-12-01');
        $this->article('News story', 'News', '2025-09-03');
        $this->article('Innovation story', 'Innovations', '2025-09-02');
        $this->article('Events story', 'Events', '2025-09-01');
        $this->article('Odd category story', 'ICT Support', '2025-08-01'); // other category: only under All Content

        $titles = function (string $url) {
            $r = $this->get($url)->assertOk();

            return collect($r->viewData('news')->items())->push($r->viewData('featured'))->filter()->pluck('title')->all();
        };

        $this->assertEqualsCanonicalizing(['News story'], array_values(array_intersect($titles('/news?category=news'), ['News story', 'Innovation story', 'Events story', 'Odd category story'])));
        $this->assertEqualsCanonicalizing(['Innovation story'], array_values(array_intersect($titles('/news?category=innovation'), ['News story', 'Innovation story', 'Events story', 'Odd category story'])));
        $this->assertEqualsCanonicalizing(['Events story'], array_values(array_intersect($titles('/news?category=events'), ['News story', 'Innovation story', 'Events story', 'Odd category story'])));
        $all = $titles('/news');
        foreach (['News story', 'Innovation story', 'Events story', 'Odd category story'] as $t) {
            $this->assertContains($t, $all, "$t must appear under All Content");
        }
    }

    public function test_search_and_pagination_still_work_with_a_filter(): void
    {
        for ($i = 1; $i <= 14; $i++) {
            $this->article("Events item $i", 'Events', '2025-09-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }
        $this->article('Unrelated news', 'News', '2025-10-01');

        $page2 = $this->get('/news?category=events&page=2')->assertOk();
        $this->assertGreaterThan(0, count($page2->viewData('news')->items()));
        $this->assertSame(2, $page2->viewData('news')->currentPage());

        $found = $this->get('/news?category=events&q=Events+item+3')->assertOk();
        $titles = collect($found->viewData('news')->items())->push($found->viewData('featured'))->filter()->pluck('title')->all();
        $this->assertContains('Events item 3', $titles);
        $this->assertNotContains('Unrelated news', $titles);
        $this->assertSame(['All Content', 'News', 'Innovation', 'Events'], $this->chips($found->getContent()));
    }
}
