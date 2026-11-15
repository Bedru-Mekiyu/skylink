@extends('layouts.master')

@section('title', $videoItem->title . ' - Sky Link')
@section('description', Str::limit($videoItem->description, 150))

@push('styles')
<style>
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
    $url = $videoItem->video_url;
    $isTikTok = strpos($url, 'tiktok.com') !== false;
    $isYouTube = (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false);
    
    $videoId = ''; // This will only be used for YouTube videos
    if ($isYouTube) {
        // This regular expression finds the YouTube ID from any format (long, short, or shorts)
        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/|shorts\/)([^"&?\/ ]{11})/i', $url, $matches);
        if (isset($matches[1])) {
            $videoId = $matches[1];
        }
    }
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

                    @if ($isTikTok)
                        {{-- If it's a TikTok link, embed their player --}}
                        <blockquote class="tiktok-embed" cite="{{ $url }}" data-video-id="{{ basename(parse_url($url, PHP_URL_PATH)) }}" style="max-width: 400px; min-width: 325px;">
                            <section>
                                <a target="_blank" title="Video by {{ $videoItem->title }}" href="{{ $url }}">Video by {{ $videoItem->title }}</a>
                            </section>
                        </blockquote>
                        {{-- IMPORTANT: This script is required by TikTok to make the player work --}}
                        <script async src="https://www.tiktok.com/embed.js"></script>

                    @elseif ($isYouTube && $videoId)
                        {{-- If it's a YouTube link, embed the YouTube player --}}
                        <div class="video-player-wrapper">
                            <iframe 
                                src="https://www.youtube.com/embed/{{ $videoId }}" 
                                title="{{ $videoItem->title }}" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                        </div>

                    @else
                        {{-- If we don't recognize the link, show an error --}}
                        <div class="alert alert-warning">
                            Could not display video. Please provide a valid Share Link from YouTube or TikTok.
                        </div>
                    @endif

                </div>

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

@endsection