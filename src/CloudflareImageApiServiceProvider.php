<?php

namespace onurozdogan\CloudflareImageApi;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

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
        AliasLoader::getInstance()->alias('CloudflareImageApi', \onurozdogan\CloudflareImageApi\Facades\CloudflareImageApi::class);
    }

    /**
     * Configure publishing for the package.
     *
     * @return void
     */
    protected function configurePublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . './../config/cloudflareimageapi.php' => config_path('cloudflareimageapi.php')],
                'cloudflareimageapi');
        }
    }
}