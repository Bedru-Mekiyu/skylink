{{-- This form is included by create.blade.php and edit.blade.php --}}

{{-- Title Field --}}
<div class="mb-3">
    <label for="title" class="form-label">Video Title</label>
    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $video->title ?? '') }}" required>
    @error('title')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Category Field --}}
<div class="mb-3">
    <label for="news_category_id" class="form-label">Category</label>
    {{-- Correct BS5 class is form-select --}}
    <select class="form-select @error('news_category_id') is-invalid @enderror" id="news_category_id" name="news_category_id" required>
        <option value="">Select a category</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ old('news_category_id', $video->news_category_id ?? '') == $category->id ? 'selected' : '' }}>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
    @error('news_category_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Video URL Field --}}
<div class="mb-3">
    <label for="video_url" class="form-label">Video URL (YouTube or TikTok)</label>
    <input type="url" class="form-control @error('video_url') is-invalid @enderror" id="video_url" name="video_url" value="{{ old('video_url', $video->video_url ?? '') }}" required placeholder="https://www.youtube.com/watch?v=... or https://www.tiktok.com/@name/video/..." aria-describedby="video_url_help">
    <div class="form-text" id="video_url_help">YouTube (youtube.com/watch?v=…, youtu.be/…) or the full link of one TikTok video (tiktok.com/@name/video/…). Short TikTok links (vm.tiktok.com) cannot be played: open them in the browser and copy the full address.</div>
    @error('video_url')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Description Field --}}
<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $video->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Thumbnail Image Field --}}
<div class="mb-3">
    <label for="thumbnail-upload" class="form-label">Thumbnail Image</label>
    <input type="file" class="form-control @error('thumbnail_image') is-invalid @enderror" id="thumbnail-upload" name="thumbnail_image">
    @error('thumbnail_image')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- This container holds the current image OR the new preview --}}
<div id="thumbnail-preview-container" class="mt-2 {{ isset($video) && $video->thumbnail_image ? '' : 'd-none' }}">
    <small class="text-muted d-block mb-1">{{ isset($video) && $video->thumbnail_image ? 'Current Thumbnail:' : 'New Image Preview:'}}</small>
    <img id="thumbnail-preview" src="{{ isset($video) && $video->thumbnail_image ? asset('storage/' . $video->thumbnail_image) : '#' }}" alt="Thumbnail Preview" style="width: 200px; height: auto; border-radius: 0.25rem;">
</div>

{{-- NOTE: The Submit and Cancel buttons have been moved to the parent create/edit views for better control. --}}