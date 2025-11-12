<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mistral API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for the Mistral API. The API key
    | is required to authenticate requests to the Mistral service.
    |
    */

    'api_key' => env('MISTRAL_API_KEY', ''),

    'url' => env('MISTRAL_URL', 'https://api.mistral.ai'),

    'model' => env('MISTRAL_MODEL', 'mistral-medium'),

    'timeout' => env('MISTRAL_TIMEOUT', 30),

    'max_retries' => env('MISTRAL_MAX_RETRIES', 3),

    'retry_delay' => env('MISTRAL_RETRY_DELAY', 1000),

];
