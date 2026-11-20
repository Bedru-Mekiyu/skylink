<?php

namespace Tests\Feature\Admin;

use App\Models\NewsCategory;
use App\Models\Portfolio;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Admin can add portfolio items, videos, services and products; guests and non-admins cannot.
 * (News and service categories have their own test files.)
 */
class AdminCanAddContentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['is_admin' => true])->save();
    }

    private function jpeg(string $name = 'pic.jpg'): UploadedFile
    {
        // 1x1 JPEG; avoids needing the GD extension.
        $bytes = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    public function test_admin_adds_a_portfolio_project(): void
    {
        $this->actingAs($this->admin)->get(route('admin.portfolio.create'))->assertOk();
        $this->actingAs($this->admin)->post(route('admin.portfolio.store'), [
            'title' => 'Test Project', 'description' => 'A project.', 'image' => $this->jpeg(),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.portfolio.index'));

        $this->assertDatabaseHas('portfolios', ['title' => 'Test Project']);
    }

    public function test_admin_adds_a_video(): void
    {
        $category = NewsCategory::create(['name' => 'Videos']);
        $this->actingAs($this->admin)->get(route('admin.videos.create'))->assertOk();
        $this->actingAs($this->admin)->post(route('admin.videos.store'), [
            'title' => 'Test Video', 'news_category_id' => $category->id, 'video_url' => 'https://www.youtube.com/watch?v=8R4J33Dp2x8',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.videos.index'));

        $this->assertDatabaseHas('videos', ['title' => 'Test Video']);
    }

    public function test_admin_adds_a_service(): void
    {
        $category = ServiceCategory::query()->first() ?? ServiceCategory::create([
            'slug' => 'cat', 'name' => 'Cat', 'description' => 'd', 'image' => 'assets/x.jpg', 'sort_order' => 1, 'is_published' => true,
        ]);
        $this->actingAs($this->admin)->get(route('admin.services.create'))->assertOk();
        $this->actingAs($this->admin)->post(route('admin.services.store'), [
            'slug' => 'test-service', 'title' => 'Test Service', 'desc' => 'Text.', 'image' => $this->jpeg(),
            'features' => ['One', 'Two'], 'category_id' => $category->id,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.services.index'));

        $this->assertDatabaseHas('services', ['slug' => 'test-service']);
        $this->assertSame(1, Service::where('slug', 'test-service')->count());
    }

    public function test_admin_adds_a_product(): void
    {
        $this->actingAs($this->admin)->get(route('admin.products.create'))->assertOk();
        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'category' => 'hardware', 'slug' => 'test-product', 'name' => 'Test Product',
            'image' => 'https://example.com/p.jpg', 'description' => 'Text.', 'price' => 99, 'features' => 'a, b',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', ['slug' => 'test-product']);
    }

    public function test_invalid_input_is_rejected_and_nothing_is_saved(): void
    {
        $this->actingAs($this->admin)->post(route('admin.portfolio.store'), [])->assertSessionHasErrors(['title', 'description', 'image']);
        $this->actingAs($this->admin)->post(route('admin.videos.store'), [])->assertSessionHasErrors(['title', 'video_url']);
        $this->actingAs($this->admin)->post(route('admin.services.store'), [])->assertSessionHasErrors(['slug', 'title']);
        $this->actingAs($this->admin)->post(route('admin.products.store'), [])->assertSessionHasErrors(['slug', 'name', 'price']);

        $this->assertSame(0, Portfolio::count() + Video::count() + Product::count());
    }

    public function test_guests_and_non_admins_cannot_add_anything(): void
    {
        $user = User::factory()->create();

        $names = ['portfolio', 'videos', 'services', 'products'];
        // Guests first: actingAs() keeps the user logged in for later requests in the same test.
        foreach ($names as $name) {
            $this->post(route("admin.$name.store"), [])->assertRedirect(route('login'));
        }
        foreach ($names as $name) {
            $this->actingAs($user)->post(route("admin.$name.store"), [])->assertForbidden();
        }

        $this->assertSame(0, Portfolio::count() + Video::count() + Product::count());
    }
}
