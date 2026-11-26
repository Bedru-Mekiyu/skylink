@extends('layouts.master')
@section('title', $service->localized('title') . ' | Sky Link')
@section('description', $service->localized('desc'))

@section('content')
<div class="container py-4 py-md-5 sky-commerce sky-catalog">
    <div class="row g-4">
        {{-- Left Sidebar: Categories & Need Help Box (Reference Layout) --}}
        <div class="col-lg-3 order-2 order-lg-1">
            {{-- Category Accordion / Tree --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#servicesSidebarCollapse" aria-expanded="false" aria-controls="servicesSidebarCollapse">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-th-list me-2"></i>{{ __('Service Categories') }}</h6>
                    <span class="d-lg-none small"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="collapse d-lg-block" id="servicesSidebarCollapse">
                    <div class="list-group list-group-flush" id="servicesSidebar">
                        @foreach($categories as $cat)
                            @php
                                $isCurrentCat = $service->category_id === $cat->id;
                            @endphp
                            <a href="{{ route('services.category', $cat->slug) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 {{ $isCurrentCat ? 'fw-bold bg-light text-primary' : 'text-dark' }}">
                                <span>{{ $cat->name }}</span>
                            </a>
                            @if($isCurrentCat)
                                <div class="ps-3 pe-2 py-1 bg-white border-bottom">
                                    @foreach($cat->services as $catService)
                                        <a href="{{ route('services.detail', $catService->slug) }}" class="d-block py-1 px-2 text-decoration-none small {{ $catService->id === $service->id ? 'text-primary fw-bold border-start border-3 border-primary ps-2' : 'text-muted' }}">
                                            {{ $catService->title }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
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
                <p class="small text-muted mb-3">{{ __('Speak with our enterprise solutions team for custom scoping.') }}</p>
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

        {{-- Main Area: Title, Tiers Cards, Details --}}
        <div class="col-lg-9 order-1 order-lg-2">
            {{-- Service Header Banner --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 bg-white">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                    <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill small fw-semibold">
                        {{ $service->category?->name ?? 'Software Service' }}
                    </span>
                    <span class="badge bg-light text-secondary border px-3 py-1 rounded-pill small">
                        🌟 {{ __('Trusted by 500+ businesses') }}
                    </span>
                </div>
                @if($service->header_image)
                    <img src="{{ asset($service->header_image) }}" alt="" class="img-fluid rounded-4 mb-3" width="1200" height="400" style="max-height: 280px; width: 100%; object-fit: cover;">
                @endif
                <h1 class="display-6 fw-bold text-dark mb-3">{{ $service->header_title ?: $service->localized('title') }}</h1>
                <p class="lead text-muted mb-0">{{ $service->header_subtitle ?: $service->localized('desc') }}</p>
                @if($service->header_subtitle)<p class="text-muted mt-3 mb-0">{{ $service->localized('desc') }}</p>@endif
                @if($service->header_cta_text)
                    <div class="mt-3"><a href="{{ $service->isQuoteOnly() ? $service->supportUrl() : '#service-form' }}" class="btn btn-primary px-4">{{ $service->header_cta_text }}</a></div>
                @endif
            </div>

            {{-- Flash Messages --}}
            @include('partials.forms.feedback', ['anchor' => 'service-form'])

            @if($service->isQuoteOnly())
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-5 text-center bg-white">
                    <h2 class="h4 fw-bold mb-2">{{ __('Priced for your project') }}</h2>
                    <p class="text-muted mb-4">{{ __('Tell us about your site and requirements and we will send you a quotation.') }}</p>
                    <div><a href="{{ $service->supportUrl() }}" class="btn btn-primary px-4"><i class="fas fa-headset me-2" aria-hidden="true"></i>{{ __('Request a Quote') }}</a></div>
                </div>
            @else
            @php
                $options = $service->options->where('is_active', true)->sortBy('sort_order')->values();
                $options->each->setRelation('service', $service);
            @endphp
            @include('partials.plans', [
                'options' => $options,
                'item' => $service,
                'itemType' => 'service',
                'heading' => $service->plans_heading ?: __('Available Packages & Plans'),
                'fallbackTitle' => __('Standard Scope'),
                'fallbackPrice' => \App\Support\PriceVisibility::shouldShowServicePrice($service) ? $service->price : null,
            ])
            @endif

            {{-- Full Features List --}}
            @php $capabilities = $service->localized('features'); @endphp
            @if(!empty($capabilities) && is_array($capabilities))
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 bg-white">
                    <h4 class="fw-bold text-dark mb-4">{{ __('Key Capabilities & Deliverables') }}</h4>
                    <div class="row g-3">
                        @foreach($capabilities as $feature)
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-3 rounded-3 bg-light">
                                    <i class="fas fa-check text-success me-3 fs-5"></i>
                                    <span class="fw-medium text-dark">{{ $feature }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- FAQ Section --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 bg-white">
                <h4 class="fw-bold text-dark mb-4">{{ __('Frequently Asked Questions') }}</h4>
                <div class="accordion" id="faqAccordion">
                    @foreach ([
                        ['q'=>'What kind of IT support does Skylink offer?', 'a'=>'We provide comprehensive IT support for businesses of all sizes, including network setup, hardware maintenance, software troubleshooting, and ongoing helpdesk services.'],
                        ['q'=>'Do you offer cybersecurity and smart CCTV solutions?', 'a'=>'Yes! We specialize in CCTV installations, access-control systems, cybersecurity audits and implementations tailored for Ethiopian businesses.'],
                        ['q'=>'Can Skylink handle ERP & School Management implementations?', 'a'=>'Absolutely — we deliver end-to-end implementation of ERP systems and School Management platforms, configured for local educational and business needs.'],
                        ['q'=>'How long does setup and deployment take?', 'a'=>'Most basic systems (networks, CCTV) are ready in 2–5 business days. Larger deployments like ERP or LMS may take 3–12 weeks depending on scope.'],
                        ['q'=>'What payment methods are accepted in Ethiopia?', 'a'=>'We accept bank transfers (CBE, Awash, Abyssinia), mobile payments (CBE Birr), and secured credit card payments.'],
                    ] as $i => $faq)
                        <div class="accordion-item mb-2 border rounded-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading{{ $i }}">
                                <button class="accordion-button {{ $i !== 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}">
                                    {{ $faq['q'] }}
                                </button>
                            </h2>
                            <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">{{ $faq['a'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Direct Service Inquiry Form --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 bg-white" id="service-form">
                <h4 class="fw-bold text-dark mb-2">{{ __('Request Custom Consultation') }}</h4>
                <p class="text-muted small mb-4">{{ __('Have specific requirements or custom enterprise needs? Send a message directly to our engineering team.') }}</p>
                <form method="POST" action="{{ route('inquiry.store') }}" data-busy>
                    @csrf
                    <x-form-guard />
                    <input type="hidden" name="type" value="service">
                    <input type="hidden" name="form_anchor" value="service-form">
                    <input type="hidden" name="item_type" value="service">
                    <input type="hidden" name="item_id" value="{{ $service->id }}">
                    <input type="hidden" name="service" value="{{ $service->title }}">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="srv-name">{{ __('Full Name') }} *</label>
                            <input type="text" class="form-control" id="srv-name" name="name" autocomplete="name" value="{{ old('name') }}" placeholder="{{ __('Your Name') }}" maxlength="120" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="srv-email">{{ __('Email Address') }} *</label>
                            <input type="email" class="form-control" id="srv-email" name="email" autocomplete="email" value="{{ old('email') }}" placeholder="name@example.com" maxlength="120" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="srv-phone">{{ __('Phone Number') }} *</label>
                            <input type="tel" class="form-control" id="srv-phone" name="phone" autocomplete="tel" inputmode="tel" value="{{ old('phone') }}" placeholder="0911000000" maxlength="30" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="srv-msg">{{ __('Project Scope & Requirements') }} *</label>
                            <textarea class="form-control" id="srv-msg" name="message" rows="3" placeholder="{{ __('Describe your timeline, scope, or requirements...') }}" maxlength="2000" required>{{ old('message') }}</textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-paper-plane me-1"></i> {{ __('Send Direct Inquiry') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
