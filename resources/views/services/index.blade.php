

@extends('layouts.master')


{{-- (An unclosed @section('additional-styles') used to sit here; it left an
     output buffer open on every request. The styles below use the stack.) --}}
@push('styles')
<style>
    /* Hero Section */
    .hero-section {
        background: #f8f9fa;
        position: relative;
        overflow: hidden;
        /* Height comes from the content (was min-height: 80vh, which left
           empty space that grew with the screen height). */
        padding: 56px 0 48px;
    }

    @media (max-width: 991.98px) {
        .hero-section {
            padding: 32px 0 24px;
        }
    }

    .hero-content {
        position: relative;
        z-index: 2;
    }

    .hero-service-tag {
        color: #1a6fb0;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;

    }
    .service-bar {
    display: flex;
    align-items: center;
    background-color: white;
    padding: 10px 0px 0px 10px;
    width: 150%;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 6rem;
    border-radius: 8px;
}

    .categories-dropdown {
        background: #1a6fb0;
        color: white;
        border: none;
        padding: 10px 18px;
        border-radius: 8px;
        margin-left:30px;
        font-weight: 500;
        margin-bottom: 10px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    .categories-dropdown:hover {
        background: #2b4c9d;
        transform: translateY(-2px);
    }

    .categories-dropdown i {
        font-size: 14px;
    }

    .hero-title {
        font-size: 48px;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: 25px;
        color: #333;
    }

    .hero-title .tech-text {
        color: #1a6fb0;
    }

    .hero-description {
        font-size: 16px;
        line-height: 1.6;
        color: #666;
        margin-bottom: 40px;
        max-width: 500px;
    }

    .search-container {
        display: flex;
        max-width: 400px;
        margin-bottom: 40px;
        position: relative;
    }

    .search-input {
        flex: 1;
        padding: 15px 20px;
        border: 1px solid #e9ecef;
        border-right: none;
        border-radius: 8px 0 0 8px;
        font-size: 14px;
        outline: none;
        transition: all 0.3s ease;
    }

    .search-input:focus {
        border-color: #1a6fb0;
        box-shadow: 0 0 0 3px rgba(26, 111, 176, 0.1);
    }

    .search-btn {
        background: #1a6fb0;
        color: white;
        border: none;
        padding: 15px 25px;
        border-radius: 0 8px 8px 0;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .search-btn:hover {
        background: #2b4c9d;
        transform: translateY(-1px);
    }

    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    }

    .search-result-item {
        padding: 12px 20px;
        border-bottom: 1px solid #f8f9fa;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .search-result-item:hover {
        background: #f8f9fa;
        color: #1a6fb0;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    /* Technology orbit: circle, rings and logos live in one square box in the
       right-hand column, so they stay together at every width. */
    .tech-orbit {
        --badge: 84px;
        container-type: inline-size;
        position: relative;
        z-index: 1;
        width: min(500px, 100% - 56px);
        aspect-ratio: 1;
        margin: 0 auto;
    }

    .tech-orbit__circle {
        position: absolute;
        inset: 13%;
        border-radius: 50%;
        /* Same two blues, blended smoothly, with a soft light from the top
           left and a soft glow around the edge. */
        background:
            radial-gradient(circle at 30% 25%, rgba(255, 255, 255, 0.18), rgba(255, 255, 255, 0) 55%),
            linear-gradient(135deg, #1a6fb0 0%, #1a6fb0 45%, #2b4c9d 100%);
        box-shadow:
            0 24px 60px -12px rgba(43, 76, 157, 0.38),
            0 0 0 10px rgba(26, 111, 176, 0.06),
            inset 0 0 0 1px rgba(255, 255, 255, 0.12);
    }

    .tech-orbit__ring {
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }

    .tech-orbit__ring--outer {
        inset: 4%;
        border: 1px solid rgba(26, 111, 176, 0.2);
        box-shadow: 0 0 24px rgba(26, 111, 176, 0.08);
    }

    .tech-orbit__ring--inner {
        inset: 27%;
        border: 1px solid rgba(255, 255, 255, 0.22);
    }

    /* The track turns; each badge sits on the outer ring at 72° steps. */
    .tech-orbit__track {
        position: absolute;
        inset: 0;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    /* Social Icons in Hero */
    .hero-social-icons {
        position: absolute;
        
        margin-left: 100%;
        display: flex;
        gap: 15px;
        z-index: 4;
    }

    .hero-social-icon {
        width: 45px;
        height: 45px;
        background: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
    }

    .hero-social-icon:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .hero-social-icon i {
        color: #1a6fb0;
        font-size: 18px;
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 20px;
        height: 20px;
        background: #ff4757;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: white;
        font-weight: 600;
    }

    /* Tech Icons: rotate to the badge's angle, move out to the outer ring
       (radius 46% of the box), rotate back so the logo stays upright. */
    .tech-icon {
        --angle: calc(var(--i) * 72deg);
        position: absolute;
        top: 50%;
        left: 50%;
        width: var(--badge);
        height: var(--badge);
        margin: calc(var(--badge) / -2) 0 0 calc(var(--badge) / -2);
        transform: rotate(var(--angle)) translateY(-46cqw) rotate(calc(-1 * var(--angle)));
    }

    .tech-icon {
        border-radius: 50%;
        outline: none;
        cursor: default;
    }

    .tech-icon__body {
        position: relative;
        display: block;
        width: 100%;
        height: 100%;
    }

    /* Round glass-style badge: no transform on hover, so nothing jumps. */
    .tech-icon__badge {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: linear-gradient(150deg, rgba(255, 255, 255, 0.97), rgba(239, 245, 255, 0.86));
        border: 1px solid rgba(255, 255, 255, 0.9);
        box-shadow:
            0 12px 28px -8px rgba(43, 76, 157, 0.28),
            0 2px 6px rgba(15, 23, 42, 0.06),
            inset 0 1px 0 #fff;
        transition: box-shadow 0.3s ease;
    }

    .tech-icon:hover .tech-icon__badge,
    .tech-icon:focus-visible .tech-icon__badge {
        box-shadow:
            0 16px 34px -8px rgba(43, 76, 157, 0.38),
            0 0 0 6px rgba(26, 111, 176, 0.16),
            inset 0 1px 0 #fff;
    }

    .tech-icon:focus-visible .tech-icon__badge {
        outline: 2px solid #2b4c9d;
        outline-offset: 3px;
    }

    .tech-icon img {
        width: 44%;
        height: 44%;
        display: block;
    }

    /* Name shown under the badge on hover or keyboard focus. */
    .tech-icon__name {
        position: absolute;
        top: calc(100% + 8px);
        left: 50%;
        transform: translateX(-50%);
        padding: 3px 10px;
        border-radius: 999px;
        background: #2b4c9d;
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.4;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    .tech-icon:hover .tech-icon__name,
    .tech-icon:focus-visible .tech-icon__name {
        opacity: 1;
    }

    .tech-icon:hover,
    .tech-icon:focus-visible {
        z-index: 2;
    }

    /* Motion (transform only). It runs only when the small script below adds
       .is-animated: never for reduced motion or Save-Data. One animation per
       element: the track turns, each badge body turns back so the logo stays
       upright, and the badge floats with the separate `translate` property. */
    @keyframes orbit-spin { to { transform: rotate(360deg); } }
    @keyframes orbit-counter { to { transform: rotate(-360deg); } }
    @keyframes badge-float {
        0%, 100% { translate: 0 0; }
        50% { translate: 0 -6px; }
    }

    .tech-orbit.is-animated .tech-orbit__track {
        animation: orbit-spin 120s linear infinite;
        animation-play-state: var(--orbit-state, running);
    }

    .tech-orbit.is-animated .tech-icon__body {
        animation: orbit-counter 120s linear infinite;
        animation-play-state: var(--orbit-state, running);
    }

    .tech-orbit.is-animated .tech-icon__badge {
        animation: badge-float 6s ease-in-out infinite;
        animation-delay: calc(var(--i) * -1.2s);
        animation-play-state: var(--orbit-state, running);
    }

    /* NestJS at the centre: about 1.6x an orbit badge, but never wider than
       32% of the orbit, so it stays inside the inner ring (46%) at every
       width. It does not rotate; only its red halo pulses. */
    .tech-center {
        --center: min(calc(var(--badge) * 1.6), 32cqw);
        position: absolute;
        top: 50%;
        left: 50%;
        z-index: 1;
        width: var(--center);
        height: var(--center);
        margin: calc(var(--center) / -2) 0 0 calc(var(--center) / -2);
        border-radius: 50%;
        outline: none;
    }

    .tech-center .tech-icon__badge {
        position: relative;
        z-index: 1;
        border: 3px solid #E0234E;
        background: linear-gradient(150deg, #fff, #fff4f6);
        box-shadow:
            0 0 0 5px rgba(224, 35, 78, 0.14),
            0 0 26px rgba(224, 35, 78, 0.35),
            0 18px 36px -10px rgba(15, 23, 42, 0.45);
    }

    .tech-center:hover .tech-icon__badge,
    .tech-center:focus-visible .tech-icon__badge {
        box-shadow:
            0 0 0 7px rgba(224, 35, 78, 0.2),
            0 0 32px rgba(224, 35, 78, 0.45),
            0 18px 36px -10px rgba(15, 23, 42, 0.45);
    }

    .tech-center:focus-visible .tech-icon__badge {
        outline: 2px solid #fff;
        outline-offset: 4px;
    }

    .tech-center img {
        width: 50%;
        height: 50%;
        display: block;
    }

    /* Soft red halo; it pulses only when the orbit is animated. */
    .tech-center::before {
        content: "";
        position: absolute;
        inset: -2px;
        border-radius: 50%;
        border: 2px solid rgba(224, 35, 78, 0.5);
        opacity: 0;
    }

    @keyframes center-pulse {
        0% { transform: scale(1); opacity: 0.55; }
        100% { transform: scale(1.22); opacity: 0; }
    }

    .tech-orbit.is-animated .tech-center::before {
        animation: center-pulse 3.2s ease-out infinite;
        animation-play-state: var(--orbit-state, running);
    }

    .tech-center:hover .tech-icon__name,
    .tech-center:focus-visible .tech-icon__name {
        opacity: 1;
    }

    .tech-center .tech-icon__name {
        z-index: 2;
    }

    /* Paused while off-screen, and on hover or keyboard focus. */
    .tech-orbit.is-paused,
    .tech-orbit:hover,
    .tech-orbit:focus-within {
        --orbit-state: paused;
    }

    @media (prefers-reduced-motion: reduce) {
        .tech-orbit *,
        .tech-orbit *::before {
            animation: none !important;
        }
    }

    /* Services Section */
    .services-section {
        padding: 100px 0;
        background: #f8f9fa;
    }

    .section-title {
        font-size: 2.5rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 60px;
        color: #333;
    }

    .service-tabs {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-bottom: 60px;
        flex-wrap: wrap;
    }

    .service-tab {
        background: #f8f9fa;
        border: none;
        padding: 12px 24px;
        border-radius: 25px;
        font-weight: 500;
        color: #666;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .service-tab.active,
    .service-tab:hover {
        background: #1a6fb0;
        color: rgb(216, 219, 220);
        transform: translateY(-2px);
    }

    .service-card.hidden { display: none !important; }


    

    
  .filter-bar .filter-btn {
    background: none;
    border: none;
    padding: 8px 16px;
    font-size: 16px;
    color: #333;
    cursor: pointer;
    transition: background-color 0.3s, color 0.3s;
    border-radius: 5px;
  }

  .filter-bar .filter-btn:hover {
    background-color: #f0f0f0;
  }

  .filter-bar .filter-btn.active {
    background-color: #1a6fb0;
    color: #fff;
  }



    /* Services Grid - Updated for proper filtering */
    #servicesGrid {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
    }

    #servicesGrid .service-item {
        flex: 0 0 calc(25% - 22.5px);
        max-width: calc(25% - 22.5px);
    }

    @media (max-width: 992px) {
        #servicesGrid .service-item {
            flex: 0 0 calc(50% - 15px);
            max-width: calc(50% - 15px);
        }
    }

    @media (max-width: 576px) {
        #servicesGrid .service-item {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    /* Testimonials Section - Fixed and Enhanced */
    .testimonials-section {
        padding: 100px 0;
        background: #f8f9fa;
        overflow: hidden;
        position: relative;
    }

    .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 3rem;
            line-height: 1.2;
        }

        .title-underline {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }

        .line-long {
            width: 80px;
            height: 4px;
            background: #1a6fb0;
            border-radius: 2px;
        }

        .line-short {
            width: 20px;
            height: 4px;
            background: #1a6fb0;
            border-radius: 2px;
        }

        .carousel-container {
            overflow: hidden;
            width: 100%;
            position: relative;
            margin-top: 2rem;
        }

        .carousel-track {
            display: flex;
            width: calc(400px * 6); /* 3 cards × 2 sets */
            animation: slideInfinite 15s linear infinite;
        }

        .carousel-track:hover {
            animation-play-state: paused;
        }

        @keyframes slideInfinite {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(calc(-400px * 3)); /* Move by 3 card widths */
            }
        }

        .testimonial-card {
            background: #e5e7eb;
            border-radius: 15px;
            padding: 30px 25px;
            margin: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            flex: 0 0 370px; /* Fixed width for consistent spacing */
            position: relative;
            overflow: hidden;
        }

        .testimonial-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            z-index: 10;
        }


        .profile-section {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            margin-right: 20px;
            border: 3px solid #1a6fb0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #1a6fb0;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            box-shadow: 0 4px 15px rgba(26, 111, 176, 0.2);
        }

        .client-info h5 {
            color: #1a6fb0;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 1.2rem;
        }

        .client-info .profession {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .testimonial-text {
            color: #495057;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* Pause animation on hover */
        .carousel-container:hover .carousel-track {
            animation-play-state: paused;
        }

        

        @media (max-width: 768px) {
            .section-title {
                font-size: 2rem;
            }
            
            .testimonial-card {
                flex: 0 0 300px;
                margin: 10px;
                padding: 20px 15px;
            }
            
            .carousel-track {
                width: calc(320px * 6);
            }
            
            @keyframes slideInfinite {
                0% {
                    transform: translateX(0);
                }
                100% {
                    transform: translateX(calc(-320px * 3));
                }
            }

            .profile-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }

        /* Additional visual effects */
        .testimonial-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .testimonial-card:hover::after {
            left: 100%;
        }

        /* Icon animation */
        .profile-icon i {
            transition: all 0.3s ease;
        }

        .testimonial-card:hover .profile-icon i {
            transform: scale(1.1);
        }


    /* Contact Section */
    .contact-section {
        padding: 100px 0;
        background: white;
    }

    .contact-info {
        padding-right: 30px;
    }

    .contact-feature {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
    }

    .contact-icon {
        width: 60px;
        height: 60px;
        background: #1a6fb0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
        color: white;
        font-size: 1.2rem;
    }

    .contact-form {
        background: rgb(26, 111, 176);
        padding: 40px;
        color: white;
    }

    .contact-form .form-control {
        padding: 15px;
        margin-bottom: 20px;
        font-size: 1rem;
        border-radius: 0%;
        background-color: #dde9ff
    }

    .contact-form .form-control:focus {
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
    }

    .btn-submit {
        background: rgb(0, 46, 137);
        color: #ffffff;
        border: none;
        padding: 15px 30px;
        font-weight: 600;
        width: 100%;
        transition: all 0.3s ease;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 255, 255, 0.3);
    }

    /* Animations */
    .fade-in {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.6s ease;
    }

    .fade-in.visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        
        .hero-title {
            font-size: 32px;
        }
        
        .hero-social-icons {
            display: none;
        }

        /* Phones: a smaller orbit under the search box, with room left for
           the badges so nothing reaches the screen edge. */
        .tech-orbit {
            --badge: 52px;
            width: min(260px, 100% - 72px);
        }
 
        .search-container {
            max-width: 100%;
        }

        .contact-info {
            padding-right: 0;
            margin-bottom: 40px;
        }

        .testimonial-card {
            min-width: 280px;
            max-width: 280px;
            height: 300px;
        }

        .testimonials-wrapper {
            height: 380px;
        }

        #servicesGrid {
            gap: 20px;
        }
    }

    @media (max-width: 992px) {
        /* Stacked under the text: a smaller orbit, centred. */
        .tech-orbit {
            --badge: 68px;
            width: min(380px, 100% - 56px);
        }

        .testimonial-card {
            min-width: 320px;
            max-width: 320px;
        }

        #servicesGrid {
            gap: 25px;
        }
    }
