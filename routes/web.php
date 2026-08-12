<?php

use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SupportReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\EventCommentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventLikeController;
use App\Http\Controllers\EventRatingController;
use App\Http\Controllers\EventSaveController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\ArtistFollowController;
use App\Http\Controllers\ArtistLikeController;
use App\Http\Controllers\HostController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\Cro\ComplaintController as CroComplaintController;
use App\Http\Controllers\Cro\HandoffController as CroHandoffController;
use App\Http\Controllers\Cro\InquiryController as CroInquiryController;
use App\Http\Controllers\Cro\ReportController as CroReportController;
use App\Http\Controllers\Cro\RefundRequestController as CroRefundRequestController;
use App\Http\Controllers\Organizer\BookingController as OrganizerBookingController;
use App\Http\Controllers\Organizer\ReportController as OrganizerReportController;
use App\Http\Controllers\Organizer\ReviewController as OrganizerReviewController;
use App\Http\Controllers\Organizer\SalesController as OrganizerSalesController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\PostponementAlertController;
use App\Http\Controllers\RefundRequestController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\TicketBookingController;
use App\Http\Controllers\ticketCategoryController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\TwoFactorController;
use App\Models\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes (Public Access)
|--------------------------------------------------------------------------
*/
Route::get('/', [DashboardController::class, 'welcome'])
    ->middleware('prevent-back')
    ->name('welcome');

Route::view('/about', 'about')
    ->middleware('prevent-back')
    ->name('about');

Route::view('/terms', 'terms')
    ->middleware('prevent-back')
    ->name('terms');

Route::view('/privacy', 'privacy')
    ->middleware('prevent-back')
    ->name('privacy');

Route::get('/help', [HelpController::class, 'index'])
    ->middleware('prevent-back')
    ->name('help');
Route::post('/help/contact', [HelpController::class, 'contact'])
    ->middleware('throttle:8,1')
    ->name('help.contact');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])
    ->name('locale.switch');

// Custom Auth Routes using AuthController
Route::get('/login', [AuthController::class, 'showLoginForm'])
    ->middleware(['guest', 'prevent-back', 'failed-login'])
    ->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['guest', 'prevent-back', 'failed-login']);

Route::get('/register', [RegisteredUserController::class, 'create'])
    ->middleware('guest')
    ->name('register');

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware(['auth', 'prevent-back'])
    ->name('logout');

// Two-Factor Authentication Challenge (during login)
Route::get('/two-factor-challenge', [TwoFactorController::class, 'showChallenge'])
    ->middleware(['guest', 'prevent-back'])
    ->name('two-factor.challenge');

Route::post('/two-factor-challenge', [TwoFactorController::class, 'verifyChallenge'])
    ->middleware(['guest', 'prevent-back'])
    ->name('two-factor.verify');

// Google Single Sign-On
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->middleware(['guest', 'prevent-back'])
    ->name('auth.google');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->middleware(['guest', 'prevent-back'])
    ->name('auth.google.callback');

Route::middleware('auth')->group(function () {
    Route::get('/auth/google/complete-profile', [GoogleAuthController::class, 'showCompleteProfile'])
        ->name('auth.google.complete-profile');
    Route::post('/auth/google/complete-profile', [GoogleAuthController::class, 'storeCompleteProfile'])
        ->name('auth.google.complete-profile.store');
});

