<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fliggy API Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for the Fliggy API.
    |
    */

    'distributor_id' => env('FLIGGY_DISTRIBUTOR_ID'),

    'private_key' => env('FLIGGY_PRIVATE_KEY'),

    'product_webhook_url' => env('FLIGGY_PRODUCT_WEBHOOK_URL'),

    'order_webhook_url' => env('FLIGGY_ORDER_WEBHOOK_URL'),

    'api_base_uri' => env('FLIGGY_API_BASE_URI', 'https://api.alitrip.alibaba.com'),

    'api_base_uri_pre' => env('FLIGGY_API_BASE_URI_PRE', 'https://pre-api.alitrip.alibaba.com'),

];
