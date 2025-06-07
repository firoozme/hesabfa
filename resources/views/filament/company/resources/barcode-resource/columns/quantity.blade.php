@php
$record = $getRecord();
$livewire = $getLivewire();
$livewire->temporaryData[$record->id] = $livewire->temporaryData[$record->id] ?? ['errors' => []];
$state = $livewire->temporaryData[$record->id]['quantity'] ?? 0;
$hasError = isset($livewire->temporaryData[$record->id]['errors']['quantity']) && $livewire->temporaryData[$record->id]['errors']['quantity'];
@endphp

<div>
    <input
        type="number"
        wire:model.live="temporaryData.{{ $record->id }}.quantity"
        wire:change="$dispatch('update-quantity', { id: {{ $record->id }}, value: $event.target.value })"
        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm {{ $hasError ? 'border-red-500' : '' }}"
        value="0"
        min="0"
        step="1"
    />
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('update-quantity', ({ id, value }) => {
            let livewire = @this;
            livewire.temporaryData[id] = livewire.temporaryData[id] || { errors: {} };
            livewire.temporaryData[id]['quantity'] = parseInt(value) || 0;
            livewire.temporaryData[id]['errors']['quantity'] = (parseInt(value) <= 0) ? 'min' : null;
        });
    });
</script>