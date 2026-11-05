<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;

class ApiKeyList extends Command
{
    protected $signature = 'apikey:list {--shop= : Only keys for this shop}';

    protected $description = 'List API keys (the keys themselves are never shown).';

    public function handle(): int
    {
        $query = ApiKey::orderBy('id');
        if ($shop = $this->option('shop')) {
            $query->where('shop', $shop);
        }

        $rows = $query->get()->map(function (ApiKey $key) {
            $status = $key->isRevoked() ? 'revoked' : ($key->isExpired() ? 'expired' : 'active');

            return [
                $key->id,
                $key->name,
                $key->shop,
                ApiKey::KEY_PREFIX . $key->key_prefix . '_…',
                implode(',', $key->abilities),
                $status,
                $key->expires_at ? $key->expires_at->toDateString() : 'never',
                $key->last_used_at ? $key->last_used_at->diffForHumans() : 'never',
            ];
        });

        $this->table(['ID', 'Name', 'Shop', 'Key', 'Abilities', 'Status', 'Expires', 'Last used'], $rows);

        return self::SUCCESS;
    }
}
