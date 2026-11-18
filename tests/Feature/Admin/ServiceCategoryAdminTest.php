<?php

namespace Tests\Feature\Admin;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Admin > Service Categories: the cards and filter buttons on /services.
 * Uses a real (tiny) JPEG so the image rules pass without the GD extension.
 */
class ServiceCategoryAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        // The migration seeds the eight existing cards; start each test from an empty list.
        ServiceCategory::query()->delete();
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['is_admin' => true])->save();
    }

    private function jpeg(string $name = 'card.jpg'): UploadedFile
    {
        // 1x1 white JPEG.
        $bytes = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Cloud Hosting Services',
            'card_title' => 'Cloud Hosting',
            'filter_label' => 'Cloud',
            'description' => 'Managed cloud hosting and backups.',
            'sort_order' => 90,
            'is_published' => '1',
            'image' => $this->jpeg(),
        ], $overrides);
    }

    public function test_guests_are_sent_to_login_and_non_admins_are_refused(): void
    {
        $this->get(route('admin.service-categories.index'))->assertRedirect(route('login'));
        $this->post(route('admin.service-categories.store'), $this->payload())->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.service-categories.index'))->assertForbidden();
        $this->assertSame(0, ServiceCategory::count());
    }

    public function test_admin_creates_a_category_and_it_appears_on_the_services_page(): void
    {
        $this->actingAs($this->admin)->get(route('admin.service-categories.create'))->assertOk();
        $this->actingAs($this->admin)->post(route('admin.service-categories.store'), $this->payload())
            ->assertRedirect(route('admin.service-categories.index'));

        $category = ServiceCategory::firstOrFail();
        $this->assertSame('cloud-hosting-services', $category->slug);
        $this->assertStringStartsWith('storage/service_categories/', $category->image);
        Storage::disk('public')->assertExists(substr($category->image, strlen('storage/')));

        $html = $this->get(route('services'))->assertOk()
            ->assertSee('Cloud Hosting')
            ->assertSee('Managed cloud hosting and backups.')
            ->getContent();
        // Filter button with its short label, and a card in that category.
        $this->assertMatchesRegularExpression('/data-filter="cloud-hosting-services"[^>]*>\s*Cloud\s*</', $html);
        $this->assertStringContainsString('data-category="cloud-hosting-services"', $html);
        $this->get(route('services.category', ['category' => 'cloud-hosting-services']))->assertOk();
    }

    public function test_hidden_categories_are_not_shown_and_order_is_respected(): void
    {
        ServiceCategory::create(['slug' => 'second', 'name' => 'Second Card', 'description' => 'b', 'image' => 'assets/x.jpg', 'sort_order' => 20, 'is_published' => true]);
        ServiceCategory::create(['slug' => 'first', 'name' => 'First Card', 'description' => 'a', 'image' => 'assets/x.jpg', 'sort_order' => 10, 'is_published' => true]);
        ServiceCategory::create(['slug' => 'draft', 'name' => 'Hidden Card', 'description' => 'c', 'image' => 'assets/x.jpg', 'sort_order' => 5, 'is_published' => false]);

        $html = $this->get(route('services'))->assertOk()->assertDontSee('Hidden Card')->getContent();
        $this->assertLessThan(strpos($html, 'Second Card'), strpos($html, 'First Card'));
    }

    public function test_admin_edits_a_category_and_its_web_address_stays(): void
    {
        $this->actingAs($this->admin)->post(route('admin.service-categories.store'), $this->payload());
        $category = ServiceCategory::firstOrFail();
        $oldImage = $category->image;

        $this->actingAs($this->admin)->get(route('admin.service-categories.edit', $category))->assertOk();
        $this->actingAs($this->admin)->put(route('admin.service-categories.update', $category), $this->payload([
            'name' => 'Renamed Category', 'card_title' => '', 'description' => 'New text.', 'is_published' => '0', 'image' => $this->jpeg('new.jpg'),
        ]))->assertRedirect(route('admin.service-categories.index'));

        $category->refresh();
        $this->assertSame('cloud-hosting-services', $category->slug);
        $this->assertSame('Renamed Category', $category->card_heading);
        $this->assertFalse($category->is_published);
        $this->assertNotSame($oldImage, $category->image);
        Storage::disk('public')->assertMissing(substr($oldImage, strlen('storage/')));
        $this->get(route('services'))->assertDontSee('Renamed Category');
    }

    public function test_editing_without_a_new_image_keeps_the_current_one(): void
    {
        $this->actingAs($this->admin)->post(route('admin.service-categories.store'), $this->payload());
        $category = ServiceCategory::firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.service-categories.update', $category), $this->payload(['image' => null]))
            ->assertSessionHasNoErrors();
        $this->assertSame($category->image, $category->fresh()->image);
    }

    public function test_admin_deletes_an_empty_category_but_not_one_that_has_services(): void
    {
        $this->actingAs($this->admin)->post(route('admin.service-categories.store'), $this->payload());
        $category = ServiceCategory::firstOrFail();
        Service::forceCreate(['slug' => 'svc', 'title' => 'A service', 'desc' => 'x', 'image' => 'x.jpg', 'features' => json_encode(['a']), 'category_id' => $category->id]);

        $this->actingAs($this->admin)->delete(route('admin.service-categories.destroy', $category))
            ->assertRedirect(route('admin.service-categories.index'))->assertSessionHas('error');
        $this->assertNotNull($category->fresh());

        Service::query()->delete();
        $image = substr($category->image, strlen('storage/'));
        $this->actingAs($this->admin)->delete(route('admin.service-categories.destroy', $category))->assertSessionHas('success');
        $this->assertNull($category->fresh());
        Storage::disk('public')->assertMissing($image);
    }

    public function test_uploads_must_be_images_and_required_on_create(): void
    {
        $this->actingAs($this->admin)->post(route('admin.service-categories.store'), $this->payload([
            'image' => UploadedFile::fake()->create('card.pdf', 20, 'application/pdf'),
        ]))->assertSessionHasErrors('image');
        $this->actingAs($this->admin)->post(route('admin.service-categories.store'), $this->payload(['image' => null]))
            ->assertSessionHasErrors('image');
        $this->assertSame(0, ServiceCategory::count());
    }
}