</style>
@endpush

@section('content')

<!-- Hero Section -->
<section class="hero-section" id="home">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="hero-content">
                    

                    
                    <h1 class="hero-title">
                        Our Top <span class="tech-text">Tech,</span><br>
                        services
                    </h1>
                    
                    <p class="hero-description">
                        At Skylink Technologies, we craft innovative digital solutions that connect, automate, and elevate your business. From custom software to secure IT infrastructure, we deliver excellence—every step of the way.
                    </p>
                    
                    <div class="search-container">
                        <input type="text" class="search-input" id="searchInput" placeholder='e.g. "software services"'>
                        <button class="search-btn" id="searchBtn">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <div class="search-results" id="searchResults"></div>
                    </div>
                </div>
            </div>

            <!-- Tech Icons: circle, rings and logos kept together in the right-hand column -->
            <div class="col-lg-6 tech-orbit-col">
                <div class="tech-orbit">
                    <div class="tech-orbit__circle" aria-hidden="true"></div>
                    <div class="tech-orbit__ring tech-orbit__ring--outer" aria-hidden="true"></div>
                    <div class="tech-orbit__ring tech-orbit__ring--inner" aria-hidden="true"></div>
                    <ul class="tech-orbit__track" aria-label="Technologies we work with">
                        <li class="tech-icon laravel" style="--i: 0" tabindex="0"><span class="tech-icon__body"><span class="tech-icon__badge"><img src="{{ asset('assets/images/services-top/laravel.svg') }}" alt="Laravel" width="40" height="40"></span><span class="tech-icon__name" aria-hidden="true">Laravel</span></span></li>
                        <li class="tech-icon angular" style="--i: 1" tabindex="0"><span class="tech-icon__body"><span class="tech-icon__badge"><img src="{{ asset('assets/images/services-top/angular.svg') }}" alt="Angular" width="40" height="40"></span><span class="tech-icon__name" aria-hidden="true">Angular</span></span></li>
                        <li class="tech-icon dotnet" style="--i: 2" tabindex="0"><span class="tech-icon__body"><span class="tech-icon__badge"><img src="{{ asset('assets/images/services-top/dotnet.svg') }}" alt=".NET" width="40" height="40"></span><span class="tech-icon__name" aria-hidden="true">.NET</span></span></li>
                        <li class="tech-icon vuejs" style="--i: 3" tabindex="0"><span class="tech-icon__body"><span class="tech-icon__badge"><img src="{{ asset('assets/images/services-top/vuedotjs.svg') }}" alt="Vue.js" width="40" height="40"></span><span class="tech-icon__name" aria-hidden="true">Vue.js</span></span></li>
                        <li class="tech-icon nodejs" style="--i: 4" tabindex="0"><span class="tech-icon__body"><span class="tech-icon__badge"><img src="{{ asset('assets/images/services-top/nodedotjs.svg') }}" alt="Node.js" width="40" height="40"></span><span class="tech-icon__name" aria-hidden="true">Node.js</span></span></li>
                    </ul>
                    <div class="tech-center" tabindex="0"><span class="tech-icon__badge"><img src="{{ asset('assets/images/services-top/nestjs.svg') }}" alt="NestJS" width="64" height="64"></span><span class="tech-icon__name" aria-hidden="true">NestJS</span></div>
                </div>
                <script>
                    // Start the slow orbit only when motion is welcome, and pause it off-screen.
                    (function () {
                        var orbit = document.querySelector('.tech-orbit');
                        if (!orbit || !('IntersectionObserver' in window)) return;
                        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        var saveData = navigator.connection && navigator.connection.saveData;
                        if (reduced || saveData) return;
                        orbit.classList.add('is-animated');
                        new IntersectionObserver(function (entries) {
                            orbit.classList.toggle('is-paused', !entries[0].isIntersecting);
                        }).observe(orbit);
                    })();
                </script>
            </div>
        </div>
    </div>