/*
|--------------------------------------------------------------------------
| Main Dashboard (All Authenticated Users)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'prevent-back'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    Route::middleware(['auth', 'verified', 'prevent-back', 'role:'.UserRole::ADMIN])->group(function () {

        // Users Management
        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::get('/user/{id}/edit', [UserController::class, 'edit'])->name('user.edit');
        Route::put('/user/{id}', [UserController::class, 'update'])->name('user.update');
        Route::post('/user/{id}/toggle-lock', [UserController::class, 'toggleLock'])->name('user.toggleLock');
        Route::post('/user/{id}/toggle-active', [UserController::class, 'toggleActive'])->name('user.toggleActive');
        Route::delete('/user/{id}', [UserController::class, 'destroy'])->name('user.destroy');

        // Employee Management
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employee.store');
        Route::get('/employees/export/csv', [EmployeeController::class, 'exportCsv'])->name('employees.export.csv');
        Route::get('/employees/export/pdf', [EmployeeController::class, 'exportPdf'])->name('employees.export.pdf');

        // Event category Management
        Route::get('/event-categories', [EventCategoryController::class, 'index'])->name('event-categories.index');
        Route::get('/event/category/form', [EventCategoryController::class, 'createEventCategory'])->name('event.category.create');
        Route::post('/event/category/store', [EventCategoryController::class, 'storeEventCategory'])->name('event.category.store');
        Route::get('/event-categories/export/csv', [EventCategoryController::class, 'exportCsv'])->name('event-categories.export.csv');
        Route::get('/event-categories/export/pdf', [EventCategoryController::class, 'exportPdf'])->name('event-categories.export.pdf');
        Route::get('/event/category/{id}/edit', [EventCategoryController::class, 'edit'])->name('event.category.edit');
        Route::put('/event/category/{id}', [EventCategoryController::class, 'update'])->name('event.category.update');
        Route::post('/event/category/{id}/toggle-lock', [EventCategoryController::class, 'toggleLock'])->name('event.category.toggleLock');
        Route::post('/event/category/{id}/toggle-active', [EventCategoryController::class, 'toggleActive'])->name('event.category.toggleActive');
        Route::delete('/event/category/{id}', [EventCategoryController::class, 'destroy'])->name('event.category.destroy');

        // Audit Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs');
        Route::get('/audit-logs/export/csv', [AuditLogController::class, 'exportCsv'])->name('audit-logs.export.csv');
        Route::get('/audit-logs/export/pdf', [AuditLogController::class, 'exportPdf'])->name('audit-logs.export.pdf');

        // Support Reports
        Route::get('/support-reports', [SupportReportController::class, 'index'])->name('support-reports');
        Route::get('/support-reports/export/csv', [SupportReportController::class, 'exportCsv'])->name('support-reports.export.csv');
        Route::get('/support-reports/export/pdf', [SupportReportController::class, 'exportPdf'])->name('support-reports.export.pdf');

        // Reports & Analytics
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
        Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
        Route::post('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');

        Route::post('/dashboard/export/pdf', [DashboardController::class, 'exportAdminPdf'])->name('dashboard.export.pdf');

    });
});

/*
|--------------------------------------------------------------------------
| Organizer Routes
|--------------------------------------------------------------------------
*/
Route::prefix('organizer')->name('organizer.')->middleware(['auth', 'verified', 'prevent-back', 'role:'.UserRole::ORGANIZER])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'organizer'])->name('dashboard');
    Route::get('/dashboard/live', [DashboardController::class, 'organizerLive'])->name('dashboard.live');
    Route::post('/dashboard/export/pdf', [DashboardController::class, 'exportOrganizerPdf'])->name('dashboard.export.pdf');

    // Event routes
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/export/csv', [EventController::class, 'exportCsv'])->name('events.export.csv');
    Route::get('/events/export/pdf', [EventController::class, 'exportPdf'])->name('events.export.pdf');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    Route::patch('/events/{event}/status', [EventController::class, 'updateStatus'])->name('events.updateStatus');
    Route::post('/events/{event}/cancel', [EventController::class, 'cancel'])->name('events.cancel');
    Route::post('/events/{event}/postpone', [EventController::class, 'postpone'])->name('events.postpone');
    Route::post('/events/{event}/postponed-schedule', [EventController::class, 'updatePostponedSchedule'])->name('events.postponed-schedule');
    Route::get('/events/{event}/export-pdf', [EventController::class, 'showexportPdf'])->name('events.exportPdf');

    // ticket category routes
    Route::get('/events/{event}/ticket-categories/create', [ticketCategoryController::class, 'create'])->name('ticket-categories.create');
    Route::post('/events/{event}/ticket-categories', [ticketCategoryController::class, 'store'])->name('ticket-categories.store');
    Route::get('/events/{event}/ticket-categories/{ticketCategory}/edit', [ticketCategoryController::class, 'edit'])->name('ticket-categories.edit');
    Route::put('/events/{event}/ticket-categories/{ticketCategory}', [ticketCategoryController::class, 'update'])->name('ticket-categories.update');
    Route::delete('/events/{event}/ticket-categories/{ticketCategory}', [ticketCategoryController::class, 'destroy'])->name('ticket-categories.destroy');

    // Guest list / bookings
    Route::get('/bookings', [OrganizerBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/export/csv', [OrganizerBookingController::class, 'exportCsv'])->name('bookings.export.csv');
    Route::get('/bookings/scan', [OrganizerBookingController::class, 'scanForm'])->name('bookings.scan');
    Route::post('/bookings/scan', [OrganizerBookingController::class, 'scan'])->name('bookings.scan.submit');
    Route::get('/bookings/{ticketBooking}', [OrganizerBookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{ticketBooking}/check-in', [OrganizerBookingController::class, 'checkIn'])->name('bookings.check-in');
    Route::post('/bookings/{ticketBooking}/undo-check-in', [OrganizerBookingController::class, 'undoCheckIn'])->name('bookings.undo-check-in');

    // Sales activity feed
    Route::get('/sales', [OrganizerSalesController::class, 'index'])->name('sales.index');
    Route::get('/sales/export/csv', [OrganizerSalesController::class, 'exportCsv'])->name('sales.export.csv');
    Route::get('/sales/export/pdf', [OrganizerSalesController::class, 'exportPdf'])->name('sales.export.pdf');

    // Reviews inbox
    Route::get('/reviews', [OrganizerReviewController::class, 'index'])->name('reviews.index');

    // Host routes
    Route::get('/hosts', [HostController::class, 'index'])->name('hosts');
    Route::get('/hosts/export/csv', [HostController::class, 'exportCsv'])->name('hosts.export.csv');
    Route::get('/hosts/export/pdf', [HostController::class, 'exportPdf'])->name('hosts.export.pdf');
    Route::get('/hosts/{host}', [HostController::class, 'organizerShow'])->name('hosts.show');
    Route::get('/host/form', [HostController::class, 'create'])->name('host.create');
    Route::post('/host/store', [HostController::class, 'store'])->name('host.store');
    Route::post('hosts/{id}/toggle-active', [HostController::class, 'toggleActive'])->name('hosts.toggleActive');
    Route::get('hosts/{id}/edit', [HostController::class, 'edit'])->name('hosts.edit');
    Route::put('hosts/{id}', [HostController::class, 'update'])->name('hosts.update');
    Route::delete('hosts/{id}', [HostController::class, 'destroy'])->name('hosts.destroy');

    // Artist routes
    Route::get('/artists', [ArtistController::class, 'index'])->name('artists');
    Route::get('/artists/export/csv', [ArtistController::class, 'exportCsv'])->name('artists.export.csv');
    Route::get('/artists/export/pdf', [ArtistController::class, 'exportPdf'])->name('artists.export.pdf');
    Route::get('/artists/{artist}', [ArtistController::class, 'organizerShow'])->name('artists.show');
    Route::get('/artist/form', [ArtistController::class, 'create'])->name('artist.create');
    Route::post('/artist/store', [ArtistController::class, 'store'])->name('artist.store');
    Route::post('artists/{id}/toggle-active', [ArtistController::class, 'toggleActive'])->name('artists.toggleActive');
    Route::get('artists/{id}/edit', [ArtistController::class, 'edit'])->name('artists.edit');
    Route::put('artists/{id}', [ArtistController::class, 'update'])->name('artists.update');
    Route::delete('artists/{id}', [ArtistController::class, 'destroy'])->name('artists.destroy');

    // Reports & Analytics
    Route::get('/reports', [OrganizerReportController::class, 'index'])->name('reports');
    Route::post('/reports/generate', [OrganizerReportController::class, 'generate'])->name('reports.generate');
    Route::get('/reports/tab-data', [OrganizerReportController::class, 'tabData'])->name('reports.tab-data');
    Route::get('/reports/export/excel', [OrganizerReportController::class, 'exportExcel'])->name('reports.export.excel');
    Route::post('/reports/export/pdf', [OrganizerReportController::class, 'exportPdf'])->name('reports.export.pdf');

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'organizer'])->name('calendar.index');
    Route::put('/revenue-goal', [DashboardController::class, 'updateRevenueGoal'])->name('revenue-goal.update');
    Route::delete('/revenue-goal/{revenueGoal}', [DashboardController::class, 'destroyRevenueGoal'])->name('revenue-goal.destroy');
});

