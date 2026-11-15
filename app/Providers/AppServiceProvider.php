<?php

namespace App\Providers;

use App\Http\Middleware\ProtectPublicForm;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Public forms (contact, chat, quote, subscribe): per visitor (IP),
        // 5 submissions per minute and 20 per hour, with a friendly message.
        RateLimiter::for('public-forms', function (Request $request) {
            $visitor = $request->ip();
            $tooMany = fn (Request $request) => ProtectPublicForm::reject(
                $request,
                'You have sent several requests in a short time. Please wait a few minutes and try again.',
                429
            );

            return [
                Limit::perMinute(5)->by('public-forms:minute:'.$visitor)->response($tooMany),
                Limit::perHour(20)->by('public-forms:hour:'.$visitor)->response($tooMany),
            ];
        });
    }
}
