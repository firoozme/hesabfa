<div class="p-6 bg-gray-50 rounded-lg shadow-sm">
    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        جزئیات هزینه
    </h3>

    <!-- بخش آیتم‌ها (جدول) -->
    <div class="mb-8">
        <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
            </svg>
            آیتم‌های هزینه
        </h4>
        @forelse($items as $item)
            @if($loop->first)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right text-gray-600 bg-white rounded-lg shadow-md">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="py-3 px-4 font-semibold">دسته</th>
                                <th class="py-3 px-4 font-semibold">مبلغ (ریال)</th>
                                <th class="py-3 px-4 font-semibold">شرح</th>
                            </tr>
                        </thead>
                        <tbody>
            @endif
                            <tr class="border-b hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">{{ $item->category->title ?? '-' }}</td>
                                <td class="py-3 px-4 text-green-600 font-medium">{{ number_format($item->amount) }}</td>
                                <td class="py-3 px-4">{{ $item->description ?: '-' }}</td>
                            </tr>
            @if($loop->last)
                        </tbody>
                    </table>
                </div>
            @endif
        @empty
            <div class="text-center py-4 text-gray-500">
                هیچ آیتمی برای این هزینه ثبت نشده است.
            </div>
        @endforelse
    </div>

    <!-- بخش پرداخت‌ها -->
    <div>
        <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            پرداخت‌ها
        </h4>
        @forelse($payments as $payment)
            <div class="bg-white p-5 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-200 border border-gray-100 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="font-semibold text-gray-800">روش پرداخت:</span>
                            <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full text-xs">
                                {{ match($payment->paymentable_type) {
                                    'App\Models\CompanyBankAccount' => 'حساب بانکی',
                                    'App\Models\PettyCash' => 'تنخواه',
                                    'App\Models\Fund' => 'صندوق',
                                    'App\Models\Check' => 'چک',
                                    default => 'نامشخص'
                                } }}
                            </span>
                        </p>
                        @if($payment->paymentable_type !== 'App\Models\Check')
                            <p class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                                <span class="font-semibold text-gray-800">نام حساب:</span>
                                {{ $payment->paymentable->name ?? '-' }}
                            </p>
                        @endif
                        <p class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                            <span class="font-semibold text-gray-800">مبلغ:</span>
                            <span class="text-green-600 font-medium">{{ number_format($payment->amount) }} ریال</span>
                        </p>
                        <p class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                            <span class="font-semibold text-gray-800">شماره ارجاع:</span>
                            {{ $payment->reference_number ?: '-' }}
                        </p>
                        <p class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                            <span class="font-semibold text-gray-800">کارمزد:</span>
                            <span class="text-orange-600">{{ $payment->commission }}%</span>
                        </p>
                    </div>
                    @if($payment->paymentable_type === 'App\Models\Check')
                        <div class="border-t md:border-t-0 md:border-l border-gray-200 pt-4 md:pt-0 md:pl-4">
                            <p class="flex items-center gap-2 text-sm text-gray-600">
                                <span class="font-semibold text-gray-800">شماره صیاد:</span>
                                {{ $payment->paymentable->serial_number ?? '-' }}
                            </p>
                            <p class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                                <span class="font-semibold text-gray-800">بانک:</span>
                                {{ $payment->paymentable->bank ?? '-' }}
                            </p>
                            <p class="mt-2 flex items-center gap-Vite نیز اضافه کنید:
                            <p class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                                <span class="font-semibold text-gray-800">شماره صیاد:</span>
                                {{ $payment->paymentable->serial_number ?? '-' }}
                            </p>
                            <p class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                                <span class="font-semibold text-gray-800">بانک:</span>
                                {{ $payment->paymentable->bank ?? '-' }}
                            </p>
                            <p class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                                <span class="font-semibold text-gray-800">شعبه:</span>
                                {{ $payment->paymentable->branch ?? '-' }}
                            </p>
                            <p class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                                <span class="font-semibold text-gray-800">تاریخ سررسید:</span>
                                {{ $payment->paymentable->due_date ?? '-' }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-gray-500">
                هیچ پرداختی برای این هزینه ثبت نشده است.
            </div>
        @endforelse
    </div>
</div>