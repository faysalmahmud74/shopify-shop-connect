<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AuthenticateApiKey
{
    /**
     * Authenticates the caller by bearer API key and rate limits per key.
     *
     * On success the key model is available as the `apiKey` request attribute. Missing, malformed, unknown,
     * revoked and expired keys all get the same 401 so callers cannot probe which case applied.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $key = ApiKey::findByPlain((string) $request->bearerToken());

        if (!$key || !$key->isUsable()) {
            return response()->json(['error' => 'Unauthorized'], 401, ['WWW-Authenticate' => 'Bearer']);
        }

        $limiterKey = 'bridge:' . $key->id;
        $limit = (int) config('bridge.rate_limit_per_minute');

        if (RateLimiter::tooManyAttempts($limiterKey, $limit)) {
            return response()->json(['error' => 'Too many requests'], 429, [
                'Retry-After' => RateLimiter::availableIn($limiterKey),
            ]);
        }
        RateLimiter::hit($limiterKey, 60);

        // Avoid a write on every request.
        if (!$key->last_used_at || $key->last_used_at->diffInSeconds(now()) > 60) {
            $key->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        $request->attributes->set('apiKey', $key);

        return $next($request);
    }
}
