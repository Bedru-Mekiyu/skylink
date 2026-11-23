{{--
    Homepage "Trusted Partners" marquee. Logos come from Admin > Partners (featured first, then the admin's order).
    With no partners in the database the original static list below is used, so the section never goes empty.
    Same look as before (round 90 px logos, hover pause, 20 s-per-screen speed); now an endless seamless loop
    (the set is repeated), a static centred row when there are only a few logos, and a static grid for reduced motion.
--}}
@php
    $rows = \App\Models\Partner::homeList();
    if (! $rows) {
        // Static fallback: the logos that were hard-coded here before the admin page existed.
        $rows = collect([
            ['assets/images/minbertv.webp', 'Minber'], ['assets/images/iman.webp', 'Iman'], ['assets/images/Ethiotel.jpg', 'Ethiotel'],
            ['assets/images/Safaricom.png', 'Safaricom'], ['assets/images/trusted/ad.png', 'Ad'], ['assets/images/trusted/amar.jpg', 'Amar'],
            ['assets/images/trusted/amin.png', 'Amin'], ['assets/images/trusted/basira.png', 'Basira'], ['assets/images/trusted/bilalul.png', 'Bilalul'],
            ['assets/images/trusted/kasma.png', 'Kasma'], ['assets/images/trusted/mslegue.JPG', 'Msleguer'], ['assets/images/newleaf.webp', 'Newleaf'],
            ['assets/images/trusted/nujum.jpg', 'Nujum'], ['assets/images/trusted/popular.png', 'Poplar'], ['assets/images/trusted/ethioceramics.jpg', 'Ethioceramics'],
            ['assets/images/trusted/softvalley.jpg', 'Softvalley'], ['assets/images/befan.jpg', 'Befam'], ['assets/images/trusted/ukaz.jpg', 'Ukaz'],
            ['assets/images/trusted/sms.png', 'SMS'], ['assets/images/trusted/unknown.PNG', 'Unknown'], ['assets/images/evergreen.jfif', 'Evergreen'],
            ['assets/images/aauLogo.png', 'AAU'], ['assets/images/trusted/diredawa.png', 'Diredawa'], ['assets/images/trusted/addis.png', 'Addis'],
            ['assets/images/trusted/alfa.jpeg', 'Alfa'], ['assets/images/trusted/hilco.png', 'Hilco'], ['assets/images/trusted/maroli.JPG', 'Maroli'],
        ])->map(fn ($r) => ['name' => $r[1], 'alt' => $r[1], 'url' => null, 'img' => asset($r[0]), 'webp' => null, 'w' => null, 'h' => null])->all();
    }
    $n = count($rows);
    $loop = $n > 6;                                   // up to 6 logos fit the container: a still, centred row
    $dur = max(12, (int) round($n * 0.91));           // about the old speed (~165 px/s with 150 px per logo)
    $copies = $loop ? [false, true] : [false];        // second copy (aria-hidden) makes the loop seamless
@endphp
@if ($n)
<style>
    .partners-slider { overflow: hidden; white-space: nowrap; }
    .partners-track { display: inline-flex; align-items: center; }
    .partners-track > * { flex: 0 0 auto; }
    .partners-track .partner-logo { height: 90px; width: auto; max-width: 220px; object-fit: contain; border-radius: 0; }
    .partners-track.is-loop { animation: partnersLoop var(--partners-dur, 25s) linear infinite; will-change: transform; }
    @keyframes partnersLoop { from { transform: translateX(0); } to { transform: translateX(-50%); } }
    .partners-track.is-loop:hover, .partners-track.is-loop:focus-within { animation-play-state: paused; }
    .partners-slider--still { white-space: normal; text-align: center; }
    .partners-slider--still .partners-track { flex-wrap: wrap; justify-content: center; width: 100%; }
    .partner-link { display: inline-flex; border-radius: 4px; }
    .partner-link:focus-visible { outline: 3px solid #3B82F6; outline-offset: 2px; }
    @media (prefers-reduced-motion: reduce) {
        .partners-slider { white-space: normal; text-align: center; }
        .partners-track, .partners-track.is-loop { animation: none; flex-wrap: wrap; justify-content: center; width: 100%; row-gap: 16px; }
        .partners-track [data-partners-copy] { display: none; }
    }
</style>
<section class="partners-section">
    <div class="container">
        <h5 class="h5 text-uppercase text-center fw-semibold mb-4 text-dark">{{ app()->getLocale() === 'am' ? 'የታመኑ ደንበኞች' : 'Trusted Clients' }}</h5>
        <div class="partners-slider {{ $loop ? '' : 'partners-slider--still' }}">
            <div class="partners-track {{ $loop ? 'is-loop' : '' }}" style="--partners-dur: {{ $dur }}s">
                @foreach ($copies as $isCopy)
                    @foreach ($rows as $r)
                        @php
                            $img = '<img src="'.e($r['img']).'" alt="'.($isCopy || $r['url'] ? '' : e($r['alt'])).'"'
                                .($r['w'] && $r['h'] ? ' width="'.(int) $r['w'].'" height="'.(int) $r['h'].'"' : '')
                                .' class="partner-logo" loading="lazy" decoding="async">';
                            $pic = $r['webp'] ? '<picture><source type="image/webp" srcset="'.e($r['webp']).'">'.$img.'</picture>' : $img;
                        @endphp
                        @if ($r['url'] && ! $isCopy)
                            <a class="partner-link" href="{{ $r['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $r['alt'] }} (opens in a new tab)">{!! $pic !!}</a>
                        @elseif ($r['url'])
                            <a class="partner-link" href="{{ $r['url'] }}" target="_blank" rel="noopener noreferrer" tabindex="-1" aria-hidden="true" data-partners-copy>{!! $pic !!}</a>
                        @elseif ($isCopy)
                            <span aria-hidden="true" data-partners-copy>{!! $pic !!}</span>
                        @else
                            {!! $pic !!}
                        @endif
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
