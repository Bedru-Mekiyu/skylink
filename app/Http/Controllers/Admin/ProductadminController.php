<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use App\Models\ItemOption;
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
        'price' => [$request->user()->hasRole('finance') ? 'required' : 'nullable', 'numeric', 'min:0'],
        'price_text' => 'nullable|string|max:255',
        'show_price' => 'nullable|boolean',
        'features' => 'required|string',
        'additional_info' => 'nullable|string',
    ]);

    if (! $request->user()->hasRole('finance')) {
        $validated['price'] = 0;
        $validated['price_text'] = 'Quote on Request';
        $request->merge(['show_price' => false]);
    }

    // Convert features string to array if needed
    $featuresArray = array_map('trim', explode(',', $validated['features']));

    // Save product
    $product = Product::create([
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

    $shortFeatures = is_array($product->features) ? array_slice($product->features, 0, 3) : [];

    ItemOption::create([
        'item_type' => 'product',
        'item_id' => $product->id,
        'name' => 'Standard',
        'short_features' => $shortFeatures,
        'price_amount' => $product->price !== null ? (float) $product->price : null,
        'price_display_text' => $product->price_text ?? ($product->price !== null ? ('ETB ' . number_format($product->price)) : null),
        'currency' => 'ETB',
        'billing_term' => 'one-time',
        'show_billing_term' => false,
        'show_price' => $product->show_price ?? true,
        'is_active' => true,
        'is_ready' => true,
        'sort_order' => 1,
    ]);

    return redirect()->route('admin.products.index')->with('success', 'Product created successfully!');
}
    public function show(Product $product)
    {
        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load('options');
        return view('admin.products.edit', compact('product'));
    }

public function update(Request $request, Product $product)
{
    // Convert comma-separated string to array
    if (is_string($request->input('features'))) {
        $request->merge(['features' => array_values(array_filter(array_map('trim', explode(',', $request->input('features')))))]);
    }
    if (is_string($request->input('features_am'))) {
        $request->merge(['features_am' => array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $request->input('features_am')))))]);
    }

    // Only validated fields are saved (the whole request used to be).
    $data = $request->validate([
        'category' => 'sometimes|required|string|max:100',
        'slug' => 'sometimes|required|string|max:190|unique:products,slug,' . $product->id,
        'name' => 'sometimes|required|string|max:255',
        'image' => 'sometimes|required|string|max:2000',
        'description' => 'sometimes|required|string',
        'price' => 'sometimes|required|numeric|min:0',
        'price_text' => 'nullable|string|max:255',
        'features' => 'sometimes|array',
        'features_am' => 'nullable|array',
        'additional_info' => 'nullable|string',
        'name_am' => 'nullable|string|max:255',
        'description_am' => 'nullable|string',
        'plans_heading' => 'nullable|string|max:150',
            'header_title' => 'nullable|string|max:255',
        'header_subtitle' => 'nullable|string|max:255',
        'header_image' => 'nullable|string|max:255',
        'header_cta_text' => 'nullable|string|max:100',
        'sort_order' => 'nullable|integer',
    ]);

    $data['is_published'] = $request->boolean('is_published', true);
    $data['is_archived'] = $request->boolean('is_archived', false);

    if ($request->user()->hasRole('finance')) {
        $data['show_price'] = $request->boolean('show_price', true);
        if (empty($data['price_text']) && isset($data['price'])) {
            $data['price_text'] = 'ETB ' . number_format($data['price']);
        }
    } else {
        unset($data['price'], $data['price_text']);
    }

    $product->update($data);

    \App\Models\AdminAuditLog::record('catalog.product.update', 'Product', $product->id, [
        'name' => $product->name,
    ]);

    return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
}

    public function duplicate(Product $product)
    {
        $product->load('options');

        $timestamp = time();
        $newProduct = $product->replicate();
        $newProduct->name = $product->name . ' (Copy)';
        $newProduct->slug = $product->slug . '-copy-' . substr((string) $timestamp, -4);
        $newProduct->is_published = false;
        $newProduct->is_archived = false;
        $newProduct->save();

        foreach ($product->options as $opt) {
            $newOpt = $opt->replicate();
            $newOpt->item_id = $newProduct->id;
            $newOpt->save();
        }

        \App\Models\AdminAuditLog::record('catalog.product.duplicate', 'Product', $newProduct->id, [
            'original_id' => $product->id,
            'new_slug' => $newProduct->slug,
        ]);

        return redirect()->route('admin.products.edit', $newProduct)->with('success', "Product duplicated as draft '{$newProduct->name}'.");
    }

    public function togglePublish(Product $product)
    {
        $product->is_published = !$product->is_published;
        $product->save();

        \App\Models\AdminAuditLog::record('catalog.product.toggle_publish', 'Product', $product->id, [
            'is_published' => $product->is_published,
        ]);

        $status = $product->is_published ? 'published' : 'draft';
        return back()->with('success', "Product '{$product->name}' marked as {$status}.");
    }

    public function toggleArchive(Product $product)
    {
        $product->is_archived = !$product->is_archived;
        $product->save();

        \App\Models\AdminAuditLog::record('catalog.product.toggle_archive', 'Product', $product->id, [
            'is_archived' => $product->is_archived,
        ]);

        $status = $product->is_archived ? 'archived' : 'active';
        return back()->with('success', "Product '{$product->name}' marked as {$status}.");
    }

    public function destroy(Product $product)
    {
        $id = $product->id;
        $name = $product->name;
        $product->delete(); // Soft delete

        \App\Models\AdminAuditLog::record('catalog.product.delete', 'Product', $id, [
            'name' => $name,
        ]);

        \Illuminate\Support\Facades\Log::notice('product deleted', ['user_id' => auth()->id(), 'product_id' => $id]);
        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}