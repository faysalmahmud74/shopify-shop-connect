<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Lib\ShopifyProducts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * GET /api/v1/products?first=
     *
     * The shop is the one the API key belongs to (resolved by the `api.shop` middleware).
     */
    public function index(Request $request): JsonResponse
    {
        $session = $request->attributes->get('shopSession');

        $products = ShopifyProducts::list($session, (int) $request->query('first', 10));

        return response()->json([
            'shop' => $session->shop,
            'products' => $products,
        ]);
    }
}
