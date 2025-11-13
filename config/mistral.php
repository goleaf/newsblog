<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mistral AI API Key
    |--------------------------------------------------------------------------
    |
    | Your Mistral AI API key for authentication. You can obtain this from
    | your Mistral AI dashboard at https://console.mistral.ai/
    |
    */

    'api_key' => env('MISTRAL_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Mistral AI API URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Mistral AI API. This should not need to be changed
    | unless you are using a custom endpoint.
    |
    */

    'api_url' => env('MISTRAL_API_URL', 'https://api.mistral.ai'),

    /*
    |--------------------------------------------------------------------------
    | Mistral AI Model
    |--------------------------------------------------------------------------
    |
    | The Mistral AI model to use for content generation. Available models
    | include: mistral-small, mistral-medium, mistral-large
    |
    */

    'model' => env('MISTRAL_MODEL', 'mistral-medium'),

    /*
    |--------------------------------------------------------------------------
    | API Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum time (in seconds) to wait for a response from the Mistral
    | AI API before timing out.
    |
    */

    'timeout' => env('MISTRAL_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Maximum Retries
    |--------------------------------------------------------------------------
    |
    | The maximum number of times to retry a failed API request before giving
    | up. Uses exponential backoff between retries.
    |
    */

    'max_retries' => env('MISTRAL_MAX_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Retry Delay
    |--------------------------------------------------------------------------
    |
    | The initial delay (in milliseconds) before retrying a failed API request.
    | This delay increases exponentially with each retry attempt.
    |
    */

    'retry_delay' => env('MISTRAL_RETRY_DELAY', 1000),

];
