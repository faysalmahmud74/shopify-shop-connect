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
| v1:      /api/v1/*   requires `Authorization: Bearer <BRIDGE_API_KEY>` (api.key)
|                      and resolves the target shop from ?shop= (api.shop)
|
*/

Route::get('/', fn () => response()->json(['status' => 'ok']))->name('api.health');

Route::prefix('v1')
    ->middleware(['api.key', 'api.shop'])
    ->name('api.v1.')
    ->group(function () {
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
    });
