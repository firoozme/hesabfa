<?php

namespace App\Http\Controllers;

use Mpdf\Mpdf;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function generatePdf($id)
    {

        $invoice = Invoice::with('items')->findOrFail($id);

         // تنظیمات MPDF
         $config = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 12,
            'default_font' => 'vazir', // اسم فونت
            'fontDir' => [resource_path('fonts')], // مسیر فونت‌ها
            'fontdata' => [
                'vazir' => [
                    'R' => 'Vazir.ttf', // فایل فونت
                    'useOTL' => 0xFF, // فعال کردن OpenType Layout برای اتصال حروف
                    'useKashida' => 75, // تنظیم کشیدگی حروف
                ],
            ],
            'autoScriptToLang' => true, // تشخیص خودکار زبان
            'autoLangToFont' => true, // انتخاب فونت بر اساس زبان
            'dir' => 'rtl', // جهت راست‌به‌چپ
        ];

        $mpdf = new Mpdf($config);
        $mpdf->WriteHTML(view('pdf.invoice', compact('invoice'))->render());
        return $mpdf->Output('فاکتور_' . $invoice->title . '.pdf', 'D');
    }
}
