<?php

namespace Tests\Feature\Admin;

use App\Models\ContactMessage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Admin inbox: delete one message, delete several, soft delete, unread count, old Messages page, access rules. */
class InboxDeleteTest extends TestCase
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
        $inquiry = (new Inquiry())->forceFill($o + ['type' => 'contact', 'name' => 'Visitor '.uniqid(), 'email' => 'v@example.test', 'message' => 'Hello']);
        $inquiry->save();

        return $inquiry;
    }

    public function test_the_list_has_a_delete_button_per_row_checkboxes_and_delete_selected_with_the_confirmation_text(): void
    {
        $a = $this->inquiry();
        $html = $this->actingAs($this->admin())->get(route('admin.inbox.index'))->assertOk()->getContent();

        $this->assertStringContainsString('form="delete-message-'.$a->id.'"', $html);
        $this->assertStringContainsString('name="ids[]" value="'.$a->id.'"', $html);
        $this->assertStringContainsString('id="select-all"', $html);
        $this->assertStringContainsString('Delete selected', $html);
        $this->assertStringContainsString("Delete this message? This can\\'t be undone from the admin.", $html);
        $this->assertStringContainsString('action="'.route('admin.inbox.destroy', $a).'"', $html);
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);

        $show = $this->actingAs($this->admin())->get(route('admin.inbox.show', $a))->assertOk()->getContent();
        $this->assertStringContainsString("Delete this message? This can\\'t be undone from the admin.", $show);
    }

    public function test_admin_deletes_one_message_for_good_and_the_unread_count_drops(): void
    {
        $admin = $this->admin();
        $a = $this->inquiry();
        $b = $this->inquiry();

        $this->actingAs($admin)->get(route('admin.inbox.index'))->assertSee('Unread only (2)', false);

        $this->actingAs($admin)->delete(route('admin.inbox.destroy', $a))
            ->assertRedirect(route('admin.inbox.index'))->assertSessionHas('success', 'Message deleted.');

        $this->assertDatabaseMissing('inquiries', ['id' => $a->id]);        // removed for good (export the CSV first)
        $this->assertNull(Inquiry::withTrashed()->find($a->id));
        $this->assertSame(1, Inquiry::unread()->count());
        $this->actingAs($admin)->get(route('admin.inbox.index'))->assertSee('Unread only (1)', false)->assertDontSee($a->reference)->assertSee($b->reference);
        $this->actingAs($admin)->get(route('admin.inbox.show', $a))->assertNotFound();
    }

    public function test_admin_deletes_several_selected_messages_at_once(): void
    {
        $admin = $this->admin();
        $keep = $this->inquiry();
        $x = $this->inquiry();
        $y = $this->inquiry();

        $this->actingAs($admin)->delete(route('admin.inbox.bulk-destroy'), ['ids' => [$x->id, $y->id]])
            ->assertRedirect(route('admin.inbox.index'))->assertSessionHas('success', '2 messages deleted.');

        $this->assertDatabaseMissing('inquiries', ['id' => $x->id]);
        $this->assertDatabaseMissing('inquiries', ['id' => $y->id]);
        $this->assertNotSoftDeleted('inquiries', ['id' => $keep->id]);
        $this->assertSame(1, Inquiry::unread()->count());
        $this->actingAs($admin)->get(route('admin.inbox.index'))->assertSee('Unread only (1)', false)->assertSee($keep->reference)->assertDontSee($x->reference)->assertDontSee($y->reference);
    }

    public function test_bulk_delete_needs_a_selection_and_ignores_unknown_ids(): void
    {
        $admin = $this->admin();
        $a = $this->inquiry();

        $this->actingAs($admin)->delete(route('admin.inbox.bulk-destroy'), [])->assertSessionHasErrors('ids');
        $this->actingAs($admin)->delete(route('admin.inbox.bulk-destroy'), ['ids' => ['abc']])->assertSessionHasErrors('ids.0');
        $this->actingAs($admin)->delete(route('admin.inbox.bulk-destroy'), ['ids' => [999999]])->assertSessionHas('success', '0 messages deleted.');
        $this->assertNotSoftDeleted('inquiries', ['id' => $a->id]);

        $this->actingAs($admin)->delete(route('admin.inbox.bulk-destroy'), ['ids' => [$a->id]])->assertSessionHas('success', 'Message deleted.');
    }

    public function test_the_older_messages_page_hides_the_copy_of_a_message_deleted_in_the_inbox(): void
    {
        $admin = $this->admin();
        $old = ContactMessage::create(['first_name' => 'Old', 'last_name' => 'Sender', 'email' => 'o@example.test', 'phone' => '0911', 'company' => 'Co', 'message' => 'legacy text']);
        $other = ContactMessage::create(['first_name' => 'Other', 'last_name' => 'Person', 'email' => 'p@example.test', 'phone' => '0922', 'company' => 'Co', 'message' => 'other legacy text']);
        $copy = $this->inquiry(['legacy_table' => 'contact_messages', 'legacy_id' => $old->id, 'name' => 'Old Sender']);
        $this->inquiry(['legacy_table' => 'contact_messages', 'legacy_id' => $other->id, 'name' => 'Other Person']);

        $this->actingAs($admin)->get(route('admin.contact-messages.index'))->assertOk()->assertSee('legacy text')->assertSee('other legacy text');

        $this->actingAs($admin)->delete(route('admin.inbox.destroy', $copy));

        $this->actingAs($admin)->get(route('admin.contact-messages.index'))->assertOk()
            ->assertDontSee('Old Sender', false)->assertSee('Other Person', false);
    }

    public function test_guests_and_non_admins_cannot_delete(): void
    {
        $a = $this->inquiry();
        $user = User::factory()->create();

        $this->delete(route('admin.inbox.destroy', $a))->assertRedirect(route('login'));
        $this->delete(route('admin.inbox.bulk-destroy'), ['ids' => [$a->id]])->assertRedirect(route('login'));
        $this->actingAs($user)->delete(route('admin.inbox.destroy', $a))->assertForbidden();
        $this->actingAs($user)->delete(route('admin.inbox.bulk-destroy'), ['ids' => [$a->id]])->assertForbidden();

        $this->assertNotSoftDeleted('inquiries', ['id' => $a->id]);
    }

    public function test_delete_is_not_possible_with_a_get_request(): void
    {
        $admin = $this->admin();
        $a = $this->inquiry();

        $this->actingAs($admin)->get('/admin/inbox/'.$a->id.'/delete')->assertNotFound();
        $this->actingAs($admin)->get(route('admin.inbox.bulk-destroy'))->assertOk(); // /admin/inbox is just the list page
        $this->assertNotSoftDeleted('inquiries', ['id' => $a->id]);
    }

    public function test_delete_requires_a_csrf_token(): void
    {
        $a = $this->inquiry();
        $admin = $this->admin();

        // the test client skips CSRF by default; the form must carry the token and the method spoof
        $html = $this->actingAs($admin)->get(route('admin.inbox.index'))->getContent();
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'name="_token"'));
        $this->assertStringContainsString('id="bulk-delete-form"', $html);
        $this->assertMatchesRegularExpression('#id="bulk-delete-form">\s*<input type="hidden" name="_token"#', $html);
    }
}
