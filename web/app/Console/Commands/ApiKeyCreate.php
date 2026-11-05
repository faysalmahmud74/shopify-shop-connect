<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Shopify\Utils;

class ApiKeyCreate extends Command
{
    protected $signature = 'apikey:create
                            {shop : The shop this key may access, e.g. my-store.myshopify.com}
                            {--name= : A label for the client that will use the key}
                            {--ability=* : Abilities to grant (default: all). Available: products:read}
                            {--expires= : Expiry as a date/time (e.g. 2027-01-31) or a number of days}
                            {--force : Allow a shop that has not installed the app yet}';

    protected $description = 'Create an API key for a client, tied to one shop. The key is shown once.';

    public function handle(): int
    {
        $shop = Utils::sanitizeShopDomain($this->argument('shop'));
        if (!$shop) {
            $this->error('Invalid shop domain. Use the my-store.myshopify.com form.');
            return self::FAILURE;
        }

        $installed = Session::where('shop', $shop)->whereNotNull('access_token')->where('is_online', 0)->exists();
        if (!$installed && !$this->option('force')) {
            $this->error("$shop has not installed the app. Install it first, or pass --force.");
            return self::FAILURE;
        }

        $abilities = $this->option('ability') ?: ApiKey::ABILITIES;
        $unknown = array_diff($abilities, ApiKey::ABILITIES);
        if ($unknown) {
            $this->error('Unknown ability: ' . implode(', ', $unknown) . '. Available: ' . implode(', ', ApiKey::ABILITIES));
            return self::FAILURE;
        }

        $expiresAt = $this->parseExpiry($this->option('expires'));
        if ($expiresAt === false) {
            $this->error('Could not read --expires. Use a date such as 2027-01-31 or a number of days.');
            return self::FAILURE;
        }

        $name = $this->option('name') ?: 'Unnamed client';
        [$key, $plain] = ApiKey::issue($shop, $name, $abilities, $expiresAt);

        $this->info("Created API key #{$key->id} \"{$name}\" for {$shop}");
        $this->line('Abilities: ' . implode(', ', $abilities));
        $this->line('Expires:   ' . ($expiresAt ? $expiresAt->toDateTimeString() : 'never'));
        $this->newLine();
        $this->warn('Copy this key now. It is stored only as a hash and cannot be shown again:');
        $this->line($plain);

        return self::SUCCESS;
    }

    /** @return Carbon|null|false null = no expiry, false = unreadable */
    private function parseExpiry(?string $value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return now()->addDays((int) $value);
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return false;
        }
    }
}
