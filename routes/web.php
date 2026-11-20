<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MyBillController;
use App\Http\Controllers\MyComplaintController;
use App\Http\Controllers\MyProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware(['auth', 'role:pemilik,penghuni'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pengaduan/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show');

    Route::middleware('role:pemilik')->group(function () {
        Route::get('/kamar', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/kamar', [RoomController::class, 'store'])->name('rooms.store');
        Route::patch('/kamar/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/kamar/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

        Route::get('/penghuni', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('/penghuni', [TenantController::class, 'store'])->name('tenants.store');
        Route::patch('/penghuni/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::delete('/penghuni/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');

        Route::get('/tagihan', [BillController::class, 'index'])->name('bills.index');
        Route::post('/tagihan', [BillController::class, 'store'])->name('bills.store');
        Route::post('/tagihan/generate', [BillController::class, 'generate'])->name('bills.generate');
        Route::patch('/tagihan/{bill}', [BillController::class, 'update'])->name('bills.update');
        Route::delete('/tagihan/{bill}', [BillController::class, 'destroy'])->name('bills.destroy');

        Route::get('/pengaduan', [ComplaintController::class, 'index'])->name('complaints.index');
        Route::post('/pengaduan', [ComplaintController::class, 'store'])->name('complaints.store');
        Route::patch('/pengaduan/{complaint}', [ComplaintController::class, 'update'])->name('complaints.update');
        Route::delete('/pengaduan/{complaint}', [ComplaintController::class, 'destroy'])->name('complaints.destroy');
    });

    Route::middleware('role:pemilik')->group(function () {
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/export', [ReportController::class, 'export'])->name('reports.export');

        Route::get('/pengaturan', [SettingController::class, 'index'])->name('settings.index');
        Route::patch('/pengaturan/profil', [SettingController::class, 'updateProfile'])->name('settings.profile');
        Route::patch('/pengaturan/tagihan', [SettingController::class, 'updateBilling'])->name('settings.billing');
        Route::post('/pengaturan/user', [SettingController::class, 'storeUser'])->name('settings.users.store');
        Route::patch('/pengaturan/user/{user}/toggle', [SettingController::class, 'toggleUser'])->name('settings.users.toggle');
    });

    Route::middleware('role:penghuni')->group(function () {
        Route::get('/saya/tagihan', [MyBillController::class, 'index'])->name('my.bills.index');
        Route::post('/saya/tagihan/{bill}/bukti', [MyBillController::class, 'uploadProof'])->name('my.bills.proof');

        Route::get('/saya/pengaduan', [MyComplaintController::class, 'index'])->name('my.complaints.index');
        Route::post('/saya/pengaduan', [MyComplaintController::class, 'store'])->name('my.complaints.store');

        Route::get('/saya/profil', [MyProfileController::class, 'index'])->name('my.profile.index');
        Route::patch('/saya/profil', [MyProfileController::class, 'update'])->name('my.profile.update');
    });
});
