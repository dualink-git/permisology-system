<?php

namespace PermisologySystem\PermisologySystem\Commands;

use Illuminate\Console\Command;

class PermisologySystemCommand extends Command
{
    public $signature = 'permisology-system';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
