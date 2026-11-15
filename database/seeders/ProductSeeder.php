<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
class ProductSeeder extends Seeder
{
    public function run()
    {
        $basicProducts = [
            'industry-solutions' => [
                [
                    'slug'=>'e-tourism',
                    'name'=>'E‑Tourism',
                    'image'=>'https://cdn.prod.website-files.com/647750108dbcda6a9297b934/65e8324f3990ac2dc7c7c098_D%C3%A9bouch%C3%A9s%20et%20m%C3%A9tiers%20du%20e-tourisme.jpg',
                    'description'=>'A digital platform enabling tourists to explore, book, and manage tourism services with ease.',
                    'price'=>185000,
                    'features'=>['Multi-language Support', 'Integrated Payment Gateway', 'Mobile App Integration'],
                    'additional_info'=>'Ideal for tour operators and travel agencies targeting international tourists.'
                ],
                [
                    'slug'=>'fleet-management',
                    'name'=>'Fleet Management',
                    'image'=>'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ4-4scvUqxDebAUh3NcDgveR2Et0Y5idzJdQ&s',
                    'description'=>'Real‑time tracking and optimization of vehicle fleets to enhance logistics operations.',
                    'price'=>220000,
                    'features'=>['GPS Tracking', 'Fuel Monitoring', 'Driver Analytics'],
                    'additional_info'=>'Suitable for logistics, transport, and delivery companies.'
                ],
                [
                    'slug'=>'project-management',
                    'name'=>'Project Management',
                    'image'=>'https://www.simplilearn.com/ice9/free_resources_article_thumb/project_management_coursefees.jpg',
                    'description'=>'Tools for planning, executing, and tracking projects to ensure timely delivery.',
                    'price'=>145000,
                    'features'=>['Kanban Boards', 'Milestone Tracking', 'Team Collaboration'],
                    'additional_info'=>'Great for engineering firms, agencies, and NGOs.'
                ],
                [
                    'slug'=>'real-estate-management',
                    'name'=>'Real Estate Management',
                    'image'=>'https://tse1.mm.bing.net/th/id/OIP.yd3ab93C5dSVnyyb9O3mkwHaE8?r=0&rs=1&pid=ImgDetMain&o=7&rm=3',
                    'description'=>'Manage property listings, tenants, and sales efficiently in one centralized platform.',
                    'price'=>165000,
                    'features'=>['Tenant Portals', 'Lease Tracking', 'Online Payments'],
                    'additional_info'=>'For real estate agencies and property managers.'
                ],
                [
                    'slug'=>'school-management',
                    'name'=>'School Management',
                    'image'=>'https://tse1.mm.bing.net/th/id/OIP.9qr9W750UCPkP7Cgnb-k1AHaHa?r=0&rs=1&pid=ImgDetMain&o=7&rm=3',
                    'description'=>'Complete administration system for student records, grades, schedules, and more.',
                    'price'=>150000,
                    'features'=>['Exam & Grades', 'Attendance Tracking', 'Parent Portals'],
                    'additional_info'=>'Designed for K-12 schools and private institutions.'
                ],
                [
                    'slug'=>'lms',
                    'name'=>'Learning Management System (LMS)',
                    'image'=>'https://tse2.mm.bing.net/th/id/OIP.Q4QyoME7s8jZNPOAmPsbhAHaEv?r=0&rs=1&pid=ImgDetMain&o=7&rm=3',
                    'description'=>'An online platform for managing educational courses and delivering digital learning.',
                    'price'=>170000,
                    'features'=>['Course Management', 'Progress Tracking', 'Certificates'],
                    'additional_info'=>'Suitable for universities, training centers, and eLearning platforms.'
                ],
                [
                    'slug'=>'smart-e-ticketing',
                    'name'=>'Smart E‑ticketing',
                    'image'=>'https://cdn3.iconfinder.com/data/icons/travel-agency-or-industry/800/Air_Ticket-512.png',
                    'description'=>'A smart and secure system for ticket booking, issuing, and validation.',
                    'price'=>130000,
                    'features'=>['QR-based Ticketing', 'SMS Alerts', 'Seat Reservation'],
                    'additional_info'=>'Designed for cinemas, buses, events, and stadiums.'
                ],
            ],
            'business-tools' => [
                [
                    'slug'=>'document-management',
                    'name'=>'Document Management',
                    'image'=>'https://img.freepik.com/premium-vector/file-management-administration-data-filing-concept-folder-gallery-administration-flat-illustration-vector-template_128772-1787.jpg',
                    'description'=>'Secure storage, sharing, and collaboration of documents in the cloud.',
                    'price'=>95000,
                    'features'=>['Version Control', 'Access Permissions', 'Cloud Backup'],
                    'additional_info'=>'Fits legal, finance, and HR departments.'
                ],
                [
                    'slug'=>'traffic-penalty',
                    'name'=>'Traffic Penalty Management',
                    'image'=>'https://img.freepik.com/premium-vector/police-characters-are-carrying-out-activities_995281-16336.jpg',
                    'description'=>'Manage traffic violations and penalty payments through a centralized dashboard.',
                    'price'=>110000,
                    'features'=>['Violation Record System', 'Online Payment', 'SMS Notifications'],
                    'additional_info'=>'Used by municipal offices and police departments.'
                ],
                [
                    'slug'=>'erp',
                    'name'=>'ERP Full Module',
                    'image'=>'https://erp.aero/uploads/attachments/ckzsczif300qxjpqfpt8pcppk-erp-2.full.png',
                    'description'=>'Integrated suite for managing business processes like HR, finance, sales, and inventory.',
                    'price'=>350000,
                    'features'=>['HRMS', 'Inventory Control', 'Accounting'],
                    'additional_info'=>'Perfect for medium to large enterprises.'
                ],
                [
                    'slug'=>'appointment-booking',
                    'name'=>'Appointment Booking',
                    'image'=>'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRrNey4WHC6J_KmPQgpNq6YEK9AjbdGSjUreA&s',
                    'description'=>'Allow customers to book appointments online, receive reminders, and manage slots.',
                    'price'=>75000,
                    'features'=>['Google Calendar Sync', 'SMS Reminders', 'Slot Management'],
                    'additional_info'=>'Useful for clinics, spas, consultants.'
                ],
                [
                    'slug'=>'qms',
                    'name'=>'Queue Management System (QMS)',
                    'image'=>'https://th.bing.com/th/id/R.abcdbe9b3349a8d73f707a0e73f48b6b?rik=34HChHkbxIVohQ&riu=http%3a%2f%2fwww.inovexidea.com%2fqueue%2fimages%2fmain-feature2.png&ehk=pfxc6GTz5A8pwZ5dvJMp1pohN%2fvtWQ8vI6PMXmxOGpM%3d&risl=&pid=ImgRaw&r=0',
                    'description'=>'Digitally manage customer queues and improve service efficiency.',
                    'price'=>90000,
                    'features'=>['Digital Token Display', 'Real-time Analytics', 'Multi-branch Support'],
                    'additional_info'=>'Ideal for banks, hospitals, and government offices.'
                ],
                [
                    'slug'=>'building-management',
                    'name'=>'Building Management',
                    'image'=>'https://images.unsplash.com/photo-1503387762-592deb58ef4e',
                    'description'=>'Centralized platform for monitoring and managing building infrastructure and services.',
                    'price'=>170000,
                    'features'=>['Energy Monitoring', 'Access Control', 'Maintenance Scheduling'],
                    'additional_info'=>'Targeted for large commercial buildings and residential complexes.'
                ],
                [
                    'slug'=>'event-management',
                    'name'=>'Event Management',
                    'image'=>'https://images.unsplash.com/photo-1551836022-d5d88e9218df',
                    'description'=>'Plan, organize, and analyze events with real‑time tools and automation.',
                    'price'=>135000,
                    'features'=>['RSVP & Invitations', 'Agenda Builder', 'Analytics Dashboard'],
                    'additional_info'=>'Event agencies, NGOs, and corporations can benefit.'
                ],
            ],
            'web-mobile-apps' => [
                [
                    'slug'=>'e-commerce',
                    'name'=>'E‑Commerce',
                    'image'=>'https://img.freepik.com/premium-photo/ecommerce-with-white-background-high-quality-ultra_670382-67400.jpg',
                    'description'=>'Customizable online shop solution with product listing, checkout, and delivery tracking.',
                    'price'=>190000,
                    'features'=>['Cart & Checkout', 'Product Reviews', 'Delivery Tracking'],
                    'additional_info'=>'Fits both B2C and B2B platforms.'
                ],
                [
                    'slug'=>'website-design',
                    'name'=>'Website Design',
                    'image'=>'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSCMJ3TE0jSSnRXkow4JkK3Lc0mVdt3CSX3qA&s',
                    'description'=>'Professional and responsive websites tailored to your brand.',
                    'price'=>65000,
                    'features'=>['SEO Optimized', 'Fast Loading', 'Mobile Friendly'],
                    'additional_info'=>'Suitable for portfolios, businesses, and blogs.'
                ],
                [
                    'slug'=>'entertainment-apps',
                    'name'=>'Entertainment Apps',
                    'image'=>'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSaZQjaSWry6pzcviXj8PGUEiztrSs3JrqM1w&s',
                    'description'=>'Interactive mobile apps focused on media, gaming, and user engagement.',
                    'price'=>120000,
                    'features'=>['User Profiles', 'Streaming Support', 'Push Notifications'],
                    'additional_info'=>'Used by media companies, influencers, and developers.'
                ],
            ],
            'digital-services' => [
                [
                    'slug'=>'e-service',
                    'name'=>'E‑Service',
                    'image'=>'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQP5WSdXpfe3tcAKJHtvfcsdSovbndPrNFrCA&s',
                    'description'=>'Digital transformation for public and private service delivery.',
                    'price'=>110000,
                    'features'=>['Citizen Portals', 'Workflow Automation', 'Case Tracking'],
                    'additional_info'=>'Useful for government and NGOs.'
                ],
                [
                    'slug'=>'customer-support',
                    'name'=>'Customer Support Systems',
                    'image'=>'https://th.bing.com/th/id/R.e84a6bad7633af3c789d0ad04bf54cba?rik=uMYwLs%2fe0M0qbA&pid=ImgRaw&r=0',
                    'description'=>'Multi-channel customer service system including chat, call, and ticketing.',
                    'price'=>95000,
                    'features'=>['Live Chat', 'Ticketing System', 'Call Logging'],
                    'additional_info'=>'Designed for support teams and call centers.'
                ],
                [
                    'slug'=>'system-maintenance',
                    'name'=>'System Maintenance',
                    'image'=>'https://tse2.mm.bing.net/th/id/OIP._mcxXuQHy53tp5-E_IR7fAHaE8?r=0&rs=1&pid=ImgDetMain&o=7&rm=3',
                    'description'=>'Ensure systems run efficiently with proactive maintenance and monitoring.',
                    'price'=>105000,
                    'features'=>['Health Checks', 'Automated Backups', 'Downtime Alerts'],
                    'additional_info'=>'Great for hosting providers and IT teams.'
                ],
                [
                    'slug'=>'security-compliance',
                    'name'=>'Security & Compliance',
                    'image'=>'https://img.freepik.com/premium-vector/internet-security-corporate-partner-with-data-security-compliance_1051349-8.jpg?w=2000',
                    'description'=>'Ensure your digital assets are secure and compliant with industry standards.',
                    'price'=>160000,
                    'features'=>['Vulnerability Scanning', 'Access Controls', 'Audit Logs'],
                    'additional_info'=>'Essential for financial, healthcare, and legal sectors.'
                ],
            ],
        ];

        foreach ($basicProducts as $category => $products) {
            foreach ($products as $product) {
                DB::table('products')->insert([
                    'category' => $category,
                    'slug' => $product['slug'],
                    'name' => $product['name'],
                    'image' => $product['image'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'features' => json_encode($product['features']),
                    'additional_info' => $product['additional_info'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}