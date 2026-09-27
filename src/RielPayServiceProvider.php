<?php

namespace RielPay\Laravel;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use RielPay\Laravel\Http\WebhookController;
use RielPay\Laravel\View\KhqrCard;

class RielPayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/rielpay.php', 'rielpay');

        $this->app->singleton(RielPayClient::class, fn ($app) => new RielPayClient(
            $app->make(HttpFactory::class),
            config('rielpay.api_key'),
            config('rielpay.base_url', 'https://rielpays.com'),
            (int) config('rielpay.timeout', 30),
            (int) config('rielpay.retries', 2),
        ));
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/rielpay.php' => config_path('rielpay.php')], 'rielpay-config');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'rielpay');
        $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/rielpay')], 'rielpay-views');
        Blade::component('rielpay-khqr', KhqrCard::class);

        if ($this->app->runningInConsole()) {
            $this->commands([Console\InstallCommand::class, Console\CheckCommand::class]);
        }

        // Webhook endpoint. Registered outside the "web" group, so no CSRF token is needed.
        if ($path = config('rielpay.webhook_path')) {
            Route::post($path, WebhookController::class)->name('rielpay.webhook');
        }
    }
}
