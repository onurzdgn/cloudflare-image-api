<?php

namespace onurozdogan\CloudflareImageApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class ResponseBuilder.
 */
class CloudflareImageApi extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'cloudflare-image-api';
    }
}