/*
|--------------------------------------------------------------------------
| CRO (Customer Relations Officer) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('cro')->name('cro.')->middleware(['auth', 'verified', 'prevent-back', 'role:'.UserRole::CRO])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'cro'])->name('dashboard');
    Route::post('/dashboard/export/pdf', [DashboardController::class, 'exportCroPdf'])->name('dashboard.export.pdf');
    Route::get('/handoffs/{event}', [CroHandoffController::class, 'show'])->name('handoffs.show');
    Route::get('/refund-requests', [CroRefundRequestController::class, 'index'])->name('refund-requests.index');
    Route::get('/refund-requests/{refundRequest}', [CroRefundRequestController::class, 'show'])->name('refund-requests.show');
    Route::post('/refund-requests/{refundRequest}/approve', [CroRefundRequestController::class, 'approve'])->name('refund-requests.approve');
    Route::post('/refund-requests/{refundRequest}/decline', [CroRefundRequestController::class, 'decline'])->name('refund-requests.decline');
    Route::get('/inquiries', [CroInquiryController::class, 'index'])->name('inquiries.index');
    Route::get('/inquiries/{inquiry}', [CroInquiryController::class, 'show'])->name('inquiries.show');
    Route::post('/inquiries/{inquiry}/reply', [CroInquiryController::class, 'reply'])->name('inquiries.reply');
    Route::patch('/inquiries/{inquiry}/status', [CroInquiryController::class, 'updateStatus'])->name('inquiries.update-status');
    Route::post('/inquiries/{inquiry}/claim', [CroInquiryController::class, 'claim'])->name('inquiries.claim');
    Route::post('/inquiries/{inquiry}/reassign', [CroInquiryController::class, 'reassign'])->name('inquiries.reassign');
    Route::patch('/inquiries/{inquiry}/notes', [CroInquiryController::class, 'updateNotes'])->name('inquiries.notes');
    Route::get('/complaints', [CroComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/{complaint}', [CroComplaintController::class, 'show'])->name('complaints.show');
    Route::post('/complaints/{complaint}/reply', [CroComplaintController::class, 'reply'])->name('complaints.reply');
    Route::patch('/complaints/{complaint}/status', [CroComplaintController::class, 'updateStatus'])->name('complaints.update-status');
    Route::post('/complaints/{complaint}/claim', [CroComplaintController::class, 'claim'])->name('complaints.claim');
    Route::post('/complaints/{complaint}/reassign', [CroComplaintController::class, 'reassign'])->name('complaints.reassign');
    Route::patch('/complaints/{complaint}/notes', [CroComplaintController::class, 'updateNotes'])->name('complaints.notes');
    Route::get('/complaints/{complaint}/attachments/{attachment}', [CroComplaintController::class, 'downloadAttachment'])->name('complaints.attachments.download');

    // Reports & Analytics
    Route::get('/reports', [CroReportController::class, 'index'])->name('reports');
    Route::post('/reports/generate', [CroReportController::class, 'generate'])->name('reports.generate');
    Route::get('/reports/export/excel', [CroReportController::class, 'exportExcel'])->name('reports.export.excel');
    Route::post('/reports/export/pdf', [CroReportController::class, 'exportPdf'])->name('reports.export.pdf');
});

/*
|--------------------------------------------------------------------------
| Stripe Webhook (no auth)
|--------------------------------------------------------------------------
*/
Route::post('/stripe/webhook', [CheckoutController::class, 'webhook'])->name('stripe.webhook');

