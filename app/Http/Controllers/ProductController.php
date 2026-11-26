<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str;
use App\Models\Product;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function showProducts()
{
    $string = Str::of('Example')->upper();
    return view('pages.productt', compact('string'));
}
    public function index()
    {
        $products = \App\Support\PublicCache::remember('products.grouped.v6', 300, function () {
            $grouped = Product::visible()->with(['productCategory', 'options' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
                ->get()
                ->groupBy('category');

            foreach ($grouped as $cat => $items) {
                foreach ($items as $product) {
                    foreach ($product->options as $opt) {
                        $opt->setRelation('product', $product);
                    }
                }
            }

            return $grouped;
        });

        foreach ($products as $cat => $items) {
            foreach ($items as $product) {
                foreach ($product->options as $opt) {
                    if (!$opt->relationLoaded('product')) {
                        $opt->setRelation('product', $product);
                    }
                }
            }
        }

        // Category labels, order and pricing mode come from product_categories (slug-keyed).
        $categories = \App\Models\ProductCategory::where('is_published', true)
            ->orderBy('sort_order')->orderBy('id')->get()->keyBy('slug');
        $products = $products->sortBy(fn ($items, $slug) => $categories[$slug]->sort_order ?? PHP_INT_MAX);

        return view('pages.products', compact('products', 'categories'));
    }

public function show($category, $slug)
{
    $product = Product::visible()->where('slug', $slug)->with('options')->first();

    if (!$product) {
        $product = Product::visible()->where('category', $category)
                          ->where('slug', $slug)
                          ->with('options')
                          ->first();
    }

    if (!$product) {
        // Fallback: check if slug matches a Service
        $service = \App\Models\Service::visible()->where('slug', $slug)->first();
        if ($service) {
            return redirect()->route('services.detail', ['subcategory' => $service->slug], 301);
        }
        return redirect()->route('products')->with('info', __('Product coming soon. Please explore our available solutions.'));
    }

    $category = $product->category;
    $allCategories = Product::visible()->select('category')->distinct()->pluck('category')->toArray();
    $relatedProducts = Product::visible()->where('category', $category)->where('id', '!=', $product->id)->take(5)->get();

    return view('pages.product-detail', compact('product', 'category', 'allCategories', 'relatedProducts'));
}

public function showBySlug($slug)
{
    $product = Product::visible()->where('slug', $slug)->first();
    if ($product) {
        return redirect()->route('products.detail', ['category' => $product->category, 'slug' => $product->slug], 301);
    }

    $service = \App\Models\Service::visible()->where('slug', $slug)->first();
    if ($service) {
        return redirect()->route('services.detail', ['subcategory' => $service->slug], 301);
    }

    return redirect()->route('products')->with('info', __('Product coming soon. Please explore our available solutions.'));
}
}
