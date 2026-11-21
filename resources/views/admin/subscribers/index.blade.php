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
@include('admin.partials.record-list', [
    'rows' => $subscribers, 'routes' => 'admin.subscribers', 'label' => 'subscriber',
    'nameOf' => fn ($r) => $r->email,
    'cols' => ['Email' => 'email', 'Subscribed' => fn ($r) => $r->created_at->format('M j, Y H:i')],
])
@endsection
