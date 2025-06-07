<x-filament::page>
    <h2 class="text-2xl font-bold mb-6">داشبورد اشتراک</h2>
    <div class="bg-white shadow-md rounded-lg p-6">
        <p class="text-gray-600">خوش آمدید به داشبورد اشتراک!</p>
        <p class="mt-4">در اینجا می‌توانید جزئیات اشتراک فعال خود را مشاهده کنید.</p>
        @if(auth('company')->user()->subscriptions()->latest()->where('status', 'active')->where('ends_at', '>', now())->first())
            @php
                $subscription = auth('company')->user()->subscriptions()->latest()->where('status', 'active')->where('ends_at', '>', now())->first();
            @endphp
            <div class="mt-6">
                <h3 class="text-lg font-semibold">اشتراک فعال</h3>
                <p><strong>پلن:</strong> {{ $subscription->plan->name }}</p>
                <p><strong>شروع:</strong> {{ $subscription->starts_at->format('Y-m-d') }}</p>
                <p><strong>پایان:</strong> {{ $subscription->ends_at->format('Y-m-d') }}</p>
            </div>
        @else
            <p class="mt-4 text-red-600">هیچ اشتراک فعالی یافت نشد.</p>
        @endif
    </div>
</x-filament::page>