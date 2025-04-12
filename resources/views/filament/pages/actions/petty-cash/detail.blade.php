<h3>عنوان: {{ $record->name ?? '-' }}</h3>
<h3>شماره کارت: {{ $record->cart_number ?? '-' }}</h3>
<h3>شماره حساب: {{ $record->account_number ?? '-' }}</h3>
<h3>شبا: {{ $record->iban ?? '-' }}</h3>

@livewire('filament.pages.actions.petty-cash.detail', ['petty_cash' => $record])
