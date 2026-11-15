<?php

namespace Tests\Feature\Security;

use App\Models\NewsCategory;
use App\Models\User;
use App\Support\ImageUploadRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Every admin image upload accepts only real JPG/PNG/WebP images up to 5 MB,
 * and stores them under a random name.
 */
class ImageUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    private function passes(UploadedFile $file): bool
    {
        return Validator::make(['image' => $file], ['image' => ImageUploadRules::rules(true)])->passes();
    }

    /**
     * A real uploaded file whose MIME type is detected from its CONTENT
     * (finfo), as in production. Laravel's UploadedFile::fake() reports the
     * MIME type from the file NAME, so it cannot test disguised files.
     */
    private function realUpload(string $clientName, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload-test-');
        file_put_contents($path, $content);

        return new UploadedFile($path, $clientName, null, null, true);
    }

    private function jpegBytes(): string
    {
        $image = UploadedFile::fake()->image('source.jpg', 40, 40); // keep a reference: the temp file dies with the object

        return file_get_contents($image->getRealPath());
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    // ----- the shared rule -----------------------------------------------

    public function test_real_jpg_png_and_webp_images_are_accepted(): void
    {
        // Real image bytes, content-sniffed exactly like a production upload.
        foreach (['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'] as $ext => $mime) {
            $source = UploadedFile::fake()->image("source.{$ext}", 30, 30); // keep a reference: the temp file dies with the object
            $upload = $this->realUpload("photo.{$ext}", file_get_contents($source->getRealPath()));

            $this->assertSame($mime, $upload->getMimeType(), "{$ext} must be detected from its content");
            $this->assertTrue($this->passes($upload), "A real {$ext} image must be accepted");
        }
    }

    public function test_scripts_and_documents_are_rejected(): void
    {
        $this->assertFalse($this->passes(UploadedFile::fake()->createWithContent('shell.php', '<?php system($_GET["c"]); ?>')));
        $this->assertFalse($this->passes(UploadedFile::fake()->createWithContent('page.html', '<html><body><?php echo 1; ?></body></html>')));
        $this->assertFalse($this->passes(UploadedFile::fake()->createWithContent('notes.txt', 'plain text')));
        $this->assertFalse($this->passes(UploadedFile::fake()->createWithContent('run.exe', "MZ\x90\x00\x03")));
        $this->assertFalse($this->passes(UploadedFile::fake()->createWithContent(
            'vector.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
        )));
    }

    public function test_a_script_disguised_with_an_image_extension_is_rejected(): void
    {
        $this->assertFalse($this->passes($this->realUpload('photo.jpg', '<?php echo "pwned"; ?>')));
        $this->assertFalse($this->passes($this->realUpload('photo.png', '<html><script>alert(1)</script></html>')));
    }

    public function test_a_real_image_with_a_script_extension_is_rejected(): void
    {
        $jpeg = $this->jpegBytes();
        $this->assertNotSame('', $jpeg);

        $this->assertFalse($this->passes($this->realUpload('shell.php', $jpeg)));
        $this->assertFalse($this->passes($this->realUpload('page.html', $jpeg)));
        $this->assertFalse($this->passes($this->realUpload('photo.php.jpg.txt', $jpeg)));
        $this->assertTrue($this->passes($this->realUpload('photo.jpg', $jpeg)), 'Control: the same bytes as .jpg pass.');
    }

    public function test_files_over_5_mb_are_rejected(): void
    {
        $this->assertFalse($this->passes(UploadedFile::fake()->image('huge.jpg')->size(ImageUploadRules::MAX_KILOBYTES + 1)));
        $this->assertTrue($this->passes(UploadedFile::fake()->image('ok.jpg')->size(ImageUploadRules::MAX_KILOBYTES)));
    }

    // ----- every upload entry point uses it ------------------------------

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function uploadEndpoints(): array
    {
        return [
            'news' => ['admin.news.store', 'image'],
            'video thumbnail' => ['admin.videos.store', 'thumbnail_image'],
            'portfolio' => ['admin.portfolio.store', 'image'],
            'services' => ['admin.services.store', 'image'],
        ];
    }

    /**
     * @dataProvider uploadEndpoints
     */
    public function test_each_admin_upload_endpoint_rejects_a_disguised_script(string $route, string $field): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route($route), [$field => $this->realUpload('photo.jpg', '<?php echo 1; ?>')])
            ->assertSessionHasErrors($field);

        $this->assertSame([], Storage::disk('public')->allFiles(), 'Nothing may be written to the public disk.');
    }

    public function test_a_valid_news_image_is_stored_under_random_names(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $category = NewsCategory::create(['name' => 'News']);
        $admin = $this->admin();

        // The form issues a one-time token (Phase 2 double-submit protection).
        $this->actingAs($admin)->get(route('admin.news.create'))->assertOk();
        $token = collect(session('form_tokens.admin-news'))->last();

        $this->actingAs($admin)
            ->post(route('admin.news.store'), [
                'title' => 'Upload test',
                'language' => 'en',
                'news_category_id' => $category->id,
                'content' => '<p>Body</p>',
                'status' => 'published',
                'form_token' => $token,
                'image' => UploadedFile::fake()->image('My Holiday Photo.jpg', 1200, 800),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.news.index'));

        // Web copy, thumbnail and JPEG share copy on the public disk, the original
        // on the private disk; none of them uses the client's file name.
        $public = Storage::disk('public')->allFiles('news');
        $this->assertCount(3, $public);
        foreach ($public as $file) {
            $this->assertMatchesRegularExpression('#^news/(web|thumbs|share)/[A-Za-z0-9]{40}\.(webp|jpg)$#', $file);
        }
        $private = Storage::disk('local')->allFiles('news-originals');
        $this->assertCount(1, $private);
        $this->assertMatchesRegularExpression('#^news-originals/[A-Za-z0-9]{40}\.jpe?g$#', $private[0]);
    }
}
