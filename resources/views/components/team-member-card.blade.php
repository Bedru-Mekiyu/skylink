@props(['member'])

{{-- Management card: portrait 4:5 photo with softly rounded corners (was a circle). Face-biased crop. --}}
<div class="text-center team-card @if (!empty($member['lead'])) team-card--lead @endif">
    <div class="team-card__photo mx-auto mb-4" style="background-color: {{ $member['bgColor'] }};">
        @if (!empty($member['image']))
            <picture>
                @if (!empty($member['webp']))<source type="image/webp" srcset="{{ $member['webp'] }}">@endif
                <img src="{{ $member['image'] }}" alt="{{ $member['name'] }}, {{ $member['title'] }}" width="{{ !empty($member['lead']) ? 1000 : 480 }}" height="{{ !empty($member['lead']) ? 1333 : 600 }}" loading="lazy" decoding="async">
            </picture>
        @else
            <span class="team-card__initials" role="img" aria-label="{{ $member['name'] }}, {{ $member['title'] }}">{{ collect(explode(' ', $member['name']))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}</span>
        @endif
    </div>

    {{-- Name and Title --}}
    <h3 class="h5 fw-bold text-dark mb-1">{{ $member['name'] }}</h3>
    <p class="text-primary fw-medium mb-3">{{ $member['title'] }}</p>
</div>
