<?php

use App\Http\Controllers\AnnualReportController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DeliveryPermitReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FinancialLinkController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\PricedDeliveryPermitController;
use App\Http\Controllers\SupplyReportController;
use App\Http\Controllers\SupplyRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.store');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'screens'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/supply-requests/lookup', [SupplyRequestController::class, 'lookup'])->name('supply-requests.lookup');
    Route::get('/supply-requests', [SupplyRequestController::class, 'index'])->name('supply-requests.index');
    Route::post('/supply-requests', [SupplyRequestController::class, 'store'])->name('supply-requests.store');
    Route::put('/supply-requests/{supplyRequest}', [SupplyRequestController::class, 'update'])->name('supply-requests.update');
    Route::delete('/supply-requests/{supplyRequest}', [SupplyRequestController::class, 'destroy'])->name('supply-requests.destroy');

    Route::get('/priced-delivery-permits', [PricedDeliveryPermitController::class, 'index'])->name('priced-delivery-permits.index');
    Route::post('/priced-delivery-permits', [PricedDeliveryPermitController::class, 'store'])->name('priced-delivery-permits.store');
    Route::put('/priced-delivery-permits/{permit}', [PricedDeliveryPermitController::class, 'update'])->name('priced-delivery-permits.update');
    Route::delete('/priced-delivery-permits/{permit}', [PricedDeliveryPermitController::class, 'destroy'])->name('priced-delivery-permits.destroy');

    Route::get('/reports/supply-requests', [SupplyReportController::class, 'index'])->name('reports.supply-requests');
    Route::get('/reports/priced-delivery-permits', [DeliveryPermitReportController::class, 'index'])->name('reports.priced-delivery-permits');
    Route::get('/reports/annual-un-disbursed', [AnnualReportController::class, 'index'])->name('reports.annual-un-disbursed');

    Route::get('/hospitals', [HospitalController::class, 'index'])->name('hospitals.index');
    Route::post('/hospitals', [HospitalController::class, 'store'])->name('hospitals.store');
    Route::put('/hospitals/{hospital}', [HospitalController::class, 'update'])->name('hospitals.update');
    Route::delete('/hospitals/{hospital}', [HospitalController::class, 'destroy'])->name('hospitals.destroy');
    Route::post('/hospitals/quick-add', [HospitalController::class, 'quickAdd'])->name('hospitals.quick-add');

    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
    Route::post('/departments/quick-add', [DepartmentController::class, 'quickAdd'])->name('departments.quick-add');

    Route::get('/financial-links', [FinancialLinkController::class, 'index'])->name('financial-links.index');
    Route::post('/financial-links', [FinancialLinkController::class, 'store'])->name('financial-links.store');
    Route::put('/financial-links/{financialLink}', [FinancialLinkController::class, 'update'])->name('financial-links.update');
    Route::delete('/financial-links/{financialLink}', [FinancialLinkController::class, 'destroy'])->name('financial-links.destroy');
    Route::get('/financial-links/types', [FinancialLinkController::class, 'typeIndex'])->name('financial-link-types.index');
    Route::post('/financial-links/types', [FinancialLinkController::class, 'typeStore'])->name('financial-link-types.store');
    Route::put('/financial-links/types/{type}', [FinancialLinkController::class, 'typeUpdate'])->name('financial-link-types.update');
    Route::delete('/financial-links/types/{type}', [FinancialLinkController::class, 'typeDestroy'])->name('financial-link-types.destroy');

    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
    Route::post('/companies/quick-add', [CompanyController::class, 'quickAdd'])->name('companies.quick-add');

    Route::get('/departments/by-hospital/{hospital}', [DepartmentController::class, 'byHospital'])->name('departments.by-hospital');

    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
