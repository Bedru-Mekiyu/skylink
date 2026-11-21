{{-- Showcase video + "Feature on top" (create and edit). Expects $clips, optional $project. --}}
@php
    $project = $project ?? null;
    $currentLibrary = null;
    foreach ($clips as $name => $clip) {
        if ($project && ($project->video_mp4 === $clip['mp4'] || $project->video_webm === $clip['webm']) && ($clip['mp4'] || $clip['webm'])) {
            $currentLibrary = $name;
        }
    }
@endphp
<fieldset class="border rounded-3 p-3 mb-3">
    <legend class="float-none w-auto px-2 fs-6 fw-semibold mb-0">Showcase video <span class="text-muted fw-normal">(optional)</span></legend>

    @if ($project && $project->has_video)
        <p class="small mb-2">
            Current video:
            <code>{{ $project->video_mp4 ?? $project->video_webm }}</code>
        </p>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remove_video" value="1" id="remove_video">
            <label class="form-check-label" for="remove_video">Remove the video (the project shows its image)</label>
        </div>
    @endif

    <div class="mb-3">
        <label for="video_library" class="form-label">Choose a clip from the site's video folder</label>
        <select name="video_library" id="video_library" class="form-select" aria-describedby="video_library_help">
            <option value="">— keep the current setting —</option>
            @foreach ($clips as $name => $clip)
                <option value="{{ $name }}" @selected(old('video_library', $currentLibrary) === $name)>
                    {{ $name }} ({{ implode(' + ', array_filter([$clip['mp4'] ? 'MP4' : null, $clip['webm'] ? 'WebM' : null, $clip['poster'] ? 'poster' : null])) }})
                </option>
            @endforeach
        </select>
        <div id="video_library_help" class="form-text">
            Clips in <code>public/assets/videos/portfolio/</code> (name.mp4, name.webm, name-poster.jpg).
            Large files are uploaded there with the cPanel File Manager.
        </div>
    </div>

    <div class="mb-3">
        <label for="video_file" class="form-label">…or upload one clip</label>
        <input type="file" name="video_file" id="video_file" class="form-control" accept=".mp4,.webm,video/mp4,video/webm" aria-describedby="video_file_help">
        <div id="video_file_help" class="form-text">MP4 or WebM, silent, about 10 seconds, up to {{ intdiv(\App\Support\ShowcaseVideos::MAX_KILOBYTES, 1024) }} MB. The file is checked; renamed files are refused.</div>
    </div>

    <div class="mb-3">
        <label for="video_poster_file" class="form-label">Poster image for the video (optional)</label>
        <input type="file" name="video_poster_file" id="video_poster_file" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <div class="form-text">Shown while the video loads, and on phones. Without one, the project image is used.</div>
    </div>

    <div class="form-check">
        <input type="hidden" name="is_featured" value="0">
        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" @checked(old('is_featured', $project?->is_featured))>
        <label class="form-check-label" for="is_featured">Pin as banner <span class="badge text-bg-warning">Overrides the newest project</span></label>
        <div class="form-text mt-0">Normally the NEWEST project becomes the banner of the Portfolio page by itself (with its video if it has one, otherwise its image). Tick this to keep THIS project in the banner instead. Only one project can be pinned; ticking it here replaces the pinned one.</div>
    </div>
</fieldset>
