# Screenshot Checklist

Use demo/fake data only. Do not show private customer data, personal-looking usernames, real names, Gmail-style emails, real driver documents, real payment details, or unreleased business information.

## Required Screenshots

| Filename | Page | Recommended viewport | What should be visible | Privacy check |
| --- | --- | --- | --- | --- |
| `home.jpeg` | Home page | Desktop, 1440x1000 | Hero area, navigation, featured cars or homepage sections | Demo content only; no private account state |
| `cars.jpeg` | Cars page | Desktop, 1440x1000 | Vehicle catalog, search/filter controls, car cards | Demo vehicle data only |
| `car-details.jpeg` | Car details page | Desktop, 1440x1000 | Vehicle gallery, booking panel, policy details, reviews if useful | Demo vehicle and public business contact data only |
| `insurance.jpeg` | Insurance selection page | Desktop, 1440x1000 | Protection-plan choices and booking summary | No personal account indicator or private customer data |
| `booking-preview.jpeg` | Booking preview page | Desktop, 1440x1000 | Booking summary, pricing, insurance/coupon result, payment/deposit call to action | Stripe test mode only; no real card/customer data |
| `booking-payment.jpeg` | Booking payment page | Desktop, 1440x1000 | Payment form and booking summary | Stripe test mode only; no real card/customer data |
| `booking-success.jpeg` | Booking success page | Desktop, 1440x1000 | Confirmation message and booking summary | Demo booking data only |
| `customer-dashboard.jpeg` | Customer dashboard/account area | Desktop, 1440x1000 | Customer overview, booking/account navigation, notifications if useful | Fake customer name and email only |
| `admin-dashboard.jpeg` | Admin dashboard | Desktop, 1440x1000 | Metrics, admin navigation, attention badges | Demo admin data only; no real emails/documents |

## Capture Notes

- Store screenshots in this folder with the exact filenames listed above.
- Use relative paths from the repository when referencing screenshots.
- Prefer a clean seeded/demo database before capture.
- Hide browser bookmarks, local paths, extensions, logged-in usernames, customer names, and personal account indicators.
- Avoid showing `.env`, logs, storage paths, real Stripe dashboard data, or uploaded identity documents.
- Capture Arabic RTL separately later if needed for the portfolio detail page.
