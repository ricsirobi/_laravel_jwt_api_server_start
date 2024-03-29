<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

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

        
        if (App::environment('local')) {
            $options = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ];
            
            $context = stream_context_create($options);
            config(['http' => ['context' => $context]]);
        }
        Config::set('app.timezone', 'Europe/Budapest');

    }
}
