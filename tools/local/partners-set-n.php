<?php
// LOCAL USE ONLY: php artisan tinker --execute="require 'tools/local/partners-set-n.php';"   with env PARTNERS_N=<number> (or "restore")
use App\Models\Partner;

$n = getenv('PARTNERS_N');
Partner::where('name', 'like', 'ZZ extra%')->delete();
if ($n === 'restore') {
    Partner::query()->update(['active' => true]);
} else {
    $n = (int) $n;
    Partner::query()->update(['active' => false]);
    $ids = Partner::orderBy('sort_order')->limit($n)->pluck('id');
    Partner::whereIn('id', $ids)->update(['active' => true]);
    for ($i = 0; $i < $n - $ids->count(); $i++) {
        Partner::create(['name' => 'ZZ extra '.$i, 'alt' => 'extra '.$i, 'legacy_src' => 'assets/images/trusted/ad.png', 'logo_w' => 200, 'logo_h' => 200, 'sort_order' => 1000 + $i]);
    }
}
App\Support\PublicCache::bust();
echo Partner::where('active', true)->count(), " active\n";
