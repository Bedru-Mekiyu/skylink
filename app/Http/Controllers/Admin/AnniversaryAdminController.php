<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnniversarySetting;
use App\Support\Anniversary;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Admin > Anniversary Ceremony: switch the 5-year ceremony on, off or automatic, without a deploy. */
class AnniversaryAdminController extends Controller
{
    private const FORM_FORMAT = 'Y-m-d\TH:i'; // <input type="datetime-local">

    public function edit()
    {
        $s = Anniversary::settings();

        return view('admin.anniversary.edit', [
            'mode' => $s['mode'],
            'start' => $s['start']->format(self::FORM_FORMAT),
            'end' => $s['end']->format(self::FORM_FORMAT),
            'adminPreview' => $s['admin_preview'],
            'timezone' => $s['timezone'],
            'status' => self::statusLine($s),
            'saved' => $s['saved'],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tz = config('anniversary.timezone');

        $data = $request->validate([
            'mode' => ['required', Rule::in(AnniversarySetting::MODES)],
            'starts_at' => ['required', 'date_format:'.self::FORM_FORMAT],
            'ends_at' => ['required', 'date_format:'.self::FORM_FORMAT],
            'admin_preview' => ['nullable', 'boolean'],
        ]);

        $start = CarbonImmutable::createFromFormat(self::FORM_FORMAT, $data['starts_at'], $tz);
        $end = CarbonImmutable::createFromFormat(self::FORM_FORMAT, $data['ends_at'], $tz);
        if ($end->lte($start)) {
            return back()->withInput()->withErrors(['ends_at' => 'The end must be after the start.']);
        }

        // One settings row: update the newest one (the one Anniversary::settings() reads), or create it.
        // (Never updateOrCreate(['id' => 1]): after the row was deleted once, id 1 never exists again, so every
        // save would add a new row while the page kept reading an older one.)
        $row = AnniversarySetting::query()->latest('id')->first() ?? new AnniversarySetting();
        $row->fill([
            'mode' => $data['mode'],
            'starts_at' => $start->utc(),
            'ends_at' => $end->utc(),
            'admin_preview' => $request->boolean('admin_preview'),
        ])->save();
        Anniversary::forgetCache(); // so the change shows on the very next page view

        return redirect()->route('admin.anniversary.edit')->with('success', 'Anniversary settings saved.');
    }

    /** One plain sentence about what visitors see right now. */
    private static function statusLine(array $s): string
    {
        $fmt = fn (CarbonImmutable $d) => $d->format('M j, Y \a\t H:i').' (Addis Ababa time)';
        $now = CarbonImmutable::now($s['timezone']);
        $live = Anniversary::liveForVisitors($now);

        if ($live && ! $s['saved'] && Anniversary::isPreviewForced()) {
            $line = 'Currently LIVE for visitors because ANNIVERSARY_FORCE is on in this local/staging setup (production ignores it). Save a mode here and that mode wins.';
        } elseif ($live) {
            $line = 'Currently LIVE for visitors until '.$fmt($s['end']).'.';
        } elseif (! $s['saved']) {
            $line = 'Currently HIDDEN for visitors (Off). Choose Automatic or On now to enable.';
        } elseif ($s['mode'] === 'off') {
            $line = 'Currently HIDDEN for visitors. It is switched off.';
        } elseif ($now->gte($s['end'])) {
            $line = 'Currently HIDDEN for visitors. The end time ('.$fmt($s['end']).') has passed.';
        } elseif ($s['mode'] === 'auto') {
            $line = 'Currently HIDDEN for visitors. Will appear automatically on '.$fmt($s['start']).'.';
        } else {
            $line = 'Currently HIDDEN for visitors.';
        }

        if (! $s['admin_preview']) {
            return $line;
        }

        // Admin preview is ticked: say what the logged-in admin sees compared with visitors.
        return $line.($live
            ? ' Admin preview is on, but it changes nothing while the ceremony is live for everyone.'
            : ' Visitors: HIDDEN. You: seeing preview (a small "Admin preview" label shows on the site for you only).');
    }
}
