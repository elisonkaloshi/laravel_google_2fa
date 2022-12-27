<?php
namespace Elison\GoogleAuthenticator;

use Illuminate\Support\ServiceProvider;

class GoogleAuthenticatorProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    public function register()
    {
        //
    }
}
