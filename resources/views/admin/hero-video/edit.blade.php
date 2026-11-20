@extends('admin.layouts.master')

@section('title', 'Homepage Video')
@section('page-title', 'Homepage Video')

@section('content')
@php
    $has = fn (string $k) => $current[$k] ?? null;
    $previewPoster = $has('poster') ?? asset('assets/videos/hero/poster.jpg');
    $previewVideo = $hasCustom ? ($has('desktop_mp4') ?? $has('desktop_webm') ?? $has('mobile_mp4')) : asset('assets/videos/hero/hero-desktop.mp4');
@endphp

@foreach ((array) session('hero_warnings', []) as $w)
    <div class="alert alert-warning" role="alert"><i class="fas fa-triangle-exclamation me-2" aria-hidden="true"></i>{{ $w }}</div>
@endforeach

<div class="alert {{ $hasCustom ? 'alert-success' : 'alert-secondary' }}" role="status">
    <i class="fas {{ $hasCustom ? 'fa-circle-check' : 'fa-film' }} me-2" aria-hidden="true"></i>
    <strong>{{ $hasCustom ? 'The homepage plays your uploaded video.' : 'The homepage plays the built-in video.' }}</strong>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-header"><h2 class="h5 mb-0"><i class="fas fa-eye me-2" aria-hidden="true"></i>Current hero</h2></div>
            <div class="card-body">
                <div class="position-relative overflow-hidden rounded bg-dark" style="aspect-ratio:16/9">
                    <img src="{{ $previewPoster }}" alt="" class="position-absolute top-0 start-0 w-100 h-100" style="object-fit:cover">
                    <video class="position-absolute top-0 start-0 w-100 h-100" style="object-fit:cover" autoplay muted loop playsinline preload="metadata" poster="{{ $previewPoster }}" id="hero-preview-video" aria-label="Preview of the homepage background video">
                        <source src="{{ $previewVideo }}" type="video/mp4">
                    </video>
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background:linear-gradient(180deg,rgba(0,0,0,.45),rgba(0,0,0,.25) 50%,rgba(0,0,0,.6));pointer-events:none"></div>
                    <div class="position-absolute bottom-0 start-0 p-3 text-white small" style="pointer-events:none">Preview with the dark overlay</div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn btn-outline-danger" id="btn-reset" @disabled(! $hasCustom)><i class="fas fa-rotate-left me-1" aria-hidden="true"></i>Reset to default</button>
                    <button type="button" class="btn btn-outline-primary" id="btn-restore" @disabled(! $hasPrevious)><i class="fas fa-clock-rotate-left me-1" aria-hidden="true"></i>Restore previous</button>
                </div>
                <form method="POST" action="{{ route('admin.hero-video.reset') }}" id="form-reset" class="d-none">@csrf @method('PUT')</form>
                <form method="POST" action="{{ route('admin.hero-video.restore') }}" id="form-restore" class="d-none">@csrf @method('PUT')</form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header"><h2 class="h5 mb-0"><i class="fas fa-upload me-2" aria-hidden="true"></i>Upload</h2></div>
            <div class="card-body">
                <ul class="text-muted small ps-3 mb-4">
                    <li>Best format: <strong>MP4 (H.264)</strong>, no sound, 10&ndash;20 seconds, 1920 px wide.</li>
                    <li>Ideal size: under <strong>5 MB</strong> for desktop, under <strong>1.5 MB</strong> for mobile.</li>
                    <li>This server accepts files up to <strong>{{ $limitLabel }} MB</strong> in one upload.</li>
                    <li>
                        @if ($ffmpeg)
                            The server will shrink the video, make a phone version and a poster automatically.
                        @else
                            This server cannot shrink videos: files are used as uploaded, so a <strong>poster image is required</strong> with a new desktop video.
                        @endif
                    </li>
                    <li>The poster shows instantly while the video loads, for visitors who prefer reduced motion, and if the video fails.</li>
                </ul>

                <form method="POST" action="{{ route('admin.hero-video.update') }}" enctype="multipart/form-data" id="hero-form" data-limit="{{ $limitBytes }}" novalidate>
                    @csrf
                    @method('PUT')
                    @foreach ([
                        'desktop_mp4' => ['Desktop video (MP4)', '.mp4,video/mp4', 'Required the first time.'],
                        'desktop_webm' => ['Desktop video (WebM)', '.webm,video/webm', 'Optional, smaller on some browsers.'],
                        'mobile_mp4' => ['Mobile video (MP4)', '.mp4,video/mp4', 'Optional, a smaller file for phones.'],
                        'poster' => ['Poster image', '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp', $ffmpeg ? 'Optional: made from the video if empty.' : 'Required with a new desktop video.'],
                    ] as $name => [$label, $accept, $help])
                        <div class="mb-3">
                            <label for="f-{{ $name }}" class="form-label mb-1">{{ $label }}</label>
                            <input type="file" class="form-control" id="f-{{ $name }}" name="{{ $name }}" accept="{{ $accept }}" aria-describedby="h-{{ $name }}">
                            <div class="form-text" id="h-{{ $name }}">{{ $help }}</div>
                            <div class="text-danger small" id="e-{{ $name }}" role="alert"></div>
                        </div>
                    @endforeach

                    <div class="progress mb-3 d-none" id="hero-progress" role="progressbar" aria-label="Upload progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="height:1.25rem">
                        <div class="progress-bar" style="width:0%">0%</div>
                    </div>
                    <div class="alert alert-danger d-none" id="hero-error" role="alert"></div>

                    <button type="submit" class="btn btn-primary" id="hero-submit"><i class="fas fa-save me-1" aria-hidden="true"></i>Upload and publish</button>
                    <p class="small text-muted mt-2 mb-0">The change is live for visitors as soon as the upload finishes. Your previous video is kept so you can restore it.</p>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('hero-form');
    var bar = document.getElementById('hero-progress');
    var fill = bar.firstElementChild;
    var err = document.getElementById('hero-error');
    var btn = document.getElementById('hero-submit');
    var limit = parseInt(form.dataset.limit, 10);
    var names = ['desktop_mp4', 'desktop_webm', 'mobile_mp4', 'poster'];

    function clearErrors() { err.classList.add('d-none'); names.forEach(function (n) { document.getElementById('e-' + n).textContent = ''; }); }
    function fail(msg) { err.textContent = msg; err.classList.remove('d-none'); btn.disabled = false; bar.classList.add('d-none'); }
    function mb(b) { return (b / 1048576).toFixed(1); }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();
        var total = 0, any = false;
        names.forEach(function (n) { var f = form.elements[n].files[0]; if (f) { any = true; total += f.size; } });
        if (!any) { return fail('Choose at least one file to upload.'); }
        if (limit && total > limit) { return fail('These files total ' + mb(total) + ' MB, but this server accepts ' + mb(limit) + ' MB per upload. Upload them one at a time or use smaller files.'); }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.onprogress = function (ev) {
            if (!ev.lengthComputable) return;
            var p = Math.round(ev.loaded / ev.total * 100);
            fill.style.width = p + '%'; fill.textContent = p + '%'; bar.setAttribute('aria-valuenow', p);
        };
        xhr.onload = function () {
            var body = {}; try { body = JSON.parse(xhr.responseText); } catch (x) {}
            if (xhr.status >= 200 && xhr.status < 300 && body.ok) { window.location.reload(); return; }
            if (xhr.status === 422 && body.errors) {
                Object.keys(body.errors).forEach(function (k) { var el = document.getElementById('e-' + k); if (el) el.textContent = body.errors[k][0]; });
                return fail('The upload was not accepted. See the messages under the files.');
            }
            if (xhr.status === 429) return fail('Too many uploads. Please wait a while and try again.');
            if (xhr.status === 419) return fail('Your session expired. Reload the page and log in again.');
            if (xhr.status === 413) return fail('The files are larger than this server accepts (' + mb(limit) + ' MB).');
            fail('The upload failed (error ' + xhr.status + '). Nothing was changed.');
        };
        xhr.onerror = function () { fail('The connection dropped during the upload. Nothing was changed.'); };
        btn.disabled = true; fill.style.width = '0%'; fill.textContent = '0%'; bar.classList.remove('d-none');
        xhr.send(new FormData(form));
    });

    function ask(btnId, formId, opts) {
        var b = document.getElementById(btnId);
        b.addEventListener('click', function () {
            window.SkyConfirm.ask(opts).then(function (ok) { if (ok) document.getElementById(formId).submit(); });
        });
    }
    ask('btn-reset', 'form-reset', { title: 'Go back to the built-in video?', message: 'Visitors see the original video again at once. Your uploaded video is kept, so you can restore it.', confirmLabel: 'Reset to default', variant: 'danger' });
    ask('btn-restore', 'form-restore', { title: 'Restore the previous video?', message: 'The previous version goes live now and the current one becomes the previous.', confirmLabel: 'Restore previous', variant: 'primary' });
})();
</script>
@endpush
