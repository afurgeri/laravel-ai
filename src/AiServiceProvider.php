<?php

namespace Modules\Ai;

use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-ai.php', 'laravel-ai');
        $this->app->singleton(ToolRegistry::class);
        $this->app->singleton(Ai::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([__DIR__.'/../config/laravel-ai.php' => config_path('laravel-ai.php')], 'laravel-ai-config');
    }
}