</section>

<section class="services-section py-5" id="services">
  <div class="container">
    <h2 class=" mb-4">Let's Check Out Services</h2>

<!-- Filter Buttons -->

<!-- Category Filter Buttons -->
<div class="text-center mb-4 py-3 bg-white">
  <div class="d-inline-flex flex-wrap justify-content-center gap-1 filter-bar">
    @php
      // Filter buttons: "All" + one per published category (Admin > Service Categories).
      $serviceCards = $serviceCards ?? collect();
      $categories = ['all' => 'All'] + $serviceCards->mapWithKeys(fn ($c) => [$c->slug => $c->filter_text])->all();
    @endphp

    @foreach($categories as $key => $name)
      <button class="filter-btn btn btn-sm text-dark px-3 py-1 rounded-pill"
              data-filter="{{ $key }}"
              style="background-color: transparent; border: 1px solid #ccc;">
        {{ $name }}
      </button>
    @endforeach
  </div>
</div>

<!-- Service Cards -->
<div class="container">
  <div class="row sky-commerce sky-catalog" id="service-cards">
    @php
      // Cards: published categories in the admin's order (Admin > Service Categories).
      $services = $serviceCards->map(fn ($c) => [
        'title' => $c->card_heading,
        'desc' => $c->description,
        'img' => $c->image_url,
        'raw' => (string) $c->image,
        'category' => $c->slug,
      ]);
    @endphp

    @foreach($services as $service)
  <div class="col-md-6 col-lg-3 mb-4 service-card" data-category="{{ $service['category'] }}">
    <article class="sky-card">
      <div class="sky-card__media">
          @if (str_starts_with($service['raw'], 'assets/'))
            @include('partials.picture', ['src' => $service['raw'], 'alt' => $service['title'], 'sizes' => '(max-width: 767px) 100vw, (max-width: 991px) 50vw, 25vw', 'class' => 'w-100 h-90 object-fit-cover'])
          @else
            <img src="{{ $service['img'] }}" alt="{{ $service['title'] }}" class="w-100 h-90 object-fit-cover" loading="lazy" decoding="async">
          @endif
      </div>
      <div class="sky-card__body">
        <h3 class="sky-card__title"><a href="{{ route('services.category', ['category' => $service['category']]) }}" class="stretched-link">{{ $service['title'] }}</a></h3>
        <p class="sky-card__desc">{{ $service['desc'] }}</p>
      </div>
    </article>
  </div>
