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
use App\Http\Controllers\HostController;
use App\Http\Controllers\HostLikeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\Cro\ComplaintController as CroComplaintController;
use App\Http\Controllers\Cro\InquiryController as CroInquiryController;
use App\Http\Controllers\Cro\ReportController as CroReportController;
use App\Http\Controllers\Cro\RefundRequestController as CroRefundRequestController;
use App\Http\Controllers\Organizer\ReportController as OrganizerReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\InquiryController;
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
        Route::get('/admin/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/admin/employees/export/csv', [EmployeeController::class, 'exportCsv'])->name('employees.export.csv');
        Route::get('/admin/employees/export/pdf', [EmployeeController::class, 'exportPdf'])->name('employees.export.pdf');

        // Event category Management
        Route::get('/event-categories', [EventCategoryController::class, 'index'])->name('event-categories');
        Route::get('/event/category/form', [EventCategoryController::class, 'createEventCategory'])->name('event.category.create');
        Route::post('/event/category/store', [EventCategoryController::class, 'storeEventCategory'])->name('event.category.store');
        Route::get('/admin/event-categories/export/csv', [EventCategoryController::class, 'exportCsv'])->name('event-categories.export.csv');
        Route::get('/admin/event-categories/export/pdf', [EventCategoryController::class, 'exportPdf'])->name('event-categories.export.pdf');
        Route::get('/event/category/{id}/edit', [EventCategoryController::class, 'edit'])->name('event.category.edit');
        Route::put('/event/category/{id}', [EventCategoryController::class, 'update'])->name('event.category.update');
        Route::post('/event/category/{id}/toggle-lock', [EventCategoryController::class, 'toggleLock'])->name('event.category.toggleLock');
        Route::post('/event/category/{id}/toggle-active', [EventCategoryController::class, 'toggleActive'])->name('event.category.toggleActive');
        Route::delete('/event/category/{id}', [EventCategoryController::class, 'destroy'])->name('event.category.destroy');
        Route::get('/admin/event-categories', [EventCategoryController::class, 'index'])->name('event-categories.index');

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
        Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
        Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');

    });
});

/*
|--------------------------------------------------------------------------
| Organizer Routes
|--------------------------------------------------------------------------
*/
Route::prefix('organizer')->name('organizer.')->middleware(['auth', 'verified', 'prevent-back', 'role:'.UserRole::ORGANIZER])->group(function () {
    // Event routes
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    Route::get('/events/export/csv', [EventController::class, 'exportCsv'])->name('events.export.csv');
    Route::get('/events/export/pdf', [EventController::class, 'exportPdf'])->name('events.export.pdf');
    Route::patch('/events/{event}/status', [EventController::class, 'updateStatus'])->name('events.updateStatus');
    Route::post('/events/{event}/cancel', [EventController::class, 'cancel'])->name('events.cancel');
    Route::get('/events/{event}/export-pdf', [EventController::class, 'exportPdf'])->name('events.exportPdf');

    // ticket category routes
    Route::get('/events/{event}/ticket-categories/create', [ticketCategoryController::class, 'create'])->name('ticket-categories.create');
    Route::post('/events/{event}/ticket-categories', [ticketCategoryController::class, 'store'])->name('ticket-categories.store');
    Route::get('/events/{event}/ticket-categories/{ticketCategory}/edit', [ticketCategoryController::class, 'edit'])->name('ticket-categories.edit');
    Route::put('/events/{event}/ticket-categories/{ticketCategory}', [ticketCategoryController::class, 'update'])->name('ticket-categories.update');
    Route::delete('/events/{event}/ticket-categories/{ticketCategory}', [ticketCategoryController::class, 'destroy'])->name('ticket-categories.destroy');

    // Host routes
    Route::get('/hosts', [HostController::class, 'index'])->name('hosts');
    Route::get('/hosts/{host}', [HostController::class, 'organizerShow'])->name('hosts.show');
    Route::get('/host/form', [HostController::class, 'create'])->name('host.create');
    Route::post('/host/store', [HostController::class, 'store'])->name('host.store');
    Route::get('/organizer/hosts/export/csv', [HostController::class, 'exportCsv'])->name('hosts.export.csv');
    Route::get('/organizer/hosts/export/pdf', [HostController::class, 'exportPdf'])->name('hosts.export.pdf');
    Route::post('hosts/{id}/toggle-active', [HostController::class, 'toggleActive'])->name('hosts.toggleActive');
    Route::get('hosts/{id}/edit', [HostController::class, 'edit'])->name('hosts.edit');
    Route::put('hosts/{id}', [HostController::class, 'update'])->name('hosts.update');
    Route::delete('hosts/{id}', [HostController::class, 'destroy'])->name('hosts.destroy');

    // Reports & Analytics
    Route::get('/reports', [OrganizerReportController::class, 'index'])->name('reports');
    Route::get('/reports/export/excel', [OrganizerReportController::class, 'exportExcel'])->name('reports.export.excel');
    Route::get('/reports/export/pdf', [OrganizerReportController::class, 'exportPdf'])->name('reports.export.pdf');
});

