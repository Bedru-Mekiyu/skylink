<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** One palette, from the logo: public/assets/css/sky-brand.css. */
class BrandColourTest extends TestCase
{
    public function test_no_generic_framework_blue_is_left_in_the_pages(): void
    {
        $generic = '/#(3b82f6|2563eb|1e40af|0d6efd|4285f4|3f7af9|4a90e2|3a7bd5|2a62b3|2f6cc4|17a2b8|2196f3)\b|rgba?\(\s*(59,\s*130,\s*246|13,\s*110,\s*253|63,\s*122,\s*249)/i';
        $found = collect(File::allFiles(resource_path('views')))->map->getPathname()
            ->merge(glob(public_path('assets/css/*.css')))
            ->reject(fn ($f) => str_contains($f, 'anniversary'))
            ->filter(fn ($f) => preg_match($generic, file_get_contents($f)))
            ->map(fn ($f) => str_replace(base_path().'/', '', $f))->values()->all();

        $this->assertSame([], $found);
    }

    public function test_public_and_sign_in_pages_load_the_brand_colours_after_bootstrap(): void
    {
        foreach (['/products', '/cart', '/login'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertStringContainsString('assets/css/sky-brand.css', $html, $path);
            $this->assertGreaterThan(strpos($html, 'bootstrap.min.css'), strpos($html, 'sky-brand.css'), $path);
        }
        $this->assertStringContainsString('sky-brand', file_get_contents(resource_path('views/admin/layouts/master.blade.php')));
    }
}
