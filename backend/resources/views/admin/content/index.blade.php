@extends('admin.layout')
@section('title', $definition['heading'])
@section('content')
<section class="dashboard-heading settings-heading"><div><h1><i class="bi bi-{{ $definition['icon'] }}" aria-hidden="true"></i> {{ $definition['heading'] }}</h1><p>Manage content, visibility and display order.</p></div><a class="btn btn-success" href="{{ route('admin.content.create', $moduleKey) }}">Add {{ $definition['singular'] }}</a></section>
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="content-panel">
    <form method="GET" action="{{ route('admin.content.index', $moduleKey) }}" class="content-filters mb-4">
        <div><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Search {{ strtolower($definition['heading']) }}"></div>
        <div><label class="form-label" for="status">Visibility</label><select class="form-select" id="status" name="status">@foreach(['all'=>'All content','visible'=>'Visible','hidden'=>'Hidden','archived'=>'Archived'] as $key=>$label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach</select></div>
        <button class="btn btn-outline-success" type="submit">Apply filters</button>
        @if($search !== '' || $status !== 'all')<a class="btn btn-light" href="{{ route('admin.content.index', $moduleKey) }}">Clear</a>@endif
    </form>
    <p class="small text-secondary">Lower display-order numbers appear first. Archived content can be restored as hidden.</p>
    <div class="table-responsive">
        <table class="table align-middle content-table">
            <thead><tr><th scope="col">Order</th><th scope="col">Content</th><th scope="col">Visibility</th><th scope="col">Updated</th><th scope="col" class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($records as $item)
                @php($itemTitle = $item->{$definition['title_field']} ?: ucfirst($definition['singular']).' #'.$item->id)
                <tr>
                    <td>{{ $item->sort_order }}</td>
                    <td><div class="content-item-title">
                        @if(isset($definition['image_column']))
                            @php($itemImage = $definition['image_column'] === 'thumbnail_image_id' ? $item->thumbnail : $item->image)
                            @if($itemImage)<img src="{{ $itemImage->url() }}" alt="" class="content-thumb" loading="lazy">@endif
                        @endif
                        <div><strong>{{ Str::limit($itemTitle, 110) }}</strong><span class="d-block small text-secondary">#{{ $item->id }}</span></div>
                    </div></td>
                    <td><span class="badge {{ $item->trashed() ? 'text-bg-secondary' : ($item->is_active ? 'text-bg-success' : 'text-bg-light border') }}">{{ $item->trashed() ? 'Archived' : ($item->is_active ? 'Visible' : 'Hidden') }}</span></td>
                    <td class="small text-secondary text-nowrap">{{ $item->updated_at?->format('d M Y') }}</td>
                    <td><div class="content-actions">
                        @if($item->trashed())
                            <form method="POST" action="{{ route('admin.content.restore', [$moduleKey, $item->id]) }}">@csrf<button class="btn btn-sm btn-outline-success" type="submit">Restore</button></form>
                            <form method="POST" action="{{ route('admin.content.force-destroy', [$moduleKey, $item->id]) }}" data-confirm="Permanently delete this archived content? It cannot be restored. Media files will be kept.">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Delete permanently</button></form>
                        @else
                            <a class="btn btn-sm btn-outline-success" href="{{ route('admin.content.edit', [$moduleKey, $item->id]) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.content.visibility', [$moduleKey, $item->id]) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $item->is_active ? '0' : '1' }}"><button class="btn btn-sm btn-outline-secondary" type="submit">{{ $item->is_active ? 'Hide' : 'Show' }}</button></form>
                            <form method="POST" action="{{ route('admin.content.destroy', [$moduleKey, $item->id]) }}" data-confirm="Archive this content? It will become hidden and can be restored later.">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Archive</button></form>
                        @endif
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="content-empty"><i class="bi bi-inbox" aria-hidden="true"></i><h2>No {{ strtolower($definition['heading']) }} found</h2><p>{{ $status === 'archived' ? 'Archived records will appear here.' : 'Add content or try another search or visibility filter.' }}</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $records->links() }}
</section>
@endsection
