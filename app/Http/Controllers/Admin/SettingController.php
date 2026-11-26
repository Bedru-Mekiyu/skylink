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
        $taxNote = SiteSetting::taxNote();

        return view('admin.settings.index', [
            'showPrices' => $showPrices,
            'taxNote' => $taxNote,
            'chapaEnabled' => SiteSetting::get('chapa_enabled') === '1',
            // Only whether each server setting is filled in, never its value.
            'chapaServer' => [
                'CHAPA_ENABLED' => (bool) config('services.chapa.enabled'),
                'CHAPA_SECRET_KEY' => filled(config('services.chapa.secret_key')),
                'CHAPA_WEBHOOK_SECRET' => filled(config('services.chapa.webhook_secret')),
            ],
            'chapaTestMode' => app(\App\Services\ChapaService::class)->isTestMode(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'show_prices' => ['nullable'],
            'tax_note' => ['nullable', 'string', 'max:255'],
            'chapa_enabled' => ['nullable'],
        ]);

        $enabled = $request->has('show_prices') && $request->input('show_prices') == '1';
        SiteSetting::set('show_prices', $enabled ? '1' : '0');
        SiteSetting::set('tax_note', trim((string) $request->input('tax_note', '')));

        // Chapa Payment Gateway Settings
        $chapaActive = $request->has('chapa_enabled') && $request->input('chapa_enabled') == '1';
        SiteSetting::set('chapa_enabled', $chapaActive ? '1' : '0');
        \App\Models\AdminAuditLog::record('settings.update', 'SiteSetting', null, ['show_prices' => $enabled, 'chapa_enabled' => $chapaActive]);

        PublicCache::bust();

        Log::notice('site settings updated', [
            'user_id' => auth()->id(),
            'setting' => 'site_and_payment_settings',
            'show_prices' => $enabled,
            'chapa_enabled' => $chapaActive,
        ]);

        return redirect()->route('admin.settings.edit')->with('success', 'Site and Payment Gateway settings saved successfully.');
    }
}
