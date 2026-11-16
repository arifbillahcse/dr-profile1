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

    public function test_imported_comments_use_bangla_and_keep_source_authors_and_actual_ratings(): void
    {
        $this->artisan('website:youtube-feedback')->expectsOutputToContain('Published 4 sourced feedback entries')->assertSuccessful();
        $this->assertSame(4, Review::approved()->count());
        $review = Review::where('name', '@SharifHossain-s2u')->firstOrFail();
        $this->assertSame('ম্যাডামের চিকিৎসা খুবই চমৎকার। আমি উনার থেকে ব্রণের চিকিৎসা নিচ্ছি, আশানুরূপ এখনই ভালো ফলাফল পাচ্ছি।', $review->message);
        $this->assertNull($review->rating);
        $this->assertNull($review->approved_by);
        $response = $this->get('/')->assertOk()->assertSee('What People')->assertDontSee('YouTube মন্তব্য')->assertDontSee('মূল মন্তব্য দেখুন')
            ->assertSee('@SharifHossain-s2u')->assertSee('@shihabchowdhury2820')->assertSee('চিকিৎসাটি খুব ভালো ছিল।')
            ->assertSee('@mahimsumi6509')->assertSee('উনি খুবই ভালো একজন ডাক্তার 🥰')->assertSee('Yeasif Ferdous')
            ->assertSee('সহযোগিতাপরায়ণ চিকিৎসক, ভালো মানুষ। রোগীদের আন্তরিকভাবে চিকিৎসা করেন এবং তাঁর ব্যবহার ভালো।')
            ->assertSee($review->message)->assertDontSee($review->youtubeCommentUrl())
            ->assertDontSee('Your experience matters')->assertDontSee('Sample review');
        $this->assertSame(1, substr_count($response->getContent(), 'aria-label="5 out of 5 stars"'));
        $this->assertSame(3, Review::where('source_type', 'youtube')->whereNull('rating')->count());
        $directoryReview = Review::where('name', 'Yeasif Ferdous')->firstOrFail();
        $this->assertSame(5, $directoryReview->rating);
        $this->assertSame('https://www.doctorbangladesh.com/dr-farzana-rahman-shathi/#comment-504', $directoryReview->feedbackSourceUrl());
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.reviews.index', ['status' => 'approved']))
            ->assertOk()->assertSee('Original feedback')->assertSee($review->youtubeCommentUrl())->assertSee($directoryReview->feedbackSourceUrl());
    }

    public function test_existing_english_feedback_is_translated_without_changing_names_ratings_or_moderation(): void
    {
        $this->artisan('website:youtube-feedback')->assertSuccessful();
        $youtube = Review::where('name', '@shihabchowdhury2820')->firstOrFail();
        $directory = Review::where('name', 'Yeasif Ferdous')->firstOrFail();
        $custom = Review::where('name', '@mahimsumi6509')->firstOrFail();
        $youtube->update(['message' => 'It’s was great treatment']);
        $directory->forceFill([
            'message' => 'Cooperative doctor,good human being,treat patients very solely,good behaviour.',
            'status' => ReviewStatus::Rejected, 'approved_at' => null,
        ])->save();
        $custom->update(['message' => 'Administrator-edited feedback']);

        $this->artisan('website:youtube-feedback')->expectsOutputToContain('translated 2 existing entries')->assertSuccessful();
        $this->assertSame('চিকিৎসাটি খুব ভালো ছিল।', $youtube->fresh()->message);
        $this->assertSame('@shihabchowdhury2820', $youtube->fresh()->name);
        $this->assertNull($youtube->fresh()->rating);
        $this->assertSame('সহযোগিতাপরায়ণ চিকিৎসক, ভালো মানুষ। রোগীদের আন্তরিকভাবে চিকিৎসা করেন এবং তাঁর ব্যবহার ভালো।', $directory->fresh()->message);
        $this->assertSame(ReviewStatus::Rejected, $directory->fresh()->status);
        $this->assertNull($directory->fresh()->approved_at);
        $this->assertSame(5, $directory->fresh()->rating);
        $this->assertSame('Yeasif Ferdous', $directory->fresh()->name);
        $this->assertSame('Administrator-edited feedback', $custom->fresh()->message);
        $this->artisan('website:youtube-feedback')->expectsOutputToContain('translated 0 existing entries')->assertSuccessful();
        $this->assertSame(4, Review::count());
    }

    public function test_repeat_import_preserves_rejection_archive_deletion_and_existing_patient_reviews(): void
    {
        $patient = Review::factory()->approved()->create(['message' => 'Existing patient feedback']);
        $this->artisan('website:youtube-feedback')->assertSuccessful();
        $comment = Review::where('source_type', 'youtube')->firstOrFail();
        $other = Review::where('source_type', 'youtube')->whereKeyNot($comment->id)->firstOrFail();
        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.reviews.update', $comment), ['status' => 'rejected'])->assertSessionHasNoErrors();
        $other->delete();
        $this->artisan('website:youtube-feedback')->expectsOutputToContain('Published 0 sourced feedback entries')->assertSuccessful();
        $this->assertSame(5, Review::withTrashed()->count());
        $this->assertSame(ReviewStatus::Rejected, $comment->fresh()->status);
        $this->assertTrue($other->fresh()->trashed());
        $this->get('/')->assertSee($patient->message)->assertDontSee($comment->message)->assertDontSee($other->message);
        $other->forceDelete();
        $this->artisan('website:youtube-feedback')->assertSuccessful();
        $this->assertSame(4, Review::withTrashed()->count());
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
        $this->assertNull($review->feedbackSourceUrl());
        $review->forceFill(['source_type' => 'doctor_bangladesh', 'source_url' => 'https://example.test/#comment-504'])->save();
        $this->assertNull($review->feedbackSourceUrl());
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
