<?php

namespace Dev\PostmanExporter;

use Illuminate\Support\ServiceProvider;
use Dev\PostmanExporter\Commands\ExportToPostman;

class PostmanExporterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ExportToPostman::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/postman-exporter.php' => config_path('postman-exporter.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/postman-exporter.php', 'postman-exporter');
    }
}
