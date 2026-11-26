{{--
    The way back, on every public page but the home page. The link points to the page above this one, so it always works;
    when the visitor came from another page of this site, back.js takes them to exactly that page instead.
--}}
@php
    $name = request()->route()?->getName();
    $parent = match (true) {
        $name === null, $name === 'home' => null,
        $name === 'cart.index' => ['Products', route('products')],
        $name === 'cart.success', $name === 'pay.return' => ['Home', route('home')],
        $name === 'pay.show' => ['Cart', route('cart.index')],
        $name === 'products.detail', $name === 'products.by-slug' => ['Products', route('products')],
        $name === 'services.category' => ['Services', route('services')],
        $name === 'services.detail', $name === 'services.by-slug' => isset($service) && $service->category
            ? [$service->category->name, route('services.category', $service->category->slug)]
            : ['Services', route('services')],
        $name === 'news.show', $name === 'videos.show' => ['News', route('content.index')],
        default => ['Home', route('home')],
    };
@endphp
@if($parent)
    <a href="{{ $parent[1] }}" class="sky-back" data-back aria-label="{{ __('Back to') }} {{ $parent[0] }}" title="{{ __('Back to') }} {{ $parent[0] }}">
        <i class="fas fa-arrow-left" aria-hidden="true"></i>
    </a>
    <script src="{{ asset('assets/js/sky-back.js') }}?v={{ @filemtime(public_path('assets/js/sky-back.js')) }}" defer></script>
@endif