/*
|--------------------------------------------------------------------------
| CRO (Customer Relations Officer) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('cro')->name('cro.')->middleware(['auth', 'verified', 'prevent-back', 'role:'.UserRole::CRO])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'cro'])->name('dashboard');
    Route::get('/refund-requests', [CroRefundRequestController::class, 'index'])->name('refund-requests.index');
    Route::post('/refund-requests/{refundRequest}/approve', [CroRefundRequestController::class, 'approve'])->name('refund-requests.approve');
    Route::post('/refund-requests/{refundRequest}/decline', [CroRefundRequestController::class, 'decline'])->name('refund-requests.decline');
    Route::get('/inquiries', [CroInquiryController::class, 'index'])->name('inquiries.index');
    Route::post('/inquiries/{inquiry}/reply', [CroInquiryController::class, 'reply'])->name('inquiries.reply');
    Route::patch('/inquiries/{inquiry}/status', [CroInquiryController::class, 'updateStatus'])->name('inquiries.update-status');
    Route::get('/complaints', [CroComplaintController::class, 'index'])->name('complaints.index');
    Route::post('/complaints/{complaint}/reply', [CroComplaintController::class, 'reply'])->name('complaints.reply');
    Route::patch('/complaints/{complaint}/status', [CroComplaintController::class, 'updateStatus'])->name('complaints.update-status');
    Route::get('/complaints/{complaint}/attachments/{attachment}', [CroComplaintController::class, 'downloadAttachment'])->name('complaints.attachments.download');

    // Reports & Analytics
    Route::get('/reports', [CroReportController::class, 'index'])->name('reports');
    Route::get('/reports/export/excel', [CroReportController::class, 'exportExcel'])->name('reports.export.excel');
    Route::get('/reports/export/pdf', [CroReportController::class, 'exportPdf'])->name('reports.export.pdf');
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

Route::prefix('attendee')->name('attendee.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'attendee'])->name('dashboard');
    Route::get('/hosts', [HostController::class, 'attendeeIndex'])->name('hosts.index');
    Route::get('/hosts/{host}', [HostController::class, 'attendeeShow'])->name('hosts.show');
    Route::post('/hosts/{host}/like', [HostLikeController::class, 'toggle'])->name('hosts.like');
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/events/{event}/cart', [CartController::class, 'store'])->name('cart.store');
    Route::put('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
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
    Route::get('/categories', [EventController::class, 'categories'])->name('categories.index');
    Route::get('/bookings', [TicketBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{ticketBooking}/download', [TicketBookingController::class, 'download'])->name('bookings.download');
    Route::get('/bookings/{ticketBooking}/refund', [RefundRequestController::class, 'create'])->name('bookings.refund.create');
    Route::post('/bookings/{ticketBooking}/refund', [RefundRequestController::class, 'store'])->name('bookings.refund.store');
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
