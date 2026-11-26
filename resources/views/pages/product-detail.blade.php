@extends('layouts.master')

@php
    \App\Support\Seo::set([
        'title' => $product->name,
        'description' => (string) $product->description,
        'breadcrumb' => [
            ['Products', route('products')],
            [ucfirst(str_replace('-', ' ', $category)), route('products') . '#' . $category],
            [$product->name, url()->current()]
        ]
    ]);
@endphp

@section('content')
<div class="container py-4 py-md-5 sky-commerce sky-catalog">
    <div class="row g-4">
        {{-- Left Sidebar: Categories & Need Help Box (Reference Layout) --}}
        <div class="col-lg-3 order-2 order-lg-1">
            {{-- Category Tree --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#productsSidebarCollapse" aria-expanded="false" aria-controls="productsSidebarCollapse">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-boxes me-2"></i>{{ __('Product Categories') }}</h6>
                    <span class="d-lg-none small"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="collapse d-lg-block" id="productsSidebarCollapse">
                    <div class="list-group list-group-flush">
                        @foreach($allCategories as $catSlug)
                            @php
                                $isCurrentCat = $catSlug === $category;
                            @endphp
                            <a href="{{ route('products') }}#{{ $catSlug }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 {{ $isCurrentCat ? 'fw-bold bg-light text-primary' : 'text-dark' }}">
                                <span>{{ ucfirst(str_replace('-', ' ', $catSlug)) }}</span>
                            </a>
                        @endforeach
                    </div>
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
        <div class="col-lg-9 order-1 order-lg-2">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 bg-white">
                <div class="row align-items-center gy-4">
                    <div class="col-md-5 text-center">
                        <img 
                            src="{{ $product->image }}" 
                            class="img-fluid rounded-4 shadow-sm" 
                            alt="{{ $product->name }}"
                            fetchpriority="high"
                            width="320" height="280"
                            style="max-height: 280px; object-fit: contain;"
                            onerror="this.onerror=null; this.src='{{ asset('assets/images/placeholder.png') }}';"
                        >
                    </div>

                    <div class="col-md-7">
                        <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill small fw-semibold mb-2">
                            {{ ucfirst(str_replace('-', ' ', $category)) }}
                        </span>
                        <h1 class="h2 fw-bold text-dark mb-3">{{ $product->header_title ?: $product->localized('name') }}</h1>
                        @if($product->header_subtitle)<p class="fw-semibold mb-2">{{ $product->header_subtitle }}</p>@endif
                        <p class="lead text-muted fs-6 mb-4">{{ $product->localized('description') }}</p>
                        @if($product->header_cta_text)<a href="{{ $product->isQuoteOnly() ? $product->supportUrl() : '#product-form' }}" class="btn btn-primary px-4 mb-3">{{ $product->header_cta_text }}</a>@endif

                        {{-- Flash Messages --}}
                        @include('partials.forms.feedback', ['anchor' => 'product-form'])
                    </div>
                </div>
            </div>

            @if($product->isQuoteOnly())
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-5 text-center bg-white">
                    <h2 class="h4 fw-bold mb-2">{{ __('Priced for your project') }}</h2>
                    <p class="text-muted mb-4">{{ __('Tell us about your site and requirements and we will send you a quotation.') }}</p>
                    <div><a href="{{ $product->supportUrl() }}" class="btn btn-primary px-4"><i class="fas fa-headset me-2" aria-hidden="true"></i>{{ __('Request a Quote') }}</a></div>
                </div>
            @else
            @php
                $options = $product->options->where('is_active', true)->sortBy('sort_order')->values();
                $options->each->setRelation('product', $product);
            @endphp
            @include('partials.plans', [
                'options' => $options,
                'item' => $product,
                'itemType' => 'product',
                'heading' => $product->plans_heading ?: __('Deployment & License Options'),
                'fallbackTitle' => __('Standard License'),
                'fallbackPrice' => \App\Support\PriceVisibility::shouldShowProductPrice($product) ? \App\Support\PriceVisibility::formatProductPrice($product) : null,
            ])
            @endif

            {{-- Product Features & Details Tabs --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 bg-white">
                <ul class="nav nav-tabs mb-4 gap-2" id="productTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button" role="tab" aria-controls="features" aria-selected="true">
                            {{ __('Features & Capabilities') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc" type="button" role="tab" aria-controls="desc" aria-selected="false">
                            {{ __('Full Description') }}
                        </button>
                    </li>
                    @if (!empty($product->additional_info))
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold" id="additional-tab" data-bs-toggle="tab" data-bs-target="#additional" type="button" role="tab" aria-controls="additional" aria-selected="false">
                                {{ __('Technical Specifications') }}
                            </button>
                        </li>
                    @endif
                </ul>

                <div class="tab-content" id="productTabContent">
                    {{-- Features Tab --}}
                    <div class="tab-pane fade show active" id="features" role="tabpanel" aria-labelledby="features-tab">
                        @if (!empty($product->features) && is_array($product->features))
                            <div class="row g-3">
                                @foreach ($product->features as $feature)
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center p-3 rounded-3 bg-light">
                                            <i class="fas fa-check-circle text-primary me-3 fs-5"></i>
                                            <span class="text-dark fw-medium">{{ $feature }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted fst-italic">{{ __('No feature highlights listed.') }}</p>
                        @endif
                    </div>

                    {{-- Description Tab --}}
                    <div class="tab-pane fade" id="desc" role="tabpanel" aria-labelledby="desc-tab">
                        <p class="lead text-dark fs-6">{{ $product->description }}</p>
                    </div>

                    {{-- Additional Info Tab --}}
                    @if (!empty($product->additional_info))
                        <div class="tab-pane fade" id="additional" role="tabpanel" aria-labelledby="additional-tab">
                            <div class="p-3 bg-light rounded-3 text-dark">
                                {{ $product->additional_info }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Direct Product Quote Request Form --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 bg-white" id="product-form">
                <h4 class="fw-bold text-dark mb-2">{{ __('Request Custom Product Quote') }}</h4>
                <p class="text-muted small mb-4">{{ __('Need tailored customization, on-premise installation, or enterprise licensing? Send us a direct inquiry.') }}</p>
                <form method="POST" action="{{ route('inquiry.store') }}" data-busy>
                    @csrf
                    <x-form-guard />
                    <input type="hidden" name="type" value="quote">
                    <input type="hidden" name="form_anchor" value="product-form">
                    <input type="hidden" name="item_type" value="product">
                    <input type="hidden" name="item_id" value="{{ $product->id }}">
                    <input type="hidden" name="service" value="{{ $product->name }}">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="prod-name">{{ __('Full Name') }} *</label>
                            <input type="text" class="form-control" id="prod-name" name="name" autocomplete="name" value="{{ old('name') }}" placeholder="{{ __('Your Name') }}" maxlength="120" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="prod-email">{{ __('Email Address') }} *</label>
                            <input type="email" class="form-control" id="prod-email" name="email" autocomplete="email" value="{{ old('email') }}" placeholder="name@example.com" maxlength="120" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="prod-phone">{{ __('Phone Number') }} *</label>
                            <input type="tel" class="form-control" id="prod-phone" name="phone" autocomplete="tel" inputmode="tel" value="{{ old('phone') }}" placeholder="0911000000" maxlength="30" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="prod-msg">{{ __('Specifications & Requirements') }} *</label>
                            <textarea class="form-control" id="prod-msg" name="message" rows="3" placeholder="{{ __('Describe your requirements...') }}" maxlength="2000" required>{{ old('message') }}</textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-paper-plane me-1"></i> {{ __('Send Direct Quote Request') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
