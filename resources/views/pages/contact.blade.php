@extends('layouts.master')
@section('title', 'Sky Link - IT Services in Ethiopia')
@section('description', 'Leading IT services company in Ethiopia, helping businesses succeed with innovative technology solutions.')

@section('additional-styles')
@push('styles')
    <style>
        :root {
            --primary-blue: #4285f4;
            --dark-purple: #2d1b69;
            --light-blue-bg: #e3f2fd;
        }

        /* Contact Hero Section */
        .contact-hero {
            background: #e5e7eb;
            padding: 40px 0;
            text-align: center;
        }

        .contact-hero h1 {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;}

        .contact-hero p {
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Main Contact Section */
        .contact-form-section {
            padding: 20px 10px;
        }

        /* Left Side - Operators Image */
        .operators-container {
            position: relative;
            height: 100%;
            min-height: 600px;
            display: flex;
            flex-direction: column;
        }

        .operators-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .operators-image:hover {
            transform: translateY(-5px);
        }

        .image-overlay {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: rgba(255,255,255,0.95);
            padding: 20px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .image-overlay h4 {
            color: #000;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .image-overlay p {
            color: #666;
            margin: 0;
            font-size: 14px;
        }

        /* Right Side - Contact Cards Row */
        .contact-cards-row {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .contact-card {
            flex: 1;
            min-width: 120px;
            background: #ffffff;
            padding: 15px 10px;
            border-radius: 8px;
            text-align: center;
            margin: 0;
        }

        .contact-card-icon {
            width: 45px;
            height: 45px;
            background: var(--primary-blue);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 1px auto ;
            color: white;
            font-size: 18px;
        }

        .contact-card h6 {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            font-size: 15px;
        }

        .contact-card p {
            color: #666;
            margin: 0;
            font-size: 14px;
            line-height: 1.4;
        }

        /* Contact Form */
        .contact-form {
            padding: 15px;
        }

        .form-title {
            font-size: 20px;
            font-weight: bold;
            color: #000;
            margin-bottom: 6px;
            text-align: center;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px 0;
            font-size: 16px;
            background: #f8fafc;
        }

        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.1);
            background: white;
        }

        .form-label {
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .btn-submit {
            background: var(--primary-blue);
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(66, 133, 244, 0.3);
        }

        .btn-submit:hover {
            background: #1976d2;
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(66, 133, 244, 0.4);
        }

        /* Map Section */
        .map-section {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #1976d2 100%);
            padding: 20px 0;
            color: white;
        }

        .map-content h2 {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .map-content p {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .map-image {
            border-radius: 10px;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        /* Responsive Design */
        @media (max-width: 991.98px) {
            .contact-hero h1 {
                font-size: 36px;
            }
            
            .contact-cards-row {
                gap: 8px;
                margin-bottom: 15px;
            }
            
            .contact-card {
                min-width: 100px;
                padding: 12px 8px;
            }
            
            /* Hide image in tablet view */
            .operators-container {
                display: none;
            }
            
            .col-lg-7 {
                display: block;
            }
            
            .contact-form {
                flex: none;
                padding: 30px;
            }
        }

        @media (max-width: 767.98px) {
            .contact-hero h1 {
                font-size: 28px;
            }
            
            .contact-hero p {
                font-size: 16px;
            }
            
            /* Hide image in mobile view */
            .operators-container {
                display: none;
            }
            
            .contact-form {
                padding: 20px;
                flex: none;
            }
            
            .form-title {
                font-size: 24px;
                margin-bottom: 20px;
            }
            
            .contact-cards-row {
                gap: 5px;
                margin-bottom: 15px;
            }
            
            .contact-card {
                min-width: 90px;
                padding: 10px 5px;
            }
            
            .contact-card-icon {
                width: 35px;
                height: 35px;
                font-size: 16px;
                margin-bottom: 8px;
            }
            
            .contact-card h6 {
                font-size: 12px;
            }
            
            .contact-card p {
                font-size: 11px;
            }
            
            .col-lg-7 {
                display: block;
            }
            
        }

        .contact-form-section .row {
            align-items: stretch;
        }

        .col-lg-7 {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

    </style>
@endpush
@endsection

@section('content')
    <!-- Contact Hero -->
    <section class="contact-hero">
        <div class="container">
            <h1>Connect With Our Experts</h1>
            <p>Have a question, business proposal, or need technical support? Our experienced team is ready to provide tailored IT solutions that help your business operate more efficiently and grow with confidence. We're just one message away.</p>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-form-section">
        <div class="container">
            <div class="row align-items-start">
                <!-- Left Column: Operators Image -->
                <div class="col-lg-5 mb-4">
                    <div class="operators-container">
                        <img src="{{ asset('assets/images/operators.jpg') }}" alt="Our Professional IT Support Team" class="operators-image">
                        <div class="image-overlay">
                            <h4>Expert Support Team</h4>
                            <p>Available Monday to Friday, 8:00 AM - 5:00 PM</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Contact Cards + Form -->
                <div class="col-lg-7">
                    <!-- Contact Cards Row -->
                    <div class="contact-cards-row">
                        <div class="contact-card">
                            <div class="contact-card-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h6>Call Us</h6>
                            <p>+251-974-967171</p>
                        </div>
                        
                        <div class="contact-card">
                            <div class="contact-card-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h6>Email Us</h6>
                            <p>info@skylinkict.com</p>
                        </div>
                        
                        <div class="contact-card">
                            <div class="contact-card-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h6>Visit Us</h6>
                            <p>Addis Ababa, Bole<br>Tropical Mall, Office 217</p>
                        </div>
                    </div>

                    <!-- Contact Form -->
                    <div class="contact-form" id="contact-form">
                        <h3 class="form-title">Send Us a Message</h3>
                        @include('partials.forms.feedback', ['anchor' => 'contact-form'])
                        <form id="contactForm" action="{{ route('inquiry.store') }}" method="POST">
                            @csrf
                            <x-form-guard />
                            <input type="hidden" name="form_anchor" value="contact-form">

                            <div class="row">
                                <div class="form-group col-sm-6 mb-3">
                                    <label class="form-label" for="cf-name">Full Name *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="cf-name" name="name" value="{{ old('name') }}" maxlength="120" autocomplete="name" required>
                                </div>
                                <div class="form-group col-sm-6 mb-3">
                                    <label class="form-label" for="cf-type">Topic *</label>
                                    <select class="form-control @error('type') is-invalid @enderror" id="cf-type" name="type" required>
                                        @foreach (['contact' => 'General question', 'support' => 'Support request', 'quote' => 'Quote request'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('type', 'contact') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-6 mb-3">
                                    <label class="form-label" for="cf-email">Email Address *</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="cf-email" name="email" value="{{ old('email') }}" maxlength="190" autocomplete="email" required>
                                </div>
                                <div class="form-group col-sm-6 mb-3">
                                    <label class="form-label" for="cf-phone">Phone Number</label>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="cf-phone" name="phone" value="{{ old('phone') }}" maxlength="30" autocomplete="tel">
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-6 mb-3">
                                    <label class="form-label" for="cf-company">Company Name</label>
                                    <input type="text" class="form-control" id="cf-company" name="company" value="{{ old('company') }}" maxlength="190" autocomplete="organization">
                                </div>
                                <div class="form-group col-sm-6 mb-3">
                                    <label class="form-label" for="cf-service">Service (for quotes)</label>
                                    <input type="text" class="form-control @error('service') is-invalid @enderror" id="cf-service" name="service" value="{{ old('service') }}" maxlength="120" placeholder="e.g. Website, CCTV, Network">
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label class="form-label" for="cf-message">Your Message *</label>
                                <textarea class="form-control @error('message') is-invalid @enderror" id="cf-message" name="message" rows="4" maxlength="5000" required placeholder="Tell us about your project or how we can help you...">{{ old('message') }}</textarea>
                            </div>
                            <div class="form-group text-center">
                                <button type="submit" class="btn-submit">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    SEND MESSAGE
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="map-content">
                        <h2>Find Us on the Map</h2>
                        <p>Located in the heart of Addis Ababa at Bole Tropical Mall. Reach out to discuss your business goals—we provide expert IT services designed to help you scale, automate, and grow efficiently.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="map-image">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3548.139703280376!2d38.78086865522774!3d8.991575084606922!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x164b85f52615ebdf%3A0x6a0949274cd951c7!2sTropical%20Mall!5e0!3m2!1sen!2set!4v1750667760140!5m2!1sen!2set" width="600" height="400" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')

    {{-- Bootstrap JS comes from the layout; a second copy broke the phone menu toggle. --}}
    <script>
        // Run once, after the page exists.
        // (The old submit handler that ran before the form existed was removed;
        // the form submits normally.)
        if (!window.__skyContactPage) {
        window.__skyContactPage = true;
        document.addEventListener('DOMContentLoaded', function () {

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Form field focus effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
        });

        });
        }
    </script>
    
@endpush
