@extends('admin.layouts.master')

@section('content')
<div class="container my-5">
    <h1 class="display-4 fw-bold mb-4">Newsletter Subscribers</h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="table-responsive shadow-sm rounded-3 mb-4">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th scope="col" style="width: 5%; text-align: center;">#</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col" class="text-center">Subscribed At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subscribers as $subscriber)
                <tr class="transition-hover">
                    <td class="text-center">{{ $loop->iteration + ($subscribers->currentPage() - 1) * $subscribers->perPage() }}</td>
                    <td class="fw-semibold">{{ $subscriber->name }}</td>
                    <td>{{ $subscriber->email }}</td>
                    <td class="text-center">{{ $subscriber->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center">
        {{ $subscribers->links() }}
    </div>
</div>

<!-- Optional: hover effect for rows -->
<style>
.transition-hover:hover {
    background-color: #f8f9fa; /* Light gray on hover */
    transition: background-color 0.3s ease;
}
</style>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-ENjdO4Dr2bkBIFxQpeo6Fj6Y6K6f6/p1Q1zE3v7b3b4UGaXKJ0O5Qb7J7VfR5vV" crossorigin="anonymous">

<!-- Bootstrap Icons CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
@endsection