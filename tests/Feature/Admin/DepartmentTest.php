<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Admin > Departments and the About page cards. */
class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    private function make(int $n): void
    {
        Department::query()->delete();
        for ($i = 1; $i <= $n; $i++) {
            Department::create(['name' => "Dept $i", 'tagline' => "Tag $i", 'stats' => [['n' => '1+', 'l' => 'One']], 'sort_order' => $i, 'visible' => true]);
        }
    }

    private function png(string $name = 'd.png'): UploadedFile
    {
        $im = imagecreatetruecolor(1200, 700);
        imagefill($im, 0, 0, imagecolorallocate($im, 10, 100, 200));
        $tmp = tempnam(sys_get_temp_dir(), 'dp').'.png';
        imagepng($im, $tmp);

        return new UploadedFile($tmp, $name, 'image/png', null, true);
    }

    public function test_the_two_existing_cards_are_imported_exactly(): void
    {
        $this->assertSame(['Software Team', 'Marketing Team'], Department::orderBy('sort_order')->pluck('name')->all());
        $html = $this->get(route('about'))->assertOk()->getContent();
        foreach (['Software Team', 'Building the future with code', 'Innovation Driven', '15+', 'Developers', '50+', 'Projects', 'Marketing Team', 'Connecting brands with audiences', 'Creative Excellence', '10+', 'Marketers', '100+', 'Campaigns', 'assets/images/contact/team-support.jpg', 'assets/images/about/marketing.jpg'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $this->assertLessThan(strpos($html, 'Marketing Team'), strpos($html, 'Software Team'));
    }

    public function test_any_number_of_departments_renders_in_two_column_rows_and_none_hides_the_section(): void
    {
        foreach ([1, 2, 3, 4, 5, 7] as $n) {
            $this->make($n);
            $html = $this->get(route('about'))->getContent();
            $this->assertSame($n, substr_count($html, 'team-showcase-card h-100'), "$n cards");
            $this->assertStringContainsString('justify-content-center', substr($html, strpos($html, 'id="departments"') - 80, 120));
            $this->assertSame($n, substr_count($html, 'col-12 col-md-6" data-aos'), "$n columns (two per row from tablet up, one on phones)");
        }
        Department::query()->update(['visible' => false]);
        \App\Support\PublicCache::bust();
        $html = $this->get(route('about'))->getContent();
        $this->assertStringNotContainsString('id="departments"', $html);
        $this->assertStringNotContainsString('team-showcase-card h-100', $html);
    }

    public function test_only_admins_reach_the_routes(): void
    {
        $d = Department::first();
        $this->get(route('admin.departments.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.departments.index'))->assertStatus(403);
        $this->actingAs(User::factory()->create())->delete(route('admin.departments.destroy', $d))->assertStatus(403);
        $this->assertModelExists($d);
        $this->actingAs($this->admin())->get(route('admin.departments.index'))->assertOk()->assertSee('Software Team');
        $this->actingAs($this->admin())->get(route('admin.departments.create'))->assertOk();
    }

    public function test_add_edit_validate_hide_move_and_delete_with_files(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.departments.store'), ['name' => 'Support Team', 'tagline' => 'Always here', 'icon' => 'fas fa-headset', 'accent' => 'success', 'stat_n' => ['8+', ''], 'stat_l' => ['Agents', ''], 'image' => $this->png()])->assertSessionHasNoErrors();
        $d = Department::where('name', 'Support Team')->firstOrFail();
        $this->assertSame([['n' => '8+', 'l' => 'Agents']], $d->stats);
        $this->assertMatchesRegularExpression('#^departments/[A-Za-z0-9]{40}\.webp$#', $d->image_path);
        Storage::disk('public')->assertExists([$d->image_path, 'departments/.htaccess']);
        Storage::disk('local')->assertExists($d->original_path);
        $this->assertStringContainsString('Support Team', $this->get(route('about'))->getContent());

        // validation
        $this->actingAs($admin)->post(route('admin.departments.store'), ['name' => 'X', 'accent' => 'primary', 'stat_n' => ['5'], 'stat_l' => ['']])->assertSessionHasErrors('stat_n');
        $this->actingAs($admin)->post(route('admin.departments.store'), ['name' => 'X', 'accent' => 'primary', 'icon' => 'bad icon <script>'])->assertSessionHasErrors('icon');
        $this->actingAs($admin)->post(route('admin.departments.store'), ['name' => 'X', 'accent' => 'purple'])->assertSessionHasErrors('accent');
        $this->actingAs($admin)->post(route('admin.departments.store'), ['name' => 'Too many', 'accent' => 'primary', 'stat_n' => ['1', '2', '3', '4', '5'], 'stat_l' => ['a', 'b', 'c', 'd', 'e']])->assertSessionHasErrors('stat_n');
        foreach ([UploadedFile::fake()->createWithContent('a.png', 'text'), UploadedFile::fake()->createWithContent('b.php.png', '<?php echo 1;'), UploadedFile::fake()->createWithContent('c.png.php', 'x')] as $bad) {
            $this->actingAs($admin)->post(route('admin.departments.store'), ['name' => 'Bad', 'accent' => 'primary', 'image' => $bad])->assertSessionHasErrors('image');
        }
        $this->assertSame(0, Department::where('name', 'Bad')->count());

        $this->actingAs($admin)->put(route('admin.departments.toggle', $d))->assertRedirect();
        $this->assertStringNotContainsString('Support Team', $this->get(route('about'))->getContent());
        $this->actingAs($admin)->put(route('admin.departments.move', $d), ['dir' => 'up'])->assertRedirect();
        $this->assertSame($d->id, Department::orderBy('sort_order')->skip(1)->first()->id);

        $paths = [$d->image_path, $d->original_path];
        $this->actingAs($admin)->delete(route('admin.departments.destroy', $d))->assertRedirect(route('admin.departments.index'));
        $this->assertModelMissing($d);
        Storage::disk('public')->assertMissing($paths[0]);
        Storage::disk('local')->assertMissing($paths[1]);
    }

    public function test_deleting_an_imported_department_never_deletes_the_site_files(): void
    {
        $d = Department::where('name', 'Marketing Team')->firstOrFail();
        $this->actingAs($this->admin())->delete(route('admin.departments.destroy', $d))->assertRedirect();
        $this->assertFileExists(public_path('assets/images/about/marketing.jpg'));
    }

    public function test_the_migration_rolls_back_and_runs_again_with_the_import(): void
    {
        $this->assertTrue(Schema::hasTable('departments'));
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/2026_11_25_100100_create_departments_table.php', '--force' => true]);
        $this->assertFalse(Schema::hasTable('departments'));
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_11_25_100100_create_departments_table.php', '--force' => true]);
        $this->assertSame(2, Department::count());
    }
}
