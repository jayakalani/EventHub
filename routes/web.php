<?php

use App\Models\UserRole;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HostController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\SeatCategoryController;
use App\Http\Controllers\AuditLogController;




/*
|--------------------------------------------------------------------------
| Guest Routes (Public Access)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
})->middleware('prevent-back');

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
    
    Route::middleware(['auth', 'verified', 'prevent-back', 'role:' . UserRole::ADMIN])->group(function () {

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

    //Audit Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs');
        Route::get('/audit-logs/export/csv', [AuditLogController::class, 'exportCsv'])->name('audit-logs.export.csv');
        Route::get('/audit-logs/export/pdf', [AuditLogController::class, 'exportPdf'])->name('audit-logs.export.pdf');



        
    });
});


/*
|--------------------------------------------------------------------------
| Organizer Routes
|--------------------------------------------------------------------------
*/
Route::prefix('organizer')->name('organizer.')->middleware(['auth', 'verified', 'prevent-back', 'role:'. UserRole::ORGANIZER])->group(function () {
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
    Route::get('/events/{event}/export-pdf', [EventController::class, 'exportPdf'])->name('events.exportPdf');
    
    //seat category routes
    Route::get('/events/{event}/seat-categories/create', [SeatCategoryController::class, 'create'])->name('seat-categories.create');
    Route::post('/events/{event}/seat-categories', [SeatCategoryController::class, 'store'])->name('seat-categories.store');
    Route::get('/events/{event}/seat-categories/{seatCategory}/edit', [SeatCategoryController::class, 'edit'])->name('seat-categories.edit');
    Route::put('/events/{event}/seat-categories/{seatCategory}', [SeatCategoryController::class, 'update'])->name('seat-categories.update');
    Route::delete('/events/{event}/seat-categories/{seatCategory}', [SeatCategoryController::class, 'destroy'])->name('seat-categories.destroy');



    // Host routes
    Route::get('/hosts', [HostController::class, 'index'])->name('hosts');
    Route::get('/host/form', [HostController::class, 'create'])->name('host.create');
    Route::post('/host/store', [HostController::class, 'store'])->name('host.store');
    Route::get('/organizer/hosts/export/csv', [HostController::class, 'exportCsv'])->name('hosts.export.csv');
    Route::get('/organizer/hosts/export/pdf', [HostController::class, 'exportPdf'])->name('hosts.export.pdf');
    Route::post('hosts/{id}/toggle-active', [HostController::class, 'toggleActive'])->name('hosts.toggleActive');
    Route::get('hosts/{id}/edit', [HostController::class, 'edit'])->name('hosts.edit');
    Route::put('hosts/{id}', [HostController::class, 'update'])->name('hosts.update');
    Route::delete('hosts/{id}', [HostController::class, 'destroy'])->name('hosts.destroy');
});



/*
|--------------------------------------------------------------------------
| CRO (Customer Relations Officer) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('cro')->name('cro.')->group(function () {
       
    // Add CRO-specific routes here
});


/*
|--------------------------------------------------------------------------
| Attendee Routes
|--------------------------------------------------------------------------
*/

Route::prefix('attendee')->name('attendee.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'attendee']) ->name('dashboard');
    Route::get('/events/{event}', [EventController::class, 'showPublishedEvent'])->name('events.show');
    Route::get('/categories', [EventController::class, 'categories'])->name('categories.index');
    Route::get('/bookings', [EventController::class, 'bookings'])->name('bookings.index');
    

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
});
