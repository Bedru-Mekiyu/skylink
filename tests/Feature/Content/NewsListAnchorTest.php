<?php

namespace Tests\Feature\Content;

use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /news: every link and form that changes the list ends in #news-list (so the
 * browser lands on the filter bar, not the top of the page) and keeps its
 * ?category=, ?q= and ?page= query string.
 */
class NewsListAnchorTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_search_clear_and_page_links_keep_their_query_and_add_the_anchor(): void
    {
        $category = NewsCategory::create(['name' => 'News']);

        // Enough matching articles for a second page (9 per page; the banner takes one).
        foreach (range(1, 12) as $i) {
            $n = new News(['title' => "Story $i", 'news_category_id' => $category->id, 'content' => 'Body']);
            $n->forceFill(['slug' => "story-$i", 'status' => 'published', 'published_at' => now()->subDays($i)])->save();
        }

        $slug = $category->slug;
        $response = $this->get('/news?category='.$slug.'&q=Story')->assertOk();

        $response->assertSee('id="news-list"', false);
        // Category chips keep the search text.
        $response->assertSee(route('content.index', ['q' => 'Story']).'#news-list');
        $response->assertSee(route('content.index', ['category' => $slug, 'q' => 'Story']).'#news-list');
        // The search form lands on the list and keeps the active category.
        $response->assertSee('action="'.route('content.index').'#news-list"', false);
        $response->assertSee('name="category" value="'.$slug.'"', false);
        // "Clear search" keeps the category.
        $response->assertSee(route('content.index', ['category' => $slug]).'#news-list');
        // Pagination keeps category and search.
        $response->assertSee(route('content.index', ['category' => $slug, 'q' => 'Story', 'page' => 2]).'#news-list');
    }
}
