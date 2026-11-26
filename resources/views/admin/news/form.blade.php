{{-- Fields shared by the create and edit pages. Expects $news, $categories, $formToken. --}}
@use('App\Http\Requests\Admin\NewsRequest')
@use('App\Models\News')
@php
    $effectiveStatus = $news->exists && $news->isVisible() ? News::STATUS_PUBLISHED : ($news->status ?? News::STATUS_DRAFT);
    $status = old('status', $effectiveStatus);
    $language = old('language', $news->language ?? 'am');
    $publishAt = old('published_at', $news->status === News::STATUS_SCHEDULED ? NewsRequest::formatAdminTime($news->published_at) : null);
    $bodyHtml = old('content', $news->exists ? $news->body_html : '');
    $similar = session('similar', []);
@endphp

<input type="hidden" name="form_token" value="{{ $formToken }}">

@if ($errors->any())
    <div class="alert alert-danger" role="alert" tabindex="-1" id="formErrors">
        <strong><i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i>The article was not saved.</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (! empty($similar))
    <div class="alert alert-warning news-similar" role="region" aria-labelledby="similarHeading">
        <h2 class="h6 mb-2" id="similarHeading"><i class="fas fa-clone me-1" aria-hidden="true"></i>Articles with a similar title</h2>
        <ul class="mb-2">
            @foreach ($similar as $item)
                <li>
                    <a href="{{ $item['edit_url'] }}" target="_blank" rel="noopener">{{ $item['title'] }}</a>
                    <span class="text-muted small">({{ ucfirst($item['status']) }}, {{ $item['date'] }})</span>
                    @if ($item['public_url'])
                        · <a href="{{ $item['public_url'] }}" target="_blank" rel="noopener" class="small">view on site</a>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="confirm_similar" value="1" id="confirm_similar">
            <label class="form-check-label" for="confirm_similar">This is a different article. Save it anyway.</label>
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="mb-3">
            <label class="form-label" for="title">Title <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="text" name="title" id="title" value="{{ old('title', $news->title) }}"
                   class="form-control form-control-lg @error('title') is-invalid @enderror" required maxlength="255"
                   lang="{{ $language }}" autocomplete="off" aria-describedby="similarLive"
                   data-similar-url="{{ route('admin.news.similar') }}" data-except="{{ $news->id }}">
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div id="similarLive" class="news-similar-live small mt-1" aria-live="polite"></div>
        </div>

        <div class="mb-3">
            <label class="form-label" id="bodyLabel" for="content">Article body <span class="text-danger" aria-hidden="true">*</span></label>
            <div class="news-editor @error('content') is-invalid @enderror" data-editor hidden>
                <div class="news-editor__toolbar" role="toolbar" aria-label="Text formatting" aria-controls="bodyEditor">
                    <button type="button" class="btn btn-sm btn-light" data-cmd="formatBlock" data-value="P" title="Paragraph">¶<span class="visually-hidden">Paragraph</span></button>
                    <button type="button" class="btn btn-sm btn-light" data-cmd="formatBlock" data-value="H2" title="Heading">H2<span class="visually-hidden"> heading</span></button>
                    <button type="button" class="btn btn-sm btn-light" data-cmd="formatBlock" data-value="H3" title="Sub-heading">H3<span class="visually-hidden"> sub-heading</span></button>
                    <span class="news-editor__sep" aria-hidden="true"></span>
                    <button type="button" class="btn btn-sm btn-light" data-cmd="bold" aria-pressed="false" title="Bold (Ctrl+B)"><i class="fas fa-bold" aria-hidden="true"></i><span class="visually-hidden">Bold</span></button>
                    <button type="button" class="btn btn-sm btn-light" data-cmd="italic" aria-pressed="false" title="Italic (Ctrl+I)"><i class="fas fa-italic" aria-hidden="true"></i><span class="visually-hidden">Italic</span></button>
                    <span class="news-editor__sep" aria-hidden="true"></span>
                    <button type="button" class="btn btn-sm btn-light" data-cmd="insertUnorderedList" aria-pressed="false" title="Bulleted list"><i class="fas fa-list-ul" aria-hidden="true"></i><span class="visually-hidden">Bulleted list</span></button>
                    <button type="button" class="btn btn-sm btn-light" data-cmd="insertOrderedList" aria-pressed="false" title="Numbered list"><i class="fas fa-list-ol" aria-hidden="true"></i><span class="visually-hidden">Numbered list</span></button>
                    <span class="news-editor__sep" aria-hidden="true"></span>
                    <button type="button" class="btn btn-sm btn-light" data-cmd="createLink" title="Add link"><i class="fas fa-link" aria-hidden="true"></i><span class="visually-hidden">Add link</span></button>
                    <button type="button" class="btn btn-sm btn-light" data-cmd="unlink" title="Remove link"><i class="fas fa-unlink" aria-hidden="true"></i><span class="visually-hidden">Remove link</span></button>
                </div>
                <div class="news-editor__area" id="bodyEditor" contenteditable="true" role="textbox" aria-multiline="true"
                     aria-labelledby="bodyLabel" aria-describedby="bodyHelp" lang="{{ $language }}"></div>
            </div>
            {{-- The real field. Without JavaScript the admin types HTML or plain text here. --}}
            <textarea name="content" id="content" rows="14" class="form-control @error('content') is-invalid @enderror" aria-describedby="bodyHelp" required>{{ $bodyHtml }}</textarea>
            <div id="bodyHelp" class="form-text">Paragraphs, bold, italic, headings, lists and links are kept. Anything else (colours, fonts, scripts) is removed when you save. Pasted text is inserted without its formatting.</div>
            @error('content')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-lg-4">
        <fieldset class="news-panel mb-3">
            <legend class="news-panel__title">Publishing</legend>
            @foreach (['draft' => ['Draft', 'Only admins can see it.'], 'published' => ['Published', 'Visible on the site now.'], 'scheduled' => ['Scheduled', 'Appears at the time you choose.']] as $value => [$label, $hint])
                <div class="form-check mb-1">
                    <input class="form-check-input" type="radio" name="status" id="status_{{ $value }}" value="{{ $value }}" @checked($status === $value) required>
                    <label class="form-check-label" for="status_{{ $value }}"><strong>{{ $label }}</strong> <span class="text-muted small">{{ $hint }}</span></label>
                </div>
            @endforeach
            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

            <div class="mt-2" data-schedule-field @if ($status !== 'scheduled') hidden @endif>
                <label class="form-label small mb-1" for="published_at">Publish on (Addis Ababa time)</label>
                <input type="datetime-local" name="published_at" id="published_at" value="{{ $publishAt }}"
                       class="form-control @error('published_at') is-invalid @enderror">
                @error('published_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            @if ($news->exists && $news->isVisible())
                <p class="small text-muted mt-2 mb-0">Published {{ $news->display_date->copy()->setTimezone(config('news.admin_timezone'))->format('M j, Y H:i') }}.
                    <a href="{{ route('news.show', $news->slug) }}" target="_blank" rel="noopener">View on site</a></p>
            @elseif ($news->exists)
                <p class="small text-muted mt-2 mb-0"><a href="{{ route('news.show', $news->slug) }}" target="_blank" rel="noopener">Preview</a> (only admins can open it)</p>
            @endif

            <hr>
            <div class="form-check">
                <input type="hidden" name="is_featured" value="0">
                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" @checked(old('is_featured', $news->is_featured))>
                <label class="form-check-label" for="is_featured">Feature on top of the News page</label>
                <div class="form-text mt-0">Only one article is featured. Ticking this replaces the current one once this article is published.</div>
            </div>
        </fieldset>

        <fieldset class="news-panel mb-3">
            <legend class="news-panel__title">Language and category</legend>
            <div class="mb-3" role="radiogroup" aria-labelledby="languageLabel">
                <div class="form-label small mb-1" id="languageLabel">Language</div>
                @foreach (News::LANGUAGES as $code => $name)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="language" id="language_{{ $code }}" value="{{ $code }}" @checked($language === $code) required>
                        <label class="form-check-label" for="language_{{ $code }}">{{ $name }}</label>
                    </div>
                @endforeach
                @error('language')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <label class="form-label small mb-1" for="news_category_id">Category</label>
            <select name="news_category_id" id="news_category_id" class="form-select @error('news_category_id') is-invalid @enderror" required>
                <option value="">Choose a category</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected((string) old('news_category_id', $news->news_category_id) === (string) $cat->id)>{{ $cat->label }}</option>
                @endforeach
            </select>
            @error('news_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </fieldset>

        <fieldset class="news-panel mb-3">
            <legend class="news-panel__title">Web address</legend>
            <label class="form-label small mb-1" for="url_title">English URL title <span class="text-muted">(optional)</span></label>
            <input type="text" name="url_title" id="url_title" value="{{ old('url_title', $news->url_title) }}" maxlength="80"
                   class="form-control @error('url_title') is-invalid @enderror" lang="en" autocomplete="off"
                   aria-describedby="urlHelp urlPreview" placeholder="e.g. Dire Dawa court digitisation">
            @error('url_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div id="urlHelp" class="form-text">A few English words for the link. Leave empty for an automatic address{{ $news->exists ? '' : ' (news-<number>)' }}.</div>
            <div id="urlPreview" class="form-text text-break">
                Address: <code>/news/<span data-slug-preview data-fallback="{{ $news->exists ? $news->slug : 'news-…' }}" data-current="{{ $news->slug }}">{{ $news->slug ?? 'news-…' }}</span></code>
            </div>
            @if ($news->exists && $news->isVisible())
                <div class="form-text text-warning-emphasis" data-slug-warning hidden>
                    <i class="fas fa-info-circle" aria-hidden="true"></i> The address will change. The old address keeps working and forwards visitors to the new one.
                </div>
            @endif
        </fieldset>

        <fieldset class="news-panel mb-3">
            <legend class="news-panel__title">Image</legend>
            @if ($news->thumb_url)
                <img src="{{ $news->thumb_url }}" alt="Current image" class="img-fluid rounded mb-2" width="640" height="360" loading="lazy">
            @endif
            <label class="form-label small mb-1" for="image">{{ $news->image ? 'Replace image' : 'Upload image' }}</label>
            <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror"
                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" aria-describedby="imageHelp">
            @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div id="imageHelp" class="form-text">JPG, PNG or WebP, up to 5 MB. A web copy (max 1600 px wide) and a card thumbnail are made automatically.</div>
            @if ($errors->any())
                <div class="form-text text-warning-emphasis"><i class="fas fa-info-circle" aria-hidden="true"></i> If you had chosen a new image, please choose it again: the browser clears it when the form comes back.</div>
            @endif
        </fieldset>
    </div>
</div>

@push('styles')
<style>
    .news-panel { border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1rem 1.1rem; background: #fff; }
    .news-panel__title { float: none; width: auto; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); margin-bottom: 0.6rem; padding: 0; }
    .news-editor { border: 1px solid #dee2e6; border-radius: 0.5rem; background: #fff; }
    .news-editor.is-invalid { border-color: #dc3545; }
    .news-editor:focus-within { border-color: #7ccdee; box-shadow: 0 0 0 0.25rem rgba(26, 111, 176, 0.25); }
    .news-editor__toolbar { display: flex; flex-wrap: wrap; gap: 4px; padding: 6px; border-bottom: 1px solid #dee2e6; background: #f8f9fa; border-radius: 0.5rem 0.5rem 0 0; position: sticky; top: 57px; z-index: 2; }
    .news-editor__toolbar .btn { min-width: 36px; min-height: 36px; font-weight: 600; }
    .news-editor__toolbar .btn[aria-pressed="true"] { background: #dbeafe; border-color: #7ccdee; color: #2b4c9d; }
    .news-editor__toolbar .btn:focus-visible { outline: 3px solid #1a6fb0; outline-offset: 1px; }
    .news-editor__sep { width: 1px; background: #dee2e6; margin: 4px 2px; }
    .news-editor__area { min-height: 320px; max-height: 70vh; overflow-y: auto; padding: 14px 16px; line-height: 1.75; outline: none; }
    .news-editor__area[lang="am"] { font-family: var(--font-ui); font-size: 1.05rem; line-height: 1.95; }
    .news-editor__area:empty::before { content: 'Write the article here…'; color: #9ca3af; }
    .news-editor__area h2 { font-size: 1.4rem; margin: 1rem 0 0.5rem; }
    .news-editor__area h3 { font-size: 1.2rem; margin: 1rem 0 0.5rem; }
    .news-editor__area a { color: #2b4c9d; text-decoration: underline; }
    .news-similar-live:empty { display: none; }
    .news-similar-live { color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 0.4rem; padding: 6px 10px; }
    .is-saving .spinner-border { display: inline-block !important; }
    /* The layout lifts cards on hover; that is distracting on a long form. */
    .news-form-card.card:hover { transform: none; box-shadow: none; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    if (window.__skyNewsForm) return;
    window.__skyNewsForm = true;

    var form = document.querySelector('[data-news-form]');
    if (!form) return;

    // ---- Error summary gets focus so screen readers announce it.
    var summary = document.getElementById('formErrors');
    if (summary) summary.focus();

    // ---- Save once: disable the button on the first submit.
    var saving = false;
    form.addEventListener('submit', function (e) {
        syncEditor();
        if (saving) { e.preventDefault(); return; }
        saving = true;
        form.querySelectorAll('[data-save-button]').forEach(function (btn) {
            btn.disabled = true;
            btn.classList.add('is-saving');
            var label = btn.querySelector('[data-label]');
            if (label) label.textContent = 'Saving…';
        });
    });
    // Coming back with the browser's Back button: allow saving again.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        saving = false;
        form.querySelectorAll('[data-save-button]').forEach(function (btn) {
            btn.disabled = false;
            btn.classList.remove('is-saving');
        });
    });

    // ---- Status: show the date field only for "Scheduled".
    var scheduleField = form.querySelector('[data-schedule-field]');
    var scheduleInput = document.getElementById('published_at');
    form.querySelectorAll('input[name="status"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var on = radio.value === 'scheduled' && radio.checked;
            if (radio.checked) {
                scheduleField.hidden = !on;
                scheduleInput.required = on;
                if (on && !scheduleInput.value) scheduleInput.focus();
            }
        });
    });
    scheduleInput.required = !scheduleField.hidden;

    // ---- Language: set lang on the title and body for correct fonts and spellcheck.
    var title = document.getElementById('title');
    var area = document.getElementById('bodyEditor');
    form.querySelectorAll('input[name="language"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (!radio.checked) return;
            title.lang = radio.value;
            area.lang = radio.value;
        });
    });

    // ---- Address preview from the English URL title (same rule as the server).
    var urlTitle = document.getElementById('url_title');
    var preview = form.querySelector('[data-slug-preview]');
    var slugWarning = form.querySelector('[data-slug-warning]');
    var initialUrlTitle = urlTitle.value;
    function slugify(text) {
        return text.normalize('NFKD').replace(/[̀-ͯ]/g, '').toLowerCase()
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80).replace(/-+$/, '');
    }
    function updatePreview() {
        var slug = slugify(urlTitle.value);
        var current = preview.dataset.current;
        // A saved article keeps its address until the English URL title is changed.
        var changed = current ? urlTitle.value.trim() !== initialUrlTitle.trim() : true;
        preview.textContent = (!changed && current) ? current : (slug || 'news-' + (title.dataset.except || '…'));
        if (slugWarning) slugWarning.hidden = !(current && changed);
    }
    urlTitle.addEventListener('input', updatePreview);
    updatePreview();

    // ---- Similar titles while typing (server decides; this is only a hint).
    var live = document.getElementById('similarLive');
    var timer = null;
    var lastQuery = title.value.trim();
    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
    }
    title.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            var q = title.value.trim();
            if (q === lastQuery) return;
            lastQuery = q;
            if (q.length < 4) { live.innerHTML = ''; return; }
            var url = title.dataset.similarUrl + '?title=' + encodeURIComponent(q) + (title.dataset.except ? '&except=' + title.dataset.except : '');
            fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : { matches: [] }; })
                .then(function (data) {
                    if (title.value.trim() !== q) return;
                    if (!data.matches || !data.matches.length) { live.innerHTML = ''; return; }
                    live.innerHTML = '<i class="fas fa-clone me-1" aria-hidden="true"></i>Similar title already exists: ' +
                        data.matches.slice(0, 3).map(function (m) {
                            return '<a href="' + escapeHtml(m.edit_url) + '" target="_blank" rel="noopener">' + escapeHtml(m.title) + '</a> (' + escapeHtml(m.date || '') + ')';
                        }).join('; ');
                })
                .catch(function () { /* the server check on save still runs */ });
        }, 450);
    });

    // ---- Body editor (contenteditable). The textarea stays the real field.
    var textarea = document.getElementById('content');
    var editor = form.querySelector('[data-editor]');
    function syncEditor() {
        if (!editor.hidden) textarea.value = area.innerHTML.trim() === '<br>' ? '' : area.innerHTML;
    }
    if (typeof document.execCommand === 'function') {
        area.innerHTML = textarea.value;
        editor.hidden = false;
        textarea.hidden = true;
        textarea.required = false;
        try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) {}

        var buttons = editor.querySelectorAll('[data-cmd]');
        function refreshState() {
            buttons.forEach(function (btn) {
                if (!btn.hasAttribute('aria-pressed')) return;
                var on = false;
                try { on = document.queryCommandState(btn.dataset.cmd); } catch (e) {}
                btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
        }
        buttons.forEach(function (btn) {
            btn.addEventListener('mousedown', function (e) { e.preventDefault(); }); // keep the text selection
            btn.addEventListener('click', function () {
                area.focus();
                var cmd = btn.dataset.cmd;
                if (cmd === 'createLink') {
                    // Keep the selected text: the dialog takes the focus, so remember the range and put it back.
                    var sel = window.getSelection();
                    var saved = sel && sel.rangeCount ? sel.getRangeAt(0).cloneRange() : null;
                    var apply = function (href) {
                        if (!href || href === 'https://') return;
                        if (!/^(https?:\/\/|mailto:|tel:|\/)/i.test(href)) href = 'https://' + href;
                        area.focus();
                        if (saved) { sel.removeAllRanges(); sel.addRange(saved); }
                        document.execCommand('createLink', false, href);
                        refreshState();
                        syncEditor();
                    };
                    if (window.SkyConfirm) {
                        window.SkyConfirm.ask({ title: 'Insert link', confirmLabel: 'Insert link', variant: 'primary', input: { label: 'Link address (https://…)', value: 'https://' } })
                            .then(function (v) { if (typeof v === 'string') apply(v); });
                    } else { apply(window.prompt('Link address (https://…)', 'https://')); }
                    return;
                } else {
                    document.execCommand(cmd, false, btn.dataset.value || null);
                }
                refreshState();
                syncEditor();
            });
        });
        // Paste as plain text: Word/web formatting would be stripped anyway.
        area.addEventListener('paste', function (e) {
            var text = (e.clipboardData || window.clipboardData).getData('text/plain');
            if (text === undefined) return;
            e.preventDefault();
            document.execCommand('insertText', false, text);
        });
        area.addEventListener('input', syncEditor);
        document.addEventListener('selectionchange', function () {
            if (document.activeElement === area) refreshState();
        });
        // Clicking the label focuses the editor.
        document.getElementById('bodyLabel').addEventListener('click', function (e) { e.preventDefault(); area.focus(); });
    }
})();
</script>
@endpush
