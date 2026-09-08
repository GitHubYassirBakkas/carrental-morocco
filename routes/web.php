<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerInvoiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InsuranceController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WishlistController;
use App\Http\Middleware\EnsureDriverIsVerifiedForBooking;
use App\Services\PublicSiteDataService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC (GUEST)
|--------------------------------------------------------------------------
*/
// Language Switcher
Route::get('language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');
Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
// HOME
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/about', function (PublicSiteDataService $publicSiteData) {
    return view('about.index', $publicSiteData->summary());
})->name('about');

Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact/send', [ContactController::class, 'send'])
    ->middleware('throttle:phase5-contact')
    ->name('contact.send');

Route::get('/privacy-policy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms-and-conditions', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/legal-notice', [LegalController::class, 'notice'])->name('legal.notice');

// Cars
Route::get('/cars', [CarController::class, 'index'])->name('cars.index');
Route::get('/cars/{car}', [CarController::class, 'show'])->name('cars.show');
Route::get('/cars/{car}/details', [CarController::class, 'details'])->name('cars.details');
Route::post('/cars/search', [CarController::class, 'search'])
    ->name('cars.search');

// Insurance
Route::get('/cars/{car}/insurance', [InsuranceController::class, 'select'])
    ->name('insurance.select');

Route::post('/cars/{car}/insurance', [InsuranceController::class, 'store'])
    ->middleware('throttle:phase5-insurance')
    ->name('insurance.store');

/*
|--------------------------------------------------------------------------
| BOOKING FLOW (AUTH REQUIRED)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Booking preview
    Route::post('/cars/{car}/booking/preview', [BookingController::class, 'preview'])
        ->middleware(['throttle:phase5-booking', EnsureDriverIsVerifiedForBooking::class])
        ->name('bookings.preview');

    Route::get('/booking/preview', [BookingController::class, 'showPreview'])
        ->middleware(EnsureDriverIsVerifiedForBooking::class)
        ->name('bookings.preview.show');

    Route::post('/booking/confirm', [BookingController::class, 'store'])
        ->middleware(['throttle:phase5-booking', EnsureDriverIsVerifiedForBooking::class])
        ->name('bookings.store');

    Route::get('/booking/driver-verification-required', [BookingController::class, 'driverVerificationRequired'])
        ->name('booking.driver-verification-required');

    Route::get('/bookings/{booking}', [BookingController::class, 'show'])
        ->name('bookings.show');
    Route::post('/cars/{car}/check-availability',
        [\App\Http\Controllers\BookingController::class, 'checkAvailability']
    )->middleware('throttle:phase5-booking')->name('cars.checkAvailability');

    // PAYMENT
    Route::get('/bookings/{booking}/payment', [PaymentController::class, 'show'])
        ->name('payments.show');

    Route::post('/bookings/{booking}/payment', [PaymentController::class, 'store'])
        ->middleware('throttle:phase5-payment')
        ->name('payments.store');

    Route::get('/booking/{booking}/success', [BookingController::class, 'success'])
        ->name('bookings.success');

});

/*
|--------------------------------------------------------------------------
| AUTH (BREEZE)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('verified')
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::middleware('verified')->group(function () {
        Route::get('/profile/driver', [ProfileController::class, 'editDriver'])
            ->name('profile.driver.edit');
        Route::patch('/profile/driver', [ProfileController::class, 'updateDriver'])
            ->name('profile.driver.update');
    });
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])
        ->name('profile.photo.delete');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('profile.password.update');
    Route::get('/profile/payment-history', [ProfileController::class, 'paymentHistory'])
        ->name('profile.payment-history');
    Route::get('/my/invoices/{invoice}', [CustomerInvoiceController::class, 'show'])
        ->name('customer.invoices.show');
    Route::get('/my/invoices/{invoice}/download', [CustomerInvoiceController::class, 'download'])
        ->name('customer.invoices.download');

});

/* /-------------------------------------------------------------
/ User daschbord my account
/-------------------------------------------------------------*/
Route::middleware('auth')->group(function () {

    Route::get('/account', [ProfileController::class, 'index'])
        ->name('account');

    Route::post('/account', [ProfileController::class, 'update'])
        ->name('account.update');

    Route::get('/my-booking', [BookingController::class, 'myBookings'])
        ->name('my_booking.index');

    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])
        ->middleware('throttle:phase5-booking')
        ->name('bookings.cancel');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('bookings/{booking}/review', [ReviewController::class, 'create'])->name('reviews.create');
        Route::post('bookings/{booking}/review', [ReviewController::class, 'store'])->middleware('throttle:phase5-support')->name('reviews.store');
    });

    Route::post('coupons/apply', [\App\Http\Controllers\CouponController::class, 'apply'])->middleware('throttle:phase5-booking')->name('coupons.apply');

    Route::post('/contact', [TicketController::class, 'store'])->middleware('throttle:phase5-support')->name('tickets.store');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{car}', [WishlistController::class, 'store'])->middleware('throttle:phase5-support')->name('wishlist.store');
    Route::delete('/wishlist/{car}', [WishlistController::class, 'destroy'])->middleware('throttle:phase5-support')->name('wishlist.destroy');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->middleware('throttle:phase5-support')->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->middleware('throttle:phase5-support')->name('notifications.read-all');

    Route::get('/account/support', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/account/support/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/account/support/{ticket}/reply', [TicketController::class, 'reply'])->middleware('throttle:phase5-support')->name('tickets.reply');

});

