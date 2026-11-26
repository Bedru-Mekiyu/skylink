@extends('admin.layouts.master')

@section('title', 'Site & Payment Settings')
@section('page-title', 'Site & Payment Settings')

@section('content')
<div class="container-fluid px-0">
    <div class="row">
        <div class="col-12 col-lg-8">
            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Card 1: Public Site & Catalog Settings --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-semibold text-dark"><i class="fas fa-sliders text-primary me-2"></i>Public Site Settings</h5>
                    </div>
                    <div class="card-body p-4">
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

                        <div class="mb-2">
                            <h6 class="fw-bold text-dark mb-2">Tax Note Under Prices</h6>
                            <p class="text-muted small mb-3">Optional note displayed beneath prices on product/service cards and the request cart (e.g. "Prices exclude 15% VAT"). Leave blank to hide tax notes completely.</p>
                            <input type="text" class="form-control" name="tax_note" value="{{ old('tax_note', $taxNote ?? '') }}" placeholder="e.g. Prices exclude 15% VAT" maxlength="255">
                        </div>
                    </div>
                </div>

                {{-- Card 2: Chapa Payment Gateway Settings --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-success-subtle text-success rounded-3 p-2 me-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <h5 class="mb-0 fw-semibold text-dark">Chapa Payment Gateway</h5>
                        </div>
                        <span class="badge {{ $chapaEnabled ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary' }} px-3 py-2">
                            {{ $chapaEnabled ? 'Gateway Enabled' : 'Gateway Disabled' }}
                        </span>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <h6 class="fw-bold text-dark mb-2">Enable Online Payments</h6>
                            <p class="text-muted small mb-3">When enabled, visitors can select "Pay Online via Chapa" during checkout for Telebirr, CBEBirr, AwashBirr, and Debit/Credit Cards.</p>

                            <div class="form-check form-switch ps-0 d-flex align-items-center gap-3">
                                <input type="hidden" name="chapa_enabled" value="0">
                                <input class="form-check-input ms-0" type="checkbox" role="switch" id="chapa_enabled" name="chapa_enabled" value="1" {{ $chapaEnabled ? 'checked' : '' }} style="width: 3rem; height: 1.6rem; cursor: pointer;">
                                <label class="form-check-label fw-semibold text-dark mb-0" for="chapa_enabled" style="cursor: pointer;">
                                    Enable Chapa Online Payments at Checkout
                                </label>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="fw-bold text-dark mb-2">Server settings</h6>
                        <p class="text-muted small">Keys are set in the server’s environment file by whoever deploys. They are never stored here or shown. Payment works only when all three are filled in and the switch above is on.</p>
                        <ul class="list-unstyled small mb-3">
                            @foreach($chapaServer as $name => $ok)
                                <li><i class="fas {{ $ok ? 'fa-check text-success' : 'fa-times text-danger' }} me-2" aria-hidden="true"></i><code>{{ $name }}</code> {{ $ok ? 'set' : 'missing' }}</li>
                            @endforeach
                            @if($chapaServer['CHAPA_SECRET_KEY'])<li class="mt-1">Key type: <strong>{{ $chapaTestMode ? 'TEST' : 'LIVE' }}</strong></li>@endif
                        </ul>
                        <div class="alert alert-light border rounded-3 p-3 small text-muted">
                            <div class="fw-semibold text-dark mb-1">Webhook address for the Chapa dashboard</div>
                            <code class="user-select-all">{{ route('webhooks.chapa') }}</code>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="d-flex align-items-center gap-3">
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
                        <i class="fas fa-save me-1"></i> Save All Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection
