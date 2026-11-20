@extends('admin.layouts.master')

@section('title', 'Newsletter Subscribers')
@section('page-title', 'Newsletter Subscribers')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card">
    <div class="card-header fw-semibold">{{ $subscribers->total() }} {{ \Illuminate\Support\Str::plural('subscriber', $subscribers->total()) }}</div>
    <div class="table-wrapper">
        <table class="table table-hover align-middle mb-0">
            <caption class="visually-hidden">Newsletter subscribers, newest first</caption>
            <thead>
                <tr>
                    <th scope="col" class="ps-3">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col" class="text-end pe-3">Subscribed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscribers as $subscriber)
                <tr>
                    <td class="ps-3 fw-semibold">{{ $subscriber->name ?: '—' }}</td>
                    <td class="text-break">{{ $subscriber->email }}</td>
                    <td class="text-end pe-3 text-nowrap text-muted">{{ $subscriber->created_at->format('M j, Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center text-muted py-5">No subscribers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($subscribers->hasPages())
        <div class="card-footer d-flex justify-content-center">{{ $subscribers->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection
