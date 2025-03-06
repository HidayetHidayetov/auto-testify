<?php

namespace Hidayetov\AutoTestify;

use Illuminate\Support\ServiceProvider;

class AutoTestifyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Hidayetov\AutoTestify\Commands\GenerateModelTest::class,
            ]);
        }
    }

    public function register()
    {
    }
}