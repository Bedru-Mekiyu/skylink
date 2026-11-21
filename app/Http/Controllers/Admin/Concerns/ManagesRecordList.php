<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Delete one, delete selected and CSV export for a plain list of visitor records (subscribers, plan subscriptions).
 * Admin-only (the routes sit in the admin group). Deleting removes the row for good and is written to the log.
 */
trait ManagesRecordList
{
    /** @return class-string<Model> */
    abstract protected function recordModel(): string;

    /** Route name prefix, e.g. "admin.subscribers". */
    abstract protected function recordRoutes(): string;

    /** Singular label for messages. */
    abstract protected function recordLabel(): string;

    /** @return array<string,string> CSV column => attribute */
    abstract protected function csvColumns(): array;

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $model = $this->recordModel();
        $row = $model::findOrFail($id);
        $row->delete();
        Log::notice($this->recordLabel().' deleted', ['user_id' => $request->user()->id, 'id' => (int) $id]);

        return redirect()->route($this->recordRoutes().'.index')->with('success', ucfirst($this->recordLabel()).' deleted.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer']], ['ids.required' => 'Tick at least one row to delete.', 'ids.min' => 'Tick at least one row to delete.']);
        $model = $this->recordModel();
        $n = $model::whereIn('id', $data['ids'])->delete();
        Log::notice($this->recordLabel().' bulk deleted', ['user_id' => $request->user()->id, 'count' => $n]);

        return redirect()->route($this->recordRoutes().'.index')->with('success', $n === 1 ? ucfirst($this->recordLabel()).' deleted.' : "{$n} rows deleted.");
    }

    /** CSV of everything (or of the ticked ids): download it before deleting. Cells starting with = + - @ are neutralised for spreadsheets. */
    public function export(Request $request): StreamedResponse
    {
        $model = $this->recordModel();
        $ids = array_filter((array) $request->query('ids', []), 'is_numeric');
        $cols = $this->csvColumns();
        $name = str($this->recordLabel())->slug().'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($model, $ids, $cols) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_keys($cols));
            $model::query()->when($ids, fn ($q) => $q->whereIn('id', $ids))->orderBy('id')->chunk(500, function ($rows) use ($out, $cols) {
                foreach ($rows as $r) {
                    fputcsv($out, array_map(function ($attr) use ($r) {
                        $v = (string) ($r->{$attr} ?? '');

                        return preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;
                    }, array_values($cols)));
                }
            });
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
