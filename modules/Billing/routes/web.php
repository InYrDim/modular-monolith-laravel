<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\InvoiceController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('billing/invoices', InvoiceController::class)->name('billing.invoices.index');
});
