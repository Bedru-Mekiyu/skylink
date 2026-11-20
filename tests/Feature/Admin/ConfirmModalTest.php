<?php

namespace Tests\Feature\Admin;

use App\Models\Inquiry;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Portfolio;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Delete actions use the in-page confirmation dialog, stay DELETE + CSRF + admin only, and no alert()/prompt() is left. */
class ConfirmModalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    public function test_the_admin_layout_loads_the_dialog_script_and_style(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('assets/js/sky-confirm.js', $html);
        $this->assertStringContainsString('assets/css/sky-confirm.css', $html);
        $this->assertFileExists(public_path('assets/js/sky-confirm.js'));
    }

    public function test_the_dialog_script_is_accessible_and_restores_focus(): void
    {
        $js = file_get_contents(public_path('assets/js/sky-confirm.js'));

        foreach (['role="dialog"', 'aria-modal="true"', 'aria-labelledby="skyModalTitle"', "'Escape'", "key === 'Tab'", 'back.focus', 'data-sky-confirm', 'form.submit()'] as $needle) {
            $this->assertStringContainsString($needle, $js, "sky-confirm.js should contain $needle");
        }
    }

    public function test_every_delete_control_in_the_admin_carries_the_dialog_text_and_keeps_the_nojs_fallback(): void
    {
        $admin = $this->admin();
        $cat = NewsCategory::create(['name' => 'News']);
        $inq = (new Inquiry())->forceFill(['type' => 'contact', 'name' => 'Vis', 'email' => 'v@example.test', 'message' => 'Hi']);
        $inq->save();
        Video::create(['title' => 'V', 'slug' => 'v-1', 'description' => 'd', 'video_url' => 'https://youtu.be/8R4J33Dp2x8', 'news_category_id' => $cat->id]);
        $news = new News(['title' => 'N', 'news_category_id' => $cat->id, 'content' => 'x']);
        $news->forceFill(['slug' => 'n-1', 'status' => News::STATUS_PUBLISHED, 'published_at' => now()])->save();

        $pages = [
            'admin.inbox.index' => 'data-sky-confirm="Delete this message?',
            'admin.videos.index' => 'data-sky-confirm="Delete this video?',
            'admin.news.index' => 'data-sky-confirm="Delete this article?',
        ];
        foreach ($pages as $route => $needle) {
            $html = $this->actingAs($admin)->get(route($route))->assertOk()->getContent();
            $this->assertStringContainsString($needle, $html, "$route should use the dialog");
            $this->assertStringContainsString('confirm(', $html, "$route keeps the inline confirm as the no-JavaScript fallback");
            $this->assertStringContainsString('name="_method" value="DELETE"', $html);
            $this->assertStringContainsString('name="_token"', $html);
        }
    }

    public function test_the_other_content_lists_use_the_dialog_too(): void
    {
        $admin = $this->admin();
        $cat = ServiceCategory::create(['name' => 'Cat', 'slug' => 'cat', 'card_title' => 'Cat', 'description' => 'd', 'sort_order' => 1, 'is_published' => true]);
        Product::create(['category' => 'business-tools', 'slug' => 'p-1', 'name' => 'P', 'image' => 'https://example.test/i.png', 'description' => 'd', 'price' => '1', 'features' => 'a', 'additional_info' => 'b']);
        Portfolio::create(['title' => 'Pf', 'description' => 'd', 'image' => 'portfolio_images/x.jpg', 'project_url' => 'https://example.test']);

        foreach ([
            'admin.products.index' => 'Delete this product?',
            'admin.portfolio.index' => 'Delete this project?',
            'admin.service-categories.index' => 'Delete the category',
        ] as $route => $text) {
            $this->actingAs($admin)->get(route($route))->assertOk()->assertSee('data-sky-confirm="'.$text, false);
        }
    }

    public function test_deleting_still_needs_admin_delete_method_and_csrf_never_a_get_link(): void
    {
        $admin = $this->admin();
        $cat = NewsCategory::create(['name' => 'News']);
        $v = Video::create(['title' => 'V', 'slug' => 'v-1', 'description' => 'd', 'video_url' => 'https://youtu.be/8R4J33Dp2x8', 'news_category_id' => $cat->id]);

        $this->get(route('admin.videos.edit', $v))->assertRedirect(route('login'));
        $this->actingAs($admin)->get('/admin/videos/'.$v->id.'/delete')->assertNotFound();
        $this->assertNotNull(Video::find($v->id));

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->delete(route('admin.videos.destroy', $v))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->delete(route('admin.videos.destroy', $v))->assertForbidden();
        $this->assertNotNull(Video::find($v->id));

        $this->actingAs($admin)->delete(route('admin.videos.destroy', $v))->assertRedirect();
        $this->assertNull(Video::find($v->id));
    }

    public function test_the_anniversary_and_inbox_scripts_use_the_dialog_and_no_alert_remains_in_the_views(): void
    {
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS));
        $alerts = [];
        $bareConfirms = [];
        foreach ($views as $file) {
            if (! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }
            foreach (file($file->getPathname()) as $i => $line) {
                if (preg_match('/(?<![\w.])alert\s*\(/', $line)) {
                    $alerts[] = $file->getFilename().':'.($i + 1);
                }
                // a confirm()/prompt() is only allowed as the no-JavaScript fallback: inline attribute next to data-sky-confirm, or the else-branch of SkyConfirm
                if (preg_match('/window\.(confirm|prompt)\s*\(/', $line) && ! str_contains($line, 'else')) {
                    $bareConfirms[] = $file->getFilename().':'.($i + 1);
                }
            }
        }
        $this->assertSame([], $alerts, 'alert() found in: '.implode(', ', $alerts));
        $this->assertSame([], $bareConfirms, 'window.confirm/prompt without the dialog in: '.implode(', ', $bareConfirms));

        $this->assertStringContainsString('SkyConfirm.ask', file_get_contents(resource_path('views/admin/anniversary/edit.blade.php')));
        $this->assertStringContainsString('SkyConfirm.ask', file_get_contents(resource_path('views/admin/inbox/index.blade.php')));
        $this->assertStringContainsString('SkyConfirm.ask', file_get_contents(resource_path('views/admin/news/form.blade.php')));
    }
}
