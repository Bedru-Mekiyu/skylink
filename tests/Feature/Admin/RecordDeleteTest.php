<?php

namespace Tests\Feature\Admin;

use App\Models\Inquiry;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Subscribers, plan subscriptions and inbox messages: delete one, delete selected, CSV export; admin only. */
class RecordDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    private function subscribers(int $n): array
    {
        $ids = [];
        for ($i = 1; $i <= $n; $i++) {
            $ids[] = Subscriber::create(['email' => "sub$i@example.test"])->id;
        }

        return $ids;
    }

    public function test_guests_and_non_admins_cannot_delete_or_export(): void
    {
        [$a] = $this->subscribers(1);
        $s = Subscription::create(['name' => 'N', 'email' => 'n@example.test', 'phone' => '1', 'plan' => 'basic']);
        $inq = Inquiry::create(['type' => 'contact', 'name' => 'V', 'email' => 'v@example.test', 'message' => 'Hello there', 'reference' => 'T-1']);
        $calls = [
            ['delete', route('admin.subscribers.destroy', $a)], ['delete', route('admin.subscribers.bulk-destroy')], ['get', route('admin.subscribers.export')],
            ['delete', route('admin.subscriptions.destroy', $s->id)], ['delete', route('admin.subscriptions.bulk-destroy')], ['get', route('admin.subscriptions.export')],
            ['delete', route('admin.inbox.destroy', $inq)], ['get', route('admin.inbox.export')],
        ];
        foreach ($calls as [$m, $url]) {
            auth()->logout();
            $this->$m($url)->assertRedirect(route('login'));
            $this->actingAs(User::factory()->create())->$m($url)->assertStatus(403);
        }
        $this->assertSame(1, Subscriber::count());
        $this->assertSame(1, Subscription::count());
        $this->assertModelExists($inq);
        auth()->logout();
        $this->get(route('admin.subscribers.destroy', $a))->assertStatus(405); // deleting is DELETE only
    }

    public function test_delete_one_and_delete_selected_remove_rows_and_the_pages_offer_the_modal_and_export(): void
    {
        $ids = $this->subscribers(5);
        $admin = $this->admin();
        $page = $this->actingAs($admin)->get(route('admin.subscribers.index'))->assertOk();
        $page->assertSee('Export CSV')->assertSee('Delete selected')->assertSee('data-sky-confirm', false)->assertSee('min-width:44px', false);
        $this->actingAs($admin)->delete(route('admin.subscribers.destroy', $ids[0]))->assertRedirect(route('admin.subscribers.index'));
        $this->assertDatabaseMissing('subscribers', ['id' => $ids[0]]);
        $this->actingAs($admin)->delete(route('admin.subscribers.bulk-destroy'), ['ids' => [$ids[1], $ids[2]]])->assertRedirect();
        $this->assertSame(2, Subscriber::count());
        $this->actingAs($admin)->delete(route('admin.subscribers.bulk-destroy'), [])->assertSessionHasErrors('ids');
        $this->assertSame(2, Subscriber::count());
    }

    public function test_subscriptions_delete_and_export(): void
    {
        $admin = $this->admin();
        $a = Subscription::create(['name' => 'A', 'email' => 'a@example.test', 'phone' => '1', 'plan' => 'basic']);
        $b = Subscription::create(['name' => 'B', 'email' => 'b@example.test', 'phone' => '2', 'plan' => 'pro']);
        $this->actingAs($admin)->get(route('admin.subscriptions.index'))->assertOk()->assertSee('Delete selected');
        $this->actingAs($admin)->delete(route('admin.subscriptions.destroy', $a->id))->assertRedirect();
        $this->assertDatabaseMissing('subscriptions', ['id' => $a->id]);
        $this->actingAs($admin)->delete(route('admin.subscriptions.bulk-destroy'), ['ids' => [$b->id]])->assertRedirect();
        $this->assertSame(0, Subscription::count());
    }

    public function test_csv_export_has_the_rows_and_neutralises_spreadsheet_formulas(): void
    {
        Subscriber::create(['email' => '=HYPERLINK("http://evil.test")@example.test']);
        Subscriber::create(['email' => 'normal@example.test']);
        $r = $this->actingAs($this->admin())->get(route('admin.subscribers.export'));
        $r->assertOk();
        $this->assertStringContainsString('text/csv', $r->headers->get('Content-Type'));
        $csv = $r->streamedContent();
        $this->assertStringContainsString('Email,Subscribed', $csv);
        $this->assertStringContainsString('normal@example.test', $csv);
        $this->assertStringNotContainsString('"=HYPERLINK', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $only = $this->actingAs($this->admin())->get(route('admin.subscribers.export', ['ids' => [Subscriber::where('email', 'normal@example.test')->value('id')]]))->streamedContent();
        $this->assertStringContainsString('normal@example.test', $only);
        $this->assertStringNotContainsString('HYPERLINK', $only);
    }

    public function test_inbox_messages_are_deleted_for_good_and_can_be_exported(): void
    {
        $admin = $this->admin();
        $a = Inquiry::create(['type' => 'contact', 'name' => 'Visitor A', 'email' => 'va@example.test', 'message' => 'Hello Amharic ሰላም', 'reference' => 'T-A']);
        $b = Inquiry::create(['type' => 'quote', 'name' => 'Visitor B', 'email' => 'vb@example.test', 'message' => 'Second', 'reference' => 'T-B']);
        $csv = $this->actingAs($admin)->get(route('admin.inbox.export'))->streamedContent();
        $this->assertStringContainsString('Visitor A', $csv);
        $this->assertStringContainsString('ሰላም', $csv);
        $this->actingAs($admin)->get(route('admin.inbox.index'))->assertOk()->assertSee('Export CSV');
        $this->actingAs($admin)->delete(route('admin.inbox.destroy', $a))->assertRedirect();
        $this->assertNull(Inquiry::withTrashed()->find($a->id), 'removed for good');
        $this->actingAs($admin)->delete(route('admin.inbox.bulk-destroy'), ['ids' => [$b->id]])->assertRedirect();
        $this->assertSame(0, Inquiry::withTrashed()->count());
    }
}
