<?php

namespace App\Filament\Company\Resources\InventoryCountResource\Pages;

use App\Filament\Company\Resources\InventoryCountResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class ManageInventoryCounts extends ManageRecords
{
    protected static string $resource = InventoryCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('شروع انبارگردانی جدید')
                ->mutateFormDataUsing(function (array $data): array {
                    $groupId = Str::uuid(); // ایجاد یک UUID منحصربه‌فرد برای گروه‌بندی
                    $companyId = auth('company')->id();

                    // ایجاد یک رکورد InventoryCount برای هر محصول در Repeater
                    foreach ($data['items'] as $item) {
                        \App\Models\InventoryCount::create([
                            // 'group_id' => $groupId,
                            'store_id' => $data['store_id'],
                            'product_id' => $item['product_id'],
                            'counted_quantity' => $item['counted_quantity'],
                            'company_id' => $companyId,
                        ]);
                    }

                    // برای جلوگیری از ایجاد رکورد اضافی توسط Filament
                    $this->halt();

                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->title('انبارگردانی ثبت شد')
                        ->body('انبارگردانی برای محصولات انتخاب‌شده با موفقیت ثبت شد.')
                        ->success()
                ),
        ];
    }
}