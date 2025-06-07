<x-filament-panels::page.simple>
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}
<style>
    .invisible {
    display: none !important;
}
</style>
    <x-filament-panels::form id="form" wire:submit.prevent="verify">
        {{ $this->form }}

        <div class="space-y-4">
            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </div>

        <div class="text-sm text-gray-600 mt-4">
            <span x-data="{ timer: {{ $resendTimer }}, canResend: {{ $canResend ? 'true' : 'false' }} }"
                  x-init="
                      console.log('Timer started:', { timer, canResend });
                      if (timer > 0) {
                          let interval = setInterval(() => {
                              timer--;
                              console.log('Timer:', timer);
                              if (timer <= 0) {
                                  canResend = true;
                                  @this.set('canResend', true);
                                  clearInterval(interval);
                                  console.log('Timer ended, canResend set to true');
                              }
                          }, 1000);
                      }
                  "
                  @reset-timer.window="
                      console.log('Resetting timer');
                      timer = 60;
                      canResend = false;
                      @this.set('canResend', false);
                      let interval = setInterval(() => {
                          timer--;
                          console.log('Timer:', timer);
                          if (timer <= 0) {
                              canResend = true;
                              @this.set('canResend', true);
                              clearInterval(interval);
                              console.log('Timer ended, canResend set to true');
                          }
                      }, 1000);
                  "
                  x-text="timer > 0 && !canResend ? 'ارسال مجدد کد پس از ' + timer + ' ثانیه' : ''"
                  x-bind:class="timer > 0 && !canResend ? 'visible' : 'invisible'">
            </span>
        </div>
    </x-filament-panels::form>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

    <style>
        .filament-actions-action.text-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        /* استایل‌های سفارشی برای هماهنگی با تم Cyan */
        .filament-actions-action {
            background-color: #06b6d4 !important; /* معادل Cyan-500 */
            color: #ffffff !important;
            border-color: #0891b2 !important; /* معادل Cyan-600 */
        }
        .filament-actions-action:hover {
            background-color: #0891b2 !important; /* معادل Cyan-600 */
        }
        .filament-actions-action:disabled {
            background-color: #22d3ee !important; /* معادل Cyan-400 */
            opacity: 0.5;
        }
        .filament-forms-text-input {
            border-color: #0891b2 !important; /* معادل Cyan-600 */
        }
        .filament-forms-text-input:focus {
            ring-color: #06b6d4 !important; /* معادل Cyan-500 */
        }
        .bg-custom-600 {
            --tw-bg-opacity: 1;
            background-color: #06b6d4 !important
        }
        .\[\&\:not\(\:has\(\.fi-ac-action\:focus\)\)\]\:focus-within\:ring-primary-600:focus-within:not(:has(.fi-ac-action:focus)) {
  --tw-ring-opacity: 1;
  --tw-ring-color: #06b6d4;
}
    </style>
</x-filament-panels::page.simple>