<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\AdminAttentionService;
use App\Services\NotificationService;
use App\Services\PublicSiteDataService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
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
        $this->configureRateLimiters();

        if (
            $this->app->runningInConsole()
            && ! $this->app->runningUnitTests()
            && $this->app->environment('production')
            && (bool) config('app.safe_mode', true)
        ) {
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

        View::composer('partials.footer', function ($view): void {
            try {
                $publicSiteData = app(PublicSiteDataService::class);

                $view->with([
                    'footerContact' => $publicSiteData->contactData(),
                    'footerPrimaryLocation' => $publicSiteData->primaryLocation(),
                    'footerSocialLinks' => $publicSiteData->socialLinks(),
                ]);
            } catch (\Exception $e) {
                $view->with([
                    'footerContact' => ['phone' => null, 'email' => null, 'address' => null],
                    'footerPrimaryLocation' => null,
                    'footerSocialLinks' => [],
                ]);
            }
        });

        View::composer('partials.navbar', function ($view): void {
            $view->with('navbarUnreadNotificationCount', auth()->check()
                ? app(NotificationService::class)->getUnreadCount()
                : 0);
        });

        View::composer(['admin.layouts.app', 'admin.layout'], function ($view): void {
            if (! auth()->check() || ! auth()->user()->isAdmin()) {
                $view->with([
                    'adminAttentionCounts' => [],
                    'adminAttentionTotal' => 0,
                ]);

                return;
            }

            try {
                $adminAttentionCounts = app(AdminAttentionService::class)->counts();
            } catch (\Exception) {
                $adminAttentionCounts = [];
            }

            $view->with([
                'adminAttentionCounts' => $adminAttentionCounts,
                'adminAttentionTotal' => array_sum($adminAttentionCounts),
            ]);
        });

        if (app()->environment(['production', 'staging'])) {

            DB::listen(function ($query) {

                $dangerous = [
                    'truncate',
                    'drop',
                    'delete from users',
                ];

                foreach ($dangerous as $danger) {
                    if (str_contains(strtolower($query->sql), $danger)) {
                        abort(403, '🚨 Dangerous DB operation blocked');
                    }
                }
            });
        }

    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('phase5-register', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('phase5-password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('phase5-contact', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('phase5-support', function (Request $request) {
            return Limit::perMinute(20)->by($this->rateLimitKey($request));
        });

        RateLimiter::for('phase5-booking', function (Request $request) {
            return Limit::perMinute(30)->by($this->rateLimitKey($request));
        });

        RateLimiter::for('phase5-payment', function (Request $request) {
            return Limit::perMinute(20)->by($this->rateLimitKey($request));
        });

        RateLimiter::for('phase5-insurance', function (Request $request) {
            return Limit::perMinute(30)->by($this->rateLimitKey($request));
        });

        RateLimiter::for('phase5-admin-action', function (Request $request) {
            return Limit::perMinute(60)->by($this->rateLimitKey($request));
        });
    }

    private function rateLimitKey(Request $request): string
    {
        return (string) ($request->user()?->id ?? $request->ip());
    }
}
