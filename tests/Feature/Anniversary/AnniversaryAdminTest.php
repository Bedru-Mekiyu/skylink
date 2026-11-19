<?php

namespace Tests\Feature\Anniversary;

use App\Models\AnniversarySetting;
use App\Models\User;
use App\Support\Anniversary;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/** Admin > Anniversary Ceremony: mode, dates, admin preview, and the config fallback. */
class AnniversaryAdminTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Africa/Addis_Ababa';

    protected function setUp(): void
    {
        parent::setUp();
        config(['anniversary.force' => false]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function at(string $local): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse($local, self::TZ));
        Anniversary::forgetCache();
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    /** Save settings the way the admin form does (Addis Ababa local times). */
    private function save(array $o = [])
    {
        return $this->actingAs($this->admin())->put(route('admin.anniversary.update'), $o + [
            'mode' => 'auto', 'starts_at' => '2026-12-01T00:00', 'ends_at' => '2026-12-10T00:00', 'admin_preview' => '0',
        ]);
    }

    private function homeShowsCeremony(): bool
    {
        return str_contains($this->get('/')->assertOk()->getContent(), 'data-anniv5-seal');
    }

    public function test_without_saved_settings_the_config_dates_decide(): void
    {
        $this->assertSame(0, AnniversarySetting::count());

        $this->at('2026-11-22 23:59:59');
        $this->assertFalse(Anniversary::isActive());
        $this->assertFalse($this->homeShowsCeremony());

        $this->at('2026-11-23 00:00:00');
        $this->assertTrue(Anniversary::isActive());
        $this->assertTrue($this->homeShowsCeremony());

        $this->at('2026-12-07 00:00:00');
        $this->assertFalse(Anniversary::isActive());
    }

    public function test_automatic_mode_follows_the_saved_start_and_end_in_addis_ababa_time(): void
    {
        $this->save();
        $this->flushSession();
        Auth::logout();

        $this->at('2026-11-30 23:59:59');
        $this->assertFalse(Anniversary::liveForVisitors());
        $this->at('2026-12-01 00:00:00');
        $this->assertTrue(Anniversary::liveForVisitors());
        $this->assertTrue($this->homeShowsCeremony());
        $this->at('2026-12-09 23:59:59');
        $this->assertTrue(Anniversary::liveForVisitors());
        $this->at('2026-12-10 00:00:00'); // end is exclusive
        $this->assertFalse(Anniversary::liveForVisitors());
        $this->assertFalse($this->homeShowsCeremony());
    }

    public function test_off_is_always_hidden_and_on_now_is_live_at_once_until_the_end(): void
    {
        $this->save(['mode' => 'off', 'starts_at' => '2026-11-01T00:00']);
        Auth::logout();
        $this->at('2026-11-05 12:00:00'); // inside the dates, but switched off
        $this->assertFalse(Anniversary::liveForVisitors());
        $this->assertFalse($this->homeShowsCeremony());

        $this->save(['mode' => 'on']); // starts 1 Dec, but "on now" ignores the start
        Auth::logout();
        $this->at('2026-11-05 12:00:00');
        $this->assertTrue(Anniversary::liveForVisitors());
        $this->assertTrue($this->homeShowsCeremony());
        $this->at('2026-12-10 00:00:00'); // ...and stops at the end time
        $this->assertFalse(Anniversary::liveForVisitors());
    }

    public function test_admin_preview_shows_it_to_a_logged_in_admin_but_never_to_visitors(): void
    {
        $this->at('2026-11-05 12:00:00'); // before the start: hidden for visitors
        $this->save(['admin_preview' => '1']);

        $this->assertTrue($this->homeShowsCeremony(), 'the admin (still logged in) sees it');

        Auth::logout();
        $this->flushSession();
        $this->assertFalse($this->homeShowsCeremony(), 'a guest does not');

        $this->actingAs(User::factory()->create()); // a non-admin account is a visitor too
        $this->assertFalse($this->homeShowsCeremony());
    }

    public function test_admin_preview_unticked_shows_nothing_to_admins_either(): void
    {
        $this->at('2026-11-05 12:00:00');
        $this->save(['admin_preview' => '0']);

        $this->assertFalse($this->homeShowsCeremony());
    }

    public function test_only_admins_can_open_or_save_the_settings_page(): void
    {
        $this->get(route('admin.anniversary.edit'))->assertRedirect(route('login'));
        $this->put(route('admin.anniversary.update'), ['mode' => 'on'])->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.anniversary.edit'))->assertForbidden();
        $this->actingAs($user)->put(route('admin.anniversary.update'), ['mode' => 'on', 'starts_at' => '2026-12-01T00:00', 'ends_at' => '2026-12-10T00:00'])->assertForbidden();
        $this->assertSame(0, AnniversarySetting::count());

        $this->actingAs($this->admin())->get(route('admin.anniversary.edit'))->assertOk()->assertSee('Anniversary Ceremony');
    }

    public function test_saving_clears_the_cache_so_the_change_shows_immediately(): void
    {
        $this->at('2026-11-05 12:00:00');
        $this->assertFalse(Anniversary::liveForVisitors()); // primes the cache with "no saved row"

        $this->save(['mode' => 'on']);
        Auth::logout();
        $this->assertTrue(Anniversary::liveForVisitors(), 'visible on the very next check, no waiting for the cache');
        $this->assertTrue($this->homeShowsCeremony());

        $this->save(['mode' => 'off']);
        Auth::logout();
        $this->assertFalse($this->homeShowsCeremony());
    }

    public function test_end_must_be_after_start_and_nothing_is_saved_otherwise(): void
    {
        $this->save(['starts_at' => '2026-12-10T00:00', 'ends_at' => '2026-12-01T00:00'])->assertSessionHasErrors('ends_at');
        $this->save(['mode' => 'bogus'])->assertSessionHasErrors('mode');
        $this->assertSame(0, AnniversarySetting::count());
    }

    public function test_the_page_states_clearly_what_visitors_see(): void
    {
        $admin = $this->admin();

        $this->at('2026-11-05 12:00:00');
        $this->actingAs($admin)->get(route('admin.anniversary.edit'))
            ->assertSee('Currently HIDDEN for visitors. Will appear automatically on Nov 23, 2026 at 00:00 (Addis Ababa time).', false);

        $this->at('2026-11-25 12:00:00');
        $this->actingAs($admin)->get(route('admin.anniversary.edit'))
            ->assertSee('Currently LIVE for visitors until Dec 7, 2026 at 00:00 (Addis Ababa time).', false);

        $this->save(['mode' => 'off']);
        $this->actingAs($admin)->get(route('admin.anniversary.edit'))->assertSee('switched off', false);
    }
}
