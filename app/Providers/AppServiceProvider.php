<?php

namespace App\Providers;

use App\Http\Middleware\ProtectPublicForm;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Support\StagingMailGuard;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
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
        // Public pages keep their data in a short cache; any change to this content rebuilds it (see App\Support\PublicCache).
        foreach ([\App\Models\Product::class, \App\Models\Service::class, \App\Models\ServiceCategory::class, \App\Models\Portfolio::class, \App\Models\News::class, \App\Models\NewsCategory::class, \App\Models\Video::class, \App\Models\SiteSetting::class] as $model) {
            foreach (['saved', 'deleted'] as $event) {
                $model::$event(fn () => \App\Support\PublicCache::bust());
            }
        }

        // Blade price visibility directives
        \Illuminate\Support\Facades\Blade::directive('price', function ($expression) {
            return "<?php echo e(\App\Support\PriceVisibility::render($expression) ?? ''); ?>";
        });
        \Illuminate\Support\Facades\Blade::if('priceVisible', function ($item = null) {
            return \App\Support\PriceVisibility::shouldShow($item);
        });
        // Test site only (MAIL_STAGING_GUARD=true): real customers in the copied database never get test emails.
        Event::listen(MessageSending::class, [StagingMailGuard::class, 'handle']);

        // Homepage video uploads: 10 per hour per admin (large files, optional ffmpeg work).
        RateLimiter::for('hero-upload', fn (Request $request) => Limit::perHour(10)->by('hero-upload:'.($request->user()?->id ?? $request->ip())));

        // Partner logo / gallery uploads: 30 per hour per admin.
        RateLimiter::for('partner-upload', fn (Request $request) => Limit::perHour(30)->by('partner-upload:'.($request->user()?->id ?? $request->ip())));

        // Gallery bulk uploads send one photo per request: 120 per hour per admin.
        RateLimiter::for('gallery-upload', fn (Request $request) => Limit::perHour(120)->by('gallery-upload:'.($request->user()?->id ?? $request->ip())));

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
