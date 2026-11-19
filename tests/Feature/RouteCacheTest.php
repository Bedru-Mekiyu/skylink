<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/** The deploy runs `php artisan route:cache`; a duplicated route name or a closure route would make it fail. */
class RouteCacheTest extends TestCase
{
    public function test_routes_can_be_cached_for_production(): void
    {
        $exit = Artisan::call('route:cache');
        $output = Artisan::output();
        Artisan::call('route:clear');

        $this->assertSame(0, $exit, "route:cache failed:\n".$output);
    }

    public function test_no_route_name_is_used_twice(): void
    {
        $names = [];
        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            if ($name = $route->getName()) {
                $names[$name][] = $route->uri();
            }
        }
        $dupes = array_filter($names, fn ($uris) => count($uris) > 1);

        $this->assertSame([], $dupes, 'These route names are defined twice: '.json_encode($dupes));
    }
}
