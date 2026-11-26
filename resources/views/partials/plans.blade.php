{{--
    Plans of one product or service, side by side, with one billing switch for all of them.
    Needs: $options, $item, $itemType, $heading, $fallbackTitle, $fallbackPrice (null = quote only).
--}}
@php
    $switchLabels = ['monthly' => __('Monthly'), 'quarterly' => __('Quarterly'), 'semi-annually' => __('6 months'), 'annually' => __('Yearly')];
    $notes = ['monthly' => __('per month'), 'quarterly' => __('per quarter'), 'semi-annually' => __('per 6 months'),
        'annually' => __('per year'), 'one-time' => __('one-time'), 'project-based' => __('per project')];
    $months = \App\Models\BillingSubscription::TERM_MONTHS;
    $saving = function ($opt, $term) use ($months) {
        $monthly = $opt->term_prices['monthly'] ?? null;
        $amount = $opt->term_prices[$term] ?? null;

        return $monthly && $amount && ($months[$term] ?? 1) > 1 ? (int) round((1 - $amount / ($monthly * $months[$term])) * 100) : 0;
    };
    $offered = $options->filter->isPriceVisible()->flatMap(fn ($o) => array_keys((array) $o->term_prices))->unique();
    $switch = collect($switchLabels)->only($offered->all());
    $switchDefault = $switch->has('monthly') ? 'monthly' : $switch->keys()->first();
    $taxNote = \App\Support\PriceVisibility::taxNote();
@endphp
<section class="sky-plans mb-5" id="plans" aria-labelledby="plans-heading">
    <h2 class="h4 fw-bold mb-3 text-center" id="plans-heading">{{ $heading }}</h2>

    @if($switch->count() > 1)
        <fieldset class="sky-switch" data-term-switch>
            <legend class="visually-hidden">{{ __('Billing Cycle') }}</legend>
            @foreach($switch as $term => $label)
                @php $best = $options->max(fn ($o) => $saving($o, $term)); @endphp
                <label>
                    <input type="radio" name="sky_term" value="{{ $term }}" @checked($term === $switchDefault)>
                    <span>{{ $label }}@if($best > 0)<small>{{ __('Save :percent%', ['percent' => $best]) }}</small>@endif</span>
                </label>
            @endforeach
        </fieldset>
    @endif

    <div class="row g-3 justify-content-center">
        @forelse($options as $opt)
            @php
                $visible = $opt->isPriceVisible();
                $terms = $opt->getAvailableTerms();
                $current = $switchDefault && isset($terms[$switchDefault]) ? $switchDefault
                    : (isset($terms[$opt->billing_term]) ? $opt->billing_term : array_key_first($terms));
                $data = [];
                foreach ($terms as $key => $term) {
                    $percent = $saving($opt, $key);
                    $data[$key] = ['price' => $term['formatted'], 'note' => $opt->show_billing_term ? ($notes[$key] ?? $key) : '',
                        'save' => $percent > 0 ? __('Save :percent%', ['percent' => $percent]) : ''];
                }
                $features = is_array($opt->short_features) ? $opt->short_features : [];
            @endphp
            <div class="{{ $options->count() === 1 ? 'col-12' : ($options->count() === 2 ? 'col-md-6' : 'col-md-6 col-lg-4') }}">
                <article class="sky-plan {{ $opt->is_recommended ? 'sky-plan--best' : '' }}" data-plan data-terms="{{ json_encode($data) }}">
                    @if($opt->is_recommended)<p class="sky-plan__badge">{{ __('Recommended') }}</p>@endif
                    <h3 class="sky-plan__name">{{ $opt->name }}</h3>
                    @if($opt->tagline)<p class="sky-plan__tagline">{{ $opt->tagline }}</p>@endif

                    <p class="sky-plan__price" aria-live="polite">
                        @if($visible)
                            <strong data-price>{{ $data[$current]['price'] }}</strong>
                            <span data-note>{{ $data[$current]['note'] }}</span>
                            <em data-save>{{ $data[$current]['save'] }}</em>
                        @else
                            <span>{{ __('Priced for your project') }}</span>
                        @endif
                    </p>
                    @if($visible && $taxNote)<p class="sky-plan__tax">{{ $taxNote }}</p>@endif

                    <form method="POST" action="{{ route('cart.add') }}" class="add-to-cart-form">
                        @csrf
                        <input type="hidden" name="item_type" value="{{ $itemType }}">
                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                        <input type="hidden" name="option_id" value="{{ $opt->id }}">
                        <input type="hidden" name="billing_term" value="{{ $current }}" data-term>
                        @if($visible && $opt->price_amount !== null)
                            <div class="sky-plan__qty">
                                <label for="qty-{{ $opt->id }}">{{ __('Quantity') }}</label>
                                <span class="sky-stepper">
                                    <button type="button" data-step="-1" aria-label="{{ __('Decrease quantity') }}">&minus;</button>
                                    <input type="number" id="qty-{{ $opt->id }}" name="quantity" value="1" min="1" max="100" inputmode="numeric" class="form-control">
                                    <button type="button" data-step="1" aria-label="{{ __('Increase quantity') }}">+</button>
                                </span>
                            </div>
                            <button type="submit" name="then" value="checkout" class="btn {{ $opt->is_recommended ? 'btn-primary' : 'btn-outline-primary' }} w-100 mb-2">{{ __('Checkout') }}</button>
                            <button type="submit" class="btn btn-link w-100 sky-plan__add">{{ __('Add to Cart') }}</button>
                        @else
                            <button type="submit" class="btn btn-outline-primary w-100">{{ __('Request a Quote') }}</button>
                        @endif
                    </form>

                    @if($features)
                        <ul class="sky-plan__features">
                            @foreach($features as $feature)
                                <li><i class="fas fa-check" aria-hidden="true"></i><span>{{ $feature }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            </div>
        @empty
            <div class="col-12">
                <article class="sky-plan text-center">
                    <h3 class="sky-plan__name">{{ $fallbackTitle }}</h3>
                    <p class="sky-plan__price justify-content-center">
                        @if($fallbackPrice)<strong>{{ \App\Support\Money::display($fallbackPrice) }}</strong>@else<span>{{ __('Priced for your project') }}</span>@endif
                    </p>
                    <form method="POST" action="{{ route('cart.add') }}">
                        @csrf
                        <input type="hidden" name="item_type" value="{{ $itemType }}">
                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                        <button type="submit" class="btn btn-primary px-4">{{ __('Add to Cart') }}</button>
                    </form>
                </article>
            </div>
        @endforelse
    </div>
</section>

@push('scripts')
<script>
document.querySelectorAll('.sky-stepper').forEach(function (stepper) {
    stepper.addEventListener('click', function (event) {
        var step = event.target.dataset.step;
        if (!step) return;
        var input = stepper.querySelector('input');
        input.value = Math.max(1, Math.min(100, (parseInt(input.value, 10) || 1) + Number(step)));
    });
});
document.querySelectorAll('[data-term-switch]').forEach(function (group) {
    group.addEventListener('change', function (event) {
        var term = event.target.value;
        group.closest('.sky-plans').querySelectorAll('[data-plan]').forEach(function (plan) {
            var data = JSON.parse(plan.dataset.terms)[term];
            if (!data) return; // this plan is not sold for that period: it keeps its own
            plan.querySelector('[data-term]').value = term;
            ['price', 'note', 'save'].forEach(function (key) {
                var el = plan.querySelector('[data-' + key + ']');
                if (el) el.textContent = data[key];
            });
        });
    });
});
</script>
@endpush
