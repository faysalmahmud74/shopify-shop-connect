<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bridge API key
    |--------------------------------------------------------------------------
    |
    | Static bearer key accepted by the `api.key` middleware. This is a test
    | shortcut; replace it with per-client keys (tied to a shop) before
    | exposing the API to real consumers.
    |
    */
    'api_key' => env('BRIDGE_API_KEY'),

];
