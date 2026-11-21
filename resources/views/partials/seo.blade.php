{{-- Canonical address and basic share tags. The canonical host is always APP_URL (https://skylinkict.com), never s. or www. --}}
@php($seoUrl = rtrim((string) config('app.url'), '/').request()->getPathInfo())
<link rel="canonical" href="{{ $seoUrl }}">
@unless (isset($newsItem))
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Sky Link Technologies">
    <meta property="og:url" content="{{ $seoUrl }}">
@endunless
