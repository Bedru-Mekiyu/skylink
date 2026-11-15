<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use App\Models\News;
use App\Models\Portfolio;
use App\Models\ContactMessage;
use App\Models\Subscriber;
use App\Models\Video;
class AdminController extends Controller
{
    /**
     * Display the admin dashboard with data.
     */
    public function index()
    {
       
// 2. GET THE COUNT FOR EACH MODEL
        $portfolioCount = Portfolio::count();
        $newsCount = News::count();
        $videoCount = Video::count();
        $subscriberCount = Subscriber::count();
        
        // You can also combine them if you like
        $totalContentCount = $newsCount + $videoCount;

        // 3. PASS THE DATA TO THE VIEW
        return view('admin.dashboard', compact(
            'portfolioCount', 
            'newsCount', 
            'videoCount', 
            'subscriberCount',
            'totalContentCount'
        ));

    }
}