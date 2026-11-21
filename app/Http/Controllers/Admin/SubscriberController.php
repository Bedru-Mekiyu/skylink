<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesRecordList;
use App\Http\Controllers\Controller;
use App\Models\Subscriber;

/** Admin > Newsletter Subscribers: list, delete one, delete selected, CSV export. */
class SubscriberController extends Controller
{
    use ManagesRecordList;

    public function index()
    {
        $subscribers = Subscriber::latest()->paginate(20);

        return view('admin.subscribers.index', compact('subscribers'));
    }

    protected function recordModel(): string
    {
        return Subscriber::class;
    }

    protected function recordRoutes(): string
    {
        return 'admin.subscribers';
    }

    protected function recordLabel(): string
    {
        return 'subscriber';
    }

    protected function csvColumns(): array
    {
        return ['Email' => 'email', 'Subscribed' => 'created_at'];
    }
}
