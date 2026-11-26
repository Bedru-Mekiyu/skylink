@extends('layouts.master')
@php use Illuminate\Support\Str; @endphp

@section('title', 'Explore ' . ucfirst(str_replace('-', ' ', $category)) . ' Services | Sky Link')
@section('description', 'Discover high-quality services in ' . ucfirst(str_replace('-', ' ', $category)) . ' tailored to your business.')

@section('content')
<div class="container py-4 py-md-5 sky-commerce sky-catalog">
    <div class="row g-4">
        {{-- Left Sidebar (Reference Layout) --}}
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-th-list me-2"></i>{{ __('Service Categories') }}</h6>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($categories as $cat)
                        @php
                            $isCurrentCat = ($categoryModel->id ?? null) === $cat->id;
                        @endphp
                        <a href="{{ route('services.category', $cat->slug) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 {{ $isCurrentCat ? 'fw-bold bg-light text-primary' : 'text-dark' }}">
                            <span>{{ $cat->name }}</span>
                        </a>
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

        {{-- Main Content --}}
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="h3 fw-bold text-dark mb-1">{{ ucfirst(str_replace('-', ' ', $category)) }}</h2>
                    <p class="text-muted small mb-0">{{ __('Showing active services and technical solutions') }}</p>
                </div>
                <div>
                    <a href="{{ route('services') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        {{ __('View All Categories') }}
                    </a>
                </div>
            </div>

            <div class="row g-4">
                @forelse($items as $item)
                    @php
                        $imagePath = Str::startsWith($item->image, 'storage/') 
                            ? asset($item->image) 
                            : asset('assets/images/services/' . ($item->image ?? 'placeholder.png'));
                        $defaultOpt = $item->options->where('is_active', true)->sortBy('sort_order')->first() ?? $item->options->first();
                        $highlightFeatures = is_array($item->features) ? array_slice($item->features, 0, 3) : [];
                    @endphp
                    <div class="col-md-6 col-xl-4">
                        @include('partials.catalog-card', [
                            'url' => route('services.detail', ['subcategory' => $item->slug ?? '']),
                            'image' => $imagePath,
                            'category' => null,
                            'title' => $item->title ?? 'Untitled',
                            'description' => $item->desc,
                            'features' => $highlightFeatures,
                            'option' => $defaultOpt,
                            'item' => $item,
                            'itemType' => 'service',
                            'quoteOnly' => $item->isQuoteOnly(),
                        ])
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-warning text-center rounded-4 py-5">
                            <p class="mb-3">{{ __('No services found in this category.') }}</p>
                            <a href="{{ route('services') }}" class="btn btn-sm btn-outline-secondary">{{ __('Browse All Services') }}</a>
                        </div>
                    </div>
                @endforelse
            </div>

            @if($items->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
