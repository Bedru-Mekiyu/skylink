<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Support\ImageUploadRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service categories = the cards and filter buttons on /services and the
 * /services/category/{slug} pages that list the services inside each one.
 */
class ServiceCategoryAdminController extends Controller
{
    public function index()
    {
        $categories = ServiceCategory::withCount('services')
            ->orderBy('sort_order')->orderBy('id')
            ->paginate(20);

        return view('admin.service-categories.index', compact('categories'));
    }

    public function create()
    {
        $category = new ServiceCategory([
            'is_published' => true,
            'sort_order' => (int) ServiceCategory::max('sort_order') + 10,
        ]);

        return view('admin.service-categories.create', compact('category'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['image'] = 'storage/'.$request->file('image')->store('service_categories', 'public');

        ServiceCategory::create($data);

        return redirect()->route('admin.service-categories.index')->with('success', 'Category created.');
    }

    public function edit(ServiceCategory $serviceCategory)
    {
        return view('admin.service-categories.edit', ['category' => $serviceCategory]);
    }

    public function update(Request $request, ServiceCategory $serviceCategory): RedirectResponse
    {
        $data = $this->validated($request, $serviceCategory);

        if ($request->hasFile('image')) {
            $old = $serviceCategory->image;
            $data['image'] = 'storage/'.$request->file('image')->store('service_categories', 'public');
            $this->deleteUpload($old);
        }

        // The slug is the category's web address (/services/category/{slug}); it never changes.
        $serviceCategory->update($data);

        return redirect()->route('admin.service-categories.index')->with('success', 'Category updated.');
    }

    public function destroy(ServiceCategory $serviceCategory): RedirectResponse
    {
        // Services belong to a category: refuse rather than leave them without one.
        if ($serviceCategory->services()->exists()) {
            return redirect()->route('admin.service-categories.index')->with('error',
                'This category still has services. Move or delete its services first, or untick "Show on the website" to hide it.');
        }

        $this->deleteUpload($serviceCategory->image);
        $serviceCategory->delete();

        return redirect()->route('admin.service-categories.index')->with('success', 'Category deleted.');
    }

    private function validated(Request $request, ?ServiceCategory $category): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'card_title' => 'nullable|string|max:255',
            'filter_label' => 'nullable|string|max:60',
            'description' => 'required|string|max:300',
            'image' => ImageUploadRules::rules($category === null),
            'sort_order' => 'required|integer|min:0|max:65535',
            'is_published' => 'nullable|boolean',
        ]);
        unset($data['image']);
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        for ($i = 2; ServiceCategory::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    /** Only files uploaded here are removed; the original card photos under assets/ stay. */
    private function deleteUpload(?string $path): void
    {
        if ($path && Str::startsWith($path, 'storage/service_categories/')) {
            Storage::disk('public')->delete(Str::after($path, 'storage/'));
        }
    }
}
