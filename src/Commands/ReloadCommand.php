<?php

namespace Fintech\Reload\Commands;

use Illuminate\Console\Command;

class ReloadCommand extends Command
{
    public $signature = 'reload';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
