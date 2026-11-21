<?php

namespace App\Console\Commands;

use App\Support\TeamPlaceholder;
use Illuminate\Console\Command;

class CheckTeamPlaceholder extends Command
{
    protected $signature = 'team:check-placeholder';

    protected $description = 'Fails on the real live site while the Head of Marketing placeholder name or photo is still in use';

    public function handle(): int
    {
        if (TeamPlaceholder::blocksThisSite()) {
            $this->error('The Head of Marketing placeholder is still in use. Replace it before going live (see config/team.php).');

            return self::FAILURE;
        }
        $this->info(TeamPlaceholder::inUse()
            ? 'Placeholder in use, allowed on a test site (not production, or MAIL_STAGING_GUARD=true).'
            : 'Head of Marketing is set.');

        return self::SUCCESS;
    }
}
