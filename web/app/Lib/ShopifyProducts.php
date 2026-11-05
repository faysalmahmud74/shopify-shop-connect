<?php

declare(strict_types=1);

namespace App\Lib;

use App\Exceptions\ShopifyApiException;
use Shopify\Auth\Session;
use Shopify\Clients\Graphql;

/**
 * Reads products from a shop through the Admin GraphQL API.
 */
class ShopifyProducts
{
    public const MAX_PAGE_SIZE = 50;

    private const LIST_QUERY = <<<'GQL'
    query ($first: Int!) {
        products(first: $first) {
            nodes { id title handle status totalInventory }
        }
    }
    GQL;

    /**
     * @param  \App\Models\Session|Session  $session  Stored offline session (needs `shop` and `access_token`).
     * @return array<int, array<string, mixed>>
     * @throws ShopifyApiException
     */
    public static function list($session, int $first): array
    {
        $first = max(1, min(self::MAX_PAGE_SIZE, $first));

        $client = new Graphql($session->shop, $session->access_token);
        $response = $client->query([
            'query' => self::LIST_QUERY,
            'variables' => ['first' => $first],
        ]);

        $body = $response->getDecodedBody();
        if ($response->getStatusCode() !== 200 || isset($body['errors'])) {
            throw new ShopifyApiException('Shopify request failed', $body['errors'] ?? null);
        }

        return $body['data']['products']['nodes'];
    }
}
