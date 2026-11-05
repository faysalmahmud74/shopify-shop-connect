<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateApiKey
{
    /**
     * Rejects the request unless it carries the configured bearer API key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) config('bridge.api_key');
        $given = (string) $request->bearerToken();

        if ($expected === '' || !hash_equals($expected, $given)) {
            return response()->json(['error' => 'Unauthorized'], 401, ['WWW-Authenticate' => 'Bearer']);
        }

        return $next($request);
    }
}
