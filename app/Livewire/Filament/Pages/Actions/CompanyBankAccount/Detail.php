<?php
namespace App\Livewire\Filament\Pages\Actions\CompanyBankAccount;

use Livewire\Component;
use Filament\Tables\Table;
use App\Models\Transaction;
use App\Models\CompanyBankAccount;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;

class Detail extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;
    public $company_bank_account;

    public function mount(CompanyBankAccount $company_bank_account)
    {
        $this->company_bank_account = $company_bank_account;
    }

    public function render()
    {
        return view('livewire.filament.pages.actions.company-bank-account.detail');
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('تراکنشی وجود ندارد')
            ->paginated(false)
            ->query(Transaction::query()->where('account_type', 'App\Models\CompanyBankAccount')->where('account_id', $this->company_bank_account->id)->latest())
            ->columns([
                TextColumn::make('account.name')
                    ->label('حساب')
                    ->description(function (Model $record) {
                        if ($record->account_type == 'App\Models\CompanyBankAccount') {
                            return 'بانک';
                        } elseif ($record->account_type == 'App\Models\Fund') {
                            return 'صندوق';
                        } elseif ($record->account_type == 'App\Models\PettyCash') {
                            return 'تنخواه';
                        } elseif ($record->account_type == 'App\Models\Capital') {
                            return 'سرمایه';
                        } else {
                            return '-';
                        }
                    }),
                TextColumn::make('description')
                    ->label('شرح')
                    ->searchable(), // امکان جستجو در ستون توضیحات
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
                // فیلتر جستجو برای توضیحات
                Filter::make('description')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('description')
                            ->label('جستجو در توضیحات')
                            ->placeholder('جستجو...'),
                    ])
                    ->query(function ($query, array $data) {
                        return $data['description']
                            ? $query->where('description', 'like', '%' . $data['description'] . '%')
                            : $query;
                    }),

             

                // فیلتر تاریخ تراکنش
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('از تاریخ')
                            ->jalali()
                            ->placeholder('انتخاب تاریخ'),
                        DatePicker::make('to_date')
                            ->jalali()
                            ->label('تا تاریخ')
                            ->placeholder('انتخاب تاریخ'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from_date'], fn($q) => $q->whereDate('created_at', '>=', $data['from_date']))
                            ->when($data['to_date'], fn($q) => $q->whereDate('created_at', '<=', $data['to_date']));
                    }),

                // فیلتر برای بدهکار
                TernaryFilter::make('debit')
                    ->label('بدهکار')
                    ->trueLabel('دارای بدهکار')
                    ->falseLabel('بدون بدهکار')
                    ->queries(
                        true: fn($query) => $query->where('debit', '>', 0),
                        false: fn($query) => $query->where('debit', 0),
                        blank: fn($query) => $query
                    ),

                // فیلتر برای بستانکار
                TernaryFilter::make('credit')
                    ->label('بستانکار')
                    ->trueLabel('دارای بستانکار')
                    ->falseLabel('بدون بستانکار')
                    ->queries(
                        true: fn($query) => $query->where('credit', '>', 0),
                        false: fn($query) => $query->where('credit', 0),
                        blank: fn($query) => $query
                    ),

               
            ])
            ->actions([
                // ...
            ])
            ->bulkActions([
                // ...
            ]);
    }
}