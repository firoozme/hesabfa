<x-filament-panels::page.simple>

{{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}


<x-filament-panels::form id="form" wire:submit.prevent="login">

{{ $this->form }}


<div class="space-y-4">

<x-filament-panels::form.actions

:actions="$this->getCachedFormActions()"

:full-width="$this->hasFullWidthFormActions()"

/>

</div>

</x-filament-panels::form>


{{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}


<style>

.filament-actions-action.text-sm {

padding: 0.5rem 1rem;

font-size: 0.875rem;

}

</style>

</x-filament-panels::page.simple> 