<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Route-level access rules from the Phase 0 audit.
 */
class RouteAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_route_requires_login_and_the_admin_role(): void
    {
        $unprotected = [];

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'admin')) {
                continue;
            }
            $middleware = $route->gatherMiddleware();
            if (! in_array('auth', $middleware, true) || ! in_array('admin', $middleware, true)) {
                $unprotected[] = implode('|', $route->methods()).' '.$route->uri();
            }
        }

        $this->assertSame([], $unprotected, 'Admin routes without auth+admin middleware.');
    }

    public function test_the_unauthenticated_admin_test_route_is_gone(): void
    {
        $this->get('/admin/test')->assertNotFound();
    }

    public function test_dashboard_requires_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_dashboard_sends_admins_to_the_admin_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    }

    public function test_dashboard_sends_other_users_home(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect(route('home'));
    }

    public function test_no_get_route_sends_the_newsletter(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (in_array('GET', $route->methods(), true)) {
                $this->assertNotSame(
                    'App\Http\Controllers\NewsletterController@send',
                    $route->getActionName(),
                    "GET {$route->uri()} must not send the newsletter."
                );
            }
        }
    }
}
