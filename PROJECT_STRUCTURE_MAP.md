# Car Rental Morocco - Project Structure Map

Generated from a full repo scan of `c:\xampp\htdocs\carrental-morocco`.

## High-Level Tree

```text
PROJECT ROOT
|-- app/
|   |-- Console/
|   |-- Events/
|   |-- Helpers/
|   |-- Http/
|   |   |-- Controllers/
|   |   |   |-- Admin/
|   |   |   |-- Auth/
|   |   |   |-- BookingController.php
|   |   |   |-- CarController.php
|   |   |   |-- ContactController.php
|   |   |   |-- CouponController.php
|   |   |   |-- InsuranceController.php
|   |   |   |-- LanguageController.php
|   |   |   |-- PaymentController.php
|   |   |   |-- ProfileController.php
|   |   |   |-- ReviewController.php
|   |   |   `-- TicketController.php
|   |   |-- Middleware/
|   |   `-- Requests/
|   |-- Listeners/
|   |-- Mail/
|   |-- Models/
|   |-- Providers/
|   |-- Services/
|   `-- View/
|-- bootstrap/
|-- config/
|-- database/
|   |-- factories/
|   |-- migrations/
|   `-- seeders/
|-- lang/
|   |-- ar/
|   |-- en/
|   `-- fr/
|-- public/
|   |-- build/
|   |-- images/
|   |-- js/
|   |-- index.php
|   |-- phpinfo.php
|   `-- robots.txt
|-- resources/
|   |-- css/
|   |-- js/
|   `-- views/
|-- routes/
|-- storage/
|-- tests/
|-- artisan
|-- composer.json
|-- package.json
|-- phpunit.xml
|-- README.md
|-- vite.config.js
|-- debug_*.php
|-- test_*.php
|-- troubleshoot.php
`-- verify_fix.php
```

## Root Files

- `artisan` - Laravel CLI entrypoint.
- `composer.json` - PHP dependencies and scripts. Uses Laravel 12, Stripe PHP, DomPDF, Breeze, Pest.
- `composer.lock` - Locked PHP dependency versions.
- `package.json` - Frontend build scripts and dependencies. Uses Vite, Tailwind, Alpine.
- `package-lock.json` - Locked Node dependency versions.
- `vite.config.js` - Vite/Laravel asset build configuration.
- `tailwind.config.js` - Tailwind configuration.
- `postcss.config.js` - PostCSS/Tailwind processing configuration.
- `phpunit.xml` - Test configuration. Current tests fail during SQLite migrations because of a MySQL-only migration.
- `README.md` - Default Laravel README. Should be replaced with a project-specific install/demo/sale README.
- `debug_admin.php`, `debug_routes.php`, `test_admin_route.php`, `test_controller.php`, `test_route_resolution.php`, `test_routes.php`, `test_simple.php`, `troubleshoot.php`, `verify_fix.php` - one-off debug/test scripts. These should not ship in production.

## app/

### Console

```text
app/Console/
|-- Kernel.php
`-- Commands/
    `-- CancelOverdueBookings.php
```

- `app/Console/Kernel.php` - schedules `bookings:cancel-overdue` hourly.
- `app/Console/Commands/CancelOverdueBookings.php` - cancels bookings whose deposit deadline passed.

### Events and Listeners

```text
app/Events/
`-- BookingCompleted.php

app/Listeners/
|-- LogSentEmail.php
`-- SendCouponRewardNotification.php
```

- `BookingCompleted.php` - event fired when a booking/rental is completed.
- `LogSentEmail.php` - intended email logging listener.
- `SendCouponRewardNotification.php` - intended coupon reward notification listener.

### Helpers

```text
app/Helpers/
`-- helpers.php
```

- `helpers.php` - defines global `setting($key, $default)` helper around `App\Models\Setting`.

### HTTP Controllers

```text
app/Http/Controllers/
|-- Admin/
|   |-- AdminBookingController.php
|   |-- AdminCarController.php
|   |-- AdminDashboardController.php
|   |-- AdminInvoiceController.php
|   |-- AdminReviewController.php
|   |-- BookingDamageController.php
|   |-- BookingInspectionController.php
|   |-- CouponController.php
|   |-- EmailLogController.php
|   |-- InsuranceController.php
|   |-- LocationController.php
|   |-- SettingController.php
|   |-- SupportController.php
|   `-- UserController.php
|-- Auth/
|   |-- AuthenticatedSessionController.php
|   |-- ConfirmablePasswordController.php
|   |-- EmailVerificationNotificationController.php
|   |-- EmailVerificationPromptController.php
|   |-- NewPasswordController.php
|   |-- PasswordController.php
|   |-- PasswordResetLinkController.php
|   |-- RegisteredUserController.php
|   `-- VerifyEmailController.php
|-- BookingController.php
|-- CarController.php
|-- ContactController.php
|-- Controller.php
|-- CouponController.php
|-- InsuranceController.php
|-- LanguageController.php
|-- PaymentController.php
|-- ProfileController.php
|-- ReviewController.php
`-- TicketController.php
```

