<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/deposits/create', function () {
        return view('user.deposits.create');
    })->name('deposits.create');

    Route::get('/transfers/create', function () {
        return view('user.transfers.create');
    })->middleware('throttle:transfers')->name('transfers.create');

    Route::get('/withdrawals/create', function () {
        return view('user.withdrawals.create');
    })->middleware('throttle:withdrawals')->name('withdrawals.create');

    Route::get('/transactions', function () {
        return view('user.transactions.index');
    })->name('transactions.index');

    Route::get('/kyc', function () {
        return view('user.compliance.kyc');
    })->middleware('throttle:kyc')->name('compliance.kyc');

    Route::get('/notifications', function () {
        return view('user.notifications.index');
    })->name('notifications.index');

    Route::get('/bills', function () {
        return view('user.bills.index');
    })->name('bills.index');

    Route::get('/cards', function () {
        return view('user.cards.index');
    })->name('cards.index');

    Route::get('/account/upgrade', function () {
        return view('user.account.upgrade');
    })->name('account.upgrade');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:Super Admin|Operations Admin|Compliance Admin|Support Admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->middleware('permission:view-metrics')->name('dashboard');

    Route::get('/compliance/kyc', function () {
        return view('admin.compliance.kyc.index');
    })->middleware('permission:review-kyc')->name('compliance.kyc.index');

    Route::get('/financials/deposits', function () {
        return view('admin.financials.deposits.index');
    })->middleware('permission:approve-deposits')->name('financials.deposits.index');

    Route::get('/financials/withdrawals', function () {
        return view('admin.financials.withdrawals.index');
    })->middleware('permission:approve-withdrawals')->name('financials.withdrawals.index');

    Route::get('/billers', function () {
        return view('admin.billers.index');
    })->middleware('permission:manage-billers')->name('billers.index');

    Route::get('/customers', function () {
        return view('admin.customers.index');
    })->middleware('permission:view-customers')->name('customers.index');

    Route::get('/audit-logs', function () {
        return view('admin.audit-logs.index');
    })->middleware('permission:view-audit-logs')->name('audit-logs.index');
});

require __DIR__.'/auth.php';
