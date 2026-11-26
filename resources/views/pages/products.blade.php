@extends('layouts.master')

@section('title', 'Explore Our Products | Sky Link ICT Solutions')
@section('description', 'Enterprise business tools, industry solutions, and cloud software systems designed for Ethiopian businesses.')

@section('content')
<div class="container py-4 py-md-5 sky-commerce sky-catalog">
    {{-- Header --}}
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold text-dark">{{ __('Explore Our Software Products') }}</h1>
        <p class="lead text-muted max-w-xl mx-auto">{{ __('Turnkey digital solutions engineered for education, real estate, hospitality, ERP, and office automation.') }}</p>
    </div>


    <div class="row g-4">
        {{-- Left Sidebar (Reference Layout) --}}
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-boxes me-2"></i>{{ __('Product Categories') }}</h6>
                </div>
                <div class="list-group list-group-flush" id="productCatFilter">
                    <button type="button" class="list-group-item list-group-item-action py-2 px-3 fw-bold text-primary bg-light" data-cat="all" aria-pressed="true">
                        {{ __('All Categories') }}
                    </button>
                    @foreach ($products as $cat => $items)
                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3" data-cat="{{ $cat }}" aria-pressed="false">
                            <span>{{ $categories[$cat]->name ?? ucfirst(str_replace('-', ' ', $cat)) }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- "Need Help? Contact Us" Box (Reference Feature) --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-light">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle" style="width: 50px; height: 50px;">
                        <i class="fas fa-headset fa-lg"></i>
                    </span>
                </div>
                <h6 class="fw-bold text-dark mb-1">{{ __('Need Help?') }}</h6>
                <p class="small text-muted mb-3">{{ __('Speak with our software engineers for customized module demos.') }}</p>
                <div class="d-grid gap-2">
                    @foreach (config('company_contact.phones') as $cPhone)
                        <a href="{{ \App\Support\PhoneNumber::tel($cPhone) }}" class="btn btn-outline-primary btn-sm rounded-pill py-2">
                            <i class="fas fa-phone-alt me-1"></i> {{ \App\Support\PhoneNumber::international($cPhone) }}
                        </a>
                    @endforeach
                    <a href="{{ route('contact') }}" class="btn btn-primary btn-sm rounded-pill py-2">
                        <i class="fas fa-envelope me-1"></i> {{ __('Contact Us') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Main Product Area --}}
        <div class="col-lg-9">
            {{-- Search & Sort Bar --}}
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <div class="row g-2 align-items-center">
                    <div class="col-md-7">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="searchInput" class="form-control border-start-0 bg-light" placeholder="{{ __('Search products by name or features...') }}">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <select id="sortSelect" class="form-select bg-light">
                            <option value="featured">{{ __('Sort by: Featured') }}</option>
                            <option value="name_asc">{{ __('Name: A to Z') }}</option>
                            <option value="name_desc">{{ __('Name: Z to A') }}</option>
                            @if(\App\Support\PriceVisibility::globalEnabled())
                                <option value="price_asc">{{ __('Price: Low to High') }}</option>
                                <option value="price_desc">{{ __('Price: High to Low') }}</option>
                            @endif
                        </select>
                    </div>
                </div>
            </div>

            {{-- Product Grid --}}
            <div class="row g-4" id="productsGrid">
                @foreach ($products as $catKey => $items)
                    @foreach ($items as $product)
                        @php
                            $defaultOpt = $product->options->where('is_active', true)->sortBy('sort_order')->first() ?? $product->options->first();
                            $featuresList = is_array($product->features) ? array_slice($product->features, 0, 3) : [];
                            $quoteOnly = ($categories[$catKey] ?? null)?->isQuoteOnly() ?? false;
                            $priceAmount = $quoteOnly ? 0 : ($defaultOpt?->price_amount ?? ($product->price ?? 0));
                        @endphp
                        <div class="col-md-6 col-xl-4 product-card-col"
                             data-cat="{{ $catKey }}"
                             data-name="{{ strtolower($product->name) }}"
                             data-price="{{ $priceAmount }}">
                            @include('partials.catalog-card', [
                                'url' => route('products.detail', ['category' => $catKey, 'slug' => $product->slug]),
                                'image' => $product->image,
                                'category' => $categories[$catKey]->name ?? ucfirst(str_replace('-', ' ', $catKey)),
                                'title' => $product->name,
                                'description' => $product->description,
                                'features' => $featuresList,
                                'option' => $defaultOpt,
                                'item' => $product,
                                'itemType' => 'product',
                                'quoteOnly' => $quoteOnly,
                            ])
                        </div>
                    @endforeach
                @endforeach
            </div>

            <div id="noProductsFound" class="alert alert-info text-center rounded-4 py-4 d-none">
                {{ __('No products match your search or filter criteria.') }}
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const sortSelect = document.getElementById('sortSelect');
    const catButtons = document.querySelectorAll('#productCatFilter button');
    const grid = document.getElementById('productsGrid');
    const noProducts = document.getElementById('noProductsFound');

    let currentCat = 'all';

    function filterAndSort() {
        const query = searchInput.value.toLowerCase().trim();
        const cards = Array.from(grid.querySelectorAll('.product-card-col'));
        let visibleCount = 0;

        cards.forEach(card => {
            const cat = card.getAttribute('data-cat');
            const name = card.getAttribute('data-name');
            const matchesCat = (currentCat === 'all' || cat === currentCat);
            const matchesQuery = (!query || name.includes(query));

            if (matchesCat && matchesQuery) {
                card.classList.remove('d-none');
                visibleCount++;
            } else {
                card.classList.add('d-none');
            }
        });

        // Sort visible
        const sortVal = sortSelect.value;
        if (sortVal !== 'featured') {
            const visibleCards = cards.filter(c => !c.classList.contains('d-none'));
            visibleCards.sort((a, b) => {
                if (sortVal === 'name_asc') {
                    return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
                } else if (sortVal === 'name_desc') {
                    return b.getAttribute('data-name').localeCompare(a.getAttribute('data-name'));
                } else if (sortVal === 'price_asc') {
                    return parseFloat(a.getAttribute('data-price') || 0) - parseFloat(b.getAttribute('data-price') || 0);
                } else if (sortVal === 'price_desc') {
                    return parseFloat(b.getAttribute('data-price') || 0) - parseFloat(a.getAttribute('data-price') || 0);
                }
                return 0;
            });
            visibleCards.forEach(c => grid.appendChild(c));
        }

        if (visibleCount === 0) {
            noProducts.classList.remove('d-none');
        } else {
            noProducts.classList.add('d-none');
        }
    }

    searchInput.addEventListener('input', filterAndSort);
    sortSelect.addEventListener('change', filterAndSort);

    catButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            catButtons.forEach(b => { b.classList.remove('bg-light', 'text-primary', 'fw-bold'); b.setAttribute('aria-pressed', 'false'); });
            this.classList.add('bg-light', 'text-primary', 'fw-bold');
            this.setAttribute('aria-pressed', 'true');
            currentCat = this.getAttribute('data-cat');
            filterAndSort();
        });
    });
});
</script>
@endsection
