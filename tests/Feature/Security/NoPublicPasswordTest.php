<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Public pages are open to everyone: no Basic Auth anywhere in what we ship or run; only /admin is protected by the app login. */
class NoPublicPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const AUTH = '/^\s*(AuthType|AuthUserFile|AuthName|Require\s+valid-user)\b/mi';

    public function test_nothing_we_ship_or_run_switches_basic_auth_on(): void
    {
        $files = [base_path('public/.htaccess'), base_path('deploy/server-deploy.sh'), base_path('deploy/server-common.sh'), base_path('deploy/server-rollback.sh'), base_path('deploy/build-release.ps1')];
        foreach (glob(base_path('deploy/domain-switch/*')) ?: [] as $f) {
            $files[] = $f;
        }
        foreach ($files as $f) {
            if (is_file($f)) {
                // a deploy script may MENTION the directives in a check; only a line that is itself a directive counts
                $this->assertDoesNotMatchRegularExpression(self::AUTH, (string) file_get_contents($f), basename($f).' must not enable Basic Auth');
            }
        }
        $lock = (string) file_get_contents(base_path('deploy/staging/02-lockdown.sh'));
        $this->assertStringContainsString('DISABLED', $lock, 'the old lock-down script (which wrote Basic Auth) is disabled');
        $this->assertStringContainsString('AuthType', (string) file_get_contents(base_path('deploy/server-deploy.sh')), 'the deploy script checks for leftover Basic Auth');
    }

    public function test_public_pages_open_without_any_credentials_and_admin_needs_the_app_login(): void
    {
        foreach (['/', '/about', '/services', '/products', '/portfolio', '/news', '/contact', '/login'] as $url) {
            $r = $this->get($url);
            $r->assertOk();
            $this->assertNull($r->headers->get('WWW-Authenticate'), $url.' must not ask for a password');
        }
        $this->get('/register')->assertNotFound();
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get('/admin/dashboard')->assertStatus(403);
    }

    public function test_admin_login_is_rate_limited_after_repeated_wrong_passwords(): void
    {
        $u = User::factory()->create(['email' => 'lock-test@example.test']);
        $limited = false;
        for ($i = 0; $i < 8; $i++) {
            $r = $this->from('/login')->post('/login', ['email' => $u->email, 'password' => 'wrong-'.$i]);
            if ($r->status() === 429 || str_contains((string) session('errors')?->first('email'), 'Too many')) {
                $limited = true;
                break;
            }
        }
        $this->assertTrue($limited, 'repeated wrong passwords must be locked out');
    }
}
