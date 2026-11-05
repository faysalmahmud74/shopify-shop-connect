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
