<?php

namespace Mariojgt\Witchcraft\Commands;

use File;
use Artisan;
use Illuminate\Console\Command;
use Mariojgt\Witchcraft\Controllers\MediaFolderController;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:witchcraft';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This Command will install this pacakge';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Copy the need file to make the onix pacakge to run
        Artisan::call('vendor:publish', [
            '--provider' => 'Mariojgt\Witchcraft\WitchcraftProvider',
            '--force'    => true
        ]);

        // Migrate
        Artisan::call('migrate');

        // Return a message in the console
        $this->newLine();
        $this->info('The command was successful!');
    }
}
