<?php

namespace Tegos\LaravelTelescopeFlusher;

use Illuminate\Support\ServiceProvider;
use Tegos\LaravelTelescopeFlusher\Console\Commands\TelescopeFlushCommand;

class LaravelTelescopeFlusherServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TelescopeFlushCommand::class
            ]);
        }
    }

    public function register(): void
    {

    }
}
