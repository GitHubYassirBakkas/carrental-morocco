<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CarController;
use App\Http\Controllers\InsuranceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PaymentController;
use App\Services\PdfService;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TicketController;


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
Route::get('/', function () {
    return view('home.index');
})->name('home');


Route::get('/about', function () {
    return view('about.index');
})->name('about');
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
    ->name('insurance.store');

/*
|--------------------------------------------------------------------------
| BOOKING FLOW (AUTH REQUIRED)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {


    // Booking preview
    Route::post('/cars/{car}/booking/preview', [BookingController::class, 'preview'])
        ->name('bookings.preview');

    Route::get('/booking/preview', [BookingController::class, 'showPreview'])
        ->name('bookings.preview.show');

    Route::post('/booking/confirm', [BookingController::class, 'store'])
        ->name('bookings.store');

    Route::get('/bookings/{booking}', [BookingController::class, 'show'])
    ->name('bookings.show');
Route::post('/cars/{car}/check-availability', 
    [\App\Http\Controllers\BookingController::class, 'checkAvailability']
)->name('cars.checkAvailability');


    // PAYMENT
    Route::get('/bookings/{booking}/payment', [PaymentController::class, 'show'])
        ->name('payments.show');

    Route::post('/bookings/{booking}/payment', [PaymentController::class, 'store'])
        ->name('payments.store');

        Route::get('/booking/{booking}/success', [BookingController::class, 'success'])
        ->name('bookings.success');

       
    Route::post('payments/{booking}/stripe', [PaymentController::class, 'processStripe'])->name('payments.stripe');
});





/*
|--------------------------------------------------------------------------
| AUTH (BREEZE)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

   

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])
        ->name('profile.photo.delete');
    Route::post('/logout', [ProfileController::class, 'logout'])
        ->name('logout');

        Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])
    ->name('profile.password.update');
        
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

      Route::middleware(['auth', 'verified'])->group(function () {
            Route::get('bookings/{booking}/review', [ReviewController::class, 'create'])->name('reviews.create');
            Route::post('bookings/{booking}/review', [ReviewController::class, 'store'])->name('reviews.store');
        });

  Route::post('coupons/apply', [\App\Http\Controllers\CouponController::class, 'apply'])->name('coupons.apply');

// contact us
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact/send', [ContactController::class, 'send'])->name('contact.send');

Route::post('/contact', [TicketController::class, 'store'])->name('tickets.store');
Route::get('/account/support', [TicketController::class, 'index'])->name('tickets.index');
Route::get('/account/support/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
Route::post('/account/support/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
  
});

// ======= USER ROUTES =======




/*
|--------------------------------------------------------------------------
| ADMIN DASHBOARD
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\BookingDamageController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminCarController;
use App\Http\Controllers\Admin\AdminInsuranceController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\Admin\BookingInspectionController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\Admin\SupportController;


Route::middleware(['auth', AdminMiddleware::class])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {

        // Dashboard
        Route::get('/', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        // Cars
        Route::resource('cars', AdminCarController::class);
        
        // Bookings - Resource routes (index, show)
        Route::resource('bookings', AdminBookingController::class)
             ->only(['index', 'show','update']);

        // ✅ Booking Actions
        Route::post('bookings/{booking}/confirm', [AdminBookingController::class, 'confirm'])
            ->name('bookings.confirm');

        Route::post('bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])
            ->name('bookings.cancel');

        Route::post('bookings/{booking}/start', [AdminBookingController::class, 'start'])
            ->name('bookings.start');

        Route::post('bookings/{booking}/complete', [AdminBookingController::class, 'complete'])
            ->name('bookings.complete');

        Route::get('bookings/{booking}/invoice', [AdminBookingController::class, 'invoice'])
            ->name('bookings.invoice');


        // ✅ Inspections
        Route::post('bookings/{booking}/inspection', [BookingInspectionController::class, 'store'])
            ->name('inspection.store');

        Route::post('inspections/{inspection}/photos', [BookingInspectionController::class, 'uploadPhotos'])
            ->name('bookings.inspection.photos.store');

        // ✅ DAMAGES (FIXED!)
        Route::post('bookings/{booking}/damages', [BookingDamageController::class, 'store'])
            ->name('bookings.damages.store');

        // ✅ Invoices
        Route::get('invoices', [AdminInvoiceController::class, 'index'])
            ->name('invoices.index');

        Route::get('invoices/{invoice}', [AdminInvoiceController::class, 'show'])
            ->name('invoices.show');

        Route::post('invoices/{invoice}/payment', [AdminInvoiceController::class, 'recordPayment'])
            ->name('invoices.payment');

        Route::post('invoices/{invoice}/refund', [AdminInvoiceController::class, 'refund'])
            ->name('invoices.refund');

        // Insurance Management
        Route::resource('insurances', \App\Http\Controllers\Admin\InsuranceController::class);


         // Users Management
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class)
        ->except(['create', 'store']); // Don't allow creating users from admin
    
    Route::post('users/{user}/ban', [\App\Http\Controllers\Admin\UserController::class, 'ban'])
        ->name('users.ban');
    
    Route::post('users/{user}/unban', [\App\Http\Controllers\Admin\UserController::class, 'unban'])
        ->name('users.unban');


        // Email Logs
    Route::get('email-logs', [\App\Http\Controllers\Admin\EmailLogController::class, 'index'])
        ->name('email-logs.index');
    
    Route::get('email-logs/{emailLog}', [\App\Http\Controllers\Admin\EmailLogController::class, 'show'])
        ->name('email-logs.show');
    
    Route::post('email-logs/{emailLog}/resend', [\App\Http\Controllers\Admin\EmailLogController::class, 'resend'])
        ->name('email-logs.resend');
    
    Route::delete('email-logs/{emailLog}', [\App\Http\Controllers\Admin\EmailLogController::class, 'destroy'])
        ->name('email-logs.destroy');


    // Settings
    Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])
        ->name('settings.index');
    
    Route::put('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])
        ->name('settings.update');

      
       // Reviews
    Route::get('reviews', [\App\Http\Controllers\Admin\AdminReviewController::class, 'index'])->name('reviews.index');
    Route::get('reviews/{review}', [\App\Http\Controllers\Admin\AdminReviewController::class, 'show'])->name('reviews.show');
    Route::post('reviews/{review}/approve', [\App\Http\Controllers\Admin\AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('reviews/{review}/reject', [\App\Http\Controllers\Admin\AdminReviewController::class, 'reject'])->name('reviews.reject');
    Route::post('reviews/{review}/respond', [\App\Http\Controllers\Admin\AdminReviewController::class, 'respond'])->name('reviews.respond');
    Route::delete('reviews/{review}', [\App\Http\Controllers\Admin\AdminReviewController::class, 'destroy'])->name('reviews.destroy');


     // Locations
    Route::resource('locations', \App\Http\Controllers\Admin\LocationController::class);
    Route::post('locations/{location}/toggle', [\App\Http\Controllers\Admin\LocationController::class, 'toggleStatus'])->name('locations.toggle');


     // Coupons
    Route::resource('coupons', \App\Http\Controllers\Admin\CouponController::class);
    Route::post('coupons/{coupon}/toggle', [\App\Http\Controllers\Admin\CouponController::class, 'toggleStatus'])->name('coupons.toggle');
    Route::post('coupons/generate-code', [\App\Http\Controllers\Admin\CouponController::class, 'generateCode'])->name('coupons.generateCode');


        Route::post('payments/{booking}/cash', [PaymentController::class, 'processCash'])->name('admin.payments.cash');

    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::get('/support/{ticket}', [SupportController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [SupportController::class, 'reply'])->name('support.reply');

     Route::post('bookings/{booking}/release-deposit', [PaymentController::class, 'releaseDeposit'])->name('deposit.release');
     
    Route::post('bookings/{booking}/deposit/charge',  [PaymentController::class, 'chargeDeposit'])->name('deposit.charge');

});
// ======= ADMIN ROUTES =======




require __DIR__.'/auth.php';
