<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate limit
    |--------------------------------------------------------------------------
    |
    | Maximum requests per minute for each API key (see App\Http\Middleware\AuthenticateApiKey).
    | API keys themselves live in the `api_keys` table; manage them with the apikey:* artisan commands.
    |
    */
    'rate_limit_per_minute' => (int) env('BRIDGE_RATE_LIMIT', 60),

];
