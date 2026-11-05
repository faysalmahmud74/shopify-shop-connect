<?php

use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Loaded by RouteServiceProvider under the `api` prefix and `api` middleware group.
|
| Public:  GET /api
| v1:      /api/v1/*   api.key  authenticates `Authorization: Bearer <API key>` and rate limits per key
|                      api.shop loads the shop the key is tied to (callers cannot choose another shop)
|                      api.can  per route, requires an ability on the key
|
| Keys are managed with: php artisan apikey:create | apikey:list | apikey:revoke
|
*/

Route::get('/', fn () => response()->json(['status' => 'ok']))->name('api.health');

Route::prefix('v1')
    ->middleware(['api.key', 'api.shop'])
    ->name('api.v1.')
    ->group(function () {
        Route::get('products', [ProductController::class, 'index'])
            ->middleware('api.can:products:read')
            ->name('products.index');
    });
