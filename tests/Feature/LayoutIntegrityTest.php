<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Guards the shared public layout against the bugs found in the layout review:
 * CSS printed before the doctype (quirks mode on /about), the scripts stack
 * rendered twice, Bootstrap JS loaded several times (phone menu would not close)
 * and the homepage carousel arrows pushing the page sideways.
 */
class LayoutIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public static function publicPages(): array
    {
        return [
            'home' => ['/'], 'about' => ['/about'], 'services' => ['/services'],
            'products' => ['/products'], 'portfolio' => ['/portfolio'],
            'news' => ['/news'], 'contact' => ['/contact'],
        ];
    }

    /** @dataProvider publicPages */
    public function test_page_starts_with_the_doctype(string $uri): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringStartsWith('<!DOCTYPE html>', ltrim($html), "$uri prints output before the doctype (quirks mode).");
    }

    /** @dataProvider publicPages */
    public function test_bootstrap_js_is_loaded_exactly_once(string $uri): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'bootstrap.bundle.min.js'), "$uri loads Bootstrap JS more than once.");
    }

    /** @dataProvider publicPages */
    public function test_page_has_exactly_one_h1(string $uri): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/<h1[\s>]/i', $html), "$uri should have one <h1>.");
    }

    public function test_pushed_scripts_render_once_and_after_the_page_content(): void
    {
        $html = $this->get('/contact')->assertOk()->getContent();

        // The contact page pushes one script containing this text.
        $this->assertSame(1, substr_count($html, '// Run once, after the page exists.'));
        $this->assertGreaterThan(strpos($html, '</footer>'), strpos($html, '// Run once, after the page exists.'));
    }

    public function test_about_page_styles_are_in_the_head(): void
    {
        $html = $this->get('/about')->assertOk()->getContent();

        $head = substr($html, 0, strpos($html, '</head>'));
        $this->assertStringContainsString('===== Page banner =====', $head);
    }

    public function test_homepage_carousel_arrows_are_clamped_to_the_viewport(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('left: max(-80px, calc((100% - 100vw) / 2 + 16px));', $html);
        $this->assertStringContainsString('right: max(-80px, calc((100% - 100vw) / 2 + 16px));', $html);
        $this->assertDoesNotMatchRegularExpression('/\.carousel-(prev|next)\s*\{\s*(left|right):\s*-80px;/', $html);
    }

    public function test_mega_menu_does_not_use_100vw(): void
    {
        $master = File::get(resource_path('views/layouts/master.blade.php'));

        $this->assertDoesNotMatchRegularExpression('/width:\s*100vw/', $master);
    }

    public function test_every_view_closes_its_sections_and_pushes(): void
    {
        $problems = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $code = preg_replace('/\{\{--.*?--\}\}/s', '', $file->getContents());

            foreach (['section' => '@(?:endsection|stop|show|overwrite|append)\b', 'push' => '@endpush\b'] as $open => $close) {
                // Block form only: @section('name') / @push('name'); @section('name', 'value') has no end tag.
                $opens = preg_match_all('/(?<![\w@])@'.$open.'\(\s*([\'"])[^\'"]+\1\s*\)/', $code);
                $closes = preg_match_all('/(?<![\w@])'.$close.'/', $code);
                if ($opens !== $closes) {
                    $problems[] = $file->getRelativePathname().": @$open $opens vs closes $closes";
                }
            }
            // Blade inside a JS line comment still compiles (e.g. a stack printed inside a comment).
            if (preg_match('#(?<![:"\'\w])//[^\n]*@(?:stack|push|yield|include|section)\s*\(#', $code, $m)) {
                $problems[] = $file->getRelativePathname().': Blade directive in a // comment: '.trim($m[0]);
            }
        }

        $this->assertSame([], $problems);
    }
}
