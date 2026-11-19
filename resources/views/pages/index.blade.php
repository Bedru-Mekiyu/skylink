@extends('layouts.master')

@section('title', 'Homepage - Sky Link IT Solutions')
@section('description', 'Explore our portfolio of successful IT projects and digital solutions delivered to clients across Ethiopia.')

@push('styles')
    <style>
        :root {
            --primary-blue:rgb(63, 122, 249);
            --dark-blue: #4a90e2;
            --light-blue:rgb(212, 224, 244);
            --gray-bg: #f8f9fa;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            height: 100vh; /* Full viewport height */
            background-image: url('../assets/images/27075.jpg');
            background-size: cover;      /* Cover entire section */
            background-position: center; /* Center the image */
            background-repeat: no-repeat;
            color: white; /* Text color (adjust if needed) */
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.12;
        }

.hero-content {
            position: relative;
            z-index: 2;
            max-width: 600px;
        }

        .hero h1 {
            font-size: 3.4rem;
            font-weight: 700;
            padding-top: 10px;
            margin-bottom: 1.8rem;
            line-height: 1.15;
            color:white;
        }

        .hero p {
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
            opacity: 0.92;
            line-height: 1.6;
            collor: white;
        }

        .btn-get-started {
        background: linear-gradient(135deg, #3B82F6,rgb(72, 113, 248));
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    /* Centre the hero "More" button under the text. display: table keeps the
       button's own width (natural on desktop, 50% from the layout on smaller
       screens) while auto margins centre it. This also keeps it clear of the
       fixed contact launcher at the bottom left. */
    .hero .btn-get-started {
        display: table;
        margin-left: auto;
        margin-right: auto;
    }

    .btn-get-started:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        color: white;
    }

        .hero-btn {
            background: white;
            color: var(--primary-blue);
            padding: 0.9rem 2.2rem;
            border: none;
            border-radius: 20px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .hero-btn:hover {
            transform: translateY(-2px);
            color: var(--primary-blue);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        /* Company Overview */
        .stats-section {
            background: var(--primary-blue);
            color: white;
            padding: 30px 0;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            display: block;
        }

        /* Trusted Partners */
        .partners-section {
            padding: 20px 0;
            background: #ffffff;
        }

        .partner-logo {
            height: 90px;
            object-fit: cover;
            border-radius:50%;
            transition: filter 0.3s;
        }

        .partner-logo:hover {
            filter: grayscale(0%);
        }

        /* Partners Slider */
.partners-slider {
    overflow: hidden;
    white-space: nowrap;
}

.partners-track {
    display: inline-flex;
    animation: slidePartners 20s linear infinite;
}

.partners-track .partner-logo {
    margin: 0 30px;
    flex-shrink: 0;
}

@keyframes slidePartners {
    0% { transform: translateX(0); }
    100% { transform: translateX(-78%); }
}
/* Pause animations on hover */
.partners-track:hover,
.products-track:hover,
.testimonials-track:hover {
    animation-play-state: paused;
}

        /* Services Section */
        .services {
            padding: 2rem 0;
            background: var(--gray-bg);
        }

        .services-header {
  text-align: left;
  margin-bottom: 60px;
}

.services-label {
  color: #4a90e2;
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 2px;
  margin-bottom: 10px;
  position: relative;
}

.services-label::after {
  content: "";
  position: absolute;
  left: 80px;
  top: 50%;
  transform: translateY(-50%);
  width: 60px;
  height: 2px;
  background-color: #4a90e2;
}

.services-title {
  font-size: 40px;
  font-weight: 700;
  color: #333;
  margin: 0;
}

        /* Services: continuous marquee, same technique as the testimonials and Featured Products
           (a track holding the cards twice, translated by -50% in a linear infinite loop, paused on hover).
           --w card width and --g gap are unitless px so the duration keeps ~80px/s, the testimonials' speed. */
        /* The window is exactly three cards wide (3 cards + 2 gaps), so the middle one is the highlighted card with a neighbour each side. */
        .services-marquee { overflow: hidden; padding: 34px 0; max-width: calc((3 * var(--w) + 2 * var(--g)) * 1px); margin-inline: auto; --w: 300; --g: 32; }

        .services-track {
            display: flex;
            width: max-content;
            animation: slideServices calc(var(--n) * (var(--w) + var(--g)) / 80 * 1s) linear infinite;
        }

        .services-marquee:hover .services-track {
            animation-play-state: paused;
        }

        @keyframes slideServices {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        .service-card {
            flex: 0 0 calc(var(--w) * 1px);
            width: calc(var(--w) * 1px);
            margin: 0 calc(var(--g) * 0.5px);
            background: var(--light-blue);
            color: #333;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.5s ease, background-color 0.5s ease, color 0.5s ease, box-shadow 0.5s ease;
            position: relative;
        }

        .service-card:hover {
            background: var(--dark-blue);
            color: white;
            transform: scale(1.06);
            z-index: 2;
            box-shadow: 0 15px 40px #1e40af;
        }

        .service-image {
            height: 110px;
            background: rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: rgba(0,0,0,0.15);
        }

        .service-card:hover .service-image {
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.4);
        }

        /* The card passing through the middle of the section (set by the script below, one at a time).
           Same sky-blue gradient as the site's buttons (.btn-view-more: #3a7bd5 -> rgb(0,136,255)), each stop darkened
           just enough that white text reaches 4.6:1 (AA needs 4.5:1). Only transform, colours and shadow change,
           so nothing moves in the layout. */
        .service-card.is-center {
            --hl-from: #3775ca;
            --hl-to: #0074d9;
            background: linear-gradient(135deg, var(--hl-from), var(--hl-to));
            color: #fff;
            transform: translateY(-6px) scale(1.12); /* steps forward out of the row */
            z-index: 3;
            box-shadow: 0 14px 28px rgba(0, 116, 217, 0.4); /* stays inside the section's padding, which clips overflow */
        }

        .service-card.is-center .service-image {
            background: rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.9);
        }

        .service-card.is-center .service-content p {
            opacity: 1;
        }

        .service-content {
            padding: 1.25rem 1.5rem 1.5rem;
            text-align: center;
        }

        .service-content h4 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.6rem;
        }

        .service-content p {
            font-size: 0.9rem;
            line-height: 1.55;
            opacity: 0.9;
        }

        /* Reduced motion: no sliding; the cards sit still, scrollable by hand, shown once. */
        @media (prefers-reduced-motion: reduce) {
            .services-track { animation: none; }
            .services-marquee { overflow-x: auto; }
            .services-marquee .marquee-dup { display: none; }
        }

        /* Latest News */
        .news-section {
            padding: 2rem 0;
            background-color: #ffffff;
        }
        
        .news-header {
            margin-bottom: 50px;
        }
        
        .news-tab {
            color: #3B82F6;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .news-tab::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 3px;
            background-color: #2196F3;
        }
        
        .news-title {
            font-size: 40px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        
        .news-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            margin-bottom: 30px;
            cursor: pointer;
            height:100%;
        }
        
        .news-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .card-image {
            position: relative;
            height: 180px;
            overflow: hidden;
        }
        
        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .category-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #2196F3;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card-content {
            padding: 20px;
        }
        
        .card-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            color: #666;
            font-size: 13px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .meta-item i {
            color: #2196F3;
            font-size: 14px;
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 12px;
            line-height: 1.3;
        }
        
        .card-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .read-more {
            color: #2196F3;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .read-more:hover {
            color: #1976D2;
            gap: 12px;
        }
        
        .read-more i {
            transition: transform 0.3s ease;
        }
        
        .read-more:hover i {
            transform: translateX(4px);
        }
        .news-link-wrapper {
    text-decoration: none;
    color: inherit;
}
.news-link-wrapper * {
    text-decoration: none !important;
}

        
        @media (max-width: 768px) {
            .news-title {
                font-size: 36px;
            }
            
            .card-content {
                padding: 15px;
            }
            
            .card-title {
                font-size: 18px;
            }
            
            .card-image {
                height: 160px;
            }

        }
        /* Featured Portfolio */
        .portfolio-section {
            padding: 2rem 0;
            background: #f8f9fa;
        }

        .portfolio-header {
  text-align: left;
  margin-bottom: 60px;
}

