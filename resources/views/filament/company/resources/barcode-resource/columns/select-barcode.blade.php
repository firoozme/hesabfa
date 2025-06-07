@php
$record = $getRecord();
$livewire = $getLivewire();
$livewire->temporaryData[$record->id] = $livewire->temporaryData[$record->id] ?? ['errors' => []];
$state = $livewire->temporaryData[$record->id]['selected_barcode'] ?? ($record->barcode[0] ?? null);
$hasError = isset($livewire->temporaryData[$record->id]['errors']['selected_barcode']) && $livewire->temporaryData[$record->id]['errors']['selected_barcode'];
@endphp

<div>
    @php
    @endphp
    <select
        wire:model.live="temporaryData.{{ $record->id }}.selected_barcode"
        wire:change="$dispatch('update-barcode', { id: {{ $record->id }}, value: $event.target.value })"
        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm {{ $hasError ? 'border-red-500' : '' }}"
        style="background-position: right .5rem center !important"
    >
        <option value="">انتخاب کنید</option>
        @php
            if(is_array($record->barcode))
                 $barcodes = $record->barcode;
            else
                $barcodes = explode(',',$record->barcode);

        @endphp
        @foreach ($barcodes as $barcode)
            <option value="{{ $barcode }}" {{ $state === $barcode ? 'selected' : '' }}>{{ $barcode }}</option>
        @endforeach
    </select>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('update-barcode', ({ id, value }) => {
            let livewire = @this;
            livewire.temporaryData[id] = livewire.temporaryData[id] || { errors: {} };
            livewire.temporaryData[id]['selected_barcode'] = value;
            livewire.temporaryData[id]['errors']['selected_barcode'] = value ? null : 'required';
        });
    });
</script>