/*
|--------------------------------------------------------------------------
| Attendee Routes
|--------------------------------------------------------------------------
*/

Route::prefix('attendee')->name('attendee.')->middleware(['auth', 'verified', 'prevent-back', 'role:'.UserRole::ATTENDEE])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'attendee'])->name('dashboard');
    Route::post('/postponement-alerts/dismiss', [PostponementAlertController::class, 'dismiss'])->name('postponement-alerts.dismiss');
    Route::post('/bookings/{ticketBooking}/keep-postponement', [PostponementAlertController::class, 'keepTicket'])->name('bookings.keep-postponement');
    Route::get('/artists', [ArtistController::class, 'attendeeIndex'])->name('artists.index');
    Route::get('/artists/{artist}', [ArtistController::class, 'attendeeShow'])->name('artists.show');
    Route::post('/artists/{artist}/like', [ArtistLikeController::class, 'toggle'])->name('artists.like');
    Route::post('/artists/{artist}/follow', [ArtistFollowController::class, 'toggle'])->name('artists.follow');
    Route::get('/saved', [EventSaveController::class, 'index'])->name('saved.index');
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/events/{event}/cart', [CartController::class, 'store'])->name('cart.store');
    Route::put('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::delete('/cart/expired', [CartController::class, 'clearExpired'])->name('cart.clear-expired');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::post('/cart/selection', [CartController::class, 'rememberSelection'])->name('cart.selection');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
    Route::get('/events/{event}', [EventController::class, 'showPublishedEvent'])->name('events.show');
    Route::post('/events/{event}/like', [EventLikeController::class, 'toggle'])->name('events.like');
    Route::post('/events/{event}/save', [EventSaveController::class, 'toggle'])->name('events.save');
    Route::post('/events/{event}/comments', [EventCommentController::class, 'store'])->name('events.comments.store');
    Route::put('/events/{event}/comments/{comment}', [EventCommentController::class, 'update'])->name('events.comments.update');
    Route::delete('/events/{event}/comments/{comment}', [EventCommentController::class, 'destroy'])->name('events.comments.destroy');
    Route::post('/events/{event}/ratings', [EventRatingController::class, 'store'])->name('events.ratings.store');
    Route::delete('/events/{event}/ratings', [EventRatingController::class, 'destroy'])->name('events.ratings.destroy');
    Route::get('/bookings', [TicketBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{ticketBooking}/download', [TicketBookingController::class, 'download'])->name('bookings.download');
    Route::get('/bookings/{ticketBooking}/refund', [RefundRequestController::class, 'create'])->name('bookings.refund.create');
    Route::post('/bookings/{ticketBooking}/refund', [RefundRequestController::class, 'store'])->name('bookings.refund.store');
    Route::post('/bookings/{ticketBooking}/postponement-refund', [RefundRequestController::class, 'storePostponementRefund'])->name('bookings.postponement-refund');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topup', [WalletController::class, 'topup'])->name('wallet.topup');
    Route::get('/wallet/topup/success', [WalletController::class, 'topupSuccess'])->name('wallet.topup.success');
    Route::post('/events/{event}/inquiries', [InquiryController::class, 'store'])->name('events.inquiries.store');
    Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store');
    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::get('/complaints/{complaint}/attachments/{attachment}', [ComplaintController::class, 'downloadAttachment'])->name('complaints.attachments.download');
});

/*
|--------------------------------------------------------------------------
| Include Additional Route Files
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Profile Routes (All Authenticated Users)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/{id}/unread', [NotificationController::class, 'markAsUnread'])->name('notifications.unread');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Two-Factor Authentication Management
    Route::post('/user/two-factor-authentication', [TwoFactorController::class, 'enable'])
        ->name('two-factor.enable');
    Route::post('/user/confirmed-two-factor-authentication', [TwoFactorController::class, 'confirm'])
        ->name('two-factor.confirm');
    Route::delete('/user/two-factor-authentication', [TwoFactorController::class, 'disable'])
        ->name('two-factor.disable');
    Route::post('/user/two-factor-recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])
        ->name('two-factor.recovery-codes');
});
