@extends('admin.layouts.master')

@php $name = $type === 'service' ? $item->title : $item->name; @endphp
@section('title', 'Prices: ' . $name)
@section('page-title', 'Prices: ' . $name)

@section('content')
    <a href="{{ route('admin.prices.index') }}" class="btn btn-outline-secondary btn-sm">&larr; All prices</a>
    @include('admin.partials.option-editor', ['type' => $type, 'item' => $item])
@endsection