.portfolio-label {
  color: #4a90e2;
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 2px;
  margin-bottom: 10px;
  position: relative;
}

.portfolio-label::after {
  content: "";
  position: absolute;
  left: 90px;
  top: 50%;
  transform: translateY(-50%);
  width: 60px;
  height: 2px;
  background-color: #4a90e2;
}

.portfolio-title {
  font-size: 40px;
  font-weight: 700;
  color: #333;
  margin: 0;
}

        .portfolio-item {
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 20px;
            position: relative;
            cursor: pointer;
        }

        ,.portfolio-item h1{
            margin-bottom: 10px;
        }

        .portfolio-item img {
            width: 90%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .portfolio-item:hover img {
            transform: scale(1.1);
        }
        /* Portfolio Overlay */
.portfolio-item {
    position: relative;
    overflow: hidden;
}

.portfolio-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to right, #5f74ff, #bbcaf3);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.portfolio-item:hover .portfolio-overlay {
    opacity: 1;
}

.portfolio-content {
    text-align: center;
    padding: 20px;
}

        /* Featured Products */
 .products-section {
            padding: 2rem 0;
        }
         .product-header {
  text-align: left;
  margin-bottom: 60px;
}

.product-label {
  color: #4a90e2;
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 2px;
  margin-bottom: 10px;
  position: relative;
}

