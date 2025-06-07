<!-- resources/views/filament/pages/pricing-page.blade.php -->
<x-filament::page>
    <h2 class="text-2xl font-bold mb-6">انتخاب پلن</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-6">
        @foreach ($this->plans() as $plan)
            <div class="border rounded-lg shadow-md p-6 flex flex-col items-center bg-white">
                <h3 class="text-xl font-semibold mb-4">{{ $plan->name }}</h3>
                <p class="text-3xl font-bold mb-4">
                    @if ($plan->price == 0)
                        رایگان
                    @else
                        {{ number_format($plan->price / 10) }} تومان
                    @endif
                </p>
                <p class="text-gray-600 mb-4">مدت: {{ $plan->duration }} روز</p>
                {{-- <ul class="mb-6 text-gray-600 text-center">
                    {!! $plan->features !!}
                </ul> --}}
                @if($plan->is_default)
                    <x-filament::button disabled readonly
                        wire:click=""
                        color="primary"
                        class="w-full">
                        انتخاب پلن
                    </x-filament::button>
                @else
                    <x-filament::button
                    wire:click="selectPlan({{ $plan->id }})"
                    color="primary"
                    class="w-full">
                    انتخاب پلن
                </x-filament::button>
                @endif
            </div>
        @endforeach
    </div>
</x-filament::page>