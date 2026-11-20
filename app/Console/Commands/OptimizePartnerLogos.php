<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Support\PartnerLogoProcessor;
use Illuminate\Console\Command;
use Throwable;

/**
 * One-time (safe to repeat): gives the imported partner logos (which still point at the big static files,
 * e.g. addis.png at 1 MB) small WebP + PNG/JPEG web copies, at most 400 px wide. The static files stay in place.
 */
class OptimizePartnerLogos extends Command
{
    protected $signature = 'partners:optimize-legacy';
    protected $description = 'Create small web copies for partner logos imported from the old static list';

    public function handle(): int
    {
        $done = 0;
        foreach (Partner::whereNull('logo_path')->whereNotNull('legacy_src')->orderBy('id')->get() as $p) {
            $file = public_path($p->legacy_src);
            if (! is_file($file)) {
                $this->warn("missing file for {$p->name}: {$p->legacy_src}");
                continue;
            }
            try {
                $p->update(PartnerLogoProcessor::fromPath($file));
                $done++;
                $this->line("ok  {$p->name}");
            } catch (Throwable $e) {
                $this->warn("skip {$p->name}: {$e->getMessage()}");
            }
        }
        $this->info("$done logo(s) optimised.");

        return self::SUCCESS;
    }
}
