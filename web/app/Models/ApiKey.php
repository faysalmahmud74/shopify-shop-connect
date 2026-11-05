<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * A third-party client's credential for the bridge API, tied to a single shop.
 *
 * Key format: sk_live_<8 hex prefix>_<48 hex secret>  (192 bits of entropy in the secret part).
 * Only a SHA-256 hash of the full key is stored; the plain key is returned once by issue().
 */
class ApiKey extends Model
{
    public const KEY_PREFIX = 'sk_live_';
    private const KEY_PATTERN = '/^sk_live_([a-f0-9]{8})_([a-f0-9]{48})$/';

    /** Abilities a key can be granted. */
    public const ABILITIES = ['products:read'];

    protected $guarded = [];

    protected $casts = [
        'abilities' => 'array',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = ['key_hash'];

    /**
     * Creates a key for a shop.
     *
     * @param  string[]  $abilities
     * @return array{0: ApiKey, 1: string}  The stored model and the plain key (shown once, never recoverable).
     */
    public static function issue(string $shop, string $name, array $abilities, ?Carbon $expiresAt = null): array
    {
        do {
            $prefix = bin2hex(random_bytes(4));
        } while (self::where('key_prefix', $prefix)->exists());

        $plain = self::KEY_PREFIX . $prefix . '_' . bin2hex(random_bytes(24));

        $key = self::create([
            'name' => $name,
            'shop' => $shop,
            'key_prefix' => $prefix,
            'key_hash' => self::hash($plain),
            'abilities' => array_values($abilities),
            'expires_at' => $expiresAt,
        ]);

        return [$key, $plain];
    }

    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /**
     * Finds the key row for a plain key, or null if the format or hash does not match.
     */
    public static function findByPlain(string $plain): ?self
    {
        if (!preg_match(self::KEY_PATTERN, $plain, $matches)) {
            return null;
        }

        $key = self::where('key_prefix', $matches[1])->first();

        return $key && hash_equals($key->key_hash, self::hash($plain)) ? $key : null;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return !$this->isRevoked() && !$this->isExpired();
    }

    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? [], true);
    }
}
