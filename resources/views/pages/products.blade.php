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
            {{-- Product Grid --}}
            <div class="row g-4" id="productsGrid">
                @foreach ($products as $catKey => $items)
                    @foreach ($items as $product)
                        @php
                            $defaultOpt = $product->options->where('is_active', true)->sortBy('sort_order')->first() ?? $product->options->first();
                            $featuresList = is_array($product->features) ? array_slice($product->features, 0, 3) : [];
                            $quoteOnly = ($categories[$catKey] ?? null)?->isQuoteOnly() ?? false;
                        @endphp
                        <div class="col-md-6 col-xl-4 product-card-col"
                             data-cat="{{ $catKey }}">
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
        </div>
    </div>
</div>

<script>
/* The sidebar is the only filter. All products show at first; a category narrows the list, pressing it again shows all.
   A link such as /products#business-tools (from a product page) opens with that category chosen. */
document.addEventListener('DOMContentLoaded', function () {
    const buttons = Array.from(document.querySelectorAll('#productCatFilter button'));
    const cards = document.querySelectorAll('#productsGrid .product-card-col');

    function show(category) {
        buttons.forEach(button => {
            const chosen = button.dataset.cat === category;
            ['bg-light', 'text-primary', 'fw-bold'].forEach(name => button.classList.toggle(name, chosen));
            button.setAttribute('aria-pressed', chosen ? 'true' : 'false');
        });
        cards.forEach(card => card.classList.toggle('d-none', category !== null && card.dataset.cat !== category));
    }

    buttons.forEach(button => button.addEventListener('click', () => {
        const next = button.getAttribute('aria-pressed') === 'true' ? null : button.dataset.cat;
        history.replaceState(null, '', next ? '#' + next : location.pathname);
        show(next);
    }));

    function fromAddress() {
        const wanted = decodeURIComponent(location.hash.slice(1));
        show(buttons.some(button => button.dataset.cat === wanted) ? wanted : null);
    }
    window.addEventListener('hashchange', fromAddress);
    fromAddress();
});
</script>
@endsection
