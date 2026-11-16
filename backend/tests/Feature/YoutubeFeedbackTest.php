<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YoutubeFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_imported_comments_keep_original_authors_words_links_and_no_invented_ratings(): void
    {
        $this->artisan('website:youtube-feedback')->expectsOutputToContain('Published 2 sourced YouTube comments')->assertSuccessful();
        $this->assertSame(2, Review::approved()->count());
        $review = Review::where('name', '@SharifHossain-s2u')->firstOrFail();
        $this->assertSame('ম্যাডামের চিকিৎসা খুবই চমৎকার। আমি উনার থেকে ব্রণের চিকিৎসা নিচ্ছি, আশানুরূপ এখনই ভালো ফলাফল পাচ্ছি।', $review->message);
        $this->assertNull($review->rating);
        $this->assertNull($review->approved_by);
        $response = $this->get('/')->assertOk()->assertSee('What People')->assertDontSee('YouTube মন্তব্য')->assertSee('মূল মন্তব্য দেখুন')
            ->assertSee('@SharifHossain-s2u')->assertSee('@shihabchowdhury2820')->assertSee('It’s was great treatment')
            ->assertSee($review->message)->assertSee($review->youtubeCommentUrl())
            ->assertDontSee('Your experience matters')->assertDontSee('Sample review')->assertDontSee('out of 5 stars');
        $this->assertSame(2, substr_count($response->getContent(), 'class="review-source-link"'));
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.reviews.index', ['status' => 'approved']))
            ->assertOk()->assertSee('YouTube comment')->assertSee($review->youtubeCommentUrl());
    }

    public function test_repeat_import_preserves_rejection_archive_deletion_and_existing_patient_reviews(): void
    {
        $patient = Review::factory()->approved()->create(['message' => 'Existing patient feedback']);
        $this->artisan('website:youtube-feedback')->assertSuccessful();
        $comment = Review::where('source_type', 'youtube')->firstOrFail();
        $other = Review::where('source_type', 'youtube')->whereKeyNot($comment->id)->firstOrFail();
        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.reviews.update', $comment), ['status' => 'rejected'])->assertSessionHasNoErrors();
        $other->delete();
        $this->artisan('website:youtube-feedback')->expectsOutputToContain('Published 0 sourced YouTube comments')->assertSuccessful();
        $this->assertSame(3, Review::withTrashed()->count());
        $this->assertSame(ReviewStatus::Rejected, $comment->fresh()->status);
        $this->assertTrue($other->fresh()->trashed());
        $this->get('/')->assertSee($patient->message)->assertDontSee($comment->message)->assertDontSee($other->message);
        $other->forceDelete();
        $this->artisan('website:youtube-feedback')->assertSuccessful();
        $this->assertSame(2, Review::withTrashed()->count());
    }

    public function test_source_links_are_restricted_to_youtube_comment_permalinks(): void
    {
        $review = Review::factory()->approved()->create(['source_type' => 'youtube', 'source_url' => 'javascript:alert(1)']);
        $this->assertNull($review->youtubeCommentUrl());
        $this->get('/')->assertOk()->assertDontSee('href="javascript:', false)->assertDontSee('class="review-source-link"', false);
        $review->forceFill(['source_url' => 'https://www.youtube.com/watch?v=scWIJ8pmROg&lc=UgyWORg-pofWtNQXf5N4AaABAg'])->save();
        $this->assertSame($review->source_url, $review->youtubeCommentUrl());
        $review->forceFill(['source_type' => null])->save();
        $this->assertNull($review->youtubeCommentUrl());
    }

    public function test_visitor_submissions_cannot_claim_a_youtube_source(): void
    {
        $this->post(route('public.reviews.store'), [
            'name' => 'Visitor', 'message' => 'My own submitted feedback.', 'rating' => 4, 'consent' => 1,
            'source_type' => 'youtube', 'source_url' => 'https://www.youtube.com/watch?v=scWIJ8pmROg&lc=UgyWORg-pofWtNQXf5N4AaABAg',
        ])->assertSessionHasNoErrors();
        $review = Review::firstOrFail();
        $this->assertNull($review->source_type);
        $this->assertNull($review->source_url);
        $this->assertSame(ReviewStatus::Pending, $review->status);
    }
}
