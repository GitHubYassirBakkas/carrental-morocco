<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole() && app()->environment('local') && (bool) config('app.safe_mode', true)) {
            Event::listen(CommandStarting::class, function (CommandStarting $event): void {
                $blockedCommands = [
                    'migrate:fresh',
                    'migrate:refresh',
                    'db:wipe',
                    'db:reset',
                ];

                if (in_array($event->command, $blockedCommands, true)) {
                    throw new RuntimeException('SAFE MODE ENABLED: Dangerous database command blocked.');
                }
            });
        }

        // Share autoload settings with all views
        try {
            $autoloadSettings = Setting::getAutoloadSettings();
            View::share('settings', $autoloadSettings);
        } catch (\Exception $e) {
            // Handle case when table doesn't exist yet (during migration)
        }


        if (app()->environment(['production', 'staging'])) {

        DB::listen(function ($query) {

            $dangerous = [
                'truncate',
                'drop',
                'delete from users'
            ];

            foreach ($dangerous as $danger) {
                if (str_contains(strtolower($query->sql), $danger)) {
                    abort(403, '🚨 Dangerous DB operation blocked');
                }
            }
        });
    }

    }
}
