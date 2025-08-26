<?php
namespace App\Classes;

use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use EightyNine\ExcelImport\EnhancedDefaultImport;

class ProductImport extends EnhancedDefaultImport
{
    
  
    protected function afterCreateRecord(array $data, $row): void
    {
// dd($data, $row);
    // db::table('store_product')->create([
    //     'store_id' => ,
    //     'product_id' => ,
    // ]);
    }

    protected function afterCollection(Collection $collection): void
    {
        // Show success message with statistics
        $count = $collection->count();
        // $this->stopImportWithSuccess();
        Notification::make()
            ->title("{$count} محصول ثبت شد")
            ->body('')
            ->success()
            ->send();
    }

 

}