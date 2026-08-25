<?php

namespace App\Providers;

use App\Auth\OraclePlainTextUserProvider;
use App\Services\ReplacementBudgetForecastService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ReplacementBudgetForecastService::class, function ($app) {
            $cfg = $app['config']['services.ai_forecast'];
            return new ReplacementBudgetForecastService(
                baseUrl: $cfg['url'],
                timeout: $cfg['timeout'],
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('oracle-plain-text', function ($app, array $config) {
            return new OraclePlainTextUserProvider($app['hash'], $config['model']);
        });
    }
}
