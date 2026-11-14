<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Models\Chamber;
use App\Models\ChamberSchedule;
use App\Models\DoctorProfile;
use App\Models\Faq;
use App\Models\GalleryImage;
use App\Models\MediaAsset;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_content_modules_can_persist_records_in_the_database(): void
    {
        foreach (config('content.modules') as $modelClass) {
            $record = $modelClass::factory()->create();
            $this->assertModelExists($record);
            $this->assertNotNull($record->fresh());
        }
    }

    public function test_publication_excludes_drafts_and_deleted_content_and_has_stable_ordering(): void
    {
        Service::factory()->create(['sort_order' => 0]);
        $last = Service::factory()->published()->create(['sort_order' => 20]);
        $first = Service::factory()->published()->create(['sort_order' => 10]);
        $second = Service::factory()->published()->create(['sort_order' => 10]);
        $deleted = Service::factory()->published()->create(['sort_order' => 1]);
        $deleted->delete();

        $this->assertSame([$first->id, $second->id, $last->id], Service::published()->ordered()->pluck('id')->all());
        $deleted->restore();
        $this->assertSame($deleted->id, Service::published()->ordered()->firstOrFail()->id);
    }

    public function test_only_explicitly_approved_reviews_are_public(): void
    {
        $pending = Review::factory()->create();
        Review::factory()->create(['status' => ReviewStatus::Rejected, 'approved_at' => now()]);
        Review::factory()->create(['status' => ReviewStatus::Approved, 'approved_at' => null]);
        $approved = Review::factory()->approved()->create();
        $deleted = Review::factory()->approved()->create();
        $deleted->delete();

        $this->assertSame(ReviewStatus::Pending, $pending->fresh()->status);
        $this->assertSame([$approved->id], Review::approved()->pluck('id')->all());
        $this->assertTrue($approved->approver->is_admin);
    }

    public function test_visitor_fields_cannot_approve_a_review(): void
    {
        $review = Review::create([
            'name' => 'Visitor',
            'message' => 'A review awaiting moderation.',
            'status' => ReviewStatus::Approved,
            'approved_at' => now(),
        ])->fresh();

        $this->assertSame(ReviewStatus::Pending, $review->status);
        $this->assertNull($review->approved_at);
        $this->assertSame(0, Review::approved()->count());
    }

    public function test_media_relationships_keep_content_when_an_asset_or_uploader_is_removed(): void
    {
        $uploader = User::factory()->admin()->create();
        $media = MediaAsset::factory()->for($uploader, 'uploader')->create();
        $gallery = GalleryImage::factory()->for($media, 'image')->create();

        $this->assertTrue($gallery->image->is($media));
        $uploader->delete();
        $this->assertNull($media->fresh()->uploaded_by);
        $media->delete();

        $this->assertNull($gallery->fresh()->image_id);
        $this->assertModelExists($gallery);
    }

    public function test_chamber_soft_deletion_preserves_schedules_and_permanent_deletion_cascades(): void
    {
        $chamber = Chamber::factory()->published()->create();
        $schedule = ChamberSchedule::factory()->for($chamber)->create();
        $this->assertTrue($schedule->chamber->is($chamber));

        $chamber->delete();
        $this->assertModelExists($schedule);
        $chamber->restore();
        $this->assertSame(1, $chamber->schedules()->count());
        $chamber->forceDelete();
        $this->assertModelMissing($schedule);
    }

    public function test_bangla_content_and_structured_credentials_round_trip(): void
    {
        $faq = Faq::factory()->create(['question' => 'অ্যাপয়েন্টমেন্ট বুক করার নিয়ম কী?', 'answer' => 'চেম্বারে ফোন করে সময় নিশ্চিত করুন।']);
        $credentials = ['এমবিবিএস', 'FCPS: Feto-Maternal Medicine'];
        $profile = DoctorProfile::factory()->create(['credentials' => $credentials]);

        $this->assertSame('চেম্বারে ফোন করে সময় নিশ্চিত করুন।', $faq->fresh()->answer);
        $this->assertSame($credentials, $profile->fresh()->credentials);
    }
}
