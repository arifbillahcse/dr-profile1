<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;

class ReviewSubmissionController extends Controller
{
    public function __invoke(StoreReviewRequest $request): RedirectResponse
    {
        Review::create($request->safe()->only(['name', 'workplace', 'message', 'rating']));

        return redirect()->to(route('public.home').'#reviews')->with('review_submitted', true);
    }
}
