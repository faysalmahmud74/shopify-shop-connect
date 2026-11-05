# Shopify API-only app (PHP / Laravel)

Built from [Shopify/shopify-app-template-php](https://github.com/Shopify/shopify-app-template-php) with the React
frontend removed. It is a **non-embedded** app: Laravel handles OAuth, webhooks and JSON endpoints only.

## Endpoints

| Method | Path                  | Auth            | Purpose                                   |
|--------|-----------------------|-----------------|-------------------------------------------|
| GET    | `/`, `/api`           | none            | health check                              |
| GET    | `/api/auth?shop=`     | none            | starts OAuth install                      |
| GET    | `/api/auth/callback`  | OAuth           | finishes install, registers webhooks      |
| GET    | `/api/products/count` | `shopify.auth`  | example Admin REST call                   |
| POST   | `/api/products`       | `shopify.auth`  | example: creates 5 products               |
| POST   | `/api/webhooks`       | HMAC            | webhook receiver (uninstall + privacy)    |

Unauthenticated calls to `shopify.auth` routes return `401` with the `X-Shopify-API-Request-Failure-Reauthorize-Url`
header pointing at `/api/auth`.

## Setup

```sh
cd web
composer install
cp .env.example .env        # then set DB_DATABASE to an absolute path (quote it if it has spaces, use /)
php artisan key:generate
touch storage/db.sqlite && php artisan migrate
```

Link to a Shopify app and run with a tunnel (sets `SHOPIFY_API_KEY`, `SHOPIFY_API_SECRET`, `SCOPES`, `HOST`):

```sh
npm install
npm run shopify app config link   # fills client_id / URLs in shopify.app.toml
npm run dev
```

## Changes from the template

- Removed `web/frontend`, the `.gitmodules` entry, the `composer build` frontend symlinks and the Dockerfile frontend build.
- `Context::initialize(..., isEmbeddedApp: false)` and `embedded = false` in `shopify.app.toml`.
- Removed the embedded-UI fallback route; OAuth callback now redirects to `https://{shop}/admin/apps`.
- `AccessControlHeaders` always returns the response (the template returned `null` when not embedded).
- `EnsureShopifySession` treats a missing session cookie as "not authenticated" instead of throwing a 500.
- Declared webhooks (uninstall + privacy) in `shopify.app.toml`.

## Notes

- The template pins Laravel 8 and `shopify/shopify-api` v6 (both abandoned/EOL); upgrade before production.
- Scopes live in `shopify.app.toml` (`write_products` by default).

## API keys (`/api/v1/*`)

Each client gets its own key, tied to one shop, stored only as a SHA-256 hash in the `api_keys` table. The shop is
decided by the key, so callers cannot read another shop. Keys have abilities (`products:read`), an optional expiry and
can be revoked; requests are rate limited per key (`BRIDGE_RATE_LIMIT`, default 60/min). Revoking happens automatically
when the shop uninstalls the app.

```sh
php artisan apikey:create my-store.myshopify.com --name="Acme ERP" --expires=90   # prints the key once
php artisan apikey:list
php artisan apikey:revoke 3
```

```sh
curl -H "Authorization: Bearer sk_live_xxxxxxxx_..." "https://<host>/api/v1/products?first=5"
```

Responses: `401` bad/expired/revoked key, `403` missing ability or shop uninstalled, `429` rate limited.
Docs: `/docs` (Swagger UI) and `/openapi.yaml`.
