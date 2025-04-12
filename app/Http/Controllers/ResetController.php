<?php

namespace App\Http\Controllers;

use App\Models\Check;
use App\Models\Store;
use App\Models\Export;
use App\Models\Import;
use App\Models\Person;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transfer;
use App\Models\PettyCash;
use App\Models\PriceList;
use App\Models\BankAccount;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use App\Models\OpeningBalance;
use App\Models\ProductCategory;
use App\Models\PriceListProduct;
use App\Models\StoreTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\StoreTransactionItem;

class ResetController extends Controller
{
    public function resetData()
    {
        ini_set('max_execution_time', 3000);

        // غیرفعال کردن بررسی کلید خارجی برای جلوگیری از خطا
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // حذف تمامی داده‌های جداول مرتبط با مدل‌ها
        Account::truncate();
        BankAccount::truncate();
        Check::truncate();
        Invoice::truncate();
        InvoiceItem::truncate();
        Payment::truncate();
        Person::truncate();
        PettyCash::truncate();
        PriceList::truncate();
        ProductCategory::truncate();
        Store::truncate();
        StoreTransaction::truncate();
        StoreTransactionItem::truncate();
        Transaction::truncate();
        Transfer::truncate();
        OpeningBalance::truncate();

        // حذف دستی داده‌های جدول بدون مدل
        DB::table('person_person_type')->truncate();
        DB::table('imports')->truncate();
        DB::table('exports')->truncate();
        DB::table('price_list_product')->truncate();
        DB::table('store_product')->truncate();

        // فعال‌سازی مجدد بررسی کلید خارجی
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Product::query()->update(['quantity' => 0]);

        return response()->json(['message' => 'تمام داده‌ها با موفقیت حذف شدند.']);
    }
}
