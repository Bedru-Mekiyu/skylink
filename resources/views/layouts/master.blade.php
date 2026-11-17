
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sky Link - IT Services in Ethiopia')</title>
    <meta name="description" content="@yield('description', 'Leading IT services company in Ethiopia, providing innovative technology solutions for businesses.')">
    
    <!-- Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

        
    <!-- Custom Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

    .social-icon {
        color: #666;
        font-size: 15px;
        transition: all 0.3s ease;
        text-decoration: none;
        margin-right: 4px;
    }

    .social-icon:hover {
        color: #3B82F6;
    }


    /* Main Navbar */
    .navbar-custom {
        background: white;
        padding: 3px 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: sticky;
        z-index: 1030;
        top: 0; /* was 30px: offset for the removed top bar (contact items moved to the contact launcher) */
        transition: all 0.3s ease;
    }

    .navbar-custom.scrolled {
        padding: 15px 0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }

    .navbar-brand {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #333 !important;
    }

    .navbar-brand:hover {
        color: #333 !important;
    }

    .logo-img {
    width: 130px;
    height: 50px;
    object-fit: contain;
    border-radius: 10px;
}

    .navbar-nav {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .nav-link {
        color: #333 !important;
        font-weight: 500;
        font-size: 15px;
        text-decoration: none;
        position: relative;
        padding: 8px 0;
        transition: all 0.3s ease;
    }

    .nav-link:hover {
        color: #3B82F6 !important;
    }

    .nav-link.active {
        color: #333 !important;
        font-weight: 600;
    }

    .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 100%;
        height: 2px;
        background: #3B82F6;
    }

    /* Logo = home link: visible keyboard focus. */
    .navbar-home-link:focus-visible {
        outline: 2px solid #3B82F6;
        outline-offset: 3px;
        border-radius: 10px;
    }

    /* Phones and short address. Hidden by default; shown on the right of the
       navbar from 1200px, and inside the collapsed menu below 992px. */
    .nav-contact {
        display: none;
        font-size: 12.5px;
        line-height: 1.5;
        color: #555;
    }

    .nav-contact__row {
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .nav-contact i {
        color: #3B82F6;
        width: 14px;
        text-align: center;
        font-size: 11px;
    }

    .nav-contact a {
        color: #333;
        text-decoration: none;
        font-weight: 500;
    }

    .nav-contact a:hover {
        color: #3B82F6;
    }

    .nav-contact a:focus-visible {
        outline: 2px solid #3B82F6;
        outline-offset: 2px;
        border-radius: 3px;
    }

    .nav-contact__sep {
        color: #ccc;
    }

    @media (min-width: 1200px) {
        .nav-contact {
            display: block;
            flex-shrink: 0;
            margin-left: 16px;
        }
    }

    @media (max-width: 991.98px) {
        .nav-contact {
            display: block;
            margin-top: 8px;
            padding: 10px 0 4px;
            border-top: 1px solid #eee;
            font-size: 14px;
            line-height: 1.9;
        }

        .nav-contact__row {
            flex-wrap: wrap;
            white-space: normal;
        }
    }

    /* Apple-style Full Width Dropdown */
    .nav-item.mega-dropdown {
        position: static;
    }

    .mega-dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        /* 100%, not 100vw: 100vw includes a classic scrollbar and would overhang it. */
        width: 100%;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border: none;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        padding: 20px 0;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-20px);
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        z-index: 999;
    }

    .mega-dropdown:hover .mega-dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .mega-dropdown-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .mega-dropdown-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 40px;
    }

    .mega-dropdown-section h6 {
        font-size: 14px;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
    }

    .mega-dropdown-item {
        display: block;
        padding: 8px 0;
        color: #333;
        text-decoration: none;
        font-size: 16px;
        font-weight: 500;
        transition: all 0.3s ease;
        border-bottom: 1px solid transparent;
    }

    .mega-dropdown-item:hover {
        color: #3B82F6;
        transform: translateX(10px);
        border-bottom-color: #3B82F6;
    }

    .mega-dropdown-item i {
        margin-right: 10px;
        width: 20px;
        color: #3B82F6;
    }
    
        /* Footer */
        .footer {
            background-color: #f8f9fa;
            padding: 60px 0 30px;
        }

        .footer h5 {
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }

        .footer ul {
            list-style: none;
            padding: 0;
        }

        .footer ul li {
            margin-bottom: 10px;
        }

        .footer ul li a {
            color: #666;
            text-decoration: none;
        }

        .footer ul li a:hover {
            color: var(--primary-blue);
        }

        .footer-bottom {
            border-top: 1px solid #ddd;
            padding-top: 20px;
            margin-top: 40px;
            text-align: center;
            color: #666;
        }

        .social-footer {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-footer a {
            color: #666;
            font-size: 18px;
        }

        /* Responsive Design */
        @media (max-width: 991.98px) {
    .navbar-collapse {
        background: white;
        padding: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .navbar-nav {
        flex-direction: column !important;
        gap: 10px !important;
        align-items: flex-start !important;
    }

    .mega-dropdown-menu {
        position: static;
        width: 100%;
        opacity: 1;
        visibility: visible;
        transform: none;
        box-shadow: none;
        padding: 20px 0;
        display: none;
    }

    .mega-dropdown:hover .mega-dropdown-menu {
        display: block;
    }

    .btn-get-started {
        margin-top: 1rem;
        width: 100%;
        text-align: center;
    }

        .navbar-custom {
            top: 0; /* was 45px (top bar removed) */
        }
        .navbar-collapse {
            background: white;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .navbar-nav {
            flex-direction: column !important;
            gap: 3px !important;
            align-items: flex-start !important;
        }

        .mega-dropdown-menu {
            position: static;
            width: 50%;
            opacity: 1;
            visibility: visible;
            transform: none;
            box-shadow: none;
            padding: 20px 0;
            display: none;
        }
        .mega-dropdown-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(200px, 1fr));
            gap: 20px;
        }
        .mega-dropdown-section h6 {
        font-size: 13px;
        margin-bottom: 10px;
    }

    .mega-dropdown-item {
        font-size: 14px;
        padding: 8px 0;
        margin-bottom: 0%;
        
    }


        .mega-dropdown:hover .mega-dropdown-menu {
            display: block;
        }

        .btn-get-started {
            margin-top: 1rem;
            width: 50%;
            text-align: center;
        }
    }
    @media (min-width: 992px) {
    .mega-dropdown-menu {
        max-height: 500px; 
        max-width: 100%;   /* Limit dropdown height */
        overflow-y: auto;
             /* Enable vertical scrolling if content overflows */
    }
    .mega-dropdown-section h6 {
        font-size: 15px;
        margin-bottom: -2%;
        margin-top: -3%;
    }

    .mega-dropdown-item {
        font-size: 13px;
        margin-bottom: 0%;
        margin-top: 0% ;
    }
}
    @media (max-width: 576px) {
    .mega-dropdown-grid {
        grid-template-columns: repeat(2, minmax(200px, 1fr));
        gap: 10px;
    }

    .mega-dropdown-section h6 {
        font-size: 14px;
    }

    .mega-dropdown-item {
        font-size: 12px;
    }
    .mega-dropdown-menu {
        max-height: 250px;
        overflow-y: auto;
        max-width: 100%;
        min-width: 100%;
    }
    .navbar-custom {
    top: 0; /* was 60px (top bar removed) */
}

}
@media (min-width: 669px) and (max-width: 991.98px) {
    .mega-dropdown-menu {
        max-height: 350px;
        overflow-y: auto;
        max-width: 100%;
        min-width: 100%;
    }
}


 </style>
    

@yield('additional-styles')
   
    @stack('styles')
    @if (\App\Support\Anniversary::isActive())
        @include('partials.anniversary.styles')
    @endif
    {{-- The scripts stack is rendered once, before </body>, after Bootstrap. --}}

</head>
<body>
    {{-- Top bar removed: its phone numbers, address, social links and the "Service Active" badge now live in the contact launcher (partials/contact-launcher). --}}

<!-- Main Navbar -->
        <nav class="navbar navbar-expand-lg navbar-custom" id="mainNavbar">
            <div class="container">
                <div class="d-flex align-items-center">
                    <a href="{{ route('home') }}" class="navbar-home-link d-flex align-items-center text-decoration-none" aria-label="Sky Link ICT Solutions - Home">
                        <img src="{{ asset('assets/images/skylink-logo.svg') }}" alt="" class="logo-img me-2">
                    </a>

                    @if (\App\Support\Anniversary::isActive())
                        @include('partials.anniversary.badge')
                    @endif
                </div>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        {{-- Desktop: the logo is the home link; "Home" stays in the collapsed menu. --}}
                        <li class="nav-item d-lg-none">
                            <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">About Us</a>
                        </li>

                        <li class="nav-item mega-dropdown">
    <a class="nav-link {{ request()->routeIs('services') ? 'active' : '' }}" href="{{ route('services') }}">Services</a>
    <div class="mega-dropdown-menu">
        <div class="mega-dropdown-content">
            <div class="mega-dropdown-grid">

                <!-- Software Service -->
                <div class="mega-dropdown-section">
                    <h6><a href="{{ route('services.category', ['category' => 'software-service']) }}">Software Service</a></h6>
                    <a href="{{ route('services.detail', ['subcategory' => 'web-apps']) }}" class="mega-dropdown-item"><i class="fas fa-laptop-code"></i> Web Applications</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'mobile-apps']) }}" class="mega-dropdown-item"><i class="fas fa-mobile-alt"></i> Mobile Applications</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'custom-dev']) }}" class="mega-dropdown-item"><i class="fas fa-cogs"></i> Custom Software</a>
                </div>

                <!-- Smart City & Infrastructure -->
                <div class="mega-dropdown-section">
                    <h6><a href="{{ route('services.category', ['category' => 'smart-city']) }}">Smart City & Infrastructure</a></h6>
                    <a href="{{ route('services.detail', ['subcategory' => 'physical-security']) }}" class="mega-dropdown-item"><i class="fas fa-shield-alt"></i> Physical Safety & Security</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'structured-cabling']) }}" class="mega-dropdown-item"><i class="fas fa-network-wired"></i> Structured Cabling System</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'smart-mobility']) }}" class="mega-dropdown-item"><i class="fas fa-car"></i> Smart Mobility & Transportation</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'integrated-infra']) }}" class="mega-dropdown-item"><i class="fas fa-layer-group"></i> Integrated Smart Infrastructure</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'data-center']) }}" class="mega-dropdown-item"><i class="fas fa-database"></i> Data Center Infrastructure</a>
                </div>

                <!-- Safety & Security -->
                <div class="mega-dropdown-section">
                    <h6><a href="{{ route('services.category', ['category' => 'safety-security']) }}">Safety & Security</a></h6>
                    <a href="{{ route('services.detail', ['subcategory' => 'network-security']) }}" class="mega-dropdown-item"><i class="fas fa-shield-virus"></i> Network & Application Security</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'endpoint-security']) }}" class="mega-dropdown-item"><i class="fas fa-laptop-medical"></i> Endpoint Security</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'data-protection']) }}" class="mega-dropdown-item"><i class="fas fa-lock"></i> Data Protection</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'access-management']) }}" class="mega-dropdown-item"><i class="fas fa-user-shield"></i> Access Management</a>
                </div>

                <!-- Enterprise Network -->
                <div class="mega-dropdown-section">
                    <h6><a href="{{ route('services.category', ['category' => 'enterprise-network']) }}">Enterprise Network Solution</a></h6>
                    <a href="{{ route('services.detail', ['subcategory' => 'enterprise-network']) }}" class="mega-dropdown-item"><i class="fas fa-network-wired"></i> Enterprise Network Overview</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'network-wlan']) }}" class="mega-dropdown-item"><i class="fas fa-wifi"></i> Network & WLAN Solution</a>
                </div>

                <!-- Consulting & Training -->
                <div class="mega-dropdown-section">
                    <h6><a href="{{ route('services.category', ['category' => 'consulting-training']) }}">Consulting & Training</a></h6>
                    <a href="{{ route('services.detail', ['subcategory' => 'smart-projects']) }}" class="mega-dropdown-item"><i class="fas fa-project-diagram"></i> Smart City Projects</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'product-dev']) }}" class="mega-dropdown-item"><i class="fas fa-cogs"></i> Product Development</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'business-strategy']) }}" class="mega-dropdown-item"><i class="fas fa-chart-line"></i> Business Strategy</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'erp-consult']) }}" class="mega-dropdown-item"><i class="fas fa-database"></i> ERP Consultancy</a>
                </div>

                <!-- ICT Support -->
                <div class="mega-dropdown-section">
                    <h6><a href="{{ route('services.category', ['category' => 'ict-support']) }}">ICT Support</a></h6>
                    <a href="{{ route('services.detail', ['subcategory' => 'tech-assistance']) }}" class="mega-dropdown-item"><i class="fas fa-headset"></i> Technical Assistance</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'system-maintenance']) }}" class="mega-dropdown-item"><i class="fas fa-tools"></i> System Maintenance</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'security-privacy']) }}" class="mega-dropdown-item"><i class="fas fa-user-lock"></i> Security & Privacy</a>
                </div>

                <!-- GPS Tracking -->
                <div class="mega-dropdown-section">
                    <h6><a href="{{ route('services.category', ['category' => 'gps-tracking']) }}">GPS Tracking System</a></h6>
                    <a href="{{ route('services.detail', ['subcategory' => 'fleet-management']) }}" class="mega-dropdown-item"><i class="fas fa-truck"></i> Fleet Management</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'asset-tracking']) }}" class="mega-dropdown-item"><i class="fas fa-map-marker-alt"></i> Asset Tracking</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'vehicle-tracking']) }}" class="mega-dropdown-item"><i class="fas fa-car"></i> Vehicle Monitoring</a>
                </div>

                <!-- Datacenter Facility -->
                <div class="mega-dropdown-section">
                    <h6><a href="{{ route('services.category', ['category' => 'datacenter-facility']) }}">Datacenter Facility & Power</a></h6>
                    <a href="{{ route('services.detail', ['subcategory' => 'server-rooms']) }}" class="mega-dropdown-item"><i class="fas fa-server"></i> Server Rooms</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'ups-power']) }}" class="mega-dropdown-item"><i class="fas fa-bolt"></i> UPS & Power Backup</a>
                    <a href="{{ route('services.detail', ['subcategory' => 'cooling-system']) }}" class="mega-dropdown-item"><i class="fas fa-snowflake"></i> Cooling Systems</a>
                </div>

            </div>
        </div>
    </div>
