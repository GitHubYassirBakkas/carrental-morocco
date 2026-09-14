# CarRental Morocco

Full-stack Laravel car-rental platform focused on Morocco.

## Overview

CarRental Morocco is a Laravel 12 application for vehicle discovery, customer bookings, driver verification, payments, security deposits, invoices, refunds, support, and admin operations. The project is prepared as a portfolio/demo repository with multilingual public and customer-facing pages for English, French, and Arabic.

## Live Demo

Live Demo: Coming soon

## Screenshots

Final public screenshots are stored under `docs/screenshots/` and use clean demo data only.

| Screenshot | Relative path |
| --- | --- |
| Home page | `docs/screenshots/home.jpeg` |
| Cars page | `docs/screenshots/cars.jpeg` |
| Car details | `docs/screenshots/car-details.jpeg` |
| Insurance selection | `docs/screenshots/insurance.jpeg` |
| Booking preview | `docs/screenshots/booking-preview.jpeg` |
| Booking payment | `docs/screenshots/booking-payment.jpeg` |
| Booking success | `docs/screenshots/booking-success.jpeg` |
| Customer dashboard | `docs/screenshots/customer-dashboard.jpeg` |
| Admin dashboard | `docs/screenshots/admin-dashboard.jpeg` |

See `docs/screenshots/README.md` for the screenshot checklist and capture guidance.

## Key Features

### Customer

- Authentication, email verification, profile management, and password updates.
- English, French, and Arabic localization, including RTL support for Arabic.
- Vehicle browsing with filtering by search term, location, brand, type, transmission, price range, and availability dates.
- Booking flow with insurance selection, booking preview, availability recheck, validation, and invoice creation.
- Driver verification with private document uploads and admin approval/rejection.
- Coupons with final validation, usage tracking, limits, allowed car type checks, and loyalty reward coupons.
- Stripe rental payments and a separate security deposit authorization flow.
- Cash payment path through admin-recorded payments.
- Customer invoices, PDF downloads, payment history, notifications, support tickets, reviews, and wishlist.

### Admin

- Admin dashboard with operational metrics and attention badges.
- Cars, bookings, invoices, refunds, insurance plans, locations, users, settings, coupons, reviews, support tickets, driver verification, and email logs.
- Booking lifecycle actions for confirmation, cancellation, rental start, completion, inspections, damage records, fuel/mileage tracking, and evidence photos.
- Security deposit release, capture, and retry actions.
- Refund receipts and resendable refund communications.

### Security And Architecture

- Stripe webhook signature verification.
- Webhook, payment, and admin-action idempotency protections.
- Private document and evidence storage with authorized access.
- Server-side validation and authorization checks.
- Rate limiting for sensitive booking, payment, support, insurance, contact, and admin actions.
- Security headers middleware with configurable CSP/HSTS behavior.
- Queue, scheduler, and outbox/event architecture for webhook and domain-event processing.
- Feature and unit tests covering booking, payment, refund, security, localization, public presentation, and admin workflows.

## Tech Stack

- PHP `8.2+`
- Laravel `12.67.0`
- Blade templates
- Tailwind CSS `3.4.18`
- Alpine.js `3.15.2`
- Axios `1.19.0`
- Vite `7.3.6`
- Laravel Vite Plugin `2.0.1`
- Stripe PHP `19.4.1`
- Resend PHP `1.12.0`
- Dompdf / Laravel Dompdf `3.1.x`
- Pest `3.8.7`
- Laravel Pint `1.25.1`
- SQLite for local development by default; MySQL/MariaDB supported through Laravel configuration

## Architecture

The application keeps major workflows in services and domain classes, including `BookingService`, `BookingPricingService`, `PaymentService`, `SecurityDepositService`, `RefundService`, `RefundPolicyService`, `InvoiceService`, `PublicSiteDataService`, and `AdminAttentionService`.

Stripe webhooks are stored and processed through queued jobs, payment/event audit records, idempotency records, booking state transitions, and an outbox/event pipeline.

## Booking Flow

Browse -> Verify Driver -> Preview -> Insurance/Coupon -> Booking -> Payment -> Confirmation -> Rental Start -> Inspection -> Completion -> Deposit Release/Capture

## Payments

CarRental Morocco supports Stripe rental payments and a separate manual-capture security deposit authorization. The application also includes a cash path where admins can record eligible cash payments.

Stripe webhook processing includes signature verification, idempotency records, payment intent metadata checks, payment event audits, and queued processing. Demo and local development should use Stripe test mode only. Do not commit or expose Stripe secrets.

## Security

Documented protections include CSRF protection, authentication and email verification middleware, admin middleware, banned-user checks, request throttling, security headers, trusted proxy configuration, private storage for driver documents and booking evidence, upload validation, authorization checks, Stripe webhook verification, idempotency, and payment intent validation.

Security still depends on the target hosting environment, correct production secrets, HTTPS, queue workers, backups, and operational monitoring.

## Localization

The repository includes English, French, and Arabic language files under `lang/`, a locale switch route, locale middleware, translated views, and RTL layout support for Arabic.

## Testing

Recommended verification commands:

```bash
php artisan test --compact
vendor/bin/pint --test
composer validate --strict
npm run build
```

On Windows PowerShell, use `npm.cmd run build` if script execution policy blocks `npm.ps1`.

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell, copy the environment file with:

```powershell
Copy-Item .env.example .env
```

Configure the database connection in `.env`. The example configuration supports local SQLite, or you can configure MySQL/MariaDB through Laravel's `DB_*` variables.

```bash
php artisan migrate
php artisan db:seed
npm run dev
php artisan serve
```

Use `php artisan db:seed` for local/demo data only when appropriate for the environment.

Run a queue worker when testing queued jobs:

```bash
php artisan queue:work
```

Run the scheduler locally when testing scheduled tasks:

```bash
php artisan schedule:work
```

## Stripe Local Development

Use the Stripe CLI only for local webhook testing:

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

Production webhooks should be configured in the Stripe Dashboard against the production HTTPS endpoint.

## Production Notes

See `DEPLOYMENT.md` for production setup notes, environment configuration, security requirements, queue/scheduler guidance, and deployment checks.

## Legal

The application includes public pages for:

- Privacy Policy
- Terms & Conditions
- Legal Notice

## Project Status

Portfolio-ready / demo-ready presentation package. A public live demo URL is still pending.

## License

No project-specific license has been published yet. The Laravel framework and third-party packages retain their own licenses.
