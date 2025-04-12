<x-filament-panels::page>
    <!-- کارت‌های آمار -->
    <h2>{{ $this->record->title }}</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200">تعداد محصولات وارد شده</h3>
            <p class="text-2xl text-gray-900 dark:text-gray-200">{{ number_format($this->record->getTotalEntries()) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200">تعداد محصولات خارج شده</h3>
            <p class="text-2xl text-gray-900 dark:text-gray-200">{{ number_format($this->record->getTotalExits()) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200">تعداد محصولات منحصربه‌فرد</h3>
            <p class="text-2xl text-gray-900 dark:text-gray-200">{{ $this->record->getUniqueProductsCount() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200">موجودی کل انبار</h3>
            <p class="text-2xl text-gray-900 dark:text-gray-200">{{ number_format($this->record->getTotalInventory()) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200">تعداد تراکنش‌های ورودی</h3>
            <p class="text-2xl text-gray-900 dark:text-gray-200">{{ $this->record->getEntryTransactionsCount() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200">تعداد تراکنش‌های خروجی</h3>
            <p class="text-2xl text-gray-900 dark:text-gray-200">{{ $this->record->getExitTransactionsCount() }}</p>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>