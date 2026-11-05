<?php

declare(strict_types=1);

namespace App\Lib\Handlers;

use Illuminate\Support\Facades\Log;
use Shopify\Webhooks\Handler;
use App\Models\ApiKey;
use App\Models\Session;

class AppUninstalled implements Handler
{
    public function handle(string $topic, string $shop, array $body): void
    {
        Log::debug("App was uninstalled from $shop - removing all sessions");
        Session::where('shop', $shop)->delete();

        // Keys for an uninstalled shop must stop working.
        ApiKey::where('shop', $shop)->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }
}
