@extends('admin.layouts.master')

@section('title', 'Site Settings')
@section('page-title', 'Site Settings')

@section('content')
<div class="container-fluid px-0">
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-semibold text-dark"><i class="fas fa-sliders text-primary me-2"></i>Public Site Settings</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <h6 class="fw-bold text-dark mb-2">Price Visibility</h6>
                            <p class="text-muted small mb-3">Controls whether prices are visible to the public across services and products. When turned off, all prices are hidden site-wide.</p>

                            <div class="form-check form-switch ps-0 d-flex align-items-center gap-3">
                                <input type="hidden" name="show_prices" value="0">
                                <input class="form-check-input ms-0" type="checkbox" role="switch" id="show_prices" name="show_prices" value="1" {{ $showPrices ? 'checked' : '' }} style="width: 3rem; height: 1.6rem; cursor: pointer;">
                                <label class="form-check-label fw-semibold text-dark mb-0" for="show_prices" style="cursor: pointer;">
                                    Show prices on the public site
                                </label>
                            </div>
                            <div class="form-text mt-2">
                                When enabled, each item's individual price checkbox is also respected. When disabled, no price is shown anywhere on the public site.
                            </div>
                        </div>

                        <hr class="my-4">

                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                            <i class="fas fa-save me-1"></i> Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
