<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Support\GalleryImageProcessor;
use App\Support\PublicCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/** Admin > Gallery: bulk upload, captions (English + Amharic), order, visible / hidden. Hide instead of delete. */
class GalleryController extends Controller
{
    private const PER_PAGE = 24;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $like = '%'.addcslashes($q, '%_\\').'%';
        $images = GalleryImage::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('caption_en', 'like', $like)->orWhere('caption_am', 'like', $like)))
            ->orderBy('sort_order')->orderBy('id')
            ->paginate(self::PER_PAGE)->withQueryString();

        return view('admin.gallery.index', [
            'images' => $images, 'q' => $q, 'reorderable' => $q === '',
            'limit' => HeroVideoController::serverLimitBytes(),
        ]);
    }

    /** One photo per request (the page sends a batch one after the other and shows the progress bar). */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'file', 'extensions:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.GalleryImageProcessor::MAX_KB],
            'caption_en' => ['nullable', 'string', 'max:200'],
            'caption_am' => ['nullable', 'string', 'max:200'],
        ], [
            'photo.max' => 'The photo is larger than 8 MB.',
            'photo.extensions' => 'Only JPG, PNG or WebP photos are allowed.',
            'photo.mimetypes' => 'Only JPG, PNG or WebP photos are allowed.',
            'photo.uploaded' => 'The photo could not be uploaded (the server accepts files up to '.round(HeroVideoController::serverLimitBytes() / 1048576).' MB).',
        ]);
        try {
            $files = GalleryImageProcessor::process($request->file('photo'));
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['photo' => $e->getMessage()]);
        }
        $image = GalleryImage::create($files + [
            'caption_en' => $data['caption_en'] ?? null,
            'caption_am' => $data['caption_am'] ?? null,
            'sort_order' => (int) GalleryImage::max('sort_order') + 1,
            'updated_by' => $request->user()->id,
        ]);
        Log::notice('gallery photo added', ['user_id' => $request->user()->id, 'image_id' => $image->id]);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'id' => $image->id])
            : redirect()->route('admin.gallery.index')->with('success', 'Photo added.');
    }

    public function edit(GalleryImage $image)
    {
        return view('admin.gallery.edit', ['image' => $image]);
    }

    public function update(Request $request, GalleryImage $image): RedirectResponse
    {
        $data = $request->validate([
            'caption_en' => ['nullable', 'string', 'max:200'],
            'caption_am' => ['nullable', 'string', 'max:200'],
            'taken_on' => ['nullable', 'date', 'before_or_equal:today', 'after:1990-01-01'],
            'sort_order' => ['nullable', 'integer', 'between:0,100000'],
        ]);
        $image->update([
            'caption_en' => $data['caption_en'] ?? null, 'caption_am' => $data['caption_am'] ?? null,
            'taken_on' => $data['taken_on'] ?? null, 'sort_order' => $data['sort_order'] ?? $image->sort_order,
            'visible' => $request->boolean('visible'), 'updated_by' => $request->user()->id,
        ]);
        Log::notice('gallery photo updated', ['user_id' => $request->user()->id, 'image_id' => $image->id]);

        return redirect()->route('admin.gallery.index')->with('success', 'Photo saved.');
    }

    public function toggle(Request $request, GalleryImage $image): RedirectResponse
    {
        $image->update(['visible' => ! $image->visible, 'updated_by' => $request->user()->id]);
        Log::notice('gallery photo visibility', ['user_id' => $request->user()->id, 'image_id' => $image->id, 'visible' => $image->visible]);

        return back()->with('success', $image->visible ? 'The photo is visible again.' : 'The photo is hidden from the gallery. You can show it again any time.');
    }

    /** Deletes the photo AND its files (thumbnail, display copy, archived original). */
    public function destroy(Request $request, GalleryImage $image): RedirectResponse
    {
        $label = $image->caption_en ?: 'photo';
        \App\Support\MediaCleanup::remove([$image->thumb_path, $image->display_path], [$image->original_path]);
        $image->delete();
        Log::notice('gallery photo deleted', ['user_id' => $request->user()->id, 'image_id' => $image->id]);

        return redirect()->route('admin.gallery.index')->with('success', "The {$label} was deleted.");
    }

    public function reorder(Request $request): JsonResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer', 'distinct']])['ids'];
        $slots = GalleryImage::whereIn('id', $ids)->orderBy('sort_order')->orderBy('id')->pluck('sort_order')->all();
        if (count($slots) !== count($ids)) {
            throw ValidationException::withMessages(['ids' => 'Unknown photo in the list.']);
        }
        DB::transaction(function () use ($ids, $slots) {
            foreach ($ids as $i => $id) {
                GalleryImage::whereKey($id)->update(['sort_order' => $slots[$i]]);
            }
        });
        PublicCache::bust();
        Log::notice('gallery reorder', ['user_id' => $request->user()->id, 'count' => count($ids)]);

        return response()->json(['ok' => true]);
    }
}
