<?php

namespace Tests\Feature\Admin;

use App\Models\ContactMessage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Admin inbox: opening a message marks it read, the badge matches the list, "Mark all as read". */
class InboxUnreadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    private function inquiry(array $o = []): Inquiry
    {
        // forceFill: read_at and the legacy_* columns are not mass-assignable.
        $inquiry = (new Inquiry())->forceFill($o + ['type' => 'contact', 'name' => 'Test Visitor', 'email' => 'v@example.test', 'message' => 'Hello']);
        $inquiry->save();

        return $inquiry;
    }

    public function test_opening_a_message_marks_it_read_and_the_badge_drops(): void
    {
        $admin = $this->admin();
        $a = $this->inquiry();
        $b = $this->inquiry();

        $this->actingAs($admin)->get(route('admin.inbox.index'))
            ->assertSee('Unread only (2)', false)->assertSee('2<span class="visually-hidden"> unread', false);

        $this->actingAs($admin)->get(route('admin.inbox.show', $a))->assertOk()->assertSee('1<span class="visually-hidden"> unread', false);
        $this->assertNotNull($a->fresh()->read_at);

        $this->actingAs($admin)->get(route('admin.inbox.show', $b))->assertOk();
        $this->actingAs($admin)->get(route('admin.inbox.index'))->assertSee('Unread only (0)', false)->assertDontSee('visually-hidden"> unread', false);
    }

    public function test_the_count_only_includes_messages_the_inbox_lists(): void
    {
        $admin = $this->admin();
        $this->inquiry();
        $this->inquiry()->delete(); // trashed: not listed, so not counted

        $this->actingAs($admin)->get(route('admin.inbox.index'))->assertSee('Unread only (1)', false);
        $this->assertSame(1, Inquiry::unread()->count());
    }

    public function test_mark_all_as_read_clears_the_count_and_keeps_every_message(): void
    {
        $admin = $this->admin();
        $this->inquiry();
        $this->inquiry();
        $this->inquiry(['read_at' => now()->subDay()]);

        $this->actingAs($admin)->get(route('admin.inbox.index'))->assertSee('Mark all as read (2)', false);
        $this->actingAs($admin)->post(route('admin.inbox.read-all'))->assertRedirect()->assertSessionHas('success', '2 messages marked as read.');

        $this->assertSame(0, Inquiry::unread()->count());
        $this->assertSame(3, Inquiry::count());
        $this->actingAs($admin)->get(route('admin.inbox.index'))->assertDontSee('Mark all as read', false);
    }

    public function test_viewing_a_message_on_the_older_messages_page_marks_its_inbox_copy_read(): void
    {
        $admin = $this->admin();
        $legacy = $this->inquiry(['legacy_table' => 'contact_messages', 'legacy_id' => 41]);
        $other = $this->inquiry(['legacy_table' => 'contact_messages', 'legacy_id' => 42]);
        ContactMessage::unguarded(fn () => ContactMessage::create(['id' => 41, 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.test', 'phone' => '0', 'message' => 'x']));

        $this->actingAs($admin)->post(route('admin.contact-messages.read', 41))->assertNoContent();

        $this->assertNotNull($legacy->fresh()->read_at);
        $this->assertNull($other->fresh()->read_at);
    }

    public function test_guests_and_non_admins_are_blocked(): void
    {
        $inquiry = $this->inquiry();
        $user = User::factory()->create();

        $this->post(route('admin.inbox.read-all'))->assertRedirect(route('login'));
        $this->get(route('admin.inbox.show', $inquiry))->assertRedirect(route('login'));
        $this->actingAs($user)->post(route('admin.inbox.read-all'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.inbox.show', $inquiry))->assertForbidden();

        $this->assertNull($inquiry->fresh()->read_at);
    }
}
