<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:init')]
#[Description('Initialize the application with fresh data')]
class Init extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('migrate:fresh');
        $this->call('app:seed-countries');
        $this->call('app:seed-leagues');
        $this->call('app:seed-markets');
        $this->call('app:seed-fixtures');
        $this->call('app:seed-odds');
        
        $this->info('Application initialized successfully!');
    }
}