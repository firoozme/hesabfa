<x-filament-panels::page.simple>
    <x-filament-panels::form wire:submit="verify">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
            :full-width="true"
        />
    </x-filament-panels::form>
</x-filament-panels::page.simple>