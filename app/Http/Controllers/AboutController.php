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
            'name' => 'Mohammedkerem Ahmed',
            'title' => 'CEO',
            'image' => asset('assets/images/about/mohammedkerem.JPG'),
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
            // Optimised copy of the owner's photo; the card shows initials until the file exists.
            'image' => is_file(public_path('assets/images/about/hanan.png')) ? asset('assets/images/about/hanan.png') : null,
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