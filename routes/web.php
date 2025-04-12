<?php

use App\Models\Log;
use App\Models\Fund;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PettyCash;
use App\Livewire\PriceList;
use Illuminate\Http\Response;
use App\Models\CompanyBankAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\DownloadExport;
use App\Http\Controllers\SiteController;
use Spatie\Activitylog\Facades\Activity;
use App\Http\Controllers\ResetController;
use App\Http\Controllers\CustomController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\DownloadPdfController;
use App\Http\Controllers\StoreTransactionController;

Route::get('/test', function () {
    
});
Route::get('/', [SiteController::class,'home']);
Route::get('/store-transaction/{id}/pdf', [StoreTransactionController::class, 'generatePdf'])->name('store.transaction.pdf');
Route::get('/invoice/{id}/pdf', [InvoiceController::class, 'generatePdf'])->name('invoice.pdf');
Route::get('/filament/exports/download/{export}', DownloadExport::class)
    ->name('filament.exports.download');
Route::get('/{record}/pdf/download', [DownloadPdfController::class, 'download'])->name('product.pdf.download');

Route::get('price/list/{record}', PriceList::class)->name('price.list');



Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return "Cache Cleared Successfully!";
});

// Route::get('/optimize', function () {
//     Artisan::call('optimize:clear');
//     Artisan::call('config:cache');
//     Artisan::call('route:cache');
//     Artisan::call('view:cache');
//     return "Optimization Done!";
// });

// Route::get('/reset', [ResetController::class, 'resetData'])->name('reset.database');
Route::get('/empty', function () {
    $tables = [
        'accounting_documents',
        'financial_documents',
        'payments',
        'invoice_items',
        'invoices',
        'store_transaction_items',
        'store_transactions',
        'store_product',
        'transactions',
        'company_bank_accounts',
        'funds',
        'petty_cashes',
        'transfers',
        'opening_balances',
        'installment_sales',
        'installments',
        // 'products',
        'logs',
    ];

    // غیرفعال کردن بررسی کلید خارجی (برای جلوگیری از خطای Constraint)
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    foreach ($tables as $table) {
        DB::table($table)->truncate();
    }
    Product::query()->update(['inventory' => 0]);

    // فعال‌سازی مجدد بررسی کلید خارجی
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    return response()->json([
        'message' => 'Tables truncated successfully',
        'tables' => $tables
    ], Response::HTTP_OK);
});
