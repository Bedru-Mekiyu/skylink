{{--
    5-year ceremony seal. One looping 8s sequence: 1 → 4 count, 5 lands in gold,
    a five-colour burst, then the settled "5 YEARS" state crossfades back to 1.
    variant="feature": homepage panel with the full headline and message.
    variant="compact": slim strip with the page's contextual line.
    Styles and the off-screen pause script: partials/anniversary/{head,scripts}.
--}}
@props(['variant' => 'compact'])
@php
    $uid = 'anniv5-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
    $palette = ['#22D3EE', '#3B82F6', '#8B5CF6', '#EC4899', '#F5C451'];
    $isFeature = $variant === 'feature';
@endphp
<aside {{ $attributes->class(['anniv5', 'anniv5--' . ($isFeature ? 'feature' : 'compact')]) }}
       data-anniv5-seal
       @if ($isFeature) aria-labelledby="{{ $uid }}-title" @else aria-label="{{ __('anniversary.seal') }}" @endif>
    <div class="anniv5__inner {{ $isFeature ? '' : 'container' }}">
        <svg class="anniv5-mark" viewBox="0 0 200 200" aria-hidden="true" focusable="false">
            <defs>
                <linearGradient id="{{ $uid }}-ring" x1="0" y1="0" x2="1" y2="1">
                    @foreach ($palette as $i => $color)
                        <stop offset="{{ $i * 25 }}%" stop-color="{{ $color }}"/>
                    @endforeach
                </linearGradient>
                <radialGradient id="{{ $uid }}-core" cx="50%" cy="42%" r="60%">
                    <stop offset="0%" stop-color="#22305F"/>
                    <stop offset="100%" stop-color="#101A35"/>
                </radialGradient>
            </defs>
            <circle class="anniv5-mark__disc" cx="100" cy="100" r="92" fill="url(#{{ $uid }}-core)"/>
            <circle class="anniv5-mark__flash" cx="100" cy="100" r="64"/>
            {{-- Orbit with two photon nodes: they race inward before the 5 lands, then circle again. --}}
            <g class="anniv5-mark__orbit-g">
                <circle class="anniv5-mark__orbit" cx="100" cy="100" r="80"/>
                <circle class="anniv5-mark__node anniv5-mark__node--a" cx="100" cy="20" r="3.2" fill="#22D3EE"/>
                <circle class="anniv5-mark__node anniv5-mark__node--b" cx="100" cy="180" r="2.6" fill="#EC4899"/>
            </g>
            <circle class="anniv5-mark__ring" cx="100" cy="100" r="92" stroke="url(#{{ $uid }}-ring)"/>
            <g class="anniv5-mark__rays">
                @for ($i = 0; $i < 12; $i++)
                    <line x1="100" y1="58" x2="100" y2="26" stroke="{{ $palette[$i % 5] }}" style="--a: {{ $i * 30 }}deg"/>
                @endfor
            </g>
            <g class="anniv5-mark__sparks">
                @for ($i = 0; $i < 15; $i++)
                    <circle cx="100" cy="100" r="{{ $i % 3 ? 2.2 : 3 }}" fill="{{ $palette[$i % 5] }}" style="--a: {{ $i * 24 + 12 }}deg"/>
                @endfor
            </g>
            @foreach ([1, 2, 3, 4, 5] as $n)
                <text class="anniv5-mark__n anniv5-mark__n--{{ $n }}" x="100" y="104" text-anchor="middle" dominant-baseline="central">{{ $n }}</text>
            @endforeach
            <text class="anniv5-mark__years" x="100" y="160" text-anchor="middle">{{ \Illuminate\Support\Str::upper(__('anniversary.years')) }}</text>
        </svg>

        <div class="anniv5__copy">
            <p class="anniv5__kicker">{{ __($isFeature ? 'anniversary.kicker' : 'anniversary.seal') }}</p>
            @if ($isFeature)
                <h2 class="anniv5__title" id="{{ $uid }}-title">{{ __('anniversary.headline') }}</h2>
                <p class="anniv5__message">{{ __('anniversary.message') }}</p>
            @else
                <p class="anniv5__line">{{ __('anniversary.context.' . \App\Support\Anniversary::contextKey()) }}</p>
            @endif
        </div>
    </div>
</aside>