.product-label::after {
  content: "";
  position: absolute;
  left: 100px;
  top: 50%;
  transform: translateY(-50%);
  width: 60px;
  height: 2px;
  background-color: #4a90e2;
}

.product-title {
  font-size: 40px;
  font-weight: 700;
  color: #333;
  margin: 0;
}

        .product-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        /* Products Slider */
.products-slider {
    overflow: hidden;
    padding: 20px 0;
}

.products-track {
    display: flex;
    animation: slideProducts 10s linear infinite;
}

.products-track .product-card {
    min-width: 300px;
    margin: 0 20px;
    flex-shrink: 0;
}


.btn-view-more {
        background: linear-gradient(135deg, #3a7bd5,rgb(0, 136, 255));
        color: white;
        border: none;
        padding: 12px 30px;
        font-size: 1rem;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(58, 123, 213, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .btn-view-more:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(58, 123, 213, 0.4);
    }

@keyframes slideProducts {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

/*testimonials-section*/
    .testimonials-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .section-title {
            font-size: 40px;
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
            margin-top: 10px;
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
            padding: 20px 25px;
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

        /* Newsletter */
        .newsletter {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 3rem 0;
            text-align: center;
        }

        .newsletter h2 {
            font-size: 2.4rem;
            font-weight: 600;
            margin-bottom: 1.2rem;
        }

        .newsletter p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 3rem;
        }

        .newsletter-form {
            max-width: 450px;
            margin: 0 auto;
        }

        .newsletter-input {
            width: 100%;
            padding: 1.2rem 1.8rem;
            border: none;
            border-radius: 50px;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            outline: none;
        }

        .newsletter-btn {
            background: white;
            color: var(--primary-blue);
            padding: 1.2rem 3.5rem;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .newsletter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

       

/* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .stats-container {
                flex-direction: column;
                gap: 2rem;
            }
            
            .stat-item::after {
                display: none;
            }
            
.portfolio-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
        }
        
        
                @media (max-width: 568px) {

                     /* Company Overview */
        .stats-section {
            padding: 20px 0;
        }

        .stat-item {
            text-align: center;
            font-size: 1 rem;
        }

        .stat-number {
            font-size: 1.5rem;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 600px;
        }

        .hero h1 {
            font-size: 2rem;
            font-weight: 400;
            padding-top: 2px;
            margin-top: -20px;
            margin-bottom: 1rem;
            line-height: 1.15;
        }

        .hero p {
            font-size: 1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .hero {
max-height: 20 vh;            
           
            
        }
        .partners-track {
    display: inline-flex;
    animation: slidePartners 20s linear infinite;
}

.partners-track .partner-logo {
    margin: 0 15px;
    flex-shrink: 0;
}
 .services-marquee {
        --w: 280;
        --g: 20;
    }

    .service-card {
        border-radius: 16px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }
 
    .service-image {
        height: 110px; /* Reduce image height */
        font-size: 2.5rem;
    }

    .service-content {
        padding: 1.5rem 1rem;
    }

    .service-content h4 {
        font-size: 1.2rem;
        margin-bottom: 0.8rem;
    }

    .service-content p {
        font-size: 0.875rem;
        line-height: 1.6;
    }

    .news-card {
        max-width: 90%;
        margin-left: auto;
        margin-right: auto;
    }
    .products-track {
        display: flex;
    animation: slideProducts 10s linear infinite;
        animation-duration: 5s !important; /* Faster scroll on mobile */
    }

    .products-track .product-card {
        min-width: 120px !important; /* Smaller cards to fit more */
        margin: 0 8px !important;
        padding: 15px !important;
    }

    .product-card img {
        height: 100px !important;
    }

    .product-card h5 {
        font-size: 14px !important;
    }

    .product-card p {
        font-size: 12px !important;
    }
    .testimonial-card {
        padding: 10px 7px; /* Reduce padding */
        margin: 5px auto;  /* Center cards and reduce side margin */
    }

    .profile-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
        margin-right: 15px;
    }

    .client-info h5 {
        font-size: 1rem;
    }

    .testimonial-text {
        font-size: 0.85rem;
    }
}
    </style>
@endpush
    
@section('content')
    <!-- Floating Social Icons -->
    {{-- The right-side "Quick Contact" (phone) and "Telegram Chat" buttons were
         removed: the contact launcher (bottom left) has the same phone and
         Telegram links, and the chat button sits bottom right. --}}




    @php
        $anniversaryHome = \App\Support\Anniversary::isActive();
    @endphp
    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="hero-content">
                        
                        <h1>Helping businesses succeed with the best IT services in Ethiopia</h1>
                        <p>Empowering businesses with cutting-edge technology solutions. We provide comprehensive IT services to help your business grow and thrive in the digital age.</p>
                        <a href="#services" class="btn-get-started">More</a>
                    </div>
                </div>
                @if ($anniversaryHome)
                    <div class="col-lg-4 d-none d-lg-block anniv5-hero-slot">
                        <x-anniversary.seal variant="feature" />
                    </div>
                @endif
            </div>
        </div>
        @include('partials.hero-media')
    </section>

    {{-- Below lg the hero is a fixed 100vh with overflow hidden, so the panel follows it instead. --}}
    @if ($anniversaryHome)
        <div class="anniv5-home-band d-lg-none">
            <div class="container">
                <x-anniversary.seal variant="feature" />
            </div>
        </div>
    @endif



<!-- Company Overview -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <h2 class="mb-4">Company overview</h2>
                <p>Sky Link is a leading IT services company in Ethiopia, providing innovative solutions to businesses of all sizes. With our experienced team and cutting-edge technology, we help organizations achieve their digital transformation goals and stay competitive in today's market.</p>
            </div>
            <div class="col-lg-6">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="stat-item">
                            <div class="stat-number" data-target="4+">0</div>
                            <small>Years Experience</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-item">
                            <div class="stat-number" data-target="400+">0</div>
                            <small>Happy Clients</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-item">
                            <div class="stat-number" data-target="100+">0</div>
                            <small>Projects Done</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- Trusted Partners -->
<section class="partners-section">
    <div class="container">
        <h5 class="h5 text-uppercase text-center fw-semibold mb-4 text-dark">Trusted Partners</h5>
        <div class="partners-slider">
            <div class="partners-track">
                <img src="{{ asset('assets/images/minbertv.webp') }}" alt="Minber" class="partner-logo">
                <img src="{{ asset('assets/images/iman.webp') }}" alt="iman" class="partner-logo">
                <img src="{{ asset('assets/images/Ethiotel.jpg') }}" alt="ethiotel" class="partner-logo">
                <img src="{{ asset('assets/images/Safaricom.png') }}" alt="safaricom" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/ad.png') }}" alt="ad" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/amar.jpg') }}" alt="amar" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/amin.png') }}" alt="amin" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/basira.png') }}" alt="basira" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/bilalul.png') }}" alt="bilalul" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/kasma.png') }}" alt="kasma" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/mslegue.JPG') }}" alt="Msleguer" class="partner-logo">
                <img src="{{ asset('assets/images/newleaf.webp') }}" alt="newleaf" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/nujum.jpg') }}" alt="nujum" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/popular.png') }}" alt="poplar" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/ethioceramics.jpg') }}" alt="ethioceramics" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/softvalley.jpg') }}" alt="softvalley" class="partner-logo">
                <img src="{{ asset('assets/images/befan.jpg') }}" alt="befam" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/ukaz.jpg') }}" alt="ukaz" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/sms.png') }}" alt="sms" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/unknown.PNG') }}" alt="unknown" class="partner-logo">
                <img src="{{ asset('assets/images/evergreen.jfif') }}" alt="evergreen" class="partner-logo">
                <img src="{{ asset('assets/images/aauLogo.png') }}" alt="aau" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/diredawa.png') }}" alt="diredawa" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/addis.png') }}" alt="addis" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/alfa.jpeg') }}" alt="alfa" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/hilco.png') }}" alt="hilco" class="partner-logo">
                <img src="{{ asset('assets/images/trusted/maroli.JPG') }}" alt="Maroli" class="partner-logo">

            </div>
        </div>
    </div>
