{{-- Head data for search engines and share previews (App\Support\Seo, config/seo.php). The canonical host is always APP_URL. --}}
@php
    $seo = \App\Support\Seo::resolve(
        request()->route()?->getName(),
        request()->getPathInfo(),
        trim($__env->yieldContent('title')),
        trim($__env->yieldContent('description')),
        (int) ($seoStatus ?? 200),
    );
@endphp
<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
@if ((int) ($seoStatus ?? 200) !== 404)<link rel="canonical" href="{{ $seo['canonical'] }}">@endif
@if ($seo['noindex'])
    <meta name="robots" content="noindex, follow">
@endif
@if ($seo['bing'])<meta name="msvalidate.01" content="{{ $seo['bing'] }}">@endif
@if ($seo['google'])<meta name="google-site-verification" content="{{ $seo['google'] }}">@endif
<meta property="og:type" content="{{ $seo['type'] }}">
<meta property="og:site_name" content="{{ config('seo.brand') }}">
<meta property="og:locale" content="{{ $seo['locale'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:image" content="{{ $seo['image'] }}">
@if ($seo['image_is_default'])
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
@endif
<meta property="og:image:alt" content="{{ $seo['title'] }}">
@if ($seo['published'])<meta property="article:published_time" content="{{ $seo['published'] }}">@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
<meta name="twitter:image" content="{{ $seo['image'] }}">
@if ($seo['jsonld'])
    <script type="application/ld+json">{!! json_encode($seo['jsonld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endif
