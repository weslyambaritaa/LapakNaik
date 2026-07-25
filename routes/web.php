<?php

use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncomingOrderController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleRunnerController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\StorefrontOrderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/toko/{slug}', [StorefrontController::class, 'show'])->name('storefront.show');
Route::post('/toko/{slug}/checkout', [StorefrontOrderController::class, 'store'])
    ->middleware('throttle:checkout')
    ->name('storefront.checkout');
Route::post('/toko/{slug}/pelanggan-lookup', [StorefrontOrderController::class, 'lookupCustomer'])
    ->middleware('throttle:customer-lookup')
    ->name('storefront.customer-lookup');
Route::get('/toko/{slug}/pesanan/{transaction}', [StorefrontOrderController::class, 'show'])->name('storefront.order');

Route::post('/midtrans/callback', [MidtransWebhookController::class, 'handle'])->name('midtrans.callback');

Route::get('/cron/run-schedule', ScheduleRunnerController::class)
    ->middleware('throttle:60,1')
    ->name('cron.run-schedule');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/products/{product}/stock', [ProductController::class, 'adjustStock'])->name('products.stock');

    Route::get('/pos/riwayat', [TransactionController::class, 'index'])->name('pos.history');
    Route::get('/pos/{transaction}', [TransactionController::class, 'show'])->name('pos.receipt');
    Route::get('/pos/{transaction}/pdf', [TransactionController::class, 'receiptPdf'])->name('pos.receipt.pdf');
    Route::get('/pos', [TransactionController::class, 'create'])->name('pos.index');
    Route::post('/pos', [TransactionController::class, 'store'])->name('pos.store');

    Route::post('/pos/{transaction}/refund', [TransactionController::class, 'refund'])
        ->middleware('role:owner,admin')
        ->name('pos.refund');

    Route::resource('cash-flows', CashFlowController::class)->only(['index', 'store', 'destroy']);

    Route::get('/pesanan-masuk', [IncomingOrderController::class, 'index'])->name('incoming-orders.index');
    Route::post('/pesanan-masuk/{transaction}/konfirmasi', [IncomingOrderController::class, 'confirm'])->name('incoming-orders.confirm');

    Route::middleware('role:owner')->group(function () {
        Route::get('/pengaturan/pembayaran', [BusinessSettingsController::class, 'edit'])->name('settings.payment.edit');
        Route::post('/pengaturan/pembayaran', [BusinessSettingsController::class, 'update'])->name('settings.payment.update');
        Route::delete('/pengaturan/pembayaran', [BusinessSettingsController::class, 'destroy'])->name('settings.payment.destroy');

        Route::post('/pengaturan/bank-rekening', [BankAccountController::class, 'store'])->name('bank-accounts.store');
        Route::delete('/pengaturan/bank-rekening/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
