<div>
    <h3 class="text-lg font-semibold mb-4">لیست اقساط</h3>

    @if($installments->isEmpty())
        <p class="text-gray-500">هیچ قسطی برای این فروش ثبت نشده است.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-2 text-right">شماره قسط</th>
                        <th class="p-2 text-right">مبلغ</th>
                        <th class="p-2 text-right">تاریخ سررسید</th>
                        <th class="p-2 text-right">وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($installments as $index => $installment)
                        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                            <td class="p-2 text-right">{{ $index + 1 }}</td>
                            <td class="p-2 text-right">{{ number_format($installment->amount) }} ریال</td>
                            <td class="p-2 text-right">{{ verta($installment->due_date)->format('Y/m/d') }}</td>
                            <td class="p-2 text-right">
                                @if($installment->status === 'paid')
                                    <span class="text-green-600">پرداخت‌شده</span>
                                @elseif($installment->status === 'pending')
                                    <span class="text-yellow-600">در انتظار</span>
                                @else
                                    <span class="text-red-600">نامشخص</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>