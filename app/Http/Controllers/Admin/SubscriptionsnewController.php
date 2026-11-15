<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
    use Illuminate\Support\Facades\Log;


class SubscriptionsnewController extends Controller
{
        public function index()
{
    // Fetch all subscriptions from the database
$subscriptions = Subscription::paginate(10);

    // Pass the subscriptions variable to the view
    return view('admin.subscriptions.index', compact('subscriptions'));
}
}
