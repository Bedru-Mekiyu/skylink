<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\ItemOption;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** "How is this sold?": fixed price, subscription or quote, for one product or service. Prices roles only. */
class SaleModeController extends Controller
{
    /** Discount on a longer period when its price is left empty. */
    private const DISCOUNTS = ['quarterly' => [3, 0.95], 'semi-annually' => [6, 0.90], 'annually' => [12, 0.80]];

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $item = ($type === 'product' ? Product::class : Service::class)::findOrFail($id);
        $plans = ItemOption::where('item_type', $type)->where('item_id', $id)->where('is_active', true)->orderBy('sort_order')->get();
        $single = $plans->count() <= 1; // with several plans, each keeps its own prices

        $data = $request->validate([
            'mode' => 'required|in:fixed,subscription,quote',
            'price' => [$single && $request->input('mode') === 'fixed' ? 'required' : 'nullable', 'numeric', 'min:1'],
            'monthly' => [$single && $request->input('mode') === 'subscription' ? 'required' : 'nullable', 'numeric', 'min:1'],
            'quarterly' => 'nullable|numeric|min:1',
            'semi-annually' => 'nullable|numeric|min:1',
            'annually' => 'nullable|numeric|min:1',
            'payable' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($data, $item, $type, $plans, $single, $request) {
            $before = ['quote_only' => (bool) $item->quote_only, 'plans' => $plans->map->only(['id', 'billing_term', 'price_amount', 'term_prices', 'is_payable_online'])->all()];
            $item->forceFill(['quote_only' => $data['mode'] === 'quote'])->save();

            if ($data['mode'] !== 'quote') {
                if ($plans->isEmpty()) {
                    $plans->push(new ItemOption(['item_type' => $type, 'item_id' => $item->id, 'name' => 'Standard', 'currency' => 'ETB', 'is_active' => true, 'sort_order' => 1]));
                }
                foreach ($plans as $plan) {
                    $single ? $this->price($plan, $data) : $this->convert($plan, $data['mode']);
                    $plan->is_payable_online = $request->boolean('payable');
                    $plan->save();
                }
            }

            AdminAuditLog::record('pricing.sale_mode', class_basename($item), $item->id, ['mode' => $data['mode'], 'before' => $before]);
        });

        return back()->with('success', 'Saved how this item is sold.');
    }

    private function price(ItemOption $plan, array $data): void
    {
        $plan->fill(['show_price' => true, 'is_ready' => true, 'show_billing_term' => true, 'price_display_text' => null]);

        if ($data['mode'] === 'fixed') {
            // Period prices stay stored (and unused), so switching back to subscription restores them.
            $plan->fill(['billing_term' => 'one-time', 'price_amount' => $data['price']]);

            return;
        }

        $plan->fill(['billing_term' => 'monthly', 'price_amount' => $data['monthly'], 'term_prices' => $this->terms((float) $data['monthly'], $data)]);
    }

    private function convert(ItemOption $plan, string $mode): void
    {
        if ($mode === 'fixed') {
            $plan->billing_term = 'one-time';

            return;
        }

        $monthly = (float) ($plan->term_prices['monthly'] ?? $plan->price_amount);
        $plan->billing_term = 'monthly';
        if ($monthly > 0) {
            $plan->price_amount = $monthly;
            $plan->term_prices = $this->terms($monthly, (array) $plan->term_prices);
        }
    }

    private function terms(float $monthly, array $given): array
    {
        $terms = ['monthly' => $monthly];
        foreach (self::DISCOUNTS as $term => [$months, $factor]) {
            $terms[$term] = (float) ($given[$term] ?? round($monthly * $months * $factor, 2));
        }

        return $terms;
    }
}
