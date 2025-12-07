<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ctrip API Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for the Ctrip API.
    |
    */

    'account_id' => env('CTRIP_ACCOUNT_ID'),

    'sign_key' => env('CTRIP_SIGN_KEY'),

    'aes_key' => env('CTRIP_AES_KEY'),

    'aes_iv' => env('CTRIP_AES_IV'),

    // Sandbox URL
    'url' => env('CTRIP_URL', 'https://ttdopen.ctrip.com/api/'),

    // Production URL
    'url_production' => env('CTRIP_URL_PRODUCTION', 'https://sopenservice.ctrip.com/openservice/'),

];
