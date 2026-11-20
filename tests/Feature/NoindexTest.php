<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Test sites carry a robots noindex meta tag; production does not. */
class NoindexTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_sites_are_marked_noindex_and_production_is_not(): void
    {
        $this->get(route('home'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->app['env'] = 'production';
        config(['mail.staging_guard.enabled' => false]);
        $this->get(route('home'))->assertDontSee('name="robots"', false);

        config(['mail.staging_guard.enabled' => true]); // bedru runs APP_ENV=production with the staging guard on
        $this->get(route('home'))->assertSee('name="robots" content="noindex, nofollow"', false);
    }
}
