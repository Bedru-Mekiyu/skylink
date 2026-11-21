<?php

namespace Tests\Feature\Admin;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Admin > Testimonials and the public strip (homepage + Services page). */
class TestimonialTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    private function make(int $n, array $extra = []): void
    {
        for ($i = 1; $i <= $n; $i++) {
            Testimonial::create($extra + ['name' => "Client $i", 'role' => 'CUSTOMER', 'quote_en' => "Quote number $i", 'sort_order' => 100 + $i, 'visible' => true]);
        }
    }

    private function png(string $name = 'face.png', int $w = 300, int $h = 200): UploadedFile
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 30, 120, 200));
        $tmp = tempnam(sys_get_temp_dir(), 'tp').'.png';
        imagepng($im, $tmp);

        return new UploadedFile($tmp, $name, 'image/png', null, true);
    }

    public function test_the_existing_six_testimonials_are_imported_in_order_and_shown_on_both_pages(): void
    {
        $this->assertSame(6, Testimonial::count());
        $names = Testimonial::orderBy('sort_order')->pluck('name')->all();
        $this->assertSame(['Ibrahim Mohammed', 'Aisha Malik', 'Khalid Kibrom', 'Zain Karim', 'Kalkidan Gezu', 'Taye Abrar'], $names);
        foreach (['/', '/services'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('What Our Clients Say About', $html);
            $this->assertStringContainsString('Ibrahim Mohammed', $html);
            $this->assertLessThan(strpos($html, 'Taye Abrar'), strpos($html, 'Aisha Malik'));
            $this->assertStringContainsString('I’d recommend them', $html);
        }
    }

    public function test_the_strip_is_seamless_for_1_3_6_and_20_testimonials(): void
    {
        foreach ([1, 3, 6, 20] as $n) {
            Testimonial::query()->delete();
            $this->make($n);
            $html = $this->get('/')->getContent();
            $this->assertStringContainsString('style="--n: '.$n.'"', $html);
            $cards = substr_count($html, 'class="testimonial-card"');
            $this->assertSame(0, $cards % $n, "$n: whole sets only");
            $this->assertGreaterThanOrEqual(2, intdiv($cards, $n), "$n: at least two sets");
            $this->assertStringContainsString('prefers-reduced-motion', $html);
        }
    }

    public function test_no_visible_testimonial_hides_the_section(): void
    {
        Testimonial::query()->update(['visible' => false]);
        \App\Support\PublicCache::bust();
        foreach (['/', '/services'] as $url) {
            $this->assertStringNotContainsString('What Our Clients Say', $this->get($url)->assertOk()->getContent());
        }
    }

    public function test_long_and_amharic_quotes_and_language_fallback(): void
    {
        Testimonial::query()->delete();
        $long = str_repeat('Very long praise. ', 60);
        Testimonial::create(['name' => 'Long One', 'quote_en' => $long, 'sort_order' => 1, 'visible' => true]);
        Testimonial::create(['name' => 'Amharic One', 'quote_am' => 'በጣም ጥሩ አገልግሎት ነው', 'sort_order' => 2, 'visible' => true]);
        $html = $this->get('/')->getContent();
        $this->assertStringContainsString(trim($long), $html);
        $this->assertStringContainsString('በጣም ጥሩ አገልግሎት ነው', $html, 'only an Amharic quote: shown in English mode too');
    }

    public function test_only_admins_reach_the_routes_and_changes_are_post_put_delete_only(): void
    {
        $t = Testimonial::first();
        $this->get(route('admin.testimonials.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.testimonials.index'))->assertStatus(403);
        $this->actingAs(User::factory()->create())->delete(route('admin.testimonials.destroy', $t))->assertStatus(403);
        $this->assertModelExists($t);
        $this->actingAs($this->admin())->get(route('admin.testimonials.destroy', $t))->assertStatus(405);
        $this->actingAs($this->admin())->get(route('admin.testimonials.index'))->assertOk()->assertSee('Ibrahim Mohammed')->assertSee('data-sky-confirm', false);
    }

    public function test_admin_can_add_with_photo_edit_hide_move_and_delete_with_files(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.testimonials.store'), ['name' => 'New Client', 'role' => 'CEO, Example PLC', 'quote_en' => 'Great work.', 'rating' => 5, 'photo' => $this->png()])->assertSessionHasNoErrors();
        $t = Testimonial::where('name', 'New Client')->firstOrFail();
        $this->assertMatchesRegularExpression('#^testimonials/[A-Za-z0-9]{40}\.webp$#', $t->photo_path);
        Storage::disk('public')->assertExists([$t->photo_path, 'testimonials/.htaccess']);
        Storage::disk('local')->assertExists($t->original_path);
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($t->photo_path));
        $this->assertSame($w, $h, 'square');
        $this->assertLessThanOrEqual(160, $w);

        $this->actingAs($admin)->put(route('admin.testimonials.update', $t), ['name' => 'New Client', 'quote_en' => 'Even better.', 'remove_photo' => 1])->assertSessionHasNoErrors();
        $this->assertNull($t->fresh()->photo_path);
        Storage::disk('public')->assertMissing($t->photo_path);

        $this->actingAs($admin)->put(route('admin.testimonials.toggle', $t))->assertRedirect();
        $this->assertFalse($t->fresh()->visible);
        $this->assertStringNotContainsString('New Client', $this->get('/')->getContent());

        $first = Testimonial::orderBy('sort_order')->first();
        $second = Testimonial::orderBy('sort_order')->skip(1)->first();
        $this->actingAs($admin)->put(route('admin.testimonials.move', $second), ['dir' => 'up'])->assertRedirect();
        $this->assertSame($second->id, Testimonial::orderBy('sort_order')->first()->id);
        $this->assertNotSame($first->id, Testimonial::orderBy('sort_order')->first()->id);

        // delete removes the row and its files
        $this->actingAs($admin)->post(route('admin.testimonials.store'), ['name' => 'Gone Soon', 'quote_am' => 'ጥሩ', 'photo' => $this->png('g.png')])->assertSessionHasNoErrors();
        $g = Testimonial::where('name', 'Gone Soon')->firstOrFail();
        $paths = [$g->photo_path, $g->original_path];
        $this->actingAs($admin)->delete(route('admin.testimonials.destroy', $g))->assertRedirect(route('admin.testimonials.index'));
        $this->assertModelMissing($g);
        Storage::disk('public')->assertMissing($paths[0]);
        Storage::disk('local')->assertMissing($paths[1]);
    }

    public function test_validation_and_bad_uploads_are_rejected(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.testimonials.store'), ['name' => 'No Quote'])->assertSessionHasErrors(['quote_en', 'quote_am']);
        $this->actingAs($admin)->post(route('admin.testimonials.store'), ['name' => 'X', 'quote_en' => 'ok', 'rating' => 9])->assertSessionHasErrors('rating');
        $bad = [
            'text renamed png' => UploadedFile::fake()->createWithContent('a.png', 'just some text'),
            'php renamed png' => UploadedFile::fake()->createWithContent('b.php.png', '<?php echo 1;'),
            'double extension' => UploadedFile::fake()->createWithContent('c.png.php', 'x'),
            'svg' => UploadedFile::fake()->createWithContent('d.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
            'too large' => UploadedFile::fake()->create('big.png', 5000, 'image/png'),
        ];
        foreach ($bad as $label => $file) {
            $this->actingAs($admin)->post(route('admin.testimonials.store'), ['name' => 'Bad '.$label, 'quote_en' => 'ok', 'photo' => $file])->assertSessionHasErrors('photo');
        }
        $this->assertSame(0, Testimonial::where('name', 'like', 'Bad %')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_the_migration_rolls_back_and_runs_again_with_the_import(): void
    {
        $this->assertTrue(Schema::hasTable('testimonials'));
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/2026_11_25_100000_create_testimonials_table.php', '--force' => true]);
        $this->assertFalse(Schema::hasTable('testimonials'));
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_11_25_100000_create_testimonials_table.php', '--force' => true]);
        $this->assertSame(6, Testimonial::count());
    }
}
