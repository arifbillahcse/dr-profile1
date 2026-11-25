<?php

namespace Tests\Feature;

use App\Models\Chamber;
use App\Models\DoctorProfile;
use App\Models\GalleryImage;
use App\Models\MediaAsset;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Models\Video;
use App\Services\DemoWebsiteMirror;
use App\Services\MediaLibrary;
use App\Services\WebsiteImporter;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoWebsiteMirrorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_reference_migration_matches_all_public_modules_and_preserves_accounts_pending_reviews_and_existing_images(): void
    {
        app(WebsiteImporter::class)->run();
        $user = User::factory()->admin()->create()->fresh();
        $userAttributes = $user->getAttributes();
        $pending = Review::factory()->create();
        $approved = Review::factory()->approved()->create(['message' => 'Previous public feedback']);
        $oldImage = MediaAsset::first();
        $oldBytes = Storage::disk('public')->get($oldImage->path);

        $this->artisan('website:match-demo')->expectsOutputToContain('Demo matched')->assertSuccessful();

        $this->assertSame($userAttributes, $user->fresh()->getAttributes());
        $this->assertModelExists($pending);
        $this->assertSoftDeleted($approved);
        $this->assertSame($oldBytes, Storage::disk('public')->get($oldImage->path));
        $this->assertSame(6, Service::published()->count());
        $this->assertSame(9, GalleryImage::published()->count());
        $this->assertSame(3, Video::published()->count());
        $this->assertSame(4, Review::approved()->count());
        $this->assertSame(1, Chamber::published()->count());
        $this->assertSame(3, Chamber::published()->first()->schedules()->count());
        $this->assertNotNull(DoctorProfile::first()->second_image_id);
        $this->assertDatabaseHas('website_imports', ['key' => DemoWebsiteMirror::MARKER]);
        $this->assertCount(1, Storage::disk('local')->files('demo-mirror-backups'));
        $backup = json_decode(Storage::disk('local')->get(Storage::disk('local')->files('demo-mirror-backups')[0]), true);
        $this->assertArrayNotHasKey('users', $backup['tables']);
        $this->assertSame('Previous public feedback', $backup['tables']['reviews'][0]['message']);

        foreach (['/', '/about', '/services', '/faqs', '/gallery', '/videos'] as $url) {
            $this->get($url)->assertOk()->assertSee('Dr. Farzana Rahman Shathi')->assertDontSee('Previous public feedback');
        }
        $this->get('/')->assertSee('Laser &amp; Skin Resurfacing', false)->assertSee('What People')->assertSee('Qualifications &amp; memberships', false);
        $this->get('/gallery')->assertSee('Clinical &amp; aesthetic dermatology', false)->assertDontSee('Explore the featured photographs above.');

        $manifest = json_decode(file_get_contents(database_path('seeders/data/demo-reference.json')), true);
        $media = MediaAsset::where('original_name', 'demo-reference-26.webp')->firstOrFail();
        $expected = collect($manifest['media'])->firstWhere('key', '26');
        $this->assertSame($expected['sha256'], hash('sha256', Storage::disk('public')->get($media->path)));
        app(MediaLibrary::class)->assertManaged($media);
        $this->get($media->url())->assertOk()->assertHeader('Content-Type', 'image/webp');
    }

    public function test_repeat_migration_is_idempotent_and_keeps_later_admin_edits(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        config(['app.url' => 'https://drfarzanashathi.com']);
        $this->artisan('website:match-demo')->assertSuccessful();
        $counts = [MediaAsset::count(), Service::withTrashed()->count(), Review::withTrashed()->count()];
        Service::published()->first()->update(['name' => 'Later edited service']);
        DoctorProfile::first()->update(['summary' => 'Later edited biography']);
        $this->artisan('website:match-demo')->expectsOutputToContain('already applied')->assertSuccessful();
        $this->assertSame($counts, [MediaAsset::count(), Service::withTrashed()->count(), Review::withTrashed()->count()]);
        $this->assertSame('Later edited biography', DoctorProfile::first()->summary);
        $this->assertContains('Member of American Academy of Aesthetic Medicine (AAAM)', DoctorProfile::first()->credentials);
        $this->assertSame('Later edited service', Service::published()->first()->name);
        $this->assertCount(1, Storage::disk('local')->files('demo-mirror-backups'));
    }

    public function test_every_public_page_matches_the_reference_except_the_requested_aaam_addition(): void
    {
        $this->artisan('website:match-demo')->assertSuccessful();
        $manifest = json_decode(file_get_contents(database_path('seeders/data/demo-reference.json')), true);
        foreach ($manifest['verification']['pages'] as $url => $expected) {
            $response = $this->get($url)->assertOk();
            $document = new \DOMDocument;
            $previous = libxml_use_internal_errors(true);
            try {
                $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
                $xpath = new \DOMXPath($document);
                $membership = 'Member of American Academy of Aesthetic Medicine (AAAM)';
                $footer = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " footer-about ")]')->item(0);
                $this->assertStringContainsString($membership, $footer->textContent, $url.' footer membership');
                $areas = match ($url) {
                    '/' => ['hero-header', 'introduction-qualifications', 'qualification-award-area'],
                    '/about' => ['academic-luxury-card'],
                    default => [],
                };
                foreach ($areas as $area) {
                    $section = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " '.$area.' ")]')->item(0);
                    $this->assertNotNull($section, $url.' '.$area);
                    $this->assertStringContainsString($membership, $section->textContent, $url.' '.$area);
                }
                foreach ($xpath->query('//section[@aria-label="Professional memberships"]') as $section) {
                    $this->assertStringContainsString($membership, $section->textContent);
                    $section->parentNode->removeChild($section);
                }
                $separator = $xpath->query('//span[contains(@class, "credential-tag") and text()="'.$membership.'"]/preceding-sibling::span[1][contains(@class, "credential-separator")]')->item(0);
                $this->assertNotNull($separator, $url.' membership separator');
                $separator->parentNode->removeChild($separator);
                foreach (['script', 'style', 'noscript'] as $tag) {
                    while ($document->getElementsByTagName($tag)->length > 0) {
                        $node = $document->getElementsByTagName($tag)->item(0);
                        $node->parentNode->removeChild($node);
                    }
                }
                $actual = preg_replace('/\s+/u', ' ', trim($document->getElementsByTagName('body')->item(0)->textContent));
                $actual = str_replace([$membership, 'Qualifications & memberships'], ['', 'Medical qualifications'], $actual);
                $actual = preg_replace('/\s+/u', ' ', trim($actual));
                $actual = preg_replace('/©\s+\d{4}/u', '© YEAR', $actual);
                $this->assertSame($expected, $actual, 'Complete public content differs on '.$url);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
        }
    }

    public function test_invalid_asset_checksum_fails_before_backups_or_content_are_written(): void
    {
        $manifest = json_decode(file_get_contents(database_path('seeders/data/demo-reference.json')), true);
        $manifest['media'][0]['sha256'] = str_repeat('0', 64);
        $path = Storage::disk('local')->path('invalid-reference.json');
        Storage::disk('local')->put('invalid-reference.json', json_encode($manifest));
        config(['website.demo_manifest' => $path]);
        $this->artisan('website:match-demo')->expectsOutputToContain('missing or changed')->assertFailed();
        $this->assertDatabaseCount('doctor_profiles', 0);
        $this->assertDatabaseCount('media_assets', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('local')->files('demo-mirror-backups'));
    }

    public function test_source_demo_and_other_production_hosts_are_protected(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        foreach (['https://doctor.demo.softorio.com', 'https://unrelated.example'] as $url) {
            config(['app.url' => $url]);
            $this->artisan('website:match-demo')->expectsOutputToContain('targets drfarzanashathi.com only')->assertFailed();
        }
        $this->assertDatabaseCount('doctor_profiles', 0);
        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_failed_database_import_rolls_back_public_changes_and_removes_only_new_files(): void
    {
        app(WebsiteImporter::class)->run();
        $profile = DoctorProfile::first()->getAttributes();
        $images = MediaAsset::count();
        $files = Storage::disk('public')->allFiles();
        $manifest = json_decode(file_get_contents(database_path('seeders/data/demo-reference.json')), true);
        $manifest['modules']['chamber'][0]['data']['schedules'][0]['opens_at'] = null;
        Storage::disk('local')->put('broken-reference.json', json_encode($manifest));
        config(['website.demo_manifest' => Storage::disk('local')->path('broken-reference.json')]);
        try {
            app(DemoWebsiteMirror::class)->run();
            $this->fail('An invalid schedule must reject the complete import.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('opens_at', $exception->getMessage());
        }
        $this->assertSame($profile, DoctorProfile::first()->getAttributes());
        $this->assertSame($images, MediaAsset::count());
        $this->assertSame($files, Storage::disk('public')->allFiles());
        $this->assertSame(3, Service::published()->count());
        $this->assertDatabaseMissing('website_imports', ['key' => DemoWebsiteMirror::MARKER]);
        $this->assertCount(1, Storage::disk('local')->files('demo-mirror-backups'));
    }

    public function test_existing_other_doctor_is_not_replaced(): void
    {
        DoctorProfile::factory()->create(['key' => 'primary', 'name' => 'Other doctor']);
        $this->artisan('website:match-demo')->expectsOutputToContain('another doctor')->assertFailed();
        $this->assertSame('Other doctor', DoctorProfile::first()->name);
        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_existing_demo_profile_gets_aaam_once_without_changing_other_content(): void
    {
        $this->artisan('website:match-demo')->assertSuccessful();
        $profile = DoctorProfile::firstOrFail();
        $credentials = ['Administrator edited qualification', 'DDV'];
        $profile->update(['credentials' => $credentials, 'summary' => 'Administrator edited biography']);
        $before = $profile->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_25_201632_add_aaam_membership_to_farzana_profile.php');

        $migration->up();
        $migration->up();
        $this->artisan('website:match-demo')->expectsOutputToContain('already applied')->assertSuccessful();

        $this->assertSame([...$credentials, 'Member of American Academy of Aesthetic Medicine (AAAM)'], $profile->fresh()->credentials);
        $this->assertSame(
            collect($before)->except(['credentials', 'updated_at'])->all(),
            collect($profile->fresh()->getAttributes())->except(['credentials', 'updated_at'])->all(),
        );
    }

    public function test_aaam_correction_preserves_existing_membership_variants_and_other_doctors(): void
    {
        $profile = DoctorProfile::factory()->create([
            'key' => 'primary',
            'name' => 'Dr. Farzana Rahman Shathi',
            'credentials' => ['Member of American Academy of Aesthetic Medicine ( AAAM)'],
        ]);
        $migration = require database_path('migrations/2026_11_25_201632_add_aaam_membership_to_farzana_profile.php');
        $before = $profile->fresh()->getAttributes();
        $migration->up();
        $this->assertSame($before, $profile->fresh()->getAttributes());

        $profile->update(['name' => 'Another doctor', 'credentials' => ['MBBS']]);
        $before = $profile->fresh()->getAttributes();
        $migration->up();
        $this->assertSame($before, $profile->fresh()->getAttributes());
    }
}