</li>


    <li class="nav-item mega-dropdown">  
    <a class="nav-link {{ request()->routeIs('products') ? 'active' : '' }}" href="{{ route('products') }}">Products</a>
    <div class="mega-dropdown-menu">
        <div class="mega-dropdown-content">
            <div class="mega-dropdown-grid">
                <!-- Industry Solutions -->
                <!-- Industry Solutions -->
<div class="mega-dropdown-section">
    <h6>Industry Solutions</h6>
    <a href="{{ route('products.detail', ['category' => 'industry-solutions', 'slug' => 'e-tourism']) }}" class="mega-dropdown-item"><i class="fas fa-suitcase-rolling"></i> E-Tourism</a>
    <a href="{{ route('products.detail', ['category' => 'industry-solutions', 'slug' => 'fleet-management']) }}" class="mega-dropdown-item"><i class="fas fa-warehouse"></i> Fleet Management</a>
    <a href="{{ route('products.detail', ['category' => 'industry-solutions', 'slug' => 'project-management']) }}" class="mega-dropdown-item"><i class="fas fa-project-diagram"></i> Project Management</a>
    <a href="{{ route('products.detail', ['category' => 'industry-solutions', 'slug' => 'real-estate-management']) }}" class="mega-dropdown-item"><i class="fas fa-building"></i> Real Estate Management</a>
    <a href="{{ route('products.detail', ['category' => 'industry-solutions', 'slug' => 'school-management']) }}" class="mega-dropdown-item"><i class="fas fa-school"></i> School Management</a>
    <a href="{{ route('products.detail', ['category' => 'industry-solutions', 'slug' => 'lms']) }}" class="mega-dropdown-item"><i class="fas fa-book-open"></i> LMS</a>
    <a href="{{ route('products.detail', ['category' => 'industry-solutions', 'slug' => 'smart-e-ticketing']) }}" class="mega-dropdown-item"><i class="fas fa-bus"></i> Smart E-ticketing</a>
