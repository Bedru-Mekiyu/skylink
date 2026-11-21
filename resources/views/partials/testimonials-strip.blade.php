{{-- "What Our Clients Say" strip (homepage + Services page). Data: Admin > Testimonials (visible ones, in order). No visible testimonial = no section. --}}
@php
    $tsItems = \App\Models\Testimonial::publicList();
    $tsN = count($tsItems);
    $tsReps = $tsN ? max(2, (int) ceil(2600 / ($tsN * 400)) + 1) : 0; // enough copies to fill any screen; the loop moves by exactly one set
    $tsAm = app()->getLocale() === 'am';
@endphp
@if ($tsN)
<section class="testimonials-section" id="testimonials">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h2 class="section-title">
                    What Our Clients Say About<br>
                    Our Digital Services
                    <div class="title-underline">
                        <div class="line-long"></div>
                        <div class="line-short"></div>
                    </div>
                </h2>
            </div>
        </div>

        <div class="carousel-container">
            <div class="carousel-track" id="carouselTrack" style="--n: {{ $tsN }}">
                @for ($rep = 0; $rep < $tsReps; $rep++)
                    @foreach ($tsItems as $t)
                        @php($quote = $tsAm ? ($t['am'] ?: $t['en']) : ($t['en'] ?: $t['am']))
                        <div class="testimonial-card" @if ($rep > 0) aria-hidden="true" @endif>
                            <div class="profile-section">
                                <div class="profile-icon">
                                    @if ($t['photo'])
                                        <img src="{{ $t['photo'] }}" alt="" width="70" height="70" loading="lazy" decoding="async" style="width:100%;height:100%;border-radius:50%;object-fit:cover">
                                    @else
                                        <i class="fas fa-user-tie"></i>
                                    @endif
                                </div>
                                <div class="client-info">
                                    <h5>{{ $t['name'] }}</h5>
                                    @if ($t['role'])<p class="profession">{{ $t['role'] }}</p>@endif
                                    @if ($t['rating'])<p class="profession" aria-label="{{ $t['rating'] }} out of 5 stars" style="letter-spacing:2px;color:#f5a623">{{ str_repeat('★', $t['rating']) }}</p>@endif
                                </div>
                            </div>
                            <p class="testimonial-text" @if ($tsAm && $t['am']) lang="am" @endif>{{ $quote }}</p>
                        </div>
                    @endforeach
                @endfor
            </div>
        </div>
    </div>
    <style>
        /* Endless loop: the strip holds several identical sets and moves by exactly ONE set (about 80 px/s, as before),
           so there is no jump. A card is 400 px wide (320 px on phones) including its margin. */
        .testimonials-section .carousel-track { width: max-content; animation: tsLoop calc(var(--n) * 5s) linear infinite; }
        @keyframes tsLoop { from { transform: translateX(0); } to { transform: translateX(calc(var(--n) * -400px)); } }
        @media (max-width: 768px) {
            .testimonials-section .carousel-track { animation-name: tsLoopM; animation-duration: calc(var(--n) * 4s); }
            @keyframes tsLoopM { from { transform: translateX(0); } to { transform: translateX(calc(var(--n) * -320px)); } }
        }
        @media (prefers-reduced-motion: reduce) {
            .testimonials-section .carousel-track { animation: none; width: auto; flex-wrap: wrap; justify-content: center; }
            .testimonials-section .testimonial-card[aria-hidden="true"] { display: none; }
        }
    </style>
</section>
@endif
