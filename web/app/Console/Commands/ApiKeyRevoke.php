<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;

class ApiKeyRevoke extends Command
{
    protected $signature = 'apikey:revoke {id : The key ID shown by apikey:list}';

    protected $description = 'Revoke an API key immediately.';

    public function handle(): int
    {
        $key = ApiKey::find($this->argument('id'));
        if (!$key) {
            $this->error('No API key with that ID.');
            return self::FAILURE;
        }

        if ($key->isRevoked()) {
            $this->warn("Key #{$key->id} was already revoked.");
            return self::SUCCESS;
        }

        $key->forceFill(['revoked_at' => now()])->save();
        $this->info("Revoked key #{$key->id} \"{$key->name}\" ({$key->shop}).");

        return self::SUCCESS;
    }
}
