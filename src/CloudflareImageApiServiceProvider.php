<?php

namespace onurozdogan\CloudflareImageApi;

use Illuminate\Support\ServiceProvider;
use onurozdogan\CloudflareImageApi\Facades\CloudflareImageApi;

/**
 * Class PackageServiceProvider.
 */
class CloudflareImageApiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('cloudflare_image_api', CloudflareImageApi::class);
        $this->mergeConfigFrom(__DIR__ . './../config/cloudflareimageapi.php', 'cloudflareimageapi');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->configurePublishing();
    }

    /**
     * Configure publishing for the package.
     *
     * @return void
     */
    protected function configurePublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . './../config/cloudflare-image-api.php' => config_path('cloudflare-image-api.php')],
                'cloudflare-image-api');
        }
    }
}