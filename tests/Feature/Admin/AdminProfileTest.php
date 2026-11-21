<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Admin > My Profile: name, email, password; security rules. */
class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $o = []): User
    {
        $u = User::factory()->create($o);
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    public function test_the_profile_page_is_in_the_admin_menu_and_shows_both_forms(): void
    {
        $admin = $this->admin(['name' => 'Test Admin', 'email' => 'admin@example.test']);

        $html = $this->actingAs($admin)->get(route('admin.profile.edit'))->assertOk()
            ->assertSee('Profile details')->assertSee('Change password')->assertSee('value="Test Admin"', false)->assertSee('value="admin@example.test"', false)
            ->getContent();

        $this->assertStringContainsString('href="'.route('admin.profile.edit').'"', $html);          // menu link
        $this->assertStringNotContainsString('Edit Profile</a>', $html);                              // the old dead link is gone
        $this->assertGreaterThanOrEqual(4, substr_count($html, 'class="btn btn-outline-secondary js-toggle-password"')); // show/hide on every password box
    }

    public function test_name_can_be_changed_without_the_password(): void
    {
        $admin = $this->admin(['name' => 'Old Name']);

        $this->actingAs($admin)->patch(route('admin.profile.update'), ['name' => 'New Name', 'email' => $admin->email])
            ->assertRedirect(route('admin.profile.edit'))->assertSessionHas('success', 'Profile updated.');

        $this->assertSame('New Name', $admin->fresh()->name);
    }

    public function test_email_change_needs_the_current_password_and_must_be_unique(): void
    {
        $admin = $this->admin(['email' => 'me@example.test']);
        $other = $this->admin(['email' => 'taken@example.test']);

        // no password
        $this->actingAs($admin)->patch(route('admin.profile.update'), ['name' => $admin->name, 'email' => 'new@example.test'])
            ->assertSessionHasErrorsIn('profile', 'current_password');
        // wrong password
        $this->actingAs($admin)->patch(route('admin.profile.update'), ['name' => $admin->name, 'email' => 'new@example.test', 'current_password' => 'wrong-password'])
            ->assertSessionHasErrorsIn('profile', 'current_password');
        $this->assertSame('me@example.test', $admin->fresh()->email);

        // taken by another account
        $this->actingAs($admin)->patch(route('admin.profile.update'), ['name' => $admin->name, 'email' => 'taken@example.test', 'current_password' => 'password'])
            ->assertSessionHasErrorsIn('profile', 'email');
        // invalid
        $this->actingAs($admin)->patch(route('admin.profile.update'), ['name' => $admin->name, 'email' => 'not-an-email', 'current_password' => 'password'])
            ->assertSessionHasErrorsIn('profile', 'email');
        $this->assertSame('me@example.test', $admin->fresh()->email);

        // right password, free address (stored lowercase)
        $this->actingAs($admin)->patch(route('admin.profile.update'), ['name' => $admin->name, 'email' => 'New@Example.test', 'current_password' => 'password'])
            ->assertSessionHasNoErrors();
        $this->assertSame('new@example.test', $admin->fresh()->email);
        $this->assertSame('taken@example.test', $other->fresh()->email);
    }

    public function test_password_change_rules(): void
    {
        $admin = $this->admin();
        $put = fn (array $d) => $this->actingAs($admin)->put(route('admin.profile.password'), $d);

        $put(['current_password' => 'wrong', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])->assertSessionHasErrorsIn('password', 'current_password');
        $put(['current_password' => 'password', 'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrorsIn('password', 'password');
        $put(['current_password' => 'password', 'password' => 'new-password-1', 'password_confirmation' => 'different-1'])->assertSessionHasErrorsIn('password', 'password');
        $put(['current_password' => 'password', 'password' => 'password', 'password_confirmation' => 'password'])->assertSessionHasErrorsIn('password', 'password');
        $this->assertTrue(Hash::check('password', $admin->fresh()->password), 'nothing changed after the failed attempts');
    }

    public function test_the_new_password_works_and_is_stored_hashed_and_never_shown(): void
    {
        $admin = $this->admin(['email' => 'pw@example.test']);

        $response = $this->actingAs($admin)->put(route('admin.profile.password'), ['current_password' => 'password', 'password' => 'brand-new-pass-9', 'password_confirmation' => 'brand-new-pass-9'])
            ->assertRedirect(route('admin.profile.edit'))->assertSessionHas('success');

        $stored = $admin->fresh()->password;
        $this->assertNotSame('brand-new-pass-9', $stored);
        $this->assertTrue(Hash::check('brand-new-pass-9', $stored));
        $this->assertStringNotContainsString('brand-new-pass-9', $this->actingAs($admin)->get(route('admin.profile.edit'))->getContent());

        // log out, then log in with the NEW password (and not with the old one)
        Auth::logout();
        $this->flushSession();
        $this->post('/login', ['email' => 'pw@example.test', 'password' => 'password'])->assertSessionHasErrors();
        $this->assertGuest();
        $this->post('/login', ['email' => 'pw@example.test', 'password' => 'brand-new-pass-9']);
        $this->assertAuthenticatedAs($admin->fresh());
    }

    public function test_after_a_password_change_other_sessions_are_signed_out_and_this_one_stays(): void
    {
        $admin = $this->admin();
        $oldHash = $admin->getAuthPassword();

        // this device changes the password
        $this->actingAs($admin)->put(route('admin.profile.password'), ['current_password' => 'password', 'password' => 'brand-new-pass-9', 'password_confirmation' => 'brand-new-pass-9'])->assertSessionHas('success');

        // the same session continues to work
        $this->get(route('admin.profile.edit'))->assertOk();

        // another device: its session still carries the OLD password hash, so it is no longer valid
        $this->flushSession();
        $this->actingAs($admin->fresh())->withSession(['password_hash_web' => $oldHash])->get(route('admin.profile.edit'))->assertRedirect(route('login'));
    }

    public function test_guests_cannot_reach_the_profile_and_non_admins_get_403(): void
    {
        $this->get(route('admin.profile.edit'))->assertRedirect(route('login'));
        $this->patch(route('admin.profile.update'), ['name' => 'x', 'email' => 'x@example.test'])->assertRedirect(route('login'));
        $this->put(route('admin.profile.password'), [])->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.profile.edit'))->assertForbidden();
        $this->actingAs($user)->patch(route('admin.profile.update'), ['name' => 'x', 'email' => 'x@example.test'])->assertForbidden();
        $this->actingAs($user)->put(route('admin.profile.password'), ['current_password' => 'password', 'password' => 'brand-new-pass-9', 'password_confirmation' => 'brand-new-pass-9'])->assertForbidden();
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_an_admin_can_only_edit_their_own_account(): void
    {
        $me = $this->admin(['name' => 'Me', 'email' => 'me@example.test']);
        $other = $this->admin(['name' => 'Other', 'email' => 'other@example.test']);

        // an id sent in the request is ignored: the account always comes from the session
        $this->actingAs($me)->patch(route('admin.profile.update'), ['id' => $other->id, 'user_id' => $other->id, 'name' => 'Changed', 'email' => 'me@example.test']);
        $this->assertSame('Changed', $me->fresh()->name);
        $this->assertSame('Other', $other->fresh()->name);

        $this->actingAs($me)->put(route('admin.profile.password'), ['id' => $other->id, 'current_password' => 'password', 'password' => 'brand-new-pass-9', 'password_confirmation' => 'brand-new-pass-9']);
        $this->assertTrue(Hash::check('password', $other->fresh()->password), "the other admin's password is untouched");
        $this->assertTrue(Hash::check('brand-new-pass-9', $me->fresh()->password));
    }

    public function test_password_attempts_are_rate_limited(): void
    {
        $admin = $this->admin();
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($admin)->put(route('admin.profile.password'), ['current_password' => 'wrong', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])->assertSessionHasErrorsIn('password', 'current_password');
        }
        $this->actingAs($admin)->put(route('admin.profile.password'), ['current_password' => 'password', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])->assertStatus(429);
        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }

    public function test_a_password_change_is_written_to_the_audit_log_without_the_password(): void
    {
        \Illuminate\Support\Facades\Log::spy();
        $admin = $this->admin();
        $this->actingAs($admin)->put(route('admin.profile.password'), ['current_password' => 'password', 'password' => 'brand-new-pass-9', 'password_confirmation' => 'brand-new-pass-9']);
        \Illuminate\Support\Facades\Log::shouldHaveReceived('notice')->withArgs(fn ($m, $c = []) => $m === 'admin password changed' && $c === ['user_id' => $admin->id])->once();
    }
}
