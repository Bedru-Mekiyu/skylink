<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsSlugRedirect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 2: admin news form (status, slugs, sanitising, images, double-submit
 * and duplicate protection) and what visitors can see.
 */
class AdminNewsFormTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private NewsCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['is_admin' => true])->save();
        $this->category = NewsCategory::create(['name' => 'News']);
    }

    /** A fresh one-time token, as the create page would render it. */
    private function token(): string
    {
        $this->actingAs($this->admin)->get(route('admin.news.create'))->assertOk();

        return collect(session('form_tokens.admin-news'))->last();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Sky Link opens a new training centre',
            'language' => 'en',
            'url_title' => '',
            'news_category_id' => $this->category->id,
            'content' => '<p>Body text.</p>',
            'status' => 'published',
            'is_featured' => '0',
            'form_token' => $this->token(),
        ], $overrides);
    }

    private function store(array $overrides = [])
    {
        return $this->actingAs($this->admin)->post(route('admin.news.store'), $this->payload($overrides));
    }

    // ----- access ----------------------------------------------------------

    public function test_only_admins_can_use_the_news_admin(): void
    {
        $this->get(route('admin.news.index'))->assertRedirect(route('login'));
        $this->get(route('admin.news.similar', ['title' => 'x']))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.news.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.news.store'), [])->assertForbidden();
    }

    // ----- slugs -----------------------------------------------------------

    public function test_slug_comes_from_the_english_url_title_or_is_news_id(): void
    {
        $this->store(['url_title' => 'Training Centre Opening'])->assertSessionHasNoErrors();
        $this->store(['title' => 'ስካይ ሊንክ አዲስ ዜና', 'language' => 'am'])->assertSessionHasNoErrors();

        $withUrl = News::where('title', 'Sky Link opens a new training centre')->first();
        $amharic = News::where('title', 'ስካይ ሊንክ አዲስ ዜና')->first();

        $this->assertSame('training-centre-opening', $withUrl->slug);
        $this->assertSame('news-'.$amharic->id, $amharic->slug);
        $this->get('/news/news-'.$amharic->id)->assertOk()->assertSee('lang="am"', false);
    }

    public function test_title_edits_never_change_the_slug_but_url_title_edits_redirect_the_old_one(): void
    {
        $this->store(['url_title' => 'First Address'])->assertSessionHasNoErrors();
        $news = News::first();
        $this->assertSame('first-address', $news->slug);

        // Title change: same slug, no redirect.
        $this->actingAs($this->admin)->put(route('admin.news.update', $news), $this->payload([
            'title' => 'A completely reworded headline here',
            'url_title' => 'First Address',
            'confirm_similar' => '1',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('first-address', $news->fresh()->slug);
        $this->assertSame(0, NewsSlugRedirect::count());

        // English URL title change: new slug, the old one answers with a 301.
        $this->actingAs($this->admin)->put(route('admin.news.update', $news), $this->payload([
            'title' => 'A completely reworded headline here',
            'url_title' => 'Second Address',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('second-address', $news->fresh()->slug);

        auth()->logout();
        $this->get('/news/first-address')->assertStatus(301)->assertRedirect('/news/second-address');
        $this->get('/news/second-address')->assertOk();
    }

    public function test_url_title_must_be_english_and_not_an_automatic_address(): void
    {
        $this->store(['url_title' => 'ስካይ ሊንክ'])->assertSessionHasErrors('url_title');
        $this->store(['url_title' => 'news 12'])->assertSessionHasErrors('url_title');
        $this->assertSame(0, News::count());
    }

    public function test_slugs_stay_unique(): void
    {
        $this->store(['url_title' => 'Same Words'])->assertSessionHasNoErrors();
        $this->store(['title' => 'Something totally unrelated to before', 'url_title' => 'Same Words'])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(['same-words', 'same-words-2'], News::pluck('slug')->all());
    }

    // ----- body ------------------------------------------------------------

    public function test_body_is_sanitised_on_save_and_basic_formatting_is_kept(): void
    {
        $this->store(['content' => '<h2>Heading</h2><p onclick="steal()">Hello <strong>bold</strong> <em>it</em> '
            .'<a href="javascript:alert(1)">bad</a> <a href="https://skylinkict.com">good</a></p>'
            .'<script>alert(1)</script><ul><li>one</li></ul><p style="color:red">styled</p><img src=x onerror=alert(1)>'])
            ->assertSessionHasNoErrors();

        $html = News::first()->content;
        $this->assertStringContainsString('<h2>Heading</h2>', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<em>it</em>', $html);
        $this->assertStringContainsString('<ul><li>one</li></ul>', $html);
        $this->assertStringContainsString('<a href="https://skylinkict.com" rel="noopener noreferrer">good</a>', $html);
        foreach (['<script', 'onclick', 'javascript:', 'style=', '<img', 'onerror'] as $bad) {
            $this->assertStringNotContainsString($bad, $html);
        }
        $this->assertSame('html', News::first()->content_format);
        $this->assertNotEmpty(News::first()->excerpt);
    }

    public function test_a_body_that_is_empty_after_cleaning_is_rejected(): void
    {
        $this->store(['content' => '<script>alert(1)</script><p> </p>'])->assertSessionHasErrors('content');
    }

    public function test_old_plain_text_bodies_still_render_as_paragraphs(): void
    {
        $news = new News(['title' => 'Old', 'news_category_id' => $this->category->id, 'content' => "First <b>para</b>\n\nSecond"]);
        $news->forceFill(['slug' => 'old-1'])->save();

        $this->get('/news/old-1')->assertOk()
            ->assertSee('<p>First &lt;b&gt;para&lt;/b&gt;</p>', false)
            ->assertSee('<p>Second</p>', false);
    }

    // ----- visibility ------------------------------------------------------

    public function test_drafts_are_hidden_from_visitors_and_search_but_admins_can_preview(): void
    {
        $this->store(['title' => 'Secret draft article', 'status' => 'draft', 'url_title' => 'Secret Draft'])->assertSessionHasNoErrors();
        $this->assertNull(News::first()->published_at);

        $this->actingAs($this->admin)->get('/news/secret-draft')->assertOk()
            ->assertSee('Preview: this article is a draft')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        auth()->logout();
        $this->get('/news/secret-draft')->assertNotFound();
        $this->get('/news')->assertOk()->assertDontSee('Secret draft article');
        $this->get('/news?q=Secret')->assertOk()->assertDontSee('Secret draft article');
        $this->get('/')->assertOk()->assertDontSee('Secret draft article');
    }

    public function test_scheduled_articles_appear_only_after_their_time_even_without_cron(): void
    {
        // 2026-12-01 10:00 Addis Ababa = 07:00 UTC.
        $this->travelTo('2026-11-30 12:00:00');
        $this->store(['title' => 'Scheduled launch announcement', 'status' => 'scheduled', 'published_at' => '2026-12-01T10:00', 'url_title' => 'Launch'])
            ->assertSessionHasNoErrors();
        $news = News::first();
        $this->assertSame('2026-12-01 07:00:00', $news->published_at->format('Y-m-d H:i:s'));
        auth()->logout();

        $this->get('/news/launch')->assertNotFound();
        $this->get('/news?q=Scheduled')->assertDontSee('Scheduled launch announcement');

        $this->travelTo('2026-12-01 07:00:30');
        $this->get('/news/launch')->assertOk();
        $this->get('/news')->assertSee('Scheduled launch announcement');
        $this->assertSame('scheduled', $news->fresh()->status, 'visible without the scheduler having run');

        $this->artisan('news:publish-scheduled')->assertSuccessful();
        $this->assertSame('published', $news->fresh()->status);
    }

    public function test_a_scheduled_time_in_the_past_or_missing_is_rejected(): void
    {
        $this->store(['status' => 'scheduled', 'published_at' => '2020-01-01T10:00'])->assertSessionHasErrors('published_at');
        $this->store(['status' => 'scheduled', 'published_at' => ''])->assertSessionHasErrors('published_at');
        $this->assertSame(0, News::count());
    }

    public function test_feature_on_top_leads_the_news_page_and_is_a_single_slot(): void
    {
        $this->store(['title' => 'Older featured story about servers', 'is_featured' => '1'])->assertSessionHasNoErrors();
        $this->travel(1)->minutes();
        $this->store(['title' => 'Newest ordinary story about cables'])->assertSessionHasNoErrors();

        $response = $this->get('/news')->assertOk();
        $this->assertSame('Older featured story about servers', $response->viewData('featured')->title);

        $this->store(['title' => 'Another featured piece on networks', 'is_featured' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(['Another featured piece on networks'], News::where('is_featured', true)->pluck('title')->all());
    }

    public function test_featuring_a_draft_or_scheduled_article_keeps_the_current_featured_story(): void
    {
        // A video fills the /news banner (as on the real site), so the listing starts with the newest article.
        // The banner shows the newest item of either kind, so the video stays newest through the time travel below.
        \App\Models\Video::create(['title' => 'Banner video', 'slug' => 'banner-video', 'video_url' => 'https://youtu.be/G_ci2p1-3N4', 'news_category_id' => \App\Models\NewsCategory::first()->id])
            ->forceFill(['created_at' => now()->addDays(5)])->save();
        $this->store(['title' => 'Live featured story about servers', 'is_featured' => '1'])->assertSessionHasNoErrors();
        $this->store(['title' => 'Draft featured piece on cables', 'is_featured' => '1', 'status' => 'draft'])->assertSessionHasNoErrors();
        $this->store(['title' => 'Scheduled featured piece on networks', 'is_featured' => '1', 'status' => 'scheduled',
            'published_at' => now()->setTimezone('Africa/Addis_Ababa')->addDay()->format('Y-m-d\TH:i')])->assertSessionHasNoErrors();

        $this->assertSame('Live featured story about servers', $this->get('/news')->viewData('featured')->title);

        // When the scheduled one goes live, it is the newest featured article and leads.
        $this->travel(2)->days();
        $this->assertSame('Scheduled featured piece on networks', $this->get('/news')->viewData('featured')->title);
    }

    public function test_long_amharic_bodies_are_not_cut_by_the_sanitiser(): void
    {
        $body = '<p>'.str_repeat('ሀ', 30000).'</p><p>END-MARKER</p>'; // 90 KB in UTF-8, under 100 000 characters
        $this->store(['title' => 'ረጅም ጽሑፍ', 'language' => 'am', 'content' => $body])->assertSessionHasNoErrors();

        $this->assertStringEndsWith('<p>END-MARKER</p>', News::first()->content);
    }

    // ----- double submit and duplicates -------------------------------------

    public function test_the_same_form_cannot_be_saved_twice(): void
    {
        $payload = $this->payload();

        $this->actingAs($this->admin)->post(route('admin.news.store'), $payload)->assertRedirect(route('admin.news.index'));
        $second = $this->actingAs($this->admin)->post(route('admin.news.store'), $payload);

        $news = News::sole();
        $second->assertRedirect(route('admin.news.edit', $news->id))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'already saved'));
    }

    public function test_a_token_not_issued_to_this_session_is_refused(): void
    {
        $this->actingAs($this->admin)->post(route('admin.news.store'), $this->payload(['form_token' => str_repeat('a', 40)]))
            ->assertSessionHasErrors('form_token');
        $this->assertSame(0, News::count());
    }

    public function test_an_identical_title_within_ten_minutes_is_blocked_even_when_confirmed(): void
    {
        $this->store(['title' => 'Sky Link wins award!'])->assertSessionHasNoErrors();

        $this->store(['title' => '  sky link   WINS award ', 'confirm_similar' => '1'])
            ->assertSessionHasErrors(['title' => 'An article with this exact title was created 0 seconds ago. It was not saved again. If this is a different article, change the title.'])
            ->assertSessionHas('similar');
        $this->assertSame(1, News::count());

        // After 10 minutes it is only a warning, which the admin can confirm.
        $this->travel(11)->minutes();
        $this->store(['title' => 'Sky Link wins award'])->assertSessionHasErrors('title')->assertSessionHas('similar');
        $this->store(['title' => 'Sky Link wins award', 'confirm_similar' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(2, News::count());
    }

    public function test_similar_titles_show_a_warning_with_links_to_the_existing_article(): void
    {
        $this->store(['title' => 'Windows 10 support ends on October 14 2025'])->assertSessionHasNoErrors();
        $existing = News::first();
        $this->travel(20)->minutes();

        $this->store(['title' => 'Windows 10 support ends October 14, 2025!'])
            ->assertSessionHasErrors('title')
            ->assertSessionHas('similar', fn ($list) => $list[0]['edit_url'] === route('admin.news.edit', $existing->id)
                && $list[0]['public_url'] === route('news.show', $existing->slug));

        // The warning is shown on the form with the confirmation box.
        $this->actingAs($this->admin)->get(route('admin.news.create'))
            ->assertSee('Articles with a similar title')
            ->assertSee('This is a different article. Save it anyway.');

        // Live hint endpoint.
        $this->actingAs($this->admin)->getJson(route('admin.news.similar', ['title' => 'windows 10 support ends october 14 2025']))
            ->assertOk()->assertJsonPath('matches.0.id', $existing->id);
        $this->actingAs($this->admin)->getJson(route('admin.news.similar', ['title' => 'Completely different topic here']))
            ->assertOk()->assertJsonCount(0, 'matches');
    }

    // ----- images ----------------------------------------------------------

    public function test_uploaded_images_get_a_web_copy_and_a_thumbnail_and_the_original_is_kept_privately(): void
    {
        $this->store(['image' => UploadedFile::fake()->image('big.jpg', 3000, 2000)])->assertSessionHasNoErrors();
        $news = News::first();

        [$webW] = getimagesizefromstring(Storage::disk('public')->get($news->image));
        [$thumbW] = getimagesizefromstring(Storage::disk('public')->get($news->image_thumb));
        $this->assertSame(1600, $webW);
        $this->assertSame(640, $thumbW);
        Storage::disk('local')->assertExists($news->image_original);
        $this->assertStringStartsWith('news-originals/', $news->image_original);

        // Cards (homepage "Latest News") use the thumbnail; the featured story and
        // the article page use the web copy; the original is never linked.
        $this->get('/')->assertSee($news->image_thumb)->assertDontSee($news->image_original);
        $this->get('/news')->assertSee($news->image)->assertDontSee($news->image_original);
        $this->get('/news/'.$news->slug)->assertSee($news->image);
    }

    public function test_small_images_are_not_enlarged(): void
    {
        $this->store(['image' => UploadedFile::fake()->image('small.png', 500, 300)])->assertSessionHasNoErrors();
        [$w] = getimagesizefromstring(Storage::disk('public')->get(News::first()->image));
        $this->assertSame(500, $w);
    }

    // ----- admin list --------------------------------------------------------

    public function test_admin_list_shows_status_language_category_and_searches(): void
    {
        $this->store(['title' => 'Draft about fibre networks', 'status' => 'draft'])->assertSessionHasNoErrors();
        $this->store(['title' => 'ስካይ ሊንክ ዜና ስለ ስልጠና', 'language' => 'am'])->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->get(route('admin.news.index'))->assertOk()
            ->assertSeeInOrder(['Title', 'Status', 'Language', 'Category', 'Date'])
            ->assertSee('Draft')->assertSee('Published')->assertSee('Amharic')->assertSee('English')
            ->assertSee('Not published');

        $this->actingAs($this->admin)->get(route('admin.news.index', ['q' => 'fibre']))->assertOk()
            ->assertSee('Draft about fibre networks')->assertDontSee('ስካይ ሊንክ ዜና ስለ ስልጠና');
        $this->actingAs($this->admin)->get(route('admin.news.index', ['status' => 'draft']))->assertOk()
            ->assertSee('Draft about fibre networks')->assertDontSee('ስካይ ሊንክ ዜና ስለ ስልጠና');
    }

    public function test_the_form_disables_its_save_button_and_carries_a_token(): void
    {
        $this->actingAs($this->admin)->get(route('admin.news.create'))->assertOk()
            ->assertSee('data-save-button', false)
            ->assertSee('name="form_token"', false)
            ->assertSee('btn.disabled = true', false);
    }
}
