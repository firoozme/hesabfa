<x-filament-panels::page>
    
    <x-filament-panels::form wire:submit="submit">
        {{ $this->form }}

        @if (method_exists($this, 'getCachedFormActions'))
            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        @endif
        <div class="mt-4 flex gap-2">
            @foreach ($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>