{{--
    One card for every product and service list. The title link is stretched over the whole card (opens the details);
    the buttons sit above it, so they do their own job.
    Needs: $url, $image, $title, $description, $features, $option, $item, $itemType, $quoteOnly. Optional: $category.
--}}
@php
    // The card speaks for the cheapest plan with a visible price, whatever order the plans are listed in.
    $plans = $item->options->where('is_active', true)->each(fn ($o) => $o->setRelation($itemType, $item))
        ->filter(fn ($o) => $o->isPriceVisible() && ($o->price_amount !== null || $o->hasMultipleTerms()));
    $option = $plans->sortBy(fn ($o) => $o->getPriceForTerm('monthly') ?? INF)->first() ?? $option;
    $several = $plans->count() > 1;
    $priced = ! $quoteOnly && $option && $option->isPriceVisible($item)
        && ($option->price_amount !== null || $option->hasMultipleTerms());
    $monthly = $priced && $option->hasMultipleTerms() ? $option->getPriceForTerm('monthly') : null;
    $term = $priced && ! $option->hasMultipleTerms() && $option->show_billing_term ? $option->billing_term : null;
    $termText = ['monthly' => __('per month'), 'quarterly' => __('per quarter'), 'semi-annually' => __('per 6 months'),
        'annually' => __('per year'), 'one-time' => __('one-time'), 'project-based' => __('per project')][$term] ?? $term;
@endphp
<article class="sky-card">
    <div class="sky-card__media">
        <img src="{{ $image }}" alt="" loading="lazy" decoding="async"
             onerror="this.onerror=null; this.src='{{ asset('assets/images/placeholder.png') }}';">
    </div>
    <div class="sky-card__body">
        @if(!empty($category))<p class="sky-card__category">{{ $category }}</p>@endif
        <h3 class="sky-card__title"><a href="{{ $url }}" class="stretched-link">{{ $title }}</a></h3>
        <p class="sky-card__desc">{{ \Illuminate\Support\Str::limit((string) $description, 110) }}</p>
        @if(!empty($features))
            <ul class="sky-card__features">
                @foreach($features as $feature)
                    <li><i class="fas fa-check" aria-hidden="true"></i><span>{{ $feature }}</span></li>
                @endforeach
            </ul>
        @endif

        <div class="sky-card__foot">
            <p class="sky-card__price">
                @if($priced)
                    @if($several || $monthly)<span class="sky-card__note">{{ __('From') }}</span>@endif
                    @if($monthly)
                        <strong>{{ \App\Support\Money::format($monthly, $option->currency) }}</strong>
                        <span class="sky-card__note">{{ __('per month') }}</span>
                    @else
                        <strong>{{ $option->formatted_price }}</strong>
                        @if($termText)<span class="sky-card__note">{{ $termText }}</span>@endif
                    @endif
                @else
                    <span class="sky-card__note">{{ __('Priced for your project') }}</span>
                @endif
            </p>

            <div class="sky-card__actions">
                @if($priced)
                    <form method="POST" action="{{ route('cart.add') }}" class="add-to-cart-form">
                        @csrf
                        <input type="hidden" name="item_type" value="{{ $itemType }}">
                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                        <input type="hidden" name="option_id" value="{{ $option->id }}">
                        <button type="submit" name="then" value="checkout" class="btn btn-primary">{{ __('Checkout') }}</button>
                        <button type="submit" class="btn btn-outline-primary sky-card__add" data-icon-only
                                aria-label="{{ __('Add to Cart') }}: {{ $title }}" title="{{ __('Add to Cart') }}">
                            <i class="fas fa-cart-plus" aria-hidden="true"></i>
                        </button>
                    </form>
                @else
                    <a href="{{ $item->supportUrl() }}" class="btn btn-outline-primary w-100">{{ __('Request a Quote') }}</a>
                @endif
            </div>
        </div>
    </div>
</article>