@endforeach


<!-- Filter JS Script -->
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const serviceCards = document.querySelectorAll('.service-card');

    filterButtons.forEach(button => {
      button.addEventListener('click', () => {
        // Remove active class
        filterButtons.forEach(btn => btn.classList.remove('bg-primary', 'text-white'));
        button.classList.add('bg-primary', 'text-white');

        const category = button.getAttribute('data-filter');

        serviceCards.forEach(card => {
          if (category === 'all' || card.getAttribute('data-category') === category) {
            card.style.display = 'block';
          } else {
            card.style.display = 'none';
          }
        });
      });
    });
  });
</script>



<!-- Testimonials Section - Fixed and Enhanced -->
@include('partials.testimonials-strip')

<!-- Contact Section -->
<section class="contact-section" id="contact">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
            <h5 class="mb-4" style="font-weight: 500; color: #1a6fb0;">REQUEST A QUOTE</h5>

                <div class="contact-info fade-in">
                    
                    <h2 class="mb-4" style="font-weight: 700; color: #333;">Need A Free Quote? Please Feel Free to Contact Us</h2>
                    
                    <div class="contact-feature">
                        
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h5 style="font-weight: 600; margin-bottom: 5px;">Respond within 24 hours</h5>
                            <p style="color: #666; margin: 0;">Quick response and fast delivery guaranteed</p>
                        </div>
                    </div>
                    
                    <div class="contact-feature">
                        <div class="contact-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div>
                            <h5 style="font-weight: 600; margin-bottom: 5px;">12 hrs telephone support</h5>
                            <p style="color: #666; margin: 0;">Round the clock customer support available</p>
                        </div>
                    </div>
                    
                    <div class="bg-light p-4 rounded" style="margin-top: 30px;">
                        <p style="color: #666; margin-bottom: 20px;">"Sky Link is among one of the most professional technology companies . We have a great team of developers who are always ready to help."</p>
                        <div class="contact-feature" style="margin-bottom: 0;">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <h5 style="font-weight: 600; margin-bottom: 5px;">Call to ask any Question</h5>
                                <p style="color: #1a6fb0; font-weight: 600; margin: 0;"><a class="tel-nowrap" style="color: inherit; text-decoration: none;" href="{{ \App\Support\PhoneNumber::tel(config('company_contact.phones')[0]) }}">{{ \App\Support\PhoneNumber::international(config('company_contact.phones')[0]) }}</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="contact-form fade-in" id="quote-form">
                    @include('partials.forms.feedback', ['anchor' => 'quote-form'])
                    <form id="quoteForm" method="POST" action="{{ route('inquiry.store') }}">
                        @csrf
                        <x-form-guard />
                        <input type="hidden" name="type" value="quote">
                        <input type="hidden" name="form_anchor" value="quote-form">
                        <label class="visually-hidden" for="qf-name">Your name</label>
                        <input type="text" class="form-control" id="qf-name" name="name" value="{{ old('name') }}" placeholder="Your Name *" maxlength="120" autocomplete="name" required>
                        <label class="visually-hidden" for="qf-email">Your email</label>
                        <input type="email" class="form-control" id="qf-email" name="email" value="{{ old('email') }}" placeholder="Your Email *" maxlength="190" autocomplete="email" required>
                        <label class="visually-hidden" for="qf-phone">Your phone</label>
                        <input type="tel" class="form-control" id="qf-phone" name="phone" value="{{ old('phone') }}" placeholder="Your Phone *" maxlength="30" autocomplete="tel" required>
                        <label class="visually-hidden" for="qf-service">Service</label>
                        <select class="form-control" id="qf-service" name="service" required>
                            <option value="">Select Service *</option>
                            @foreach (['Web Development', 'Mobile App Development', 'UI/UX Design', 'Digital Marketing', 'Tech Consulting', 'Network & Infrastructure', 'Security & CCTV', 'Other'] as $service)
                                <option value="{{ $service }}" @selected(old('service') === $service)>{{ $service }}</option>
                            @endforeach
                        </select>
                        <label class="visually-hidden" for="qf-message">Your message</label>
                        <textarea class="form-control" id="qf-message" name="message" rows="4" placeholder="Your Message *" maxlength="5000" required>{{ old('message') }}</textarea>
                        <button type="submit" class="btn-submit">Request A Quote</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection


