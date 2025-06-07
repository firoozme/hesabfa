@php
setlocale(LC_ALL, 'fa_IR');
use Picqer\Barcode\Types\TypeCode128;
use Picqer\Barcode\Renderers\SvgRenderer;
@endphp
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>بارکدها</title>
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
            background-color: #fff;
            color: #000;
        }
        .container {
            max-width: 180mm;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }
        .header .info {
            font-size: 14px;
            color: #000;
        }
        .cards {
    display: flex;
    flex-wrap: wrap;
   }

.card {
    width: 40mm;
    height: 40mm;
    border: 1px solid #000;
    padding: 5px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-evenly;
    align-items: center;
    font-size: 10pt;
    text-align: center;
    page-break-inside: avoid;
}

        .barcode svg {
            width: 100%;
            height: auto;
        }
        .barcode-value {
            font-weight: bold;
            font-size: 12pt;
        }
        .price {
            margin-top: 4px;
        }

        @media print {
            body {
                margin: 0;
            }
            .cards {
                justify-content: flex-start;
                gap: 8px;
            }
            .card {
                float: right;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h3>بارکد</h3>
        <div class="info">
            <div>تاریخ: {{ verta()->format('Y/m/d') }}</div>
            <div>شرکت: {{ auth()->user('company')->name ?? 'بدون نام' }}</div>
        </div>
    </div>

    <div class="cards">
        @foreach ($barcodes as $barcode)
            @for ($i = 0; $i < $barcode['quantity']; $i++)
                @php
                    $bar = (new TypeCode128())->getBarcode($barcode['barcode']);
                    $renderer = new SvgRenderer();
                    $renderer->setForegroundColor([0, 0, 0]);
                    $renderer->setBackgroundColor([255, 255, 255]);
                    $renderer->setSvgType($renderer::TYPE_SVG_INLINE);
                    $barcodeSvg = $renderer->render($bar, $bar->getWidth() * 1.5, 30);
                    $barcodeSvg = preg_replace('/<\?xml[^>]+>\s*<!DOCTYPE[^>]+>/i', '', $barcodeSvg);
                @endphp
                <div class="card">
                    <div class="company">{{ auth()->user('company')->name ?? 'بدون نام' }}</div>
                    <div class="product">{{ $barcode['name'] }}</div>
                    <div class="barcode">{!! $barcodeSvg !!}</div>
                    <div class="barcode-value">{{ $barcode['barcode'] }}</div>
                    <div class="price">{{ $barcode['selling_price'] }} ریال</div>
                </div>
            @endfor
        @endforeach
    </div>
</div>
</body>
</html>
