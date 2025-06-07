<?php
namespace App\Traits;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            self::logActivity('created', $model);
        });

        static::updated(function ($model) {
            self::logActivity('updated', $model);
        });

        static::deleted(function ($model) {
            self::logActivity('deleted', $model);
        });
    }

    protected static function logActivity(string $action, $model)
    {
        // ترجمه عمل‌ها
        $actionMessages = [
            'created' => 'ایجاد شد',
            'updated' => 'ویرایش شد',
            'deleted' => 'حذف شد',
        ];

        // نام فارسی مدل
        $modelName = self::getPersianModelName(get_class($model));

        // اطلاعات انجام‌دهنده
        $performer = 'یک کاربر ناشناس';
        if (Auth::check()) {
            $performerType = self::getPersianModelName(get_class(Auth::user()));
            $performerName = self::getReadableName(Auth::user());
            $performer = "{$performerType} با نام {$performerName}";
        }

        // نام قابل فهم برای مدل هدف
        $readableModelName = self::getReadableName($model);

        // ساخت متن نهایی
        if ($action === 'updated') {
            $changes = self::getModelChanges($model, $modelName); // پاس دادن $modelName
            $message = $changes ?: "{$readableModelName} را {$actionMessages[$action]}.";
        } else {
            $message = "{$readableModelName} در قسمت {$modelName}  {$actionMessages[$action]}.";
        }

        Log::create([
            'action' => $action,
            'model' => get_class($model),
            'model_id' => $model->id,
            'description' => $message,
            'loggable_id' => Auth::check() ? Auth::id() : null,
            'loggable_type' => Auth::check() ? get_class(Auth::user()) : null,
        ]);
    }

    protected static function getPersianModelName(string $modelClass): string
    {
        $modelNames = [
            'App\Models\Account' => 'حساب',
            'App\Models\AccountingCategory' => 'دسته‌بندی حسابداری',
            'App\Models\AccountingDocument' => 'گردش',
            'App\Models\AccountingTransaction' => 'تراکنش حسابداری',
            'App\Models\Bank' => 'بانک',
            'App\Models\BankAccount' => 'حساب بانکی',
            'App\Models\Capital' => 'سرمایه',
            'App\Models\Check' => 'چک',
            'App\Models\City' => 'شهر',
            'App\Models\Company' => 'شرکت',
            'App\Models\CompanyBankAccount' => 'حساب بانکی شرکت',
            'App\Models\CompanyOtp' => 'رمز یکبار مصرف شرکت',
            'App\Models\Expense' => 'هزینه',
            'App\Models\ExpenseItem' => 'آیتم هزینه',
            'App\Models\FinancialDocument' => 'سند مالی',
            'App\Models\FinancialDocumentLine' => 'ردیف سند مالی',
            'App\Models\FiscalYear' => 'سال مالی',
            'App\Models\Fund' => 'صندوق',
            'App\Models\Income' => 'درآمد',
            'App\Models\IncomeCategory' => 'دسته‌بندی درآمد',
            'App\Models\IncomeReceipt' => 'رسید درآمد',
            'App\Models\Installment' => 'قسط',
            'App\Models\InstallmentSale' => 'فروش اقساطی',
            'App\Models\Invoice' => 'فاکتور',
            'App\Models\InvoiceItem' => 'آیتم فاکتور',
            'App\Models\Log' => 'لاگ',
            'App\Models\OpeningBalance' => 'مانده افتتاحیه',
            'App\Models\Payment' => 'پرداخت',
            'App\Models\Person' => 'شخص',
            'App\Models\PersonTax' => 'مالیات شخص',
            'App\Models\PersonType' => 'نوع شخص',
            'App\Models\PettyCash' => 'تنخواه',
            'App\Models\PriceList' => 'لیست قیمت',
            'App\Models\Product' => 'محصول',
            'App\Models\ProductCategory' => 'دسته‌بندی محصول',
            'App\Models\ProductUnit' => 'واحد محصول',
            'App\Models\Setting' => 'تنظیمات',
            'App\Models\Store' => 'انبار',
            'App\Models\StoreProduct' => 'محصول انبار',
            'App\Models\StoreTransaction' => 'تراکنش انبار',
            'App\Models\StoreTransactionItem' => 'آیتم تراکنش انبار',
            'App\Models\Tax' => 'مالیات',
            'App\Models\Transaction' => 'تراکنش',
            'App\Models\Transfer' => 'انتقال',
            'App\Models\User' => 'کاربر',
        ];

        return $modelNames[$modelClass] ?? class_basename($modelClass);
    }

    protected static function getReadableName($model): string
    {if ($model instanceof \App\Models\Account) {
        return $model->name ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\AccountingCategory) {
        return $model->title ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\AccountingDocument) {
        return $model->reference ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\AccountingTransaction) {
        return $model->amount ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Bank) {
        return $model->name ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\BankAccount) {
        return $model->bank_name ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Capital) {
        return $model->amount ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Check) {
        return $model->serial_number ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\City) {
        return $model->name ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Company) {
        return $model->fullname ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\CompanyBankAccount) {
        return $model->name ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\CompanyOtp) {
        return $model->otp_code ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Expense) {
        return $model->title ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\ExpenseItem) {
        return $model->description ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\FinancialDocument) {
        return $model->document_number ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\FinancialDocumentLine) {
        return $model->line_number ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\FiscalYear) {
        return $model->name ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Fund) {
        return $model->name ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Income) {
        return $model->amount ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\IncomeCategory) {
        return $model->title ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\IncomeReceipt) {
        return $model->receipt_number ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Installment) {
        return $model->installment_number ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\InstallmentSale) {
        return $model->sale_number ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Invoice) {
        return $model->invoice_number ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\InvoiceItem) {
        return $model->description ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Log) {
        return $model->log_type ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\OpeningBalance) {
        return $model->amount ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Payment) {
        return $model->payment_number ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Person) {
        return $model->fullname ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\PersonTax) {
        return $model->title ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\PersonType) {
        return $model->title ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\PettyCash) {
        return $model->amount ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\PriceList) {
        return $model->name ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Product) {
        return $model->name ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\ProductCategory) {
        return $model->title ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\ProductUnit) {
        return $model->name ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Setting) {
        return $model->key ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Store) {
        return $model->name ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\StoreProduct) {
        return $model->product_name ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\StoreTransaction) {
        return $model->transaction_number ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\StoreTransactionItem) {
        return $model->description ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Tax) {
        return $model->title ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Transaction) {
        return $model->description ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\Transfer) {
        return $model->transfer_number ?? 'نامشخص';
    } elseif ($model instanceof \App\Models\User) {
        return $model->username ?? 'ناشناس';
    } 
     
        return "شناسه {$model->id}";
    }

    protected static function getModelChanges($model, string $modelName): string
    {
        $changes = $model->getChanges(); // تغییرات جدید
        $original = $model->getOriginal(); // مقادیر قدیمی
        $readableChanges = [];

        foreach ($changes as $key => $newValue) {
            if (array_key_exists($key, $original) && $original[$key] != $newValue) {
                $oldValue = $original[$key];
                $fieldName = self::getPersianFieldName($key);
                if($fieldName != 'updated_at'){

                    $readableChanges[] = "تغییر {$fieldName} از '{$oldValue}' به '{$newValue}' در قسمت {$modelName}";
                }
            }
        }

        return $readableChanges ? implode(' و ', $readableChanges) : '';
    }

    protected static function getPersianFieldName(string $field): string
    {
        $fieldNames = [
            'name' => 'نام',
            'title' => 'عنوان',
            'email' => 'ایمیل',
            // فیلدهای دیگه رو اینجا اضافه کنید
        ];

        return $fieldNames[$field] ?? $field;
    }
}