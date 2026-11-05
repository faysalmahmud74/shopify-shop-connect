<?php

namespace App\Http\Middleware;

use App\Models\Session;
use Closure;
use Illuminate\Http\Request;
use Shopify\Utils;

class ResolveShop
{
    /**
     * Resolves which installed shop the request is for and stores its offline session on the request
     * (attribute `shopSession`).
     *
     * The shop comes from the `shop` query parameter. It may be omitted only when exactly one shop has
     * installed the app.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $installed = Session::whereNotNull('access_token')->where('is_online', 0);

        if ($request->query('shop')) {
            $shop = Utils::sanitizeShopDomain($request->query('shop'));
            $session = $shop ? $installed->where('shop', $shop)->first() : null;

            if (!$session) {
                return response()->json(['error' => 'Shop not installed'], 404);
            }
        } else {
            $count = $installed->count();
            if ($count !== 1) {
                return response()->json(['error' => "Pass ?shop=<domain>; $count shops installed"], 422);
            }
            $session = $installed->first();
        }

        $request->attributes->set('shopSession', $session);

        return $next($request);
    }
}
