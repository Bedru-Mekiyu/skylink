{{--
    Responsive image: WebP copies for browsers that support them (small files
    for phones via srcset/sizes), the original path as the fallback, and
    width/height so the page does not jump while the image loads.
    The <picture> wrapper uses display:contents (see layouts/master), so the
    <img> keeps exactly the same place and styles as a plain <img>.

    @include('partials.picture', [
        'src' => 'assets/images/services/cabling.jpg',  // relative to public/
        'alt' => 'Structured cabling',
        'sizes' => '(max-width: 767px) 100vw, 50vw',     // how wide it is shown
        'class' => 'img-fluid',                          // optional
        'priority' => false,  // true only for the first-screen image: no lazy, fetchpriority=high
        'attrs' => '',        // optional extra attributes (trusted, written by us)
    ])
--}}
@php
    $pSrc = $src;
    $pSrcset = \App\Support\ResponsiveImage::webpSrcset($pSrc);
    $pSize = \App\Support\ResponsiveImage::size($pSrc);
    $pPriority = $priority ?? false;
@endphp
@if ($pSrcset)<picture class="rimg"><source type="image/webp" srcset="{{ $pSrcset }}" sizes="{{ $sizes ?? '100vw' }}">@endif<img src="{{ asset($pSrc) }}" alt="{{ $alt }}"@if ($pSize) width="{{ $pSize[0] }}" height="{{ $pSize[1] }}"@endif @if (! empty($class))class="{{ $class }}" @endif{!! $pPriority ? 'fetchpriority="high"' : 'loading="lazy"' !!} decoding="async"@if (! empty($attrs)) {!! $attrs !!}@endif>@if ($pSrcset)</picture>@endif
