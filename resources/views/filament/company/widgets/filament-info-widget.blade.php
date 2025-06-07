<x-filament-widgets::widget class="fi-filament-info-widget">
    <x-filament::section>
        <div class="flex items-center gap-x-3">
            <div class="flex-1">
                <a
                    href=""
                    rel="noopener noreferrer"
                    target="_blank"
                >
                   <h1 class="font-bold">اشتراک فعال</h1>
                </a>

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    نموتک
                </p>
            </div>

            <div class="flex flex-col items-end gap-y-1">


               
                   
                  
                    {{-- {{ __('filament-panels::widgets/filament-info-widget.actions.open_github.label') }} --}}
            </div>
        </div>
        <div>
            @if(auth('company')->user()->subscriptions()->latest()->where('status', 'active')->where('ends_at', '>', now())->first())
            @php
                $subscription = auth('company')->user()->subscriptions()->latest()->where('status', 'active')->where('ends_at', '>', now())->first();
            @endphp
            <div class="mt-6 flex justify-between">
                <p><strong>پلن:</strong> {{ $subscription->plan->name }}</p>
                <p><strong>شروع:</strong> {{ $subscription->starts_at_jalali }}</p>
                <p><strong>پایان:</strong> {{ $subscription->ends_at_jalali }}</p>
            </div>
        @else
            <p class="mt-4 text-red-600">هیچ اشتراک فعالی یافت نشد.</p>
        @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
