<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every test process writes its files (private archive, uploads) into its OWN temporary folder, never into the
     * real storage/app. Before this, two runs at the same time (a build and a manual run, or a running dev server)
     * trampled each other's files and tests failed at random.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'skylink-tests-'.getmypid();
        foreach (['app/private', 'app/public'] as $d) {
            if (! is_dir($root.'/'.$d)) {
                mkdir($root.'/'.$d, 0777, true);
            }
        }
        config(['filesystems.disks.local.root' => $root.'/app/private', 'filesystems.disks.public.root' => $root.'/app/public']);
        \Illuminate\Support\Facades\Storage::forgetDisk(['local', 'public']);
    }

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
