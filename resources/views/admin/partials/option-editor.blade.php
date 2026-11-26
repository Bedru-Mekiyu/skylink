{{-- Pricing tiers of one service or product. Expects $type ('service'|'product'), $item. Prices roles only. --}}
@php
    $termLabels = ['one-time' => 'One-time', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'semi-annually' => 'Every six months', 'annually' => 'Yearly', 'project-based' => 'Project-based'];
    $recurring = \App\Models\BillingSubscription::TERM_MONTHS;
    $tiers = $item->options->values()->push(new \App\Models\ItemOption(['currency' => 'ETB', 'billing_term' => 'one-time', 'show_price' => true, 'is_ready' => true, 'show_billing_term' => true]));
@endphp
<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white py-3">
        <h4 class="mb-0 fw-bold">Pricing tiers ({{ $item->options->count() }})</h4>
        <small class="text-muted">Leave every price empty for “Quote Request”. Term prices are the full amount for that period; only filled terms are offered.</small>
    </div>
    <div class="card-body">
        @foreach($tiers as $opt)
            @php $id = $opt->exists ? 'tier-'.$opt->id : 'tier-new'; @endphp
            <fieldset class="border rounded-3 p-3 mb-3">
                <legend class="float-none w-auto px-2 fs-6 fw-semibold mb-0">{{ $opt->exists ? $opt->name : 'Add a new tier' }}
                    @if($opt->needs_review)<span class="badge bg-warning text-dark">Needs review</span>@endif</legend>
                <form id="{{ $id }}" method="POST" action="{{ $opt->exists ? route('admin.prices.update', $opt) : route('admin.prices.store') }}">
                    @csrf
                    @if($opt->exists) @method('PUT') @else
                        <input type="hidden" name="item_type" value="{{ $type }}"><input type="hidden" name="item_id" value="{{ $item->id }}">
                    @endif
                    <div class="row g-2">
                        <div class="col-md-4"><label class="form-label small" for="{{ $id }}-name">Tier name</label><input id="{{ $id }}-name" name="name" class="form-control form-control-sm" value="{{ $opt->name }}" maxlength="100" required></div>
                        <div class="col-md-2"><label class="form-label small" for="{{ $id }}-cur">Currency</label><select id="{{ $id }}-cur" name="currency" class="form-select form-select-sm">@foreach(['ETB', 'USD'] as $c)<option @selected($opt->currency === $c)>{{ $c }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label small" for="{{ $id }}-amount">Price</label><input id="{{ $id }}-amount" type="number" step="0.01" min="0" name="price_amount" class="form-control form-control-sm" value="{{ $opt->price_amount }}"></div>
                        <div class="col-md-3"><label class="form-label small" for="{{ $id }}-term">Default term</label><select id="{{ $id }}-term" name="billing_term" class="form-select form-select-sm">@foreach($termLabels as $k => $label)<option value="{{ $k }}" @selected($opt->billing_term === $k)>{{ $label }}</option>@endforeach</select></div>
                        @foreach(array_keys($recurring) as $term)
                            <div class="col-6 col-md-3"><label class="form-label small" for="{{ $id }}-{{ $term }}">{{ $termLabels[$term] }} price</label><input id="{{ $id }}-{{ $term }}" type="number" step="0.01" min="0" name="term_prices[{{ $term }}]" class="form-control form-control-sm" value="{{ $opt->term_prices[$term] ?? '' }}"></div>
                        @endforeach
                        <div class="col-md-6"><label class="form-label small" for="{{ $id }}-tagline">Short line under the name</label><input id="{{ $id }}-tagline" name="tagline" maxlength="160" class="form-control form-control-sm" value="{{ $opt->tagline }}" placeholder="e.g. Up to 25 users"></div>
                        <div class="col-md-3"><label class="form-label small" for="{{ $id }}-order">Position (1 comes first)</label><input id="{{ $id }}-order" type="number" min="0" max="999" name="sort_order" class="form-control form-control-sm" value="{{ $opt->sort_order }}"></div>
                        <div class="col-md-3 d-flex align-items-end"><input type="hidden" name="is_recommended" value="0"><label class="form-check-label small"><input type="checkbox" class="form-check-input me-1" name="is_recommended" value="1" @checked($opt->is_recommended)>Mark as Recommended</label></div>
                        <div class="col-md-6"><label class="form-label small" for="{{ $id }}-text">Price text (optional)</label><input id="{{ $id }}-text" name="price_display_text" class="form-control form-control-sm" value="{{ $opt->price_display_text }}" maxlength="190"></div>
                        <div class="col-md-6"><label class="form-label small" for="{{ $id }}-feat">Highlights, one per line</label><textarea id="{{ $id }}-feat" name="short_features" rows="2" class="form-control form-control-sm">{{ implode("\n", $opt->short_features ?? []) }}</textarea></div>
                    </div>
                    <div class="d-flex flex-wrap gap-3 mt-2 small">
                        <input type="hidden" name="show_billing_term" value="0">
                        @foreach(['show_price' => 'Show price', 'is_ready' => 'Ready', 'show_billing_term' => 'Show term', 'is_payable_online' => 'Payable online'] as $field => $label)
                            <label class="form-check-label"><input type="checkbox" class="form-check-input me-1" name="{{ $field }}" value="1" @checked($opt->{$field})>{{ $label }}</label>
                        @endforeach
                    </div>
                </form>
                <div class="d-flex gap-2 mt-2">
                    <button type="submit" form="{{ $id }}" class="btn btn-sm btn-primary">{{ $opt->exists ? 'Save tier' : 'Add tier' }}</button>
                    @if($opt->exists)
                        <form method="POST" action="{{ route('admin.prices.destroy', $opt) }}" data-sky-confirm="Remove this tier from the website? It is kept in the database." data-sky-title="Remove tier?" data-sky-ok="Remove" data-sky-variant="danger">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remove</button></form>
                    @endif
                </div>
            </fieldset>
        @endforeach
    </div>
</div>
