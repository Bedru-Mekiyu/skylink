<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class NoLeftoversTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_old_plan_subscription_address_leads_to_services(): void
    {
        $this->get('/subscribe/gold')->assertRedirect('/services');
        $this->assertFileDoesNotExist(resource_path('views/services/subscribe.blade.php'));
    }

    public function test_no_debug_output_or_unused_controller_is_left(): void
    {
        $noisy = collect(File::allFiles(resource_path('views')))->filter(fn ($f) => preg_match('/console\.log\(/', $f->getContents()))
            ->map->getRelativePathname()->values()->all();

        $this->assertSame([], $noisy);
        $this->assertFileDoesNotExist(app_path('Http/Controllers/ChatController.php'));
    }
}
