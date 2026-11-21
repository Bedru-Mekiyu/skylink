{{-- About page department cards. Data: Admin > Departments (visible ones, in order). Rows of two; a last odd card is centred; one column on phones. None = nothing is rendered. --}}
@php($deps = \App\Models\Department::publicList())
@if (count($deps))
    <div class="row g-4 mt-3 justify-content-center" id="departments">
        @foreach ($deps as $i => $d)
            <div class="col-12 col-md-6" data-aos="{{ $i % 2 === 0 ? 'fade-right' : 'fade-left' }}" data-aos-duration="1000">
                <div class="team-showcase-card h-100 bg-white rounded-4 shadow-lg overflow-hidden position-relative">
                    <div class="position-absolute top-0 {{ $i % 2 === 0 ? 'end-0' : 'start-0' }} bg-{{ $d['accent'] }} bg-opacity-10 rounded-circle" style="width: {{ $i % 2 === 0 ? 100 : 80 }}px; height: {{ $i % 2 === 0 ? 100 : 80 }}px; transform: translate({{ $i % 2 === 0 ? '30%' : '-30%' }}, -30%);"></div>

                    <div class="p-4 p-lg-5">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-{{ $d['accent'] }} bg-opacity-10 rounded-3 p-3 me-3">
                                <i class="{{ $d['icon'] }} text-{{ $d['accent'] }} fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h4 fw-bold text-dark mb-1">{{ $d['name'] }}</h3>
                                @if ($d['tagline'])<p class="text-muted mb-0">{{ $d['tagline'] }}</p>@endif
                            </div>
                        </div>

                        @if ($d['image'])
                            <div class="position-relative overflow-hidden rounded-3 mb-4">
                                <img src="{{ $d['image'] }}" loading="lazy" decoding="async" width="1000" height="625"
                                     class="img-fluid w-100 transition-transform"
                                     style="height: 250px; object-fit: cover; transition: transform 0.3s ease;"
                                     alt="{{ $d['alt'] }}"
                                     onmouseover="this.style.transform='scale(1.05)'"
                                     onmouseout="this.style.transform='scale(1)'">
                                @if ($d['label'])
                                    <div class="position-absolute bottom-0 start-0 end-0 bg-gradient-to-top from-dark to-transparent p-3">
                                        <div class="d-flex justify-content-between align-items-center text-white"><span class="fw-medium">{{ $d['label'] }}</span></div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if (count($d['stats']))
                            <div class="row g-3">
                                @foreach ($d['stats'] as $s)
                                    <div class="col-6">
                                        <div class="text-center p-3 bg-light rounded-3">
                                            <div class="fw-bold text-{{ $s['color'] }} fs-5">{{ $s['n'] }}</div>
                                            <small class="text-muted">{{ $s['l'] }}</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
