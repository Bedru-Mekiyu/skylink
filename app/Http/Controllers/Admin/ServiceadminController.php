<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemOption;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\ImageUploadRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class ServiceadminController extends Controller
{
    public function index()
    {
        $services = Service::with('category')->latest()->get();
        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        $categories = ServiceCategory::all();
        return view('admin.services.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|unique:services',
            'title' => 'required',
            'desc' => 'required',
            'image' => ImageUploadRules::rules(true),
            'price' => 'nullable',
            'features' => 'required|array',
            'category_id' => 'required|exists:service_categories,id',
            'show_price' => 'nullable|boolean',
        ]);

        $imagePath = $request->file('image')->store('services', 'public');

        $validated['image'] = 'storage/' . $imagePath;
        $validated['show_price'] = $request->boolean('show_price', true);
        if (! $request->user()->hasRole('finance')) {
            unset($validated['price']);
            $validated['show_price'] = false;
        }

        $service = Service::create($validated);

        $numericPrice = null;
        if (!empty($validated['price']) && is_numeric($validated['price'])) {
            $numericPrice = (float) $validated['price'];
        }
        $currency = 'ETB';
        if (!empty($validated['price']) && str_contains($validated['price'], '$')) {
            $currency = 'USD';
        }
        $shortFeatures = is_array($service->features) ? array_slice($service->features, 0, 3) : [];

        ItemOption::create([
            'item_type' => 'service',
            'item_id' => $service->id,
            'name' => 'Standard',
            'short_features' => $shortFeatures,
            'price_amount' => $numericPrice,
            'price_display_text' => !empty($validated['price']) ? trim($validated['price']) : null,
            'currency' => $currency,
            'billing_term' => 'monthly',
            'show_billing_term' => true,
            'show_price' => $service->show_price ?? true,
            'is_active' => true,
            'is_ready' => true,
            'sort_order' => 1,
        ]);

        return redirect()->route('admin.services.index')->with('success', 'Service created successfully.');
    }

    public function show(Service $service)
    {
        return view('admin.services.show', compact('service'));
    }

    public function edit(Service $service)
    {
        $categories = ServiceCategory::all();
        $service->load('options');
        return view('admin.services.edit', compact('service', 'categories'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'slug' => 'required|unique:services,slug,' . $service->id,
            'title' => 'required',
            'desc' => 'required',
            'image' => ImageUploadRules::rules(),
            'price' => 'nullable',
            'features' => 'required|array',
            'category_id' => 'required|exists:service_categories,id',
            'show_price' => 'nullable|boolean',
            'title_am' => 'nullable|string',
            'desc_am' => 'nullable|string',
            'plans_heading' => 'nullable|string|max:150',
            'header_title' => 'nullable|string|max:255',
            'header_subtitle' => 'nullable|string|max:255',
            'header_image' => 'nullable|string|max:255',
            'header_cta_text' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('services', 'public');
            $validated['image'] = 'storage/' . $imagePath;
        }

        if ($request->user()->hasRole('finance')) {
            $validated['show_price'] = $request->boolean('show_price', true);
            if ($request->has('quote_only')) {
                $validated['quote_only'] = $request->boolean('quote_only');
            }
        } else {
            unset($validated['price'], $validated['show_price']);
        }
        $validated['is_published'] = $request->boolean('is_published', true);
        $validated['is_archived'] = $request->boolean('is_archived', false);

        if ($request->has('features_am')) {
            $fAm = $request->input('features_am');
            if (is_string($fAm)) {
                $lines = preg_split('/[\r\n,]+/', $fAm);
                $validated['features_am'] = array_values(array_filter(array_map('trim', $lines)));
            } elseif (is_array($fAm)) {
                $validated['features_am'] = array_values(array_filter(array_map('trim', $fAm)));
            }
        }

        $service->update($validated);

        \App\Models\AdminAuditLog::record('catalog.service.update', 'Service', $service->id, [
            'title' => $service->title,
        ]);

        return redirect()->route('admin.services.index')->with('success', 'Service updated successfully.');
    }

    public function duplicate(Service $service)
    {
        $service->load('options');

        $timestamp = time();
        $newService = $service->replicate();
        $newService->title = $service->title . ' (Copy)';
        $newService->slug = $service->slug . '-copy-' . substr((string) $timestamp, -4);
        $newService->is_published = false;
        $newService->is_archived = false;
        $newService->save();

        foreach ($service->options as $opt) {
            $newOpt = $opt->replicate();
            $newOpt->item_id = $newService->id;
            $newOpt->save();
        }

        \App\Models\AdminAuditLog::record('catalog.service.duplicate', 'Service', $newService->id, [
            'original_id' => $service->id,
            'new_slug' => $newService->slug,
        ]);

        return redirect()->route('admin.services.edit', $newService)->with('success', "Service duplicated as draft '{$newService->title}'.");
    }

    public function togglePublish(Service $service)
    {
        $service->is_published = !$service->is_published;
        $service->save();

        \App\Models\AdminAuditLog::record('catalog.service.toggle_publish', 'Service', $service->id, [
            'is_published' => $service->is_published,
        ]);

        $status = $service->is_published ? 'published' : 'draft';
        return back()->with('success', "Service '{$service->title}' marked as {$status}.");
    }

    public function toggleArchive(Service $service)
    {
        $service->is_archived = !$service->is_archived;
        $service->save();

        \App\Models\AdminAuditLog::record('catalog.service.toggle_archive', 'Service', $service->id, [
            'is_archived' => $service->is_archived,
        ]);

        $status = $service->is_archived ? 'archived' : 'active';
        return back()->with('success', "Service '{$service->title}' marked as {$status}.");
    }

    public function destroy(Service $service)
    {
        $id = $service->id;
        $title = $service->title;
        $service->delete(); // Soft delete

        \App\Models\AdminAuditLog::record('catalog.service.delete', 'Service', $id, [
            'title' => $title,
        ]);

        \Illuminate\Support\Facades\Log::notice('service deleted', ['user_id' => auth()->id(), 'service_id' => $id]);
        return redirect()->route('admin.services.index')->with('success', 'Service deleted successfully.');
    }
}
