@extends('admin.layouts.master')

@section('title', 'Newsletter Subscribers')
@section('page-title', 'Newsletter Subscribers')

@section('content')
@include('admin.partials.record-list', [
    'rows' => $subscribers, 'routes' => 'admin.subscribers', 'label' => 'subscriber',
    'nameOf' => fn ($r) => $r->email,
    'cols' => ['Email' => 'email', 'Subscribed' => fn ($r) => $r->created_at->format('M j, Y H:i')],
])
@endsection
