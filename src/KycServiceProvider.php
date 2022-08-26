<?php

namespace Alikhedmati\Kyc;

use Alikhedmati\Kyc\Contracts\Factory;
use Illuminate\Support\ServiceProvider;

class KycServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/kyc.php',
            'kyc'
        );

        $this->app->bind(Factory::class, fn($app) => new Kyc($app));
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/kyc.php' => config_path('kyc.php')
        ], 'config');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'kyc');
    }

    /**
     * @return string[]
     */

    public function provides(): array
    {
        return [Factory::class];
    }
}