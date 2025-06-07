@php
setlocale(LC_ALL, 'fa_IR');
use App\Models\ProductType;
use Picqer\Barcode\Types\TypeCode128;
use Picqer\Barcode\Renderers\SvgRenderer;
@endphp
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>محصولات</title>
    <style>
        @font-face {
            font-family: 'Vazir';
            src: url('{{ public_path('fonts/Vazir-Regular.ttf') }}') format('truetype');
            font-weight: normal;
        }
        @font-face {
            font-family: 'Vazir';
            src: url('{{ public_path('fonts/Vazir-Bold.ttf') }}') format('truetype');
            font-weight: bold;
        }
        body {
            font-family: 'Vazir', sans-serif;
            direction: rtl;
            text-align: right;
            margin: 20mm;
            background-color: #f9fafb;
            color: #1f2937;
        }
        .container {
            max-width: 180mm;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        .header h3 {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            margin: 0;
        }
        .header .info {
            font-size: 14px;
            color: #4b5563;
            line-height: 1.6;
        }
        .cards {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: center;
            font-size: 14px;
        }
        th {
            background-color: #1e40af;
            color: #fff;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            background-color: #fff;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:nth-child(even) td {
            background-color: #f9fafb;
        }
        tr:hover td {
            background-color: #f1f5f9;
            transition: background-color 0.3s ease;
        }
        .price {
            font-weight: bold;
            color: #dc2626;
        }

        @media print {
            body {
                margin: 0;
                background-color: #fff;
            }
            .container {
                box-shadow: none;
                padding: 0;
                border-radius: 0;
            }
            .header {
                border-bottom: 1px solid #000;
            }
            .header h3 {
                color: #000;
            }
            .header .info {
                color: #000;
            }
            table {
                width: 100%;
                text-align: center;
            }
            th {
                background-color: #333;
                color: #fff;
            }
            td {
                border-bottom: 1px solid #000;
            }
            tr:nth-child(even) td {
                background-color: #f0f0f0;
            }
            tr:hover td {
                background-color: transparent;
            }
            .price {
                color: #000;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h3>محصولات</h3>
        <div class="info">
            <div>تاریخ: {{ verta()->format('Y/m/d') }}</div>
            <div>شرکت: {{ auth()->user('company')->name ?? 'بدون نام' }}</div>
        </div>
    </div>

    <div class="cards">
      
        <table>
            <thead>
                <tr>
                    <th>نام محصول</th>
                    <th>نوع محصول</th>
                    <th>قیمت خرید</th>
                    <th>قیمت فروش</th>
                </tr>
            </thead>
            <tbody>
              
                @foreach ($products as $product)
                @php
                    $product_type = ProductType::find($product['type']);
                @endphp
                <tr>
                    <td>{{ $product['name'] }}</td>
                    <td>{{ $product_type->title ?? '' }}</td>
                    <td class="price">{{ $product['purchase_price'] }} ریال</td>
                    <td class="price">{{ $product['selling_price'] }} ریال</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</body>
</html>