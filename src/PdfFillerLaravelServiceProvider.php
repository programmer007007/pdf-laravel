<?php

namespace Andrew\PdfFillerLaravel;

use Illuminate\Support\ServiceProvider;

class PdfFillerLaravelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->publishes([
            __DIR__ . '/../config/pdffiller.php' => config_path('pdffiller.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('pdf-filler-laravel', function ($app) {
            return new PdfFiller();
        });
    }
}
