<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Support\PublicCache;
use App\Support\SmallImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/** Admin > Departments: the department cards of the About page (rows of two, the last odd card centred). */
class DepartmentController extends Controller
{
    private const DIR = 'departments';

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $like = '%'.addcslashes($q, '%_\\').'%';
        $items = Department::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('tagline', 'like', $like)))
            ->orderBy('sort_order')->orderBy('id')->paginate(20)->withQueryString();

        return view('admin.departments.index', ['items' => $items, 'q' => $q, 'reorderable' => $q === '']);
    }

    public function create()
    {
        return view('admin.departments.form', ['item' => new Department(['visible' => true, 'accent' => 'primary', 'sort_order' => (int) Department::max('sort_order') + 1, 'stats' => []])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $item = Department::create($data + $this->image($request) + ['updated_by' => $request->user()->id]);
        Log::notice('department created', ['user_id' => $request->user()->id, 'department_id' => $item->id]);

        return redirect()->route('admin.departments.index')->with('success', "Department \"{$item->name}\" was added.");
    }

    public function edit(Department $department)
    {
        return view('admin.departments.form', ['item' => $department]);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $data = $this->validated($request);
        $img = $request->hasFile('image') ? $this->image($request) : [];
        $oldUploaded = ($img || $request->boolean('remove_image')) && $department->image_path && ! str_starts_with($department->image_path, 'assets/') ? $department->image_path : null;
        if ($request->boolean('remove_image') && ! $img) {
            $img = ['image_path' => null, 'original_path' => null];
        }
        $department->update($data + $img + ['updated_by' => $request->user()->id]);
        if ($oldUploaded) {
            Storage::disk('public')->delete($oldUploaded);
        }
        Log::notice('department updated', ['user_id' => $request->user()->id, 'department_id' => $department->id]);

        return redirect()->route('admin.departments.index')->with('success', "Department \"{$department->name}\" was saved.");
    }

    public function toggle(Request $request, Department $department): RedirectResponse
    {
        $department->update(['visible' => ! $department->visible, 'updated_by' => $request->user()->id]);
        Log::notice('department visibility', ['user_id' => $request->user()->id, 'department_id' => $department->id, 'visible' => $department->visible]);

        return back()->with('success', $department->visible ? "\"{$department->name}\" is visible again." : "\"{$department->name}\" is hidden. You can show it again any time.");
    }

    public function move(Request $request, Department $department): RedirectResponse
    {
        $dir = $request->validate(['dir' => ['required', 'in:up,down']])['dir'];
        $all = Department::orderBy('sort_order')->orderBy('id')->get();
        $i = $all->search(fn ($d) => $d->id === $department->id);
        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if ($i !== false && isset($all[$j])) {
            $ids = $all->pluck('id')->all();
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            DB::transaction(function () use ($ids) {
                foreach ($ids as $n => $id) {
                    Department::whereKey($id)->update(['sort_order' => $n + 1]);
                }
            });
            PublicCache::bust();
            Log::notice('department moved', ['user_id' => $request->user()->id, 'department_id' => $department->id, 'dir' => $dir]);
        }

        return back();
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        $name = $department->name;
        $department->deleteFiles();
        $department->delete();
        Log::notice('department deleted', ['user_id' => $request->user()->id, 'department_id' => $department->id, 'name' => $name]);

        return redirect()->route('admin.departments.index')->with('success', "Department \"{$name}\" was deleted.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'icon' => ['nullable', 'string', 'max:60', 'regex:/^(fas|far|fab) fa-[a-z0-9-]{2,40}$/'],
            'image_alt' => ['nullable', 'string', 'max:200'],
            'image_label' => ['nullable', 'string', 'max:80'],
            'accent' => ['required', Rule::in(Department::ACCENTS)],
            'sort_order' => ['nullable', 'integer', 'between:0,100000'],
            'stat_n' => ['nullable', 'array', 'max:4'],
            'stat_n.*' => ['nullable', 'string', 'max:12'],
            'stat_l' => ['nullable', 'array', 'max:4'],
            'stat_l.*' => ['nullable', 'string', 'max:40'],
            'image' => ['nullable', 'file', 'extensions:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.SmallImageProcessor::MAX_KB],
        ], [
            'icon.regex' => 'Use a Font Awesome class like "fas fa-code".',
            'image.max' => 'The picture is larger than 4 MB.',
            'image.extensions' => 'Only JPG, PNG or WebP images are allowed.',
            'image.mimetypes' => 'Only JPG, PNG or WebP images are allowed.',
            'image.uploaded' => 'The picture could not be uploaded (the server limit is lower than 4 MB).',
        ]);
        $stats = [];
        foreach (range(0, 3) as $i) {
            $n = trim((string) ($data['stat_n'][$i] ?? ''));
            $l = trim((string) ($data['stat_l'][$i] ?? ''));
            if ($n === '' && $l === '') {
                continue;
            }
            if ($n === '' || $l === '') {
                throw ValidationException::withMessages(['stat_n' => 'Each number needs a label and each label a number (or leave both empty).']);
            }
            $stats[] = ['n' => $n, 'l' => $l];
        }
        unset($data['stat_n'], $data['stat_l'], $data['image']);
        $data['stats'] = $stats;
        $data['visible'] = $request->boolean('visible', true);
        $data['sort_order'] = $data['sort_order'] ?? ((int) Department::max('sort_order') + 1);

        return $data;
    }

    private function image(Request $request): array
    {
        if (! $request->hasFile('image')) {
            return [];
        }
        try {
            $r = SmallImageProcessor::process($request->file('image'), self::DIR, 1000, 625);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['image' => $e->getMessage()]);
        }

        return ['image_path' => $r['path'], 'original_path' => $r['original']];
    }
}
