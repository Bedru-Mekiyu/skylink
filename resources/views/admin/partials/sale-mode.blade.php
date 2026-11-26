{{-- "How is this sold?" Expects $type ('service'|'product'), $item. Shown to price roles only. --}}
@php
    $plans = $item->options->where('is_active', true)->sortBy('sort_order')->values();
    $single = $plans->count() <= 1;
    $first = $plans->first();
    $mode = old('mode', $item->quote_only ? 'quote' : ($plans->contains(fn ($p) => isset(\App\Models\BillingSubscription::TERM_MONTHS[$p->billing_term])) ? 'subscription' : 'fixed'));
    $payable = $plans->isNotEmpty() && $plans->every->is_payable_online;
    $modes = ['fixed' => ['Fixed price', 'One payment'], 'subscription' => ['Subscription', 'Paid per period'], 'quote' => ['Request a quote', 'No price shown']];
    $periods = ['monthly' => 'Monthly', 'quarterly' => 'Quarterly (5% off)', 'semi-annually' => '6 months (10% off)', 'annually' => 'Yearly (20% off)'];
    $categoryQuote = $type === 'product' ? $item->productCategory?->isQuoteOnly() : $item->category?->pricing_mode === 'quote_only';
@endphp
<form method="POST" action="{{ route('admin.prices.sale', [$type, $item->id]) }}" class="card shadow-sm border-0 mb-4" data-sale>
    @csrf @method('PUT')
    <div class="card-body">
        <fieldset>
            <legend class="fs-5 fw-bold">How is this sold?</legend>
            @if($categoryQuote)<div class="alert alert-warning small">Its category is set to quote only, which wins over this choice. Change the category to show prices.</div>@endif
            @if($errors->hasAny(['mode', 'price', 'monthly', 'quarterly', 'semi-annually', 'annually']))<div class="alert alert-danger small" role="alert">{{ $errors->first() }}</div>@endif
            <div class="row g-2 mb-3">
                @foreach($modes as $value => [$label, $hint])
                    <div class="col-md-4">
                        <input type="radio" class="btn-check" name="mode" id="mode-{{ $value }}" value="{{ $value }}" {{ $mode === $value ? 'checked' : '' }}>
                        <label class="btn btn-outline-primary w-100 h-100 d-flex flex-column justify-content-center py-2" for="mode-{{ $value }}"><span class="d-block fw-semibold">{{ $label }}</span><small>{{ $hint }}</small></label>
                    </div>
                @endforeach
            </div>
        </fieldset>
        @if($single)
            <div class="row g-2" data-for="fixed">
                <div class="col-md-4"><label class="form-label small" for="sale-price">Price (ETB)</label><input id="sale-price" type="number" step="0.01" min="1" name="price" class="form-control" value="{{ old('price', $first?->price_amount) }}"></div>
            </div>
            <div class="row g-2" data-for="subscription">
                @foreach($periods as $term => $label)
                    <div class="col-6 col-md-3"><label class="form-label small" for="sale-{{ $term }}">{{ $label }}</label><input id="sale-{{ $term }}" type="number" step="0.01" min="1" name="{{ $term }}" class="form-control" value="{{ old($term, $first?->term_prices[$term] ?? null) }}" placeholder="{{ $term === 'monthly' ? '' : 'automatic' }}"></div>
                @endforeach
            </div>
        @else
            <p class="small text-muted">This item has {{ $plans->count() }} plans. This choice applies to all of them; edit each plan's prices in Pricing tiers below. Check those prices after switching.</p>
        @endif
        <div class="form-check mt-3" data-for="fixed subscription">
            <input type="checkbox" class="form-check-input" id="sale-payable" name="payable" value="1" @checked($payable)>
            <label class="form-check-label" for="sale-payable">Customers can pay online</label>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Save how it is sold</button>
    </div>
</form>
<script>
(function (form) {
    function show() {
        var mode = form.querySelector('input[name="mode"]:checked').value;
        form.querySelectorAll('[data-for]').forEach(function (el) { el.hidden = el.dataset.for.indexOf(mode) === -1; });
    }
    form.addEventListener('change', show);
    show();

    // A longer period that still holds the automatic price follows a new monthly price; a hand-set one stays.
    var monthly = form.querySelector('input[name="monthly"]');
    if (!monthly) return;
    var rules = { 'quarterly': [3, 0.95], 'semi-annually': [6, 0.90], 'annually': [12, 0.80] };
    var automatic = Object.keys(rules).filter(function (term) {
        var value = form.querySelector('input[name="' + term + '"]').value;
        return value && Math.abs(value - monthly.value * rules[term][0] * rules[term][1]) < 0.01;
    });
    monthly.addEventListener('input', function () {
        automatic.forEach(function (term) { form.querySelector('input[name="' + term + '"]').value = ''; });
    });
    Object.keys(rules).forEach(function (term) {
        form.querySelector('input[name="' + term + '"]').addEventListener('input', function () {
            automatic = automatic.filter(function (t) { return t !== term; });
        });
    });
})(document.querySelector('[data-sale]'));
</script>