</div>

<!-- Business & Office Tools -->
<div class="mega-dropdown-section">
    <h6>Business & Office Tools</h6>
    <a href="{{ route('products.detail', ['category' => 'business-tools', 'slug' => 'document-management']) }}" class="mega-dropdown-item"><i class="fas fa-folder-open"></i> Document Management</a>
    <a href="{{ route('products.detail', ['category' => 'business-tools', 'slug' => 'traffic-penalty']) }}" class="mega-dropdown-item"><i class="fas fa-gavel"></i> Traffic Penalty Management</a>
    <a href="{{ route('products.detail', ['category' => 'business-tools', 'slug' => 'erp']) }}" class="mega-dropdown-item"><i class="fas fa-cogs"></i> ERP Full Module</a>
    <a href="{{ route('products.detail', ['category' => 'business-tools', 'slug' => 'appointment-booking']) }}" class="mega-dropdown-item"><i class="fas fa-calendar-check"></i> Appointment Booking</a>
    <a href="{{ route('products.detail', ['category' => 'business-tools', 'slug' => 'qms']) }}" class="mega-dropdown-item"><i class="fas fa-stream"></i> QMS</a>
    <a href="{{ route('products.detail', ['category' => 'business-tools', 'slug' => 'building-management']) }}" class="mega-dropdown-item"><i class="fas fa-city"></i> Building Management</a>
    <a href="{{ route('products.detail', ['category' => 'business-tools', 'slug' => 'event-management']) }}" class="mega-dropdown-item"><i class="fas fa-calendar-alt"></i> Event Management</a>
