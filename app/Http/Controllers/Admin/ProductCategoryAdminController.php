<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Product categories are created automatically when a product is saved; here admins tune them. */
class ProductCategoryAdminController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::withCount('products')->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.product-categories.index', compact('categories'));
    }

    public function update(Request $request, ProductCategory $productCategory): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'pricing_mode' => ['required', Rule::in([
                ProductCategory::MODE_SUBSCRIPTION, ProductCategory::MODE_ONE_TIME, ProductCategory::MODE_QUOTE_ONLY,
            ])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);
        $data['is_published'] = $request->boolean('is_published');
        // Pricing mode decides whether prices show at all, so only price roles may change it.
        if (! $request->user()->hasRole('finance')) {
            unset($data['pricing_mode']);
        }

        $productCategory->update($data);
        \App\Support\PublicCache::bust();

        return back()->with('success', "Category '{$productCategory->name}' saved.");
    }
}
