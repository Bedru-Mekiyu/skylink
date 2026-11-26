<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use App\Models\Service;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    public function category(string $categorySlug)
    {
        $category = ServiceCategory::where('slug', $categorySlug)->where('is_published', true)->first();

        if (!$category) {
            abort(404, 'Category not found.');
        }

        $categories = ServiceCategory::where('is_published', true)->withCount(['services' => fn ($q) => $q->visible()])->orderBy('sort_order')->orderBy('name')->get();
        $items = Service::visible()->where('category_id', $category->id)
            ->with(['options' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->paginate(9);

        $items->getCollection()->transform(function ($service) {
            if (is_numeric($service->price)) {
                $service->price_formatted = 'Br' . number_format($service->price, 2);
            } else {
                $service->price_formatted = $service->price;
            }
            foreach ($service->options as $opt) {
                $opt->setRelation('service', $service);
            }
            return $service;
        });

        return view('services.servicecategory', [
            'category' => $category->name,
            'categoryModel' => $category,
            'categories' => $categories,
            'items' => $items
        ]);
    }

    public function detail($subcategory)
    {
        // 1. Direct database match
        $service = Service::visible()->where('slug', $subcategory)->with(['category', 'options'])->first();

        // 2. Multi-environment candidate alias mapping
        if (!$service) {
            $aliasGroups = [
                'custom-software'       => ['custom-dev', 'custom-software'],
                'custom-dev'            => ['custom-software', 'custom-dev'],
                'physical-safety'       => ['physical-security', 'physical-safety'],
                'physical-security'     => ['physical-safety', 'physical-security'],
                'smart-infrastructure'  => ['integrated-infra', 'smart-infrastructure'],
                'integrated-infra'      => ['smart-infrastructure', 'integrated-infra'],
                'datacenter-infra'      => ['data-center', 'datacenter-infra'],
                'data-center'           => ['datacenter-infra', 'data-center'],
                'network-overview'      => ['enterprise-network', 'network-overview'],
                'enterprise-network'    => ['network-overview', 'enterprise-network'],
                'network-wlan'          => ['wlan-solution', 'network-lan'],
                'wlan-solution'         => ['network-lan', 'wlan-solution'],
                'network-lan'           => ['wlan-solution', 'network-lan'],
                'smart-city-consulting' => ['smart-projects', 'smart-city-consulting'],
                'smart-projects'        => ['smart-city-consulting', 'smart-projects'],
                'product-development'   => ['product-dev', 'product-development'],
                'product-dev'           => ['product-development', 'product-dev'],
                'erp-consult'           => ['erp-consultancy', 'erp-consult'],
                'erp-consultancy'       => ['erp-consult', 'erp-consultancy'],
            ];

            if (isset($aliasGroups[$subcategory])) {
                foreach ($aliasGroups[$subcategory] as $candidate) {
                    if ($candidate !== $subcategory) {
                        $found = Service::visible()->where('slug', $candidate)->with(['category', 'options'])->first();
                        if ($found) {
                            return redirect()->route('services.detail', ['subcategory' => $found->slug], 301);
                        }
                    }
                }
            }
        }

        // 3. Fallback: check if subcategory is a ServiceCategory slug
        if (!$service) {
            $cat = ServiceCategory::where('slug', $subcategory)->first();
            if ($cat) {
                return redirect()->route('services.category', ['category' => $cat->slug], 301);
            }
            // 4. Fallback: check if subcategory matches a Product slug
            $prod = Product::visible()->where('slug', $subcategory)->first();
            if ($prod) {
                return redirect()->route('products.detail', ['category' => $prod->category, 'slug' => $prod->slug], 301);
            }
            return redirect()->route('services')->with('warning', __('Service not found.'));
        }

        $categories = ServiceCategory::where('is_published', true)->with(['services' => fn ($q) => $q->visible()])->orderBy('sort_order')->orderBy('name')->get();

        return view('services.servicedetail', [
            'service' => $service,
            'categories' => $categories
        ]);
    }

    public function resolveServiceOrCategory(string $slug)
    {
        // 1. Direct service match
        if ($service = Service::visible()->where('slug', $slug)->first()) {
            return redirect()->route('services.detail', ['subcategory' => $service->slug], 301);
        }

        // 2. Candidate alias match
        $aliasGroups = [
            'custom-software'       => ['custom-dev', 'custom-software'],
            'custom-dev'            => ['custom-software', 'custom-dev'],
            'physical-safety'       => ['physical-security', 'physical-safety'],
            'physical-security'     => ['physical-safety', 'physical-security'],
            'smart-infrastructure'  => ['integrated-infra', 'smart-infrastructure'],
            'integrated-infra'      => ['smart-infrastructure', 'integrated-infra'],
            'datacenter-infra'      => ['data-center', 'datacenter-infra'],
            'data-center'           => ['datacenter-infra', 'data-center'],
            'network-overview'      => ['enterprise-network', 'network-overview'],
            'enterprise-network'    => ['network-overview', 'enterprise-network'],
            'network-wlan'          => ['wlan-solution', 'network-lan'],
            'wlan-solution'         => ['network-lan', 'wlan-solution'],
            'network-lan'           => ['wlan-solution', 'network-lan'],
            'smart-city-consulting' => ['smart-projects', 'smart-city-consulting'],
            'smart-projects'        => ['smart-city-consulting', 'smart-projects'],
            'product-development'   => ['product-dev', 'product-development'],
            'product-dev'           => ['product-development', 'product-dev'],
            'erp-consult'           => ['erp-consultancy', 'erp-consult'],
            'erp-consultancy'       => ['erp-consult', 'erp-consultancy'],
        ];

        if (isset($aliasGroups[$slug])) {
            foreach ($aliasGroups[$slug] as $candidate) {
                if ($candidate !== $slug) {
                    $found = Service::visible()->where('slug', $candidate)->first();
                    if ($found) {
                        return redirect()->route('services.detail', ['subcategory' => $found->slug], 301);
                    }
                }
            }
        }

        // 3. Category match
        if ($category = ServiceCategory::where('slug', $slug)->first()) {
            return redirect()->route('services.category', ['category' => $category->slug], 301);
        }

        // 4. Product match
        if ($product = Product::visible()->where('slug', $slug)->first()) {
            return redirect()->route('products.detail', ['category' => $product->category, 'slug' => $product->slug], 301);
        }

        return redirect()->route('services')->with('warning', __('Page not found.'));
    }

    public function index()
    {
        try {
            $serviceCategories = \App\Support\PublicCache::remember('services.categories', 300, fn () => ServiceCategory::where('is_published', true)->get());
            $portfolioItems = \App\Support\PublicCache::remember('services.items', 300, fn () => Service::visible()->with('category')->get())->map(function ($service) {
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