</div>

<!-- Web & Mobile Applications -->
<div class="mega-dropdown-section">
    <h6>Web & Mobile Applications</h6>
    <a href="{{ route('products.detail', ['category' => 'web-mobile-apps', 'slug' => 'e-commerce']) }}" class="mega-dropdown-item"><i class="fas fa-store"></i> E-Commerce</a>
    <a href="{{ route('products.detail', ['category' => 'web-mobile-apps', 'slug' => 'website-design']) }}" class="mega-dropdown-item"><i class="fas fa-paint-brush"></i> Website Design</a>
    <a href="{{ route('products.detail', ['category' => 'web-mobile-apps', 'slug' => 'entertainment-apps']) }}" class="mega-dropdown-item"><i class="fas fa-music"></i> Entertainment Apps</a>
</div>

<!-- Digital Services -->
<div class="mega-dropdown-section">
    <h6>Digital Services</h6>
    <a href="{{ route('products.detail', ['category' => 'digital-services', 'slug' => 'e-service']) }}" class="mega-dropdown-item"><i class="fas fa-concierge-bell"></i> E-Service</a>
    <a href="{{ route('products.detail', ['category' => 'digital-services', 'slug' => 'customer-support']) }}" class="mega-dropdown-item"><i class="fas fa-headset"></i> Customer Support Systems</a>
    <a href="{{ route('products.detail', ['category' => 'digital-services', 'slug' => 'system-maintenance']) }}" class="mega-dropdown-item"><i class="fas fa-tools"></i> System Maintenance</a>
    <a href="{{ route('products.detail', ['category' => 'digital-services', 'slug' => 'security-compliance']) }}" class="mega-dropdown-item"><i class="fas fa-user-shield"></i> Security & Compliance</a>
