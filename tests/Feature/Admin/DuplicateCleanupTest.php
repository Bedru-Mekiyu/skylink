<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsSlugRedirect;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Phase 4: news:clean-duplicates, the "Hidden duplicates" filter and Restore. */
class DuplicateCleanupTest extends TestCase
{
    use RefreshDatabase;

    private NewsCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->category = NewsCategory::create(['name' => 'News']);
    }

    private function article(string $title, string $slug, string $date, string $language = 'en'): News
    {
        $n = new News(['title' => $title, 'news_category_id' => $this->category->id, 'content' => 'Body', 'language' => $language]);
        $n->forceFill(['slug' => $slug, 'status' => 'published', 'published_at' => $date, 'created_at' => $date])->save();

        return $n;
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    public function test_dry_run_lists_pairs_and_changes_nothing(): void
    {
        $older = $this->article('AI IN ETHIOPIA', 'ai-old', '2025-07-12 06:59:26');
        $newer = $this->article('Ai in Ethiopia!', 'ai-new', '2025-07-26 05:54:20');
        $this->article('Something else entirely', 'other', '2025-07-20');

        $this->artisan('news:clean-duplicates')
            ->expectsOutputToContain('KEEP    #'.$older->id)
            ->expectsOutputToContain('HIDE    #'.$newer->id)
            ->expectsOutputToContain('/news/ai-new -> 301 -> /news/ai-old')
            ->expectsOutputToContain('DRY RUN: nothing was changed')
            ->assertSuccessful();

        $this->assertFalse($newer->fresh()->trashed());
        $this->assertSame(0, NewsSlugRedirect::count());
        $this->assertSame([], Storage::disk('local')->allFiles('backups'));
    }

    public function test_apply_backs_up_hides_the_newer_one_and_redirects_its_address(): void
    {
        $older = $this->article('AI IN ETHIOPIA', 'ai-old', '2025-07-12 06:59:26');
        $newer = $this->article('Ai in Ethiopia', 'ai-new', '2025-07-26 05:54:20');
        $newer->forceFill(['is_featured' => true])->save();

        $this->artisan('news:clean-duplicates', ['--apply' => true, '--force' => true])->assertSuccessful();

        $hidden = News::withTrashed()->find($newer->id);
        $this->assertTrue($hidden->trashed(), 'hidden, not deleted');
        $this->assertSame($older->id, (int) $hidden->duplicate_of_id);
        $this->assertFalse($hidden->is_featured);
        $this->assertFalse($older->fresh()->trashed());

        $backups = Storage::disk('local')->allFiles('backups');
        $this->assertCount(1, $backups);
        $snapshot = json_decode(Storage::disk('local')->get($backups[0]), true);
        $this->assertCount(2, $snapshot['news']);

        $this->get('/news/ai-new')->assertStatus(301)->assertRedirect('/news/ai-old');
        $this->get('/news')->assertDontSee('/news/ai-new"', false);

        // Running again: nothing left to do, and the admin report was refreshed.
        $this->artisan('news:clean-duplicates')->expectsOutputToContain('DRY RUN: nothing to change.')->assertSuccessful();
        $this->assertSame(0, json_decode(Storage::disk('local')->get('reports/news-duplicates.txt.json'), true)['pending']);
    }

    public function test_apply_asks_for_confirmation_and_can_be_cancelled(): void
    {
        $this->article('Same title', 'a', '2025-01-01');
        $b = $this->article('Same title', 'b', '2025-01-02');

        $this->artisan('news:clean-duplicates', ['--apply' => true])
            ->expectsConfirmation('Apply 1 change(s)? A backup is made first.', 'no')
            ->assertFailed();
        $this->assertFalse($b->fresh()->trashed());
    }

    public function test_keep_newer_option_and_config_exceptions(): void
    {
        $a = $this->article('Weekly update', 'weekly-1', '2025-01-01');
        $b = $this->article('Weekly update', 'weekly-2', '2025-01-08');
        $c = $this->article('Big launch', 'launch-1', '2025-02-01');
        $d = $this->article('Big launch', 'launch-2', '2025-02-02');

        // These two are different articles with the same title.
        config(['news.duplicates.not_duplicates' => [['weekly-1', 'weekly-2']]]);
        $this->artisan('news:clean-duplicates', ['--keep' => 'newer', '--apply' => true, '--force' => true])
            ->expectsOutputToContain('IGNORED (config: not_duplicates)')
            ->assertSuccessful();

        $this->assertFalse($a->fresh()->trashed());
        $this->assertFalse($b->fresh()->trashed());
        $this->assertTrue(News::withTrashed()->find($c->id)->trashed(), '--keep=newer hides the older one');
        $this->assertFalse($d->fresh()->trashed());

        // always_keep wins over the rule, and a previously hidden article is restored.
        config(['news.duplicates.always_keep' => ['launch-1']]);
        $this->artisan('news:clean-duplicates', ['--keep' => 'newer', '--apply' => true, '--force' => true])
            ->assertSuccessful();
        $this->assertFalse(News::withTrashed()->find($c->id)->trashed());
        $this->assertTrue(News::withTrashed()->find($d->id)->trashed());
        $this->get('/news/launch-1')->assertOk();
        $this->get('/news/launch-2')->assertRedirect('/news/launch-1');
    }

    public function test_same_title_in_another_language_or_deleted_by_an_admin_is_not_a_duplicate(): void
    {
        $this->article('Sky Link', 'en-1', '2025-01-01', 'en');
        $this->article('Sky Link', 'am-1', '2025-01-02', 'am');
        $deleted = $this->article('Old news', 'old-1', '2025-01-03');
        $this->article('Old news', 'old-2', '2025-01-04');
        $deleted->delete(); // deleted by an admin, not a duplicate

        $this->artisan('news:clean-duplicates')->expectsOutputToContain('No duplicate groups found.')->assertSuccessful();
    }

    public function test_report_is_written_for_the_admin_and_cannot_apply(): void
    {
        $this->article('Same title', 'a', '2025-01-01');
        $this->article('Same title', 'b', '2025-01-02');

        $this->artisan('news:clean-duplicates', ['--report' => true])->assertSuccessful();
        $this->assertStringContainsString('HIDE', Storage::disk('local')->get('reports/news-duplicates.txt'));
        $this->assertSame(1, json_decode(Storage::disk('local')->get('reports/news-duplicates.txt.json'), true)['pending']);
        $this->assertSame(0, News::onlyTrashed()->count());

        $this->artisan('news:clean-duplicates', ['--report' => true, '--apply' => true])->assertExitCode(2);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.news.index'))->assertSee('1 article(s) could be hidden as duplicates');
        $this->actingAs($admin)->get(route('admin.news.duplicates-report'))->assertOk()->assertSee('HIDE');
    }

    public function test_the_scheduler_only_runs_the_dry_run_report(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($e) => str_contains((string) $e->command, 'news:clean-duplicates'));

        $this->assertCount(1, $events);
        $this->assertStringContainsString('--report', $events->first()->command);
        $this->assertStringNotContainsString('--apply', $events->first()->command);
    }

    public function test_hidden_duplicates_filter_and_restore(): void
    {
        $kept = $this->article('Minber TV', 'minber-1', '2025-10-09 14:05:58');
        $dup = $this->article('Minber TV', 'minber-2', '2025-10-09 14:05:59');
        $deletedByAdmin = $this->article('Removed by admin', 'removed', '2025-01-01');
        $deletedByAdmin->delete();
        $this->artisan('news:clean-duplicates', ['--apply' => true, '--force' => true]);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.news.index', ['status' => 'hidden-duplicates']))->assertOk()
            ->assertSee('Hidden duplicates (1)')
            ->assertSee('/news/minber-2')
            ->assertSee('Redirects to')
            ->assertSee('Restore')
            ->assertDontSee('Removed by admin');
        // The normal list does not show hidden articles.
        $this->actingAs($admin)->get(route('admin.news.index'))->assertDontSee('/news/minber-2');

        // Only admins can restore.
        $this->actingAs(User::factory()->create())->post(route('admin.news.restore', $dup->id))->assertForbidden();

        $this->actingAs($admin)->post(route('admin.news.restore', $dup->id))
            ->assertRedirect(route('admin.news.index', ['status' => 'hidden-duplicates']));
        $this->assertFalse($dup->fresh()->trashed());
        $this->assertNull($dup->fresh()->duplicate_of_id);
        $this->assertSame(0, NewsSlugRedirect::where('old_slug', 'minber-2')->count());
        auth()->logout();
        $this->get('/news/minber-2')->assertOk();

        // An article deleted by an admin cannot be "restored" through this action.
        $this->actingAs($admin)->post(route('admin.news.restore', $deletedByAdmin->id))->assertNotFound();
    }
}
