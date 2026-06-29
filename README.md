# SnipVIP — URL Shortener SaaS on WordPress VIP

A production-grade SaaS URL shortener built on WordPress VIP Go,
demonstrating enterprise WordPress development with custom REST APIs,
Stripe billing, and a modern JavaScript frontend.

## Live features

- Shorten any URL instantly — no account required for a preview
- Sign up to save and manage your links with full history
- Click analytics — track every visit with referrer and timestamp
- Stripe-powered subscription plans with webhook sync
- Free tier with 3 links, paid plans up to unlimited

## Pricing plans

| Plan | Links | Price |
|------|-------|-------|
| Free | 3 | $0 |
| Starter | 100 | $9/mo |
| Pro | 1,000 | $29/mo |
| Enterprise | Unlimited | $99/mo |

## Tech stack

- **Platform** — WordPress VIP Go (enterprise managed hosting)
- **Backend** — PHP 8.2, custom REST API (`/wp-json/snipvip/v1/`)
- **Database** — Custom MySQL tables (`snipvip_links`, `snipvip_clicks`)
- **Payments** — Stripe Checkout + webhooks for plan management
- **Caching** — Memcached object cache, VIP page cache (Batcache)
- **Frontend** — Vanilla JS + Fetch API, no build step required
- **Standards** — WordPress VIP coding standards (PHPCS)

## Project structure
snipvip/
├── client-mu-plugins/
│   ├── snipvip-core.php              ← VIP loader
│   └── snipvip-core/
│       ├── snipvip-core.php          ← Plugin bootstrap
│       └── includes/
│           ├── class-db.php          ← Custom DB tables
│           ├── class-link-engine.php ← Slug generation + redirects
│           ├── class-plan-guard.php  ← Quota enforcement
│           ├── class-analytics.php   ← Click tracking
│           └── class-rest-api.php    ← All REST endpoints
├── plugins/
│   └── snipvip-stripe/
│       ├── snipvip-stripe.php        ← Plugin header
│       ├── class-checkout.php        ← Stripe Checkout sessions
│       └── class-webhook.php         ← Stripe webhook handler
├── themes/
│   └── snipvip-theme/
│       ├── style.css
│       ├── functions.php
│       ├── index.php                 ← Homepage
│       ├── page-dashboard.php        ← User dashboard
│       └── page-pricing.php          ← Pricing page
└── vip-config/
└── vip-config.php                ← VIP environment config

## REST API endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/snipvip/v1/shorten` | Required | Create a short link |
| `GET` | `/snipvip/v1/links` | Required | List user's links |
| `DELETE` | `/snipvip/v1/links/{id}` | Required | Delete a link |
| `GET` | `/snipvip/v1/links/{id}/stats` | Required | Click analytics |
| `GET` | `/snipvip/v1/plan` | Required | Current plan + quota |
| `POST` | `/snipvip/v1/stripe/checkout` | Required | Start Stripe checkout |

## Key WordPress VIP concepts demonstrated

- **Must-use plugins** — core logic in `client-mu-plugins`, always loaded, no activation needed
- **Custom DB tables** — `dbDelta()` for safe schema management
- **Object caching** — `wp_cache_get/set` on every redirect lookup
- **VIP-safe redirects** — `wp_redirect()` + `exit`, never header() directly
- **Prepared statements** — `$wpdb->prepare()` on every query, no raw SQL
- **REST API** — proper permission callbacks, sanitization, validation args
- **Stripe webhooks** — signature verification, idempotent event handling
- **VIP config** — secrets via environment variables, never hardcoded

## Local development

```bash
# Requires Docker + VIP CLI
npm install -g @automattic/vip

vip dev-env create --slug=snipvip
vip dev-env start --slug=snipvip

# Run coding standards check
./vendor/bin/phpcs --standard=phpcs.xml .
```

## What I learned building this

Building on WordPress VIP forced me to write better code than I normally would.
No direct file writes, no `curl` without approval, no raw SQL — every shortcut
that works on shared hosting is blocked. The result is code that is genuinely
production-safe, cacheable, and scalable to millions of requests.

---

Built by Moiz Iqbal · [github.com/moiziqbal](https://github.com/moiziqbal)# wpwip-snipvip
# wpwip-snipvip
