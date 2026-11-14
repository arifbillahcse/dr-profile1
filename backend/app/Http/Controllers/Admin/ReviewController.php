<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateReviewRequest;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:all,pending,approved,rejected,archived']]);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? 'pending';
        $query = $status === 'archived' ? Review::onlyTrashed() : Review::query();
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('workplace', 'like', '%'.$search.'%')->orWhere('message', 'like', '%'.$search.'%');
            });
        }

        return view('admin.reviews.index', [
            'records' => $query->with('approver')->latest('id')->paginate(12)->withQueryString(),
            'search' => $search, 'status' => $status,
            'totals' => Review::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'archivedCount' => Review::onlyTrashed()->count(),
        ]);
    }

    public function update(ModerateReviewRequest $request, int $review): RedirectResponse
    {
        $status = ReviewStatus::from($request->validated('status'));
        DB::transaction(function () use ($review, $status, $request): void {
            $record = Review::query()->lockForUpdate()->findOrFail($review);
            if ($record->status === $status && ($status !== ReviewStatus::Approved || $record->approved_at !== null)) {
                return;
            }
            $record->forceFill([
                'status' => $status,
                'approved_at' => $status === ReviewStatus::Approved ? now() : null,
                'approved_by' => $status === ReviewStatus::Approved ? $request->user()->id : null,
            ])->save();
        });

        return to_route('admin.reviews.index')->with('status', 'Review marked '.$status->value.'.');
    }

    public function destroy(int $review): RedirectResponse
    {
        DB::transaction(function () use ($review): void {
            $record = Review::query()->lockForUpdate()->findOrFail($review);
            $record->forceFill(['status' => ReviewStatus::Pending, 'approved_at' => null, 'approved_by' => null])->save();
            $record->delete();
        });

        return to_route('admin.reviews.index')->with('status', 'Review archived and hidden from the website.');
    }

    public function restore(int $review): RedirectResponse
    {
        DB::transaction(function () use ($review): void {
            $record = Review::onlyTrashed()->lockForUpdate()->findOrFail($review);
            $record->forceFill(['status' => ReviewStatus::Pending, 'approved_at' => null, 'approved_by' => null])->save();
            $record->restore();
        });

        return to_route('admin.reviews.index')->with('status', 'Review restored as pending. Approve it to publish it again.');
    }

    public function forceDestroy(int $review): RedirectResponse
    {
        DB::transaction(fn () => Review::onlyTrashed()->lockForUpdate()->findOrFail($review)->forceDelete());

        return to_route('admin.reviews.index', ['status' => 'archived'])->with('status', 'Archived review permanently deleted.');
    }
}
