<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Tests that resize or re-encode images need the GD extension (App\Support\NewsImageProcessor).
     * Where PHP has no GD they are skipped with a reason instead of failing for an environment problem.
     */
    protected function requireGd(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is not installed in this PHP (enable extension=gd in php.ini to run this test).');
        }
    }
}
