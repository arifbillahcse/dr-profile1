<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function submission(array $changes = []): array
    {
        return array_replace(['name' => 'Visitor name', 'workplace' => 'Dhaka', 'message' => 'Helpful and attentive consultation.', 'rating' => 4, 'consent' => 1, 'contact_fax' => ''], $changes);
    }

    public function test_guest_submission_is_pending_and_cannot_set_moderation_fields(): void
    {
        $this->post(route('public.reviews.store'), $this->submission(['status' => 'approved', 'approved_at' => now()->toDateTimeString(), 'approved_by' => 999, 'deleted_at' => now()->toDateTimeString()]))
            ->assertRedirect(route('public.home').'#reviews')->assertSessionHas('review_submitted', true)->assertSessionHasNoErrors();
        $review = Review::firstOrFail();
        $this->assertSame(ReviewStatus::Pending, $review->status);
        $this->assertNull($review->approved_by);
        $this->assertNull($review->approved_at);
        $this->assertNull($review->deleted_at);
        $this->assertSame(4, $review->rating);
        $this->get('/')->assertSee('awaiting approval')->assertDontSee('Helpful and attentive consultation.');
        $this->assertDatabaseCount('users', 0);
    }

    public static function invalidSubmissions(): array
    {
        return [
            'missing name' => ['name', ''], 'name too long' => ['name', str_repeat('a', 121)],
            'array name' => ['name', ['bad']], 'location too long' => ['workplace', str_repeat('a', 161)],
            'missing message' => ['message', ''], 'short message' => ['message', 'short'],
            'long message' => ['message', str_repeat('a', 3001)],
            'low rating' => ['rating', 0], 'high rating' => ['rating', 6],
            'fractional rating' => ['rating', 2.5], 'missing rating' => ['rating', null],
            'no consent' => ['consent', null], 'spam trap' => ['contact_fax', 'spam.example'],
        ];
    }

    #[DataProvider('invalidSubmissions')]
    public function test_invalid_or_spam_submissions_do_not_create_reviews(string $field, mixed $value): void
    {
        $this->postJson(route('public.reviews.store'), $this->submission([$field => $value]))->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_validation_returns_to_form_with_named_errors_and_preserves_input(): void
    {
        $this->followingRedirects()->post(route('public.reviews.store'), $this->submission(['message' => 'short']))
            ->assertOk()->assertSee('data-review-errors', false)->assertSee('Visitor name')->assertSee('short');
    }

    public function test_bangla_and_optional_location_are_supported_and_visitor_markup_is_escaped(): void
    {
        $this->post(route('public.reviews.store'), $this->submission(['name' => 'রোগীর অভিজ্ঞতা', 'workplace' => null, 'message' => 'চিকিৎসকের পরামর্শ সহায়ক ছিল। <img src=x onerror=alert(1)>']))->assertSessionHasNoErrors();
        $review = Review::firstOrFail();
        $this->assertNull($review->workplace);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.reviews.index'))->assertSee('রোগীর অভিজ্ঞতা')->assertDontSee('<img src=x onerror=alert(1)>', false);
        $this->patch(route('admin.reviews.update', $review), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('চিকিৎসকের পরামর্শ সহায়ক ছিল।')->assertSee('<img src=x onerror=alert(1)>')->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_public_submission_is_rate_limited_per_ip_and_recovers_after_an_hour(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('public.reviews.store'), $this->submission())->assertRedirect();
        }
        $this->post(route('public.reviews.store'), $this->submission())->assertTooManyRequests()->assertHeader('Retry-After');
        $this->assertDatabaseCount('reviews', 5);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.22'])->post(route('public.reviews.store'), $this->submission())->assertRedirect();
        $this->travel(61)->minutes();
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->post(route('public.reviews.store'), $this->submission())->assertRedirect();
        $this->assertDatabaseCount('reviews', 7);
    }

    public function test_public_and_admin_mutations_require_csrf_tokens(): void
    {
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->post(route('public.reviews.store'), $this->submission())->assertStatus(419);
        $this->withSession(['_token' => 'known-test-token'])->post(route('public.reviews.store'), [...$this->submission(), '_token' => 'known-test-token'])->assertRedirect();
        $review = Review::firstOrFail();
        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.reviews.update', $review), ['status' => 'approved'])->assertStatus(419);
        $this->assertSame(ReviewStatus::Pending, $review->fresh()->status);
    }

    public function test_guests_and_non_administrators_cannot_access_any_moderation_action(): void
    {
        $review = Review::factory()->create();
        $routes = [['get', route('admin.reviews.index')], ['patch', route('admin.reviews.update', $review)], ['delete', route('admin.reviews.destroy', $review)], ['post', route('admin.reviews.restore', $review)], ['delete', route('admin.reviews.force-destroy', $review)]];
        foreach ($routes as [$method, $url]) {
            $this->$method($url, ['status' => 'approved'])->assertRedirect(route('admin.login'));
        }
        $this->actingAs(User::factory()->create());
        foreach ($routes as [$method, $url]) {
            $this->$method($url, ['status' => 'approved'])->assertForbidden();
        }
        $this->assertSame(ReviewStatus::Pending, $review->fresh()->status);
    }

    public function test_approval_publication_rejection_and_reapproval_preserve_visitor_words(): void
    {
        $admin = User::factory()->admin()->create();
        $review = Review::factory()->create(['message' => 'Visitor feedback to moderate', 'rating' => 2]);
        $this->actingAs($admin);
        $this->patch(route('admin.reviews.update', $review), ['status' => 'approved', 'message' => 'Changed by request'])->assertRedirect(route('admin.reviews.index'));
        $review->refresh();
        $this->assertSame(ReviewStatus::Approved, $review->status);
        $this->assertSame($admin->id, $review->approved_by);
        $this->assertNotNull($review->approved_at);
        $this->assertSame('Visitor feedback to moderate', $review->message);
        $this->get('/')->assertSee('Visitor feedback to moderate');
        $approvedAt = $review->approved_at->toDateTimeString();
        $this->travel(2)->minutes();
        $this->patch(route('admin.reviews.update', $review), ['status' => 'approved']);
        $this->assertSame($approvedAt, $review->fresh()->approved_at->toDateTimeString());
        foreach (['rejected', 'pending'] as $status) {
            $this->patch(route('admin.reviews.update', $review), ['status' => $status])->assertSessionHasNoErrors();
            $this->assertNull($review->fresh()->approved_at);
            $this->assertNull($review->fresh()->approved_by);
            $this->get('/')->assertDontSee('Visitor feedback to moderate');
        }
        $this->patch(route('admin.reviews.update', $review), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Visitor feedback to moderate');
    }

    public function test_archive_restore_and_permanent_delete_never_republish_automatically(): void
    {
        $review = Review::factory()->approved()->create(['message' => 'Archived visitor feedback']);
        $this->actingAs(User::factory()->admin()->create());
        $this->delete(route('admin.reviews.force-destroy', $review))->assertNotFound();
        $this->post(route('admin.reviews.restore', $review))->assertNotFound();
        $this->delete(route('admin.reviews.destroy', $review))->assertRedirect();
        $this->assertSoftDeleted($review);
        $this->get('/')->assertDontSee('Archived visitor feedback');
        $this->patch(route('admin.reviews.update', $review), ['status' => 'approved'])->assertNotFound();
        $this->post(route('admin.reviews.restore', $review))->assertRedirect();
        $this->assertNotSoftDeleted($review);
        $this->assertSame(ReviewStatus::Pending, $review->fresh()->status);
        $this->assertNull($review->fresh()->approved_at);
        $this->assertNull($review->fresh()->approved_by);
        $this->get('/')->assertDontSee('Archived visitor feedback');
        $this->delete(route('admin.reviews.destroy', $review));
        $this->delete(route('admin.reviews.force-destroy', $review))->assertRedirect();
        $this->assertModelMissing($review);
        $this->delete(route('admin.reviews.force-destroy', $review))->assertNotFound();
    }

    public function test_review_queue_supports_status_search_counts_pagination_and_private_responses(): void
    {
        Review::factory()->count(13)->create(['name' => 'Pending visitor']);
        Review::factory()->approved()->create(['name' => 'Approved visitor', 'message' => 'Find this unique feedback']);
        Review::factory()->state(['status' => ReviewStatus::Rejected])->create(['name' => 'Rejected visitor']);
        Review::factory()->create(['name' => 'Archived visitor'])->delete();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.reviews.index'))->assertOk()->assertSee('Pending (13)')->assertSee('Approved (1)')->assertSee('Rejected (1)')->assertSee('Archived (1)')
            ->assertViewHas('records', fn ($records): bool => $records->count() === 12 && $records->lastPage() === 2)
            ->assertDontSee('Approved visitor')->assertDontSee('Archived visitor')->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('admin.reviews.index', ['status' => 'all', 'search' => 'unique feedback']))->assertSee('Approved visitor')->assertDontSee('Pending visitor');
        $this->get(route('admin.reviews.index', ['status' => 'archived']))->assertSee('Archived visitor')->assertDontSee('Approved visitor');
        $this->get(route('admin.modules.show', 'review'))->assertRedirect(route('admin.reviews.index'));
        $this->get(route('admin.reviews.index', ['search' => ['bad']]))->assertSessionHasErrors('search');
        $review = Review::first();
        $this->patch(route('admin.reviews.update', $review), ['status' => 'invalid'])->assertSessionHasErrors('status');
    }
}
