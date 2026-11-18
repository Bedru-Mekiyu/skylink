@extends('admin.layouts.master')

@section('title', 'News List')
@section('page-title', 'Manage News')

@php
    $badges = [
        'draft' => ['Draft', 'text-bg-secondary'],
        'scheduled' => ['Scheduled', 'text-bg-warning'],
        'published' => ['Published', 'text-bg-success'],
    ];
    $tz = config('news.admin_timezone');
@endphp

@section('content')
<div class="card news-list-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h5 mb-0"><i class="fas fa-newspaper me-2" aria-hidden="true"></i>News articles <span class="text-muted fw-normal small">({{ $news->total() }})</span></h2>
        <a href="{{ route('admin.news.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2" aria-hidden="true"></i>Create new
        </a>
    </div>

    <div class="card-body border-bottom">
        <form method="GET" action="{{ route('admin.news.index') }}" class="row g-2 align-items-end" role="search">
            <div class="col-12 col-md-6">
                <label for="q" class="form-label small mb-1">Search title or address</label>
                <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" maxlength="100" placeholder="e.g. Minber, Windows, ዜና">
            </div>
            <div class="col-7 col-md-3">
                <label for="statusFilter" class="form-label small mb-1">Status</label>
                <select id="statusFilter" name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($badges as $value => [$label])
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                    <option value="hidden-duplicates" @selected($hiddenView)>Hidden duplicates ({{ $hiddenCount }})</option>
                </select>
            </div>
            <div class="col-5 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary flex-fill"><i class="fas fa-search me-1" aria-hidden="true"></i>Search</button>
                @if ($search !== '' || $status)
                    <a href="{{ route('admin.news.index') }}" class="btn btn-light" title="Clear search"><i class="fas fa-times" aria-hidden="true"></i><span class="visually-hidden">Clear search</span></a>
                @endif
            </div>
        </form>
    </div>

    @if ($duplicateCheck)
        <div class="card-body border-bottom py-2 small d-flex flex-wrap align-items-center gap-2 {{ $duplicateCheck['pending'] ? 'bg-warning-subtle' : 'bg-light' }}" role="status">
            <i class="fas fa-clone" aria-hidden="true"></i>
            <span>
                Duplicate check ({{ \Illuminate\Support\Carbon::parse($duplicateCheck['generated_at'])->setTimezone($tz)->format('M j, H:i') }}):
                @if ($duplicateCheck['pending'])
                    <strong>{{ $duplicateCheck['pending'] }} article(s) could be hidden as duplicates.</strong> Nothing is changed automatically.
                @else
                    no new duplicates.
                @endif
            </span>
            <a href="{{ route('admin.news.duplicates-report') }}">View report</a>
        </div>
    @endif

    @if ($hiddenView)
        <div class="card-body border-bottom py-2 small text-muted">
            Hidden duplicates are not shown on the site; their addresses forward visitors to the article that was kept. <strong>Restore</strong> shows an article again.
        </div>
    @endif

    <div class="card-body p-0">
        <div class="table-wrapper">
            <table class="table table-hover align-middle mb-0 table-responsive-cards">
                <caption class="visually-hidden">News articles, newest first. Drafts are listed first.</caption>
                <thead>
                    <tr>
                        <th scope="col" class="ps-4">Title</th>
                        <th scope="col">Status</th>
                        <th scope="col">Language</th>
                        <th scope="col">Category</th>
                        <th scope="col">Date</th>
                        <th scope="col" class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($news as $item)
                        @php
                            $isHidden = $item->trashed();
                            $state = $isHidden ? 'hidden' : ($item->isVisible() ? 'published' : $item->status);
                            [$badgeLabel, $badgeClass] = $isHidden ? ['Hidden duplicate', 'text-bg-dark'] : ($badges[$state] ?? [ucfirst($state), 'text-bg-light']);
                        @endphp
                        <tr>
                            <td data-label="Title" class="ps-4">
                                @if ($isHidden)
                                    <span class="fw-semibold news-list-title" lang="{{ $item->language }}">{{ $item->title }}</span>
                                @else
                                    <a href="{{ route('admin.news.edit', $item->id) }}" class="fw-semibold text-body text-decoration-none news-list-title" lang="{{ $item->language }}">{{ $item->title }}</a>
                                @endif
                                @if ($item->is_featured)
                                    <span class="badge rounded-pill text-bg-primary ms-1"><i class="fas fa-star" aria-hidden="true"></i> Featured</span>
                                @endif
                                <div class="small text-muted text-break">/news/{{ $item->slug }}</div>
                                @if ($isHidden)
                                    <div class="small text-break">
                                        <i class="fas fa-share" aria-hidden="true"></i> Redirects to
                                        @if ($item->duplicateOf)
                                            <a href="{{ route('admin.news.edit', $item->duplicateOf->id) }}" lang="{{ $item->duplicateOf->language }}">{{ $item->duplicateOf->title }}</a>
                                            <span class="text-muted">(/news/{{ $item->duplicateOf->slug }})</span>
                                        @else
                                            <span class="text-muted">an article that no longer exists (#{{ $item->duplicate_of_id }})</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td data-label="Status"><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
                            <td data-label="Language">{{ \App\Models\News::LANGUAGES[$item->language] ?? $item->language }}</td>
                            <td data-label="Category">{{ $item->category->label ?? '—' }}</td>
                            <td data-label="Date" class="text-nowrap">
                                @if ($item->published_at)
                                    <time datetime="{{ $item->published_at->toIso8601String() }}">{{ $item->published_at->copy()->setTimezone($tz)->format('M j, Y') }}</time>
                                    @if ($state === 'scheduled')
                                        <div class="small text-muted">{{ $item->published_at->copy()->setTimezone($tz)->format('H:i') }} Addis Ababa</div>
                                    @endif
                                @else
                                    <span class="text-muted">Not published</span>
                                @endif
                            </td>
                            <td data-label="Actions" class="pe-4">
                                @if ($isHidden)
                                <div class="action-buttons">
                                    <form action="{{ route('admin.news.restore', $item->id) }}" method="POST" onsubmit="return confirm('Show this article on the site again? Its address will stop redirecting to the kept article.');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-undo me-1" aria-hidden="true"></i>Restore<span class="visually-hidden">: {{ $item->title }}</span>
                                        </button>
                                    </form>
                                </div>
                                @else
                                <div class="action-buttons">
                                    <a href="{{ route('news.show', $item->slug) }}" class="btn btn-sm btn-light" target="_blank" rel="noopener" title="{{ $state === 'published' ? 'View on site' : 'Preview' }}">
                                        <i class="fas fa-eye" aria-hidden="true"></i><span class="visually-hidden">{{ $state === 'published' ? 'View on site' : 'Preview' }}: {{ $item->title }}</span>
                                    </a>
                                    <a href="{{ route('admin.news.edit', $item->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-pencil-alt" aria-hidden="true"></i><span class="visually-hidden">Edit: {{ $item->title }}</span>
                                    </a>
                                    <form action="{{ route('admin.news.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this article? It will be hidden from the site but kept in the database.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash" aria-hidden="true"></i><span class="visually-hidden">Delete: {{ $item->title }}</span>
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3" aria-hidden="true"></i>
                                @if ($search !== '' || $status)
                                    <p class="h5 text-muted">No articles match your search.</p>
                                    <a href="{{ route('admin.news.index') }}">Show all articles</a>
                                @else
                                    <p class="h5 text-muted">No news articles yet.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($news->hasPages())
        <div class="card-footer d-flex justify-content-center">
            {{ $news->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    .news-list-card.card:hover { transform: none; box-shadow: none; }
    /* The layout breaks words anywhere; short columns must not split ("Amhari c"). */
    @media (min-width: 768px) {
        .news-list-card th,
        .news-list-card td[data-label="Status"],
        .news-list-card td[data-label="Language"],
        .news-list-card td[data-label="Category"],
        .news-list-card td[data-label="Date"] { white-space: nowrap; word-break: normal; hyphens: manual; }
    }
    .news-list-title:hover { color: #2563eb !important; text-decoration: underline !important; }
    .news-list-title[lang="am"] { font-family: var(--font-ui); }
</style>
@endpush
