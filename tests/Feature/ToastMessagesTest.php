<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CatalogFixtures;
use Tests\TestCase;

class ToastMessagesTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    public function test_confirmations_are_one_pop_up_and_never_a_box_left_on_the_page(): void
    {
        $service = $this->service(null, ['slug' => 'toasty']);
        $this->option($service);

        foreach (['/products', '/services/detail/toasty', '/cart'] as $path) {
            $page = $this->withSession(['success' => 'Saved it.'])->get($path)->assertOk()->assertSee('data-toast="success"', false);
            $this->assertSame(1, substr_count($page->getContent(), 'Saved it.'), $path);
            $page->assertDontSee('class="alert alert-success', false);
        }
    }

    public function test_the_admin_uses_the_same_pop_up(): void
    {
        $this->actingAs($this->staff('super_admin'));

        foreach (['/admin/dashboard', '/admin/products', '/admin/services', '/admin/prices-overview', '/admin/subscribers', '/admin/orders'] as $path) {
            $page = $this->withSession(['success' => 'Saved it.', 'warning' => 'Careful.'])->get($path)->assertOk()
                ->assertSee('data-toast="success"', false)->assertSee('data-toast="warning"', false);
            $this->assertSame(1, substr_count($page->getContent(), 'Saved it.'), $path);
        }
    }

    public function test_a_sent_form_pops_up_with_its_reference_number(): void
    {
        $this->withSession(['inquiry_success' => ['anchor' => 'contact-form', 'reference' => 'SK-TEST-1']])->get('/contact')
            ->assertOk()->assertSee('data-toast="success"', false)->assertSee('SK-TEST-1');
    }

    public function test_an_error_is_shown_once_and_waits_to_be_closed(): void
    {
        $page = $this->withSession(['error' => 'Could not do it.'])->get('/cart')->assertSee('data-toast="error"', false)->assertSee('data-timeout="0"', false);
        $this->assertSame(1, substr_count($page->getContent(), 'Could not do it.'));
    }
}
