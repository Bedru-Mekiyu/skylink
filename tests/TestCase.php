<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CatalogFixtures;

    public function createApplication()
    {
        $app = parent::createApplication();

        // Safety stop: tests wipe their database, so it must be the dedicated test one.
        if ($app['config']->get('database.connections.mysql.database') !== 'skylink_test') {
            throw new RuntimeException('Tests may only run against the skylink_test database (is the config cached?).');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\SiteSetting::clearRuntimeCache();
    }
}
