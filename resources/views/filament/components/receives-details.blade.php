<div class="p-6 bg-gray-50 rounded-lg shadow-sm">
    {{-- <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        جزئیات دریافت ها
    </h3> --}}

    <!-- بخش آیتم‌ها (جدول) -->
    <div class="mb-8">
        {{-- <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
            </svg>
            آیتم‌های هزینه
        </h4> --}}
        @forelse($receives as $receive)
            @if($loop->first)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right text-gray-600 bg-white rounded-lg shadow-md">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="py-3 px-4 font-semibold">روش</th>
                                <th class="py-3 px-4 font-semibold">مبلغ (ریال)</th>
                                <th class="py-3 px-4 font-semibold">شرح</th>
                            </tr>
                        </thead>
                        <tbody>
            @endif
                            <tr class="border-b hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">
                                    {{ match($receive->receivable_type) {
                                        'App\Models\CompanyBankAccount' => 'حساب بانکی',
                                        'App\Models\PettyCash' => 'تنخواه',
                                        'App\Models\Fund' => 'صندوق',
                                        'App\Models\Check' => 'چک',
                                        default => 'نامشخص'
                                    } }}
                                </td>
                                <td class="py-3 px-4 text-green-600 font-medium">{{ number_format($receive->amount) }}</td>
                                <td class="py-3 px-4">
                                @if($receive->receivable_type !== 'App\Models\Check')
                                    <p class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                                        <span class="font-semibold text-gray-800">نام حساب:</span>
                                        {{ $receive->receivable->name ?? '-' }}
                                    </p>
                                @endif
                                @if($receive->receivable_type === 'App\Models\Check')
                        <div class="border-t md:border-t-0 md:border-l border-gray-200 pt-4 md:pt-0 md:pl-4">
                            <p class="flex items-center gap-2 text-sm text-gray-600">
                                <span class="font-semibold text-gray-800">شماره صیاد:</span>
                                {{ $receive->receivable->serial_number ?? '-' }}
                            </p>
                            <p class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                                <span class="font-semibold text-gray-800">بانک:</span>
                                {{ $receive->receivable->bank ?? '-' }}
                            </p>
                          
                           
                            <p class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                                <span class="font-semibold text-gray-800">شعبه:</span>
                                {{ $receive->receivable->branch ?? '-' }}
                            </p>
                            <p class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                                <span class="font-semibold text-gray-800">تاریخ سررسید:</span>
                                {{ verta($receive->receivable->due_date)->format('Y/m/d') ?? '-' }}
                            </p>
                        </div>
                    @endif
                                </td>
                            </tr>
            @if($loop->last)
                        </tbody>
                    </table>
                </div>
            @endif
        @empty
            <div class="text-center py-4 text-gray-500">
                هیچ دریافتی ثبت نشده است.
            </div>
        @endforelse
    </div>

   
</div>