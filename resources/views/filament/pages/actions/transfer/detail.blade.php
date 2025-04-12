<h3>تاریخ: {{ verta($record->created_at)->format('Y/m/d') }}</h3>
<h3>شرح: </h3>
<p>{{ $record->description }}</p>
@livewire('filament.pages.actions.transfer.detail', ['transfer' => $record])
