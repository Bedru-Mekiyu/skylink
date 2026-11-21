<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\NewsCategory;
use App\Rules\EmbeddableVideoUrl;
use App\Support\ImageUploadRules;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminVideoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $videos = Video::with('category')->latest()->get();
        return view('admin.videos.index', compact('videos'));
    }

    /**
     * Show the form for creating a new resource.
     * WE HAVE UPDATED THIS METHOD.
     */
    public function create()
    {
        // OLD: $categories = NewsCategory::all();

        // NEW: Only get the specific categories we want.
        $categories = NewsCategory::whereIn('name', ['News', 'Events', 'Innovations'])->get();

        return view('admin.videos.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'news_category_id' => 'required|exists:news_categories,id',
            'description' => 'nullable|string',
            'video_url' => ['required', 'url', 'max:2048', new EmbeddableVideoUrl()], // a link the site can play (YouTube, or one TikTok video)
            'thumbnail_image' => ImageUploadRules::rules(),
        ]);

        if ($request->hasFile('thumbnail_image')) {
            $data['thumbnail_image'] = $request->file('thumbnail_image')->store('videos', 'public');
        }

        $data['slug'] = Str::slug($data['title']) . '-' . time();

        Video::create($data);

        return redirect()->route('admin.videos.index')->with('success', 'Video created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     * WE HAVE UPDATED THIS METHOD.
     */
    public function edit(Video $video)
    {
        // OLD: $categories = NewsCategory::all();

        // NEW: Only get the specific categories we want.
        $categories = NewsCategory::whereIn('name', ['News', 'Events', 'Innovations'])->get();

        return view('admin.videos.edit', compact('video', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Video $video)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'news_category_id' => 'required|exists:news_categories,id',
            'description' => 'nullable|string',
            'video_url' => ['required', 'url', 'max:2048', new EmbeddableVideoUrl()],
            'thumbnail_image' => ImageUploadRules::rules(),
        ]);

        if ($request->hasFile('thumbnail_image')) {
            $data['thumbnail_image'] = $request->file('thumbnail_image')->store('videos', 'public');
        }

        if ($request->title !== $video->title) {
            $data['slug'] = Str::slug($data['title']) . '-' . time();
        }

        $video->update($data);

        return redirect()->route('admin.videos.index')->with('success', 'Video updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Video $video)
    {
        \App\Support\MediaCleanup::remove([$video->thumbnail_image]);
        $video->delete();
        \Illuminate\Support\Facades\Log::notice('video deleted', ['user_id' => auth()->id(), 'video_id' => $video->id]);
        return redirect()->route('admin.videos.index')->with('success', 'Video deleted successfully.');
    }
}