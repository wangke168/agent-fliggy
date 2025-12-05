<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hengdian API Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials and endpoints for the
    | Hengdian World Studios API.
    |
    */

    'url' => env('HENGDIAN_URL', 'http://testhotel.hengdianworld.com/Interface/hotel_order.aspx'),

    'url_production' => env('HENGDIAN_URL_PRODUCTION', 'https://e.hengdianworld.com/Interface/hotel_order.aspx'),

    'username' => env('HENGDIAN_USERNAME', 'mpbxczl_common'),

    'password' => env('HENGDIAN_PASSWORD', 'mpbxczl20250820!@#$'),

];
