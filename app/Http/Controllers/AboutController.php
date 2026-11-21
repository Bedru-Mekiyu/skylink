<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AboutController extends Controller
{
    public function index()
    {
        $data = [
            'pageTitle' => 'About Us',
            'heroTitle' => 'About Sky Link',
            'heroDescription' => 'We are Ethiopia\'s leading IT services company, dedicated to transforming businesses through innovative technology solutions.',
            
        ];
        
        return view('pages.aboutpage', $data);
    }

     public function about()
{
    $managementTeam = [
        [
            'name' => config('team.ceo.name'),
            'title' => config('team.ceo.title'),
            'image' => asset(config('team.ceo.image')),
            'lead' => true, // the CEO card is shown larger (see .team-card--lead)
            'bgColor' => '#f7f7f7ff',
        ],
        [
            'name' => 'Bedru Hussen',
            'title' => 'Head of business development',
            'image' => asset('assets/images/about/bedru hussen.png'),
            'bgColor' => '#f7f7f7ff',
        ],
        [
            'name' => 'Mohammed Oumer',
            'title' => 'Head of software department',
            'image' => asset('assets/images/about/mohammed oumer.png'),
            'bgColor' => '#f7f7f7ff',
        ],
        [
            'name' => 'Hanan Nesru',
            'title' => 'Head of Human Resource & Administration',
            // Cropped 4:5 copy of the owner's photo (480x600); the original is archived privately.
            'image' => asset('assets/images/about/hanan.jpg'),
            'webp' => asset('assets/images/_opt/about/hanan-480.webp'),
            'bgColor' => '#f7f7f7ff',
        ],
        [
            // PLACEHOLDER until the real name and photo arrive: see config/team.php (one-file swap).
            'name' => config('team.marketing.name'),
            'title' => config('team.marketing.title'),
            'image' => asset(config('team.marketing.image')),
            'bgColor' => '#f7f7f7ff',
        ],
        // [
        //     'name' => 'Mohammed Ahmed',
        //     'title' => 'project manager',
        //     'image' => asset('assets/images/about/mohammed ahmed.JPG'),
        //     'bgColor' => '#f7f7f7ff',
        // ],
        
        
    ];

    return view('pages.aboutpage', compact('managementTeam'));
}

}