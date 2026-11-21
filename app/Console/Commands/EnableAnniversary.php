<?php

namespace App\Console\Commands;

use App\Models\AnniversarySetting;
use App\Support\Anniversary;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Saves the anniversary as "Automatic" with the dates of config/anniversary.php (ANNIVERSARY_START / ANNIVERSARY_END,
 * Addis Ababa time), exactly what an admin would save in Admin > Anniversary Ceremony. Used once at go-live.
 * The admin can switch it off any time on that page.
 */
class EnableAnniversary extends Command
{
    protected $signature = 'anniversary:enable';

    protected $description = 'Save the anniversary as Automatic with the configured start and end (same as the admin page)';

    public function handle(): int
    {
        $tz = config('anniversary.timezone');
        $start = CarbonImmutable::parse(config('anniversary.start'), $tz);
        $end = CarbonImmutable::parse(config('anniversary.end'), $tz);
        if ($end->lte($start)) {
            $this->error('The end must be after the start.');

            return self::FAILURE;
        }
        $row = AnniversarySetting::query()->latest('id')->first() ?? new AnniversarySetting();
        $row->fill(['mode' => 'auto', 'starts_at' => $start->utc(), 'ends_at' => $end->utc(), 'admin_preview' => false])->save();
        Anniversary::forgetCache();
        $this->info('Anniversary: Automatic, '.$start->toDateTimeString().' to '.$end->toDateTimeString().' ('.$tz.'). Switch it off in Admin > Anniversary Ceremony.');

        return self::SUCCESS;
    }
}
