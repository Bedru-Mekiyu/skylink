<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\PublicCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $showPrices = SiteSetting::showPrices();

        return view('admin.settings.index', compact('showPrices'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'show_prices' => ['nullable'],
        ]);

        $enabled = $request->has('show_prices') && $request->input('show_prices') == '1';
        SiteSetting::set('show_prices', $enabled ? '1' : '0');
        PublicCache::bust();

        Log::notice('site settings updated', [
            'user_id' => auth()->id(),
            'setting' => 'show_prices',
            'value' => $enabled,
        ]);

        return redirect()->route('admin.settings.edit')->with('success', 'Site settings saved successfully.');
    }
}