</div>

            </div>
        </div>
    </div>
</li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('portfolio') ? 'active' : '' }}" href="{{ route('portfolio') }}">Projects</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('news') ? 'active' : '' }}" href="{{ route('content.index') }}">News</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Contact Us</a>
                        </li>
                    </ul>

                    {{-- Phones and short address (config/company_contact.php): in the
                         navbar from 1200px, inside the collapsed menu below 992px. --}}
                    <div class="nav-contact">
                        <div class="nav-contact__row">
                            <i class="fas fa-phone" aria-hidden="true"></i>
                            @foreach (config('company_contact.phones') as $navPhone)
                                @if (! $loop->first)<span class="nav-contact__sep" aria-hidden="true">|</span>@endif
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $navPhone) }}">{{ $navPhone }}</a>
                            @endforeach
                        </div>
                        <div class="nav-contact__row">
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                            <span>{{ config('company_contact.address_short') }}</span>
                        </div>
                    </div>

                </div>
            </div>
        </nav>


    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer">
    <div class="container">
        <div class="row">
            <!-- Column 1 -->
            <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                <div class="logo mb-3">
                    <a href="/" class="d-flex align-items-center text-decoration-none">
                        <img src="{{ asset('assets/images/skylink-logo.svg') }}" alt="Skylink Logo" class="logo-img me-2">
                    </a>
                </div>
                <p>
                    Sky Link Technologies specializes in delivering reliable software, cybersecurity, and digital transformation services to businesses across sectors.
                </p>
                <div class="social-footer">
                    <a href="https://www.facebook.com/profile.php?id=61562181474117" target="_blank" rel="noopener noreferrer" aria-label="Sky Link on Facebook (opens in a new tab)"><i class="fab fa-facebook" aria-hidden="true"></i></a>
                    <a href="https://www.instagram.com/skylink_technologies?igsh=NXZwMjQyMHo1bHJm&utm_source=qr" target="_blank" rel="noopener noreferrer" aria-label="Sky Link on Instagram (opens in a new tab)"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                    <a href="https://www.linkedin.com/company/skylinkict/" target="_blank" rel="noopener noreferrer" aria-label="Sky Link on LinkedIn (opens in a new tab)"><i class="fab fa-linkedin" aria-hidden="true"></i></a>
                    <a href="https://www.tiktok.com/@skylink_technologies?_t=ZM-8vv40eFtXHh&_r=1" target="_blank" rel="noopener noreferrer" aria-label="Sky Link on TikTok (opens in a new tab)"><i class="fab fa-tiktok" aria-hidden="true"></i></a>
                    <a href="https://www.youtube.com/@skylink_technologie" target="_blank" rel="noopener noreferrer" aria-label="Sky Link on YouTube (opens in a new tab)"><i class="fab fa-youtube" aria-hidden="true"></i></a>

                </div>
            </div>

            <!-- Column 2 -->
            <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                <h5>Quick Links</h5>
                <ul>
                    <li><a href="{{ route('home')}}">Home</a></li>
                    <li><a href="{{ route('about') }}">About Us</a></li>
                    <li><a href="{{ route('services') }}">Services</a></li>
                    <li><a href="{{ route('products')}}">Products</a></li>
                    <li><a href="{{ route('content.index')}}">News and Events</a></li>
                    <li><a href="{{ route('contact')}}">Contact Us</a></li>
                </ul>
            </div>

            <!-- Column 3 -->
            <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                <h5>Services</h5>
                <ul>
                    <li><a href="{{ route('services.category', ['category' => 'software-service']) }}">Software Service</a></li>
                    <li><a href="{{ route('services.category', ['category' => 'smart-city']) }}">Smart City Infrastructure</a></li>
                    <li><a href="{{ route('services.category', ['category' => 'safety-security']) }}">Safety and Security</a></li>
                    <li><a href="{{ route('services.category', ['category' => 'ict-support']) }}">ICT Support</a></li>
                    <li><a href="{{ route('services.category', ['category' => 'enterprise-network']) }}">Enterprise Network Solution</a></li>
                </ul>
            </div>

            <!-- Column 4 -->
            <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                <h5>Contact</h5>
                <p><i class="fas fa-map-marker-alt"></i> A.A Bole, Tropical Mall, 2nd Floor No.217</p>
                <p><i class="fas fa-globe"></i> www.skylinkict.com</p>
                <p><i class="fas fa-phone"></i> +251-974-967171</p>
                <p><i class="far fa-clock"></i> Mon - Fri 8:00AM - 17:00 <br> Saturday 8:00-12:00 </p>
            </div>
        </div>

        <div class="footer-bottom text-center pt-3">
            <p>Copyright © 2025 | Powered By Sky Link Technologies</p>
        </div>
    </div>
</footer>


    @include('partials.contact-launcher')

    <!-- JavaScript -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/687a0cac83aacb1919337378/1j0ec35jq';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->

    
    @if (\App\Support\Anniversary::isActive())
        @include('partials.anniversary.intro')
    @endif

    @stack('scripts')
</body>
</html>