@push('scripts')

{{-- Bootstrap JS comes from the layout; a second copy broke the phone menu toggle. --}}
<script>
    <!-- Filter Script -->

  document.addEventListener("DOMContentLoaded", function () {
    const filterButtons = document.querySelectorAll(".filter-btn");
    const cards = document.querySelectorAll(".service-card");

    filterButtons.forEach(button => {
      button.addEventListener("click", () => {
        // Remove active class from all buttons
        filterButtons.forEach(btn => btn.classList.remove("active"));
        button.classList.add("active");

        const filter = button.getAttribute("data-filter");

        cards.forEach(card => {
          const category = card.getAttribute("data-category");
          if (filter === "all" || category === filter) {
            card.style.display = "block";
          } else {
            card.style.display = "none";
          }
        });
      });
    });
  });

    
    
    document.addEventListener('DOMContentLoaded', function() {
        let lastScrollTop = 0;
        let currentTestimonial = 1; // Start with center testimonial
        const totalTestimonials = 6;
        let testimonialInterval;
        
        // Fixed navbar on scroll
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const topBar = document.getElementById('topBar');
            const navbar = document.getElementById('mainNavbar');
          
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');
        const searchResults = document.getElementById('searchResults');
        const serviceCards = document.querySelectorAll('.service-card');

        function performSearch(query) {
            const results = [];
            serviceCards.forEach(card => {
                const titleEl = card.querySelector('.sky-card__title');
                const descEl = card.querySelector('.sky-card__desc');
                const title = titleEl ? titleEl.textContent.toLowerCase() : '';
                const desc = descEl ? descEl.textContent.toLowerCase() : '';
                const cat = card.getAttribute('data-category') || '';
                
                if (title.includes(query.toLowerCase()) || desc.includes(query.toLowerCase()) || cat.includes(query.toLowerCase())) {
                    card.style.display = 'block';
                    results.push({
                        title: titleEl ? titleEl.textContent.trim() : '',
                        description: descEl ? descEl.textContent.trim() : '',
                        link: card.querySelector('a') ? card.querySelector('a').getAttribute('href') : '#'
                    });
                } else {
                    card.style.display = 'none';
                }
            });
            return results;
        }

        function displaySearchResults(results) {
            if (!searchResults) return;
            if (results.length === 0) {
                searchResults.innerHTML = '<div class="p-2 text-muted small">No services found</div>';
            } else {
                searchResults.innerHTML = results.slice(0, 5).map(result => 
                    `<a href="${result.link}" class="d-block p-2 text-decoration-none border-bottom search-result-item" style="color: #333;">
                        <strong class="text-primary">${result.title}</strong><br>
                        <small class="text-muted">${result.description.substring(0, 70)}...</small>
                    </a>`
                ).join('');
            }
            searchResults.style.display = 'block';
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                if (query.length > 0) {
                    const results = performSearch(query);
                    displaySearchResults(results);
                } else {
                    if (searchResults) searchResults.style.display = 'none';
                    serviceCards.forEach(card => card.style.display = 'block');
                }
            });
        }

        if (searchBtn) {
            searchBtn.addEventListener('click', function() {
                const query = searchInput ? searchInput.value.trim() : '';
                if (query) {
                    performSearch(query);
                    const servicesSec = document.getElementById('services');
                    if (servicesSec) servicesSec.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }

        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (searchResults && searchInput && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });

        
        // Contact form submission
        // The quote form now really submits (InquiryController). Only disable
        // the button while it is sending, so it cannot be sent twice.
        (function () {
            var quoteForm = document.getElementById('quoteForm');
            if (!quoteForm || quoteForm.__skyBound) return;
            quoteForm.__skyBound = true;
            quoteForm.addEventListener('submit', function () {
                var btn = quoteForm.querySelector('.btn-submit');
                if (btn) { btn.disabled = true; btn.textContent = 'Sending...'; }
            });
        })();

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href !== '#') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });

        // Hero social icons functionality
        document.querySelectorAll('.hero-social-icon').forEach(icon => {
            icon.addEventListener('click', function() {
                const iconClass = this.querySelector('i').classList;
                if (iconClass.contains('fa-heart')) {
                    const heartIcon = this.querySelector('i');
                    if (heartIcon.classList.contains('far')) {
                        heartIcon.classList.remove('far');
                        heartIcon.classList.add('fas');
                        this.style.background = '#ff6b6b';
                        heartIcon.style.color = 'white';
                    } else {
                        heartIcon.classList.remove('fas');
                        heartIcon.classList.add('far');
                        this.style.background = 'white';
                        heartIcon.style.color = '#1a6fb0';
                    }
                } else if (iconClass.contains('fa-bell')) {
                    // (demo pop-up removed: the site has no notifications)
                    const badge = this.querySelector('.notification-badge');
                    if (badge) badge.style.display = 'none';
                }
            });
        });

        

        // View details button functionality
        document.querySelectorAll('.btn-view-details').forEach(btn => {
            btn.addEventListener('click', function() {
                const card = this.closest('.service-card');
                // (demo pop-up removed: the button had no real action)
            });
        });

        // Mega dropdown interactions
        

    });



