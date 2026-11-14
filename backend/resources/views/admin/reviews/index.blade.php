@extends('admin.layout')
@section('title', 'Reviews')
@section('content')
<section class="dashboard-heading"><h1><i class="bi bi-chat-square-quote" aria-hidden="true"></i> Reviews</h1><p>Read visitor feedback and approve it for the public website.</p></section>
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="content-panel">
    <div class="d-flex flex-wrap gap-2 mb-4" aria-label="Review totals">
        @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $key=>$label)<a class="btn btn-sm {{ $status === $key ? 'btn-success' : 'btn-outline-secondary' }}" href="{{ route('admin.reviews.index', ['status'=>$key]) }}">{{ $label }} ({{ $totals[$key] ?? 0 }})</a>@endforeach
        <a class="btn btn-sm {{ $status === 'archived' ? 'btn-success' : 'btn-outline-secondary' }}" href="{{ route('admin.reviews.index', ['status'=>'archived']) }}">Archived ({{ $archivedCount }})</a>
    </div>
    <form method="GET" action="{{ route('admin.reviews.index') }}" class="content-filters mb-4">
        <div><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" maxlength="100" value="{{ $search }}" placeholder="Name, location or review"></div>
        <div><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status">@foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','all'=>'All active reviews','archived'=>'Archived'] as $key=>$label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach</select></div>
        <button type="submit" class="btn btn-outline-success">Apply filters</button>
        @if($search !== '' || $status !== 'pending')<a class="btn btn-light" href="{{ route('admin.reviews.index') }}">Clear</a>@endif
    </form>
    <p class="small text-secondary">Only approved reviews appear publicly. Restoring an archived review returns it to Pending. Visitor wording is kept as submitted.</p>
    <div class="table-responsive"><table class="table align-middle content-table review-admin-table">
        <thead><tr><th scope="col">Visitor</th><th scope="col">Review</th><th scope="col">Status</th><th scope="col" class="text-end">Actions</th></tr></thead>
        <tbody>@forelse($records as $item)<tr>
            <td><strong>{{ $item->name }}</strong>@if($item->workplace)<span class="d-block small text-secondary">{{ $item->workplace }}</span>@endif<span class="d-block small text-secondary">#{{ $item->id }} · {{ $item->created_at?->format('d M Y H:i') }}</span>@if($item->rating)<span class="small">Rating: {{ $item->rating }}/5</span>@endif</td>
            <td><p class="review-admin-message mb-0">{{ $item->message }}</p></td>
            <td><span class="badge {{ $item->trashed() ? 'text-bg-secondary' : ($item->status->value === 'approved' ? 'text-bg-success' : ($item->status->value === 'rejected' ? 'text-bg-danger' : 'text-bg-warning')) }}">{{ $item->trashed() ? 'Archived' : ucfirst($item->status->value) }}</span>@if($item->approved_at)<small class="d-block text-secondary mt-1">Approved {{ $item->approved_at->format('d M Y H:i') }}@if($item->approver) by {{ $item->approver->name }}@endif</small>@endif</td>
            <td><div class="content-actions">
                @if($item->trashed())
                    <form method="POST" action="{{ route('admin.reviews.restore', $item->id) }}">@csrf<button class="btn btn-sm btn-outline-success" type="submit">Restore</button></form>
                    <form method="POST" action="{{ route('admin.reviews.force-destroy', $item->id) }}" data-confirm="Permanently delete this archived review? It cannot be restored.">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Delete permanently</button></form>
                @else
                    @foreach(['approved'=>'Approve','rejected'=>'Reject','pending'=>'Mark pending'] as $value=>$label)
                        @if($item->status->value !== $value)<form method="POST" action="{{ route('admin.reviews.update', $item->id) }}" @if($value === 'approved') data-confirm="Publish this visitor’s name, location, rating and review on the website?" @endif>@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $value }}"><button class="btn btn-sm {{ $value === 'approved' ? 'btn-success' : 'btn-outline-secondary' }}" type="submit">{{ $label }}</button></form>@endif
                    @endforeach
                    <form method="POST" action="{{ route('admin.reviews.destroy', $item->id) }}" data-confirm="Archive this review? It will be hidden and can be restored later.">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Archive</button></form>
                @endif
            </div></td>
        </tr>@empty<tr><td colspan="4"><div class="content-empty"><i class="bi bi-chat-square-quote" aria-hidden="true"></i><h2>No matching reviews</h2><p>New visitor submissions appear under Pending. Try another status or search.</p></div></td></tr>@endforelse</tbody>
    </table></div>
    {{ $records->links() }}
</section>
@endsection
