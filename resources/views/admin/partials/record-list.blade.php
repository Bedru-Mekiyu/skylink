{{--
  Shared list for visitor records with delete / delete selected / CSV export.
  Expects: $rows (paginator), $routes (e.g. "admin.subscribers"), $label (singular), $cols (header => callable|attribute), $nameOf (callable giving the row's name for the modal).
--}}
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <span class="fw-semibold">{{ $rows->total() }} {{ \Illuminate\Support\Str::plural($label, $rows->total()) }}</span>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route($routes.'.export') }}" class="btn btn-outline-secondary" style="min-height:44px"><i class="fas fa-file-csv me-1" aria-hidden="true"></i>Export CSV (all)</a>
            <form action="{{ route($routes.'.bulk-destroy') }}" method="POST" id="bulk-form" data-sky-confirm="Delete the ticked {{ \Illuminate\Support\Str::plural($label) }}? They are removed for good. Export the CSV first if you need a copy." data-sky-title="Delete selected?" data-sky-variant="danger" data-sky-ok="Delete selected">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger" id="bulk-btn" style="min-height:44px" disabled><i class="fas fa-trash me-1" aria-hidden="true"></i>Delete selected (<span id="bulk-count">0</span>)</button>
            </form>
        </div>
    </div>
    @if ($errors->has('ids'))<div class="alert alert-danger m-3 mb-0" role="alert">{{ $errors->first('ids') }}</div>@endif
    <div class="table-wrapper">
        <table class="table table-hover align-middle mb-0 table-responsive-cards">
            <caption class="visually-hidden">{{ \Illuminate\Support\Str::plural($label) }}, newest first</caption>
            <thead>
                <tr>
                    <th scope="col" class="ps-3" style="width:3rem"><input class="form-check-input" type="checkbox" id="check-all" aria-label="Select all on this page"></th>
                    @foreach ($cols as $head => $get)<th scope="col">{{ $head }}</th>@endforeach
                    <th scope="col" class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="ps-3" data-label="Select"><input class="form-check-input row-check" type="checkbox" form="bulk-form" name="ids[]" value="{{ $row->id }}" aria-label="Select {{ $nameOf($row) }}"></td>
                    @foreach ($cols as $head => $get)<td data-label="{{ $head }}" class="text-break">{{ is_callable($get) ? $get($row) : ($row->{$get} ?: '—') }}</td>@endforeach
                    <td data-label="Actions" class="text-end pe-3">
                        <form action="{{ route($routes.'.destroy', $row->id) }}" method="POST" class="d-inline" data-sky-confirm="Delete {{ $label }} &quot;{{ $nameOf($row) }}&quot;? The row is removed for good. Export the CSV first if you need a copy." data-sky-title="Delete {{ $label }}?" data-sky-variant="danger" data-sky-ok="Delete">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" style="min-width:44px;min-height:44px" aria-label="Delete {{ $nameOf($row) }}"><i class="fas fa-trash" aria-hidden="true"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ count($cols) + 2 }}" class="text-center text-muted py-5">No {{ \Illuminate\Support\Str::plural($label) }} yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($rows->hasPages())<div class="card-footer d-flex justify-content-center">{{ $rows->onEachSide(1)->links('pagination::bootstrap-5') }}</div>@endif
</div>
<script>
(function () {
    var checks = [].slice.call(document.querySelectorAll('.row-check')), all = document.getElementById('check-all'), btn = document.getElementById('bulk-btn'), cnt = document.getElementById('bulk-count');
    function sync() { var n = checks.filter(function (c) { return c.checked; }).length; cnt.textContent = n; btn.disabled = n === 0; }
    checks.forEach(function (c) { c.addEventListener('change', sync); });
    if (all) all.addEventListener('change', function () { checks.forEach(function (c) { c.checked = all.checked; }); sync(); });
})();
</script>
