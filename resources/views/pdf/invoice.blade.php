<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>فاکتور</title>
    <style>
        @font-face {
            font-family: 'Vazir';
            src: url('{{ public_path('fonts/Vazir.ttf') }}') format('truetype');
        }
        body {
            font-family: 'Vazir', sans-serif;
            direction: rtl;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #f2f2f2;
        }

    </style>
</head>
<body>
    <h1>فاکتور</h1>
    <h3>{{ $invoice->title }}</h3>
    <h6>شماره فاکتور: {{ $invoice->number }}</h6>
    <h6>تاریخ: {{ verta($invoice->date)->format('Y/m/d') }}</h6>
    <h6>نوع: @if($invoice->type=='purchase') خرید @elseif($invoice->type=='sale') فروش  @elseif($invoice->type=='purchase_return') برگشت خرید @elseif($invoice->type=='sale_return') برگشت فروش @endif</h6>
    <h6>انبار: {{ $invoice->store->title ?? ''}}</h6>
    <h6>تامین کننده: {{ $invoice->person->fullname }}</h6>

    <table>
        <thead>
            <tr>
                <th>محصول</th>
                <th>واحد</th>
                <th>تعداد</th>
                <th>قیمت واحد</th>
                <th>تخفیف</th>
                <th>مالیات</th>
                <th>قیمت کل</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'بدون نام' }}</td>
                    <td>{{ $item->product->unit->name ?? 'بدون واحد' }}</td>
                    <td>{{ number_format($item->quantity ?? 0) }}</td>
                    <td>{{ number_format($item->unit_price ?? 0).' ریال ' }}</td>
                    <td>{{ $item->discount ?? 0 }} درصد</td>
                    <td>{{ $item->tax ??  0}} درصد</td>
                    <td>{{ number_format($item->total_price ?? 0).' ریال ' }}</td>
                </tr>
            @endforeach
            <tfoot>
            <tr>
                <th></th>
                <th></th>
                <th>{{ number_format($invoice->items->sum('quantity')) }}</th>
                <th>{{ number_format($invoice->items->sum('unit_price')).' ریال ' }}</th>
                <th></th>
                <th></th>
                <th>{{ number_format($invoice->items->sum('total_price')).' ریال '  }}</th>
            </tr>
        </tfoot>
        </tbody>
    </table>
</body>
</html>
