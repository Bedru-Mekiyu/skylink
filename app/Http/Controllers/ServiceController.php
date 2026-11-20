<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use App\Models\Service;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    public function category(string $categorySlug)
    {
        $category = ServiceCategory::where('slug', $categorySlug)->with('services')->first();

        if (!$category) {
            abort(404, 'Category not found.');
        }

        $items = Service::where('category_id', $category->id)->paginate(9);

        $items->getCollection()->transform(function ($service) {
            if (is_numeric($service->price)) {
                $service->price_formatted = 'Br' . number_format($service->price, 2);
            } else {
                $service->price_formatted = $service->price;
            }
            return $service;
        });

        return view('services.servicecategory', [
            'category' => $category->name,
            'items' => $items
        ]);
    }

    public function detail($subcategory)
    {
        $service = Service::where('slug', $subcategory)->firstOrFail();

        return view('services.servicedetail', [
            'service' => $service
        ]);
    }

    public function index()
    {
        try {
            $serviceCategories = \App\Support\PublicCache::remember('services.categories', 300, fn () => ServiceCategory::withCount('services')->get());
            $portfolioItems = \App\Support\PublicCache::remember('services.items', 300, fn () => Service::with('category')->get())->map(function ($service) {
                return [
                    'title' => $service->title,
                    'description' => $service->desc,
                    'image' => asset('assets/images/services/' . $service->image),
                    'category' => $service->category?->slug ?? 'uncategorized',
                    'link' => route('services.detail', ['subcategory' => $service->slug])
                ];
            });

            $data = [
                'pageTitle' => 'Our Services',
                'heroTitle' => 'Our Services',
                'heroDescription' => 'Explore our comprehensive range of IT services designed to help your business succeed in the digital world.',
                'techServices' => [
                    'title' => 'Our Top Tech Services',
                    'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
                    'technologies' => [
                        ['name' => 'Laravel', 'icon' => 'fab fa-laravel', 'color' => '#ff2d20'],
                        ['name' => 'Angular', 'icon' => 'fab fa-angular', 'color' => '#dd0031'],
                        ['name' => 'Node.js', 'icon' => 'fab fa-node-js', 'color' => '#339933'],
                        ['name' => 'Vue.js', 'icon' => 'fab fa-vuejs', 'color' => '#4fc08d'],
                        ['name' => '.NET', 'icon' => 'fas fa-code', 'color' => '#512bd4']
                    ]
                ],
                'portfolioItems' => $portfolioItems,
                // Cards + filter buttons, managed in Admin > Service Categories.
                'serviceCards' => ServiceCategory::forServicesPage()->get(),
                
                'quoteSection' => [
                    'title' => 'Need A Free Quote? Please Feel Free to Contact Us',
                    'subtitle' => 'REQUEST A QUOTE',
                    'description' => 'Let us know specifically what you need...',
                    'features' => [
                        ['icon' => 'fas fa-clock', 'text' => 'Reply within 24 hours'],
                        ['icon' => 'fas fa-phone', 'text' => '12 hrs telephone support']
                    ],
                    'phone' => '+251 123 456 789',
                    'services' => [
                        'web-development' => 'Web Development',
                        'mobile-development' => 'Mobile Development',
                        'cloud-solutions' => 'Cloud Solutions',
                        'it-consulting' => 'IT Consulting',
                        'digital-marketing' => 'Digital Marketing',
                        'custom-software' => 'Custom Software Development'
                    ]
                ]
            ];

            return view('services.index', $data);
        } catch (\Exception $e) {
            Log::error('Error loading services: ' . $e->getMessage());

            return view('services.index', [
                'pageTitle' => 'Our Services',
                'heroTitle' => 'Our Services',
                'heroDescription' => 'We are currently updating our service listings. Please check back soon!',
                'portfolioItems' => [],
                'techServices' => null,
                'testimonials' => [],
                'quoteSection' => []
            ])->with('error', 'Failed to load services.');
        }
    }
}