</section>

    <!-- Services Section -->
    <section class="services" id="services">
        <div class="container">
            <div class="services-header">
                <div class="services-label">SERVICES</div>
                <h2 class="services-title">Our Services</h2>
            </div>
            @php
                $services = [
                    ['icon' => 'fas fa-laptop-code', 'title' => 'Web Development', 'desc' => 'We create modern, responsive websites and web applications using the latest technologies and best practices to ensure optimal performance and exceptional user experience for your business.'],
                    ['icon' => 'fas fa-mobile-alt', 'title' => 'Mobile Apps', 'desc' => 'End-to-end mobile app development for iOS and Android that delivers seamless user experiences and powerful functionality.'],
                    ['icon' => 'fas fa-shield-alt', 'title' => 'Physical Safety and Security', 'desc' => 'Integrated physical security systems including surveillance, access control, and alarm solutions to safeguard your assets.'],
                    ['icon' => 'fas fa-shield-alt', 'title' => 'Enterprise Network Solution', 'desc' => 'Scalable and secure network infrastructure solutions designed to meet the demands of growing enterprise environments.'],
                    ['icon' => 'fas fa-database', 'title' => 'ERP Consultancy', 'desc' => 'Expert guidance on ERP system selection, implementation, and optimization to streamline your business operations.'],
                    ['icon' => 'fas fa-cogs', 'title' => 'ICT Support', 'desc' => 'Reliable and timely technical support services to ensure your IT systems run smoothly with minimal downtime.'],
                    ['icon' => 'fas fa-database', 'title' => 'Smart City Projects', 'desc' => 'Innovative smart city technologies that enhance urban living through intelligent infrastructure and data-driven services.'],
                ];
            @endphp
            <div class="services-marquee" style="--n: {{ count($services) }}">
                <div class="services-track">
                    @foreach ([0, 1] as $copy)
                        @foreach ($services as $service)
                            <div class="service-card{{ $copy ? ' marquee-dup' : '' }}" @if ($copy) aria-hidden="true" @endif>
                                <div class="service-image">
                                    <i class="{{ $service['icon'] }}" aria-hidden="true"></i>
                                </div>
                                <div class="service-content">
                                    <h4>{{ $service['title'] }}</h4>
                                    <p>{{ $service['desc'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
            <script>
                // Highlight the service card passing through the middle of the section, one at a time.
                // One requestAnimationFrame loop reads the track position once per frame and picks the card whose
                // centre is nearest the middle. Each service exists twice in the track (for the seamless loop) and the
                // two copies are half a track apart, so both copies carry the class: at the loop point the track jumps
                // by exactly one copy and the highlighted card is already in place (no flicker). Only one of the pair
                // is ever on screen. The loop runs only while the section is on screen and the tab is visible. With
                // reduced motion the cards stand still and nothing is highlighted.
                (function () {
                    var box = document.querySelector('.services-marquee');
                    var track = box && box.querySelector('.services-track');
                    if (!track || (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches)) return;
                    var cards = Array.prototype.slice.call(track.children), half = cards.length / 2, centers = [], active = -1, raf = 0;
                    var onScreen = !('IntersectionObserver' in window);

                    function measure() { centers = cards.map(function (c) { return c.offsetLeft + c.offsetWidth / 2; }); }
                    function mark(i, on) {
                        cards[i].classList.toggle('is-center', on);
                        cards[i + half].classList.toggle('is-center', on);
                    }
                    function pick() {
                        var x = track.getBoundingClientRect().left - box.getBoundingClientRect().left;
                        var mid = box.clientWidth / 2, best = 0, bestD = Infinity;
                        for (var i = 0; i < centers.length; i++) {
                            var d = Math.abs(x + centers[i] - mid);
                            if (d < bestD) { bestD = d; best = i; }
                        }
                        best %= half;
                        if (best !== active) {
                            if (active >= 0) mark(active, false);
                            active = best;
                            mark(active, true);
                        }
                    }
                    function frame() { pick(); raf = requestAnimationFrame(frame); }
                    function update() {
                        cancelAnimationFrame(raf);
                        raf = 0;
                        if (onScreen && !document.hidden) frame();
                    }

                    measure();
                    window.addEventListener('resize', measure);
                    document.addEventListener('visibilitychange', update);
                    if ('IntersectionObserver' in window) {
                        new IntersectionObserver(function (entries) { onScreen = entries[0].isIntersecting; update(); }).observe(box);
                    } else {
                        update();
                    }
                })();
            </script>

            <div class="text-center mt-5">
            <a href="{{ route('services') }}" class=" btn-view-more">
                View More Services
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        </div>
    </section>






    <!-- Latest News -->
<section class="news-section">
    <div class="container">
        <div class="news-header">
            <div class="news-tab">NEWS</div>
            <h2 class="news-title">Latest News</h2>
        </div>
        
        <div class="row">
            @forelse($latestNews as $news)
            <div class="col-lg-4 col-md-6">
                <article class="news-card">
                    <a href="{{ route('news.show', $news->slug) }}" class="news-link-wrapper">
                        <div class="card-image">

                            <img src="{{ $news->thumb_url }}" alt="{{ $news->title }}" loading="lazy">

                            <div class="category-badge">{{ $news->category->name ?? 'News' }}</div>
                        </div>
                        
                        <div class="card-content">
                            <div class="card-meta">
                                <div class="meta-item">
                                    <i class="fas fa-user"></i>
                                    {{-- Assuming an author relationship or field, otherwise defaults to 'Admin' --}}
                                    <span>{{ $news->author->name ?? 'Admin' }}</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    {{-- Format the date --}}
                                    <span>{{ $news->display_date->format('d M Y') }}</span>
                                </div>
                            </div>
                            
                            <h3 class="card-title">{{ $news->title }}</h3>

                            <p class="card-description">{{ Str::limit($news->excerpt, 120) }}</p>
                            
                            <span class="read-more">
                                Read More
                                <i class="fas fa-arrow-right"></i>
                            </span>
                        </div>
                    </a>
                </article>
            </div>
            @empty
            <div class="col-12 text-center">
                <p class="text-muted">No news available at the moment.</p>
            </div>
            @endforelse
        </div>
    </div>
</section>

<!-- Featured Portfolio -->
<section id="portfolio" class="portfolio-section">
    <div class="container">
        <div class="portfolio-header">
            <div class="portfolio-label">PROJECTS</div>
            <h2 class="portfolio-title">Featured Portfolio</h2>
        </div>
        @push('styles')
            @include('partials.portfolio.styles')
        @endpush
        <div class="row g-4">
            @forelse($latestPortfolios as $portfolio)
            <div class="col-lg-4 col-md-6">
                @include('partials.portfolio.card', ['project' => $portfolio])
            </div>
            @empty
            <div class="col-12 text-center">
                <p class="text-muted">No projects available at the moment.</p>
            </div>
            @endforelse
        </div>
        <div class="text-center mt-5">
            <a href="{{ route('portfolio') }}" class="btn-view-more">
                More Projects
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>



     <!-- Featured Products -->
<section class="products-section">
    <div class="container">
        <div class="product-header">
                <div class="product-label">PRODUCTS</div>
                <h2 class="product-title">Featured Products</h2>
        </div>
        <div class="products-slider">
            <div class="products-track">
                @php
                    $products = [
                        ['name' => 'Cctv Camera', 'description' => 'High-definition surveillance camera', 'image' => asset('assets/images/cctv.jfif'), 'rating' => 5],
                        ['name' => 'Attendance Tracker', 'description' => 'Biometric and RFID-based attendance', 'image' => asset('assets/images/attendance.jfif'), 'rating' => 4],
                        ['name' => 'Security Camera', 'description' => 'Advanced indoor/outdoor camera', 'image' => asset('assets/images/cctv3.jfif'), 'rating' => 5],
                        ['name' => 'Ecommerce Application', 'description' => 'Custom-built online store solutions', 'image' => asset('assets/images/ecommerce.jfif'), 'rating' => 5],
                        ['name' => 'Cctv Camera', 'description' => 'Durable and weatherproof CCTV system', 'image' => asset('assets/images/cctv4.jfif'), 'rating' => 4]
                    ];
                @endphp
                
                @foreach(array_merge($products, $products) as $product)
                <div class="product-card">
                    <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="img-fluid mb-3" style="height: 200px; object-fit: contain;">
                    <h5>{{ $product['name'] }}</h5>
                    <p class="text-muted">{{ $product['description'] }}</p>
                    
                </div>
                @endforeach
            </div>
            
        </div>
        <div class="text-center mt-5">
            <a href="https://kirbgebeya.com/" class=" btn-view-more">
                View More Products
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Client Testimonials -->
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

    <!-- Newsletter -->
    <section class="newsletter" id="newsletter" style="scroll-margin-top: 110px;">
        <div class="container">

        {{-- Shown here because the sign-up redirects back to #newsletter. --}}
        @if (session('newsletter_status'))
            <div class="alert alert-success" role="status" tabindex="-1" data-form-feedback>{{ session('newsletter_status') }}</div>
        @endif

        @php($newsletterErrors = $errors->getBag('newsletter'))
        @php($guardErrors = $errors->getBag(\App\Http\Middleware\ProtectPublicForm::ERROR_BAG))
        @if ($newsletterErrors->any() || ($guardErrors->any() && old('form_anchor') === 'newsletter'))
            <div class="alert alert-danger" role="alert" tabindex="-1" data-form-feedback>
                <ul class="mb-0">
                    @foreach ($guardErrors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                    @foreach ($newsletterErrors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

            <h2>Subscribe for our latest update</h2>
            <p>Stay informed about our latest services, technology trends, and industry insights delivered directly to your inbox</p>
           <form class="newsletter-form" method="POST" action="{{ route('subscribe') }}">
    @csrf
    <input type="hidden" name="form_anchor" value="newsletter">
    <x-form-guard />
    <input type="email" name="email" class="newsletter-input" placeholder="Enter your email address" required>
    <button type="submit" class="newsletter-btn">Subscribe</button>
</form>
        </div>
    </section>


    
   


@endsection

@push('scripts')

    {{-- Bootstrap JS comes from the layout; a second copy broke the phone menu toggle. --}}
    <script >
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
  // Counter animation for stats
  function animateCounters() {
    document.querySelectorAll('.stat-number').forEach(counter => {
      if (counter.dataset.animated === "true") return;
      counter.dataset.animated = "true";

      const target = counter.getAttribute('data-target') || '0';
      const isK = target.toLowerCase().includes('k');
      const isPlus = target.includes('+');
      const numericValue = parseFloat(target.replace(/[^\d.]/g, ''));
      const numericTarget = isK ? numericValue * 1000 : numericValue;

      const duration = 2000; // animation duration in ms
      const startTime = performance.now();

      function updateCounter(now) {
        const elapsed = now - startTime;
        let progress = Math.min(elapsed / duration, 1);
        let current = Math.floor(progress * numericTarget);

        if (isK) {
          counter.textContent = Math.floor(current / 1000) + 'k';
        } else if (isPlus) {
          counter.textContent = current + '+';
        } else {
          counter.textContent = current;
        }

        if (progress < 1) {
          requestAnimationFrame(updateCounter);
        } else {
          counter.textContent = target;
        }
      }

      requestAnimationFrame(updateCounter);
    });
  }

  const statsSection = document.querySelector('.stats-section');
  if (statsSection) {
    const observer = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCounters();
          observer.unobserve(entry.target); // only once
        }
      });
    }, { threshold: 0.2 });

    observer.observe(statsSection);
  }
});


        document.addEventListener('DOMContentLoaded', function () {
   // Counter animation for stats
        function animateCounters() {
    document.querySelectorAll('.stat-number').forEach(counter => {
        if (counter.dataset.animated) return;
        counter.dataset.animated = "true";

        const target = counter.getAttribute('data-target') || '0';
        console.log('Counter target:', target);
        const isK = target.toLowerCase().includes('k');
        const isPlus = target.includes('+');
        const numericValue = parseFloat(target.replace(/[^\d.]/g, ''));
        const numericTarget = isK ? numericValue * 1000 : numericValue;

        const duration = 2000; // ms
        const startTime = performance.now();

        function updateCounter(now) {
            const elapsed = now - startTime;
            let progress = Math.min(elapsed / duration, 1);
            let current = Math.floor(progress * numericTarget);

            if (isK) {
                counter.textContent = Math.floor(current / 1000) + 'k';
            } else if (isPlus) {
                counter.textContent = current + '+';
            } else {
                counter.textContent = current;
            }

            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target; // Final value with suffix
            }
        }

        requestAnimationFrame(updateCounter);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const statsSection = document.querySelector('.stats-section');
    if (!statsSection) return;

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                observer.unobserve(entry.target); // Only animate once
            }
        });
    }, { threshold: 0.1 }); // 50% visible

    observer.observe(statsSection);
});

        // Add smooth scrolling and animation effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Initially hide cards for animation
            const cards = document.querySelectorAll('.news-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
                observer.observe(card);
            });
        });

        // Add click tracking
        document.querySelectorAll('.news-card').forEach(card => {
            card.addEventListener('click', function() {
                // Add click animation
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });

     


        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Initialize scroll animations
        document.querySelectorAll('section').forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(30px)';
            section.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
            observer.observe(section);
        });

});

</script>
@endpush

   