- `BookingController.php` - customer booking flow: preview, session storage, booking creation, success page, my bookings, availability check. Missing a routed `show()` method.
- `CarController.php` - public car listing, filtering/search, car details and review display. Route references a missing `show()` method.
- `ContactController.php` - contact page and raw email submission.
- `CouponController.php` - customer coupon application/removal during booking preview.
- `InsuranceController.php` - public insurance/protection plan selection stored in session.
- `LanguageController.php` - switches locale among `en`, `fr`, and `ar`.
- `PaymentController.php` - Stripe rental payments, card security deposit authorization, webhooks, cash booking path, admin release/charge deposit actions.
- `ProfileController.php` - account/profile updates, profile photo handling, password update, account delete, logout.
- `ReviewController.php` - customer review creation for completed bookings.
- `TicketController.php` - customer support tickets and replies.

Admin controllers:

- `AdminBookingController.php` - admin booking list/show/update, confirm/cancel/start/complete, invoice PDF download.
- `AdminCarController.php` - admin fleet CRUD, image/gallery upload, car-insurance pivot sync.
- `AdminDashboardController.php` - admin KPIs, alerts, charts, revenue, top cars/customers, late bookings.
- `AdminInvoiceController.php` - invoice list/show, payment recording, refund processing.
- `AdminReviewController.php` - review moderation and admin responses.
- `BookingDamageController.php` - admin damage records, photos, chargeable checkout damages, invoice total update.
- `BookingInspectionController.php` - admin check-in/check-out inspection, mileage, fuel, late/fuel charge calculation, inspection photos.
- `CouponController.php` - admin coupon CRUD, filters, generated codes, activation toggle.
- `EmailLogController.php` - admin email log list/show/resend/delete.
- `InsuranceController.php` - admin insurance/protection plan CRUD.
- `LocationController.php` - admin pickup/dropoff location CRUD and status toggle.
- `SettingController.php` - admin settings editor using `Setting::set`.
- `SupportController.php` - admin ticket inbox, replies, status updates.
- `UserController.php` - admin user list/show/edit/ban/unban/delete.

Auth controllers:

- Breeze authentication controllers for login, registration, email verification, password reset, password confirmation, and password update.
- `RegisteredUserController.php` includes unreachable `dd('redirect works')` after a return and should be cleaned.

### Middleware and Requests

```text
app/Http/
|-- Kernel.php
|-- Middleware/
|   |-- AdminMiddleware.php
|   |-- Authenticate.php
|   |-- Authorize.php
|   |-- EncryptCookies.php
|   |-- EnsureEmailIsVerified.php
|   |-- HandlePrecognitiveRequests.php
|   |-- PreventRequestsDuringMaintenance.php
|   |-- RedirectIfAuthenticated.php
|   |-- RequirePassword.php
|   |-- SetCacheHeaders.php
|   |-- SetLocale.php
|   |-- ThrottleRequests.php
|   |-- TrimStrings.php
|   |-- TrustProxies.php
|   |-- ValidateSignature.php
|   `-- VerifyCsrfToken.php
`-- Requests/
    |-- Auth/
    |   `-- LoginRequest.php
    `-- ProfileUpdateRequest.php
```

- `Kernel.php` - registers middleware stack.
- `AdminMiddleware.php` - blocks non-admin users.
- `SetLocale.php` - applies session locale to the app.
- `SetCacheHeaders.php` - custom cache header behavior.
- Other middleware are standard Laravel/Breeze style request, auth, CSRF, cookie, signature, and proxy handlers.
- `LoginRequest.php` - Breeze login validation/authentication.
- `ProfileUpdateRequest.php` - profile validation request, partly superseded by inline validation in `ProfileController`.

### Mail

```text
app/Mail/
|-- AdminPaymentConfirmedMail.php
|-- BookingConfirmedMail.php
|-- BookingPendingMail.php
|-- CouponRewardMail.php
|-- TicketCreatedMail.php
`-- TicketRepliedMail.php
```

- Booking mails notify users about confirmed/pending booking states.
- Ticket mails notify support ticket creation/replies.
- Coupon reward mail supports loyalty/reward email flow.
- Admin payment confirmation mail is sent from booking confirmation logic.

### Models

```text
app/Models/
|-- Booking.php
|-- BookingDamage.php
|-- BookingInspection.php
|-- BookingPhoto.php
|-- Car.php
|-- Coupon.php
|-- CouponUsage.php
|-- EmailLog.php
|-- Insurance.php
|-- Invoice.php
|-- Location.php
|-- Notification.php
|-- Payment.php
|-- Review.php
|-- Setting.php
|-- Ticket.php
|-- TicketMessage.php
|-- User.php
`-- VerificationToken.php
```

