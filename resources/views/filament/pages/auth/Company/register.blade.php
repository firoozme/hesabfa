<x-filament-panels::page.simple>
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit.prevent="register">
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
                      let startTimer = () => {
                          let interval = setInterval(() => {
                              if (timer <= 0) {
                                  canResend = true;
                                  @this.set('canResend', true);
                                  clearInterval(interval);
                              } else {
                                  timer--;
                              }
                          }, 1000);
                      };
                      if (timer > 0) startTimer();
                  "
                  @reset-timer.window="
                      timer = 60;
                      canResend = false;
                      let interval = setInterval(() => {
                          if (timer <= 0) {
                              canResend = true;
                              @this.set('canResend', true);
                              clearInterval(interval);
                          } else {
                              timer--;
                              console.log(timer); // برای دیباگ
                          }
                      }, 1000);
                  "
                  x-text="timer > 0 ? 'ارسال مجدد کد پس از ' + timer + ' ثانیه' : ''">
            </span>
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