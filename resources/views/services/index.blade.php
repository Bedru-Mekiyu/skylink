

@extends('layouts.master')

@section('title', 'Sky Link - IT Services in Ethiopia')
@section('description', 'Leading IT services company in Ethiopia, helping businesses succeed with innovative technology solutions.')

{{-- (An unclosed @section('additional-styles') used to sit here; it left an
     output buffer open on every request. The styles below use the stack.) --}}
@push('styles')
<style>
    /* Hero Section */
    .hero-section {
        background: #f8f9fa;
        min-height: 80vh;
        position: relative;
        overflow: hidden;
        padding: 60px 0;
    }

    .hero-content {
        position: relative;
        z-index: 2;
    }

    .hero-service-tag {
        color: #3B82F6;
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
        background: #3B82F6;
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
        background: #1E40AF;
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
        color: #3B82F6;
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
        border-color: #3B82F6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .search-btn {
        background: #3B82F6;
        color: white;
        border: none;
        padding: 15px 25px;
        border-radius: 0 8px 8px 0;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .search-btn:hover {
        background: #1E40AF;
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
        color: #3B82F6;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    /* Blue Circle Background */
    .hero-circle {
        position: absolute;
        right: -30px;
        top: 50%;
        transform: translateY(-50%);
        width: 700px;
        height: 700px;
        background: linear-gradient(135deg, #3B82F6, #1E40AF);
        border-radius: 50%;
        z-index: 1;
    }

    /* Hero Icons Container */
    .hero-icons-container {
        position: absolute;
        margin-left: 45rem;
        top: 60%;
        transform: translateY(-50%);
        width: 500px;
        height: 500px;
        z-index: 5;
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
        color: #3B82F6;
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

    /* Tech Icons */
    .tech-icon {
        position: absolute;
        width: 100px;
        height: 100px;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        animation: float 6s ease-in-out infinite;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .tech-icon:hover {
        transform: translateY(-5px) scale(1.05);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        animation-play-state: paused;
    }

    .tech-icon img {
        width: 50px;
        height: 50px;
    }

    /* Specific positioning for each tech icon */
    .tech-icon.laravel {
        top: 40px;
        right: 370px;
        animation-delay: 0s;
    }

    .tech-icon.angular {
        top: 70px;
        right: 130px;
        animation-delay: -2s;
    }

    .tech-icon.nodejs {
        top: 220px;
        right: 420px;
        animation-delay: -4s;
    }

    .tech-icon.vuejs {
        top: 370px;
        right: 250px;
        animation-delay: -1s;
    }

    .tech-icon.dotnet {
        top: 270px;
        right: 70px;
        animation-delay: -3s;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        25% { transform: translateY(-10px) rotate(1deg); }
        50% { transform: translateY(-5px) rotate(-1deg); }
        75% { transform: translateY(-15px) rotate(0.5deg); }
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
        background: #3B82F6;
        color: rgb(216, 219, 220);
        transform: translateY(-2px);
    }

    .service-card {
        background: white;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        margin-bottom: 30px;
        opacity: 1;
        transform: translateY(0);
    }

    .service-card.hidden {
        display: none !important;
    }

    .service-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .service-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        transition: all 0.3s ease;
    }

    .service-card:hover img {
        transform: scale(1.05);
    }

    .service-card-body {
        padding: 25px;
    }

    .service-card h5 {
        font-weight: 600;
        margin-bottom: 10px;
        color: #333;
    }

    .service-card p {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 20px;
    }

    .service-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    

    
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
    background-color: #0d6efd;
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
            background: #17a2b8;
            border-radius: 2px;
        }

        .line-short {
            width: 20px;
            height: 4px;
            background: #17a2b8;
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
            border: 3px solid #17a2b8;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #17a2b8;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.2);
        }

        .client-info h5 {
            color: #17a2b8;
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
        background: #3B82F6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
        color: white;
        font-size: 1.2rem;
    }

    .contact-form {
        background: rgb(26, 137, 255);
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
        
        .hero-circle,
        .hero-icons-container,
        .hero-social-icons {
            display: none;
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
        .hero-circle {
            right: -300px;
            width: 600px;
            height: 600px;
        }
        
        .hero-icons-container {
            width: 400px;
            height: 400px;
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
    <div class="hero-circle"></div>
    
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
        </div>
    </div>
    
    
    
    <!-- Tech Icons -->
    <div class="hero-icons-container">
        <div class="tech-icon laravel">
            <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/laravel/laravel-original.svg" alt="Laravel">
        </div>
        <div class="tech-icon angular">
            <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/angularjs/angularjs-original.svg" alt="Angular">
        </div>
        <div class="tech-icon nodejs">
            <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg" alt="Node.js">
        </div>
        <div class="tech-icon vuejs">
            <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/vuejs/vuejs-original.svg" alt="Vue.js">
        </div>
        <div class="tech-icon dotnet">
            <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/dot-net/dot-net-original.svg" alt=".NET">
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
      $categories = [
        'all' => 'All',
        'software-service' => 'Software Service',
        'smart-city' => 'Infrastructure',
        'safety-security' => 'Security',
        'enterprise-network' => 'Networking',
        'consulting-training' => 'Consulting',
        'ict-support' => 'ICT Support',
        'gps-tracking' => 'GPS Tracking',
        'datacenter-facility' => 'Data Center',
      ];
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
  <div class="row" id="service-cards">
    @php
      $services = [
        [
          'title' => 'Software Service',
          'desc' => 'Custom software solutions like web, mobile and enterprise platforms.',
            'img' => asset('assets/images/services/software service.jpg'),
          'category' => 'software-service',
        ],
        [
          'title' => 'Smart City & Infrastructure',
          'desc' => 'Innovative infrastructure with EV charging, IoT and city automation.',
            'img' => asset('assets/images/services/smart city.jpg'),
          'category' => 'smart-city',
        ],
        [
          'title' => 'Safety & Security',
          'desc' => 'Cybersecurity, surveillance systems, and access control solutions.',
            'img' => asset('assets/images/services/security.jpg'),
          'category' => 'safety-security',
        ],
        [
          'title' => 'Enterprise Network',
          'desc' => 'Secure, scalable LAN/WAN and cloud network infrastructure.',
          'category' => 'enterprise-network',
          'img' => asset('assets/images/services/enterprise networking.png'),
        ],
        [
          'title' => 'Consulting & Training',
          'desc' => 'IT consulting, professional training, and strategy workshops.',
          'img' => asset('assets/images/services/training-consulting.jpg'),
          'category' => 'consulting-training',
        ],
        [
          'title' => 'ICT Support',
          'desc' => '24/7 technical support and remote system troubleshooting.',
          'img' => asset('assets/images/services/ict support.jpg'),
          'category' => 'ict-support',
        ],
        [
          'title' => 'GPS Tracking',
          'desc' => 'Fleet and asset tracking with real-time GPS and analytics.',
          'img' => asset('assets/images/services/gps-tracking.png'),
          'category' => 'gps-tracking',
        ],
        [
          'title' => 'Data Center & Power',
          'desc' => 'Secure data centers with backup power and climate control.',
          'img' => asset('assets/images/services/datacenter.png'),
          'category' => 'datacenter-facility',
        ],
      ];
    @endphp

    @foreach($services as $service)
  <div class="col-md-6 col-lg-3 mb-4 service-card" data-category="{{ $service['category'] }}">
    <div class="card h-100 shadow-sm border-0 overflow-hidden">
      <div style="aspect-ratio: 16/9; overflow: hidden;">
        <img src="{{ $service['img'] }}" alt="{{ $service['title'] }}" class="w-100 h-90 object-fit-cover">
      </div>
      <div class="card-body d-flex flex-column">
        <h5 class="card-title fw-bold">{{ $service['title'] }}</h5>
        <p class="card-text flex-grow-1">{{ $service['desc'] }}</p>
        <a href="{{ route('services.category', ['category' => $service['category']]) }}"
           class="text-decoration-none text-primary fw-semibold mt-2">
          Learn more →
        </a>
      </div>
    </div>
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
<section class="testimonials-section">
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
                <div class="carousel-track" id="carouselTrack">
                    <!-- First set of cards -->
                    <div class="testimonial-card">
                        <div class="profile-section">
                            <div class="profile-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="client-info">
                                <h5>Ibrahim Mohammed</h5>
                                <p class="profession">CUSTOMER</p>
                            </div>
                        </div>
                        <p class="testimonial-text">
                Skylink Technologies exceeded our expectations with their technical expertise and timely delivery. Their solutions are well-structured and scalable, making them ideal for any growing business.
            </p>
                    </div>

                    <div class="testimonial-card">
                        <div class="profile-section">
                            <div class="profile-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="client-info">
                                <h5>Aisha Malik</h5>
                                <p class="profession">CUSTOMER</p>
                            </div>
                        </div>
                        <p class="testimonial-text">
                Working with Skylink has been a game-changer. Their team helped digitize our operations with a custom e-commerce platform that increased our efficiency and customer satisfaction.
            </p>
                    </div>

                    <div class="testimonial-card">
                        <div class="profile-section">
                            <div class="profile-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="client-info">
                                <h5>Khalid Kibrom</h5>
                                <p class="profession">CUSTOMER</p>
                            </div>
                        </div>
                        <p class="testimonial-text">
                The collaboration with Skylink was smooth and productive. Their developers understood our vision and turned it into a responsive, user-friendly mobile app within a short timeframe.
            </p>
                    </div>

                    <div class="testimonial-card">
                        <div class="profile-section">
                            <div class="profile-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="client-info">
                                <h5>Zain Karim</h5>
                                <p class="profession">CUSTOMER</p>
                            </div>
                        </div>
                        <p class="testimonial-text">
                I was impressed by the professionalism and depth of knowledge at Skylink. Their network solutions significantly improved our infrastructure performance and security.
            </p>
                    </div>

                    <div class="testimonial-card">
                        <div class="profile-section">
                            <div class="profile-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="client-info">
                                <h5>Kalkidan Gezu</h5>
                                <p class="profession">CUSTOMER</p>
                            </div>
                        </div>
                        <p class="testimonial-text">
                Skylink helped transform our business with a beautiful, easy-to-manage website. Their support team is always available and attentive to our needs.
            </p>
                    </div>

                    <div class="testimonial-card">
                        <div class="profile-section">
                            <div class="profile-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="client-info">
                                <h5>Taye Abrar</h5>
                                <p class="profession">CUSTOMER</p>
                            </div>
                        </div>
                        <p class="testimonial-text">
                Their commitment to quality and innovation is what sets Skylink apart. I’d recommend them to anyone looking to build a reliable tech solution.
            </p>
                    </div>
                </div>
            </div>

        </div>
</section>

<!-- Contact Section -->
<section class="contact-section" id="contact">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
            <h5 class="mb-4" style="font-weight: 500; color: #1076f3;">REQUEST A QUOTE</h5>

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
                                <p style="color: #3B82F6; font-weight: 600; margin: 0;">+251-974-967171</p>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
        const serviceItems = document.querySelectorAll('.service-item');

        function performSearch(query) {
            const results = [];
            serviceItems.forEach(item => {
                const title = item.querySelector('h5').textContent.toLowerCase();
                const description = item.querySelector('p').textContent.toLowerCase();
                const keywords = item.getAttribute('data-keywords').toLowerCase();
                
                if (title.includes(query.toLowerCase()) || 
                    description.includes(query.toLowerCase()) || 
                    keywords.includes(query.toLowerCase())) {
                    results.push({
                        title: item.querySelector('h5').textContent,
                        description: item.querySelector('p').textContent,
                        element: item
                    });
                }
            });
            return results;
        }

        function displaySearchResults(results) {
            if (results.length === 0) {
                searchResults.innerHTML = '<div class="search-result-item">No services found</div>';
            } else {
                searchResults.innerHTML = results.map(result => 
                    `<div class="search-result-item" data-title="${result.title}">
                        <strong>${result.title}</strong><br>
                        <small>${result.description.substring(0, 80)}...</small>
                    </div>`
                ).join('');
            }
            searchResults.style.display = 'block';
        }

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length > 0) {
                const results = performSearch(query);
                displaySearchResults(results);
            } else {
                searchResults.style.display = 'none';
            }
        });

        searchBtn.addEventListener('click', function() {
            const query = searchInput.value.trim();
            if (query) {
                const results = performSearch(query);
                displaySearchResults(results);
                
                // Filter services based on search and rearrange
                filterAndRearrangeServices(results.map(r => r.element));
                
                // Scroll to services section
                document.getElementById('services').scrollIntoView({ behavior: 'smooth' });
            }
        });

        // Click on search results
        searchResults.addEventListener('click', function(e) {
            if (e.target.classList.contains('search-result-item')) {
                const title = e.target.getAttribute('data-title');
                searchInput.value = title;
                searchResults.style.display = 'none';
                
                // Filter and highlight the selected service
                const matchingItems = [];
                serviceItems.forEach(item => {
                    if (item.querySelector('h5').textContent === title) {
                        matchingItems.push(item);
                    }
                });
                
                filterAndRearrangeServices(matchingItems);
                document.getElementById('services').scrollIntoView({ behavior: 'smooth' });
            }
        });

        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });

        // Function to filter and rearrange services
        function filterAndRearrangeServices(visibleItems) {
            const servicesGrid = document.getElementById('servicesGrid');
            
            // Hide all items first
            serviceItems.forEach(item => {
                item.classList.add('hidden');
            });
            
            // Clear the grid and re-append only visible items
            const allItems = Array.from(serviceItems);
            allItems.forEach(item => {
                if (servicesGrid.contains(item)) {
                    servicesGrid.removeChild(item);
                }
            });
            
            // Add visible items back to the grid in order
            visibleItems.forEach(item => {
                item.classList.remove('hidden');
                servicesGrid.appendChild(item);
            });
        }

        // Service tabs functionality - Updated to rearrange services
        document.querySelectorAll('.service-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.service-tab').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                const visibleItems = [];
                
                serviceItems.forEach(item => {
                    const category = item.getAttribute('data-category');
                    const title = item.querySelector('h5').textContent.toLowerCase();
                    
                    // Check if filter matches category or if it's "all"
                    let shouldShow = false;
                    
                    if (filter === 'all') {
                        shouldShow = true;
                    } else if (category === filter) {
                        shouldShow = true;
                    } else {
                        // Also check if the filter word is in the title
                        shouldShow = title.includes(filter.toLowerCase());
                    }
                    
                    if (shouldShow) {
                        visibleItems.push(item);
                    }
                });
                
                if (filter === 'all') {
                    // Show all items in original order
                    const servicesGrid = document.getElementById('servicesGrid');
                    const allItems = Array.from(serviceItems);
                    
                    // Clear grid
                    allItems.forEach(item => {
                        if (servicesGrid.contains(item)) {
                            servicesGrid.removeChild(item);
                        }
                    });
                    
                    // Re-add all items in original order
                    allItems.forEach(item => {
                        item.classList.remove('hidden');
                        servicesGrid.appendChild(item);
                    });
                } else {
                    filterAndRearrangeServices(visibleItems);
                }
                
                // Clear search when filtering
                searchInput.value = '';
                searchResults.style.display = 'none';
            });
        });

        // Categories dropdown filtering - Updated to rearrange services
        document.querySelectorAll('.categories-dropdown + .dropdown-menu .dropdown-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const filter = this.getAttribute('data-filter');
                
                // Update active tab
                document.querySelectorAll('.service-tab').forEach(t => t.classList.remove('active'));
                const matchingTab = document.querySelector(`[data-filter="${filter}"]`);
                if (matchingTab) {
                    matchingTab.classList.add('active');
                }
                
                // Filter services and rearrange
                const visibleItems = [];
                serviceItems.forEach(serviceItem => {
                    const category = serviceItem.getAttribute('data-category');
                    const title = serviceItem.querySelector('h5').textContent.toLowerCase();
                    
                    let shouldShow = false;
                    
                    if (category === filter) {
                        shouldShow = true;
                    } else {
                        // Check if the filter word is in the title
                        shouldShow = title.includes(filter.toLowerCase());
                    }
                    
                    if (shouldShow) {
                        visibleItems.push(serviceItem);
                    }
                });
                
                filterAndRearrangeServices(visibleItems);
                
                // Scroll to services
                document.getElementById('services').scrollIntoView({ behavior: 'smooth' });
            });
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
                        heartIcon.style.color = '#3B82F6';
                    }
                } else if (iconClass.contains('fa-shopping-cart')) {
                    alert('Shopping cart clicked!');
                } else if (iconClass.contains('fa-bell')) {
                    alert('You have 3 new notifications!');
                    const badge = this.querySelector('.notification-badge');
                    if (badge) badge.style.display = 'none';
                }
            });
        });

        

        // View details button functionality
        document.querySelectorAll('.btn-view-details').forEach(btn => {
            btn.addEventListener('click', function() {
                const card = this.closest('.service-card');
                const title = card.querySelector('h5').textContent;
                alert(`Viewing details for: ${title}`);
            });
        });

        // Mega dropdown interactions
        

        console.log('All functionality loaded successfully!');
    });



//testimonial
document.addEventListener("DOMContentLoaded", () => {
    const track = document.getElementById("testimonialsTrack");
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