// ======= USER ROUTES =======

/*
|--------------------------------------------------------------------------
| ADMIN DASHBOARD
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminCarController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\Admin\BookingDamageController;
use App\Http\Controllers\Admin\BookingEvidencePhotoController;
use App\Http\Controllers\Admin\BookingInspectionController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Middleware\AdminMiddleware;

Route::middleware(['auth', AdminMiddleware::class])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {

        // Dashboard
        Route::get('/', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        // Cars
        Route::resource('cars', AdminCarController::class)
            ->except(['show']);

        // Bookings - Resource routes (index, show)
        Route::resource('bookings', AdminBookingController::class)
            ->only(['index', 'show']);

        // ✅ Booking Actions
        Route::post('bookings/{booking}/confirm', [AdminBookingController::class, 'confirm'])
            ->middleware('throttle:phase5-admin-action')
            ->name('bookings.confirm');

        Route::post('bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])
            ->middleware('throttle:phase5-admin-action')
            ->name('bookings.cancel');

        Route::post('bookings/{booking}/start', [AdminBookingController::class, 'start'])
            ->middleware('throttle:phase5-admin-action')
            ->name('bookings.start');

        Route::post('bookings/{booking}/complete', [AdminBookingController::class, 'complete'])
            ->middleware('throttle:phase5-admin-action')
            ->name('bookings.complete');

        Route::get('bookings/{booking}/invoice', [AdminBookingController::class, 'invoice'])
            ->name('bookings.invoice');

        // ✅ Inspections
        Route::post('bookings/{booking}/inspection', [BookingInspectionController::class, 'store'])
            ->middleware('throttle:phase5-admin-action')
            ->name('inspection.store');

        Route::post('inspections/{inspection}/photos', [BookingInspectionController::class, 'uploadPhotos'])
            ->middleware('throttle:phase5-admin-action')
            ->name('bookings.inspection.photos.store');

        Route::get('inspections/photos/{photo}', [BookingEvidencePhotoController::class, 'inspectionPhoto'])
            ->name('bookings.inspection.photos.show');

        // ✅ DAMAGES (FIXED!)
        Route::post('bookings/{booking}/damages', [BookingDamageController::class, 'store'])
            ->middleware('throttle:phase5-admin-action')
            ->name('bookings.damages.store');

        Route::get('damages/{damage}/photos/{photoIndex}', [BookingEvidencePhotoController::class, 'damagePhoto'])
            ->whereNumber('photoIndex')
            ->name('bookings.damages.photos.show');

        // ✅ Invoices
        Route::get('invoices', [AdminInvoiceController::class, 'index'])
            ->name('invoices.index');

        Route::get('invoices/{invoice}', [AdminInvoiceController::class, 'show'])
            ->name('invoices.show');

        Route::post('invoices/{invoice}/payment', [AdminInvoiceController::class, 'recordPayment'])
            ->middleware('throttle:phase5-admin-action')
            ->name('invoices.payment');

        Route::post('invoices/{invoice}/refund', [AdminInvoiceController::class, 'refund'])
            ->middleware('throttle:phase5-admin-action')
            ->name('invoices.refund');

        Route::get('payments/{payment}/refund-receipt', [AdminInvoiceController::class, 'refundReceipt'])
            ->name('payments.refund-receipt');

        // ✅ Refund Management
        Route::get('refunds', [\App\Http\Controllers\Admin\AdminRefundController::class, 'index'])
            ->name('refunds.index');

        Route::get('refunds/{payment}/receipt', [\App\Http\Controllers\Admin\AdminRefundController::class, 'downloadReceipt'])
            ->name('refunds.receipt');

        Route::post('refunds/{payment}/resend-email', [\App\Http\Controllers\Admin\AdminRefundController::class, 'resendEmail'])
            ->middleware('throttle:phase5-admin-action')
            ->name('refunds.resend-email');

        // Insurance Management
        Route::resource('insurances', \App\Http\Controllers\Admin\InsuranceController::class)
            ->except(['show']);

        // Users Management
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class)
            ->except(['create', 'store']); // Don't allow creating users from admin

        Route::post('users/{user}/ban', [\App\Http\Controllers\Admin\UserController::class, 'ban'])
            ->middleware('throttle:phase5-admin-action')
            ->name('users.ban');

        Route::post('users/{user}/unban', [\App\Http\Controllers\Admin\UserController::class, 'unban'])
            ->middleware('throttle:phase5-admin-action')
            ->name('users.unban');

        Route::get('customer-profiles/{customerProfile}/documents/{document}', [\App\Http\Controllers\Admin\CustomerDocumentController::class, 'show'])
            ->name('customer-profiles.documents.show');

        Route::post('customer-profiles/{customerProfile}/driver-verification/verify', [\App\Http\Controllers\Admin\DriverVerificationController::class, 'verify'])
            ->middleware('throttle:phase5-admin-action')
            ->name('customer-profiles.driver-verification.verify');

        Route::post('customer-profiles/{customerProfile}/driver-verification/reject', [\App\Http\Controllers\Admin\DriverVerificationController::class, 'reject'])
            ->middleware('throttle:phase5-admin-action')
            ->name('customer-profiles.driver-verification.reject');

        // Email Logs
        Route::get('email-logs', [\App\Http\Controllers\Admin\EmailLogController::class, 'index'])
            ->name('email-logs.index');

        Route::get('email-logs/{emailLog}', [\App\Http\Controllers\Admin\EmailLogController::class, 'show'])
            ->name('email-logs.show');

        Route::post('email-logs/{emailLog}/resend', [\App\Http\Controllers\Admin\EmailLogController::class, 'resend'])
            ->middleware('throttle:phase5-admin-action')
            ->name('email-logs.resend');

        Route::delete('email-logs/{emailLog}', [\App\Http\Controllers\Admin\EmailLogController::class, 'destroy'])
            ->middleware('throttle:phase5-admin-action')
            ->name('email-logs.destroy');

        // Settings
        Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])
            ->name('settings.index');

        Route::put('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])
            ->middleware('throttle:phase5-admin-action')
            ->name('settings.update');

        // Reviews
        Route::get('reviews', [\App\Http\Controllers\Admin\AdminReviewController::class, 'index'])->name('reviews.index');
        Route::get('reviews/{review}', [\App\Http\Controllers\Admin\AdminReviewController::class, 'show'])->name('reviews.show');
        Route::post('reviews/{review}/approve', [\App\Http\Controllers\Admin\AdminReviewController::class, 'approve'])->middleware('throttle:phase5-admin-action')->name('reviews.approve');
        Route::post('reviews/{review}/reject', [\App\Http\Controllers\Admin\AdminReviewController::class, 'reject'])->middleware('throttle:phase5-admin-action')->name('reviews.reject');
        Route::post('reviews/{review}/respond', [\App\Http\Controllers\Admin\AdminReviewController::class, 'respond'])->middleware('throttle:phase5-admin-action')->name('reviews.respond');
        Route::delete('reviews/{review}', [\App\Http\Controllers\Admin\AdminReviewController::class, 'destroy'])->middleware('throttle:phase5-admin-action')->name('reviews.destroy');

        // Locations
        Route::resource('locations', \App\Http\Controllers\Admin\LocationController::class)
            ->except(['show']);
        Route::post('locations/{location}/toggle', [\App\Http\Controllers\Admin\LocationController::class, 'toggleStatus'])->middleware('throttle:phase5-admin-action')->name('locations.toggle');

        // Coupons
        Route::resource('coupons', \App\Http\Controllers\Admin\CouponController::class);
        Route::post('coupons/{coupon}/toggle', [\App\Http\Controllers\Admin\CouponController::class, 'toggleStatus'])->middleware('throttle:phase5-admin-action')->name('coupons.toggle');
        Route::post('coupons/generate-code', [\App\Http\Controllers\Admin\CouponController::class, 'generateCode'])->middleware('throttle:phase5-admin-action')->name('coupons.generateCode');

        Route::post('payments/{booking}/cash', [PaymentController::class, 'processCash'])->middleware('throttle:phase5-admin-action')->name('payments.cash');

        Route::get('/support', [SupportController::class, 'index'])->name('support.index');
        Route::get('/support/{ticket}', [SupportController::class, 'show'])->name('support.show');
        Route::post('/support/{ticket}/reply', [SupportController::class, 'reply'])->middleware('throttle:phase5-admin-action')->name('support.reply');

        Route::post('bookings/{booking}/security-deposit/release', [PaymentController::class, 'releaseSecurityDeposit'])->middleware('throttle:phase5-admin-action')->name('security-deposit.release');

        Route::post('bookings/{booking}/security-deposit/charge', [PaymentController::class, 'chargeSecurityDeposit'])->middleware('throttle:phase5-admin-action')->name('security-deposit.charge');
        Route::post('bookings/{booking}/security-deposit/retry-refund', [PaymentController::class, 'retrySecurityDepositRefund'])->middleware('throttle:phase5-admin-action')->name('security-deposit.retry-refund');

    });
// ======= ADMIN ROUTES =======

require __DIR__.'/auth.php';
