<?php

namespace App\Livewire\Filament\Pages\Actions\PettyCash;

use Livewire\Component;
use App\Models\PettyCash;
use Filament\Tables\Table;
use App\Models\Transaction;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class Detail extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;
    public $petty_cash;
    public function mount(PettyCash $petty_cash)
    {
        $this->petty_cash = $petty_cash;
    }
    public function render()
    {
        return view('livewire.filament.pages.actions.petty-cash.detail');
    }
    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->query(Transaction::query()->where('account_type','App\Models\PettyCash')->where('account_id', $this->petty_cash->id)->latest())
            ->columns([
                TextColumn::make('account.name')
                    ->label('حساب')
                    ->description(function (Model $record) {
                        if ($record->account_type == 'App\Models\CompanyBankAccount') {
                            return 'بانک';
                        } elseif ($record->account_type == 'App\Models\Fund')
                        return 'صندوق';
                        elseif ($record->account_type == 'App\Models\PettyCash')
                        return 'تنخواه';
                        elseif ($record->account_type == 'App\Models\Capital')
                        return 'سرمایه';
                        else{
                            return '-';
                        }

                    }),
                TextColumn::make('description')
                    ->label('شرح'),
                // ->formatStateUsing(function(){
                //     // dd($this->transfer);
                //     if($this->transfer->source && $this->transfer->destination)
                //     return 'انتقال وجه از '.$this->transfer->source->name. ' به ' .$this->transfer->destination->name;
                // else
                // return '-';
                // }),
                TextColumn::make('debit')
                    ->label('بدهکار')
                    ->money('irr')
                    ->color(function ($state) {
                        if ($state != 0) {
                            return 'danger';
                        }

                    }),
                TextColumn::make('credit')
                    ->label('بستانکار')
                    ->money('irr')
                    ->color(function ($state) {
                        if ($state != 0) {
                            return 'success';
                        }

                    }),
            ])
            ->filters([
                // ...
            ])
            ->actions([

            ])
            ->bulkActions([
                // ...
            ]);
    }
}