- `Booking.php` - central booking/rental aggregate: relations, status helpers, timeline labels, late fee, fuel fee, final total, deposit overdue, inspections, damages, invoice, coupon usage.
- `BookingDamage.php` - check-in/check-out damage records with chargeability, estimated cost, and photos.
- `BookingInspection.php` - check-in/check-out inspection record with mileage, fuel, damage notes, photos.
- `BookingPhoto.php` - photo rows for inspections.
- `Car.php` - fleet car model: specs, pricing, location, bookings, reviews, image URL resolution, gallery, insurance pivot, availability checks.
- `Coupon.php` - coupon eligibility, discount calculation, usage recording, scopes and display helpers.
- `CouponUsage.php` - coupon use audit per user/booking.
- `EmailLog.php` - email log records and scopes.
- `Insurance.php` - insurance/protection plan model with pricing, coverage, deductible, features, active/ordered scopes.
- `Invoice.php` - invoice totals, payment/refund-derived paid amount and balance.
- `Location.php` - pickup/dropoff branches, map helpers, opening hours, active/city scopes.
- `Notification.php` - app notification records.
- `Payment.php` - invoice payment/refund/deposit-charge record.
- `Review.php` - car/user/booking review, approval scopes, rating helpers.
- `Setting.php` - cached app settings and typed value accessor.
- `Ticket.php` - support ticket model, number generation, statuses, unread count.
- `TicketMessage.php` - support ticket messages.
- `User.php` - auth user, admin/customer roles, bookings/reviews, banned/active scopes.
- `VerificationToken.php` - custom token model for verification/reset-like flows.

### Services

```text
app/Services/
|-- BookingService.php
|-- DepositService.php
|-- InspectionService.php
|-- PaymentService.php
|-- PdfService.php
|-- RefundPolicyService.php
|-- RefundService.php
`-- RentalService.php
```

- `BookingService.php` - admin booking state transitions, invoice creation, cancellation/refund policy, overdue cancellation.
- `DepositService.php` - booking advance deposit percentage calculations and overdue checks.
- `InspectionService.php` - alternative service for check-in/check-out inspection logic, fuel and late calculation.
- `PaymentService.php` - invoice payment recording and status calculation. Currently has broken references to undefined `$paidAmount` and `$this->depositService`.
- `PdfService.php` - PDF generation helper service.
- `RefundPolicyService.php` - refund policy calculation wrapper.
- `RefundService.php` - refund payment records and invoice status updates.
- `RentalService.php` - alternative start/complete/checkout calculation service. References methods/config that are missing or inconsistent.

### Providers and View Components

```text
app/Providers/
|-- AppServiceProvider.php
|-- EventServiceProvider.php
`-- RouteServiceProvider.php

app/View/Components/
|-- AppLayout.php
`-- GuestLayout.php
```

- Providers register app boot logic, events, routes.
- View components support Breeze-style app/guest layouts.

## routes/

```text
routes/
|-- auth.php
|-- console.php
`-- web.php
```

- `web.php` - public pages, car search/details, insurance selection, booking flow, payments, account/support, admin CRUD/resources, Stripe webhook.
- `auth.php` - Breeze auth routes.
- `console.php` - console route definitions.

Route notes:

- `cars.show` points to `CarController@show`, which is missing.
- `bookings.show` points to `BookingController@show`, which is missing.
- Admin cash payment route name becomes `admin.admin.payments.cash` because it is inside the `admin.` name group and also named `admin.payments.cash`.
- Deposit routes are named `admin.deposit.release` and `admin.deposit.charge`; previous logs show a stale view or cached view looked for `admin.bookings.release-deposit`.

## database/

### Factories and Seeders

```text
database/
|-- factories/
|   `-- UserFactory.php
`-- seeders/
    |-- CarSeeder.php
    |-- DatabaseSeeder.php
    |-- InsuranceSeeder.php
    |-- LocationSeeder.php
    |-- SettingsSeeder.php
    `-- UserSeeder.php
