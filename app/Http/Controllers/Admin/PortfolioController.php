<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Support\ImageUploadRules;
use App\Support\ShowcaseVideos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // We need this to create slugs
use Illuminate\Validation\Rule;

class PortfolioController extends Controller
{
    public function adminIndex()
    {
        $projects = Portfolio::latest()->paginate(10);
        return view('admin.portfolio.index', compact('projects'));
    }

    public function create()
    {
        return view('admin.portfolio.create', ['clips' => ShowcaseVideos::library()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'project_url' => 'nullable|url',
            'image' => ImageUploadRules::rules(true),
        ] + $this->videoRules());

        $imagePath = $request->file('image')->store('portfolio_images', 'public');

        $portfolio = new Portfolio([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'image' => $imagePath,
            'project_url' => $validated['project_url'] ?? null,
            'slug' => Str::slug($validated['title']) . '-' . time(),
        ]);

        DB::transaction(function () use ($request, $portfolio) {
            $this->applyVideo($request, $portfolio);
            $portfolio->save();
            $this->keepSingleFeatured($portfolio);
        });

        return redirect()->route('admin.portfolio.index')->with('success', 'Project added!');
    }

    public function edit(Portfolio $portfolio) // Using Route Model Binding
    {
        return view('admin.portfolio.edit', ['project' => $portfolio, 'clips' => ShowcaseVideos::library()]);
    }

    public function update(Request $request, Portfolio $portfolio) // Using Route Model Binding
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'project_url' => 'nullable|url',
            'image' => ImageUploadRules::rules(),
        ] + $this->videoRules());

        $updateData = collect($validated)->only(['title', 'description', 'project_url'])->all();

        if ($request->hasFile('image')) {
            $updateData['image'] = $request->file('image')->store('portfolio_images', 'public');
        }

        DB::transaction(function () use ($request, $portfolio, $updateData) {
            $portfolio->fill($updateData);
            $this->applyVideo($request, $portfolio);
            $portfolio->save();
            $this->keepSingleFeatured($portfolio);
        });

        return redirect()->route('admin.portfolio.index')->with('success', 'Project updated!');
    }

    public function destroy(Portfolio $portfolio) // Using Route Model Binding
    {
        $portfolio->delete();
        return back()->with('success', 'Project deleted!');
    }

    /** Optional showcase video: a clip from the site's library, or an upload. */
    private function videoRules(): array
    {
        return [
            'video_library' => ['nullable', 'string', Rule::in(array_keys(ShowcaseVideos::library()))],
            'video_file' => ShowcaseVideos::uploadRules(),
            'video_poster_file' => ImageUploadRules::rules(),
            'remove_video' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Order: "remove" wins; then an uploaded file; then a library clip. Leaving
     * everything empty keeps the current video.
     */
    private function applyVideo(Request $request, Portfolio $portfolio): void
    {
        $portfolio->is_featured = $request->boolean('is_featured');

        if ($request->boolean('remove_video')) {
            $portfolio->forceFill(['video_mp4' => null, 'video_webm' => null, 'video_poster' => null]);

            return;
        }

        if ($request->hasFile('video_file')) {
            $file = $request->file('video_file');
            $kind = ShowcaseVideos::signatureOf($file); // mp4 | webm (validated)
            $path = $file->storeAs('portfolio_videos', Str::random(40).'.'.$kind, 'public');
            // One uploaded file replaces both formats.
            $portfolio->forceFill([
                'video_mp4' => $kind === 'mp4' ? $path : null,
                'video_webm' => $kind === 'webm' ? $path : null,
                'video_poster' => null,
            ]);
        } elseif ($request->filled('video_library')) {
            $clip = ShowcaseVideos::library()[$request->input('video_library')];
            $portfolio->forceFill([
                'video_mp4' => $clip['mp4'],
                'video_webm' => $clip['webm'],
                'video_poster' => $clip['poster'],
            ]);
        }

        if ($request->hasFile('video_poster_file')) {
            $portfolio->video_poster = $request->file('video_poster_file')->store('portfolio_images', 'public');
        }
    }

    /** "Feature on top" is one slot: ticking it clears it on the other projects. */
    private function keepSingleFeatured(Portfolio $portfolio): void
    {
        if ($portfolio->is_featured) {
            Portfolio::whereKeyNot($portfolio->id)->where('is_featured', true)->update(['is_featured' => false]);
        }
    }
}
