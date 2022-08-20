<?php

namespace Alikhedmati\Kyc;

use Illuminate\Support\ServiceProvider;

class KycServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'../config/kyc.php',
            'kyc'
        );
    }

    public function boot()
    {
        if (function_exists('config_path')) {

            $this->publishes([
                __DIR__ . '../config/kyc.php' => config_path('kyc.php')
            ], 'config');

        }

        $this->loadTranslationsFrom(__DIR__.'../lang', 'kyc');
    }
}