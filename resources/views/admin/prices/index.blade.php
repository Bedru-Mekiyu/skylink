@extends('admin.layouts.master')

@section('title', 'Catalog Prices & Tiers Overview')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-gray-800 mb-1">Catalog Prices & Tiers Overview</h1>
            <p class="text-muted small mb-0">Control, adjust, add, and audit all pricing tiers, display texts, and visibility switches across services and products.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addTierModal">
                <i class="fas fa-plus-circle me-1"></i> Add New Tier
            </button>
            <button type="button" class="btn btn-outline-info shadow-sm" data-bs-toggle="modal" data-bs-target="#discountHelperModal">
                <i class="fas fa-calculator me-1"></i> Discount % Helper
            </button>
            <a href="{{ route('admin.prices.export') }}" class="btn btn-outline-success">
                <i class="fas fa-file-csv me-1"></i> Export to CSV
            </a>
            @if(Auth::user()->hasRole('super_admin'))
                <a href="{{ route('admin.settings.edit') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-cog me-1"></i> Global Price Settings
                </a>
            @endif
        </div>
    </div>


    {{-- Quick Metrics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3 text-primary">
                        <i class="fas fa-tags fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Tiers</div>
                        <div class="h4 fw-bold mb-0 text-dark">{{ $metrics['total'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3 text-info">
                        <i class="fas fa-cogs fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Service Tiers</div>
                        <div class="h4 fw-bold mb-0 text-dark">{{ $metrics['services'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3 text-success">
                        <i class="fas fa-box fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Product Tiers</div>
                        <div class="h4 fw-bold mb-0 text-dark">{{ $metrics['products'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-6 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3 text-warning">
                        <i class="fas fa-exclamation-triangle fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Needs Review</div>
                        <div class="h4 fw-bold mb-0 text-dark">{{ $metrics['needs_review'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-secondary bg-opacity-10 p-3 me-3 text-secondary">
                        <i class="fas fa-file-invoice-dollar fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Quote Only</div>
                        <div class="h4 fw-bold mb-0 text-dark">{{ $metrics['quote_only'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter toolbar & Bulk Actions --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.prices.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-sm-6 col-md-2">
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="service" @selected(request('type') === 'service')>Services Only</option>
                        <option value="product" @selected(request('type') === 'product')>Products Only</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <select name="visibility" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Visibility</option>
                        <option value="visible" @selected(request('visibility') === 'visible')>Visible Prices Only</option>
                        <option value="quote" @selected(request('visibility') === 'quote')>Quote-Only / Hidden</option>
                    </select>
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <select name="currency" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Currencies</option>
                        <option value="ETB" @selected(request('currency') === 'ETB')>ETB</option>
                        <option value="USD" @selected(request('currency') === 'USD')>USD</option>
                    </select>
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <select name="needs_review" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Review Statuses</option>
                        <option value="1" @selected(request('needs_review') == '1')>Needs Review Only</option>
                    </select>
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search tier or price..." value="{{ request('search') }}">
                </div>
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    @if(request()->hasAny(['type', 'visibility', 'currency', 'needs_review', 'search']))
                        <a href="{{ route('admin.prices.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk Action Form --}}
    <form method="POST" action="{{ route('admin.prices.bulk-reviewed') }}" id="bulkReviewForm">
        @csrf
        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-sm btn-warning text-dark" id="bulkReviewBtn" disabled>
                    <i class="fas fa-check-double me-1"></i> Mark Selected as Reviewed
                </button>
                <span class="text-muted small" id="selectedCountText">0 selected</span>
            </div>
        </div>

        {{-- Options Table --}}
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;" class="text-center">
                                <input type="checkbox" class="form-check-input" id="selectAllCheckbox" title="Select all on this page">
                            </th>
                            <th style="width: 70px;">Type</th>
                            <th style="min-width: 160px;">Catalog Item</th>
                            <th style="min-width: 140px;">Option Name</th>
                            <th style="width: 80px;">Currency</th>
                            <th style="width: 120px;">Amount</th>
                            <th style="min-width: 140px;">Display Text</th>
                            <th style="width: 120px;">Billing Term</th>
                            <th style="width: 60px;" class="text-center" title="Visible on Website">Show</th>
                            <th style="width: 60px;" class="text-center" title="Ready to Order">Ready</th>
                            <th style="width: 60px;" class="text-center" title="Payable via Chapa Online">Online</th>
                            <th style="width: 100px;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($options as $option)
                            @php
                                $itemName = $option->item_type === 'service' ? ($option->service?->title ?? 'N/A') : ($option->product?->name ?? 'N/A');
                                $editRoute = $itemName === 'N/A' ? '#' : route('admin.prices.item', [$option->item_type, $option->item_id]);
                            @endphp
                            <tr class="{{ $option->needs_review ? 'table-warning bg-opacity-25' : '' }}">
                                <td class="text-center">
                                    <input type="checkbox" name="option_ids[]" value="{{ $option->id }}" class="form-check-input option-checkbox" form="bulkReviewForm">
                                </td>
                                <td>
                                    <span class="badge {{ $option->item_type === 'service' ? 'bg-primary' : 'bg-info' }} text-uppercase" style="font-size: 10px;">
                                        {{ $option->item_type }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ $editRoute }}" class="fw-semibold text-decoration-none text-dark small" title="Edit Catalog Item">
                                        {{ $itemName }}
                                    </a>
                                </td>
                                <form method="POST" action="{{ route('admin.prices.update', $option) }}" id="form-update-{{ $option->id }}">
                                    @csrf
                                    @method('PUT')
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $option->name }}" required>
                                            @if($option->needs_review)
                                                <span class="badge bg-warning text-dark border border-warning-subtle" title="Flagged for review" style="font-size: 10px;">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </span>
                                            @endif
                                        </div>
                                        @if($option->hasMultipleTerms())
                                            <div class="mt-1">
                                                <span class="badge bg-primary-subtle text-primary border" style="font-size: 10px;" title="{{ implode(', ', array_keys($option->term_prices)) }}">
                                                    <i class="fas fa-layer-group me-1"></i>{{ count($option->term_prices) }} Terms
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <select name="currency" class="form-select form-select-sm">
                                            <option value="ETB" @selected($option->currency === 'ETB')>ETB</option>
                                            <option value="USD" @selected($option->currency === 'USD')>USD</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="price_amount" class="form-control form-control-sm" value="{{ $option->price_amount }}" placeholder="Empty = Quote">
                                    </td>
                                    <td>
                                        <input type="text" name="price_display_text" class="form-control form-control-sm" value="{{ $option->price_display_text }}" placeholder="e.g. From 25,000 ETB">
                                    </td>
                                    <td>
                                        <select name="billing_term" class="form-select form-select-sm">
                                            <option value="one-time" @selected($option->billing_term === 'one-time')>One-time</option>
                                            <option value="monthly" @selected($option->billing_term === 'monthly')>Monthly</option>
                                            <option value="quarterly" @selected($option->billing_term === 'quarterly')>Quarterly</option>
                                            <option value="semi-annually" @selected($option->billing_term === 'semi-annually')>Semi-Annually</option>
                                            <option value="annually" @selected($option->billing_term === 'annually')>Annually</option>
                                            <option value="project-based" @selected($option->billing_term === 'project-based')>Project-based</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input" type="checkbox" name="show_price" value="1" @checked($option->show_price)>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input" type="checkbox" name="is_ready" value="1" @checked($option->is_ready)>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input" type="checkbox" name="is_payable_online" value="1" @checked($option->is_payable_online)>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button type="submit" class="btn btn-sm btn-primary me-1" title="Save Option Changes">
                                            <i class="fas fa-save"></i>
                                        </button>
                                </form>
                                        <form method="POST" action="{{ route('admin.prices.toggle-review', $option) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $option->needs_review ? 'btn-warning text-dark' : 'btn-outline-secondary' }} me-1" title="{{ $option->needs_review ? 'Mark as Reviewed' : 'Flag for Review' }}">
                                                <i class="fas fa-flag"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.prices.destroy', $option) }}" class="d-inline" data-sky-confirm="Delete the pricing tier “{{ $option->name }}”?" data-sky-title="Delete tier?" data-sky-ok="Delete" data-sky-variant="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Tier">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-5 text-muted">
                                    <i class="fas fa-search fa-2x mb-3 d-block text-secondary opacity-50"></i>
                                    No catalog options found matching your filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($options->hasPages())
                <div class="card-footer bg-white py-3">
                    {{ $options->links() }}
                </div>
            @endif
        </div>
    </form>
</div>

{{-- Add New Tier Modal --}}
<div class="modal fade" id="addTierModal" tabindex="-1" aria-labelledby="addTierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('admin.prices.store') }}">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="addTierModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Add New Pricing Tier
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Item Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="item_type" id="typeService" value="service" checked onchange="toggleItemSelect()">
                                <label class="form-check-label fw-semibold" for="typeService">Service</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="item_type" id="typeProduct" value="product" onchange="toggleItemSelect()">
                                <label class="form-check-label fw-semibold" for="typeProduct">Product</label>
                            </div>
                        </div>
                    </div>

                    {{-- Service Selector --}}
                    <div class="mb-3" id="serviceSelectWrapper">
                        <label class="form-label fw-semibold">Target Service <span class="text-danger">*</span></label>
                        <select name="service_id" id="serviceSelect" class="form-select">
                            @foreach($services as $svc)
                                <option value="{{ $svc->id }}">{{ $svc->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Product Selector --}}
                    <div class="mb-3 d-none" id="productSelectWrapper">
                        <label class="form-label fw-semibold">Target Product <span class="text-danger">*</span></label>
                        <select name="product_id" id="productSelect" class="form-select">
                            @foreach($products as $prd)
                                <option value="{{ $prd->id }}">{{ $prd->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="item_id" id="targetItemId" value="{{ $services->first()?->id }}">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tier Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Starter, Business, Enterprise, 1-Year License" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label fw-semibold">Currency <span class="text-danger">*</span></label>
                            <select name="currency" class="form-select">
                                <option value="ETB" selected>ETB</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                        <div class="col-8">
                            <label class="form-label fw-semibold">Price Amount (Numeric)</label>
                            <input type="number" step="0.01" min="0" name="price_amount" id="modalPriceAmount" class="form-control" placeholder="Empty = Quote">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Display Price Text (Optional)</label>
                        <input type="text" name="price_display_text" class="form-control" placeholder="e.g. From 25,000 ETB, $120/mo">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Billing Term</label>
                        <select name="billing_term" class="form-select">
                            <option value="monthly" selected>Monthly</option>
                            <option value="one-time">One-time</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="semi-annually">Semi-Annually</option>
                            <option value="annually">Annually</option>
                            <option value="project-based">Project-based</option>
                        </select>
                    </div>

                    <div class="card bg-light border p-3 mb-3 rounded-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-dark mb-0">
                                <i class="fas fa-calendar-alt text-primary me-1"></i> Multi-Term Explicit Prices
                            </label>
                            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" onclick="prefillFromModalHelper()">
                                <i class="fas fa-magic me-1"></i>Auto-Calculate Discounts
                            </button>
                        </div>
                        <small class="text-muted d-block mb-2" style="font-size: 11px;">Values are stored explicitly in the database:</small>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small text-muted mb-0" style="font-size: 11px;">Monthly (ETB/USD)</label>
                                <input type="number" step="0.01" min="0" name="term_prices[monthly]" id="modalTermMonthly" class="form-control form-control-sm" placeholder="e.g. 15757.90">
                            </div>
                            <div class="col-6">
                                <label class="small text-muted mb-0" style="font-size: 11px;">Quarterly (ETB/USD)</label>
                                <input type="number" step="0.01" min="0" name="term_prices[quarterly]" id="modalTermQuarterly" class="form-control form-control-sm" placeholder="e.g. 44910.00">
                            </div>
                            <div class="col-6">
                                <label class="small text-muted mb-0" style="font-size: 11px;">Semi-Annually (ETB/USD)</label>
                                <input type="number" step="0.01" min="0" name="term_prices[semi-annually]" id="modalTermSemiAnnually" class="form-control form-control-sm" placeholder="e.g. 85092.60">
                            </div>
                            <div class="col-6">
                                <label class="small text-muted mb-0" style="font-size: 11px;">Annually (ETB/USD)</label>
                                <input type="number" step="0.01" min="0" name="term_prices[annually]" id="modalTermAnnually" class="form-control form-control-sm" placeholder="e.g. 151275.70">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Short Highlights / Features (1 per line, up to 3)</label>
                        <textarea name="short_features" class="form-control" rows="3" placeholder="Dedicated support&#10;Up to 5 team members&#10;Full API access"></textarea>
                    </div>

                    <div class="row g-2">
                        <div class="col-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="show_price" value="1" id="modalShowPrice" checked>
                                <label class="form-check-label small" for="modalShowPrice">Show Price</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_ready" value="1" id="modalIsReady" checked>
                                <label class="form-check-label small" for="modalIsReady">Ready</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_payable_online" value="1" id="modalPayableOnline">
                                <label class="form-check-label small" for="modalPayableOnline">Payable Online</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Create Pricing Tier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Discount Calculation Helper Modal --}}
<div class="modal fade" id="discountHelperModal" tabindex="-1" aria-labelledby="discountHelperModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold" id="discountHelperModalLabel">
                    <i class="fas fa-calculator me-2"></i>Term Discount Calculator Helper
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    Calculate pre-filled explicit term prices from a monthly base price and discount percentages. You can review, adjust, or copy them. <em>Formulas are never stored in code; only explicit numbers are saved.</em>
                </p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Monthly Base Price (Br)</label>
                    <input type="number" step="0.01" min="0" id="calcBasePrice" class="form-control form-control-lg fw-bold text-primary" placeholder="e.g. 15757.90" value="15757.90" oninput="calculateDiscounts()">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label small fw-semibold">Quarterly % Off</label>
                        <input type="number" step="0.1" min="0" max="100" id="calcQuarterlyDisc" class="form-control form-control-sm" value="5" oninput="calculateDiscounts()">
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-semibold">Semi-Annual % Off</label>
                        <input type="number" step="0.1" min="0" max="100" id="calcSemiDisc" class="form-control form-control-sm" value="10" oninput="calculateDiscounts()">
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-semibold">Annual % Off</label>
                        <input type="number" step="0.1" min="0" max="100" id="calcAnnualDisc" class="form-control form-control-sm" value="20" oninput="calculateDiscounts()">
                    </div>
                </div>

                <div class="card bg-light border p-3 rounded-3">
                    <h6 class="fw-bold mb-2 text-dark">Calculated Explicit Prices:</h6>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted small">Monthly (1 mo):</span>
                        <span class="fw-bold text-dark" id="outMonthly">15,757.90 Br</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted small">Quarterly (3 mo, 5% off):</span>
                        <span class="fw-bold text-dark" id="outQuarterly">44,910.00 Br</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted small">Semi-Annually (6 mo, 10% off):</span>
                        <span class="fw-bold text-dark" id="outSemiAnnually">85,092.60 Br</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted small">Annually (12 mo, 20% off):</span>
                        <span class="fw-bold text-dark" id="outAnnually">151,275.70 Br</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="applyDiscountsToNewTierModal()">
                    <i class="fas fa-check me-1"></i> Pre-Fill New Tier Modal
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleItemSelect() {
        const isService = document.getElementById('typeService').checked;
        const svcWrapper = document.getElementById('serviceSelectWrapper');
        const prdWrapper = document.getElementById('productSelectWrapper');
        const hiddenItemId = document.getElementById('targetItemId');
        const svcSelect = document.getElementById('serviceSelect');
        const prdSelect = document.getElementById('productSelect');

        if (isService) {
            svcWrapper.classList.remove('d-none');
            prdWrapper.classList.add('d-none');
            hiddenItemId.value = svcSelect.value;
        } else {
            svcWrapper.classList.add('d-none');
            prdWrapper.classList.remove('d-none');
            hiddenItemId.value = prdSelect.value;
        }
    }

    document.getElementById('serviceSelect')?.addEventListener('change', function() {
        if (document.getElementById('typeService').checked) {
            document.getElementById('targetItemId').value = this.value;
        }
    });

    document.getElementById('productSelect')?.addEventListener('change', function() {
        if (document.getElementById('typeProduct').checked) {
            document.getElementById('targetItemId').value = this.value;
        }
    });

    // Bulk selection handler
    const selectAll = document.getElementById('selectAllCheckbox');
    const optionCheckboxes = document.querySelectorAll('.option-checkbox');
    const bulkReviewBtn = document.getElementById('bulkReviewBtn');
    const countText = document.getElementById('selectedCountText');

    function updateBulkState() {
        const checkedCount = document.querySelectorAll('.option-checkbox:checked').length;
        if (bulkReviewBtn) bulkReviewBtn.disabled = checkedCount === 0;
        if (countText) countText.textContent = `${checkedCount} selected`;
    }

    selectAll?.addEventListener('change', function() {
        optionCheckboxes.forEach(cb => cb.checked = selectAll.checked);
        updateBulkState();
    });

    optionCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkState);
    });

    // Discount calculator logic
    function calculateDiscounts() {
        const base = parseFloat(document.getElementById('calcBasePrice')?.value) || 0;
        const qDisc = (parseFloat(document.getElementById('calcQuarterlyDisc')?.value) || 0) / 100;
        const sDisc = (parseFloat(document.getElementById('calcSemiDisc')?.value) || 0) / 100;
        const aDisc = (parseFloat(document.getElementById('calcAnnualDisc')?.value) || 0) / 100;

        const monthly = base;
        const quarterly = (base * 3) * (1 - qDisc);
        const semiAnnually = (base * 6) * (1 - sDisc);
        const annually = (base * 12) * (1 - aDisc);

        const fmt = (n) => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' Br';

        if (document.getElementById('outMonthly')) document.getElementById('outMonthly').textContent = fmt(monthly);
        if (document.getElementById('outQuarterly')) document.getElementById('outQuarterly').textContent = fmt(quarterly);
        if (document.getElementById('outSemiAnnually')) document.getElementById('outSemiAnnually').textContent = fmt(semiAnnually);
        if (document.getElementById('outAnnually')) document.getElementById('outAnnually').textContent = fmt(annually);

        return { monthly, quarterly, semiAnnually, annually };
    }

    function applyDiscountsToNewTierModal() {
        const res = calculateDiscounts();
        const base = parseFloat(document.getElementById('calcBasePrice')?.value) || 0;

        const mPrice = document.getElementById('modalPriceAmount');
        const tMonthly = document.getElementById('modalTermMonthly');
        const tQuarterly = document.getElementById('modalTermQuarterly');
        const tSemi = document.getElementById('modalTermSemiAnnually');
        const tAnnual = document.getElementById('modalTermAnnually');

        if (mPrice) mPrice.value = base.toFixed(2);
        if (tMonthly) tMonthly.value = res.monthly.toFixed(2);
        if (tQuarterly) tQuarterly.value = res.quarterly.toFixed(2);
        if (tSemi) tSemi.value = res.semiAnnually.toFixed(2);
        if (tAnnual) tAnnual.value = res.annually.toFixed(2);

        // Hide calculator and show add tier modal
        bootstrap.Modal.getInstance(document.getElementById('discountHelperModal'))?.hide();
        new bootstrap.Modal(document.getElementById('addTierModal')).show();
    }

    function prefillFromModalHelper() {
        const modalPrice = parseFloat(document.getElementById('modalPriceAmount')?.value) || parseFloat(document.getElementById('modalTermMonthly')?.value) || 0;
        if (modalPrice > 0 && document.getElementById('calcBasePrice')) {
            document.getElementById('calcBasePrice').value = modalPrice;
            calculateDiscounts();
        }
        bootstrap.Modal.getInstance(document.getElementById('addTierModal'))?.hide();
        new bootstrap.Modal(document.getElementById('discountHelperModal')).show();
    }

    document.addEventListener('DOMContentLoaded', calculateDiscounts);
</script>
@endpush
@endsection