```

- `UserFactory.php` - default test user factory.
- `DatabaseSeeder.php` - root seeder.
- `UserSeeder.php` - initial users/admin data.
- `CarSeeder.php` - demo fleet/car data.
- `InsuranceSeeder.php` - default Basic/Standard/Premium insurance rows.
- `LocationSeeder.php` - initial branch/location rows.
- `SettingsSeeder.php` - site, payment, booking, tax, fuel, late fee settings.

### Migrations by Domain

Users/auth:

- `2024_01_01_000001_create_users_table.php` - users table.
- `2024_01_01_000008_create_verification_tokens_table.php` - custom verification tokens.
- `2026_03_11_014755_add_is_banned_to_users_table.php` - user ban flag.
- `2026_03_22_222209_create_password_reset_tokens_table.php` - password resets.

Fleet/locations:

- `2024_01_01_000002_create_locations_table.php` - branch/pickup/dropoff locations.
- `2024_01_01_000003_create_cars_table.php` - base cars table.
- `2025_12_28_204929_add_missing_columns_to_cars_table.php` - gallery, conditions, age, fuel policy, cancellation policy, deposit, documents.
- `2026_02_10_115645_add_fuel_config_to_cars_table.php` - fuel tank and fuel price config.

Insurance/protection:

- `2024_01_01_000004_create_insurances_table.php` - base insurance plans.
- `2026_01_21_120021_create_car_insurance_table.php` - car-insurance pivot and pivot price.
- `2026_03_07_225249_add_morocco_fields_to_insurances_table.php` - type, coverage, deductible, features, sort order.
- `2026_03_08_225617_add_is_default_to_car_insurance_table.php` - default insurance flag on pivot.

Booking/rental lifecycle:

- `2024_01_01_000005_create_bookings_table.php` - base bookings table.
- `2024_01_01_000016_add_additional_fields_to_bookings_table.php` - driver, deposit, instructions.
- `2025_12_28_212728_fix_bookings_table_column_names.php` - renames return location to dropoff location.
- `2026_02_10_112759_add_invoiced_at_to_bookings_table.php` - invoice timestamp.
- `2026_02_10_115846_add_fuel_tracking_to_bookings_table.php` - booking fuel tracking.
- `2026_02_10_174703_add_late_fields_to_bookings_table.php` - late minutes and late fee.
- `2026_02_11_145830_add_completed_at_to_bookings_table.php` - completed/started timestamps.
- `2026_02_18_004951_add_deposit_dates_to_bookings_table.php` - deposit paid/due timestamps.
- `2026_04_10_211507_add_deposit_intent_to_bookings_table.php` - Stripe security deposit intent/status/charged amount.
- `2026_04_10_230319_add_payment_intents_to_bookings_table.php` - rental payment intent.

Inspections/damages:

- `2026_02_01_172715_create_booking_inspections_table.php` - inspection records.
- `2026_02_01_172911_create_booking_photos_table.php` - inspection photos.
- `2026_02_01_191757_create_booking_damages_table.php.php` - damage records. Filename has duplicate `.php.php`.
- `2026_03_07_151444_add_photos_to_booking_damages_table.php` - damage photo support.

Invoices/payments/refunds:

- `2026_02_13_150140_create_invoices_table.php` - invoice totals/status.
- `2026_02_13_185611_payments_table.php` - payments/refunds/deposit-charge records.
- `2026_02_15_193136_add_amount_paid_to_invoices_table.php` - stored amount paid column, but code uses computed `paid_amount`.
- `2026_03_18_013709_add_discount_fields_to_invoices_table.php` - discount fields.
- `2026_03_18_015843_update_invoices_status_enum.php` - MySQL-only status enum migration; breaks SQLite tests and removes `partial` while code still uses `partial`.

Reviews/coupons/support/settings/logging:

- `2024_01_01_000006_create_reviews_table.php` - reviews.
- `2024_01_01_000007_create_email_logs_table.php` - email logs.
- `2024_01_01_000010_create_notifications_table.php` - notifications.
- `2024_01_01_000011_create_settings_table.php` - settings.
- `2024_01_01_000012_create_coupons_table.php` - coupons.
- `2026_02_22_233221_add_flags_to_settings_table.php` - settings flags.
- `2026_03_11_021904_add_fields_to_email_logs_table.php` - extra email log metadata.
- `2026_03_16_000549_add_approved_at_to_reviews_table.php` - review approval timestamp.
- `2026_03_16_000909_add_response_columns_to_reviews_table.php` - admin review responses.
- `2026_03_18_000224_add_advanced_fields_to_coupons_table.php` - coupon restrictions/categories.
- `2026_03_18_000258_create_coupon_usages_table.php` - coupon usage audit.
- `2026_04_09_100000_create_tickets_table.php` - support tickets.
- `2026_04_09_100100_create_ticket_messages_table.php` - ticket messages.
- `2026_04_15_000000_add_unique_constraint_to_coupon_usages_table.php` - duplicate coupon use protection.

## resources/

### Views

```text
resources/views/
|-- about/index.blade.php
|-- account/index.blade.php
|-- admin/
|   |-- bookings/
|   |-- cars/
|   |-- coupons/
|   |-- email-logs/
|   |-- insurances/
|   |-- invoices/
|   |-- layouts/
|   |-- locations/
|   |-- reviews/
|   |-- settings/
|   |-- support/
|   |-- users/
|   |-- dashboard.blade.php
|   `-- layout.blade.php
|-- auth/
|-- bookings/
|-- cars/
|-- components/
|-- contact/
|-- emails/
|-- home/
|-- insurance/
|-- layouts/
|-- my_booking/
|-- partials/
|-- payments/
|-- profile/
|-- reviews/
|-- tickets/
|-- dashboard.blade.php
`-- welcome.blade.php
```

Public/customer views:

- `home/index.blade.php` - public homepage with hero, categories, brands, process, testimonials.
- `about/index.blade.php` - public about page.
- `cars/index.blade.php` - customer car listing/filtering.
- `cars/details.blade.php` - car details, calculator, reviews, booking form.
- `insurance/select.blade.php` - protection/insurance plan selection.
- `bookings/preview.blade.php` - booking preview before persistence.
- `bookings/success.blade.php` - booking/payment result page.
- `payments/index.blade.php` - Stripe/cash payment UI and card deposit authorization.
- `my_booking/index.blade.php` - customer booking history.
- `account/index.blade.php` - customer account page.
- `contact/index.blade.php` - contact page.
- `reviews/create.blade.php` - customer review form.
- `tickets/index.blade.php`, `tickets/show.blade.php` - customer support tickets.

Admin views:

- `admin/dashboard.blade.php` - admin metrics dashboard.
- `admin/bookings/index.blade.php` - booking list/filter.
- `admin/bookings/show.blade.php` - booking detail, actions, inspections, damages, deposits.
- `admin/bookings/invoice.blade.php` - invoice PDF view.
- `admin/cars/create.blade.php`, `edit.blade.php`, `index.blade.php` - fleet management.
- `admin/coupons/create.blade.php`, `edit.blade.php`, `index.blade.php`, `show.blade.php` - coupon admin.
- `admin/email-logs/index.blade.php`, `show.blade.php` - email logs.
- `admin/insurances/create.blade.php`, `edit.blade.php`, `index.blade.php` - insurance/protection plan admin.
- `admin/invoices/index.blade.php`, `show.blade.php` - invoices, payment and refund admin.
- `admin/locations/create.blade.php`, `edit.blade.php`, `index.blade.php` - branches/locations.
- `admin/reviews/index.blade.php`, `show.blade.php` - review moderation.
- `admin/settings/index.blade.php` - settings editor.
- `admin/support/index.blade.php`, `show.blade.php` - support ticket admin.
- `admin/users/edit.blade.php`, `index.blade.php`, `show.blade.php` - user admin.
- `admin/layout.blade.php` and `admin/layouts/app.blade.php` - two admin layout variants; likely should be consolidated.

Auth/layout/components:

- `auth/*.blade.php` - login, register, password reset, verification, shared auth styles.
- `layouts/app.blade.php`, `guest.blade.php`, `auth.blade.php`, `navigation.blade.php` - public/app layouts.
- `partials/navbar.blade.php`, `footer.blade.php` - public nav/footer.
- `components/*.blade.php` - Breeze-style Blade components.
- `emails/*.blade.php` - email templates.
- `dashboard.blade.php`, `welcome.blade.php` - default/older starter views; likely unused or low-value now.

### JS and CSS

```text
resources/js/
|-- app.js
`-- bootstrap.js

resources/css/
|-- app.css
|-- car.css
`-- sections/
    |-- brands.css
    |-- categories.css
    |-- hero.css
    |-- process.css
    |-- rent-cta.css
    |-- testimonials.css
    `-- components/
        |-- buttons.css
        |-- cards.css
        `-- titles.css
```

- `resources/js/app.js` - app JavaScript entry.
- `resources/js/bootstrap.js` - Axios/bootstrap setup.
- `resources/css/app.css` - main CSS entry.
- `resources/css/car.css` - car-specific styling.
- `resources/css/sections/*` - homepage/section styling.

## public/

```text
public/
|-- build/
|-- images/
|   |-- brands/
|   |-- cars/
|   |-- categories/
|   |-- hero/
|   |-- placeholder/
|   `-- logo.png
|-- js/
|-- index.php
|-- phpinfo.php
|-- robots.txt
`-- favicon.ico
```

- `public/index.php` - web entrypoint.
- `public/build/*` - compiled Vite assets.
- `public/hot` - Vite dev server marker. Should not be committed/deployed unless intentionally using hot reload.
- `public/js/*.js` - legacy/static sliders, validation, scroll-top scripts.
- `public/images/*` - seeded/demo vehicle, brand, category, hero assets.
- `public/phpinfo.php` - dangerous in production; exposes server/PHP info.

## lang/

```text
lang/
|-- ar.json
|-- en.json
|-- fr.json
|-- ar/
|-- en/
`-- fr/
```

- Locale JSON files provide flat UI translations.
- Locale folders include `actions.php`, `auth.php`, `cars.php`, `http-statuses.php`, `messages.php`, `pagination.php`, `passwords.php`, `validation.php`.
- The app supports Arabic, English, and French, useful for Morocco.

## config/

```text
config/
|-- app.php
|-- auth.php
|-- cache.php
|-- database.php
|-- filesystems.php
|-- logging.php
|-- mail.php
|-- queue.php
|-- services.php
`-- session.php
```

- Standard Laravel config.
- `services.php` contains Stripe/service integration configuration.
- No `config/rental.php` exists, even though some services call `config('rental.*')`.

## tests/

```text
tests/
|-- Feature/
|   |-- Auth/
|   |-- ExampleTest.php
|   `-- ProfileTest.php
|-- Unit/
|   `-- ExampleTest.php
|-- Pest.php
`-- TestCase.php
```

- Mostly default Breeze/Pest tests.
- No domain tests for booking, payments, deposit holds, refunds, inspections, damages, coupons, or insurance.
- Current test run fails during migration because `2026_03_18_015843_update_invoices_status_enum.php` uses MySQL-only SQL on SQLite.

## Business Domain Map

### Booking / Rental Domain

- Models: `Booking`, `BookingInspection`, `BookingDamage`, `BookingPhoto`.
- Controllers: `BookingController`, `AdminBookingController`, `BookingInspectionController`, `BookingDamageController`.
- Services: `BookingService`, `RentalService`, `InspectionService`.
- Views: `bookings/preview`, `bookings/success`, `my_booking/index`, `admin/bookings/*`.
- Migrations: bookings base/additional fields, fuel tracking, late fields, completed/start timestamps, inspections/photos/damages.
- Notes: This is the core domain, but lifecycle and checkout logic is spread across model, controllers, and multiple services.

### Payment / Invoice / Deposit Domain

- Models: `Invoice`, `Payment`, booking deposit fields on `Booking`.
- Controllers: `PaymentController`, `AdminInvoiceController`.
- Services: `PaymentService`, `DepositService`, `RefundService`, `RefundPolicyService`.
- Views: `payments/index`, `admin/invoices/*`, deposit section inside `admin/bookings/show`.
- Migrations: invoices, payments, amount paid, discount fields, payment intents, deposit intent.
- Notes: There are two different meanings of deposit: booking advance payment and security deposit/card hold. These should be split in naming and schema.

### Insurance / Protection Domain

- Models: `Insurance`, car-insurance pivot via `Car::insurances()`.
- Controllers: `InsuranceController`, `Admin\InsuranceController`.
- Views: `insurance/select`, `admin/insurances/*`.
- Migrations: insurances, car_insurance pivot, Morocco insurance fields, default pivot flag.
- Notes: Good domain to keep for Morocco, but public naming should become "protection package" or "excess reduction" with mandatory base coverage included.

### Fleet / Cars / Locations Domain

- Models: `Car`, `Location`.
- Controllers: `CarController`, `AdminCarController`, `Admin\LocationController`.
- Views: `cars/*`, `admin/cars/*`, `admin/locations/*`.
- Migrations: cars, locations, car conditions, fuel config.
- Notes: Strong sellable feature set, but car create/update validation is inconsistent.

### Admin Domain

- Controllers: all `app/Http/Controllers/Admin/*`.
- Views: `resources/views/admin/*`.
- Middleware: `AdminMiddleware`.
- Notes: Admin is broad and useful, but too many calculations happen in dashboard and views. Move metrics to services/query objects.

### Customer Account / Auth Domain

- Models: `User`, `VerificationToken`.
- Controllers: `ProfileController`, `Auth/*`.
- Views: `auth/*`, `profile/*`, `account/index`.
- Middleware: auth, verification, password, locale.
- Notes: Standard Breeze foundation with custom profile/account enhancements.

### Coupon / Promotion Domain

- Models: `Coupon`, `CouponUsage`.
- Controllers: customer `CouponController`, admin `Admin\CouponController`.
- Views: `admin/coupons/*`, coupon UI embedded in booking preview.
- Migrations: coupons, advanced fields, usages, unique usage constraint.
- Notes: Good business feature; coupon eligibility logic currently lives in the model and could become a service/action.

### Reviews Domain

- Models: `Review`.
- Controllers: `ReviewController`, `AdminReviewController`.
- Views: `reviews/create`, `admin/reviews/*`, reviews embedded in car details.
- Migrations: reviews, approval, responses.
- Notes: Clean domain overall.

### Support / Contact Domain

- Models: `Ticket`, `TicketMessage`.
- Controllers: `TicketController`, `Admin\SupportController`, `ContactController`.
- Mail: `TicketCreatedMail`, `TicketRepliedMail`.
- Views: `tickets/*`, `admin/support/*`, `contact/index`.
- Migrations: tickets, ticket messages.
- Notes: Contact form and ticket creation overlap. Decide if public contact creates email only or support ticket.

### Settings / Localization / Email Logs Domain

- Models: `Setting`, `EmailLog`, `Notification`.
- Controllers: `SettingController`, `EmailLogController`, `LanguageController`.
- Views: `admin/settings`, `admin/email-logs`, language switcher component.
- Migrations: settings, email logs, notifications.
- Notes: Settings are useful, but some code still uses hard-coded values instead of settings.

## Duplicated or Scattered Logic

1. Deposit meaning is duplicated.
   - `DepositService` calculates booking advance deposit percentage.
   - `PaymentController` handles Stripe security deposit card holds.
   - Booking fields use generic `deposit_*` names for both concepts.
   - Refactor to `advance_payment_*` and `security_deposit_*`.

2. Invoice/payment status logic is duplicated and inconsistent.
   - `PaymentController` updates invoice status and tries to set `paid_amount`.
   - `PaymentService` calculates status from payments.
   - `RefundService` also updates invoice status.
   - `Invoice` computes `paid_amount` dynamically, while a migration adds `amount_paid`.
   - Centralize in one `InvoicePaymentService`.

3. Booking lifecycle logic is duplicated.
   - `BookingService::startRental/completeRental`.
   - `RentalService::start/complete`.
   - `BookingInspectionController` directly changes checkout data.
   - `InspectionService` also changes checkout data.
   - Keep one lifecycle service and route all admin actions through it.

4. Fuel and late fee calculation is duplicated.
   - `Booking` accessors/constants.
   - `BookingInspectionController`.
   - `InspectionService`.
   - `RentalService`.
   - Some use fixed `5`, some use `config('rental.*')`, some use booking daily rate.
   - Create `CheckoutChargeService` or `RentalChargeCalculator`.

5. Insurance pricing is inconsistent.
   - `Insurance::daily_rate` suggests per-day pricing.
   - `Insurance::getInsuranceFeeAttribute()` says actually one-time fee.
   - `BookingController` adds insurance only once, not multiplied by days.
   - Decide one-time vs per-day and rename columns/UI accordingly.

6. Car availability overlap logic appears in multiple places.
   - `Car::isAvailableForDates`.
   - `CarController::index` date filtering.
   - `BookingController::preview/store`.
   - Keep overlap rules in one query scope/service.

7. Email sending is direct in controllers/services.
   - Booking, support, contact all call `Mail` directly.
   - Email logs/listeners exist but are not clearly the single path.
   - Consider queued jobs and notification classes.

8. Settings vs hard-coded constants.
   - `SettingsSeeder` has late/fuel settings.
   - `Booking` has constants for late fee.
   - `BookingInspectionController` hard-codes `pricePerPercent = 5`.
   - Services call missing `config('rental.*')`.

## Dead, Risky, or Cleanup Files

Likely delete before production/sale:

- `public/phpinfo.php` - exposes PHP/server info.
- `debug_admin.php`
- `debug_routes.php`
- `test_admin_route.php`
- `test_controller.php`
- `test_route_resolution.php`
- `test_routes.php`
- `test_simple.php`
- `troubleshoot.php`
- `verify_fix.php`
- `public/hot` - dev-only Vite hot file.

Likely unused/starter files:

- `resources/views/welcome.blade.php` - default/old Laravel landing view.
- `resources/views/dashboard.blade.php` - default user dashboard view unless still routed elsewhere.
- `README.md` - default Laravel README, not project-specific.

Risky/buggy files to fix rather than delete:

- `database/migrations/2026_03_18_015843_update_invoices_status_enum.php` - MySQL-only and removes `partial`.
- `database/migrations/2026_02_01_191757_create_booking_damages_table.php.php` - odd duplicate extension.
- `app/Services/PaymentService.php` - undefined variables/properties.
- `app/Services/RentalService.php` - references `canStart()` and `config('rental.*')`, which are missing/inconsistent.
- `app/Http/Controllers/Auth/RegisteredUserController.php` - unreachable `dd()`.
- `routes/web.php` - routes missing controller methods.

## Missing Structure / Recommended Folders

These folders would make the project easier to maintain and sell:

```text
app/
|-- Actions/
|   |-- Bookings/
|   |-- Payments/
|   |-- Rentals/
|   `-- Coupons/
|-- DTOs/
|-- Enums/
|-- Jobs/
|-- Notifications/
|-- Policies/
|-- QueryBuilders/
|-- Rules/
|-- Support/
|   |-- Money/
|   `-- Dates/
`-- ViewModels/

config/
`-- rental.php

tests/
|-- Feature/
|   |-- Booking/
|   |-- Payment/
|   |-- Admin/
|   `-- Insurance/
`-- Unit/
    |-- Pricing/
    |-- Deposits/
    `-- Availability/
```

Recommended additions:

- `app/Enums/BookingStatus.php`, `InvoiceStatus.php`, `PaymentStatus.php`, `DepositStatus.php` - remove stringly typed statuses.
- `app/Actions/Bookings/CreateBooking.php` - move session-to-booking creation out of controller.
- `app/Actions/Payments/RecordPayment.php` - one write path for invoice payments.
- `app/Actions/Payments/HoldSecurityDeposit.php`, `ReleaseSecurityDeposit.php`, `ChargeSecurityDeposit.php`.
- `app/Services/PricingService.php` - rental total, insurance/protection, dropoff fee, coupon, taxes.
- `app/Services/AvailabilityService.php` - overlap checks and date search.
- `app/Services/RentalChargeCalculator.php` - fuel, mileage, late fees, damage totals.
- `app/Policies/*` - booking/ticket/review authorization instead of repeated `abort_if`.
- `app/Jobs/*` - queued emails, deposit expiry checks, overdue cancellations.
- `app/Notifications/*` - Laravel notifications for booking, payment, ticket, coupon events.
- `app/Http/Requests/*` - form request classes for booking, payment, cars, coupons, inspections, damages.
- `config/rental.php` - central grace period, late fee, fuel fee, deposit hold rules.
- `docs/INSTALL.md`, `docs/SELLER_DEMO.md`, `docs/DOMAIN_MODEL.md`.

## Architecture Summary

### Well Organized

- Clear MVC foundation with Laravel conventions.
- Useful domain models for a real rental company: cars, bookings, invoices, payments, inspections, damages, coupons, reviews, tickets, locations.
- Admin panel is broad and business-oriented.
- Multilingual setup is valuable for Morocco.
- Stripe security deposit hold is a strong feature if cleaned up.

### Messy

- Business logic is spread across controllers, models, and services.
- Payment/deposit/invoice responsibilities overlap.
- Some services look half-refactored or unused.
- Some route/controller methods are missing.
- Some migrations fight the code, especially invoice statuses.
- Encoding artifacts in comments/text suggest file encoding cleanup is needed.
- Two admin layouts exist and should be consolidated.

### Refactor First

1. Split booking advance payment from security deposit.
2. Centralize invoice/payment/refund status logic.
3. Centralize rental pricing, insurance/protection pricing, coupon, tax, dropoff fee.
4. Centralize availability checks.
5. Centralize checkout charges: fuel, late, damage.
6. Fix migrations and add domain tests.
7. Replace debug scripts and default README with production docs.

### Move

- Move booking creation out of `BookingController` into an action/service.
- Move Stripe deposit actions out of `PaymentController` into a `SecurityDepositService`.
- Move admin dashboard queries into `AdminDashboardQuery` or dedicated services.
- Move coupon eligibility from `Coupon` model into a service if it grows further.
- Move repeated authorization checks into policies.

### Delete

- Delete public/debug/test helper scripts before production.
- Delete `public/phpinfo.php`.
- Delete stale starter views if not routed.
- Delete or replace default Laravel README.
- Delete `public/hot` from committed/deployed files.

## Suggested Domain Renaming

For Morocco rental companies, keep both insurance and deposit, but rename them professionally:

- `insurance` in customer UI -> `protection plan`.
- Mandatory base coverage -> included with rental.
- Optional paid plans -> deductible/excess reduction, premium protection.
- `deposit` as card hold/cash guarantee -> `security deposit`.
- Booking confirmation percentage -> `advance payment` or `reservation deposit`.

This avoids confusing customers and makes the project easier to sell to agencies.
