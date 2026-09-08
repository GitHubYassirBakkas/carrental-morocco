<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ✅ ADD THIS LINE:
        $schedule->command('bookings:cancel-overdue')->hourly();

        // You can also add other schedules here:
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        if (app()->environment(['production', 'staging'])) {

            $this->app['events']->listen('artisan.start', function ($command) {

                $blocked = [
                    'migrate:fresh',
                    'migrate:refresh',
                    'db:wipe',
                    'db:reset',
                ];

                foreach ($blocked as $bad) {
                    if (str_contains($command, $bad)) {
                        abort(403, '🚨 This artisan command is blocked in Safe Mode');
                    }
                }
            });
        }

        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
