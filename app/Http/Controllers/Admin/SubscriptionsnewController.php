<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesRecordList;
use App\Http\Controllers\Controller;
use App\Models\Subscription;

/** Admin > Subscriptions (plan requests): list, delete one, delete selected, CSV export. */
class SubscriptionsnewController extends Controller
{
    use ManagesRecordList;

    public function index()
    {
        $subscriptions = Subscription::latest()->paginate(10);

        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    protected function recordModel(): string
    {
        return Subscription::class;
    }

    protected function recordRoutes(): string
    {
        return 'admin.subscriptions';
    }

    protected function recordLabel(): string
    {
        return 'subscription';
    }

    protected function csvColumns(): array
    {
        return ['Name' => 'name', 'Email' => 'email', 'Phone' => 'phone', 'Plan' => 'plan', 'Notes' => 'notes', 'Subscribed' => 'created_at'];
    }
}
