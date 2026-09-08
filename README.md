# CarRental Morocco

CarRental Morocco is a full-stack Laravel car-rental platform built around a Moroccan rental workflow: public vehicle discovery, customer booking, driver verification, payment/deposit handling, rental lifecycle management, inspections, invoices, refunds, and an agency admin dashboard.

## Screenshots / Demo

Live demo: Coming soon

Screenshots can be added here after the project is deployed or captured from a polished local run.

## Key Features

- Customer registration, login, email verification, profile management, password updates, account deletion safeguards, and wishlist support.
- Multilingual public and customer UI for English, French, and Arabic, including RTL layout support for Arabic.
- Dynamic vehicle catalog with database-backed cars, locations, available brands, featured cars, testimonials, and public statistics.
- Vehicle search and filtering by search term, location, brand, type, transmission, price range, and availability dates.
- Booking flow with insurance selection, preview, final availability recheck, booking-date validation, max-advance validation, database locking, and invoice creation.
- Driver verification workflow with private document uploads, admin review, approval/rejection notifications, and booking/rental protection for unverified drivers.
- Coupon support with final validation, usage records, user/category limits, allowed car type enforcement, and loyalty reward coupons.
- Stripe architecture with separate rental PaymentIntent and manual-capture security deposit PaymentIntent handling.
- Cash payment requests, admin cash payment recording, invoices, PDF invoice downloads, refund receipts, and Stripe/cash refund communication.
- Rental lifecycle support for pending, confirmed, active, completed, and cancelled bookings, including inspections, damage records, mileage/fuel tracking, and evidence photos.
- Private storage and authorized serving for driver documents and booking inspection/damage evidence.
- Customer notifications, support tickets, review submission, admin review moderation, and email logs.
- Responsive public/customer UI, with manual responsive smoke-test guidance in `docs/RESPONSIVE_SMOKE_TEST.md`.

## Admin Dashboard

The admin area includes dashboard metrics, booking management, rental start/completion/cancellation actions, inspections, damage evidence, security deposit actions, invoices, refunds, cars, insurance plans, locations, users, driver verification, coupons, reviews, support tickets, settings, and email logs.

Admin sidebar attention badges are driven by pending bookings, unread customer messages on actionable tickets, pending driver verifications, pending/partial invoices, and unapproved reviews. No real admin credentials are published in this README.

## Tech Stack

- PHP `^8.2`
- Laravel `12.67.0`
- Blade templates
- Tailwind CSS `3.4.18`
- Alpine.js `3.15.2`
- Axios `1.19.0`
- Vite `7.3.6` with `laravel-vite-plugin` `2.0.1`
- SQLite for local development by default; MySQL/MariaDB supported through Laravel database configuration
- Stripe PHP `19.4.1`
- Resend PHP `1.12.0`
- Dompdf / Laravel Dompdf for PDF invoices and receipts
- Pest `3.8.7`, PHPUnit through Laravel testing, and Laravel Pint `1.25.1`
- Composer and npm

## Architecture / Important Services

The application keeps business workflows in Laravel services and supporting domain classes rather than only in controllers. Important pieces include `BookingService`, `BookingPricingService`, `PaymentService`, `SecurityDepositService`, `RefundService`, `RefundPolicyService`, `InvoiceService`, `NotificationService`, `PublicSiteDataService`, `AdminAttentionService`, Stripe webhook processing jobs, payment idempotency records, outbox/event infrastructure, and booking state transition validation.

## Security

Confirmed controls include Laravel CSRF protection, authentication and email verification middleware, dedicated admin middleware, request throttling for sensitive customer/admin actions, banned-user middleware, security headers middleware, configurable CSP and production-only HSTS behavior, trusted proxy configuration, Stripe webhook signature verification, webhook/event idempotency, PaymentIntent ID/status/currency/amount/metadata verification, private local storage for sensitive documents/evidence, upload validation, and authorization checks around customer invoices, tickets, reviews, documents, and admin-only actions.

Security is an ongoing process; this repository should still be reviewed for the target hosting environment before production use.

## Installation

```bash
git clone <repository-url>
cd carrental-morocco
composer install
npm install
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell, use this instead of `cp` if preferred:

```powershell
Copy-Item .env.example .env
```

Configure the database in `.env`. The example file defaults to SQLite for local development; create the SQLite file if using that setup, or configure MySQL/MariaDB with the `DB_*` variables.

```bash
php artisan migrate
php artisan db:seed
```

Seeding loads local/demo data such as settings, locations, cars, insurance plans, notifications, and sample users. Replace seeded credentials and data for any shared environment.

## Running Locally

Run the Laravel app:

```bash
php artisan serve
```

Run Vite for frontend development:

```bash
npm run dev
```

Run a queue worker when testing queued jobs:

```bash
php artisan queue:work
```

Run the scheduler locally when testing scheduled tasks:

```bash
php artisan schedule:work
```

Use Stripe CLI only for local webhook testing:

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

Production Stripe webhooks must be configured in the Stripe Dashboard against the production HTTPS endpoint.

## Environment Configuration

Use `.env.example` as the local template. Important groups include `APP_*`, `DB_*`, `CACHE_STORE`, `QUEUE_CONNECTION`, `WEBHOOK_QUEUE_CONNECTION`, `WEBHOOK_QUEUE`, `MAIL_*`, `RESEND_KEY`, `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `SESSION_SECURE_COOKIE`, `TRUSTED_PROXIES`, `SECURITY_CSP_ENABLED`, `SECURITY_CSP_REPORT_ONLY`, `SECURITY_HSTS_ENABLED`, `FILESYSTEM_DISK`, `FILESYSTEM_LOCAL_SERVE`, Redis variables, AWS/S3 variables, `GOOGLE_MAPS_API_KEY`, and rental tuning variables under `RENTAL_*`.

Never commit real secrets or production `.env` files.

## Production Notes

See `DEPLOYMENT.md` for the deployment checklist. Production requires normal persistent infrastructure: HTTPS, a production database, configured mail provider, real Stripe webhook endpoint, managed queue workers through Supervisor/systemd/platform workers, cron calling `php artisan schedule:run`, persistent storage, backups, `APP_DEBUG=false`, secure session cookies, and environment secrets supplied by the host or secret manager.

Do not run local terminal tools such as Stripe CLI or `php artisan schedule:work` as the production operating model.

## Testing

Recommended pre-push verification:

```bash
php artisan test --compact
vendor/bin/pint --test
composer validate --strict
composer audit
npm audit --omit=dev
npm run build
git diff --check
php artisan route:list
php artisan schedule:list
```

On Windows PowerShell, `npm.cmd` can be used if script execution policy blocks `npm.ps1`.

## Localization

The repository includes English, French, and Arabic language files under `lang/`, locale switching through `/language/{locale}`, locale middleware, translated public/customer views, and RTL CSS rules for Arabic layouts.

## Legal / Public Presentation

The application includes public routes and Blade views for Privacy Policy, Terms & Conditions, and Legal Notice. The repository does not invent company registration numbers, tax numbers, physical legal-entity details, social links, or compliance certifications.

## Project Status

This project is prepared for GitHub and portfolio demonstration once final verification passes and screenshots or a hosted demo are added. It should not be described as production-ready until production infrastructure, secrets, monitoring, backups, and deployment-specific security checks are completed.

## License

No standalone project-specific license file has been published yet. The underlying Laravel framework and third-party packages retain their own licenses.
