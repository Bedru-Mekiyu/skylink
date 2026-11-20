<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Support\PartnerLogoProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/** Admin > Partners: the logos of the homepage "Trusted Partners" marquee. Hide instead of delete. */
class PartnerController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $partners = Partner::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', '%'.addcslashes($q, '%_\\').'%')->orWhere('alt', 'like', '%'.addcslashes($q, '%_\\').'%')))
            ->orderBy('sort_order')->orderBy('id')
            ->paginate(self::PER_PAGE)->withQueryString();

        return view('admin.partners.index', ['partners' => $partners, 'q' => $q, 'reorderable' => $q === '']);
    }

    public function create()
    {
        return view('admin.partners.form', ['partner' => new Partner(['active' => true, 'sort_order' => (int) Partner::max('sort_order') + 1])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);
        $logo = $this->logo($request);
        $partner = Partner::create($data + $logo + ['updated_by' => $request->user()->id]);
        Log::notice('partner created', ['user_id' => $request->user()->id, 'partner_id' => $partner->id]);

        return redirect()->route('admin.partners.index')->with('success', "Partner \"{$partner->name}\" was added.");
    }

    public function edit(Partner $partner)
    {
        return view('admin.partners.form', ['partner' => $partner]);
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $data = $this->validated($request, false);
        $logo = $request->hasFile('logo') ? $this->logo($request) : [];
        $old = $logo ? [$partner->logo_path, $partner->logo_fallback] : [];
        $partner->update($data + $logo + ['updated_by' => $request->user()->id]);
        // Old web copies go; the private originals stay archived.
        foreach (array_filter($old) as $path) {
            Storage::disk('public')->delete($path);
        }
        Log::notice('partner updated', ['user_id' => $request->user()->id, 'partner_id' => $partner->id, 'logo_changed' => (bool) $logo]);

        return redirect()->route('admin.partners.index')->with('success', "Partner \"{$partner->name}\" was saved.");
    }

    /** Hide or show (never deletes). */
    public function toggle(Request $request, Partner $partner): RedirectResponse
    {
        $partner->update(['active' => ! $partner->active, 'updated_by' => $request->user()->id]);
        Log::notice('partner visibility', ['user_id' => $request->user()->id, 'partner_id' => $partner->id, 'active' => $partner->active]);

        return back()->with('success', $partner->active ? "\"{$partner->name}\" is visible on the homepage again." : "\"{$partner->name}\" is hidden from the homepage. You can show it again any time.");
    }

    /** Drag-and-drop / arrow buttons send the ids of the page in their new order. */
    public function reorder(Request $request)
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer', 'distinct']])['ids'];
        $slots = Partner::whereIn('id', $ids)->orderBy('sort_order')->orderBy('id')->pluck('sort_order')->all();
        if (count($slots) !== count($ids)) {
            throw ValidationException::withMessages(['ids' => 'Unknown partner in the list.']);
        }
        // The same set of order numbers is handed out again in the new sequence, so other pages keep their place.
        DB::transaction(function () use ($ids, $slots) {
            foreach ($ids as $i => $id) {
                Partner::whereKey($id)->update(['sort_order' => $slots[$i]]);
            }
        });
        \App\Support\PublicCache::bust();
        Log::notice('partner reorder', ['user_id' => $request->user()->id, 'count' => count($ids)]);

        return response()->json(['ok' => true]);
    }

    private function validated(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'alt' => ['nullable', 'string', 'max:160'],
            'website_url' => ['nullable', 'url:https', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'since_year' => ['nullable', 'integer', 'between:1980,'.date('Y')],
            'sort_order' => ['nullable', 'integer', 'between:0,100000'],
            'logo' => [$creating ? 'required' : 'nullable', 'file', 'extensions:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.PartnerLogoProcessor::MAX_KB],
        ], [
            'logo.max' => 'The logo is larger than 2 MB.',
            'logo.extensions' => 'Only JPG, PNG or WebP images are allowed.',
            'logo.mimetypes' => 'Only JPG, PNG or WebP images are allowed.',
            'logo.required' => 'Please choose a logo.',
            'logo.uploaded' => 'The logo could not be uploaded (the server accepts files up to 2 MB here).',
            'website_url.url' => 'The website must be a full https:// address.',
        ]);
        unset($data['logo']);
        $data['alt'] = ($data['alt'] ?? '') !== '' ? $data['alt'] : $data['name'];
        $data['featured'] = $request->boolean('featured');
        $data['active'] = $request->boolean('active', true);
        $data['sort_order'] = $data['sort_order'] ?? ((int) Partner::max('sort_order') + 1);

        return $data;
    }

    private function logo(Request $request): array
    {
        try {
            return PartnerLogoProcessor::process($request->file('logo'));
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['logo' => $e->getMessage()]);
        }
    }
}
