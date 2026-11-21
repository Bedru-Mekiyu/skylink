@extends('admin.layouts.master')

@section('title', 'Departments')
@section('page-title', 'Departments')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h2 class="h5 mb-0"><i class="fas fa-people-group me-2" aria-hidden="true"></i>About page department cards</h2>
        <div class="d-flex flex-wrap gap-2">
            <form method="GET" action="{{ route('admin.departments.index') }}" class="d-flex flex-nowrap gap-2" role="search">
                <label for="q" class="visually-hidden">Search departments</label>
                <input type="search" id="q" name="q" value="{{ $q }}" class="form-control" placeholder="Search name">
                <button class="btn btn-outline-secondary text-nowrap" type="submit">Search</button>
            </form>
            <a href="{{ route('admin.departments.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1" aria-hidden="true"></i>Add department</a>
        </div>
    </div>
    <div class="card-body p-0">
        <p class="small text-muted px-4 pt-3 mb-2">
            The About page shows the <strong>visible</strong> departments in this order, two per row (a last single card is centred; one column on phones).
            @if ($reorderable) Use the arrows to change the order. @else Clear the search to change the order. @endif
            If none is visible, the section is not shown.
        </p>
        <div class="table-wrapper">
            <table class="table table-hover align-middle mb-0 table-responsive-cards">
                <thead><tr><th scope="col" class="ps-4" style="width:6rem">Order</th><th scope="col">Department</th><th scope="col">Numbers</th><th scope="col">Status</th><th scope="col" class="text-end pe-4">Actions</th></tr></thead>
                <tbody>
                @forelse ($items as $d)
                    <tr>
                        <td data-label="Order" class="ps-4 text-nowrap">
                            @if ($reorderable)
                                @foreach (['up' => 'fa-arrow-up', 'down' => 'fa-arrow-down'] as $dir => $icon)
                                    <form action="{{ route('admin.departments.move', $d) }}" method="POST" class="d-inline">
                                        @csrf @method('PUT')<input type="hidden" name="dir" value="{{ $dir }}">
                                        <button type="submit" class="btn btn-sm btn-light" aria-label="Move {{ $d->name }} {{ $dir }}" style="min-width:44px;min-height:44px"><i class="fas {{ $icon }}" aria-hidden="true"></i></button>
                                    </form>
                                @endforeach
                            @else {{ $d->sort_order }} @endif
                        </td>
                        <td data-label="Department"><div class="fw-semibold"><i class="{{ $d->icon ?: 'fas fa-users' }} me-1 text-{{ $d->accent }}" aria-hidden="true"></i>{{ $d->name }}</div><div class="small text-muted">{{ $d->tagline }}</div></td>
                        <td data-label="Numbers" class="small">{{ collect($d->stats ?? [])->map(fn ($s) => ($s['n'] ?? '').' '.($s['l'] ?? ''))->implode(', ') ?: '–' }}</td>
                        <td data-label="Status">@if ($d->visible)<span class="badge text-bg-success">Visible</span>@else<span class="badge text-bg-secondary">Hidden</span>@endif</td>
                        <td data-label="Actions" class="pe-4">
                            <div class="action-buttons">
                                <a href="{{ route('admin.departments.edit', $d) }}" class="btn btn-sm btn-warning" aria-label="Edit {{ $d->name }}"><i class="fas fa-pencil-alt" aria-hidden="true"></i></a>
                                <form action="{{ route('admin.departments.toggle', $d) }}" method="POST" @if ($d->visible) data-sky-confirm="It disappears from the About page but stays in this list, so you can show it again." data-sky-title="Hide department?" data-sky-ok="Hide" @endif>
                                    @csrf @method('PUT')
                                    <button type="submit" class="btn btn-sm {{ $d->visible ? 'btn-outline-secondary' : 'btn-outline-success' }}" aria-label="{{ $d->visible ? 'Hide' : 'Show' }} {{ $d->name }}"><i class="fas {{ $d->visible ? 'fa-eye-slash' : 'fa-eye' }}" aria-hidden="true"></i></button>
                                </form>
                                <form action="{{ route('admin.departments.destroy', $d) }}" method="POST" data-sky-confirm="Delete the department &quot;{{ $d->name }}&quot;? Its card, numbers and any picture you uploaded are removed for good. (To keep it, use Hide instead.)" data-sky-title="Delete department?" data-sky-variant="danger" data-sky-ok="Delete">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $d->name }}" style="min-width:44px;min-height:44px"><i class="fas fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-5"><i class="fas fa-people-group fa-3x text-muted mb-3" aria-hidden="true"></i><p class="text-muted mb-1">{{ $q !== '' ? 'No department matches your search.' : 'No departments yet. The section is hidden on the About page until you add one.' }}</p><a href="{{ route('admin.departments.create') }}">Add a department</a></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($items->hasPages())<div class="card-footer">{{ $items->onEachSide(1)->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
