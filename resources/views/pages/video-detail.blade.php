@extends('layouts.master')

@section('title', $videoItem->title . ' - Sky Link')
@section('description', Str::limit($videoItem->description, 150))

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/sky-video-fallback.css') }}">
<style>
    .sky-player { position: relative; width: min(100%, 400px); aspect-ratio: 9 / 16; border-radius: 12px; overflow: hidden; background: #000; box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
    .sky-player iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
    .sky-lite { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; padding: 0; cursor: pointer; background: #06122b center / cover no-repeat; display: flex; align-items: center; justify-content: center; }
    .sky-lite::before { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(4,12,34,.15), rgba(4,12,34,.55)); }
    .sky-lite__play { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 72px; height: 72px; border-radius: 50%; background: #fff; color: #06122b; font-size: 26px; padding-left: 5px; box-shadow: 0 8px 25px rgba(0,0,0,.35); transition: transform .2s ease; }
    .sky-lite:hover .sky-lite__play, .sky-lite:focus-visible .sky-lite__play { transform: scale(1.08); }
    .sky-lite:focus-visible { outline: 3px solid #1a6fb0; outline-offset: -3px; }
    .sky-player--unknown { aspect-ratio: 16 / 9; width: min(100%, 800px); }
    /* These styles are great and will remain the same */
    .video-detail-section {
        padding: 60px 0;
    }
    .video-title-header {
        margin-bottom: 20px;
    }
    .video-title-header h1 {
        font-weight: bold;
    }
    .meta-data {
        color: #6c757d;
        margin-bottom: 30px;
    }
    .meta-data span {
        margin-right: 20px;
    }
    /* This style is for the YouTube player wrapper */
    .video-player-wrapper {
        position: relative;
        width: 100%;
        max-width: 800px;
        padding-top: 56.25%; /* 16:9 aspect ratio */
        height: 0;
        overflow: hidden;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        margin-bottom: 30px;
    }
    .video-player-wrapper iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 0;
    }
    .video-description {
        line-height: 1.8;
        font-size: 1.1rem;
    }
    .back-link {
        margin-top: 40px;
        display: inline-block;
        color: var(--primary-color);
        font-weight: 500;
    }
</style>
@endpush

@section('content')

{{-- =================================================================== --}}
{{--    SMART LOGIC BLOCK - This figures out which video player to use    --}}
{{-- =================================================================== --}}
@php
    // The player is chosen from the saved link by the Video model (id parsed server-side, never the raw link).
    $url = (string) $videoItem->video_url;
    $platform = $videoItem->platform;                       // youtube | tiktok | null
    $platformName = ['youtube' => 'YouTube', 'tiktok' => 'TikTok'][$platform] ?? null;
    $link = preg_match('~^https?://~i', $url) ? $url : null;
    $thumb = $videoItem->thumbnail_url;
@endphp

<section class="video-detail-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">

                <!-- Video Title and Meta -->
                <div class="video-title-header">
                    <h1>{{ $videoItem->title }}</h1>
                    <div class="meta-data">
                        <span><i class="fas fa-tag me-1"></i> {{ $videoItem->category->name }}</span>
                        <span><i class="fas fa-calendar-alt me-1"></i> {{ $videoItem->created_at->copy()->setTimezone(config('news.display_timezone'))->format('F j, Y') }}</span>
                    </div>
                </div>

                {{-- =================================================================== --}}
                {{--         SMART VIDEO PLAYER - This displays the correct video        --}}
                {{-- =================================================================== --}}
                <div class="d-flex justify-content-center">

                    @if ($platform === 'tiktok')
                        {{-- TikTok's own player iframe; assets/js/sky-video-guard.js swaps in "Watch on TikTok" if it cannot load. --}}
                        <div class="sky-player sky-player--tiktok" data-sky-player data-platform="tiktok" data-link="{{ $link }}" data-thumb="{{ $thumb }}" data-title="{{ $videoItem->title }}">
                            <button type="button" class="sky-lite" data-lite-embed="https://www.tiktok.com/player/v1/{{ $videoItem->tiktok_id }}?rel=0&amp;description=1&amp;music_info=1&amp;autoplay=1" data-title="{{ $videoItem->title }}" @if ($thumb) style="background-image: url('{{ $thumb }}')" @endif>
                                <span class="sky-lite__play" aria-hidden="true"><i class="fas fa-play"></i></span><span class="visually-hidden">Play video: {{ $videoItem->title }}</span>
                            </button>
                        </div>
                    @elseif ($platform === 'youtube')
                        <div class="video-player-wrapper">
                            <button type="button" class="sky-lite" data-lite-embed="https://www.youtube-nocookie.com/embed/{{ $videoItem->youtube_id }}?rel=0&amp;modestbranding=1&amp;autoplay=1" data-title="{{ $videoItem->title }}" data-platform="youtube" style="background-image: url('{{ $thumb ?: 'https://i.ytimg.com/vi/'.$videoItem->youtube_id.'/hqdefault.jpg' }}')">
                                <span class="sky-lite__play" aria-hidden="true"><i class="fas fa-play"></i></span><span class="visually-hidden">Play video: {{ $videoItem->title }}</span>
                            </button>
                        </div>
                    @elseif ($link)
                        {{-- A link the site cannot embed (for example a short TikTok link): thumbnail + button, never an error. --}}
                        <div class="sky-player sky-player--unknown">
                            <div class="sky-video-fallback" @if ($thumb) style="background-image: url('{{ $thumb }}')" @endif>
                                <div class="sky-video-fallback__inner">
                                    <p class="sky-video-fallback__msg">This video cannot be played here.</p>
                                    <a class="sky-video-fallback__btn" href="{{ $link }}" target="_blank" rel="noopener noreferrer"><span class="sky-video-fallback__icon" aria-hidden="true">&#9654;</span> Watch video<span class="visually-hidden"> (opens in a new tab)</span></a>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">This video has no valid link. Please edit it in the admin.</div>
                    @endif
                </div>

                @if ($link && $platformName)
                    <p class="text-center mt-3 mb-0"><a class="btn btn-outline-primary" href="{{ $link }}" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt me-1" aria-hidden="true"></i>Watch on {{ $platformName }}<span class="visually-hidden"> (opens in a new tab)</span></a></p>
                @endif

                <!-- Video Description -->
                @if($videoItem->description)
                    <div class="video-description mt-4">
                        {!! nl2br(e($videoItem->description)) !!}
                    </div>
                @endif
                
                <!-- Back Link -->
                <a href="{{ route('content.index') }}" class="back-link">
                    <i class="fas fa-arrow-left me-1"></i> Back to All Content
                </a>

            </div>
        </div>
    </div>
</section>

@push('scripts')
<script src="{{ asset('assets/js/sky-video-guard.js') }}?v={{ @filemtime(public_path('assets/js/sky-video-guard.js')) }}"></script>
<script>
    // Lite embed: only a thumbnail and a play button load with the page; the player starts after the click (faster page, no third-party requests until needed).
    (function () {
        var ALLOWED = /^https:\/\/(www\.youtube-nocookie\.com\/embed\/|www\.tiktok\.com\/player\/v1\/)/;
        function init() {
            document.querySelectorAll('[data-lite-embed]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var src = btn.getAttribute('data-lite-embed') || '';
                    if (!ALLOWED.test(src)) return;
                    var box = btn.parentNode;
                    var iframe = document.createElement('iframe');
                    iframe.src = src; iframe.title = btn.getAttribute('data-title') || 'Video';
                    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen'; iframe.allowFullscreen = true;
                    iframe.referrerPolicy = 'strict-origin-when-cross-origin';
                    iframe.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;border:0';
                    box.replaceChild(iframe, btn);
                    if (window.SkyVideoGuard && box.hasAttribute('data-sky-player') && box.getAttribute('data-platform') === 'tiktok') {
                        window.SkyVideoGuard.watch(iframe, { box: box, link: box.getAttribute('data-link'), thumb: box.getAttribute('data-thumb'), platform: 'tiktok', title: box.getAttribute('data-title') });
                    }
                });
            });
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    })();
</script>
@endpush

@endsection