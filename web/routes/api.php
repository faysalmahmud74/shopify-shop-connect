<?php

use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Shopify\Clients\Graphql;
use Shopify\Utils;

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});

/*
 * Test bridge endpoint:  GET /api/v1/products?shop=my-store.myshopify.com&first=10
 * Header:                Authorization: Bearer <BRIDGE_API_KEY from .env>
 *
 * `shop` may be omitted when exactly one shop has installed the app.
 */
Route::get('/v1/products', function (Request $request) {
    // 1. Authenticate the caller with the static test key.
    $expected = (string) env('BRIDGE_API_KEY', '');
    $given = (string) $request->bearerToken();
    if ($expected === '' || !hash_equals($expected, $given)) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // 2. Resolve which installed shop to talk to.
    $shops = Session::whereNotNull('access_token')->where('is_online', 0)->get();
    if ($request->query('shop')) {
        $shopDomain = Utils::sanitizeShopDomain($request->query('shop'));
        $session = $shops->firstWhere('shop', $shopDomain);
    } elseif ($shops->count() === 1) {
        $session = $shops->first();
    } else {
        return response()->json(['error' => 'Pass ?shop=<domain>; ' . $shops->count() . ' shops installed'], 422);
    }
    if (!$session) {
        return response()->json(['error' => 'Shop not installed'], 404);
    }

    // 3. Call Shopify's Admin GraphQL API with that shop's offline token.
    $first = max(1, min(50, (int) $request->query('first', 10)));
    $client = new Graphql($session->shop, $session->access_token);
    $response = $client->query([
        'query' => <<<'GQL'
        query ($first: Int!) {
            products(first: $first) {
                nodes { id title handle status totalInventory }
            }
        }
        GQL,
        'variables' => ['first' => $first],
    ]);

    $body = $response->getDecodedBody();
    if ($response->getStatusCode() !== 200 || isset($body['errors'])) {
        return response()->json(['error' => 'Shopify request failed', 'details' => $body['errors'] ?? null], 502);
    }

    return response()->json(['shop' => $session->shop, 'products' => $body['data']['products']['nodes']]);
});
