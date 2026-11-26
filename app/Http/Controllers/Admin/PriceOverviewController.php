<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemOption;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PriceOverviewController extends Controller
{
    /**
     * Display all service and product options in one management table with metrics and filters
     */
    public function index(Request $request): View
    {
        // Compute high-level catalog metrics for the admin header
        $metrics = [
            'total' => ItemOption::count(),
            'services' => ItemOption::where('item_type', 'service')->count(),
            'products' => ItemOption::where('item_type', 'product')->count(),
            'visible' => ItemOption::where('show_price', true)->whereNotNull('price_amount')->count(),
            'needs_review' => ItemOption::where('needs_review', true)->count(),
            'quote_only' => ItemOption::where(function ($q) {
                $q->whereNull('price_amount')->orWhere('show_price', false);
            })->count(),
        ];

        $query = ItemOption::with(['service', 'product'])
            ->orderBy('item_type')
            ->orderBy('item_id')
            ->orderBy('sort_order');

        // Filter by needs_review flag
        if ($request->filled('needs_review') && $request->needs_review == '1') {
            $query->where('needs_review', true);
        }

        // Filter by item type (service vs product)
        if ($request->filled('type') && in_array($request->type, ['service', 'product'], true)) {
            $query->where('item_type', $request->type);
        }

        // Filter by price visibility
        if ($request->filled('visibility')) {
            if ($request->visibility === 'visible') {
                $query->where('show_price', true)->whereNotNull('price_amount');
            } elseif ($request->visibility === 'quote') {
                $query->where(function ($q) {
                    $q->whereNull('price_amount')->orWhere('show_price', false);
                });
            }
        }

        // Filter by currency
        if ($request->filled('currency') && in_array($request->currency, ['ETB', 'USD'], true)) {
            $query->where('currency', $request->currency);
        }

        // Search by option name or display text
        if ($request->filled('search')) {
            $search = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('price_display_text', 'like', $search);
            });
        }

        $options = $query->paginate(25)->withQueryString();

        // Load active services and products for tier creation modal
        $services = Service::select('id', 'title')->orderBy('title')->get();
        $products = Product::select('id', 'name')->orderBy('name')->get();

        return view('admin.prices.index', compact('options', 'metrics', 'services', 'products'));
    }

    /** All tiers of one service or product, for people who manage prices but not content. */
    public function item(string $type, int $id): View
    {
        $item = ($type === 'service' ? Service::query() : Product::query())->with('options')->findOrFail($id);

        return view('admin.prices.item', compact('type', 'item'));
    }

    /**
     * Create a new pricing tier / option for a Service or Product
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_type' => 'required|string|in:service,product',
            'item_id' => 'required|integer',
            'name' => 'required|string|max:100',
            'price_amount' => 'nullable|numeric|min:0',
            'price_display_text' => 'nullable|string|max:190',
            'currency' => 'required|string|in:ETB,USD',
            'billing_term' => 'nullable|string|in:one-time,monthly,quarterly,semi-annually,annually,project-based',
            'term_prices' => 'nullable|array',
            'term_prices.*' => 'nullable|numeric|min:0',
            'short_features' => 'nullable|string',
            'is_payable_online' => 'nullable|boolean',
            'show_price' => 'nullable|boolean',
            'show_billing_term' => 'nullable|boolean',
            'is_ready' => 'nullable|boolean',
            'tagline' => 'nullable|string|max:160',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_recommended' => 'nullable|boolean',
        ]);

        // Process multi-term prices if provided
        $termPrices = null;
        if (!empty($request->term_prices) && is_array($request->term_prices)) {
            $filtered = [];
            foreach ($request->term_prices as $tKey => $tVal) {
                if ($tVal !== null && $tVal !== '') {
                    $filtered[$tKey] = (float) $tVal;
                }
            }
            if (!empty($filtered)) {
                $termPrices = $filtered;
            }
        }

        // Verify parent model exists
        if ($validated['item_type'] === 'service') {
            $parent = Service::findOrFail($validated['item_id']);
        } else {
            $parent = Product::findOrFail($validated['item_id']);
        }

        // Parse short features (one per line, up to 5)
        $features = [];
        if (!empty($validated['short_features'])) {
            $lines = explode("\n", str_replace("\r", "", $validated['short_features']));
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (!empty($trimmed)) {
                    $features[] = $trimmed;
                }
            }
        } elseif (!empty($parent->features) && is_array($parent->features)) {
            $features = array_slice($parent->features, 0, 3);
        }

        $maxSort = ItemOption::where('item_type', $validated['item_type'])
            ->where('item_id', $validated['item_id'])
            ->max('sort_order') ?? 0;

        $option = ItemOption::create([
            'item_type' => $validated['item_type'],
            'item_id' => $validated['item_id'],
            'name' => trim($validated['name']),
            'short_features' => $features,
            'price_amount' => $validated['price_amount'] !== null && $validated['price_amount'] !== '' ? (float) $validated['price_amount'] : null,
            'price_display_text' => !empty($validated['price_display_text']) ? trim($validated['price_display_text']) : null,
            'currency' => $validated['currency'],
            'billing_term' => $validated['billing_term'] ?? ($validated['item_type'] === 'service' ? 'monthly' : 'one-time'),
            'term_prices' => $termPrices,
            'show_billing_term' => $request->boolean('show_billing_term', true),
            'show_price' => $request->boolean('show_price'),
            'is_active' => true,
            'is_ready' => $request->boolean('is_ready'),
            'is_payable_online' => $request->boolean('is_payable_online'),
            'sort_order' => $request->filled('sort_order') ? (int) $request->input('sort_order') : $maxSort + 1,
            'tagline' => trim((string) $request->input('tagline')) ?: null,
            'is_recommended' => $request->boolean('is_recommended'),
        ]);

        $this->keepOneRecommended($option);

        \App\Models\AdminAuditLog::record('pricing.create', 'ItemOption', $option->id, $option->only(['item_type', 'item_id', 'name', 'price_amount', 'term_prices', 'currency']));

        // Audit log
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/price-audit.log'),
        ])->info('Pricing tier created', [
            'user_id' => auth()->id() ?? 'system',
            'option_id' => $option->id,
            'item_type' => $option->item_type,
            'item_id' => $option->item_id,
            'name' => $option->name,
            'price_amount' => $option->price_amount,
            'currency' => $option->currency,
        ]);

        $parentTitle = $validated['item_type'] === 'service' ? ($parent->title ?? 'Service') : ($parent->name ?? 'Product');
        return back()->with('success', "Pricing tier '{$option->name}' added to '{$parentTitle}' successfully.");
    }

    /**
     * Inline / Quick update of an option
     */
    public function update(Request $request, ItemOption $option): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'price_amount' => 'nullable|numeric|min:0',
            'price_display_text' => 'nullable|string|max:190',
            'currency' => 'required|string|in:ETB,USD',
            'billing_term' => 'nullable|string|in:monthly,quarterly,semi-annually,annually,one-time,project-based',
            'term_prices' => 'nullable|array',
            'term_prices.*' => 'nullable|numeric|min:0',
            'show_price' => 'nullable|boolean',
            'is_ready' => 'nullable|boolean',
            'tagline' => 'nullable|string|max:160',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_recommended' => 'nullable|boolean',
            'show_billing_term' => 'nullable|boolean',
            'needs_review' => 'nullable|boolean',
            'is_payable_online' => 'nullable|boolean',
        ]);

        $before = $option->only([
            'name', 'price_amount', 'price_display_text', 'currency',
            'billing_term', 'show_price', 'is_ready', 'show_billing_term',
            'needs_review', 'is_payable_online', 'term_prices', 'tagline', 'sort_order', 'is_recommended'
        ]);

        $option->name = $validated['name'];
        $option->price_amount = $validated['price_amount'] !== null && $validated['price_amount'] !== '' ? (float) $validated['price_amount'] : null;
        $option->price_display_text = !empty($validated['price_display_text']) ? trim($validated['price_display_text']) : null;
        $option->currency = $validated['currency'];
        $option->billing_term = $validated['billing_term'] ?? null;

        if ($request->has('term_prices')) {
            $filtered = [];
            if (is_array($request->term_prices)) {
                foreach ($request->term_prices as $tKey => $tVal) {
                    if ($tVal !== null && $tVal !== '') {
                        $filtered[$tKey] = (float) $tVal;
                    }
                }
            }
            $option->term_prices = !empty($filtered) ? $filtered : null;
        }

        $option->show_price = $request->boolean('show_price');
        $option->is_ready = $request->boolean('is_ready');
        $option->is_payable_online = $request->boolean('is_payable_online');
        // Not every price form has these, so they change only when sent.
        if ($request->has('show_billing_term')) {
            $option->show_billing_term = $request->boolean('show_billing_term');
        }
        if ($request->has('needs_review')) {
            $option->needs_review = $request->boolean('needs_review');
        }
        if ($request->has('short_features')) {
            $option->short_features = array_values(array_filter(array_map('trim', explode("\n", (string) $request->input('short_features')))));
        }
        if ($request->has('tagline')) {
            $option->tagline = trim((string) $request->input('tagline')) ?: null;
        }
        if ($request->filled('sort_order')) {
            $option->sort_order = (int) $request->input('sort_order');
        }
        if ($request->has('is_recommended')) {
            $option->is_recommended = $request->boolean('is_recommended');
        }
        $option->save();
        $this->keepOneRecommended($option);

        // Audit log changes
        $after = $option->only(array_keys($before));
        $diff = [];
        foreach ($before as $key => $oldVal) {
            $newVal = $after[$key];
            if ($oldVal != $newVal) {
                $diff[$key] = ['old' => $oldVal, 'new' => $newVal];
            }
        }

        if (!empty($diff)) {
            \App\Models\AdminAuditLog::record('pricing.update', 'ItemOption', $option->id, $diff);

            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/price-audit.log'),
            ])->info('Price option updated', [
                'user_id' => auth()->id() ?? 'system',
                'option_id' => $option->id,
                'item_type' => $option->item_type,
                'item_id' => $option->item_id,
                'changes' => $diff,
            ]);
        }

        return back()->with('success', "Price option '{$option->name}' updated successfully.");
    }

    /**
     * Toggle the needs_review status of an option
     */
    public function toggleReview(ItemOption $option): RedirectResponse
    {
        $option->needs_review = !$option->needs_review;
        $option->save();

        \App\Models\AdminAuditLog::record('pricing.toggle_review', 'ItemOption', $option->id, [
            'needs_review' => $option->needs_review,
        ]);

        $statusText = $option->needs_review ? 'flagged for review' : 'marked as reviewed';
        return back()->with('success', "Option '{$option->name}' {$statusText}.");
    }

    /**
     * Bulk mark options as reviewed
     */
    public function bulkReviewed(Request $request): RedirectResponse
    {
        $ids = $request->input('option_ids', []);
        if (empty($ids) || !is_array($ids)) {
            return back()->with('warning', 'No options selected to mark as reviewed.');
        }

        $count = ItemOption::whereIn('id', $ids)->where('needs_review', true)->update(['needs_review' => false]);

        \App\Models\AdminAuditLog::record('pricing.bulk_reviewed', 'ItemOption', null, [
            'count' => $count,
            'option_ids' => $ids,
        ]);

        return back()->with('success', "{$count} pricing options marked as reviewed.");
    }

    /**
     * Delete an option tier
     */
    public function destroy(ItemOption $option): RedirectResponse
    {
        $name = $option->name;
        $itemType = $option->item_type;
        $itemId = $option->item_id;

        $option->delete(); // soft delete: kept for orders and history
        \App\Models\AdminAuditLog::record('pricing.delete', 'ItemOption', $option->id, ['name' => $name]);

        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/price-audit.log'),
        ])->info('Price tier deleted', [
            'user_id' => auth()->id() ?? 'system',
            'option_name' => $name,
            'item_type' => $itemType,
            'item_id' => $itemId,
        ]);

        return back()->with('success', "Pricing tier '{$name}' was removed successfully.");
    }

    /**
     * CSV Export of catalog prices
     */
    public function export(): StreamedResponse
    {
        $options = ItemOption::with(['service', 'product'])->orderBy('item_type')->orderBy('item_id')->orderBy('sort_order')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="skylink-catalog-prices-' . now()->format('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($options) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Item Type', 'Item Name', 'Option Name', 'Currency', 'Price Amount', 'Display Text', 'Billing Term', 'Show Term', 'Show Price', 'Ready', 'Monthly', 'Quarterly', 'Semi-annually', 'Annually', 'Payable Online', 'Needs Review']);

            foreach ($options as $opt) {
                $itemName = $opt->item_type === 'service' ? ($opt->service?->title ?? 'N/A') : ($opt->product?->name ?? 'N/A');
                fputcsv($handle, \App\Support\Csv::row([
                    $opt->id,
                    $opt->item_type,
                    $itemName,
                    $opt->name,
                    $opt->currency,
                    $opt->price_amount ?? '',
                    $opt->price_display_text ?? '',
                    $opt->billing_term ?? '',
                    $opt->show_billing_term ? '1' : '0',
                    $opt->show_price ? '1' : '0',
                    $opt->is_ready ? '1' : '0',
                    $opt->term_prices['monthly'] ?? '',
                    $opt->term_prices['quarterly'] ?? '',
                    $opt->term_prices['semi-annually'] ?? '',
                    $opt->term_prices['annually'] ?? '',
                    $opt->is_payable_online ? '1' : '0',
                    $opt->needs_review ? '1' : '0',
                ]));
            }
            fclose($handle);
        }, 200, $headers);
    }

    /** Only one plan of an item carries the "Recommended" mark. */
    private function keepOneRecommended(ItemOption $option): void
    {
        if ($option->is_recommended) {
            ItemOption::where('item_type', $option->item_type)->where('item_id', $option->item_id)
                ->whereKeyNot($option->id)->update(['is_recommended' => false]);
        }
    }
}
