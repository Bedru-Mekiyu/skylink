<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The typography system: central tokens, self-hosted fonts, none of the old faces. */
class TypographyTest extends TestCase
{
    use RefreshDatabase;

    private const OLD = ['Bodoni', 'Playfair', 'Belleza', 'EB Garamond', 'Manrope', 'fonts.googleapis.com', 'fonts.gstatic.com'];

    public function test_public_pages_use_the_new_tokens_and_load_no_old_fonts(): void
    {
        foreach (['home', 'about', 'contact', 'products.index', 'portfolio.index', 'news.index'] as $name) {
            if (! \Route::has($name)) {
                continue;
            }
            $html = $this->get(route($name))->assertOk()->getContent();
            foreach (['--font-body', '--font-heading', '--font-card-title', '--font-display', '--font-numeric', 'font-synthesis: none'] as $needle) {
                $this->assertStringContainsString($needle, $html, "$name lacks $needle");
            }
            foreach (self::OLD as $old) {
                $this->assertStringNotContainsString($old, $html, "$name still mentions $old");
            }
        }
    }

    public function test_admin_pages_use_the_same_tokens(): void
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();
        $html = $this->actingAs($u)->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('--font-heading', $html);
        foreach (self::OLD as $old) {
            $this->assertStringNotContainsString($old, $html);
        }
    }

    public function test_font_files_exist_and_are_woff2(): void
    {
        foreach (['inter-latin-var', 'inter-latin-italic-var', 'plus-jakarta-sans-latin-var', 'fraunces-latin-var', 'noto-sans-ethiopic-var', 'noto-serif-ethiopic-var'] as $f) {
            $path = public_path("assets/fonts/$f.woff2");
            $this->assertFileExists($path);
            $this->assertSame('wOF2', file_get_contents($path, false, null, 0, 4), "$f is not WOFF2");
        }
    }
}
