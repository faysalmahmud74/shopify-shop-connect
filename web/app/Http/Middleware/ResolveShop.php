<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\Session;
use Closure;
use Illuminate\Http\Request;

class ResolveShop
{
    /**
     * Loads the offline session of the shop the API key belongs to and stores it on the request
     * (attribute `shopSession`).
     *
     * The shop is decided by the key alone; callers cannot choose another shop. Must run after `api.key`.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var ApiKey $key */
        $key = $request->attributes->get('apiKey');

        $session = Session::where('shop', $key->shop)
            ->whereNotNull('access_token')
            ->where('is_online', 0)
            ->first();

        if (!$session) {
            return response()->json(['error' => 'Shop not installed'], 403);
        }

        $request->attributes->set('shopSession', $session);

        return $next($request);
    }
}
