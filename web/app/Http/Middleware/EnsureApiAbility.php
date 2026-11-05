<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;

class EnsureApiAbility
{
    /**
     * Requires the authenticated API key (set by `api.key`) to hold every listed ability,
     * e.g. `->middleware('api.can:products:read')`.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$abilities
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string ...$abilities)
    {
        /** @var ApiKey|null $key */
        $key = $request->attributes->get('apiKey');

        foreach ($abilities as $ability) {
            if (!$key || !$key->can($ability)) {
                return response()->json(['error' => "Forbidden: missing ability $ability"], 403);
            }
        }

        return $next($request);
    }
}
