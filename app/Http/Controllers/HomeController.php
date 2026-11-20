<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\News;
use App\Models\Portfolio;



class HomeController extends Controller
{
    public function index()

    {
        // Short cache (60 s for news, which can go live on a schedule; 5 min for projects). Rebuilt at once after any admin save.
        $latestNews = \App\Support\PublicCache::remember('home.news', 60, fn () => News::visible()->newestFirst()->take(3)->get());
        $latestPortfolios = \App\Support\PublicCache::remember('home.portfolios', 300, fn () => Portfolio::latest()->take(6)->get());




        $data = [
            'heroTitle' => 'Helping businesses succeed with the best IT services in Ethiopia',
            'heroDescription' => 'Discover IT solutions that drive growth and innovation for your business. We provide comprehensive technology services tailored to your needs.',
            'companyDescription' => 'Sky Link is a leading IT services company in Ethiopia, dedicated to providing innovative technology solutions that help businesses thrive in the digital age. With years of experience and a team of skilled professionals, we deliver comprehensive IT services and solutions.',
            
            'stats' => [
                ['number' => '15+', 'label' => 'Years Experience'],
                ['number' => '84k', 'label' => 'Happy Clients'],
                ['number' => '600+', 'label' => 'Projects Completed']
            ],
            
            
            




            'products' => [
                [
                            'name' => 'iPhone 14 Pro Max 256GB',

                            'image' => asset('assets/images/products/software.jpg'),
                            'rating' => 5
                        ],
                        [
                            'name' => 'Apple AirPods',
                            'image' => asset('assets/images/products/cecurity.jpg'),
                            'rating' => 5
                        ],
                        [
                            'name' => 'iPhone 14 Pro 128GB',
                            'image' => asset('assets/images/products/erp.png'),
                            'rating' => 5
                        ]
            ]
            
            
        ];
        
return view('pages.index', array_merge($data, [
        'latestNews' => $latestNews,
        'latestPortfolios' => $latestPortfolios,
    ]));    }

    
}
