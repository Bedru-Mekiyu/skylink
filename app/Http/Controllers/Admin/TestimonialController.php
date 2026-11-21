<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Support\SmallImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/** Admin > Testimonials: the client quotes of the homepage and Services strip "What Our Clients Say". */
class TestimonialController extends Controller
{
    private const PER_PAGE = 20;
    private const DIR = 'testimonials';

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $like = '%'.addcslashes($q, '%_\\').'%';
        $items = Testimonial::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('role', 'like', $like)->orWhere('quote_en', 'like', $like)->orWhere('quote_am', 'like', $like)))
            ->orderBy('sort_order')->orderBy('id')
            ->paginate(self::PER_PAGE)->withQueryString();

        return view('admin.testimonials.index', ['items' => $items, 'q' => $q, 'reorderable' => $q === '']);
    }

    public function create()
    {
        return view('admin.testimonials.form', ['item' => new Testimonial(['visible' => true, 'sort_order' => (int) Testimonial::max('sort_order') + 1])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $photo = $this->photo($request);
        $item = Testimonial::create($data + $photo + ['updated_by' => $request->user()->id]);
        Log::notice('testimonial created', ['user_id' => $request->user()->id, 'testimonial_id' => $item->id]);

        return redirect()->route('admin.testimonials.index')->with('success', "Testimonial from \"{$item->name}\" was added.");
    }

    public function edit(Testimonial $testimonial)
    {
        return view('admin.testimonials.form', ['item' => $testimonial]);
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $data = $this->validated($request);
        $photo = $request->hasFile('photo') ? $this->photo($request) : [];
        $old = $photo ? [$testimonial->photo_path] : [];
        if ($request->boolean('remove_photo') && ! $photo) {
            $old = [$testimonial->photo_path];
            $photo = ['photo_path' => null, 'original_path' => null];
        }
        $testimonial->update($data + $photo + ['updated_by' => $request->user()->id]);
        foreach (array_filter($old) as $path) {
            Storage::disk('public')->delete($path); // the archived original stays until the item is deleted
        }
        Log::notice('testimonial updated', ['user_id' => $request->user()->id, 'testimonial_id' => $testimonial->id]);

        return redirect()->route('admin.testimonials.index')->with('success', "Testimonial from \"{$testimonial->name}\" was saved.");
    }

    public function toggle(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update(['visible' => ! $testimonial->visible, 'updated_by' => $request->user()->id]);
        Log::notice('testimonial visibility', ['user_id' => $request->user()->id, 'testimonial_id' => $testimonial->id, 'visible' => $testimonial->visible]);

        return back()->with('success', $testimonial->visible ? "\"{$testimonial->name}\" is visible again." : "\"{$testimonial->name}\" is hidden. You can show it again any time.");
    }

    /** Move one step up or down in the order (swaps the order numbers with the neighbour). */
    public function move(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $dir = $request->validate(['dir' => ['required', 'in:up,down']])['dir'];
        $all = Testimonial::orderBy('sort_order')->orderBy('id')->get();
        $i = $all->search(fn ($t) => $t->id === $testimonial->id);
        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if ($i !== false && isset($all[$j])) {
            // re-number the whole list so equal order numbers can never block a move
            $ids = $all->pluck('id')->all();
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            DB::transaction(function () use ($ids) {
                foreach ($ids as $n => $id) {
                    Testimonial::whereKey($id)->update(['sort_order' => $n + 1]);
                }
            });
            \App\Support\PublicCache::bust();
            Log::notice('testimonial moved', ['user_id' => $request->user()->id, 'testimonial_id' => $testimonial->id, 'dir' => $dir]);
        }

        return back();
    }

    /** Deletes the row AND its photo files. */
    public function destroy(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $name = $testimonial->name;
        $testimonial->deleteFiles();
        $testimonial->delete();
        Log::notice('testimonial deleted', ['user_id' => $request->user()->id, 'testimonial_id' => $testimonial->id, 'name' => $name]);

        return redirect()->route('admin.testimonials.index')->with('success', "Testimonial from \"{$name}\" was deleted.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:160'],
            'quote_en' => ['nullable', 'string', 'max:1200', 'required_without:quote_am'],
            'quote_am' => ['nullable', 'string', 'max:1200', 'required_without:quote_en'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'sort_order' => ['nullable', 'integer', 'between:0,100000'],
            'photo' => ['nullable', 'file', 'extensions:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.SmallImageProcessor::MAX_KB],
        ], [
            'quote_en.required_without' => 'Write the quote in English or in Amharic (at least one).',
            'quote_am.required_without' => 'Write the quote in English or in Amharic (at least one).',
            'photo.max' => 'The photo is larger than 4 MB.',
            'photo.extensions' => 'Only JPG, PNG or WebP images are allowed.',
            'photo.mimetypes' => 'Only JPG, PNG or WebP images are allowed.',
            'photo.uploaded' => 'The photo could not be uploaded (the server limit is lower than 4 MB).',
        ]);
        unset($data['photo']);
        $data['visible'] = $request->boolean('visible', true);
        $data['sort_order'] = $data['sort_order'] ?? ((int) Testimonial::max('sort_order') + 1);

        return $data;
    }

    private function photo(Request $request): array
    {
        if (! $request->hasFile('photo')) {
            return [];
        }
        try {
            $r = SmallImageProcessor::process($request->file('photo'), self::DIR, 160, 160);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['photo' => $e->getMessage()]);
        }

        return ['photo_path' => $r['path'], 'original_path' => $r['original']];
    }
}
