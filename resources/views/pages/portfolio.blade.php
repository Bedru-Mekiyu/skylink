@extends('layouts.master')

@section('title', 'Portfolio - Sky Link IT Solutions')
@section('description', 'Explore our portfolio of successful IT projects and digital solutions delivered to clients across Ethiopia.')

@push('styles')
    @include('partials.news.styles')
    @include('partials.portfolio.styles')
    <style>
        .portfolio-search-empty {
            display: none;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            padding: 40px 24px;
            text-align: center;
            color: #6b7280;
        }

        .portfolio-page .news-toolbar {
            justify-content: flex-end;
        }
    </style>
@endpush

@section('content')
<div class="news-page portfolio-page">
    {{-- Banner: the featured project (video when it has one). It is not repeated in the cards below. --}}
    @include('partials.portfolio.hero', ['heroProject' => $heroProject ?? null])

    <div class="container pb-5">
        @if (\App\Support\Anniversary::isActive())
            @include('partials.anniversary.portfolio-feature')
        @endif

        <div class="news-toolbar">
            <form id="portfolioSearchForm" class="news-search" role="search">
                <label for="portfolioSearchInput" class="visually-hidden">Search projects</label>
                <input type="search" id="portfolioSearchInput" placeholder="Search by title or description...">
                <button type="submit" aria-label="Search"><i class="fas fa-search" aria-hidden="true"></i></button>
            </form>
        </div>

        <div class="portfolio-search-empty" id="searchResults" role="status">
            <p class="mb-0">No projects found matching your search.</p>
        </div>

        @if ($projects->count())
            <div class="row g-4" id="projectsContainer">
                @foreach ($projects as $project)
                    <div class="col-12 col-md-6" data-project>
                        @include('partials.portfolio.card', ['project' => $project, 'headingLevel' => 'h2', 'withClip' => true])
                    </div>
                @endforeach
            </div>

            @if ($projects->hasPages())
                <nav class="mt-5 d-flex justify-content-center" aria-label="Project pages">
                    {{ $projects->onEachSide(1)->links('pagination::bootstrap-5') }}
                </nav>
            @endif
        @elseif (! ($heroProject ?? null))
            <div class="text-center py-5">
                <p>No projects have been added yet.</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Client-side search over the projects on this page.
    // The layout renders the scripts stack twice: run once.
    (function () {
        if (window.__skyPortfolioSearch) return;
        window.__skyPortfolioSearch = true;
        function init() {
            var form = document.getElementById('portfolioSearchForm');
            var input = document.getElementById('portfolioSearchInput');
            var empty = document.getElementById('searchResults');
            var items = document.querySelectorAll('[data-project]');
            if (!form || !input) return;
            function filter() {
                var term = input.value.toLowerCase().trim();
                var visible = 0;
                items.forEach(function (item) {
                    var card = item.querySelector('.project-card');
                    var text = (card.getAttribute('data-title') + ' ' + card.querySelector('.project-card__text').textContent).toLowerCase();
                    var show = term === '' || text.indexOf(term) !== -1;
                    item.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                // The banner project is not repeated as a card; say so when it is the match.
                var hero = document.querySelector('[data-pf-hero-title]');
                var heroMatch = !!(hero && term !== '' && hero.getAttribute('data-pf-hero-title').indexOf(term) !== -1);
                empty.querySelector('p').textContent = heroMatch
                    ? 'This is the featured project at the top of this page.'
                    : 'No projects found matching your search.';
                empty.style.display = (visible === 0 && term !== '') ? 'block' : 'none';
            }
            form.addEventListener('submit', function (e) { e.preventDefault(); filter(); });
            input.addEventListener('search', filter); // the "x" clear button
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    })();
</script>
@endpush
