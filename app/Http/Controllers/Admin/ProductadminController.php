<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use App\Models\Product;

class ProductadminController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.create');
    }

    public function store(Request $request)
{
    // Validate input
    $validated = $request->validate([
        'category' => 'required|string',
        'slug' => 'required|string|unique:products,slug',
        'name' => 'required|string',
        'image' => 'required|url',
        'description' => 'required|string',
        'price' => 'required|numeric|min:0',
        'price_text' => 'nullable|string|max:255',
        'show_price' => 'nullable|boolean',
        'features' => 'required|string',
        'additional_info' => 'nullable|string',
    ]);

    // Convert features string to array if needed
    $featuresArray = array_map('trim', explode(',', $validated['features']));

    // Save product
    Product::create([
        'category' => $validated['category'],
        'slug' => $validated['slug'],
        'name' => $validated['name'],
        'image' => $validated['image'],
        'description' => $validated['description'],
        'price' => $validated['price'],
        'price_text' => $validated['price_text'] ?? ('ETB ' . number_format($validated['price'])),
        'show_price' => $request->boolean('show_price', true),
        'features' => $featuresArray, // Make sure your model casts features as array
        'additional_info' => $validated['additional_info'] ?? null,
    ]);

    return redirect()->route('admin.products.index')->with('success', 'Product created successfully!');
}
    public function show(Product $product)
    {
        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    // public function update(Request $request, Product $product)
    // {
    //     $validated = $request->validate([
    //         'category' => 'required|string',
    //         'slug' => 'required|string|unique:products,slug,' . $product->id,
    //         'name' => 'required|string',
    //         'image' => 'required|url',
    //         'description' => 'required|string',
    //         'price' => 'required|integer',
    //         'features' => 'required|array',
    //         'additional_info' => 'nullable|string',
    //     ]);

    //     $product->update([
    //         'category' => $validated['category'],
    //         'slug' => $validated['slug'],
    //         'name' => $validated['name'],
    //         'image' => $validated['image'],
    //         'description' => $validated['description'],
    //         'price' => $validated['price'],
    //         'features' => json_encode($validated['features']),
    //         'additional_info' => $validated['additional_info'],
    //     ]);
public function update(Request $request, Product $product)
{
    $data = $request->all();

    // Convert comma-separated string to array
    if (isset($data['features']) && is_string($data['features'])) {
        $data['features'] = array_map('trim', explode(',', $data['features']));
    }

    $data['show_price'] = $request->boolean('show_price', true);
    if (empty($data['price_text']) && isset($data['price']) && is_numeric($data['price'])) {
        $data['price_text'] = 'ETB ' . number_format($data['price']);
    }

    $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        \App\Support\MediaCleanup::remove([$product->image]);
        $product->delete();
        \Illuminate\Support\Facades\Log::notice('product deleted', ['user_id' => auth()->id(), 'product_id' => $product->id]);
        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}