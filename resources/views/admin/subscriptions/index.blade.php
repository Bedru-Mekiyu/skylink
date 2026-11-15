@extends('admin.layouts.master') 

@section('title', 'All Subscriptions')

@section('content')
<div class="container py-5">
    <h2 class="mb-4">Subscription List</h2>

    @if($subscriptions->count())
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Plan</th>
                        <th>Notes</th>
                        <th>Subscribed At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subscriptions as $index => $sub)
                        <tr>
                            <td>{{ $index + $subscriptions->firstItem() }}</td>
                            <td>{{ $sub->name }}</td>
                            <td>{{ $sub->email }}</td>
                            <td>{{ $sub->phone }}</td>
                            <td>{{ ucfirst($sub->plan) }}</td>
                            <td>{{ $sub->notes }}</td>
                            <td>{{ $sub->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $subscriptions->links() }}
    @else
        <div class="alert alert-info">No subscriptions found.</div>
    @endif
</div>
@endsection
