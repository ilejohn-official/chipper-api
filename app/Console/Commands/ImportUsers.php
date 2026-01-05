<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:users {url} {limit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from a given URL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
