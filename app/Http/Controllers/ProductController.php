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
    private $products = [];

    public function __construct()
{
        $this->products = Product::all()->groupBy('category')->toArray();
    }


public function index()
{
    $products = Product::all()->groupBy('category');
    return view('pages.products', compact('products'));
}

public function show($category, $slug)
{
    $product = Product::where('category', $category)
                      ->where('slug', $slug)
                      ->firstOrFail();

    return view('pages.product-detail', compact('product', 'category'));
}

    private function getProductBySlug($category, $slug)
    {
        $categoryProducts = $this->products[$category] ?? [];
        return collect($categoryProducts)->firstWhere('slug', $slug);
    }

    public function getAllProductsAsFlatArray()
    {
        return collect($this->products)->flatten(1)->toArray();
    }
}

