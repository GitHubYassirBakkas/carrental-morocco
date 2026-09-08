# Production Deployment Checklist

This project is deployment-ready after production infrastructure and secrets are configured. Do not commit real secrets, local databases, backups, logs, or Vite development markers.

## Required Environment

`.env.example` is a local development template. Do not copy it unchanged to production.
Create a production `.env` from the host's secret manager or deployment platform
settings, and never commit it.

Application:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.example`
- `APP_KEY=base64:...`
- `SAFE_MODE=true`
- `LOG_CHANNEL=stack`
- `LOG_LEVEL=info`

Database:

- `DB_CONNECTION=mysql`
- `DB_HOST=<database-host>`
- `DB_PORT=3306`
- `DB_DATABASE=<database-name>`
- `DB_USERNAME=<database-user>`
- `DB_PASSWORD=<secret>`

Session and HTTPS:

- `SESSION_SECURE_COOKIE=true`
- `SESSION_HTTP_ONLY=true`
- `SESSION_SAME_SITE=lax`
- `SESSION_DOMAIN=` blank unless a specific cookie domain is required
- `TRUSTED_PROXIES=` blank for no reverse proxy, or comma-separated trusted proxy IPs/CIDRs

Cache, queue, and files:

- `CACHE_STORE=redis` or `database`; use a shared cache store when multiple PHP processes or servers need scheduler/cache locks
- `QUEUE_CONNECTION=database` or `redis`
- `WEBHOOK_QUEUE_CONNECTION=database` or `redis`
- `WEBHOOK_QUEUE=stripe-webhooks`
- `FILESYSTEM_DISK=local`
- `FILESYSTEM_LOCAL_SERVE=false`

Stripe live mode:

- `STRIPE_KEY=pk_live_...`
- `STRIPE_SECRET=sk_live_...`
- `STRIPE_WEBHOOK_SECRET=whsec_...`

Do not use `pk_test_`, `sk_test_`, or a test webhook secret in production.

Mail:

- `MAIL_MAILER=resend`
- `RESEND_KEY=<secret>`
- `MAIL_FROM_ADDRESS=<verified-sender@your-domain.example>`
- `MAIL_FROM_NAME="Car Rental Morocco"`

Do not regenerate `APP_KEY` after production data exists unless following a planned key-rotation process. Changing it invalidates encrypted cookies and sessions, and may affect encrypted application data.

## HTTPS And Proxies

Terminate HTTPS at the web server or load balancer and redirect HTTP to HTTPS.
If a reverse proxy or load balancer is used, configure `TRUSTED_PROXIES` with the
specific proxy IPs or CIDR ranges so Laravel trusts `X-Forwarded-Proto` and
generates secure URLs. Do not use `TRUSTED_PROXIES=*` in production; the
application middleware intentionally refuses wildcard trust in production.

Do not force HTTPS globally with application code unless the deployment topology
has been audited. Correct trusted-proxy headers are the safer default.

## Security Headers

The application emits baseline browser security headers from Laravel middleware:
`X-Content-Type-Options=nosniff`, `Referrer-Policy=strict-origin-when-cross-origin`,
`Permissions-Policy=camera=(), microphone=(), geolocation=()`, and
same-origin frame protection.

Content Security Policy is configurable and should start in report-only mode:

- `SECURITY_CSP_ENABLED=true`
- `SECURITY_CSP_REPORT_ONLY=true`

After deploying with valid HTTPS, inspect browser console/report-only violations
while testing login, registration, email verification, booking, coupons, Stripe
payment, admin pages, and private evidence routes. Tighten allowed sources only
when they are legitimate app requirements, then switch to enforcement with
`SECURITY_CSP_REPORT_ONLY=false`.

HSTS is intentionally disabled by default and is only emitted when
`SECURITY_HSTS_ENABLED=true`, `APP_ENV=production`, and Laravel recognizes the
request as HTTPS. Enable it only after HTTPS and trusted-proxy handling are stable:

- `SECURITY_HSTS_ENABLED=true`
- emitted value: `Strict-Transport-Security: max-age=31536000`

Do not enable HSTS preload yet. Do not add `includeSubDomains` until every
subdomain is confirmed HTTPS-only. HSTS can persist in browsers, so test carefully
before enabling it on the public domain.

## Build And Release Commands

Run these from the release directory:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

After caching configuration, verify non-secret production settings without
printing keys:

```bash
php artisan tinker --execute="dump([
    'env' => app()->environment(),
    'debug' => (bool) config('app.debug'),
    'url_scheme' => parse_url(config('app.url'), PHP_URL_SCHEME),
    'queue' => config('queue.default'),
    'cache' => config('cache.default'),
    'session_secure' => config('session.secure'),
    'mail' => config('mail.default'),
    'stripe_key_configured' => filled(config('services.stripe.key') ?? null),
    'resend_configured' => filled(config('services.resend.key') ?? null),
]);"
```

Use this during rollback or troubleshooting:

```bash
php artisan optimize:clear
```

`public/hot` is created by `npm run dev` and must not be present in production. `npm run build` writes static assets under `public/build` and does not require `public/hot`.

## Web Server

The document root must be:

```text
<project>/public
```

Never expose the repository root. Keep `.env`, `vendor`, `storage/app`, `tests`, backups, and database files outside the public webroot. Disable directory listing and redirect HTTP to HTTPS.

For Apache, `public/.htaccess` handles the Laravel front controller. For Nginx, configure the front controller explicitly because `.htaccess` is ignored.

The repository-root `.htaccess` file is treated as a local Apache/XAMPP safeguard only and is not part of production deployment. Production must point the web server directly at `public`; do not rely on a root `.htaccess` to protect or route requests from the repository root.

## Storage

Run once on the server:

```bash
php artisan storage:link
```

Writable paths:

- `storage/`
- `storage/app/private`
- `storage/app/public`
- `storage/app/temp`
- `storage/framework`
- `storage/logs`
- `bootstrap/cache`

Use web-server ownership/group permissions appropriate to the host. Prefer directories `775` or stricter and files `664` or stricter. Do not use world-writable `777` as a default.

Private booking inspection/damage evidence must remain under private storage and be served only through authorized Laravel routes.

Keep `FILESYSTEM_LOCAL_SERVE=false` in production so Laravel does not register anonymous `storage/{path}` routes for the private local disk. Public car/profile images are served through the `public` disk and the `public/storage` symlink.

## Queue And Scheduler

Run queue workers for the default queue and the Stripe webhook queue:

```bash
php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=90
php artisan queue:work database --queue=stripe-webhooks --sleep=1 --tries=3 --timeout=90
```

Install one scheduler cron entry:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

## Stripe Webhook

Configure the live Stripe webhook endpoint:

```text
https://your-domain.example/stripe/webhook
```

Enable the events used by the application:

- `payment_intent.amount_capturable_updated`
- `payment_intent.canceled`
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `refund.created`
- `refund.updated`
- `refund.succeeded`
- `refund.failed`

## Before GitHub Push

Run these checks before staging or pushing:

```bash
git status --short
git diff --check
git diff --cached --name-only
git ls-files
composer validate --strict
php artisan test --compact
```

Confirm no sensitive or local-only path is tracked:

```bash
git ls-files | rg "(\.env$|\.sql$|\.log$|backups/|storage/app/private|public/storage|vendor/|node_modules/)"
```

Use a dry run before broad staging:

```bash
git add --dry-run .
```

The dry run must not include `.env`, backups, SQL dumps, logs, private storage,
`public/storage`, `vendor`, `node_modules`, `public/build`, root `.htaccess`, or
local diagnostic helper files.

## Backup Process

Before deployment, take a verified database and storage backup. Do not store backup files inside the public webroot or commit them to the repository.

Recommended database backup pattern:

```bash
mysqldump --single-transaction --routines --triggers --events --default-character-set=utf8mb4 --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" "$DB_DATABASE" > "/secure/backups/carrental-$(date +%Y%m%d-%H%M%S).sql"
```

Use environment or secret-manager credentials instead of hardcoding passwords. Encrypt backups, copy them off-server, and test restores regularly.

## Legacy Evidence Dry Run

Before migrating historical booking evidence, run:

```bash
php artisan booking-evidence:migrate-private
```

Only run the execute mode after reviewing the dry-run counts and confirming a backup exists:

```bash
php artisan booking-evidence:migrate-private --execute
```
