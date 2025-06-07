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
use App\Filament\Pages\Auth\Company\Otp;
use App\Http\Controllers\DownloadExport;
use App\Http\Controllers\SiteController;
use Spatie\Activitylog\Facades\Activity;
use App\Http\Controllers\ResetController;
use App\Http\Controllers\CustomController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DownloadPdfController;
use App\Http\Controllers\StoreTransactionController;
use App\Http\Controllers\SubscriptionPaymentController;

Route::get('/test', function () {
    
});
Route::get('/', [SiteController::class,'home']);
Route::get('/store-transaction/{id}/pdf', [StoreTransactionController::class, 'generatePdf'])->name('store.transaction.pdf');
Route::get('/invoice/{id}/pdf', [InvoiceController::class, 'generatePdf'])->name('invoice.pdf');
Route::get('/filament/exports/download/{export}', DownloadExport::class)
    ->name('filament.exports.download');
Route::get('/{record}/pdf/download', [DownloadPdfController::class, 'download'])->name('product.pdf.download');

Route::get('price/list/{record}', PriceList::class)->name('price.list');

// Auth
Route::get('/company/send-otp', Otp::class)->name('send.otp');


// Products
Route::get('/barcode/pdf', [ProductController::class, 'generatePdf'])->name('barcode.pdf');
Route::get('/products/pdf', [ProductController::class, 'generateProductsPdf'])->name('products.pdf');
// Route::get('/products/pdf', [ProductController::class, 'generateProductListsPdf'])->name('products.list.pdf');

// Plan & Subscription
Route::get('/subscription/payment/callback', [SubscriptionPaymentController::class, 'callback'])->name('subscription.payment.callback');

Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return "Cache Cleared Successfully!";
});

Route::get('/empty', function () {
    $tables = [
        'accounting_documents',
            'accounting_transactions',
            'accounts',
            'bank_accounts',
            'cache',
            'cache_locks',
            'capitals',
            'checks',
            'companies',
            'company_bank_accounts',
            'company_otps',
            'company_settings',
            'discounts',
            'discount_product_category',
            'expenses',
            'expense_items',
            'exports',
            'failed_import_rows',
            'financial_documents',
            'financial_document_lines',
            'funds',
            'imports',
            'incomes',
            'income_receipts',
            'installments',
            'installment_sales',
            'invoices',
            'invoice_items',
            'opening_balances',
            'payments',
            'petty_cashes',
            'price_lists',
            'price_list_product',
            'products',
            'product_categories',
            'stores',
            'banks',
            'taxes',
            'product_types',
            'person_taxes',
            'store_product',
            'product_units',
            'store_transactions',
            'store_transaction_items',
            'subscriptions',
            'subscription_payments',
            'transactions',
            'transfers',
            'logs',
            'otp_codes',
            'job_batches',
            'people',
            'person_person_type',
            'inventory_counts',
            'inventory_verifications',
            'subscriptions',
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
