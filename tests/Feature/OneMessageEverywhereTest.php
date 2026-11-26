<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\CatalogFixtures;
use Tests\CheckoutHelpers;
use Tests\TestCase;

/** Every page shows each message exactly once, and every "are you sure?" uses the site's own window. */
class OneMessageEverywhereTest extends TestCase
{
    use CatalogFixtures, CheckoutHelpers, RefreshDatabase;

    private const MESSAGES = ['success' => 'Msg-ok-71', 'error' => 'Msg-bad-72', 'warning' => 'Msg-careful-73', 'info' => 'Msg-note-74'];

    private function problems(array $paths): array
    {
        $problems = [];
        foreach ($paths as $path) {
            $response = $this->withSession(self::MESSAGES)->get($path);
            if ($response->getStatusCode() !== 200 || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
                continue;
            }
            $html = $response->getContent();
            if (! str_contains($html, '</html>')) {
                continue;
            }
            foreach (self::MESSAGES as $kind => $text) {
                if (($n = substr_count($html, $text)) !== 1) {
                    $problems[] = "$path shows the $kind message $n times";
                }
            }
        }

        return $problems;
    }

    public function test_every_admin_page_shows_each_message_once(): void
    {
        $this->enableChapa();
        $this->fakeChapa();
        $service = $this->service();
        $this->checkout($service->id, $this->option($service)->id);
        $this->actingAs($this->staff('super_admin'));

        $paths = collect(Route::getRoutes())
            ->filter(fn ($r) => str_starts_with((string) $r->getName(), 'admin.') && in_array('GET', $r->methods(), true) && ! str_contains($r->uri(), '{'))
            ->map(fn ($r) => '/'.$r->uri())->values()->all();
        $paths[] = '/admin/orders/'.Order::value('id');
        $paths[] = "/admin/services/{$service->id}/edit";

        $this->assertGreaterThan(30, count($paths));
        $this->assertSame([], $this->problems($paths));
    }

    public function test_every_public_page_shows_each_message_once(): void
    {
        $service = $this->service(null, ['slug' => 'once']);
        $this->option($service);

        $this->assertSame([], $this->problems(['/', '/about', '/services', '/services/detail/once', '/services/category/'.$service->category->slug,
            '/products', '/cart', '/contact', '/news', '/portfolio']));
    }

    public function test_no_button_uses_the_browsers_own_grey_pop_up(): void
    {
        $found = [];
        $files = collect(\Illuminate\Support\Facades\File::allFiles(resource_path('views')))->map->getPathname()->merge(glob(public_path('assets/js/*.js')));
        $this->assertGreaterThan(80, $files->count());
        foreach ($files as $file) {
            foreach (file($file) as $i => $line) {
                $native = preg_match('/(?<![\w.])(?:confirm|alert)\(|window\.(?:confirm|alert)\(/', $line);
                // Allowed: the no-JavaScript fallback beside the site's own window.
                if ($native && ! str_contains($line, 'window.SkyConfirm') && ! str_contains($line, 'else if (window.confirm')
                    && ! str_contains(implode('', array_slice(file($file), max(0, $i - 3), 4)), 'data-sky-confirm') && ! str_ends_with($file, 'sky-confirm.js')) {
                    $found[] = basename(dirname($file)).'/'.basename($file).':'.($i + 1);
                }
            }
        }

        $this->assertSame([], $found);
    }
}
