<?php

namespace Inmanturbo\ModuleCompiler\Providers;

use Illuminate\Support\ServiceProvider;
use Inmanturbo\ModuleCompiler\Console\Commands\BuildCommand;

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
        if ($this->app->runningInConsole()) {
            $this->commands([
                BuildCommand::class,
            ]);
        }
    }
}
