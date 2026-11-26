@extends('admin.layouts.master')

@section('content')
<div class="container mt-5">
    <h1 class="h3 mb-2">Product Categories</h1>
    <p class="text-muted mb-4">
        Categories appear automatically when you save a product. The <strong>pricing mode</strong> decides how its products are sold:
        <em>Subscription</em> (tiers + monthly / quarterly / semi-annual / annual), <em>One-time</em> (single price),
        <em>Quote only</em> (no price, no online payment, e.g. data center, power).
    </p>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="table-responsive">
        <table class="table align-middle bg-white shadow-sm">
            <thead>
                <tr>
                    <th>Name</th><th>Pricing mode</th><th style="width:90px">Order</th>
                    <th>Shown</th><th>Products</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($categories as $c)
                <tr>
                    <form method="POST" action="{{ route('admin.product-categories.update', $c) }}" id="pc-{{ $c->id }}">
                        @csrf @method('PUT')
                    </form>
                    <td>
                        <input form="pc-{{ $c->id }}" name="name" class="form-control form-control-sm" value="{{ $c->name }}" required maxlength="120" aria-label="Name">
                        <input form="pc-{{ $c->id }}" name="description" class="form-control form-control-sm mt-1" value="{{ $c->description }}" maxlength="500" placeholder="Short description (optional)" aria-label="Description">
                        <div class="small text-muted mt-1">{{ $c->slug }}</div>
                    </td>
                    <td>
                        <select form="pc-{{ $c->id }}" name="pricing_mode" class="form-select form-select-sm" aria-label="Pricing mode">
                            <option value="subscription" @selected($c->pricing_mode === 'subscription')>Subscription (terms)</option>
                            <option value="one_time" @selected($c->pricing_mode === 'one_time')>One-time</option>
                            <option value="quote_only" @selected($c->pricing_mode === 'quote_only')>Quote only (no price)</option>
                        </select>
                    </td>
                    <td><input form="pc-{{ $c->id }}" type="number" name="sort_order" min="0" max="9999" class="form-control form-control-sm" value="{{ $c->sort_order }}" aria-label="Order"></td>
                    <td><input form="pc-{{ $c->id }}" type="checkbox" name="is_published" value="1" class="form-check-input" @checked($c->is_published) aria-label="Shown"></td>
                    <td>{{ $c->products_count }}</td>
                    <td><button form="pc-{{ $c->id }}" class="btn btn-primary btn-sm">Save</button></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No product categories yet. Add a product first.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
