# Responsive Smoke Test Checklist

This project does not currently use Laravel Dusk, Playwright, Cypress, or
Selenium. Run these manual checks before publishing a public demo or portfolio
link. These are manual viewport checks, not automated browser-test results.

## Viewports

- Mobile: about 375px wide
- Tablet: about 768px wide
- Desktop: about 1440px wide

## Customer Flow

At each viewport, verify:

- Home page: hero, navigation, featured cars, category carousel, testimonials,
  and call-to-action remain reachable without horizontal page overflow.
- Browse cars: filters, cards, images, and empty state fit the viewport.
- Car details: gallery, booking controls, review distribution, and primary CTA
  remain readable and clickable.
- Register and login: labels, inputs, password toggles, validation messages, and
  submit buttons fit without clipping.
- Email verification: resend and logout actions remain reachable.
- Driver profile: all inputs, document upload labels, rejection reason text, and
  submit button wrap safely.
- Booking preview: rental summary, coupon field/actions, acknowledgement, and
  confirm button remain readable.
- Payment page: card/cash choices, Stripe card container, error area, totals, and
  submit button fit narrow screens.
- Booking success and booking history: status labels, vehicle images, totals,
  invoice/review/cancel actions, and empty states remain reachable.
- Support tickets: stats, ticket list, long subjects, reply form, and new-ticket
  action wrap safely.
- Notifications: long titles/messages, unread badges, and mark-read actions wrap
  without overlapping.

## Admin Flow

Admin is desktop-oriented, but at each viewport verify:

- Dashboard: KPI cards, charts, recent activity, and bounded tables remain usable.
- Bookings index: filters, pagination, status badges, progress bars, and action
  buttons remain reachable through horizontal table scrolling where needed.
- Booking detail: customer/vehicle/pricing cards, status actions, inspections,
  damage photo grids, deposit controls, and invoice links remain reachable.
- Driver verification: document previews, approval/rejection actions, and long
  rejection text remain readable.
- Invoices: invoice list scrolls horizontally on narrow screens; invoice detail
  payment/refund sections do not clip controls.
- Refund/deposit views: refund table, receipt links, retry actions, and deposit
  controls remain accessible.
- Coupons: filters, create/edit forms, allowed-car-type controls, and coupon
  tables remain usable.
- Cars: create/edit image upload previews, feature inputs, and cars table remain
  usable.
- Reviews: review table, moderation actions, and response form remain reachable.
- Support: ticket dashboard table and reply form remain usable.
- Settings: setting groups and fixed save/cancel bar remain reachable.

## Pass Criteria

- No critical horizontal page overflow on customer pages.
- Wide admin tables have intentional horizontal scrolling instead of clipped
  action columns.
- Primary navigation works, including mobile menu links and logout.
- Primary forms fit the viewport and preserve labels, inputs, errors, and submit
  buttons.
- Images scale or crop inside their containers without forcing page width.
- Long user-generated text wraps or truncates intentionally.
- No important action requires hover only.
- Stripe Elements are visible and payment errors are readable.
- Private evidence/document routes remain protected.
