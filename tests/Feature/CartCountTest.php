<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CatalogFixtures;
use Tests\TestCase;

class CartCountTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    public function test_the_navbar_number_counts_items_not_quantities(): void
    {
        $first = $this->service();
        $second = $this->service();
        $line = ['item_type' => 'service', 'item_id' => $first->id, 'option_id' => $this->option($first)->id];

        $this->postJson('/cart/add', $line)->assertJson(['count' => 1]);
        $this->postJson('/cart/add', $line + ['quantity' => 4])->assertJson(['count' => 1]);

        $key = array_key_first(session('cart'));
        $this->postJson('/cart/update', ['line_key' => $key, 'quantity' => 9])->assertJson(['count' => 1]);
        $this->assertSame(9, session('cart')[$key]['quantity']);

        $this->postJson('/cart/add', ['item_type' => 'service', 'item_id' => $second->id, 'option_id' => $this->option($second)->id])->assertJson(['count' => 2]);
        $this->postJson('/cart/remove', ['line_key' => $key])->assertJson(['count' => 1]);
        $this->getJson('/cart/count')->assertExactJson(['count' => 1]);
    }
}
