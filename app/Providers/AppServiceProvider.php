<?php

namespace Inmanturbo\ModuleCompiler\Providers;

use Illuminate\Support\ServiceProvider;
use Inmanturbo\ModuleCompiler\Console\Commands\AddProvidersCommand;
use Inmanturbo\ModuleCompiler\Console\Commands\AppendCommand;
use Inmanturbo\ModuleCompiler\Console\Commands\BuildCommand;
use Inmanturbo\ModuleCompiler\Console\Commands\CombineCommand;

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
                CombineCommand::class,
                AppendCommand::class,
                AddProvidersCommand::class,
            ]);
        }
    }
}
