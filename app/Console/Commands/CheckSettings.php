<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class CheckSettings extends Command
{
    protected $signature = 'settings:check';

    protected $description = 'Check settings in database';

    public function handle()
    {
        $this->info('Total settings: '.Setting::count());
        $this->info('Groups: '.Setting::select('group')->distinct()->pluck('group')->implode(', '));
        $this->info('Refund settings count: '.Setting::where('group', 'refund')->count());
        $this->info('Refund settings: '.Setting::where('group', 'refund')->pluck('key')->implode(', '));

        $this->newLine();
        $this->info('All settings by group:');

        $groups = Setting::select('group')->distinct()->pluck('group');
        foreach ($groups as $group) {
            $this->info("  [{$group}] ".Setting::where('group', $group)->count().' settings');
        }

        return Command::SUCCESS;
    }
}
