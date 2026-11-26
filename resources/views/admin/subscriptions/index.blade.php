@extends('admin.layouts.master')

@section('title', 'All Subscriptions')
@section('page-title', 'Subscriptions')

@section('content')
@include('admin.partials.record-list', [
    'rows' => $subscriptions, 'routes' => 'admin.subscriptions', 'label' => 'subscription',
    'nameOf' => fn ($r) => $r->email ?: $r->name,
    'cols' => ['Name' => 'name', 'Email' => 'email', 'Phone' => 'phone', 'Plan' => fn ($r) => ucfirst((string) $r->plan), 'Notes' => 'notes', 'Subscribed' => fn ($r) => $r->created_at->format('Y-m-d H:i')],
])
@endsection
