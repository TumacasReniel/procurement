<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use App\Listeners\LoginFailed;
use App\Listeners\LoginSuccessful;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

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
        Schema::defaultStringLength(191);
        Event::listen(
            LoginSuccessful::class,
            LoginFailed::class
        );

        // Renders user-authored rich text (BAC bodies, NOA/NTP remarks, item
        // descriptions) through an allowlist sanitizer. Print templates must use
        // this instead of {!! !!} — the content is attacker-controlled.
        Blade::directive('richtext', function ($expression) {
            return "<?php echo \App\Support\RichText::sanitize({$expression}); ?>";
        });

        if (App::environment('production')) {
            Artisan::command('migrate:fresh', function () {
                $this->error('-');
            });

            Artisan::command('db:wipe', function () {
                $this->error('-');
            });

            Artisan::command('migrate:fresh --seed', function () {
                $this->error('-');
            });
        }
    }
}
