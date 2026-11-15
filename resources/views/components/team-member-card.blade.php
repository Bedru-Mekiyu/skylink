@props(['member'])

<div class="text-center">
    {{-- Profile Image --}}
    <div class="position-relative mx-auto mb-4" style="width: 192px; height: 192px;">
        <div class="rounded-circle overflow-hidden mx-auto" style="width: 100%; height: 100%; background-color: {{ $member['bgColor'] }};">
            <img src="{{ asset($member['image']) }}" alt="{{ $member['name'] }}" class="w-100 h-100 object-fit-cover">
        </div>
    </div>

    {{-- Name and Title --}}
    <h3 class="h5 fw-bold text-dark mb-1">{{ $member['name'] }}</h3>
    <p class="text-primary fw-medium mb-3">{{ $member['title'] }}</p>

    {{-- Description --}}
    {{-- <p class="text-muted small mb-4 px-2">{{ $member['description'] }}</p> --}}

</div>
