<?php

namespace App\Http\Controllers;

use Mpdf\Mpdf;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function generatePdf(Request $request)
    {

        // بازیابی آرایه از سشن
        $barcodes = $request->session()->get('barcodes', []);
        
        if (empty($barcodes)) {
            return redirect()->back()->with('error', 'هیچ داده‌ای برای تولید PDF یافت نشد.');
        }

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
        $mpdf->WriteHTML(view('pdf.barcode', ['barcodes' => $barcodes])->render());
        return $mpdf->Output('بارکد_' . time() . '.pdf', 'D');
    }

    public function generateProductsPdf(Request $request)
    {
        
        // بازیابی آرایه از سشن
        $products = $request->session()->get('products', []);

        if (empty($products)) {
            return redirect()->back()->with('error', 'هیچ داده‌ای برای تولید PDF یافت نشد.');
        }

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
        $mpdf->WriteHTML(view('pdf.products', ['products' => $products])->render());
        return $mpdf->Output('محصولات_' . time() . '.pdf', 'D');
    }

    public function generateProductListsPdf(Request $request)
    {
        
        // بازیابی آرایه از سشن
        $lists = $request->session()->get('lists', []);
        if (empty($lists)) {
            return redirect()->back()->with('error', 'هیچ داده‌ای برای تولید PDF یافت نشد.');
        }

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
        $mpdf->WriteHTML(view('pdf.products_list', ['lists' => $lists])->render());
        return $mpdf->Output('لیست_محصول_' . time() . '.pdf', 'D');
    }
}
