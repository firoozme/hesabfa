<x-filament-panels::page>
    <x-filament-forms::form wire:submit.prevent="save">
        {{ $this->form }}
        <x-filament-panels::button type="submit">
            ذخیره
        </x-filament-panels::button>
    </x-filament-forms::form>
</x-filament-panels::page>
