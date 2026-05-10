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
    // Users Management
    Route::middleware(['auth', 'verified', 'role:' . UserRole::ADMIN])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::get('/user/{id}/edit', [UserController::class, 'edit'])->name('user.edit');
        Route::put('/user/{id}', [UserController::class, 'update'])->name('user.update');
        Route::post('/user/{id}/toggle-lock', [UserController::class, 'toggleLock'])->name('user.toggleLock');
        Route::post('/user/{id}/toggle-active', [UserController::class, 'toggleActive'])->name('user.toggleActive');
        Route::delete('/user/{id}', [UserController::class, 'destroy'])->name('user.destroy');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employee.store');
        Route::post('/admin/employees', [EmployeeController::class, 'store'])->name('admin.employee.store');
        Route::get('/admin/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/admin/employees/export/csv', [EmployeeController::class, 'exportCsv'])->name('employees.export.csv');
        Route::get('/admin/employees/export/pdf', [EmployeeController::class, 'exportPdf'])->name('employees.export.pdf');


        
    });
});


/*
|--------------------------------------------------------------------------
| Organizer Routes
|--------------------------------------------------------------------------
*/
Route::prefix('organizer')->name('organizer.')->group(function () {
       
    // Add organizer-specific routes here
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
Route::prefix('attendee')->name('attendee.')->group(function () {
        
    // Add attendee-specific routes here
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