//testimonial
document.addEventListener("DOMContentLoaded", () => {
    const track = document.getElementById("testimonialsTrack");
    if (!track) return; // no testimonials strip on this page
    const cards = Array.from(track.children);
    const cardCount = cards.length;

    let currentIndex = 0;
    let autoScrollInterval;
    let cardWidth;

    // Clone cards for seamless infinite scroll
    cards.forEach(card => {
        const clone = card.cloneNode(true);
        track.appendChild(clone);
    });

    const allCards = Array.from(track.children);

    function setCardWidth() {
        const screenWidth = window.innerWidth;
        const cardsVisible = screenWidth < 768 ? 1 : 3;
        cardWidth = track.offsetWidth / cardsVisible;
        allCards.forEach(card => {
            card.style.minWidth = `${cardWidth}px`;
        });
    }

    function updateScroll() {
        const totalCards = allCards.length;
        track.style.transition = "transform 0.6s ease";
        track.style.transform = `translateX(-${cardWidth * currentIndex}px)`;

        currentIndex++;

        // Reset position seamlessly
        if (currentIndex >= totalCards - 2) {
            setTimeout(() => {
                track.style.transition = "none";
                currentIndex = 0;
                track.style.transform = `translateX(0px)`;
            }, 2000);
        }
    }

    function startAutoScroll() {
        autoScrollInterval = setInterval(updateScroll, 2000);
    }

    function stopAutoScroll() {
        clearInterval(autoScrollInterval);
    }

    // Recalculate on resize
    window.addEventListener("resize", () => {
        setCardWidth();
        track.style.transition = "none";
        track.style.transform = `translateX(-${cardWidth * currentIndex}px)`;
    });

    // Pause on hover
    const section = document.querySelector(".testimonials-section");
    section.addEventListener("mouseenter", stopAutoScroll);
    section.addEventListener("mouseleave", startAutoScroll);

    // Init
    setCardWidth();
    startAutoScroll();
});

    
</script>
    
@endpush