@extends('admin.layouts.master')

@section('title', 'Testimonials')
@section('page-title', 'Testimonials')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h2 class="h5 mb-0"><i class="fas fa-comment-dots me-2" aria-hidden="true"></i>What our clients say</h2>
        <div class="d-flex flex-wrap gap-2">
            <form method="GET" action="{{ route('admin.testimonials.index') }}" class="d-flex flex-nowrap gap-2" role="search">
                <label for="q" class="visually-hidden">Search testimonials</label>
                <input type="search" id="q" name="q" value="{{ $q }}" class="form-control" placeholder="Search name or quote">
                <button class="btn btn-outline-secondary text-nowrap" type="submit">Search</button>
            </form>
            <a href="{{ route('admin.testimonials.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1" aria-hidden="true"></i>Add testimonial</a>
        </div>
    </div>
    <div class="card-body p-0">
        <p class="small text-muted px-4 pt-3 mb-2">
            The strip on the homepage and the Services page shows the <strong>visible</strong> testimonials in this order.
            @if ($reorderable) Use the arrows to change the order. @else Clear the search to change the order. @endif
            Hide a testimonial to keep it here without showing it. If none is visible, the section is not shown.
        </p>
        <div class="table-wrapper">
            <table class="table table-hover align-middle mb-0 table-responsive-cards">
                <thead>
                    <tr>
                        <th scope="col" class="ps-4" style="width:6rem">Order</th>
                        <th scope="col">Client</th>
                        <th scope="col">Quote</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($items as $t)
                    <tr>
                        <td data-label="Order" class="ps-4 text-nowrap">
                            @if ($reorderable)
                                @foreach (['up' => 'fa-arrow-up', 'down' => 'fa-arrow-down'] as $dir => $icon)
                                    <form action="{{ route('admin.testimonials.move', $t) }}" method="POST" class="d-inline">
                                        @csrf @method('PUT')<input type="hidden" name="dir" value="{{ $dir }}">
                                        <button type="submit" class="btn btn-sm btn-light" aria-label="Move {{ $t->name }} {{ $dir }}" style="min-width:44px;min-height:44px"><i class="fas {{ $icon }}" aria-hidden="true"></i></button>
                                    </form>
                                @endforeach
                            @else
                                {{ $t->sort_order }}
                            @endif
                        </td>
                        <td data-label="Client">
                            <div class="d-flex align-items-center gap-2">
                                @if ($t->photoUrl())
                                    <img src="{{ $t->photoUrl() }}" alt="" width="44" height="44" loading="lazy" style="width:44px;height:44px;border-radius:50%;object-fit:cover">
                                @else
                                    <span class="d-inline-grid text-center fw-bold bg-secondary-subtle rounded-circle" style="width:44px;height:44px;line-height:44px" aria-hidden="true">{{ $t->initials() }}</span>
                                @endif
                                <div><div class="fw-semibold">{{ $t->name }}</div><div class="small text-muted">{{ $t->role }}</div></div>
                            </div>
                        </td>
                        <td data-label="Quote" class="small" style="max-width:26rem">{{ \Illuminate\Support\Str::limit($t->quote_en ?: $t->quote_am, 110) }}</td>
                        <td data-label="Status">@if ($t->visible)<span class="badge text-bg-success">Visible</span>@else<span class="badge text-bg-secondary">Hidden</span>@endif</td>
                        <td data-label="Actions" class="pe-4">
                            <div class="action-buttons">
                                <a href="{{ route('admin.testimonials.edit', $t) }}" class="btn btn-sm btn-warning" aria-label="Edit {{ $t->name }}"><i class="fas fa-pencil-alt" aria-hidden="true"></i></a>
                                <form action="{{ route('admin.testimonials.toggle', $t) }}" method="POST" @if ($t->visible) data-sky-confirm="It disappears from the site but stays in this list, so you can show it again." data-sky-title="Hide testimonial?" data-sky-ok="Hide" @endif>
                                    @csrf @method('PUT')
                                    <button type="submit" class="btn btn-sm {{ $t->visible ? 'btn-outline-secondary' : 'btn-outline-success' }}" aria-label="{{ $t->visible ? 'Hide' : 'Show' }} {{ $t->name }}"><i class="fas {{ $t->visible ? 'fa-eye-slash' : 'fa-eye' }}" aria-hidden="true"></i></button>
                                </form>
                                <form action="{{ route('admin.testimonials.destroy', $t) }}" method="POST" data-sky-confirm="Delete the testimonial from &quot;{{ $t->name }}&quot;? The quote and the photo files are removed for good. (To keep it, use Hide instead.)" data-sky-title="Delete testimonial?" data-sky-variant="danger" data-sky-ok="Delete">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $t->name }}" style="min-width:44px;min-height:44px"><i class="fas fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-5">
                        <i class="fas fa-comment-dots fa-3x text-muted mb-3" aria-hidden="true"></i>
                        <p class="text-muted mb-1">{{ $q !== '' ? 'No testimonial matches your search.' : 'No testimonials yet. The section is hidden on the site until you add one.' }}</p>
                        <a href="{{ route('admin.testimonials.create') }}">Add a testimonial</a>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($items->hasPages())<div class="card-footer">{{ $items->onEachSide(1)->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
