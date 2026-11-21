<?php

namespace Tests\Feature\Admin;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Admin > Partners and the homepage "Trusted Partners" marquee. */
class PartnerTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    private function png(string $name = 'logo.png', int $px = 200): UploadedFile
    {
        $im = imagecreatetruecolor($px, (int) ($px / 2));
        ob_start(); imagepng($im); $bytes = ob_get_clean();

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    private function homeLogoCount(): int
    {
        return substr_count($this->get(route('home'))->getContent(), 'class="partner-logo"');
    }

    // ---- access ---------------------------------------------------------------------------------------------------

    public function test_guests_and_non_admins_cannot_reach_partner_routes(): void
    {
        $p = Partner::first();
        $routes = [
            ['get', route('admin.partners.index')], ['get', route('admin.partners.create')], ['post', route('admin.partners.store')],
            ['get', route('admin.partners.edit', $p)], ['put', route('admin.partners.update', $p)],
            ['put', route('admin.partners.toggle', $p)], ['put', route('admin.partners.reorder')],
        ];
        foreach ($routes as [$verb, $url]) {
            $this->$verb($url)->assertRedirect(route('login'));
        }
        $this->actingAs(User::factory()->create());
        foreach ($routes as [$verb, $url]) {
            $this->$verb($url)->assertForbidden();
        }
        $this->assertTrue($p->fresh()->active);
    }

    public function test_changes_need_the_right_http_verb(): void
    {
        $this->actingAs($this->admin());
        $p = Partner::first();
        $this->get(route('admin.partners.toggle', $p))->assertStatus(405);
        $this->get(route('admin.partners.destroy', $p))->assertStatus(405); // deleting is DELETE only (see DeleteEverythingTest)
        $this->assertSame(27, Partner::count(), 'nothing can be deleted');
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['name' => 'x', 'email' => 'x@example.com', 'password' => 'password-123', 'password_confirmation' => 'password-123'])->assertStatus(404);
    }

    // ---- homepage: import, order, hidden, featured, fallback -------------------------------------------------------

    public function test_import_keeps_the_homepage_logos_in_the_same_order(): void
    {
        $this->assertSame(27, Partner::count());
        $html = $this->get(route('home'))->getContent();
        $this->assertSame(54, $this->homeLogoCount(), '27 logos, repeated once for the seamless loop');
        $first = strpos($html, 'assets/images/minbertv.webp');
        $mid = strpos($html, 'assets/images/trusted/ad.png');
        $last = strpos($html, 'assets/images/trusted/maroli.JPG');
        $this->assertTrue($first < $mid && $mid < $last);
        $this->assertStringContainsString('Trusted Partners', $html);
        $this->assertStringContainsString('width="', $html);
        $this->assertStringContainsString('prefers-reduced-motion', $html);
    }

    public function test_hidden_partners_do_not_show_and_featured_come_first(): void
    {
        Partner::where('name', 'Minber')->update(['active' => false]);
        Partner::where('name', 'Maroli')->update(['featured' => true]);
        \App\Support\PublicCache::bust();
        $html = $this->get(route('home'))->getContent();
        $this->assertStringNotContainsString('assets/images/minbertv.webp', $html);
        $this->assertSame(52, $this->homeLogoCount());
        $this->assertLessThan(strpos($html, 'assets/images/trusted/ad.png'), strpos($html, 'assets/images/trusted/maroli.JPG'), 'featured first');
    }

    public function test_empty_table_falls_back_to_the_static_logos(): void
    {
        Partner::query()->delete();
        \App\Support\PublicCache::bust();
        $this->assertSame(54, $this->homeLogoCount());
    }

    public function test_few_logos_render_a_still_row_and_many_still_work(): void
    {
        Partner::query()->where('id', '>', 3)->update(['active' => false]);
        \App\Support\PublicCache::bust();
        $html = $this->get(route('home'))->getContent();
        $this->assertSame(3, $this->homeLogoCount());
        $this->assertStringContainsString('partners-slider--still', $html);

        Partner::query()->update(['active' => true]);
        for ($i = 0; $i < 13; $i++) {
            Partner::create(['name' => "Extra $i", 'legacy_src' => 'assets/images/trusted/ad.png', 'sort_order' => 100 + $i]);
        }
        \App\Support\PublicCache::bust();
        $this->assertSame(80, $this->homeLogoCount());
    }

    public function test_linked_logos_open_in_a_new_tab_safely(): void
    {
        Partner::where('name', 'Minber')->update(['website_url' => 'https://example.com/']);
        \App\Support\PublicCache::bust();
        $html = $this->get(route('home'))->getContent();
        $this->assertStringContainsString('href="https://example.com/" target="_blank" rel="noopener noreferrer" aria-label="Minber (opens in a new tab)"', $html);
    }

    // ---- admin pages ------------------------------------------------------------------------------------------------

    public function test_list_search_paging_and_toggle(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('admin.partners.index'))->assertOk()->assertSee('Minber')->assertSee('Next');
        $this->get(route('admin.partners.index', ['q' => 'safari']))->assertOk()->assertSee('Safaricom')->assertDontSee('Minber');
        $p = Partner::where('name', 'Minber')->first();
        $this->put(route('admin.partners.toggle', $p))->assertRedirect();
        $this->assertFalse($p->fresh()->active);
        $this->assertSame(27, Partner::count());
        $this->put(route('admin.partners.toggle', $p))->assertRedirect();
        $this->assertTrue($p->fresh()->active);
    }

    public function test_reorder_swaps_positions_and_busts_the_cache(): void
    {
        $this->actingAs($this->admin());
        $ids = Partner::orderBy('sort_order')->limit(3)->pluck('id')->all();
        $this->putJson(route('admin.partners.reorder'), ['ids' => [$ids[2], $ids[0], $ids[1]]])->assertOk();
        $this->assertSame([$ids[2], $ids[0], $ids[1]], Partner::orderBy('sort_order')->limit(3)->pluck('id')->all());
        $this->putJson(route('admin.partners.reorder'), ['ids' => [999999]])->assertStatus(422);
    }

    // ---- uploads ----------------------------------------------------------------------------------------------------

    public function test_invalid_uploads_are_rejected(): void
    {
        $this->actingAs($this->admin());
        $bad = [
            'double extension' => UploadedFile::fake()->createWithContent('evil.php.png', '<?php echo 1;'),
            'php as png' => UploadedFile::fake()->createWithContent('x.png', '<?php echo 1;'),
            'svg' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
            'html as jpg' => UploadedFile::fake()->createWithContent('x.jpg', '<html><script>1</script></html>'),
            'gif' => UploadedFile::fake()->createWithContent('x.gif', 'GIF89a'.str_repeat("\0", 20)),
            'mp4' => UploadedFile::fake()->createWithContent('x.mp4', "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom"),
        ];
        foreach ($bad as $label => $file) {
            $this->postJson(route('admin.partners.store'), ['name' => 'Bad', 'logo' => $file])->assertStatus(422)->assertJsonValidationErrors('logo');
        }
        $big = UploadedFile::fake()->createWithContent('big.png', base64_decode(self::PNG))->size(2049);
        $this->postJson(route('admin.partners.store'), ['name' => 'Big', 'logo' => $big])->assertStatus(422)->assertJsonValidationErrors('logo');
        $this->postJson(route('admin.partners.store'), ['logo' => UploadedFile::fake()->createWithContent('t.png', base64_decode(self::PNG))])->assertStatus(422)->assertJsonValidationErrors('name');
        $this->postJson(route('admin.partners.store'), ['name' => 'No logo'])->assertStatus(422)->assertJsonValidationErrors('logo');
        $this->postJson(route('admin.partners.store'), ['name' => 'Http', 'website_url' => 'http://insecure.example', 'logo' => UploadedFile::fake()->createWithContent('t.png', base64_decode(self::PNG))])->assertStatus(422)->assertJsonValidationErrors('website_url');
        $this->assertSame(27, Partner::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_valid_upload_makes_small_copies_and_archives_the_original(): void
    {
        $this->requireGd();
        $this->actingAs($this->admin());
        $this->post(route('admin.partners.store'), [
            'name' => 'Acme Ltd', 'website_url' => 'https://acme.example', 'since_year' => 2020, 'featured' => 1, 'logo' => $this->png('big logo.png', 1200),
        ])->assertRedirect(route('admin.partners.index'));

        $p = Partner::where('name', 'Acme Ltd')->firstOrFail();
        $this->assertSame('Acme Ltd', $p->alt, 'alt defaults to the name');
        $this->assertMatchesRegularExpression('#^partners/[A-Za-z0-9]{40}\.webp$#', $p->logo_path);
        $this->assertMatchesRegularExpression('#^partners/[A-Za-z0-9]{40}\.png$#', $p->logo_fallback);
        $this->assertLessThanOrEqual(400, $p->logo_w);
        Storage::disk('public')->assertExists([$p->logo_path, $p->logo_fallback, 'partners/.htaccess']);
        Storage::disk('local')->assertExists($p->original_path);
        $this->assertStringContainsString('Require all denied', Storage::disk('public')->get('partners/.htaccess'));
        $this->assertTrue($p->featured);

        $html = $this->get(route('home'))->getContent();
        $this->assertStringContainsString('storage/'.$p->logo_path, $html);
        $this->assertStringContainsString('width="'.$p->logo_w.'"', $html);
    }

    public function test_edit_keeps_logo_when_none_is_uploaded(): void
    {
        $this->actingAs($this->admin());
        $p = Partner::first();
        $this->put(route('admin.partners.update', $p), ['name' => 'Renamed', 'alt' => '', 'sort_order' => 5])->assertRedirect(route('admin.partners.index'));
        $p->refresh();
        $this->assertSame('Renamed', $p->name);
        $this->assertSame('Renamed', $p->alt);
        $this->assertNotNull($p->legacy_src);
    }

    public function test_uploads_are_rate_limited(): void
    {
        $this->actingAs($this->admin());
        for ($i = 0; $i < 30; $i++) {
            $this->postJson(route('admin.partners.store'), [])->assertStatus(422);
        }
        $this->postJson(route('admin.partners.store'), [])->assertStatus(429);
    }
}
