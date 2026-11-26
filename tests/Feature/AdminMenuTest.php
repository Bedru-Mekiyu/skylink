<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** What the sidebar shows and what the server allows must agree, role by role. */
class AdminMenuTest extends TestCase
{
    use RefreshDatabase;

    /** Allowed by the server but deliberately not in that role's sidebar (as on bedru). */
    private const ALLOWED_BUT_NOT_LISTED = ['sales' => ['/admin/orders']];

    private function menu(string $role): array
    {
        $this->flushSession();
        $html = $this->actingAs($this->staff($role))->get('/admin/dashboard')->assertOk()->getContent();
        preg_match_all('/<a[^>]*class="nav-link[^"]*"[^>]*href="http:\/\/localhost(\/admin\/[^"#?]*)"/', $html, $m);

        return array_values(array_unique($m[1]));
    }

    public function test_every_menu_link_opens_and_every_hidden_one_is_forbidden(): void
    {
        $all = $this->menu('super_admin');
        $this->assertGreaterThan(15, count($all));
        $problems = [];

        foreach (['super_admin', 'content_editor', 'sales', 'finance'] as $role) {
            $shown = $this->menu($role);
            foreach ($all as $path) {
                $status = $this->get($path)->getStatusCode();
                $allowed = in_array($path, $shown, true) || in_array($path, self::ALLOWED_BUT_NOT_LISTED[$role] ?? [], true);
                if ($status !== ($allowed ? 200 : 403)) {
                    $problems[] = "$role $path gave $status";
                }
            }
        }

        $this->assertSame([], $problems);
    }

    /** Both links are in live's sidebar; the server already limits the pages to Super Admin and Sales. */
    public function test_subscribers_and_contact_messages_are_listed_for_the_roles_that_may_open_them(): void
    {
        $links = ['/admin/subscribers', '/admin/contact-messages'];

        foreach (['super_admin' => true, 'sales' => true, 'content_editor' => false, 'finance' => false] as $role => $listed) {
            $shown = $this->menu($role);
            $this->assertSame($listed ? $links : [], array_values(array_intersect($shown, $links)), $role);
        }
    }
}
